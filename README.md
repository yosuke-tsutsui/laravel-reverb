# Laravel Reverbを試す

## 環境構築
1. `git clone https://github.com/yosuke-tsutsui/laravel-reverb.git`
1. `cd laravel-reverb`
1. `echo`、`laravel-reverb`、`frontend` それぞれで、 `.env.example` を `.env` にコピーする。
    - ポート番号を調整したい場合は適宜変更する。
    - ただし、内部向けポートを変更した場合は `nginx.conf` 内も書き換える必要がある。
1. `docker compose build`
1. `docker compose up -d`
1. `docker compose exec [web/echo1/echo2] composer install`
    - `echo1` だけやれば `echo2` はやらなくてもいい。
1. `docker compose node yarn install`

## 起動方法

コンソールを最低3つ開いて、下記それぞれを実行する。

```sh
# 1つのコンソールで3つやるのではなく、3つのコンソールで1コマンドずつ実行する

docker compose exec web php artisan octane:start --server=roadrunner --host=0.0.0.0 --port=8000

docker compose echo1 php artisan reverb:start --debug

docker compose node yarn run dev
```

Reverbサーバを複数実行する場合は、コンソールをもう1つ開いて、2つめのコマンドの `echo1` の部分を `echo2` にして実行する。

## 動作確認
1. `http://localhost:28080/chat` をブラウザで開く。
    - WebSocketが機能しているかどうかはdevtoolで確認。
1. メッセージをPOSTで投げる（Postman等を使用）
    ```sh
    curl --location 'http://localhost:19100/api/chat' \
    --header 'Content-Type: application/json' \
    --data '{
        "message": "chat message 1"
    }'
    ```

すべてがきちんと動作していれば、ブラウザで開いている画面にリアルタイムで「chat message 1」と出るはず。

## 注意事項
- `echo` ディレクトリは `laravel-reverb` をコピーして余計なファイルを消した結果だが、最小構成というわけではない。
- `frontend` に関しても、最小構成というわけではない。
    * 当初は画面上でチャットができるようにするつもりで、 `axios` 等のライブラリを入れたようだが、Postman等で叩くようにしたので今は無くて問題ないはず。
