# linnoa（バクチスコーポレーション株式会社）BORN STEM 購入お問い合わせフォーム

linnoa（吉澤さん）案件「BORN STEM 購入お問い合わせフォーム」の実装です。
`~/.claude/workspaces/sendmail-form-base/` v1.6.0 をベースに作成しました。

- 本番URL: `https://company.bakuchis.com/bornstem_contact`
- 管理者宛メール宛先: `info@bakuchis.com` / `bakuchis@officeueda.net`（2件）

## ディレクトリ構成

```
bornstem_contact/
├── index.php                ← フォーム本体（PHP / CSRF / JSON-LD）
├── submit.php               ← 送信処理（バリデーション・メール送信・ログ）
├── thanks.html              ← サンクスページ
├── assets/                  ← 公開前提（静的配信のみ）
│   ├── css/
│   │   ├── form.css         ← フォームページ専用スタイル（FLOCSS + BEM）
│   │   └── site.css         ← サイト共通スタイル（FLOCSS + BEM）
│   ├── js/
│   │   ├── form.js          ← バリデーション・YubinBango連携・プライバシーモーダル・非同期送信
│   │   └── vendor/
│   │       ├── yubinbango.js          ← YubinBango.js ローカルホスティング版（MIT License）
│   │       └── LICENSE-yubinbango.txt ← MIT License 全文（Teruyuki Kobayashi, 2015）
│   └── images/
├── includes/                ← 非公開PHP（require専用・URL直叩き拒否）
│   ├── config.php           ← 設定ファイル（.gitignore 除外・本番値を直接記載）
│   ├── session-init.php     ← セッション初期化（Cookie属性設定）
│   └── .htaccess            ← Apache アクセス全面拒否（2.4/2.2両対応）
├── templates/               ← 非公開データ（URL直叩き拒否）
│   ├── admin-mail.txt       ← 管理者宛メール本文テンプレート
│   ├── autoreply-mail.txt   ← 自動返信メール本文テンプレート
│   └── .htaccess
└── logs/
    ├── .gitkeep
    └── .htaccess
```

## フォーム項目一覧

| フィールド | 必須 | バリデーション |
|---|---|---|
| 会社名・サロン名 | ✓ | 必須・1〜100文字 |
| 会社名・サロン名カナ | ✓ | 必須・全角カタカナ（スペース不可）・1〜100文字 |
| 代表者 姓 | ✓ | 必須・1〜50文字 |
| 代表者 名 | ✓ | 必須・1〜50文字 |
| 代表者カナ セイ | ✓ | 必須・全角カタカナ（スペース不可） |
| 代表者カナ メイ | ✓ | 必須・全角カタカナ（スペース不可） |
| 担当者 姓 | 任意 | 1〜50文字 |
| 担当者 名 | 任意 | 1〜50文字 |
| 担当者カナ セイ | 任意 | 全角カタカナ（スペース不可） |
| 担当者カナ メイ | 任意 | 全角カタカナ（スペース不可） |
| 郵便番号 | ✓ | 必須・ハイフンあり/なし両対応。サーバー側でハイフン削除・7桁数字で保存 |
| 都道府県 | ✓ | 必須（YubinBangoで自動入力→手修正可） |
| 市区町村 | ✓ | 必須（YubinBangoで自動入力→手修正可） |
| 町域・番地 | ✓ | 必須（YubinBangoで自動入力→手修正可） |
| ビル建物名など | 任意 | - |
| メールアドレス | ✓ | 必須・`FILTER_VALIDATE_EMAIL` |
| 電話番号 | ✓ | 必須・ハイフンあり/なし・全角数字・全角ハイフン・スペース全対応 |
| 携帯番号 | 任意 | ハイフンあり/なし・全角数字・全角ハイフン・スペース全対応 |
| 業態 / 業種 | ✓ | 必須・1〜100文字 |
| ホームページURL | ✓ | 必須・`FILTER_VALIDATE_URL`・http/httpsのみ許容 |
| プライバシーポリシー同意 | ✓ | チェック必須 |

## 電話番号バリデーション詳細

**全角・ハイフン混在への対応内容（今回改修済み）:**
- 全角数字（０〜９）→ 半角変換
- ハイフン各種（`-` `-` `ー` `‐` `‑` `‒` `–` `—`）・スペースを除去
- 除去後に半角数字のみか検査 → OK なら桁数チェックへ
- 携帯・IP電話・フリーダイヤル0800（090/080/070/050/0800頭）: 11桁必須
- 固定電話・0120（10桁体系のため固定電話グループで判定）: 10桁必須

## 郵便番号自動入力（YubinBango.js）

**ライブラリ:** [YubinBango.js](https://github.com/yubinbango/yubinbango)（ローカルホスティング）
**採用理由:** APIキー不要・純粋クライアントサイド動作・住所データ内蔵・jQuery不要
**ライセンス:** MIT License (Copyright (c) 2015 Teruyuki Kobayashi)
**配置場所:** `assets/js/vendor/yubinbango.js`（CDNからダウンロード済み）
**ダウンロード元:** `https://yubinbango.github.io/yubinbango/yubinbango.js`
**バージョンクエリ:** `?v=20260428`（キャッシュ更新時は日付を変更する）

**組み込み方法:**
1. フォームに `class="h-adr"` を付与（`form` タグ相当の要素）
2. 隠し input に `class="p-country-name" value="Japan"` を配置
   ※ YubinBango.js は `value` を `["Japan","JP","JPN","JAPAN"]` のいずれかと完全一致判定する。`"ja"` 等の言語コードでは keyup リスナー登録自体がスキップされるため、必ず上記4候補のいずれかを使うこと。
3. 郵便番号 input に `class="p-postal-code"` を付与
4. 都道府県・市区町村・町域の input に `class="p-region"` / `class="p-locality"` / `class="p-street-address"` を付与
5. `form.js` でハイフン除去・全角→半角変換後に value 上書き → `keyup` イベントを発火して YubinBango を起動
6. 自動入力後もユーザーが手修正可能

**フォールバック:** 住所が見つからない場合・ネットワーク失敗の場合は手入力で進められる

## プライバシーポリシーモーダル

- 「プライバシーポリシーを確認する」ボタンクリックでモーダルを表示
- 閉じ方: ×ボタン / オーバーレイクリック / ESCキー
- フォーカストラップ（モーダル外にフォーカスが逃げない）実装済み
- 開いている間は `body` のスクロールロック
- a11y: `role="dialog"` `aria-modal="true"` `aria-labelledby` 対応

## セキュリティ

以下の3層スパム対策を実装しています:

| 対策 | 実装箇所 |
|---|---|
| CSRFトークン | `index.php`（発行）/ `submit.php`（検証） |
| ハニーポット（`url_homepage` フィールド） | `index.php` / `submit.php` |
| レートリミット（同一IP・10分3回まで） | `submit.php` / `logs/rate-limit/` |

メールヘッダーインジェクション対策・XSS対策（出力エスケープ）・SQL インジェクション対策（DB使用なし）は実装済みです。

## カスタマイズ手順

### 1. config.php を編集する

`includes/config.php` を直接開いて本番値を設定してください。
`FROM_EMAIL` は本番投入前に吉澤さんへ確認してください（下記参照）。

`ADMIN_EMAILS` は PHP 配列で複数のアドレスを指定できます。追加するときは要素として並べてください。

```php
define('ADMIN_EMAILS', [
    'info@bakuchis.com',
    'bakuchis@officeueda.net',
    // 追加する場合はここに 'xxx@example.com' を追加
]);
```

### 2. メールテンプレート確認

- `templates/autoreply-mail.txt` — 自動返信メール（送信者向け）
- `templates/admin-mail.txt` — 管理者宛通知メール

### 3. logs/ ディレクトリのパーミッション設定（本番サーバー）

```bash
chmod 700 logs/
```

### 4. Nginx をご利用の場合

`.htaccess` は Apache 用です。Nginx の場合はサーバー設定に以下を追加してください:

```nginx
location ~ /(includes|templates|logs)/ {
    deny all;
}
```

## ログ

`logs/contact-YYYYMM.log` にJSON Lines形式で記録されます:

```json
{"timestamp":"2026-04-28T10:30:00+09:00","result":"success","ip":"192.168.1.1","company":"株式会社〇〇","rep_name":"山田 太郎","email":"yamada@example.com"}
```

## メール送信方式について

本フォームは `mail()` 関数を直接使い、件名・本文・ヘッダーをすべて自前でエンコードしています。
`mb_send_mail()` は使用していません（理由: `mb_language('Japanese')` 設定環境での二重変換文字化けを防ぐため）。

---

## 本番投入時に吉澤さんへ確認すべき事項

1. **`FROM_EMAIL`（送信元メールアドレス）**
   - 現在は `noreply@bakuchis.com` を仮置きしています
   - `company.bakuchis.com` ドメインに紐付くメールアドレスを確定してください（SPF整合のため）

2. **所在地の郵便番号 `〒050-6018` の整合性確認**
   - 依頼文に記載された `〒050-6018` は北海道室蘭市の郵便番号です
   - メール本文に記載されている住所「東京都渋谷区恵比寿4-20-3 恵比寿ガーデンプレイスタワー18階」と郵便番号が一致しません
   - 正しい郵便番号（`〒150-6018` 等）を確認の上、`templates/autoreply-mail.txt` を修正してください

3. **本番サーバーのメール送信設定**
   - `mail()` 関数が使用できるか・`sendmail` / `postfix` が設定されているかを確認してください

4. **YubinBango.js について**
   - ローカルホスティングに変更済みです: `assets/js/vendor/yubinbango.js`
   - 外部CDNへの依存を解消しています（CSP制限環境でも動作可能）
   - ライセンス: MIT License（`assets/js/vendor/LICENSE-yubinbango.txt` 参照）
