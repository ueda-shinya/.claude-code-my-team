---
name: mcp-config-troubleshoot
description: Claude Code / Antigravity の MCP サーバー（search-analytics 等）が「.env を更新したのに反映されない」「再起動しても旧値が返る」「環境変数が効いてない」「環境変数が古い」「設定変更したのに反映されない」「GA4 の値が古い」「GA4 の数値がおかしい」「GA4 property_id が反映されない」「property_id が違う」「GSC データが旧サイトのまま」「GSC が旧 URL を叩いている」「GSC だけエラー」「GSC が落ちる」「GSC だけ動かない」「GA4 は動くのに GSC だけダメ」「ドメインプロパティ」「URL プレフィックス」「sc-domain」「~/.claude.json を編集したい」等の症状で動作不整合を起こしたときに、最適な順序（第1容疑者: ~/.claude.json の env ハードコード → 第2容疑者: MCPプロセス重複 → 第3容疑者: コード側 load_dotenv パス → 第4容疑者: .env 値そのもの → 第5容疑者: GSC プロパティタイプと URL 形式の不一致）でトラブルシュートする手順を提示する。MCP の動作が変わらない／MCPサーバーが反映されない／MCPの値が古いまま／search-analytics MCP のエラー／MCPプロセスがおかしい／Claude Code 再起動しても変わらない／Antigravity 再起動しても効かない／gsc_search_analytics ツールエラー 等のキーワードで起動。
---

# MCP 設定反映問題のトラブルシュート

> **5状態契約の対象外（根拠）**: 本スキルは**ユーザー実行型の手順書**であり、スキル本体は確認コマンド・修正方針を**提示するのみで実行しない**。各 Step のコマンド実行・結果解釈・次 Step 進行判断はユーザー（アスカ／シンヤさん／シュウ）の責任で行う。スキル自体に外部 API 呼び出し・サブプロセス実行・外部設定依存処理が存在しないため、5状態契約（[OK:N件]/[FAIL]/[SKIP]/[PARTIAL]）の対象外。

## なぜこのスキルが必要か

2026-05-01、シンヤさんから「ussaijo の GSC/GA4 設定を `.env` で更新したから読み込みできるか確認して」という単純な依頼に対し、アスカが切り分け順序を間違えて**シンヤさんに4回も再起動を要求**し、コード修正・サクラレビューまで実施したが解決しなかった。最終的な真因は `~/.claude.json` の `mcpServers.<name>.env` に旧値がハードコードされていたこと。シンヤさんから「毎回これで手こずってるよね、学習しないの?」とフィードバックを受けたため、切り分け順序を恒久ルール化した。

さらに **2026-05-02**、Step 1〜4 すべて解消した後も GSC のみエラー継続する事例が発生。**追加で30分以上を消費**。真因は Search Console の**プロパティタイプ（ドメインプロパティ / URLプレフィックスプロパティ）と URL 形式の不一致**だった。Step 1〜4 全部 OK でも GSC だけ落ちる場合は、即 Step 5 を疑うこと。**GA4 が OK な時点で認証・ネットワーク・サービスアカウント登録は通っている確証になる**ため、GSC 固有の設定値ミスマッチに即座に切り替えるのが正解。

**本スキルの最重要メッセージ**:
- MCP の動作が `.env` 更新後に変わらない場合、**最初に疑うのは `~/.claude.json` のハードコード**。プロセス重複でもコード修正でもない
- **GA4 は動くのに GSC だけ落ちる**場合、Step 1〜4 を機械的に全部踏むより**先に Step 5（GSC プロパティタイプと URL 形式）を疑う**

## 真因候補と優先順位（必ずこの順序で確認）

### 第1容疑者: `~/.claude.json` の env ハードコード（最頻出）

**仕組み**: Claude Code は起動時に `~/.claude.json` の `mcpServers.<name>.env` を読み、MCP プロセスへ**環境変数として直接注入**する。Python 側の `load_dotenv(override=False)` は既に注入済みの環境変数を `.env` で上書きしないため、`.env` をいくら更新しても無視される。

**確認コマンド**:
```bash
# 対象キー（例: MEBELCENTER_GSC_URL, USSAIJO_GA4_PROPERTY_ID 等）が ~/.claude.json に書かれていないか確認
grep -E "GSC_URL|GA4_PROPERTY_ID|GA4_CLIENT" ~/.claude.json

# 全 MCP サーバーの env 設定を一覧（jq があれば）
jq '.mcpServers | to_entries | map({name: .key, env: .value.env})' ~/.claude.json
```

**修正方針**:
- **推奨**: 該当キーを `~/.claude.json` から**削除**し、`.env` 側を唯一の真実とする運用に統一
- 暫定: `~/.claude.json` の値を新値に書き換える（ただし二重管理が残る）
- 修正後は **Antigravity / Claude Code の完全再起動が必須**（プロセスに注入された環境変数は再起動するまで生きている）

**重要（Step 1 と Step 2 の橋渡し）**: **Step 1 修正 → Antigravity 完全再起動を行えば、Step 2 のプロセス重複も同時に解消する**。Antigravity 完全再起動時に MCP プロセスは正規化され、重複していた古いプロセスは終了し、新しい設定で1本だけ起動する。つまり Step 1 該当時は、わざわざ Step 2 のプロセス kill を行う必要はなく、完全再起動だけで両症状が解消される。Step 2 を独立して疑うのは Step 1 にハードコードがなかった場合のみ。

**注意**: `~/.claude.json` は `.gitignore` 対象。編集前にバックアップ必須。
```bash
cp ~/.claude.json ~/.claude.json.bak.$(date +%Y%m%d-%H%M%S)
```

### 第2容疑者: MCP プロセスの重複起動

**仕組み**: Antigravity / Claude Code は MCP を venv 経由とシステム Python 経由の両方で起動する仕様（観測済）。古いプロセスが旧設定をキャッシュしたまま応答することがある。

**前提**: Step 1 で `~/.claude.json` のハードコードが見つからなかった場合のみ、独立した真因として Step 2 を疑う。Step 1 が真因の場合、完全再起動でプロセス重複も同時解消するため Step 2 は不要。

**確認コマンド（Windows）**:
```powershell
Get-CimInstance Win32_Process -Filter "Name = 'python.exe'" | Where-Object { $_.CommandLine -like "*unified_analytics_server*" } | Select-Object ProcessId, ParentProcessId, CommandLine | Format-Table -Wrap -AutoSize
```

**確認コマンド（Mac）**:
```bash
ps -ef | grep unified_analytics_server | grep -v grep
```

対象スクリプト名は適宜置き換え（`unified_analytics_server.py` / `line-works-bot/scripts/server.py` 等）。

**修正方針（破壊的操作・シンヤさん承認必須）**:
```powershell
# Windows
Stop-Process -Id <PID1>,<PID2>,... -Force
```
```bash
# Mac
kill -9 <PID1> <PID2> ...
```

kill 後は Antigravity が MCP を自動再起動する。**他の MCP（line-works-bot 等）は kill しない**。対象スクリプトに絞ること。

### 第3容疑者: コード側の `.env` 読み込みパス

**仕組み**: `load_dotenv()` を引数なしで呼ぶと、スクリプトの CWD またはスクリプト配置ディレクトリの `.env` を読む。プロジェクトごとに `.env` が分散していると、想定と違う `.env` を読んでいる可能性がある。

**実行範囲の境界（CLAUDE.md「Asuka Never Codes Directly」拡張範囲・2026-04-25 準拠）**:
- **アスカ実行可**: `grep -rn "load_dotenv" <対象スクリプト>` での**文字列検索のみ**（機械的事実確認）
- **シュウ委任必須**: コード意味の解釈（`load_dotenv()` 引数の有無、`override=True/False` の設定、`dotenv_path` の指定先解釈等）、修正実装、ロジックの妥当性判断

つまりアスカは「`load_dotenv` が何箇所に出現するか」「どのファイルに書かれているか」までは確認可能だが、その引数や周辺ロジックの**意味解釈**および**修正方針の決定**はシュウに委任する。

**確認コマンド（アスカ実行可・文字列検索のみ）**:
```bash
grep -rn "load_dotenv" ~/.claude/mcp-servers/<対象MCP>/
```

**修正方針(シュウ委任)**: `~/.claude/.env` 一元読み込みに統一。
```python
from pathlib import Path
from dotenv import load_dotenv
load_dotenv(dotenv_path=Path.home() / ".claude" / ".env")
```

**注意**: コード修正は「Asuka Never Codes Directly」によりシュウ（backend-engineer）に委任必須。

### 第4容疑者: `.env` 値そのもの

**仕組み**: 実は `.env` に新値が保存されていないケース。編集が保存されていない、別の `.env`（例: プロジェクトローカル）を編集していた、改行コードや BOM の混入等。

**確認コマンド**:
```bash
grep -E "<対象キー>" ~/.claude/.env
# 例: grep -E "USSAIJO_GA4|MEBELCENTER_GSC" ~/.claude/.env

# .env のバックアップ世代を確認（編集が反映されているか確証）
ls -la ~/.claude/.env*
```

### 第5容疑者: GSC プロパティタイプと URL 形式の不一致（GSC 固有）

**症状の特徴（最重要）**: **GA4 は正常動作しているのに GSC だけがエラー**。`.claude.json` env 修正済・Antigravity 完全再起動済・サービスアカウント権限付与済（後述）でも `ツール 'gsc_search_analytics' の実行中にエラーが発生しました` 等が継続する。Step 1〜4 すべて解消後も GSC のみ落ちる場合の典型パターン。

**仕組み**: Search Console には**2種類のプロパティタイプ**が存在し、API へ渡す URL 形式がそれぞれ異なる。プロパティタイプと `.env`（または `~/.claude.json`）に設定した URL 形式が一致しないと、API は当該プロパティを認識せずエラーを返す。

| プロパティタイプ | 設定値の形式 | 例 |
|---|---|---|
| ドメインプロパティ | `sc-domain:<domain>` | `sc-domain:example.com` |
| URLプレフィックスプロパティ | `https://<domain>/`（**末尾スラッシュ必須**） | `https://example.com/` |

**典型的な失敗例（2026-05-02 ussaijo 事案）**:
- 旧 `wsp.us-saijo.com` は **ドメインプロパティ**で登録 → `sc-domain:wsp.us-saijo.com`
- 新 `workshirtsproduct.com` は **URLプレフィックスプロパティ**で登録 → `https://workshirtsproduct.com/`
- 設定値を旧形式（`sc-domain:`）のまま新ドメインに使ったり、URL プレフィックス形式の末尾スラッシュを忘れるとエラー

**確認方法（Search Console UI）**:
1. Search Console (`https://search.google.com/search-console`) にログイン
2. 左上のプロパティ選択ドロップダウンを開く
3. 対象プロパティのアイコンと表示で判定：
   - **🌐 地球儀アイコン**または「ドメイン プロパティ」表示 → ドメインプロパティ
   - **🔗 鎖アイコン**または `https://...` 表示 → URL プレフィックスプロパティ
4. 設定 → 所有権の確認でも判定可：
   - 「ドメイン名プロバイダ」（DNS認証） → ドメインプロパティ
   - 「HTML タグ / HTML ファイル / Google Analytics / Google Tag Manager」 → URL プレフィックスプロパティ

**修正方法**:
1. `~/.claude.json` または `~/.claude/.env` の対象キー（例: `USSAIJO_GSC_URL`, `MEBELCENTER_GSC_URL`）を上記表の形式に揃える
2. URL プレフィックスプロパティの場合は**末尾スラッシュを必ず付ける**（`https://example.com` ではなく `https://example.com/`）
3. **Antigravity 完全再起動**

**判別のショートカット**: 「GA4 は動くのに GSC だけ落ちる」状況は、Step 1〜4 を機械的に全部踏む前にまず Step 5 を疑う。GA4 が動いている時点で：
- 認証情報（サービスアカウント JSON）は正しく読み込めている
- ネットワーク到達性も問題なし
- サービスアカウントが Google Cloud に登録されており API キーも有効
…の3点が確証として得られているため、残る GSC 固有変数は **URL 形式のミスマッチ**である可能性が極めて高い。

**関連: サービスアカウントの権限要件**

サービスアカウント `ga4-mcp@claude-mcp-integration-490103.iam.gserviceaccount.com` に必要な権限：

| サービス | 必要権限 | 備考 |
|---|---|---|
| GA4 | プロパティで「閲覧者」以上 | プロパティ単位で付与 |
| GSC | プロパティで「制限付き」以上 | フルユーザー不要、制限付きで十分 |

GSC の権限不足でもエラーになるが、`PERMISSION_DENIED` 系のメッセージで識別可能。URL 形式不一致は権限エラーとは別のメッセージ（`ツール実行中にエラー` 等の汎用エラー）で出ることが多い。

## 切り分けフロー（決定版）

依頼を受けたら以下を順番に実行し、各 Step で「該当あり」なら修正後に再確認、なければ次 Step へ。**ただし「GA4 は動くのに GSC だけ落ちる」場合は Step 5 へ即ジャンプ**。

```
症状: MCP の動作が `.env` 更新後・再起動後にも変わらない
      / GA4 の値が古い / property_id が反映されない / GSC が旧 URL を叩く 等

[Pre-Check] GA4 は動くか？ GSC だけ落ちているか？
  ├ Yes（GA4 動作・GSC のみエラー） → Step 5 へ即ジャンプ（Step 1〜4 はスキップ可）
  └ No（GA4 も GSC も両方ダメ／GA4 が古い値）→ Step 1 から順に進む

[Step 1] ~/.claude.json の env ハードコード確認 ← まずここ！
  grep -E "<対象キー>" ~/.claude.json
  ├ ハードコードあり → 該当キーを削除（または書き換え）→ Antigravity 完全再起動 → 確認
  │                    ※ この時点で Step 2 のプロセス重複も同時解消するため Step 2 不要
  └ ハードコードなし → Step 2 へ

[Step 2] MCP プロセス重複確認（Step 1 該当なしの場合のみ）
  Get-CimInstance Win32_Process ... | Where-Object { $_.CommandLine -like "*<対象スクリプト>*" }
  ├ 2つ以上プロセス → 全 kill（シンヤさん承認後）→ Antigravity が自動再起動 → 確認
  └ 1つのみ → Step 3 へ

[Step 3] コード側の load_dotenv 読み込みパス確認
  grep -rn "load_dotenv" ~/.claude/mcp-servers/<対象MCP>/  ← アスカは文字列検索のみ可
  ├ パスが分散 / 想定外 → ~/.claude/.env 一元読み込みに修正（シュウ委任：解釈・修正実装）
  └ 一元化済み → Step 4 へ

[Step 4] .env 値そのものの確認
  grep -E "<対象キー>" ~/.claude/.env
  ├ 値が古い / 未保存 → .env を更新 → Antigravity 完全再起動 → 確認
  └ 値も正しい → Step 5 へ（GSC のみエラーなら）／別の問題（API キー失効、ネットワーク等）

[Step 5] GSC プロパティタイプと URL 形式の不一致確認（GSC 固有）
  Search Console UI でプロパティタイプを目視確認
  ├ ドメインプロパティ → 設定値を `sc-domain:<domain>` 形式に
  ├ URLプレフィックスプロパティ → 設定値を `https://<domain>/`（末尾スラッシュ必須）に
  └ 修正後 Antigravity 完全再起動 → 確認
     なお解消しない場合 → サービスアカウント権限（GSC「制限付き」以上）を確認
```

## 重要な運用注意点

### `~/.claude.json` と `~/.claude/.env` の二重管理

現在の Claude Code 仕様上、MCP 環境変数の設定箇所は2つある：

| 設定箇所 | 反映タイミング | git 管理 |
|---|---|---|
| `~/.claude.json` の `mcpServers.<name>.env` | Claude Code / Antigravity 完全再起動時 | 管理外（`.gitignore`） |
| `~/.claude/.env`（コード側で `load_dotenv` 経由読み込み） | MCP プロセス再起動時 | 管理外（`.gitignore`） |

**運用方針（推奨）**: `.env` を唯一の真実とし、`~/.claude.json` 側の `env` は空または最小限にする。GA4 property_id / GSC URL 等のクライアント別設定を変更する際は、`.env` のみ更新で完結させる。

### `~/.claude.json` 編集時の注意

- 編集前に必ずバックアップ（`cp ~/.claude.json ~/.claude.json.bak.YYYYMMDD-HHMMSS`）
- JSON 構文エラーで Claude Code 起動不能になるリスクあり
- 編集後は **Antigravity / Claude Code の完全再起動**が必要（プロセス再起動だけでは不十分なケースあり）
- 「Safe Editing Rule for Git-Ignored Files」適用対象

### 再起動の使い分け

| 操作 | 効くケース |
|---|---|
| MCP プロセス kill → 自動再起動 | コード修正、`.env` 更新、プロセス重複解消（Step 1 非該当時） |
| Claude Code 再起動 | `~/.claude.json` 編集、Claude Code 設定変更 |
| Antigravity 完全再起動 | `~/.claude.json` 編集の確実反映、プロセス重複の同時正規化、GSC プロパティ URL 形式変更後、上記いずれでも解決しない最終手段 |

**よくある失敗**: `~/.claude.json` を編集後、MCP プロセス kill だけで済ませてしまい「反映されない」となる。`~/.claude.json` 編集時は必ず Antigravity 完全再起動。

## 過去事例

- **2026-05-01 ussaijo GSC/GA4 設定更新事件（Step 1 教訓）**: `.env` 更新後も旧値が返る問題で、アスカが Step 2（プロセス重複）→ Step 3（コード一元化）→ サクラレビューと進めたが解決せず。最終的に Step 1（`~/.claude.json` ハードコード）が真因だった。シンヤさんに4回も再起動を要求した反省から本スキル化。
- **2026-05-02 GSC のみエラー継続事件（Step 5 教訓）**: Step 1〜4 全て解消後も GSC のみエラー継続。**追加で30分以上を消費**。真因は Search Console の**プロパティタイプ（ドメインプロパティ → URLプレフィックスプロパティ）と URL 形式の不一致**だった（旧 `wsp.us-saijo.com` はドメインプロパティ＝`sc-domain:` 形式、新 `workshirtsproduct.com` は URL プレフィックスプロパティ＝`https://.../` 形式）。**GA4 が OK な時点で認証・ネットワーク・サービスアカウント登録は通っている確証になる**ため、Step 1〜4 全部 OK でも GSC だけ落ちる場合は即 Step 5 を疑うこと。

## 関連ファイル

- `~/.claude.json`（MCP サーバー設定・git 管理外）
- `~/.claude/.env`（環境変数・git 管理外）
- `~/.claude/mcp-servers/mcp-search-analytics/unified_analytics_server.py`
- 旧仮説草案: `~/.claude/tmp/feedback-mcp-server-restart-DRAFT.md`（プロセス重複ベースで作成、本スキルで上書き）

## 制約・禁止事項

- `~/.claude.json` の編集は破壊的操作。必ずバックアップ後に実施
- MCP プロセスの kill はシンヤさん承認必須（現セッションの MCP ツールが一時停止する）
- コード修正（`load_dotenv` パス変更等）はシュウ（backend-engineer）委任必須（Asuka Never Codes Directly）
- Step 3 の `grep` 結果**解釈**もシュウ委任（アスカは文字列検索の事実確認まで）
- Step 5 の Search Console UI 操作はシンヤさん実行（権限・認証情報の確認は人間判断必須）
- `line-works-bot/scripts/server.py` 等、対象外の MCP プロセスを kill しない
