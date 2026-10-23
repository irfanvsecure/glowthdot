{{-- New website enquiry. $lead: page, url, received_at, fields (label => value). $waLink: wa.me link or null. --}}
<div style="font-family:Arial,Helvetica,sans-serif;max-width:600px;margin:auto;border:1px solid #ddd;border-radius:15px;overflow:hidden;">
    <div style="background:linear-gradient(90deg,#FF2E7E,#7C3AED);background-color:#FF2E7E;padding:25px;color:#ffffff;">
        <h2 style="margin:0;">🎯 New Glowth {{ $lead['form_id'] === 'get-a-free-audit' ? 'Audit Lead' : 'Website Enquiry' }}</h2>
        <p style="margin:6px 0 0;opacity:.9;font-size:13px;">{{ $lead['page'] }} · {{ \Illuminate\Support\Carbon::parse($lead['received_at'])->timezone('Asia/Dubai')->format('d M Y, H:i') }} (UAE)</p>
    </div>
    <table style="width:100%;border-collapse:collapse;font-size:14px;">
        @foreach ($lead['fields'] as $label => $value)
            <tr>
                <td style="padding:10px 20px;border-bottom:1px solid #eee;width:40%;"><b>{{ $label }}</b></td>
                <td style="padding:10px 20px;border-bottom:1px solid #eee;">
                    @if ($waLink && $value === $waValue)
                        <a href="{{ $waLink }}">{{ $value }}</a>
                    @else
                        {{ $value !== '' ? $value : '—' }}
                    @endif
                </td>
            </tr>
        @endforeach
        @if ($lead['url'] !== '')
            <tr>
                <td style="padding:10px 20px;width:40%;"><b>Sent from</b></td>
                <td style="padding:10px 20px;"><a href="{{ $lead['url'] }}">{{ $lead['url'] }}</a></td>
            </tr>
        @endif
    </table>
    @if ($waLink)
        <div style="text-align:center;padding:20px;">
            <a href="{{ $waLink }}" style="background:#25D366;color:#ffffff;padding:12px 25px;border-radius:8px;text-decoration:none;display:inline-block;">Reply on WhatsApp</a>
        </div>
    @endif
</div>
