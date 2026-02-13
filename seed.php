<?php
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

$redis = new Redis();
try {
    $redis_host = getenv('REDIS_HOST') ?: 'cache';
    $redis->connect($redis_host, 6379);
} catch (Exception $e) {
    echo "❌ Could not connect to Redis at " . ($redis_host ?? 'cache') . ":6379\n";
    exit(1);
}

$count = 10_000_000;
$batchSize = 10_000;

echo "🚀 Starting PHP seed of $count keys...\n";
$start = microtime(true);

for ($i = 0; $i < ($count / $batchSize); $i++) {
    $pipe = $redis->multi(Redis::PIPELINE);
    for ($j = 0; $j < $batchSize; $j++) {
        $keyIdx = $i * $batchSize + $j;
        $pipe->set("user:session:$keyIdx", json_encode([
            'status' => 'active',
            'role' => 'senior',
            'seeded_at' => time()
        ]));
    }
    $pipe->exec();
    
    if ($i % 100 === 0) {
        $progress = round(($i / ($count / $batchSize)) * 100);
        echo "✅ Progress: $progress%\r";
    }
}

$elapsed = microtime(true) - $start;
echo "\n✨ Finished in " . round($elapsed, 2) . "s. Speed: " . round($count / $elapsed) . " keys/sec\n";
