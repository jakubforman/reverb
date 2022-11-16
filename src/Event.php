<?php

namespace Laravel\Reverb;

use Clue\React\Redis\Client;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Laravel\Reverb\Channels\ChannelBroker;
use Laravel\Reverb\Contracts\Connection;

class Event
{
    /**
     * Dispatch a message to a channel.
     *
     * @param  array  $payload
     * @param  \Laravel\Reverb\Contracts\Connection  $connection
     * @return void
     */
    public static function dispatch(array $payload, Connection $connection = null): void
    {
        if (! Config::get('reverb.pubsub.enabled')) {
            static::dispatchSynchronously($payload, $connection);

            return;
        }

        $redis = App::make(Client::class);

        $redis->publish(
            Config::get('reverb.pubsub.channel'),
            json_encode($payload)
        );
    }

    /**
     * Notify all connections subscribed to the given channel.
     *
     * @param  array  $payload
     * @param  \Laravel\Reverb\Contracts\Connection  $connection
     * @return void
     */
    public static function dispatchSynchronously(array $payload, Connection $connection = null): void
    {
        $channels = isset($payload['channel']) ? [$payload['channel']] : $payload['channels'];

        foreach ($channels as $channel) {
            $channel = ChannelBroker::create($channel);

            $channel->broadcast($payload, $connection);
        }
    }
}
