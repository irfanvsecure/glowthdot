<?php

$f = dirname(__DIR__).'/storage/app/live-html/home.html';
$html = file_get_contents($f);

$css = <<<'CSS'
<style>
    /* News Bar */
.nichebar {
    width: 100%;
    overflow: hidden;
    background: #000;
    color: #fff;
    padding: 14px 0;
    white-space: nowrap;
    position: relative;
}

.ticker {
    display: inline-flex;
    align-items: center;
    gap: 24px;
    animation: tickerScroll 35s linear infinite;
    width: max-content;
}

.ticker span {
    font-size: 14px;
    font-weight: 600;
    letter-spacing: 1px;
    text-transform: uppercase;
    flex-shrink: 0;
}

.separator {
    color: #c6f432;    
    font-size: 12px;
}

@keyframes tickerScroll {
    from {
        transform: translateX(0);
    }
    to {
        transform: translateX(-50%);
    }
}

/* Pause animation on hover */
.nichebar:hover .ticker {
    animation-play-state: paused;
}
</style>
CSS;

// Already has full nichebar news bar style?
if (str_contains($html, '/* News Bar */') && str_contains($html, '@keyframes tickerScroll')) {
    echo "nichebar CSS already present\n";
} else {
    // Insert after closing </div> of nichebar (after ticker's parent)
    // Pattern: nichebar ... </div>\n</div> then maybe missing style
    $replaced = preg_replace(
        '#(<div class="nichebar">.*?</div>\s*</div>)(\s*)(</div>)#s',
        '$1'."\n".$css.'$2$3',
        $html,
        1,
        $count
    );
    if ($count < 1) {
        // simpler: after last separator HAIR CLINICS block closes nichebar
        $replaced = preg_replace(
            '#(class="nichebar">[\s\S]*?</div>\s*</div>)#',
            '$1'."\n".$css,
            $html,
            1,
            $count
        );
    }
    if ($count > 0) {
        file_put_contents($f, $replaced);
        echo "nichebar CSS restored ($count)\n";
        $html = $replaced;
    } else {
        // append before </body> as fallback
        $html = str_replace('</body>', $css.'</body>', $html);
        file_put_contents($f, $html);
        echo "nichebar CSS appended before body end\n";
    }
}

// Fix audit CTA to use en-dash like typical live
$html = file_get_contents($f);
$html = str_replace('Get My SEO Audit — Free', 'Get My SEO Audit – Free', $html);
file_put_contents($f, $html);

echo 'has News Bar: '.(str_contains(file_get_contents($f), '/* News Bar */') ? 'yes' : 'no')."\n";
echo 'has tickerScroll: '.(str_contains(file_get_contents($f), 'tickerScroll') ? 'yes' : 'no')."\n";
