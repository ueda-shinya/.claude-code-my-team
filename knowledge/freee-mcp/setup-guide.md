# freee × Claude Code 連携セットアップガイド

## 全体の流れ

```
① freee登録（無料トライアル）
      ↓
② freeeアプリ作成（OAuth認証用）
      ↓
③ Claude CodeにMCPサーバーを追加
      ↓
④ Claude Codeで見積書・請求書を作成
```

---

## ① freee登録（5分）

1. [freee会計 公式サイト](https://www.freee.co.jp/) にアクセス
2. 「無料で試す」からアカウント作成
   - クレジットカード不要
   - 30日間フル機能が使える
   - 終了後は自動で無料プランへ（勝手に課金されない）

---

## ② freeeアプリ作成（10分）

Claude Codeから freee にアクセスするための「アプリ」を作る手順。

1. [freee アプリストア 開発者ページ](https://app.secure.freee.co.jp/developers/applications) にアクセス
2. 「新しいアプリを作成」をクリック
3. 以下を入力：
   - **アプリ名**：任意（例：`Claude Code連携`）
   - **コールバックURL**：`http://127.0.0.1:54321/callback`
4. 作成後に表示される **クライアントID** と **クライアントシークレット** をメモしておく

---

## ③ Claude Code に MCPサーバーを追加（5分）

### 方法A：プラグインコマンド（推奨）

```bash
claude plugin install freee/freee-mcp
```

### 方法B：手動設定

`~/.claude/mcp-settings.json`（または `claude_desktop_config.json`）に追記：

```json
{
  "mcpServers": {
    "freee": {
      "command": "npx",
      "args": ["freee-mcp"],
      "env": {
        "FREEE_CLIENT_ID": "ここにクライアントID",
        "FREEE_CLIENT_SECRET": "ここにクライアントシークレット"
      }
    }
  }
}
```

設定後に Claude Code を再起動すると認証フローが走り、ブラウザで freee ログインが求められる。

---

## ④ Claude Codeでできること

設定完了後は自然言語で操作できる：

| やりたいこと | 話しかけ方の例 |
|---|---|
| 見積書を作る | 「〇〇社向けに見積書作って。内訳は…」 |
| 請求書を作る | 「先月の〇〇の請求書を作って」 |
| 取引先を登録 | 「新しい取引先として〇〇を登録して」 |
| 取引を記録 | 「〇〇円の売上を記録して」 |

---

## よくある注意点

- **見積書API** は freee請求書（IV）APIに含まれる。freee会計とは別サービス扱いなので、アプリ作成時に「freee請求書」のスコープも有効にすること
- インボイス制度（適格請求書）対応は freee側で設定が必要（登録番号の入力など）
- トークンの有効期限が切れたら再認証が必要（Claude Codeが自動で促してくれる）

---

## 参考リンク

- [freee/freee-mcp（GitHub）](https://github.com/freee/freee-mcp)
- [freee請求書APIリファレンス](https://developer.freee.co.jp/reference/iv)
- [freeeアプリストア 開発者ページ](https://app.secure.freee.co.jp/developers/applications)
