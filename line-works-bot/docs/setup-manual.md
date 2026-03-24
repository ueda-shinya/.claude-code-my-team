# LINE WORKS Bot セットアップマニュアル

**対象：** シンヤさん（初回設定用）
**作成日：** 2026-03-24
**所要時間：** 約30〜40分

---

## このマニュアルでやること

LINE WORKS に「アスカ Bot」を作成し、メッセージを送受信できる状態にします。
設定はすべて LINE WORKS の管理画面・Developer Console で行います。

---

## 事前確認

- [ ] LINE WORKS にログインできる状態になっている
- [ ] LINE WORKS の管理者権限（または管理者に依頼できる状態）がある
- [ ] パソコンで作業している

---

## STEP 1：LINE WORKS Developer Console にアクセス

1. ブラウザで以下の URL を開く

   👉 https://developers.worksmobile.com/jp/console/

2. LINE WORKS のアカウントでログインする

3. 「開発者登録」が求められる場合は登録を完了させる
   - 名前・メールアドレスを入力するだけでOK

---

## STEP 2：アプリを作成する

Developer Console にログインすると「API 2.0」のダッシュボードが表示されます。

1. 左サイドメニューの **「アプリ」** をクリック

2. 画面右上の **「アプリの新規追加」** ボタンをクリック

3. アプリ名を入力する
   - 例：`asuka-bot`（なんでもOK）

4. **「同意して利用する」** をクリック
   - アプリが作成され、詳細画面が開く

5. 画面に表示される **「Client ID」** と **「Client Secret」** を**メモ帳にコピー**しておく
   - ⚠️ あとで使います。必ず控えてください

---

## STEP 3：Service Account と Private Key を発行する

アプリ詳細画面のまま進めます。

1. 「**Service Account**」の項目を探す

2. **「発行」** ボタンをクリック
   - Service Account ID が表示される
   - 例：`xxxx.serviceaccount` のような形式（`.serviceaccount` で終わる）
   - これも**メモ帳にコピー**しておく

3. 「**Private Key**」の項目を探す

4. **「発行 / 再発行」** ボタンをクリック
   - Private Key ファイルが自動でダウンロードされる（拡張子は `.key` または `.pem`）
   - 中身は `-----BEGIN PRIVATE KEY-----` から始まるテキスト形式（PEM形式）
   - ⚠️ このファイルは**絶対になくさないこと**（再発行はできますが手間がかかります）
   - ダウンロードフォルダにあるファイルを `C:\Users\ueda-\.claude\line-works-bot\` フォルダに移動しておく
     - ファイル名の例：`privateKey.key`

5. 「**OAuth Scopes**」の項目を探す

6. **「bot」** にチェックを入れて **「保存」** をクリック

---

## STEP 4：Bot を登録する

1. 左サイドメニューの **「Bot」** をクリック

2. 画面右上の **「登録」** ボタンをクリック

3. 以下の項目を入力する

   | 項目 | 入力内容 |
   |---|---|
   | Bot 名 | `アスカ`（好きな名前でOK） |
   | Bot の説明 | `AI アシスタント`（なんでもOK） |
   | Callback URL | 今は**空欄でOK**（後でサーバーを起動してから設定します） |

4. **「保存」** をクリック

5. Bot の詳細画面に移動する

6. **「Bot ID」** が表示されている → **メモ帳にコピー**しておく

---

## STEP 5：アプリと Bot を紐づける

1. 左サイドメニューの **「アプリ」** をクリック

2. STEP 2 で作ったアプリをクリック

3. **「Bot」** の項目を探す

4. **「追加」** ボタンをクリック

5. STEP 4 で作った Bot を選択して保存

---

## STEP 6：LINE WORKS 管理画面で Bot をメンバーに追加する

Developer Console から LINE WORKS の管理画面に移動します。

1. ブラウザで以下の URL を開く

   👉 https://admin.worksmobile.com/

2. 左メニューから **「サービス」 → 「Bot」** を探してクリック

3. STEP 4 で作った **「アスカ」Bot** を探す

4. **「メンバーに公開」** または **「使用設定」** を有効にする
   - これをしないと LINE WORKS アプリで Bot が見つかりません

---

## STEP 7：LINE WORKS アプリで Bot を追加する

スマートフォンまたは PC の LINE WORKS アプリで作業します。

1. LINE WORKS アプリを開く

2. 下メニューの **「トーク」** をタップ

3. 右上の **「＋」** または **「新しいトーク」** ボタンをタップ

4. 新規トークの作成画面が開いたら、**「Bot」タブ** を選択

5. 「アスカ」Bot を探してタップ → **「トークを開始」**

   > ⚠️ 「Bot をトークに追加」というメニューが見つからない場合：
   > 既存のトーク一覧画面の「...」または「設定」から「Bot を招待」で追加できる場合もあります

6. 試しにメッセージを送ってみる
   - まだサーバーが動いていないので返事は来ませんが、Bot が追加できていれば OK

---

## STEP 8：控えた情報をアスカに渡す

ここまでの作業で、以下の情報が手元にあるはずです。

| 項目 | 確認 |
|---|---|
| Client ID | `[ ]` 控えた |
| Client Secret | `[ ]` 控えた |
| Service Account ID | `[ ]` 控えた |
| Private Key ファイル（.key） | `[ ]` `~/.claude/` に保存した |
| Bot ID | `[ ]` 控えた |
| 自分の LINE WORKS ユーザーID | `[ ]` 後述 |

---

## 補足：自分のユーザーIDの確認方法

送信制限（シンヤさん以外が送っても Bot が反応しない設定）のために必要です。

Bot が使う LINE WORKS のユーザーID は、管理画面に表示されるログインID（`shinya@example.com` 形式）とは異なり、**UUID 形式**（例：`00f4ca87-5717-42de-1540-041b9e780a45`）です。

**最確実な確認方法：サーバーログから取得する**

1. アスカにサーバーを起動してもらう（ALLOWED_USER_ID は仮の値でOK）
2. LINE WORKS アプリからアスカ Bot にメッセージを送る
3. サーバーログ（`~/.claude/line-works-bot/logs/server.log`）を確認する
4. 以下のような行に UUID が記録されている：
   ```
   [WARNING] 未許可ユーザーからのメッセージを無視: 00f4ca87-5717-42de-1540-041b9e780a45
   ```
5. この UUID が自分のユーザーID → アスカに伝える

---

## 設定完了後にアスカに伝えること

以下の情報をチャットで教えてください。アスカがサーバーを準備します。

```
Bot ID:
Client ID:
Client Secret:
Service Account ID:
Private Key ファイルの場所: C:\Users\ueda-\.claude\line-works-bot\(ファイル名)
自分のユーザーID:
```

---

## うまくいかないとき

| 症状 | 確認ポイント |
|---|---|
| Developer Console にログインできない | LINE WORKS アカウントで試す。会社アカウントの場合は管理者権限が必要な場合あり |
| Bot が LINE WORKS アプリで見つからない | STEP 6 の「メンバーに公開」が完了しているか確認 |
| Private Key のダウンロードボタンがない | ページをリロードして再試行 |
| Client Secret が表示されない | アプリ詳細画面で「再発行」から確認できる場合がある |
| ngrok 起動時に ERR_NGROK_108 が出る | ngrok のセッション上限（無料は3本）に達している。https://dashboard.ngrok.com/agents で古いセッションを Terminate してから再起動 |
| ngrok 起動時に「authtoken」エラーが出る | `ngrok config add-authtoken <token>` コマンドを実行（トークンは https://dashboard.ngrok.com/get-started/your-authtoken で確認） |

---

*不明点があればアスカに声かけてください。一緒に確認します。*
