<?php

declare(strict_types=1);

use Illuminate\Cache\RedisStore;
use Illuminate\Support\Facades\Cache;
use HubSpot\Discovery\Discovery;

class HubspotClientWrapper
{
    public function __construct(
        protected Discovery $hubspotClient,
        protected Cache $cache,
        protected int $secondLimit = 100,
        protected int $dailyLimit = 250000,
        protected int $retryInterval = 10000
    ) {
    }


    public function __call(string $name, array $arguments)
    {
        while (!$this->enforceRateLimits()) {
            usleep($this->retryInterval);
        }

        return call_user_func_array([$this->hubspotClient, $name], $arguments);
    }

    private function enforceRateLimits(): bool
    {
        $now = microtime(true);

        $luaScript = <<<LUA
            local second_limit_key = KEYS[1]
            local daily_limit_key = KEYS[2]
            local now = tonumber(ARGV[1])
            local second_limit = tonumber(ARGV[2])
            local daily_limit = tonumber(ARGV[3])

            local one_second_ago = now - 1
            local one_day_ago = now - 86400

            -- Enforce the per-second limit
            redis.call('ZREMRANGEBYSCORE', second_limit_key, '-inf', one_second_ago)
            local current_second_count = tonumber(redis.call('ZCARD', second_limit_key))
            if current_second_count >= second_limit then
                return 0
            end

            -- Enforce the daily limit
            redis.call('ZREMRANGEBYSCORE', daily_limit_key, '-inf', one_day_ago)
            local current_daily_count = tonumber(redis.call('ZCARD', daily_limit_key))
            if current_daily_count >= daily_limit then
                return 0
            end

            -- If both checks passed, update the counts and allow the request
            redis.call('ZADD', second_limit_key, now, now)
            redis.call('ZADD', daily_limit_key, now, now)
            return 1
        LUA;

        if ($this->cache->getStore() instanceof RedisStore) {
            $redis = $this->cache->getStore()->connection();
            $result = $redis->eval(
                $luaScript,
                2,
                'hubspot_second_limit',
                'hubspot_daily_limit',
                (string)$now,
                (string)$this->secondLimit,
                (string)$this->dailyLimit
            );
            return $result === 1;
        } else {
            $canPassSecondLimit = $this->genericRateLimit('hubspot_second_limit', $this->secondLimit, 1);
            $canPassDailyLimit = $this->genericRateLimit('hubspot_daily_limit', $this->dailyLimit, 86400);

            return $canPassSecondLimit && $canPassDailyLimit;
        }
    }

    protected function genericRateLimit(string $limitKey, int $limitValue, int $interval): bool
    {
        $timestamps = $this->cache->get($limitKey, []);

        $now = microtime(true);
        $limitTimestamp = $now - ($interval * 1000000);;

        // Filter out timestamps outside of the limiting interval
        $timestamps = array_filter($timestamps, function ($timestamp) use ($limitTimestamp) {
            return $timestamp > $limitTimestamp;
        });

        // If limit exceeded, return false
        if (count($timestamps) >= $limitValue) {
            return false;
        }

        // Add the current request's timestamp and save it
        $timestamps[] = $now;
        $this->cache->put($limitKey, $timestamps, 86400); // Store for a day

        return true;
    }
}