# Configuration
Captain Hook simplifies managing webhooks from third-party services by allowing you to specify
a single URL on each service's website where it can send its webhooks. It does this by

1. receiving webhooks from third-party services,
2. directing the data to other addons, then
3. letting the services know if everything was handled properly (some services will resend
   improperly handled events).

This also means addon developers don't *have* to create a dedicated listener method (or even class)!


## Captain Hook
Just define an associative array whose keys match the services' names in [snake_case][snake_case],
which are indexed arrays of the addons that should handle the event.

### Example `site/settings/addons/captain_hook.yaml`
```yaml
handlers:
  stripe:
    - Horse
    - Pidgey
    - Tiger
  service_with_multi_word_name:
    - Addon That Integrates With Above Service
```

If addons should handle an event in a certain order, list them in that order. For example, on a
Stripe event, Horse would handle the event first, then Pidgey, then finally Tiger.


## Service
In the settings for the third-party service, set the URL to `/!/captain-hook/snake_cased_service_name`.

Some services have dedicated handlers to better handle the event. For example, at
[LionsMouth Digital][lmd], when we receive a Stripe event, we only grab the event ID from that data
and query Stripe with [Tiger][tiger] for that event's data to verify it actually came from Stripe,
not a malicious jabroni. When using one of these dedicated handlers, you need to prefix the service
name with an underscore (*ex:* `/!/captain-hook/_stripe`). If you should use an underscore, the
addon's docs will instruct you to do so.

This allows us to write better code without forcing our ways on other addon developers who might
integrate their addon with Captain Hook.




---




# Integration
*This section pertains only to addon developers wishing to integrate with Captain Hook.*

Anyone can integrate their addon with Captain Hook! You just need to follow some conventions:

1. Create an [API class](http://docs.talonsbeard.com/addons/anatomy/api).
2. In that API class, create a method named after the service it integrates with as `Str::camel()`
   returns it, followed by `Handler`.


## Example
```php
<?php

namespace Statamic\Addons\Pidgey;

use Statamic\Extend\API;

class PidgeyAPI extends API
{
    /**
     * Handle events from Stripe.
     * @author Curtis Blackwell
     * @return boolean Whether or not the event was handled successfully.
     */
    public function stripeHandler($)
    {
        if ($this->stripe->eventWasHandled()) {
            return true;
        }

        ...

        if ($success) {
            return true;
        }

        return false;
    }
}
```

Notice we just return `true` if Pidgey already handled the event. Stripe resends event data to
endpoints that don't respond with `2xx`. Captain Hook will send a `500` if *any* addon using an
event returns `false`. If you don't want your addon to reprocess any events, make sure your handler
simply returns `true` for previously processed events.

Here's how we do this:

**After successfully handling an event:**
```php
$events_processed   = $this->storage->getYAML('processed.yaml', []);
$events_processed[] = $event->id;
$this->storage->putYAML('processed.yaml', $events_processed);
```




[camelCase]: https://en.wikipedia.org/wiki/CamelCase
[lmd]: http://lionsmouth.digital
[snake_case]: https://en.wikipedia.org/wiki/Snake_case
[tiger]: http://lionsmouth.digital
