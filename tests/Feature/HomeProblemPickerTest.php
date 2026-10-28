<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomeProblemPickerTest extends TestCase
{
    public function test_packages_are_replaced_by_the_problem_picker(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('k-5aea8b3', $html);
        $this->assertStringNotContainsString('priced to your chair count', $html);
        $this->assertSame(1, substr_count($html, 'id="find-your-fix"'));
        $this->assertSame(1, preg_match('#<section class="gl-fix".*?</section>#s', $html, $section));
        $this->assertSame(5, substr_count($section[0], 'role="tab" id="gl-fix-tab-'));
        $this->assertSame(5, substr_count($section[0], 'role="tabpanel"'));
        // Only the first answer shows before the script runs
        $this->assertSame(4, preg_match_all('#role="tabpanel"[^>]*\shidden>#', $section[0]));
    }

    public function test_every_fix_opens_the_funnel_with_a_goal_it_knows(): void
    {
        $home = $this->get('/')->getContent();
        $funnel = $this->get('/get-a-free-audit/')->getContent();

        preg_match_all('#href="/get-a-free-audit/\?goal=([^"]+)"#', $home, $m);
        $this->assertCount(6, $m[1]);
        foreach ($m[1] as $goal) {
            $this->assertStringContainsString('data-v="'.rawurldecode($goal).'"', $funnel, "funnel has no \"$goal\" goal");
        }
        $this->assertStringContainsString("params.get('goal')", file_get_contents(public_path('js/pages/get-a-free-audit.js')));
    }
}
