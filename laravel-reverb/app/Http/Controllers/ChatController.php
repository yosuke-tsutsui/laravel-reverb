<?php

namespace App\Http\Controllers;

use App\Events\ChatEvent;
use App\Models\Message;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Laravel\Reverb\Contracts\ApplicationProvider;
use Laravel\Reverb\Protocols\Pusher\Contracts\ChannelManager;
use Laravel\Reverb\Protocols\Pusher\EventDispatcher;
use Laravel\Reverb\Protocols\Pusher\Managers\ArrayChannelManager;
use Laravel\Reverb\Protocols\Pusher\MetricsHandler;
use Laravel\Reverb\Protocols\Pusher\PusherPubSubIncomingMessageHandler;
use Laravel\Reverb\Servers\Reverb\Contracts\PubSubIncomingMessageHandler;
use Laravel\Reverb\Servers\Reverb\Contracts\PubSubProvider;
use Laravel\Reverb\Servers\Reverb\Http\Response as ReverbResponse;
use React\EventLoop\Loop;

class ChatController extends Controller
{
    public function index(): JsonResponse
    {
        // 最後の20件を取得
        $messages = Message::orderBy('created_at', 'desc')->take(20)->get();
        $response = [
            'messages' => $messages
        ];
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * イベントをキューに入れて処理させるほう
     */
    public function store1(Request $request): JsonResponse
    {
        $message = $request->message;

        $this->storeMessage($message);

        ChatEvent::dispatch($message);
        return response()->json(['result' => true], Response::HTTP_OK);
    }

    /**
     * イベントをキューに入れず、このメソッド内でpublishする。
     * キューワーカーを動作させる必要はない。
     */
    public function store(Request $request): JsonResponse
    {
        $message = $request->message;

        $this->storeMessage($message);

        $event = new ChatEvent($message);
        $event->publish();

        return response()->json(['result' => true], Response::HTTP_OK);
    }

    private function storeMessage(string $message): void
    {
        $messages = new Message();
        $messages->message = $message;
        $messages->save();
    }

    /**
     * キューワーカーがブロードキャストを処理した際に呼ばれるAPI。
     * 基本的に \Laravel\Reverb\Protocols\Pusher\Http\Controllers\EventsController で
     * やっている処理を移植してきただけ。
     * 
     * Redisに直接publishする。
     */
    public function events1(Request $request, string $appId): ReverbResponse
    {
        logger()->debug(__METHOD__, [
            'request' => $request,
            'appId' => $appId,
        ]);

        // この中は、基本的に
        // \Laravel\Reverb\Protocols\Pusher\Http\Controllers\EventsController::__invoke()
        // の処理をそのままコピーしてきている。
        // ただし、そのままだといくつかエラーが起きたり足りないものがあったりしたので、
        // 必要なところを継ぎ接ぎしている。

        // ==================================================
        // \Laravel\Reverb\Protocols\Pusher\Http\Controllers\EventsController
        // の親クラスである
        // \Laravel\Reverb\Protocols\Pusher\Http\Controllers\Controller::verify()
        // でやっていることをそのまま持ってきている。
        $reverbApp = app(ApplicationProvider::class)->findById($appId);
        // $channels = app(ChannelManager::class)->for($reverbApp);
        // ==================================================

        logger()->debug(__METHOD__, [
            'payload' => $request->all(),
            'path' => $request->path(),
            'appId' => $appId,
        ]);

        $payload = $request->all();

        $validator = $this->validator($payload);

        if ($validator->fails()) {
            return new ReverbResponse($validator->errors(), 422);
        }

        $payloadChannels = Arr::wrap($payload['channels'] ?? $payload['channel'] ?? []);

        Redis::connection('reverb')
            ->publish('reverb', json_encode([
                'type' => 'message',
                'application' => serialize($reverbApp),
                'payload' => [
                    'event' => $payload['name'],
                    'channels' => $payloadChannels,
                    'data' => $payload['data'],
                ],
            ]));


        // if (isset($payload['info'])) {
        //     return app(MetricsHandler::class)
        //         ->gather($reverbApp, 'channels', ['info' => $payload['info'], 'channels' => $channels])
        //         ->then(fn($channels) => new ReverbResponse(['channels' => array_map(fn($channel) => (object) $channel, $channels)]));
        // }

        return new ReverbResponse((object) []);
    }

    /**
     * キューワーカーがブロードキャストを処理した際に呼ばれるAPI。
     * \Laravel\Reverb\Protocols\Pusher\Http\Controllers\EventsController で
     * やっている処理をほぼ丸ごと再現している。
     * 
     * Redisにpublishはされるが、なぜかロードランナーを止めないとブロードキャストが届かない。
     */
    public function events2(Request $request, string $appId): ReverbResponse
    {
        logger()->debug(__METHOD__, [
            'request' => $request,
            'appId' => $appId,
        ]);

        // この中は、基本的に
        // \Laravel\Reverb\Protocols\Pusher\Http\Controllers\EventsController::__invoke()
        // の処理をそのままコピーしてきている。
        // ただし、そのままだといくつかエラーが起きたり足りないものがあったりしたので、
        // 必要なところを継ぎ接ぎしている。

        // ==================================================
        // \Laravel\Reverb\Servers\Reverb\Factory::makePusherRouter()
        // でルータを作る以外の部分を持ってきている。
        app()->singleton(
            ChannelManager::class,
            fn() => new ArrayChannelManager
        );

        // app()->bind(
        //     ChannelConnectionManager::class,
        //     fn() => new ArrayChannelConnectionManager
        // );

        app()->singleton(
            PubSubIncomingMessageHandler::class,
            fn() => new PusherPubSubIncomingMessageHandler,
        );
        // ==================================================

        // ==================================================
        // \Laravel\Reverb\Protocols\Pusher\Http\Controllers\EventsController
        // の親クラスである
        // \Laravel\Reverb\Protocols\Pusher\Http\Controllers\Controller::verify()
        // でやっていることをそのまま持ってきている。
        $reverbApp = app(ApplicationProvider::class)->findById($appId);
        $channels = app(ChannelManager::class)->for($reverbApp);
        $query = $request->query->all();
        // ==================================================

        logger()->debug(__METHOD__, [
            'payload' => $request->all(),
            'path' => $request->path(),
            'query' => $query,
            'appId' => $appId,
        ]);

        $payload = $request->all();

        $validator = $this->validator($payload);

        if ($validator->fails()) {
            return new ReverbResponse($validator->errors(), 422);
        }

        $payloadChannels = Arr::wrap($payload['channels'] ?? $payload['channel'] ?? []);
        if ($except = $payload['socket_id'] ?? null) {
            $except = $channels->connections()[$except] ?? null;
        }

        // ==================================================
        // Redisへの接続が確立されていないというエラーが出るので繋いでおく
        app(PubSubProvider::class)->connect(Loop::get());
        // ==================================================

        EventDispatcher::dispatch(
            $reverbApp,
            [
                'event' => $payload['name'],
                'channels' => $payloadChannels,
                'data' => $payload['data'],
            ],
            $except ? $except->connection() : null
        );

        // app(PubSubProvider::class)->publish([
        //     'type' => 'message',
        //     'application' => serialize($reverbApp),
        //     'payload' => [
        //         'event' => $payload['name'],
        //         'channels' => $payloadChannels,
        //         'data' => $payload['data'],
        //     ],
        // ]);

        if (isset($payload['info'])) {
            return app(MetricsHandler::class)
                ->gather($reverbApp, 'channels', ['info' => $payload['info'], 'channels' => $channels])
                ->then(fn($channels) => new ReverbResponse(['channels' => array_map(fn($channel) => (object) $channel, $channels)]));
        }

        return new ReverbResponse((object) []);
    }

    /**
     * Create a validator for the incoming request payload.
     */
    protected function validator(array $payload): Validator
    {
        return ValidatorFacade::make($payload, [
            'name' => ['required', 'string'],
            'data' => ['required', 'string'],
            'channels' => ['required_without:channel', 'array'],
            'channel' => ['required_without:channels', 'string'],
            'socket_id' => ['string'],
            'info' => ['string'],
        ]);
    }
}
