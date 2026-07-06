<?php
/**
 * TEMP deployment diagnostic — safe to expose (never prints secret VALUES).
 * DELETE once the site works.   Open: https://hissabkitaab.com/diag.php
 */
header('Content-Type: text/plain; charset=utf-8');
echo "NANANANANANANANANANANANANANANANANANANANANA";
$public = __DIR__;
$root   = dirname($public);

echo "== ERP deploy diagnostic ==\n\n";
echo "PHP      : " . PHP_VERSION . "\n";
echo "app root : {$root}\n\n";

/* ---- is the latest code deployed? ---- */
echo "Code markers (confirm the deploy pulled the newest commit):\n";
printf("  AutoSetup filter : %s\n", is_file($root . '/app/Filters/AutoSetup.php') ? 'PRESENT' : '** NOT DEPLOYED **');
printf("  autosetup in Filters.php : %s\n",
    (is_file($root . '/app/Config/Filters.php') && strpos((string) file_get_contents($root . '/app/Config/Filters.php'), 'autosetup') !== false)
        ? 'yes' : '** NO **');

/* ---- writable perms ---- */
echo "\nwritable/ permissions:\n";
foreach (['writable', 'writable/cache', 'writable/session', 'writable/logs'] as $d) {
    $p = $root . '/' . $d;
    echo '  ' . str_pad($d, 18) . (! file_exists($p) ? 'MISSING' : (is_writable($p) ? 'writable' : '** NOT WRITABLE **')) . "\n";
}

/* ---- DB: does notifications table exist now? ---- */
echo "\nDatabase tables:\n";
$envRoot = $root . '/.env';
$cfg = [];
if (is_file($envRoot)) {
    foreach (file($envRoot, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
        [$k, $v] = array_map('trim', explode('=', $line, 2));
        $cfg[$k] = trim($v, " '\"");
    }
}
mysqli_report(MYSQLI_REPORT_OFF);
$conn = @mysqli_connect($cfg['database.default.hostname'] ?? 'localhost', $cfg['database.default.username'] ?? '', $cfg['database.default.password'] ?? '', $cfg['database.default.database'] ?? '', (int) ($cfg['database.default.port'] ?? 3306));
if (! $conn) {
    echo "  ** DB connect failed: " . mysqli_connect_error() . "\n";
} else {
    $have = [];
    if ($res = mysqli_query($conn, "SHOW TABLES")) {
        while ($r = mysqli_fetch_row($res)) $have[] = $r[0];
    }
    printf("  notifications table : %s\n", in_array('notifications', $have, true) ? 'EXISTS' : '** MISSING **');
    printf("  total tables : %d\n", count($have));
    if ($res = mysqli_query($conn, "SELECT version FROM migrations ORDER BY id")) {
        $v = [];
        while ($r = mysqli_fetch_row($res)) $v[] = $r[0];
        echo "  migrations recorded : " . (count($v) ? implode(', ', $v) : '(none)') . "\n";
    }
    mysqli_close($conn);
}

/* ---- latest error log (safe) ---- */
echo "\n== latest error log ==\n";
$logs = @glob($root . '/writable/logs/log-*.php') ?: [];
if (! $logs) {
    echo "  (no readable log files)\n";
} else {
    usort($logs, static fn ($a, $b) => filemtime($b) <=> filemtime($a));
    $raw = @file($logs[0], FILE_IGNORE_NEW_LINES);
    if ($raw === false) { $raw = []; echo "  (log exists but is not readable — permissions)\n"; }
    echo "  file: " . basename($logs[0]) . "\n\n";
    foreach (array_slice($raw, -45) as $l) {
        if (strpos($l, '<' . '?php') !== false) continue;
        echo $l . "\n";
    }
}
