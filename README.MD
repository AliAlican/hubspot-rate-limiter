# HubSpot Rate Limiter

This package provides a rate limiting wrapper for the HubSpot API client, allowing you to set per-second and daily rate limits for your HubSpot API calls. It leverages the Redis cache store for efficient rate limiting. If Redis is not available, it falls back to a generic cache implementation.

## Installation

Install the package using Composer:

```bash
composer require alialican/hubspot-rate-limiter
```

Usage:

1.Import the package in your code:

`use HubSpotRateLimiter\HubSpotClientWrapper;`

2.Create an instance of the HubspotClientWrapper class and pass in the required dependencies:
```
use Illuminate\Support\Facades\Cache;
use HubSpot\Discovery\Discovery;

$hubspotClient = new Discovery();

$secondLimit = 100; // Replace with your desired per-second limit
$dailyLimit = 250000; // Replace with your desired daily limit

$hubspot = new HubspotClientWrapper($hubspotClient, $secondLimit, $dailyLimit);
```

Use the $hubspot instance as you would use the original HubSpot API client. The rate limiter will automatically enforce the specified rate limits on each API call.

`$result = $hubspot->crm()->contacts()->getAll();`

Important Points

This package uses Laravel's Cache facade for rate limiting. Make sure you have the proper cache driver set up in your Laravel configuration.
When the cache driver is Redis, the rate limiter directly interacts with Redis for efficient rate limiting. Ensure that your Redis configuration is correctly set up.
If your Redis setup is unusual or complex, the Redis-based rate limiting may not work as expected.
The rate limiting mechanism used in the enforceRateLimitGeneric method is simplistic and may not handle high rates of concurrency as efficiently as the Redis-based solution.
The package assumes that the Redis server supports the eval command, as it uses Lua scripts for rate limiting. If your Redis server does not support this command, the rate limiting may fail.
Please test the package thoroughly in your specific environment to ensure it meets your requirements and works correctly with your Redis configuration.

License
This package is open-source and licensed under the MIT License.

`Feel free to customize and expand on the README to provide more information or clarify any additional details specific to your use case.`