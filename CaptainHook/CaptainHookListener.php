<?php

namespace Statamic\Addons\CaptainHook;

use Statamic\API\Str;
use Statamic\Extend\Listener;

class CaptainHookListener extends Listener
{
    /**
     * The events to be listened for, and the methods to call.
     *
     * @var array
     */
    public $events = [
        // Later indices override the wildcard event.
        'captain-hook.*'       => 'handlerHandler',
        'captain-hook._stripe' => 'stripe',
    ];

    /**
     * Call the configured handlers.
     * @author Curtis Blackwell
     * @param  string $service The snake_cased service name.
     * @param  mixed  $event   The data from the service.
     * @return void
     */
    private function handlerCaller($service, $event)
    {
        $handler = Str::camel($service) . 'Handler';
        $addons  = $this->getConfig('handlers:' . $service, []);

        $success = [];
        foreach ($addons as $addon) {
            $success[] = $this->api(str_replace(' ', '', $addon))->$handler($event);
        }

        if (in_array(false, $success)) {
            // NOTE The addon should log an error if it fails to perform a task.
            // Inform the service an addon failed to handle the event properly.
            http_response_code(500);
            exit;
        }

        // Inform the service that everything went smoovely.
        http_response_code(200);
        exit;
    }

    /**
     * Receive webhooks from third-party services and deliver data.
     * @author Curtis Blackwell
     * @return void
     */
    public function handlerHandler()
    {
        $event   = @file_get_contents('php://input');
        $service = request()->segment(3);

        $this->handlerCaller($service, $event);
    }

    /**
     * Receive webhooks from Stripe and handle the LMD way.
     * @author Curtis Blackwell
     * @return void
     */
    public function stripe()
    {
        // Convert Stripe's JSON to an object.
        $event = json_decode(@file_get_contents('php://input'));

        // Verify event data with Stripe to prevent hax0rz from ruining
        // our lives.
        $event = $this->api('Tiger')->getEvent(
            $event->id,
            array_get($_GET, 'account')
        );

        // Call configured handlers.
        $this->handlerCaller('stripe', $event);
    }
}
