<?php

/**
 * Writes a .webp copy next to every JPG/PNG in public/images (photo.jpg -> photo.jpg.webp).
 * public/.htaccess serves the copy to browsers that accept WebP; the HTML keeps the original paths.
 * Skips copies that are already up to date and ones that would not be smaller.
 * Usage: php -d memory_limit=2G scripts/make-webp.php
 */

$root = dirname(__DIR__).'/public/images';
$quality = 80;
$saved = 0;
$made = 0;
$failed = [];

$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($files as $file) {
    $path = $file->getPathname();
    if (! preg_match('/\.(jpe?g|png)$/i', $path)) {
        continue;
    }
    $out = $path.'.webp';
    if (is_file($out) && filemtime($out) >= filemtime($path)) {
        continue;
    }

    $img = preg_match('/\.png$/i', $path) ? @imagecreatefrompng($path) : @imagecreatefromjpeg($path);
    if (! $img) {
        $failed[] = substr($path, strlen($root));

        continue;
    }
    if (! imageistruecolor($img)) {
        imagepalettetotruecolor($img);
    }
    imagealphablending($img, false);
    imagesavealpha($img, true);

    $tmp = $out.'.tmp';
    imagewebp($img, $tmp, $quality);
    imagedestroy($img);

    $before = filesize($path);
    $after = filesize($tmp);
    if ($after > 0 && $after < $before) {
        rename($tmp, $out);
        $saved += $before - $after;
        $made++;
    } else {
        unlink($tmp);
    }
}

printf("WebP copies written: %d, saved %.1f MB\n", $made, $saved / 1048576);
if ($failed) {
    echo "Could not read (broken file?):\n  ".implode("\n  ", $failed)."\n";
}
