<?php
/**
 * TEMP deployment diagnostic — safe to expose (never prints secret VALUES).
 * DELETE THIS FILE once the site works.   Open: https://hissabkitaab.com/diag.php
 */
header('Content-Type: text/plain; charset=utf-8');

$public = __DIR__;            // .../public
$root   = dirname($public);   // app root (ROOTPATH) — where .env must live

echo "== ERP deploy diagnostic ==\n\n";
echo "PHP        : " . PHP_VERSION . "\n";
echo "app root   : {$root}\n\n";

echo "App-root contents (must be FOUND):\n";
foreach (['app', 'vendor', 'writable', 'vendor/autoload.php'] as $f) {
    $p = $root . '/' . $f;
    printf("  %-20s %s\n", $f, (is_file($p) || is_dir($p)) ? 'FOUND' : '** MISSING **');
}

$envRootPath   = $root . '/.env';
$envPublicPath = $public . '/.env';
echo "\n.env location:\n";
printf("  app root (CORRECT) : %s\n", is_file($envRootPath) ? 'YES' : 'no');
printf("  public/  (ignored) : %s\n", is_file($envPublicPath) ? 'YES' : 'no');

// mysqli availability
echo "\nmysqli extension : " . (function_exists('mysqli_connect') ? 'available' : '** MISSING **') . "\n";

if (! is_file($envRootPath)) {
    echo "\n>> PROBLEM: no .env in the app root. Put .env in:\n   {$root}\n";
    echo "   (the folder with app/, vendor/, writable/ — NOT inside public/)\n";
    exit;
}

// Parse only the DB keys from .env (values used, never printed)
$cfg = [];
foreach (file($envRootPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
        continue;
    }
    [$k, $v] = array_map('trim', explode('=', $line, 2));
    $v = trim($v, " '\"");
    $cfg[$k] = $v;
}

echo "\nKeys active in .env (values hidden):\n";
foreach (array_keys($cfg) as $k) {
    echo "  {$k}\n";
}

$host = $cfg['database.default.hostname'] ?? '';
$user = $cfg['database.default.username'] ?? '';
$pass = $cfg['database.default.password'] ?? '';
$db   = $cfg['database.default.database'] ?? '';
$port = (int) ($cfg['database.default.port'] ?? 3306);

echo "\nDB config present: host=" . ($host !== '' ? 'yes' : 'NO')
   . " user=" . ($user !== '' ? 'yes' : 'NO')
   . " db=" . ($db !== '' ? 'yes' : 'NO')
   . " pass=" . ($pass !== '' ? 'yes' : 'NO') . "\n";

echo "\nDB connection test:\n";
mysqli_report(MYSQLI_REPORT_OFF);
$conn = @mysqli_connect($host, $user, $pass, $db, $port);
if (! $conn) {
    echo "  ** FAILED: " . mysqli_connect_error() . "\n";
    echo "  (check password / db name / that the DB exists)\n";
} else {
    echo "  CONNECTED OK\n";
    $res = @mysqli_query($conn, "SELECT COUNT(*) AS c FROM users");
    if ($res) {
        $row = mysqli_fetch_assoc($res);
        echo "  users table: FOUND ({$row['c']} rows)\n";
        echo "\n>> Everything looks good. If login still fails, clear writable/cache and retry.\n";
    } else {
        echo "  users table: ** MISSING ** (" . mysqli_error($conn) . ")\n";
        echo "\n>> DB connects but schema not imported. Import deploy/database.sql via phpMyAdmin.\n";
    }
    mysqli_close($conn);
}
