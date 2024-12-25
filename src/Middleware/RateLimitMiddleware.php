<?php

namespace App\Middleware;

use App\Exceptions\HttpException;
use App\Interfaces\CacheInterface;
use App\Cache\RedisClient;
use Closure;

class RateLimitMiddleware
{
    private array $config;
    private string $cachePrefix = 'rate_limit:';
    private CacheInterface $cache;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->cache = new RedisClient();
    }

    public function handle(array $request, Closure $next)
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        $key = $this->cachePrefix . $ip;

        $requestsPerMinute = $this->config['requests_per_minute'] ?? 60;
        $windowMinutes = $this->config['window_minutes'] ?? 1;
        $windowSeconds = $windowMinutes * 60;

        try {
            // Get current request count from cache
            $requestData = $this->cache->get($key);
            $currentTime = time();

            $requests = $requestData ? json_decode($requestData, true) : [
                'count' => 0,
                'window_start' => $currentTime
            ];

            // Reset if window has expired
            if ($currentTime - $requests['window_start'] >= $windowSeconds) {
                $requests = [
                    'count' => 0,
                    'window_start' => $currentTime
                ];
            }

            // Increment request count
            $requests['count']++;

            // Store updated count with expiration
            $this->cache->setex(
                $key,
                $windowSeconds,
                json_encode($requests)
            );

            // Check if limit exceeded
            if ($requests['count'] > $requestsPerMinute) {
                $resetTime = $requests['window_start'] + $windowSeconds;
                $retryAfter = $resetTime - $currentTime;

                header('X-RateLimit-Limit: ' . $requestsPerMinute);
                header('X-RateLimit-Remaining: 0');
                header('X-RateLimit-Reset: ' . $resetTime);
                header('Retry-After: ' . $retryAfter);

                throw new HttpException('Too Many Requests', 429);
            }

            // Add rate limit headers
            header('X-RateLimit-Limit: ' . $requestsPerMinute);
            header('X-RateLimit-Remaining: ' . ($requestsPerMinute - $requests['count']));
            header('X-RateLimit-Reset: ' . ($requests['window_start'] + $windowSeconds));

            return $next($request);

        } catch (\Exception $e) {
            error_log('Rate limiting error: ' . $e->getMessage());
            // Fallback: allow request on cache error
            return $next($request);
        }
    }

    public function __destruct()
    {
        if (isset($this->cache)) {
            $this->cache->disconnect();
        }
    }
} 