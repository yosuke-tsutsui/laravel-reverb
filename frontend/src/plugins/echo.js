import Echo from 'laravel-echo';
// import { io } from 'socket.io-client'; // socket.io-clientのインポート
import Pusher from 'pusher-js';
window.Pusher = Pusher;

// ioをwindowオブジェクトに追加 (必要に応じて)
// window.io = io;

// Echoのインスタンスを作成
// const echo = new Echo({
//     broadcaster: 'socket.io', // broadcasterを 'socket.io' に変更
//     host: `http://localhost:9008`, // WebSocketサーバーのホスト (ポートに注意)
//     transports: ['websocket', 'polling', 'flashsocket'], // 必要に応じてトランスポートを指定
// });


const echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: 'localhost',
    wsPort: 9101,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
});

console.log(echo);

// public-channelをリッスンしてイベントをキャッチする
echo.channel('public-channel')
    .listen('public.event', (data) => {
        console.log('Public Event Received:', data);
    });

export default echo;