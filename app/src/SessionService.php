<?php
declare(strict_types=1);

namespace App;

use Redis;

class SessionService
{
    private Redis $redis;

    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
    }

    /**
     * Validates and retrieves a session.
     * 
     * [STUDY POINT]: We use the binary Redis protocol for retrieval.
     * Complexity: O(1) - Constant time lookups are the key to 20k RPS.
     */
    public function getSession(int $userId): ?array
    {
        $key = "user:session:{$userId}";
        $data = $this->redis->get($key);

        if (!$data) {
            return null;
        }

        // [STUDY POINT]: JSON decoding happens after retrieval. 
        // In ultra-high perf systems, you'd use MessagePack to save CPU here.
        $session = json_decode($data, true);
        
        // Simulate minor business logic overhead
        if (isset($session['status']) && $session['status'] === 'active') {
            $session['validated_at'] = microtime(true);
            return $session;
        }

        return null;
    }

    /**
     * Retrieves database performance statistics.
     * 
     * [STUDY POINT]: DragonflyDB provides Redis-compatible INFO command.
     * We monitor 'instantaneous_ops_per_sec' to verify our 300k OPS target.
     * 
     * [OPTIMIZATION]: We cache this result in PHP memory for 1 second to avoid
     * hitting the database with expensive INFO commands 1000s of times per second.
     */
    private static array $cachedStats = [];
    private static int $lastCacheTime = 0;

    public function getStats(): array
    {
        $now = time();
        if ($now - self::$lastCacheTime < 1 && !empty(self::$cachedStats)) {
            return self::$cachedStats;
        }

        $info = $this->redis->info();
        
        self::$cachedStats = [
            'ops_per_sec' => (int)($info['instantaneous_ops_per_sec'] ?? 0),
            'memory_used' => $info['used_memory_human'] ?? '0B',
            'total_keys' => $this->redis->dbSize(),
            'engine' => $info['redis_version'] ?? 'dragonfly',
            'timestamp' => $now,
        ];
        self::$lastCacheTime = $now;

        return self::$cachedStats;
    }
}
