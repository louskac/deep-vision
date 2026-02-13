<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\SessionService;

// Initialize outside the loop for "Worker Mode"
$redis = new Redis();
try {
    $redis_host = getenv('REDIS_HOST') ?: 'cache';
    $redis_port = (int)(getenv('REDIS_PORT') ?: 6379);
    $redis->pconnect($redis_host, $redis_port); // Persistent connection
} catch (Exception $e) {
    // Fallback for local dev if 'cache' host is not reachable
    try {
        $redis->pconnect('127.0.0.1', 6379);
    } catch (Exception $e) {
        // Just continue, we'll handle the error during requests
    }
}

$sessionService = new SessionService($redis);

$handler = static function () use ($sessionService) {
    $path = $_SERVER['REQUEST_URI'] ?? '/';

    if (str_starts_with($path, '/user/session/')) {
        $userId = (int)str_replace('/user/session/', '', $path);
        $session = $sessionService->getSession($userId);

        if ($session === null) {
            header('HTTP/1.1 404 Not Found');
            echo json_encode(['error' => 'Session not found']);
            return;
        }

        header('Content-Type: application/json');
        echo json_encode($session);
    } elseif ($path === '/stats') {
        $stats = $sessionService->getStats();
        header('Content-Type: application/json');
        echo json_encode($stats);
    } else {
        // Modern Dashboard UI
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>High-Perf PHP Worker | Dashboard</title>
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;600&family=JetBrains+Mono&display=swap" rel="stylesheet">
            <style>
                :root {
                    --bg: #0a0a0c;
                    --card-bg: #16161a;
                    --primary: #00ff88;
                    --secondary: #00ddeb;
                    --text: #e0e0e6;
                    --muted: #888891;
                }
                body {
                    background: var(--bg);
                    color: var(--text);
                    font-family: 'Outfit', sans-serif;
                    margin: 0;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    min-height: 100vh;
                    overflow: hidden;
                }
                .container {
                    width: 90%;
                    max-width: 800px;
                    text-align: center;
                }
                h1 {
                    font-weight: 600;
                    font-size: 2.5rem;
                    margin-bottom: 0.5rem;
                    background: linear-gradient(45deg, var(--primary), var(--secondary));
                    -webkit-background-clip: text;
                    -webkit-text-fill-color: transparent;
                }
                .grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                    gap: 1.5rem;
                    margin-top: 3rem;
                }
                .card {
                    background: var(--card-bg);
                    padding: 2rem;
                    border-radius: 16px;
                    border: 1px solid rgba(255, 255, 255, 0.05);
                    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
                    transition: transform 0.3s ease, border-color 0.3s ease;
                }
                .card:hover {
                    transform: translateY(-5px);
                    border-color: var(--primary);
                }
                .label {
                    color: var(--muted);
                    font-size: 0.8rem;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                    margin-bottom: 0.5rem;
                }
                .value {
                    font-family: 'JetBrains Mono', monospace;
                    font-size: 1.8rem;
                    font-weight: 600;
                    color: var(--primary);
                }
                .status {
                    margin-top: 2rem;
                    font-size: 0.9rem;
                    color: var(--muted);
                }
                .status span {
                    color: var(--secondary);
                    font-weight: 600;
                }
            </style>
            <script>
                async function updateStats() {
                    try {
                        const res = await fetch('/stats');
                        const data = await res.json();
                        document.getElementById('ops').innerText = data.ops_per_sec.toLocaleString();
                        document.getElementById('mem').innerText = data.memory_used;
                        document.getElementById('keys').innerText = (data.total_keys / 1000000).toFixed(1) + 'M';
                    } catch (e) {
                        console.error('Failed to update stats');
                    }
                }
                setInterval(updateStats, 1000);
            </script>
        </head>
        <body>
            <div class="container">
                <h1>🚀 High-Performance PHP Worker</h1>
                <p style="color: var(--muted)">FrankenPHP + DragonflyDB Real-time Metrics</p>
                
                <div class="grid">
                    <div class="card">
                        <div class="label">Throughput / sec</div>
                        <div class="value" id="ops">--</div>
                    </div>
                    <div class="card">
                        <div class="label">Memory Usage</div>
                        <div class="value" id="mem">--</div>
                    </div>
                    <div class="card">
                        <div class="label">Total Keys</div>
                        <div class="value" id="keys">--</div>
                    </div>
                </div>

                <div class="status">
                    Runtime: <span>FrankenPHP (Worker Mode)</span> &nbsp;|&nbsp; 
                    Mode: <span>Production</span>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
};

if (function_exists('frankenphp_handle_request')) {
    // Worker mode
    $nbRequests = 0;
    while (frankenphp_handle_request($handler)) {
        // Optional: gc_collect_cycles() or other cleanup every N requests
        $nbRequests++;
    }
} else {
    // Normal PHP mode (CLI or other SAPI)
    $handler();
}