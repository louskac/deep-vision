<?php
declare(strict_types=1);

/**
 * Basic verification script to check connectivity and latency.
 */

$host = 'http://localhost:80';

echo "🔍 Starting verification tests...\n";

// 1. Check Connectivity
$start = microtime(true);
$response = @file_get_contents("$host/stats");
$elapsed = (microtime(true) - $start) * 1000;

if ($response === false) {
    echo "❌ [FAIL] Cannot connect to $host/stats\n";
    exit(1);
}

$stats = json_decode($response, true);
echo "✅ [PASS] Connected to PHP Worker in " . round($elapsed, 2) . "ms\n";
echo "📊 Current DB Keys: " . ($stats['total_keys'] ?? 'unknown') . "\n";

// 2. Check Session Retrieval
$userId = rand(0, 1000000);
$start = microtime(true);
$response = @file_get_contents("$host/user/session/$userId");
$elapsed = (microtime(true) - $start) * 1000;

if ($response === false) {
    echo "⚠️ [WARN] Session $userId not found (expected if seeding not finished)\n";
} else {
    echo "✅ [PASS] Session retrieval latency: " . round($elapsed, 2) . "ms\n";
}

// 3. Performance Target Check
if (($stats['ops_per_sec'] ?? 0) > 0) {
    echo "🚀 Engine is processing " . $stats['ops_per_sec'] . " OPS/sec\n";
}

echo "\n✨ Verification complete!\n";
