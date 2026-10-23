<?php

$r = json_decode(file_get_contents(dirname(__DIR__).'/storage/app/live-seo/audit-report.json'), true);
$real = [];
foreach ($r['broken_links'] as $b) {
    $e = $b['expected'];
    $h = $b['href'];
    if (str_contains($e, 'feed') || str_contains($e, 'xmlrpc') || str_contains($e, 'wp-')
        || str_contains($e, 'fonts.') || str_contains($e, 'author-')
        || preg_match('#page-\d+#', $e) || str_ends_with($e, '.php.html')) {
        continue;
    }
    if (str_starts_with($h, '//') && ! str_contains($h, 'glowth')) {
        continue;
    }
    if (str_contains($h, 'fonts.googleapis') || str_contains($h, 'fonts.gstatic')) {
        continue;
    }
    $real[] = $b;
}
echo 'Real broken: '.count($real).PHP_EOL;
$byTarget = [];
foreach ($real as $b) {
    $byTarget[$b['expected']][] = $b['from'].' via '.$b['href'];
}
ksort($byTarget);
foreach ($byTarget as $t => $froms) {
    echo $t.' ('.count($froms).')'.PHP_EOL;
    echo '  e.g. '.$froms[0].PHP_EOL;
}
