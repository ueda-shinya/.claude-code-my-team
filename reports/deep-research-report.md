# VS Code版Claude CodeでMCPサーバーが動かない（CLIでは動く）事例の深掘り調査レポート

## エグゼクティブサマリ

本件「CLIではMCPサーバーが動くのに、VS Code拡張（Claude Code）では動かない」は、実務的には **拡張側のMCPブリッジ不具合（-32601）**、**拡張UIがMCP設定を正しく反映しない／見えない（/mcpが空・“No servers configured”）**、**拡張ホストとCLIの実行環境差（PATH・環境変数・作業ディレクトリ・Remote/WSL/Containerの“実行場所”差）** の3系統に大別できます。特に **`MCP error -32601: Method not found`（claude-vscode）** は、拡張が起動直後にクラッシュ／ハング／応答不能になる既知報告が複数あり、CLI正常でも拡張だけ破綻する典型パターンとして優先的に疑うべきです。citeturn11view5turn10view6turn11view4turn11view1

一方で、拡張側が **/mcpに何も表示しない**・**CLIで追加したMCPが拡張UIに出ない** といった「見えない／反映されない」系も、2026年初頭に複数のIssueとして報告されています（macOS/Windows双方）。拡張が“別リスト”を持っているように見える事例や、設定自体は接続しているのに `/mcp` UIだけが “No servers configured” と誤表示するレースコンディション疑いもあります。citeturn10view0turn10view1turn10view2turn11view0

そして「CLIはOKだが拡張はNG」の現場原因として頻出するのが **拡張ホストの環境差（PATH/環境変数）** と **作業ディレクトリ・パス正規化差（特にWindowsのドライブレター大小文字・区切り差）** です。PATH差はVS Codeの拡張ホストで起こり得ることがMicrosoft側でも古くから認識され、MCP側でも `spawn ... ENOENT` の対処として「実行ファイルのフルパス指定」が公式に案内されています。citeturn12search2turn7view2turn8view1turn9view0

結論として、**最短で復旧する優先度付き手順**は次です。  
(1) VS Code拡張のログ（Output/Extension Host/Claude VSCode.log）で **-32601有無**と **/mcp表示状態**を確認 → (2) 公式に用意された **「Use Terminal（ターミナルモード）」へ切り替え**を回避策として使い、業務継続（CLI連携） → (3) 同時に **環境差（PATH/環境変数/実行場所/Working directory）** をコマンドで差分取得し、再現情報を固めて恒久対策（フルパス化・承認リセット・ポリシー確認・アップデート）を適用、が最も再現性が高いです。citeturn13search4turn4view0turn6view0turn7view2turn12search36

---

## 対象環境と前提

### 未指定事項（本レポートでは未指定として扱う）
ご要望どおり、以下は現時点で **未指定** です（ここが未確定だと原因の最終確定はできません）。

- OS：**未指定**
- VS Codeバージョン：**未指定**
- Claude Code拡張バージョン：**未指定**
- MCPサーバーのバージョン／起動コマンド：**未指定**
- CLIでの起動手順と成功ログ：**未指定**

### 本件の構成要点（切り分けの前提）
- Claude CodeのMCP設定はスコープにより保存先が異なり、少なくとも **`~/.claude.json`**（MCPサーバーのユーザー/ローカルスコープ等）と **プロジェクトルートの `.mcp.json`**（プロジェクトスコープ）を見分ける必要があります。citeturn1search1turn6view1turn4view2  
- `.mcp.json` のプロジェクトスコープサーバーは **使用前に承認が必要**で、承認状態は `claude mcp reset-project-choices` でリセットできます。citeturn6view0  
- VS Code拡張には **「Use Terminal（ターミナルモード）」**があり、GUI拡張パネルではなくCLIスタイルで実行できます（回避策として公式に案内）。citeturn13search4  
- Remote SSH/WSL/Dev Containers等では、拡張が「ローカル」か「リモート」どちらで動いているかで、参照するホームディレクトリや環境変数が変わります（＝CLIと拡張の“実行場所”が一致しているかが最重要の切り分け軸）。citeturn2search0turn12search19turn12search36  
- Workspace Trust（Restricted Mode）では、拡張機能が無効化されうるため「そもそも拡張が動いていない」系の誤認を避ける必要があります。citeturn2search2turn5view1  

---

## 原因候補の整理と優先度

本件は「症状（何が起きているか）」を固定すると、原因候補が急速に絞れます。以下は **“CLIは動く”を前提**に、拡張側で起きやすい原因候補を網羅し、優先度順（S→A→B→C）に整理したものです。

### 優先度S（まずログで即判定する）
- **拡張内蔵MCPブリッジ（`claude-vscode`）の不具合／プロトコル不整合（-32601）**  
  兆候：Outputや拡張ログに `MCP error -32601: Method not found`、起動直後に「Host Extension has terminated」やクラッシュループ、応答が返らないハング。CLIは同一プロジェクト・同一設定でも正常。citeturn11view5turn10view6turn11view1turn11view4  
  影響：MCPツール列挙（`tools/list`）に失敗し、セッション終了・拡張ホストクラッシュ・二重起動などへ波及する報告があります。citeturn11view5turn11view1  

- **VS Code拡張側のUIがMCP設定を反映しない／表示が空になる既知不具合**  
  兆候：`/mcp` が空、あるいは “No servers configured” と表示される（CLIの `claude mcp list` は接続済みを示す）。再起動やreset-project-choicesでも改善しない例が報告。citeturn10view0turn10view1turn11view0  

### 優先度A（「拡張とCLIが同じものを見ていない」を疑う）
- **CLIで追加したMCPが拡張UIに同期されない／別リストのように見える**  
  「CLIで `claude mcp add` → `claude mcp list` では接続、しかし拡張の“Manage MCP Servers”に出ない。両者が“別リスト”を持つように見える」という報告。citeturn10view2  
  ここは「拡張とCLIが同じ `.claude.json` を見ているはず」という前提自体が崩れている可能性があるため、まず **実行場所（ローカル/リモート）** と **参照している設定ファイルパス** を揃える必要があります。citeturn2search0turn12search36turn1search1  

- **作業ディレクトリ／プロジェクトパスの不一致（Windowsのドライブレター大小文字・区切り差など）**  
  VS Code拡張が認識するWorking directory表記と、`.claude.json` の `projects` キーが **文字列として一致せず** MCPが効かない事例（Windows、`C:` vs `c:` 等）が日本語で報告されています。citeturn9view0  
  “CLIで動く”のに“拡張で見えない”は、この **パス正規化差**で説明できるケースがあります。

- **Remote/WSL/Dev Containersによる“実行場所”差**  
  VS Codeは拡張をローカルかリモートで動かします。拡張がリモート側で動く場合、参照する `~/.claude.json` や環境変数もリモート側になり、ローカルで動かしたCLIと状態がズレます。citeturn2search0turn12search19turn12search36  

### 優先度B（環境差・起動差で「起動できていない」を疑う）
- **PATH/環境変数差によりstdio MCPサーバー起動が失敗（`spawn ... ENOENT`）**  
  公式MCPドキュメントでも、`command` がPATH上に無いと `spawn ... ENOENT` が起きるため **フルパス指定**が案内されています。citeturn7view2  
  また、VS Code拡張ホストの `process.env.PATH` がシェルと一致しない現象はMicrosoft側Issueでも示されています。citeturn12search2  
  日本語では、MCP（stdio）で `uvx` が見つからずENoentになるケースで「フルパス指定／envでPATH指定」が実例として整理されています。citeturn8view1  

- **`.mcp.json` の承認未実施／承認状態の破損**  
  `.mcp.json` のサーバーは使用前承認が必要で、承認状態を `claude mcp reset-project-choices` でリセットできます。拡張だけが承認状態を誤認している場合、これが効く可能性があります。citeturn6view0  

- **拡張機能が二重にMCPサーバーを起動（重い・ポート競合・不可解な挙動）**  
  拡張ホストが `.mcp.json` を読んで起動し、さらに拡張がspawnするClaude CLIも `.mcp.json` を読んで **同じサーバーを2重起動**する報告があります。citeturn10view8  

### 優先度C（周辺要因：権限・ネットワーク・制約）
- **Workspace Trust（Restricted Mode）で拡張が無効化／機能制限**  
  Restricted Modeでは信頼されない拡張が無効化されるため、拡張が反応しない原因になり得ます。citeturn2search2turn5view1  

- **プロキシ／カスタムCA／mTLSなどの企業ネットワーク設定差**  
  ネットワーク系環境変数は `settings.json` でも設定可能で、プロキシ経由が必要な環境では“拡張だけ通信できない”が起こり得ます（CLIと拡張で環境変数が異なる場合）。citeturn4view3turn1search1  

- **ポート競合（OAuthコールバック等）**  
  Claude CodeのChangelogに「OAuthコールバックポート使用中でハングする不具合を修正」が明記されており、古い版では該当し得ます。citeturn1search2  

- **拡張のサンドボックス／拡張ホスト制約（Web/Remote）**  
  VS Codeの拡張は “UI/Workspace” のどちらで動くかがあり、Node APIや外部プロセス起動はリモート/制約下で問題化しやすい、という設計上の注意があります。citeturn12search19turn2search23  

- **MCPツール実行の承認ダイアログが毎回出て止まる（「動かない」に見える）**  
  VS Code拡張で「毎回Yes/No承認が必要で自動化できない」というIssueがあります。ワークフローによっては“動かない”と誤認されます。citeturn11view3  

---

## 切り分けフロー

以下は「CLI成功・拡張失敗」を前提に、**最短で原因カテゴリを確定する**ための実務フローです。

```mermaid
flowchart TD
  A[前提: CLIではMCPが動作] --> B{VS CodeがRestricted Mode?}
  B -- はい --> B1[Workspace Trustを信頼に変更し拡張を有効化]
  B -- いいえ --> C[拡張のOutput/Extension Host/Claude VSCode.logを確認]
  C --> D{ログに -32601 Method not found?}
  D -- はい --> D1[拡張内蔵MCPブリッジ不具合: 更新/ターミナルモードへ回避/Issue照合]
  D -- いいえ --> E{/mcpは何を表示?}
  E -- 空/No servers configured --> E1[設定反映不具合 or 実行場所差 or パス不一致: 1)Use Terminal回避 2)設定/承認/実行場所を検証]
  E -- サーバ一覧が出る --> F{サーバ状態はConnected?}
  F -- いいえ --> F1[起動失敗: PATH/環境変数/プロキシ/ポート/承認を順に検証]
  F -- はい --> G{ツールが使えない/見えない?}
  G -- はい --> G1[MCPツール未露出/承認待ち/権限制約/表示バグ: ツール一覧・承認UI・ログで確認]
  G -- いいえ --> H[再現手順とログをテンプレ化し既知Issueと突合]
```

このフロー中の主要分岐（Restricted Mode、/mcp管理、-32601、/mcp空、表示バグ、Use Terminal回避、設定/承認の所在）は、公式ドキュメントと既知Issueを根拠にしています。citeturn2search2turn4view0turn13search4turn11view5turn10view0turn11view0  

---

## コマンド付きチェックリスト

### 使い方
- **同じプロジェクト・同じ“実行場所”**（ローカル/Remote/WSL/Container）で、CLIと拡張の状態を揃えて取ってください。Remote環境では、拡張がどちら側で動いているかが結果を左右します。citeturn2search0turn12search36  
- 期待出力は「例」であり、文言は環境で変わります。重要なのは **該当エラー（-32601/ENOENT/No servers configured）** と **差分が取れること**です。citeturn11view5turn7view2turn11view0  

| チェック目的 | 実行手順（実務向け） | 実行コマンド例 | 期待出力例（抜粋） / 判定 |
|---|---|---|---|
| VS CodeがRestricted Modeではないか | VS Code右下に“Restricted Mode”表示がないか確認。Command PaletteでTrust管理を開く | （VS Code）`Workspaces: Manage Workspace Trust` | Restricted Modeなら拡張が無効化され得る（まず解除）。citeturn2search2turn5view1 |
| 拡張をターミナルモードへ切替（即時回避） | 設定で **Use Terminal** を有効化（または“ターミナルモードに切り替える”） | （VS Code設定）Extensions → Claude Code → Use Terminal | GUI側の不具合回避になり得る（公式手順）。citeturn13search4turn11view2 |
| MCPサーバー一覧（CLI側） | CLIが見ているMCP一覧と状態を採取 | `claude mcp list` | 例: `figma ✓ Connected` / `atlassian ✓ Connected` 等（CLI正常の証跡）。citeturn10view1turn10view0 |
| MCP管理（拡張側） | 拡張チャットで `/mcp` を実行 | （拡張チャット入力）`/mcp` | サーバー一覧が出るのが正常。空なら既知Issue群に合致。citeturn4view0turn10view0turn10view1 |
| -32601の有無（最重要） | Outputまたは拡張ログで `-32601` を検索 | （VS Code）Output: “Claude Code”/ログフォルダ、`find "-32601"` 等 | `MCP error -32601: Method not found` が出ればブリッジ不具合系。citeturn11view5turn10view6turn11view4 |
| VS Codeログの取得 | Command Paletteでログを開く/フォルダを開く | （VS Code）`Developer: Show Logs...` / `Developer: Open Logs Folder` | Extension Host/Window等のログが開ける（拡張障害の一次情報）。citeturn2search5 |
| VS Codeをverboseで起動（拡張デバッグ相当） | まず `code --help` でオプション確認→`--verbose`で起動、DevToolsでConsole確認 | `code --help` / `code --verbose <project>` | verbose＋DevToolsで異常が見える場合あり。citeturn12search0turn12search18 |
| 拡張データのリセット（公式） | 拡張をアンインストールしても残る状態を削除 | `rm -rf ~/.vscode/globalStorage/anthropic.claude-code` | 拡張状態が壊れている疑いの公式リセット。citeturn5view1 |
| `.mcp.json` の承認状態リセット | projectスコープ承認が怪しい時に実行 | `claude mcp reset-project-choices` | 次回、承認プロンプトが出る想定。citeturn6view0 |
| PATH差（CLI vs VS Code統合ターミナル） | それぞれでPATHと実行ファイル位置を採取しdiff | macOS/Linux: `echo $PATH; which npx; which uvx` / Windows: `$env:PATH; where.exe npx; where.exe uvx` | 拡張ホストではPATHが限定される事がある。フルパス化が堅い。citeturn12search2turn7view2turn8view1 |
| stdioサーバー起動失敗（ENOENT）対策 | `.mcp.json` の `command` をフルパス化、または `env.PATH` を明示 | 例（方針）: `command: "/opt/homebrew/bin/uvx"` | フルパス指定が最も確実という実例整理。citeturn8view1turn7view2 |
| Windowsでnpx stdioが落ちる | `cmd /c` ラッパーを使う（ネイティブWindows） | `claude mcp add --transport stdio my-server -- cmd /c npx -y @some/package` | ラッパー無しだと “Connection closed” になり得る（公式）。citeturn7view0 |
| 環境変数差分取得 | CLI環境とVS Code統合ターミナル環境を保存して比較 | macOS/Linux: `env | sort > env_cli.txt` / VS Code統合ターミナルでも同様→`diff -u ...` | 差分（HTTP_PROXY等）が出れば拡張のみ失敗の根拠になる。citeturn4view3turn12search36 |
| プロセス二重起動/残存確認 | Claude/MCPサーバーが二重起動してないか確認 | macOS/Linux: `ps aux | egrep 'claude|mcp'` / Windows: `Get-Process | ? {$_.ProcessName -match 'claude'}` | `.mcp.json` 由来で二重起動の報告あり。citeturn10view8turn11view1 |
| ポート競合確認（OAuth/HTTP MCP） | コールバックポート等が埋まっていないか確認 | macOS/Linux: `lsof -nP -iTCP:<PORT> -sTCP:LISTEN` / Windows: `netstat -ano | findstr :<PORT>` | 競合時にOAuthがハングする不具合修正がChangelogにある。citeturn1search2 |
| “Working directory”の不一致確認（Windows重要） | 拡張に「Working directory」を問う→`.claude.json` のprojectsキーと比較 | （拡張チャット）「Working directoryを教えて」 | `C:`/`c:`不一致でMCPが効かない事例（日本語報告）。citeturn9view0turn1search1 |

---

## 既知事例と一次情報の要約

本件に直結する「CLIはOK／拡張はNG」の類似事例を、信頼度の高い一次情報（公式Docs＋GitHub Issues）中心に、要点だけまとめます。括弧内は根拠ソースです。

### /mcpが空・CLIで追加したMCPが拡張に出ない
- **macOSで、CLIで `claude mcp add` 済みなのに拡張の `/mcp` に何も表示されない**（Claude Code 2.1.11）。再現手順が非常に近い。citeturn10view0  
- **macOSで、`~/.claude.json` と `.mcp.json` 両方に設定がありCLIは接続済みでも、拡張の `/mcp` が空でツールが使えない**（拡張 2.1.38）。reset-project-choices等も試しているが改善しない旨。citeturn10view1  

### CLIと拡張のMCP一覧が同期しないように見える
- **Windowsで、CLIの `claude mcp list` と拡張UI（Manage MCP Servers）の一覧が一致せず、別のサーバーリストを持つように見える**という報告。CLIは `C:\Users\<user>\.claude.json` を読む一方で、拡張は別管理に見えると記載。citeturn10view2  

### MCPツールが拡張内で露出しない（価値が大きく下がる）
- **VS Code拡張内でMCP統合を活用できない**というIssueで、回避策として **CLI利用**が明示。citeturn10view3  

### 起動はするが接続失敗が“静かに”起きる
- **拡張起動時にMCPサーバーが黙って接続失敗し、ツールが欠落する（警告なし）**という報告。特にバックエンド接続を伴うカスタムMCPを想定。citeturn10view4  

### Native UIがMCP接続を阻害する疑い
- **拡張のNative UI（GUIパネル）を使うとMCP接続が大幅に遅延/失敗し、ターミナル実行では問題ない**という報告。citeturn10view5  
- これに対し、公式ドキュメントは **Use Terminal（ターミナルモード）** を案内しており、実務上の第一回避策として妥当。citeturn13search4  

### `claude-vscode` ブリッジが -32601 で崩壊し、拡張がクラッシュ/ハング
- **Windowsで拡張が起動直後にクラッシュループ**し、`claude-vscode` MCPへの `tools/list` が `-32601` で失敗、SessionEnd→拡張ホスト終了という詳細ログ付き報告。CLIは同条件で正常。citeturn11view5  
- **-32601はJSON-RPCの“Method not found”で、拡張が `claude-vscode` MCPに未実装メソッドを呼んでいる可能性**が分析されています。citeturn11view4turn10view6  
- **二重起動（同時に複数のClaudeプロセスが設定ファイルに書き込み）＋-32601でハング→拡張ホストクラッシュ**という報告もあり、現象が複合化し得ます。citeturn11view1  

### /mcp UIだけが壊れて “No servers configured” と出る（実際は接続）
- **遅いstdioサーバー（npx起動）を `.mcp.json` に追加すると `/mcp` が “No servers configured” と表示されるが、実際はツールが使える**という報告。UI表示側のレース/状態破損疑い。citeturn11view0  

### `.mcp.json` 由来のMCPサーバー二重起動
- **拡張ホストと拡張がspawnするClaude CLIが両方 `.mcp.json` を読んでMCPサーバーを2重起動**するという報告（プロセスツリー付き）。citeturn10view8  

### 日本語の実例（WindowsのWorking directoryと設定パス不一致）
- **Windowsで、VS Code拡張が認識するWorking directoryの表記（c:など）と `.claude.json` のprojectsキー（C:など）が一致せずMCPが認識されない**という日本語の備忘録。CLIでは `claude mcp list` が正常という点で本件に酷似。citeturn9view0  

---

## 回避策と恒久対策

ここでは「まず動かす（回避策）」→「再発を減らす（恒久対策）」の順で、優先度付きに提示します。

### 優先度Sの回避策（業務を止めない）
- **Use Terminal（ターミナルモード）に切り替える**  
  Native UI側のMCP障害（接続遅延・未露出・-32601等）を回避できる可能性があり、公式に案内されている切替手段です。citeturn13search4turn10view5turn11view2  

- **VS Code統合ターミナルでCLIを直接使う（必要なら `/ide` 接続）**  
  公式ドキュメント上も「VS Code内でCLIを実行」でき、会話履歴も共有できる前提です。拡張内のMCPが不調でもCLIで外部ツール連携を継続できます。citeturn13search4turn4view0turn10view3  

- **-32601が出る場合は“拡張側の不具合”として切り分け、更新＋回避（Use Terminal/CLI）に即移行**  
  -32601は拡張内蔵ブリッジ層の問題で説明され、クラッシュループ/ハングに直結します。まず回避に寄せ、再現ログを整えてIssue照合・アップデート適用が現実的です。citeturn11view5turn11view4turn10view6  

### 優先度Aの恒久対策（構成・スコープ・承認の統制）
- **MCP設定の所在を統一し、スコープを明確化する（`~/.claude.json` / `.mcp.json`）**  
  どこにサーバーが登録されているか（user/local/project/managed）を確定し、拡張とCLIが同じ場所（同じ実行側）を参照するよう運用を揃えます。citeturn1search1turn6view1turn12search36  

- **`.mcp.json` 承認状態をリセットして再承認する**  
  projectスコープは承認が必要で、承認状態が壊れていると“拡張だけ見えない”が起こり得ます。citeturn6view0  

- **Windows: プロジェクトパス表記（ドライブレター大小文字・区切り）を正規化**  
  `.claude.json` の `projects` キーが拡張のWorking directory表記と一致するよう統一します（例：`C:/...` と `c:/...` の両方が混在しないようにする）。citeturn9view0turn1search1  

- **拡張データを公式手順でリセット**  
  拡張状態の破損・キャッシュ不整合が疑われる場合の標準対処として、公式が `~/.vscode/globalStorage/anthropic.claude-code` 削除を提示しています。citeturn5view1turn11view1  

### 優先度Bの恒久対策（環境差：PATH/環境変数/起動方式）
- **stdio MCPサーバーは“フルパス指定”を基本戦略にする**  
  PATH差は拡張ホストで起こり得るため、`command` をフルパス化するのが最も堅いです（公式でも `spawn ENOENT` の典型対処としてフルパスを案内）。citeturn7view2turn12search2  
  日本語の実例でも、`uvx` を `/opt/homebrew/bin/uvx` に変えることで解決した手順が整理されています。citeturn8view1  

- **`.mcp.json` の `env` でPATHや必要変数を明示**  
  `.mcp.json` は環境変数展開 `${VAR}` に対応するため、チーム共有でもローカル差分を吸収できます（ただし、拡張側に必要VARが無いと展開失敗し得るため、VARの供給元を統制）。citeturn6view1turn4view3  

- **Windows（ネイティブ）でnpx stdioを使う場合は `cmd /c` を徹底**  
  `cmd /c` ラッパー無しで “Connection closed” になり得ることが公式に書かれています。citeturn7view0  

- **Remote/WSL/Containerでは「拡張が動いている側」でCLIを実行して検証**  
  Remote環境の拡張ホストは、デフォルトシェルを起動して環境変数を評価している、という説明がVS Code公式にあります。検証コマンドは必ず“同じ側”で揃えてください。citeturn12search36turn2search0  

### 優先度Cの恒久対策（ネットワーク・ポート・ポリシー）
- **OAuth/ポート競合の検査とアップデート**  
  OAuthコールバックポート使用中でハングする不具合修正がChangelogにあるため、症状が「認証で固まる」ならアップデートとポート競合チェックを優先します。citeturn1search2turn11view0  

- **企業プロキシ環境は `network-config` に沿って設定統一**  
  CLIと拡張でHTTP_PROXY等が違うと「片方だけ通信できない」が起こり得るため、`settings.json` 側で統一する方が事故が減ります。citeturn4view3turn1search1  

- **管理対象MCP（managed-mcp.json）/許可・拒否リストの確認**  
  企業配布の `managed-mcp.json` や `allowedMcpServers/deniedMcpServers` により、特定のMCPサーバーがブロックされる設計です。CLIと拡張で見え方が違う場合、ポリシーの影響も疑います。citeturn6view1turn7view3turn1search1  

### 起動スクリプト／systemdサービス化（恒久対策の方向性）
OSが**未指定**のため、ここでは「設計として再現性が高い」方向性を提示します。

- **狙い**：stdio（拡張ホストがspawn）に依存せず、**HTTP型MCPサーバーを“常駐1本化”**して、Claude Code（拡張/CLI）からはHTTPで接続する。  
  これにより、PATH差・二重起動・起動タイムアウト・UIレース（/mcp誤表示）などの影響を受けにくくします。citeturn10view8turn11view0turn4view0  

- **systemd（Linux系）のテンプレ方針（例）**  
  1) MCPサーバーを `127.0.0.1:<port>` で起動するUnitを作る（常駐）  
  2) Claude側は `claude mcp add --transport http <name> http://127.0.0.1:<port>/mcp` のようにHTTP接続で登録する  
  3) ポート競合はservice側で吸収（必要なら別ポートへ）し、VS Code拡張のspawnに依存しない

- **起動スクリプト（クロスプラットフォーム）方針**  
  - stdioが必要な場合でも、`command` に直接実行バイナリを書くのではなく、**ラッパースクリプトでPATH/ENV/cwdを固定**してから実行する（拡張ホスト環境差を吸収）。citeturn7view2turn12search2turn8view1  

（注）上記は“方針”であり、具体Unit/スクリプトはOS・起動コマンドが**未指定**のためテンプレ確定ができません。必要なら、OS/起動コマンドが分かった時点で最小テンプレに落とし込めます。

---

## 再現手順とログ収集テンプレート

以下は、GitHub Issue提出や社内エスカレーションでそのまま使える **最小再現＋ログ収集テンプレ** です。

### 最小再現テンプレ
- OS：**未指定**（例：Windows 11 / macOS / Ubuntu / WSL2 / Dev Containers 等）
- VS Code：**未指定**（Stable/Insidersも）
- Claude Code拡張：**未指定**
- Claude Code CLI：`claude --version` の出力を貼る（**未指定**のため要取得）
- MCPサーバー：バージョン/起動コマンド（**未指定**のため要取得）
- 実行形態：Local / Remote SSH / WSL / Dev Containers（拡張が動いている側を明記）citeturn2search0turn12search36  

手順：
1) VS Codeで対象プロジェクトを開く  
2) 拡張チャットで `/mcp` を実行し、表示内容を記録（空/No servers configured/一覧あり等）citeturn4view0turn10view0turn11view0  
3) VS Code統合ターミナルで `claude mcp list` を実行し、出力を保存citeturn10view1  
4) Output/拡張ログで `-32601` / `ENOENT` / “No servers configured” 等を検索し、該当箇所（前後20行）を保存citeturn11view5turn7view2turn11view0  

期待結果：
- `/mcp` にサーバーが表示され、Connectedになり、MCPツールが利用できるciteturn4view0  

実結果：
- （例）`/mcp` が空、`MCP error -32601`、stdioサーバーが `spawn ... ENOENT`、などを具体的に記載citeturn11view5turn7view2turn10view0  

### ログ収集テンプレ
- VS Code側ログ  
  - `Developer: Show Logs...` で **Extension Host** を開き、該当箇所を貼付citeturn2search5  
  - `Developer: Open Logs Folder` からログフォルダを開き、Claude Code関連ファイル（例：Issue内で言及される `Claude VSCode.log` 等）を添付citeturn2search5turn11view0  
  - 必要なら `code --verbose` で起動して追加ログを採取citeturn12search18turn12search0  

- Claude Code側（CLI）ログ  
  - `claude mcp list` の出力  
  - `claude mcp reset-project-choices` 実行の有無と結果citeturn6view0  

- 環境差分  
  - CLI実行シェルの `env | sort` と、VS Code統合ターミナルの `env | sort` をdiff  
  - PATHと実行ファイル位置（`which`/`where.exe`）  
  - WindowsならWorking directory表記と `.claude.json` projectsキーの一致確認（大小文字・区切り）citeturn9view0turn12search36  

---

以上は、現時点で公開されている一次情報（公式Docs/Changelog、GitHub Issues、VS Code公式Docs、日本語実例）に基づき、「CLIは動くが拡張で動かない」事例を **再現性の高い観点で分類し、切り分け→回避→恒久化**の順に落とし込んだものです。citeturn13search4turn6view1turn11view5turn10view0turn12search36