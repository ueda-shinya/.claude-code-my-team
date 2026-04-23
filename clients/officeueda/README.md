# オフィスウエダ

## 基本情報

- **屋号**: オフィスウエダ
- **代表**: 上田伸也（シンヤさん）
- **サイト**: https://officeueda.com/
- **所在地**: 広島県東広島市黒瀬町楢原
- **対応エリア**: 東広島市・呉市・広島市周辺

## SNS・プロフィール

- **Instagram（事業）**: https://www.instagram.com/officeueda_/
- **Instagram（個人）**: https://www.instagram.com/_shinyaueda/
- **X（個人）**: https://x.com/aynihsadeu
- **X（事業）**: https://x.com/OfficeUeda
- **Threads**: https://www.threads.com/@_shinyaueda
- **Googleビジネスプロフィール**: https://www.google.com/search?q=%E3%82%AA%E3%83%95%E3%82%A3%E3%82%B9%E3%82%A6%E3%82%A8%E3%83%80

## 共通資料

- [単価マスター](price-master.md) — 全サービスの価格・内容・ID。見積り作成時はここを参照
- [サービス設計](services/README.md) — **ITまるごとサポートv4（メインサービス）の正本位置・監視トリガー・関連ファイル**
- [GBP整備ドキュメント](gbp/setup-v4.md) — **Stage2：Googleビジネスプロフィール整備仕様書**（v4整合・カテゴリ・説明文・サービス登録・Q&A・投稿テンプレ・運用ルール）

## 事業一覧

| ディレクトリ | 事業 |
|---|---|
| [biz-web/](biz-web/) | Web制作事業 |
| [biz-ai/](biz-ai/) | AI事業 |

※ メインサービス「ITまるごとサポート」は [services/README.md](services/README.md) を参照

---

## LP管理ルール（lp.officeueda.com / lpwp.officeueda.com）

### 発効・適用範囲
- 発効日: 2026-04-23
- 適用範囲: 本ルール発効日以降の新規LP案件
- 「新規LP案件」の定義: 既存LPの全面刷新（FV以外も含む全セクション書換）は新規扱い。部分改修は既存LPの管理方式を継続
- 既存LP（`biz-web/lp-260319/`, `lp-260326/`, `lp-260407/`）は本ルール対象外。移行は別タスクとして Notion 案件に登録のうえ個別対応

### ディレクトリ構造
```
clients/officeueda/biz-web/
├── lp/                          ← lp.officeueda.com（静的HTML/PHP）
│   └── <service>/
│       ├── INDEX.md
│       └── <YYMMDD-意味>/
└── lpwp/                        ← lpwp.officeueda.com（WordPress）
    └── <service>/
        ├── INDEX.md
        └── <YYMMDD-意味>/       実装方式は構築時に決定
```

**lpwp の実装レイヤー方針**:
- 階層思想（service/pattern）・URL設計・状態管理は lp と共通
- WordPress 実装方式は構築時にシュウが判断（固定ページ複製 / カスタムテーマ / カスタムフィールド等）
- 決定した実装方式は INDEX.md 冒頭に追記
- **lpwp 実装方式決定時の再検証義務**: 構築着手時点で本ルールの URL配信方式・canonical・状態遷移の規定が WordPress 実装で成立するか再検証する。不成立なら lpwp 専用の例外規定を CLAUDE.md または本 README.md に追記してから実装開始

### URL設計
- current 配信URL: `https://<domain>/<service>/`
- パターン個別URL: `https://<domain>/<service>/<YYMMDD-意味>/`
- URL末尾スラッシュは付ける（`/service/` 形式で統一）

### URL配信方式
- **rewrite方式で固定**: サーバー設定で `/service/` → `/service/<current-pattern>/` に内部書換え
- lp 側: `.htaccess`（Apache）または nginx.conf で rewrite 設定
- lpwp 側: WordPress 実装方式に応じてシュウが判断

### 4状態管理

| 状態 | 配信URL | meta robots | sitemap | GA4計測 |
|---|---|---|---|---|
| current | `/service/` で配信（rewrite経由） | index,follow | 載せる | する |
| testing | `/service/<YYMMDD-意味>/` | noindex,nofollow | 載せない | する |
| archive | `/service/<YYMMDD-意味>/` | noindex,nofollow | 載せない | 任意 |
| draft | 非公開（デプロイ対象外） | ー | ー | ー |

**current パターン個別URL の扱い**:
- rewrite 方式では current の実体ファイルも `/service/<current-pattern>/` で直アクセス可能
- この場合 canonical は **`/service/` 絶対URL** を指し、current 配信URL に統合
- sitemap には current 配信URL のみ記載（current パターン個別URLは記載しない）
- 過去のブックマーク・被リンク救済のため直アクセスは許容（canonical で評価統合）

### current 切替時の手順（FTP手動アップロード前提・必須）

**設計原則**: 中間状態を作らない。全ファイル書換をローカルで完了させ、FTPアップロードの順序で rewrite 切替を最後にすることで、canonical/robots の整合を保ったまま切替を完了させる。

1. **ローカル準備（全変更をローカルで完成）**:
   - INDEX.md 更新（新current宣言＋旧current を archive に降格）
   - 旧current パターンHTML: canonical を `https://<domain>/<service>/<old-pattern>/` に書換、meta robots を `noindex,nofollow` に書換
   - 新current パターンHTML: canonical を `https://<domain>/<service>/` に書換、meta robots を `index,follow` に書換
   - sitemap.xml 更新（current URL のみ掲載、パターン個別URLは含めない）
   - rewrite 設定ファイル（`.htaccess` 等）更新（`/service/` → `/service/<new-pattern>/`）
2. **サクラレビュー**:
   - canonical・robots・rewrite 設定・HTML 変更をサクラ（code-reviewer）に依頼
   - **サクラ通過までアップロード禁止**
   - 指摘あれば修正→再レビュー（通過するまでアップロード禁止）
3. **FTP アップロード（シンヤさん手動実施・順序厳守）**:
   - **先にアップロード**: 新currentパターンのHTML、旧currentパターンのHTML、sitemap.xml、その他静的アセット
   - **最後にアップロード**: rewrite 設定ファイル（`.htaccess` 等）← この瞬間に切替完了
   - 理由: rewrite を先に切替えると、新currentのHTMLがまだサーバーに上がっていない瞬間が生じうる。最後にすれば HTML が全て揃った状態で切替が起きる
4. **GSC 対応**: sitemap 再送信 + 新current URL検査リクエスト

**301 リダイレクトは設定しない**: 旧current は archive 状態で `/service/<old-pattern>/` として配信継続。rewrite 先が変わるだけなので `/service/` への外部リンクは自然に新current を指す。

**将来のデプロイ自動化**: シュウが FTP デプロイスクリプトを実装する際、上記「FTPアップロード順序」を同様に守る設計にする（rewrite 最後）。

### canonical 設定（状態別・絶対URL固定）

- **current パターン**: canonical = `https://<domain>/<service>/`
- **testing パターン**: canonical = `https://<domain>/<service>/<YYMMDD-意味>/`（自身）
- **archive パターン**: canonical = `https://<domain>/<service>/<YYMMDD-意味>/`（自身固定。新currentに寄せない）
- **current の実体ファイル直アクセス時**: canonical = `https://<domain>/<service>/` に統合
- **絶対URL必須**: 相対表記は不可

### testing パターンへの流入

- testing への流入は **広告側で直接 `/service/<pattern>/` を指定** して分配
- current ページ内から testing パターンへのリンクは **設置しない**
- 広告 A/B テストで検証して勝ったパターンを次 current に昇格する流れ

### 編集主体の明示

- **INDEX.md**: 運用管理ファイル。成果物（proposals/contracts/deliverables）に該当せず、**アスカが自律更新可能**
- **canonical タグ・meta robots タグ・rewrite 設定ファイル・HTML/PHP/CSS/JS 全般**: コード扱い。**シュウ（backend-engineer）に委任必須**。アスカは直接編集しない
- **切替時のコード変更**: シュウ実装後、**サクラ（code-reviewer）レビュー必須**（Deliverable Quality Gate 準拠）

### INDEX.md（service直下に1ファイル）

**冒頭記載項目**:
- ドメイン（lp or lpwp）
- service 名・用途
- 現行稼働パターン名 + 公開URL（絶対URL）+ 切替日
- lpwp の場合: WordPress 実装方式

**パターン一覧表**:
- パターン名（YYMMDD-意味）
- 状態（current / testing / archive / draft）
- 作成日
- 目的（日本語で記載）
- GA4 content_group 値
- 所感（CV率・学び等）

**更新義務**: パターン追加・状態変更のたびに必ず更新。current 切替時は旧current を必ず archive 降格。

### 命名規則

- パターンディレクトリ名: `YYMMDD-<意味>` 固定
- 意味部分の規約: **小文字英数字とハイフンのみ、最大30文字**（例: `fv-red`, `abtest-b`, `cta-color-test`）
- 命名主体: **アスカ**。シンヤさんの日本語指示を英数字＋ハイフンに変換し、日本語の意味は INDEX.md の「目的」欄に記録

### 共有アセット方針

- service 横断の共有アセットは作らない
- service 単位で完全独立（重複を許容し、変更コストを避ける）

### GA4 計測ルール

- **content_group を必ず設定**: `<domain>/<service>/<pattern>` 形式（例: `lp/web-service/260407-fv-red`）
- **content_group の実装方式**: 各パターンの実体 HTML/PHP に gtag/GTM 設定として **静的に埋め込む**（パターンディレクトリ作成時にシュウがテンプレから生成）
- **A/Bテスト**: URL分離（current vs パターン個別URL）を基本。同一URL内JSスイッチの場合は `ab_variant` カスタムディメンション必須
- **CVイベント命名**: `<domain>_<service>_contact_submit` 形式（例: `lp_web-service_contact_submit`）

### GSC 運用ルール

- `lp.officeueda.com` と `lpwp.officeueda.com` を **それぞれドメインプロパティで別登録**
- sitemap.xml: 各ドメインルートに配置。current パターン URL のみ掲載。service 追加・current 切替時に更新（lpwp は WordPress プラグイン自動生成でも可）

### draft 状態のデプロイ除外

- `.deployignore` ファイルで draft 状態のディレクトリを明示除外
- **配置場所**: `lp/` および `lpwp/` それぞれの直下に配置
- **形式**: gitignore 形式互換
- デプロイスクリプト側で `.deployignore` をパース
- デプロイスクリプト実装はシュウに委任（draft 状態のパターンが発生した時点で実装）

### archive 保持ポリシー

- archive 状態は **1年間保持** を基本
- 1年経過後は年次レビューでシンヤさん判断により削除または継続保持
- **削除の範囲**: リポジトリから削除 + 本番配信からも除外（両方実施）
- 削除時は INDEX.md に削除履歴を残す（「YYYY-MM-DD 削除」）

### 運用整合性チェック

- **頻度**: 月1回（毎月第1日曜の morning-briefing-weekly 内）
- **確認主体**: アスカ
- **確認手段**: ローカルリポジトリ内の `.htaccess` / nginx.conf / WordPress 設定ファイル等を Read + 本番URL を curl で実挙動確認
- **チェック項目**:
  1. INDEX.md の current 宣言 vs rewrite 設定の整合
  2. current HTML の canonical が `https://<domain>/<service>/` を指しているか
  3. current HTML の meta robots が `index,follow` か
  4. archive パターンHTML の meta robots が `noindex,nofollow` か
  5. sitemap.xml に current のみ記載されているか（testing/archive は含まない）
- **不整合発見時**: シンヤさんに報告 → シュウ委任で修正
- **自動化切替閾値**: **lp / lpwp それぞれのドメイン単位で** service 数が10を超えた時点で整合性チェックスクリプト化をシュウに委任（閾値は暫定）

