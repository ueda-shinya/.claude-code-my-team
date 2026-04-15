---
name: claude-code-radar
description: Claude Code 関連の新情報（スキル / MCP / Tips / 公式ドキュメント更新）を毎日自動リサーチし、ファクトチェック・導入判断・おすすめ度付与を行って Notion「Claude Code レーダー」DB に登録するオーケストレータ。朝6:00 JST に CronCreate で自動実行され、朝ブリーフィングで参照される。手動実行や期間指定も可能。
---

# /claude-code-radar

Claude Code エコシステムの新着情報を毎日収集し、アスカチームの成長を加速するためのレーダースキルです。ミオ（リサーチ）・リク（ファクトチェック）・オーケストレータ自身の導入判断の連携で候補を精査し、Notion DB「Claude Code レーダー」に蓄積します。

## 実行モード

| モード | コマンド | 用途 |
|---|---|---|
| 通常 | `/claude-code-radar` | 過去26時間の新着を収集・登録（Cron経由/手動共通） |
| ドライラン | `/claude-code-radar --dry-run` | Notion 登録・seen.json 追記をスキップ。動作確認・テスト用 |
| 期間指定 | `/claude-code-radar --hours N` | 過去 N 時間を対象に実行（デフォルト 26） |
| テスト | `/claude-code-radar --test-mode` | 手動テスト実行。終了後にコスト報告を必ず行う |

複数フラグは併用可（例: `--dry-run --test-mode --hours 48`）。

`--hours` のデフォルトを **26** にしているのは、Cron 実行遅延・ソース反映遅延に対して 2 時間のオーバーラップを取るため。重複は Step 2 の seen.json で排除されるため副作用はありません。

## クロスプラットフォーム

`notion-radar.py` 実行時の Python コマンドは `~/.claude/.env` の `PC_PLATFORM` を確認して読み替えてください。

- Windows（`PC_PLATFORM=win`）: `python ~/.claude/scripts/notion-radar.py ...`
- Mac（`PC_PLATFORM=mac`）: `python3 ~/.claude/scripts/notion-radar.py ...`

以下のコマンド例は便宜上 `python` と表記します。

## 事前チェック（必須）

スキル開始時に以下を必ず確認し、満たさない場合は明確なエラーで停止してください。

1. `~/.claude/.env` に `NOTION_RADAR_DB_ID` が設定されているか
   - 未設定時: 「❌ NOTION_RADAR_DB_ID が .env に未設定です。`notion-radar.py --create-db` を先に実行してください」と出力して終了
2. `~/.claude/scripts/notion-radar.py` が存在するか
   - 未存在時: 「❌ notion-radar.py が未配置です。シュウの実装完了を待ってください」と出力して終了

※ `--test-mode` 未指定の手動実行でも処理は続行しますが、コスト履歴への記録のみ行い、実行直後のコスト報告は行いません（Cron と同じ扱い）。コスト確認をしたい手動実行では `--test-mode` を付与してください。

## パイプライン

### Step 1. リサーチ（ミオに委譲）

`subagent_type: researcher`（ミオ）を Task ツールで呼び出し、以下の指示を渡します。

**ミオへの依頼文テンプレ:**
```
目的: 解（最短）
期間: 過去 {HOURS} 時間（デフォルト26時間）
対象情報源:
  1. GitHub Trending / GitHub Search で claude-code / anthropic claude 関連リポジトリの新着・急上昇
  2. Reddit r/ClaudeAI の hot 投稿（新規の Tips / MCP / スキル紹介）
  3. Anthropic 公式ドキュメント更新（docs.claude.com 配下の変更・新規ページ）
  ※ X（旧Twitter）は将来対応予定のため今回はスキップ

除外: 既に seen.json に記録済みの URL（後段で除外するため、ミオは気にせず全件返してよい）

出力フォーマット（JSON配列、各要素に以下フィールド）:
  - title: 日本語タイトル（原題が英語なら意訳）
  - url: 一次ソース URL
  - source: GitHub / Reddit / 公式docs / その他（Notion の情報源 select と一致させる）
  - category: スキル / MCP / Tips / ドキュメント更新 / その他
  - summary: 100〜200字の日本語要約
  - published_at: 可能なら ISO8601（YYYY-MM-DD 形式でも可）
```

### Step 2. 重複排除

ミオが返した各候補について、以下を実行して既知 URL を除外：

```bash
python ~/.claude/scripts/notion-radar.py --seen-check "<url>"
```

**仕様（stdout ベースで判定推奨）:**
- stdout = `NEW` / exit code = `1` → 新規（次ステップへ）
- stdout = `SEEN` / exit code = `0` → 既知（除外カウンタに加算してスキップ）

`--dry-run` 時も Step 2 は実行します（出力妥当性の確認のため）。スキップされるのは Step 5（Notion 登録）と Step 6（seen-add）のみ。

### Step 3. ファクトチェック（リクに委譲）

新規候補のみを `subagent_type: fact-checker`（リク）に渡し、信頼性判定を依頼します。

**リクへの依頼文テンプレ:**
```
以下の候補について信頼性を判定してください。
各候補に以下を付与:
  - reliability: ✅信頼 / ⚠️要注意 / ❌怪しい（この3択厳守、Notion select と一致）
  - note: 懸念点があれば一言

候補一覧:
[ミオの出力をそのまま渡す]
```

`❌怪しい` 判定は Notion 登録対象から除外します（ログにのみ残す）。

### Step 4. 導入判断（オーケストレータ自身）

`✅信頼` / `⚠️要注意` の候補について、**スキル実行者（オーケストレータ）自身**が以下3項目を付与します。カナタはスキル構築役であり、ここでの判定はカナタ固定ではありません。

- **判定**: 導入推奨 / 中立 / 非推奨
- **理由**: 一言（30字以内）
- **おすすめ度**: ⭐1〜5（下記チェックリストで算出）

**おすすめ度チェックリスト（客観基準）:**

デフォルト **⭐2**。以下の各項目に該当するごとに +1（最大⭐5）。

1. 現在の Notion 案件管理に未解決の **P1/P2** 案件があり、それと関連している（+1）
2. 既存スキル / MCP を **置き換え or 拡張** するもの（+1）
3. **一次ソース**があり、かつ**実装可能な具体性**がある（コード例・手順・設定が明確）（+1）
4. **過去7日のレーダー**に類似情報がない（目新しさ）（+1）

判断時は「アスカチーム（Asuka/Shu/Mio/Riku/Kanata 他）の現在の作業を楽にするか」を最優先基準にしてください。

### Step 5. Notion 登録

`--dry-run` でない場合、各エントリを以下コマンドで登録します。**各件を個別 try/catch** で実行し、失敗した件は集計して Step 7 のサマリに `除外: 登録失敗N件` として追加してください。**登録失敗した URL は Step 6 で seen.json に積まない**（次回再試行可能にする）。

※ `--kanata-*` はスクリプト側実装名称の残存であり、判定者はオーケストレータ自身（Step 4 参照）。フラグ名変更コスト回避のため現行名を維持しています。

```bash
python ~/.claude/scripts/notion-radar.py --add \
  --title "<title>" \
  --date "<YYYY-MM-DD>" \
  --category "<スキル|MCP|Tips|ドキュメント更新|その他>" \
  --summary "<summary>" \
  --url "<url>" \
  --source "<GitHub|Reddit|公式docs|その他>" \
  --riku-check "<✅信頼|⚠️要注意>" \
  --kanata-verdict "<導入推奨|中立|非推奨>" \
  --kanata-reason "<理由>" \
  --recommend <1-5>
```

※ フラグ名・オプション値は `notion-radar.py --help` で最終確認してください。シュウの実装と差異があれば実装に従います（現時点の実装確認済みフィールド名は上記の通り）。

※ **Notion 側の重複ガード（実装済み）**: シュウ側で `check_notion_url_duplicate` が実装済みで、URL 重複時は **exit 0 + stdout に `[SKIP] 既にNotion登録済み: <url>` 接頭辞** を出力します（エラーではない）。Step 5 の try/catch 内では、stdout が `[SKIP]` で始まる場合は **「登録失敗」ではなく「重複除外」として集計**し、Step 7 サマリの `除外: 重複` カウンタ（seen.json 由来と合算）に加算してください。この場合 Step 6 の seen-add は実行しないでください（Notion 側で既に登録済みのため seen.json へのエントリ追加は次回の `--seen-check` で吸収されます）。

### Step 6. seen.json 追記

**Step 5 で登録成功**した URL のみを記録します（重複除外・登録失敗は対象外）：

```bash
python ~/.claude/scripts/notion-radar.py --seen-add "<url>" --title "<title>"
```

- 失敗時は警告ログ（`[WARN] seen-add failed: <url>`）のみで処理続行してください。スキル全体は停止させない
- `--dry-run` 時は Step 6 をスキップ

### Step 7. サマリ出力

標準出力に以下フォーマットで出力します。朝ブリーフィングスキルがこれをパースして統合するため、フォーマットは厳守してください。

```
=== Claude Code レーダー実行結果 ===
期間: 過去 {HOURS} 時間
新規登録: {N} 件
  - スキル: {a} 件
  - MCP: {b} 件
  - Tips: {c} 件
  - ドキュメント更新: {d} 件
  - その他: {e} 件
⭐4以上: {M} 件
⭐5（重要新着）: {K} 件

[⭐4以上のエントリ一覧]
  ⭐5 [カテゴリ] タイトル — 一言理由
       URL
  ⭐4 [カテゴリ] タイトル — 一言理由
       URL
  ...

除外: 重複 {dup} 件 / ❌判定 {ng} 件 / 登録失敗 {fail} 件
```

**`除外: 重複 {dup}` の定義:** seen.json 由来の重複（Step 2）と Notion 側重複ガード由来の重複（Step 5 `[SKIP]`）を **合算** して `dup` にまとめます（内訳は出力しない）。

**重要フラグ:** ⭐5 が1件以上あった場合、サマリ末尾に以下を必ず追加：
```
🔥 重要新着 {K} 件（朝ブリーフィングで強調）
```

### Step 8. 朝ブリーフィング連携ファイル出力

サマリと同内容を JSON 化して以下パスに保存します（`--dry-run` 時もファイルは出力 = 動作確認用）。morning-briefing スキルがこのファイルを読み込みます。

- パス: `~/.claude/tmp/claude-code-radar-latest.json`
- 内容（例）:

```json
{
  "executed_at": "2026-04-15T06:00:00+09:00",
  "hours": 26,
  "registered": 12,
  "by_category": {"スキル": 3, "MCP": 2, "Tips": 4, "ドキュメント更新": 2, "その他": 1},
  "star5_count": 1,
  "star4plus_count": 4,
  "star4plus_entries": [
    {"stars": 5, "category": "MCP", "title": "...", "reason": "...", "url": "..."}
  ],
  "excluded": {"duplicate": 3, "ng": 1, "failed": 0},
  "dry_run": false
}
```

※ `excluded.duplicate` は Step 7 の `dup` と同様、seen.json 由来と Notion 側重複ガード由来を **合算** した値を格納してください（内訳フィールドは持たない）。

morning-briefing 側は、このファイルが **未存在 or タイムスタンプが当日でない** 場合「本日未実行」と表示します（morning-briefing 側の修正はアスカが別途対応）。

## コスト管理（CLAUDE.md APIコスト管理ポリシー準拠）

- **閾値**: `COST_THRESHOLD_USD = 0.50`（**1回の実行あたり**、chatwork-sync.py 準拠）
- **履歴**: `~/.claude/tmp/api-cost-history.json` に追記（`script=claude-code-radar`, `timestamp`, `cost_usd`, `tokens_in`, `tokens_out`）
- **履歴上限**: 最新 500 件保持
- **報告ルール**:
  - `--test-mode`: 実行直後に推定コスト（USD / 円換算）・トークン数・要因を必ず報告
  - 通常運用（Cron経由 / `--test-mode` 無しの手動）: ログのみ。実行コストが `COST_THRESHOLD_USD` を超過した場合のみアラート出力（超過額・要因を明示）
- **注意**: ミオ・リクへの Task 委譲もサブエージェント実行コストに含めて合算してください
- **将来課題**: 月次累計コストの集計・アラートは別途検討（現状は実行単位のみ）

## 制約・注意事項

- シンヤさんへの直接報告は行わない。朝ブリーフィング統合が基本線
- ⭐5 フラグ検出時のみ朝ブリーフィングで強調される
- X（旧Twitter）連携は将来対応（情報源追加時はこのスキルの Step 1 および Step 5 の `--source` 選択肢を更新）
- `notion-radar.py` のインターフェース（フラグ名・select 値）が確定版と差異あればシュウの実装に合わせて追随すること。ハードコードしすぎない
- 実装コード（Python スクリプト等）が必要になった場合はシュウ（backend-engineer）に再委譲する。本スキルはオーケストレーションのみ
- `NOTION_RADAR_DB_ID` 未設定時は必ずエラー停止（サイレント失敗禁止）
- Step 5 は個別 try/catch。失敗しても他の件の処理を継続し、失敗件は seen.json に積まない

## 想定呼び出し元

- 朝6:00 JST に CronCreate から自動実行（通常モード）
- 朝ブリーフィングスキル（morning-briefing / morning-briefing-weekly）が `~/.claude/tmp/claude-code-radar-latest.json` を参照
- シンヤさんの手動起動（`--test-mode` 推奨）

## 完了基準

- Notion DB にその日の新規エントリが登録されている（`--dry-run` 時を除く）
- サマリが標準出力に規定フォーマットで出力されている
- `~/.claude/tmp/claude-code-radar-latest.json` が更新されている
- ⭐5 があれば 🔥 フラグが付いている
- コスト履歴が追記されている（閾値超過時はアラート出力）
