<?php
$h = file_get_contents(__DIR__.'/../storage/app/live-html/home.html');
if (preg_match_all('/\.ast-container\{[^}]+\}/', $h, $m)) {
  foreach (array_unique($m[0]) as $r) echo $r."\n";
}
if (preg_match_all('/site-primary-header[^{]*\{[^}]+\}/', $h, $m)) {
  foreach (array_slice(array_unique($m[0]),0,5) as $r) echo $r."\n";
}
// from astra main
$c = file_get_contents(__DIR__.'/../public/assets/themes/astra/assets/css/minified/main.min.css');
if (preg_match_all('/\.ast-container\{[^}]+\}/', $c, $m)) {
  echo "ASTRA:\n";
  foreach ($m[0] as $r) echo $r."\n";
}
if (preg_match('/@media \(min-width:922px\)\{\.ast-container\{[^}]+\}/', $c, $m)) echo $m[0]."\n";
