<?php

$secret = $_GET['secret'] ?? '';
if ($secret !== 'migrate2026finanku') {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'message' => 'Forbidden']));
}

header('Content-Type: text/html; charset=utf-8');

define('LARAVEL_START', microtime(true));

$results = [];
$hasError = false;

// Bootstrap Laravel
try {
    require __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
} catch (\Throwable $e) {
    $hasError = true;
    $results[] = ['status' => 'error', 'message' => 'Bootstrap failed: ' . $e->getMessage()];
    renderOutput($results, $hasError);
    exit;
}

// Run migrate
try {
    $output = new \Symfony\Component\Console\Output\BufferedOutput();
    $exitCode = $kernel->call('migrate', ['--force' => true], $output);
    $migrationOutput = $output->fetch();

    if ($exitCode === 0) {
        $results[] = ['status' => 'success', 'message' => 'Migration berhasil', 'detail' => $migrationOutput];
    } else {
        $hasError = true;
        $results[] = ['status' => 'error', 'message' => 'Migration gagal (exit code: ' . $exitCode . ')', 'detail' => $migrationOutput];
    }
} catch (\Throwable $e) {
    $hasError = true;
    $results[] = ['status' => 'error', 'message' => 'Exception: ' . $e->getMessage(), 'detail' => $e->getTraceAsString()];
}

// Run storage:link
try {
    $output2 = new \Symfony\Component\Console\Output\BufferedOutput();
    $kernel->call('storage:link', [], $output2);
    $results[] = ['status' => 'success', 'message' => 'Storage link OK', 'detail' => $output2->fetch()];
} catch (\Throwable $e) {
    $results[] = ['status' => 'warning', 'message' => 'Storage link: ' . $e->getMessage()];
}

// Clear caches
try {
    $kernel->call('config:clear');
    $kernel->call('cache:clear');
    $kernel->call('view:clear');
    $results[] = ['status' => 'success', 'message' => 'Cache cleared'];
} catch (\Throwable $e) {
    $results[] = ['status' => 'warning', 'message' => 'Cache clear: ' . $e->getMessage()];
}

renderOutput($results, $hasError);

function renderOutput(array $results, bool $hasError): void
{
    $elapsed = round(microtime(true) - LARAVEL_START, 2);
    $statusColor = $hasError ? '#dc3545' : '#198754';
    $statusText  = $hasError ? 'SELESAI DENGAN ERROR' : 'BERHASIL';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Romoly — Deploy Runner</title>
    <style>
        body { font-family: monospace; background: #0d1117; color: #c9d1d9; padding: 2rem; margin: 0; }
        h2 { color: #58a6ff; border-bottom: 1px solid #30363d; padding-bottom: .5rem; }
        .card { background: #161b22; border: 1px solid #30363d; border-radius: 6px; padding: 1rem; margin-bottom: 1rem; }
        .success { border-left: 4px solid #198754; }
        .error   { border-left: 4px solid #dc3545; }
        .warning { border-left: 4px solid #e3b341; }
        .badge { display: inline-block; padding: .2rem .6rem; border-radius: 4px; font-size: .8rem; font-weight: bold; margin-bottom: .4rem; }
        .badge-success { background: #198754; color: #fff; }
        .badge-error   { background: #dc3545; color: #fff; }
        .badge-warning { background: #e3b341; color: #000; }
        pre { background: #0d1117; padding: .8rem; border-radius: 4px; overflow-x: auto; font-size: .85rem; color: #8b949e; margin: .5rem 0 0; }
        .summary { color: <?= $statusColor ?>; font-size: 1.2rem; font-weight: bold; }
        .warning-box { background: #3d2b00; border: 1px solid #e3b341; border-radius: 6px; padding: 1rem; margin-top: 1.5rem; color: #e3b341; }
    </style>
</head>
<body>
    <h2>Romoly — Deploy Runner</h2>
    <p class="summary">Status: <?= $statusText ?> (<?= $elapsed ?>s)</p>

    <?php foreach ($results as $result): ?>
    <div class="card <?= $result['status'] ?>">
        <span class="badge badge-<?= $result['status'] ?>"><?= strtoupper($result['status']) ?></span>
        <div><?= htmlspecialchars($result['message']) ?></div>
        <?php if (!empty($result['detail'])): ?>
        <pre><?= htmlspecialchars(trim($result['detail'])) ?></pre>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <div class="warning-box">
        ⚠️ <strong>HAPUS FILE INI SEKARANG</strong> setelah selesai!<br>
        Path: <code>/public/run-migrate.php</code>
    </div>
</body>
</html>
<?php
}
