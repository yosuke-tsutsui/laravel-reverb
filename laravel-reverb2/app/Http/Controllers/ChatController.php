<?php

namespace App\Http\Controllers;

use App\Events\ChatEvent;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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

    public function store(Request $request): JsonResponse
    {
        ChatEvent::dispatch($request->message);
        return response()->json(['result' => true], Response::HTTP_OK);
    }
}
