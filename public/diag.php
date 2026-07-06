<?php
/**
 * TEMP deployment diagnostic — safe to expose (shows file locations only,
 * never secret values). DELETE THIS FILE once the site is working.
 *
 * Open:  https://hissabkitaab.com/diag.php
 */
header('Content-Type: text/plain; charset=utf-8');

$public = __DIR__;                 // .../public
$root   = dirname($public);        // app root (ROOTPATH) — where .env must live

echo "== ERP deploy diagnostic ==\n\n";
echo "PHP version : " . PHP_VERSION . "\n";
echo "public dir  : {$public}\n";
echo "app root    : {$root}\n\n";

echo "App-root contents (these must be FOUND):\n";
foreach (['app', 'vendor', 'writable', 'spark', 'vendor/autoload.php'] as $f) {
    $p = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $f);
    printf("  %-22s %s\n", $f, is_file($p) || is_dir($p) ? 'FOUND' : '** MISSING **');
}

echo "\n.env location check:\n";
$envRoot   = is_file($root . DIRECTORY_SEPARATOR . '.env');     // CORRECT place
$envPublic = is_file($public . DIRECTORY_SEPARATOR . '.env');   // wrong place
printf("  .env in app root (CORRECT) : %s\n", $envRoot ? 'YES' : 'no');
printf("  .env in public/  (ignored) : %s\n", $envPublic ? 'YES' : 'no');

if ($envRoot) {
    // Show only the KEYS present, never the values (no secrets leaked).
    echo "\nKeys active in .env (values hidden):\n";
    foreach (file($root . DIRECTORY_SEPARATOR . '.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        $key = trim(explode('=', $line, 2)[0]);
        echo "  {$key}\n";
    }
    echo "\n>> .env IS in the right place. If the site still misbehaves, the keys above are what CI4 sees.\n";
} else {
    echo "\n>> PROBLEM: no .env in the app root. Put your .env in:\n   {$root}\n";
    echo "   (the folder that contains app/, vendor/, writable/ — NOT inside public/)\n";
}
