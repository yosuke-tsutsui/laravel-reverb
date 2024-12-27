<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;
use Laravel\Reverb\Contracts\ApplicationProvider;

class ChatEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public ?string $message = null)
    {
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('channel-chat'),
        ];
    }

    public function publish(): void
    {
        $reverbApp = app(ApplicationProvider::class)->findById(env('REVERB_APP_ID'));

        Redis::connection('reverb')
            ->publish('reverb', json_encode([
                'type' => 'message',
                'application' => serialize($reverbApp),
                'payload' => [
                    'event' => self::class,
                    'channels' => collect($this->broadcastOn())->map(fn ($c) => (string) $c)->toArray(),
                    'data' => ["message" => $this->message],
                ],
            ]));
    }
}
