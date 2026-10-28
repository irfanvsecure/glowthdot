<?php

namespace App\Http\Controllers;

use App\Support\SiteRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * Receives the website's enquiry forms (POST /api/enquiry, sent by public/js/site.js) and the
 * audit funnel (POST /api/audit). Answers in the shape site.js shows under the form.
 */
class LeadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $fields = array_map(
            fn ($v) => is_string($v) ? trim(mb_substr($v, 0, 2000)) : '',
            (array) $request->input('form_fields', [])
        );

        // The browser already enforces each form's own required fields
        if (implode('', $fields) === '') {
            return response()->json([
                'success' => false,
                'data' => ['message' => 'Please fill in the form before sending.', 'errors' => [], 'data' => []],
            ]);
        }

        $url = (string) $request->headers->get('referer', '');
        $labels = $this->labels($url);
        $named = [];
        foreach ($fields as $key => $value) {
            $named[$labels[$key] ?? $key] = $value;
        }

        $lead = [
            'received_at' => now()->toIso8601String(),
            'page' => (string) $request->input('referer_title', ''),
            'url' => $url,
            'form_id' => (string) $request->input('form_id', ''),
            'fields' => $named,
        ];

        $this->save($lead, $request);

        return response()->json([
            'success' => true,
            'data' => ['message' => 'Thank you! We will reach out on WhatsApp within 48 hours.', 'data' => []],
        ]);
    }

    /**
     * The multi-step form on /get-a-free-audit/ (posts JSON).
     */
    public function audit(Request $request): JsonResponse
    {
        // Honeypot: real visitors never see this field
        if (trim((string) $request->input('website_hp', '')) !== '') {
            return response()->json(['success' => true]);
        }

        $fields = [
            'Your Name' => 'name',
            'Salon / clinic name' => 'bizName',
            'WhatsApp number' => 'wa',
            'Website' => 'url',
            'Business type' => 'biz',
            'Main goal' => 'goal',
            'Monthly marketing spend' => 'budget',
            'Emirate' => 'emirate',
        ];
        $named = [];
        foreach ($fields as $label => $key) {
            $value = $request->input($key);
            $named[$label] = is_string($value) ? trim(mb_substr($value, 0, 500)) : '';
        }

        if ($named['Your Name'] === '' || $named['WhatsApp number'] === '') {
            return response()->json(['success' => false, 'message' => 'Name and WhatsApp number are required.'], 422);
        }

        $lead = [
            'received_at' => now()->toIso8601String(),
            'page' => 'Free Growth Audit',
            'url' => (string) $request->headers->get('referer', ''),
            'form_id' => 'get-a-free-audit',
            'fields' => $named,
        ];
        $this->save($lead, $request);

        // The Google Sheet's column layout
        $sheet = array_combine(array_values($fields), array_values($named)) + [
            'ip' => (string) $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 300),
        ];
        defer(fn () => $this->sendToSheet($sheet));

        return response()->json(['success' => true]);
    }

    private function save(array $lead, Request $request): void
    {
        // Kept even when mail fails, so no enquiry is ever lost
        Storage::append('leads/'.now()->format('Y-m').'.jsonl', json_encode(
            $lead + ['ip' => (string) $request->ip()],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ));

        // Mail goes out after the visitor already has their answer
        defer(fn () => $this->mail($lead));
    }

    private function mail(array $lead): void
    {
        $to = config('services.leads.to');
        if (! $to) {
            return;
        }

        // The first field that looks like a phone number gets a wa.me reply link
        $waValue = null;
        foreach ($lead['fields'] as $label => $value) {
            if (preg_match('/whatsapp|phone|mobile|number/i', $label) && preg_match('/\d{6,}/', preg_replace('/\D/', '', $value))) {
                $waValue = $value;
                break;
            }
        }
        $waLink = $waValue !== null ? 'https://wa.me/'.preg_replace('/\D/', '', $waValue) : null;

        $who = array_values(array_filter([
            $lead['fields']['Salon / clinic name'] ?? '',
            reset($lead['fields']) ?: '',
        ]));
        $subject = ($lead['form_id'] === 'get-a-free-audit' ? 'New Growth Audit Lead' : 'New website enquiry')
            .($who ? ' - '.implode(' - ', array_unique($who)) : '');

        try {
            Mail::send('emails.lead', ['lead' => $lead, 'waLink' => $waLink, 'waValue' => $waValue], function ($m) use ($to, $subject) {
                $m->to(array_map('trim', explode(',', $to)))->subject($subject);
                if ($replyTo = config('services.leads.reply_to')) {
                    $m->replyTo($replyTo);
                }
            });
        } catch (\Throwable $e) {
            Log::error('Lead mail failed: '.$e->getMessage());
        }
    }

    private function sendToSheet(array $row): void
    {
        $url = config('services.leads.sheet_url');
        if (! $url) {
            return;
        }

        try {
            $response = Http::timeout(30)->asJson()->post($url, $row);
            if ($response->failed()) {
                Log::error('Lead sheet failed: HTTP '.$response->status());
            }
        } catch (\Throwable $e) {
            Log::error('Lead sheet failed: '.$e->getMessage());
        }
    }

    /**
     * Field labels ("Salon / clinic name") keyed by form field id ("field_877ea9e"),
     * read from the page the form was sent from.
     */
    private function labels(string $url): array
    {
        $found = SiteRenderer::resolve((string) parse_url($url, PHP_URL_PATH));
        $html = match ($found[0] ?? null) {
            'page' => $found[1]->sections->map->html()->implode("\n"),
            'post' => $found[1]->content,
            default => '',
        };

        preg_match_all('#<label for="form-field-([a-z0-9_]+)"[^>]*>(.*?)</label>#s', $html, $m, PREG_SET_ORDER);
        $labels = [];
        foreach ($m as [, $id, $text]) {
            $labels[$id] = trim(html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5));
        }

        return $labels;
    }
}
