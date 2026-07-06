<?php
/**
 * TEMP deployment diagnostic — safe to expose (never prints secret VALUES).
 * DELETE THIS FILE once the site works.   Open: https://hissabkitaab.com/diag.php
 */
header('Content-Type: text/plain; charset=utf-8');

$public = __DIR__;
$root   = dirname($public);

echo "== ERP deploy diagnostic ==\n\n";
echo "PHP      : " . PHP_VERSION . "\n";
echo "app root : {$root}\n\n";

/* ---- writable permissions (a common cause of login 500) ---- */
echo "writable/ permission check (must all be WRITABLE):\n";
foreach (['writable', 'writable/cache', 'writable/session', 'writable/logs', 'writable/uploads'] as $d) {
    $p = $root . '/' . $d;
    $state = ! file_exists($p) ? 'MISSING' : (is_writable($p) ? 'writable' : '** NOT WRITABLE **');
    printf("  %-20s %s\n", $d, $state);
}

/* ---- .env presence ---- */
echo "\n.env in app root : " . (is_file($root . '/.env') ? 'YES' : 'no') . "\n";

/* ---- latest error log (the real reason for the 500) ---- */
echo "\n== latest error log ==\n";
$logDir = $root . '/writable/logs';
$logs = glob($logDir . '/log-*.php');
if (! $logs) {
    echo "  (no log files yet — trigger the error once, then reload this page)\n";
} else {
    usort($logs, static fn ($a, $b) => filemtime($b) <=> filemtime($a));
    $newest = $logs[0];
    echo "  file: " . basename($newest) . "\n\n";
    $lines = file($newest, FILE_IGNORE_NEW_LINES);
    // skip the leading "<?php ... ?>" guard line CI4 writes
    $lines = array_values(array_filter($lines, static fn ($l) => strpos($l, '<' . '?php') === false && strpos($l, 'exit;') === false));
    foreach (array_slice($lines, -45) as $l) {
        echo $l . "\n";
    }
}
