<?php


use Predis\Client as Redis;
use HubSpot\Discovery\Discovery;

class HubspotClientWrapper
{
    private Discovery $hubspotClient;
    private Redis $redis;
    private int $rateLimit;

    public function __construct(Discovery $hubspotClient, Redis $redis, int $rateLimit = 100)
    {
        $this->hubspotClient = $hubspotClient;
        $this->redis = $redis;
        $this->rateLimit = $rateLimit;
    }

    public function __call(string $name, array $arguments)
    {
        if (!$this->enforceRateLimit()) {
            throw new \Exception('Rate limit exceeded');
        }

        return call_user_func_array([$this->hubspotClient, $name], $arguments);
    }

    private function enforceRateLimit(): bool
    {
        // Using microtime as a float to store timestamps with greater precision
        $now = microtime(true);

        // Lua script for rate limiting
        $lua = <<<LUA
        local one_second_ago = tonumber(ARGV[1]) - 1
        redis.call('ZREMRANGEBYSCORE', KEYS[1], '-inf', one_second_ago)
        local current = tonumber(redis.call('ZCARD', KEYS[1]))
        if current < tonumber(ARGV[2]) then
            redis.call('ZADD', KEYS[1], ARGV[1], ARGV[1])
            return 1
        else
            return 0
        end
        LUA;

        // Execute the Lua script and get the result
        $result = $this->redis->eval($lua, [
            'timestamps', // The key of the sorted set in Redis
            $now,         // The current timestamp
            $this->rateLimit, // The rate limit
        ], 1);

        // Return whether the request is allowed
        return $result === 1;
    }
}