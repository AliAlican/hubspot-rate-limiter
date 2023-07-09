Instructions:

install with;
`composer install alialican/hubspot-rate-limiter
`
import the package in your code, wrap your hubspot client with it and use it as you would use the original client.

`
$redis = new \Predis\Client($yourRedisConfiguration);
$rateLimit = 100; // replace with your desired rate limit
$hubspot = new HubSpotRateLimiter\HubSpotClientWrapper($hubspotClient, $redis, $rateLimit);`