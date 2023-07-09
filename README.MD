Instructions:

install with;
`composer install alialican/hubspot-rate-limiter
`
import the package in your code, wrap your hubspot client with it and use it as you would use the original client.

`
$redis = new \Predis\Client($yourRedisConfiguration);
$secondLimit = 100;  (replace with your desired per second limit)
$dailyLimit = 250000;  (replace with your desired daily limit)
$hubspot = new HubSpotRateLimiter\HubSpotClientWrapper($hubspotClient, $redis, $rateLimit);`


There are some points to be aware of:

It uses Laravel's Cache facade, so you'll need to ensure that you have the proper cache driver set up in your Laravel configuration.
It directly accesses Redis when the cache driver is Redis. If your Redis setup is unusual or complex, this might not work correctly.
The rate limiting mechanism in enforceRateLimitGeneric is simplistic and does not handle high rates of concurrency as efficiently as the Redis-based solution.
It assumes that the Redis-based solution will run the Lua script correctly. If your Redis server does not support the eval command, this could fail.