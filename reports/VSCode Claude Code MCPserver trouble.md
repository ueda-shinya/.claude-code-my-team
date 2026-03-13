# **Claude Code VSCode拡張機能におけるMCPサーバー接続障害：CLIとの環境的乖離と包括的解決アプローチ**

## **1\. 導入と背景：エージェント型コーディング支援とMCPの役割**

現代のソフトウェア開発において、大規模言語モデル（LLM）を統合したコーディング支援ツールは、単なるコード補完の領域を超え、リポジトリ全体のコンテキストを理解し、自律的にタスクを実行する「エージェント型」へと進化を遂げている。Anthropicが提供する「Claude Code」は、この次世代型コーディングエージェントの代表格であり、ターミナル上で動作するコマンドラインインターフェース（CLI）環境と、Visual Studio Code（VSCode）のグラフィカルな統合環境（GUI）という二つの主要なインターフェースを提供している1。

この自律型エージェントの能力を飛躍的に拡張する中核技術が、Model Context Protocol（MCP）である。MCPは、AIアプリケーションに対してローカルリソース、データベース、外部API、および各種ツールへの安全かつ制御されたアクセスを提供するためのオープンスタンダードな通信プロトコルである4。MCPサーバーを導入することにより、Claude Codeは単なるソースコードの読み書きにとどまらず、JiraやLinearからのイシュー取得、PostgreSQLデータベースへの直接クエリ、Figmaのデザインデータの参照、Chromeブラウザの自動操作など、開発ワークフロー全体をシームレスに横断することが可能となる6。

しかしながら、この高度な拡張性をVSCode環境で利用する際、極めて深刻かつ広範な障害が報告されている。具体的には、「ターミナルから起動したCLI環境（claudeコマンド）ではMCPサーバーが完全に正常に動作し、ツールが利用可能であるにもかかわらず、VSCode拡張機能のチャットパネルや内部システムからは同等のMCPサーバーが認識されない、あるいはツールが利用できない」という事象である10。この現象は、単なる設定ミスによるものではなく、Node.jsベースの独立したCLIプロセスと、厳格なサンドボックス環境下で動作するVSCode Extension Hostプロセスとの間に存在する、アーキテクチャの根本的な乖離に起因している。本レポートでは、この現象の背後にある技術的メカニズムを解体し、設定スコープの不一致、プロセス間通信の競合、非同期初期化のタイムアウト、および環境変数の消失という観点から詳細な分析を行うとともに、開発環境に応じた最適かつ具体的な解決策を提示する。

## **2\. アーキテクチャの根本的差異：CLIとVSCode Extension Host**

Claude CodeのVSCode拡張機能がCLIと同じ設定を共有しているように見えながら、実際には全く異なる挙動を示す理由は、両者が依存するホスト環境の構造的差異に求められる。ターミナル上で実行されるCLIは、ユーザーのオペレーティングシステムに直接統合された同期的なプロセスとして起動する。これに対し、VSCode拡張機能はエディタのライフサイクルにバインドされた非同期のサンドボックスプロセス内で稼働する。

| 特性 | CLI環境 (claude コマンド) | VSCode拡張機能環境 | 運用上の影響と乖離 |
| :---- | :---- | :---- | :---- |
| **プロセスモデル** | TTYベースの同期プロセス。標準入出力を直接制御。 | VSCode Extension Host内の非同期プロセス。 | 拡張機能特有の厳格なタイムアウト制限の影響を直接受ける。 |
| **環境変数の継承** | 親シェル（.bashrcや.zshrcなど）から全変数を完全に継承。 | VSCodeの内部設定（terminal.integrated.env等）の制約下で解決。 | APIキーや実行パス（PATH）の意図せぬ欠落によりMCPが起動不能に陥る。 |
| **設定ファイルの解決** | グローバルスコープ（\~/.claude.json）をカレントディレクトリから動的かつ透過的に参照。 | ワークスペースルートのローカルスコープ（.mcp.json）を優先的かつ厳格に要求。 | CLIで動作するグローバルMCPサーバーがVSCodeUI上に表示されない主要因。 |
| **排他制御とロック** | 単一プロセスの実行においてリソースを独占的に確保。 | 複数のバックグラウンドタスクが並行稼働し、ロック獲得の競合が発生。 | NON-FATAL: Lock acquisition failedエラーによる内部ステートの不整合。 |

### **2.1 環境変数と実行パス（PATH）のコンテキスト境界**

CLI環境では、ユーザーがターミナルを開いた時点で、OSの環境変数がすべて解決された状態のシェルプロセスが生成される。このため、npx、node、python、dockerといったMCPサーバーの起動に必須のコマンド群は、システムのPATHを通じて遅滞なく発見される14。しかし、VSCode拡張機能は、OSのシェルを直接経由せずに起動されることが多く、特にmacOSやLinux環境、あるいはWindows Subsystem for Linux (WSL)を経由するリモート開発環境において、シェル初期化スクリプトが読み込まれない「環境変数の真空状態」が発生する15。この結果、拡張機能がバックグラウンドでstdioベースのローカルMCPサーバーを起動しようとした際、コマンド自体が解決できず、サイレントに起動プロセスが異常終了する事態を引き起こす。

### **2.2 マルチプロセスシナリオにおけるロック競合**

Claude Codeは内部状態の管理やセッションの維持のために、ローカルディレクトリ（例：\~/.local/share/claude/versions/）に対してファイルベースのロック機構を利用している17。開発者がCLIとVSCode拡張機能を同時に稼働させている場合、あるいはVSCode内で複数の拡張機能インスタンスが並行して初期化を試みている場合、「NON-FATAL: Lock acquisition failed」というエラーがバックグラウンドで頻発する18。ログ上の記録によれば、これはマルチプロセスシナリオにおいて「予期された動作（expected）」とされているが、この競合状態がMCPサーバーとの接続プール管理や、ツール定義の読み込みフェーズに微細な遅延や不整合をもたらし、結果としてGUI側でのリソース認識の失敗に繋がっていることが示唆されている17。

## **3\. 障害の類型化とメカニズム解析**

VSCode拡張機能におけるMCPの動作不良は、単一の単純なバグではなく、複数の異なる技術的要因が引き起こす複合的な障害群である。イシューログおよびコミュニティの報告を統合すると、現象は大きく4つの類型に分類される。それぞれのメカニズムを詳細に解剖する。

### **3.1 類型A：非同期ハンドシェイクのタイムアウトによる「サイレント障害」**

この事象は、拡張機能の起動直後に発生し、エラーダイアログや警告メッセージを一切伴わずに進行するため、トラブルシューティングを極めて困難にする20。

VSCode拡張機能は、ロード時に設定ファイル（\~/.claude/settings.jsonや.mcp.json）を読み込み、そこに定義されたMCPサーバーに対して非同期のハンドシェイクを試行する20。データベース（FalkorDBやQdrantなど）の初期化を伴うもの、あるいはDockerコンテナのコールドスタートを必要とするカスタムPython stdioサーバーなどは、接続の確立とツール定義リストの返却までに数秒から数十秒の時間を要する20。VSCode拡張機能のアーキテクチャ内には、この初期化フェーズに対して非常に短く厳格なタイムアウトウィンドウが設定されている。

サーバー側の初期化がこのタイムアウトウィンドウを超過した場合、拡張機能は接続試行をサイレントに放棄する20。結果として、セッション自体は何事もなかったかのように継続されるが、ユーザーがツールリストを確認した際、あるいはAIに対して特定のツール使用を要求した際に初めて、MCPサーバーが一切認識されていない事実が露呈する20。CLI（ターミナルでのclaude実行）においては、プロセスが同期的にブロックされるか、またはより寛容なタイムアウト設定が適用されているため、同一の設定であっても100%の確率で正常に接続が完了する20。

### **3.2 類型B：ツール公開レイヤーの断絶と「ゴースト・コネクション」**

類型Aとは異なり、サーバーとの接続自体は成功しているにもかかわらず、その機能がAIに届かないという奇妙な断絶状態が存在する11。

VSCodeのチャットパネルから/mcpコマンドを実行すると、GUI上には対象のサーバーが「Connected（接続済み）」という正常なステータスで表示される11。さらに、内部のデバッグログ（\~/.claude/debug/latest）を精査すると、サーバーからのハンドシェイクに対する応答として"hasTools":trueというステータスが記録されており、システムがツールの存在を明確に認識していることが証明されている11。しかし、ユーザーがプロンプトを通じてツール（例：「raasサービスを使用して要件をリストアップして」）の実行を指示すると、Claudeは「そのツールは利用できません」と回答する11。

この事象は、WSL2（Ubuntu on Windows）環境上でDockerコンテナを利用したカスタムstdioサーバーを稼働させたケース等で顕著に報告されている11。根本的な原因は、MCPプロトコルを処理する下層レイヤーと、抽出されたツールリストをAIアシスタントのコンテキスト（LLMが利用可能なツールのホワイトリスト）にマッピング・公開する上層レイヤーとの間に存在するVSCode拡張機能特有のバグである11。通信は確立しメタデータも取得しているが、最終的なAIへの「手渡し」フェーズでロジックが破綻しているため、システム内部にのみ存在する「ゴースト・コネクション」と化している。

### **3.3 類型C：環境変数（ENV）の消失と解決失敗**

MCPサーバー、とりわけSaaSプラットフォームやクラウドデータベース（Supabase、Notion、GitHubなど）と通信するHTTPトランスポートベースのサーバーは、認証のためにアクセストークンやAPIキーを環境変数経由で動的に受け取るよう設計されている14。

設定ファイル（.mcp.json）内で、"url": "https://mcp.supabase.com/mcp?project\_ref=${SUPABASE\_PROJECT\_REF}" や "Authorization": "Bearer ${SUPABASE\_ACCESS\_TOKEN}" のように環境変数の展開記法（${VARIABLE\_NAME}）を用いた場合、CLI環境では親シェルの環境変数が正しく評価され、正常に認証が行われる21。しかし、VSCode拡張機能においては、この環境変数の展開プロセスが頻繁に失敗する21。

ユーザーの報告によれば、同一のマシン上でCursorエディタのClaude Code拡張機能を使用した場合、これらの環境変数は正しく解決されSupabaseのテーブル読み込みに成功するが、純粋なVSCode上の同拡張機能では解決に失敗し、「認証が必要です」というエラーが返される21。開発者向けのリフレッシュコマンドやエディタの再起動を実行し、システム側が「環境変数を認識した」と通知した場合であっても、実際の接続フェーズでは変数が空のまま渡され、接続障害が継続するケースが確認されている21。

### **3.4 類型D：セキュリティ制約と自動承認ループによるブロック**

MCPサーバーは、ユーザーのローカルファイルシステムへの完全なアクセスや、任意のコマンド実行権限を持ち得る強力なツールである。過去に、悪意を持って構成された.mcp.jsonファイルを開かせることで、ユーザーの同意なしにリモートコード実行（RCE）とAPIトークンの流出を引き起こす脆弱性（CVE-2025-59536）が報告された23。Anthropicはこの重大なセキュリティインシデントに対応するため、VSCode拡張機能におけるMCPサーバーの自動初期化プロセスに厳格なユーザー同意ダイアログとホワイトリスト制限を導入した23。

このセキュリティ強化措置の副作用として、正当な利用であっても、構成ファイルに適切な権限付与設定が存在しない場合、拡張機能がサイレントにサーバーの起動をブロックしたり、あるいは単一のツールを呼び出すたびに無限に承認ダイアログがポップアップし続けるという運用上の深刻な阻害要因が発生している23。CLI環境ではプロンプトに対する標準入力で一括承認が可能な場合でも、GUI環境ではこの権限管理のステートが正常に引き継がれず、ツールの使用が実質的に不可能になる事態が頻発している。

## **4\. 設定ファイルとスコープの階層構造の解明**

障害の多くが設定ファイルの配置場所や形式に起因しているため、Claude Codeが採用している複雑な設定管理の階層構造（スコープ）を正確に理解することがトラブルシューティングの第一歩となる。Claude Codeは、用途とセキュリティ要件に応じて複数の設定ファイルを使い分けており、これがVSCode拡張機能における「設定の迷子」を引き起こす要因となっている26。

| スコープ | ファイルパス | 用途と特徴 | VSCode拡張機能における挙動 |
| :---- | :---- | :---- | :---- |
| **ユーザースコープ** | \~/.claude.json または \~/.claude/settings.json | システム全体で共通する個人的な設定や、グローバルなMCPサーバー（Notion、Linear等）を登録する。 | CLIは透過的に読み込むが、**VSCode拡張機能はこれを無視する、あるいはUIに反映させないバグ**が頻発する10。 |
| **プロジェクトスコープ** | .mcp.json または .claude/settings.json | チーム全体で共有すべきリポジトリ固有のMCPサーバー（Sentry、ローカルDB等）をバージョン管理下に置く22。 | **VSCode拡張機能が最も確実に認識するファイル。** トラブルシューティングにおける構成移動の第一候補13。 |
| **ローカルスコープ** | .claude/settings.local.json | リポジトリ固有だが、バージョン管理には含めない個人の認証情報やオーバーライド設定を記述する26。 | 特定のプロジェクトにおける一時的なパッチや、自動承認設定（enabledMcpjsonServers）の注入に最適24。 |
| **マネージドスコープ** | managed-mcp.json | 組織の管理者が一元的に統制するポリシー設定。ユーザーやプロジェクトの設定を強制的に上書きする26。 | 企業環境において、特定のMCPが意図せずブロックされている原因となる場合がある26。 |

### **4.1 VSCodeネイティブのMCP設定との混同リスク**

トラブルシューティングをさらに複雑にしているのが、MicrosoftがVSCodeやGitHub Copilot向けに提供しているネイティブのMCP構成メカニズムとの混同である。VSCode自体もMCPをサポートしており、その設定ファイルは .vscode/mcp.json またはユーザープロファイル内に格納される14。このファイルは "servers" や "inputs" といった独自のスキーマを持つ14。

一方で、Claude Codeが要求するプロジェクトスコープのファイルは、単にプロジェクトルートの .mcp.json であり、その内部構造は {"mcpServers": {... }} から始まる21。開発者がVSCodeの公式ドキュメントを参照して .vscode/mcp.json にサーバーを定義しても、Claude Codeの拡張機能（およびその背後のエージェント）はこれを一切認識しない14。Claude Code拡張機能のための設定は、必ずAnthropicが指定する階層とスキーマ（https://json.schemastore.org/claude-code-settings.json）に従って記述されなければならない7。

## **5\. 具体的な対処法と恒久的回避策**

上述した障害のメカニズムとアーキテクチャの特性を踏まえ、VSCode拡張機能上でMCPサーバーを正常に稼働させる、あるいは障害を回避するための具体的かつ実践的な対処手順を提示する。

**【優先対応ステップ（トラブルシューティング要約）】**

ファクトチェックおよびコミュニティの報告に基づき、以下の順序で対処法を優先的に試すことが推奨される。

1. **設定ファイルをプロジェクト直下に配置する（最も有効）**：\~/.claude.jsonのMCP設定を、ワークスペース直下の.mcp.jsonに移動する13。  
2. **CLI手動トリガーまたはウィンドウ再読み込み**：タイムアウトによるサイレント障害を避けるため、ターミナルでclaude mcp listを実行するか、VSCodeの「Developer: Reload Window」を実行する13。  
3. **環境変数のハードコード（一時的措置）**：変数の展開に失敗する場合、.mcp.jsonに実値を直接記述する（※必ず.claudeignore等でバージョン管理から除外すること）21。  
4. **ハイブリッド運用への移行（最適解）**：VSCode拡張機能でのMCP管理を避け、ターミナルでの並行稼働に切り替える12。

以下に、それぞれの詳細な実行手順と背景を解説する。

### **5.1 構成のローカライズ：グローバルからプロジェクトスコープへの移行**

「CLIでは認識されるが、VSCode拡張機能の/mcpコマンドでは何も表示されない」という典型的な症状（類型Bおよびスコープ認識バグ）に対する最も確実な修正手法である。

**手順:**

1. ターミナルを開き、グローバル設定ファイルである \~/.claude.json の内容を確認する。  
2. VSCode拡張機能で利用したいMCPサーバーのJSON定義（例：figma、atlassian、githubなど）を特定する13。  
3. 現在のプロジェクトのルートディレクトリに新しく .mcp.json ファイルを作成する。  
4. 特定したサーバー定義を、プロジェクトルートの .mcp.json 内の "mcpServers" ブロックに直接移植する13。

この操作により、VSCode拡張機能のロードシーケンスが、遠隔のユーザースコープよりも優先度が高く、かつ拡張機能が確実にパースできる直下のファイルを読み込むようになるため、UI上に突如としてサーバーが出現し、利用可能となるケースが多数報告されている13。

### **5.2 手動トリガーによる初期化の強制と「ウォーム・リロード」**

初期化のタイムアウト（類型A）や遅延ロードの失敗によってサーバーがサイレントに放棄された場合、拡張機能の内部ステートを強制的に揺さぶることで接続を回復させることが可能である。

**アプローチ1：CLI経由のハンドシェイク誘発** VSCodeのパネルUIでサーバーが表示されない状態のまま、VSCodeの下部に統合ターミナルを開く。そこで以下のコマンドを実行する。 claude mcp list この単純なコマンド実行が、VSCode拡張機能の内部ステートマシンに対する外部からの割り込みトリガーとして機能する。CLI側でサーバーリストが列挙されるプロセスと同調して、VSCode拡張機能側でも見失っていたサーバー（HoneycombやSnowflakeなど）の初期化プロセスが再評価され、設定ファイルを書き換えることなくUI上にサーバーが復帰する現象が確認されている13。

**アプローチ2：開発者向けウィンドウの再読み込み（The "Reload" Fix）**

ローカルのDockerコンテナやデータベースの起動に時間がかかり、拡張機能の短いタイムアウトウィンドウを超過してサイレントに接続が放棄された場合の対処法である。

1. 初回起動時にMCPサーバーが接続に失敗したことを確認する（背後でDBコンテナなどは起動プロセスを継続している）。  
2. コマンドパレット（Ctrl+Shift+P または Cmd+Shift+P）を開く。  
3. **Developer: Reload Window** を選択し、VSCodeのUIをリロードする13。

この操作により、拡張機能の初期化フェーズが再実行される。2回目のハンドシェイク時には、バックエンドのデータベースやコンテナが既に初回試行によって「ウォーム（温まった）」状態になっているため、応答が即座に返り、タイムアウトの制限時間内に接続シーケンスが完了する20。

### **5.3 環境変数（ENV）とPATHの確実な注入**

VSCode Extension Hostの環境変数の真空状態（類型C）に起因するエラーを解決するためには、エディタの設定レベルでの介入、またはセキュリティを一部妥協した直接的なハードコードが必要となる。

**アプローチ1：設定ファイル内での直接定義（ハードコード）** ${SUPABASE\_ACCESS\_TOKEN} のような変数展開が拡張機能内で空文字列として評価されてしまう場合、一時的かつ確実な措置として、.mcp.json 内に変数の実値を直接記述する21。

JSON

{  
  "mcpServers": {  
    "supabase": {  
      "type": "http",  
      "url": "https://mcp.supabase.com/mcp?project\_ref=実際のプロジェクトID",  
      "headers": {  
        "Authorization": "Bearer 実際のアクセストークン"  
      }  
    }  
  }  
}

この手法を採用する場合、認証情報がソースコードリポジトリにコミットされてしまう重大なセキュリティインシデントを防ぐため、必ず .claudeignore ファイルおよび .gitignore ファイルに .mcp.json を追加し、追跡対象から除外する防御措置が必須である28。

**アプローチ2：VSCode内部設定を介したPATHの強制解決** npx や node といったコマンド自体がVSCode拡張機能から発見できない場合、VSCodeのユーザー設定（settings.json）を修正して、統合ターミナルおよび拡張機能ホストにPATH変数を強制的に注入する。 コマンドパレットから Preferences: Open Settings (JSON) を実行し、OSに応じたプロパティをファイルの末尾に追加する16。

JSON

"terminal.integrated.env.osx": {  
  "PATH": "/usr/local/bin:/opt/homebrew/bin:${env:PATH}"  
},  
"terminal.integrated.env.windows": {  
  "PATH": "C:\\\\Program Files\\\\nodejs;${env:PATH}"  
}

VSCodeを再起動することで、拡張機能がこれらのPATHを認識し、stdioタイプのMCPサーバー（AirtableやローカルPythonスクリプトなど）を正常にスポーン（起動）できるようになる16。WSL環境においては、Windows側のローミング設定ではなく、WSL側の \~/.vscode-server/data/User/mcp.json に設定を配置しなければならないという経路の違いにも留意が必要である29。

### **5.4 権限の明示的付与とタイムアウト設定のオーバーライド**

セキュリティダイアログの無限ループや、権限不足によるサイレントブロック（類型D）を回避するためには、Claude Codeの設定ファイル群に対し、セキュリティの自動承認と環境変数のオーバーライドを明示的に記述する。

プロジェクトの .claude/settings.local.json または \~/.claude/settings.json に以下のブロックを追加する7。

JSON

{  
  "enableAllProjectMcpServers": true,  
  "enabledMcpjsonServers": \[  
    "chrome-devtools",  
    "github",  
    "supabase"  
  \],  
  "permissions": {  
    "allow": \["mcp\_\_\*"\],  
    "deny":,  
    "ask":  
  },  
  "env": {  
    "BASH\_DEFAULT\_TIMEOUT\_MS": "1800000",  
    "BASH\_MAX\_TIMEOUT\_MS": "7200000"  
  }  
}

* **enableAllProjectMcpServers と enabledMcpjsonServers:** これらを記述することで、CVE対策として導入された厳格な確認ダイアログをバイパスし、信頼できるプロジェクトにおいてシームレスにMCPサーバーを起動・統合することが可能となる23。  
* **permissions.allow:** "mcp\_\_\*" というワイルドカード表現を用いることで、MCPサーバーから提供されるすべてのツール（例：mcp\_\_chrome-devtools\_\_take\_screenshot 等）に対するAIのアクセス権限を無条件で許可し、実行時のブロックや承認ダイアログの反復を防ぐ7。  
* **env ブロックによるタイムアウト延長:** 一部の重いタスク（巨大なデータベースのインデックス検索や、ブラウザでのE2Eテスト実行など）がClaude Code標準のタイムアウト制限（通常は数分）によって強制終了されるのを防ぐため、BASH\_DEFAULT\_TIMEOUT\_MS をミリ秒単位で大幅に延長する30。

## **6\. 最適解としての「ハイブリッド・ワークフロー」の実践**

これまでに詳述した設定の調整やワークアラウンドを駆使しても、VSCode拡張機能のアーキテクチャに内在する「非同期通信の不安定性」や「ツールマッピングバグ」を完全かつ恒久的に排除することは現時点では困難である。そのため、エージェントワークフローを日常的に駆使するエキスパートユーザーの間で確立された最も信頼性の高い運用形態が、「CLIの堅牢性」と「GUIの可視性」を融合させた**ハイブリッド・ワークフロー**である12。

この手法は、VSCode拡張機能のGUIインターフェースにMCPサーバーの管理や接続を一切依存させず、すべてのエージェント的タスクの実行をターミナル上のCLIプロセスに委ねるアプローチである12。

**ハイブリッド・ワークフローの構築手順と役割分担：**

1. **GUI拡張機能におけるMCPの無効化:** VSCodeのチャットパネルから呼び出せる /mcp ダイアログや、拡張機能自体の設定において、MCPサーバーの直接的な追加や管理を行わないようにする12。  
2. **ターミナルペインでのCLI常駐:** VSCodeの下部にある統合ターミナル（Integrated Terminal）ペインを広く取り、プロジェクトディレクトリで claude コマンドを実行する。このターミナルプロセスを、開発セッション中のメインの指示受け付け窓口とする12。  
3. **MCPサーバー群の堅牢な並行稼働:** ターミナル上で動作するCLIはプロセスが独立しており環境変数を完全に継承するため、Google Workspace連携（Gmail/Drive）、Chromeブラウザ自動化（Browser Tools）、Reddit API連携といった複数の重いMCPサーバーを同時に起動しても、クラッシュ率ゼロという極めて高い安定性を発揮する8。  
4. **VSCodeの本来の機能の活用:** VSCode自体は、CLIプロセス（Claude Code）がディスク上のファイルを書き換えた際の結果をリアルタイムに確認するための「高度なビューア兼エディタ」として機能させる。ファイルツリーの可視性や、VimライクなインラインDiffの確認、シンタックスハイライトといったエディタ本来の強みを享受する12。

このワークフローは、拡張機能が抱えるPATH解決の失敗やUIの切断といった煩わしいバグを完全にバイパスしつつ31、CLI版のみに先行して実装される最新機能（例：v2.1.49における、完全に隔離されたGit環境でタスクを並行処理する \--worktree オプションなど）を即座に利用できるという強力な副次的メリットをもたらす12。技術的なパッチが提供されるまでの過渡期において、開発のベロシティを最大化するための極めて実践的な最適解であると言える。

## **7\. 結論と今後の技術的展望**

Claude Codeにおける「MCPサーバーがCLIでは動くがVSCode拡張機能では動かない」という障害は、単一のコーディングミスによって引き起こされているものではない。それは、OSネイティブのターミナルという「自由で同期的な環境」と、VSCode Extension Hostという「厳格に管理された非同期のサンドボックス環境」という、全く異なる二つのパラダイム間で同一の機能セットを無理に動かそうとした結果生じた、構造的・アーキテクチャ的な軋轢の現れである。

非同期ハンドシェイクにおける猶予なきタイムアウト設定（類型A）、プロトコルとLLMコンテキスト間のマッピングバグ（類型B）、環境変数やPATHの解決能力の欠如（類型C）、そして脆弱性対策が生み出した過剰なセキュリティブロック（類型D）——これらはすべて、GUI拡張機能がCLIツールの堅牢な基盤に追いつけていない過渡期特有の課題である。

当面の解決策として、開発者は設定ファイル（.mcp.json）のスコープをプロジェクト直下に厳格に固定し、必要に応じて環境変数を明示的に注入するか、あるいは「ウィンドウの再読み込み」や「CLIからの手動トリガー」といったハック的手法を駆使してシステムの初期化を補助する必要がある。しかし、生産性の最大化とトラブルシューティングからの解放を求めるのであれば、VSCodeを強力なコードビューアとして用いながら、MCPの接続とタスク実行の重責は安定したターミナルプロセス（CLI）に委ねる「ハイブリッド・ワークフロー」の採用が最も合理的である。

Model Context Protocol（MCP）は、自律型AIエージェントが現実世界のシステムやデータと安全にやり取りするためのオープンスタンダードとして、急速にエコシステムの中心的な位置を占めつつある4。この巨大なポテンシャルをGUI環境で完全に引き出すためには、Anthropicの開発チームによるアーキテクチャレベルでの抜本的な改修が不可欠である。具体的には、起動シーケンスにおける指数的バックオフ（Exponential Backoff）を伴う再試行ロジックの導入、リソースを節約するためのツール呼び出し時の遅延ロード（Lazy Initialization）、そしてVSCode環境変数を透過的に継承しエラーを可視化する堅牢なエラーハンドリング機構の実装が急務である20。これらのインフラストラクチャが整備されるまでの間、開発者は本レポートで提示した環境境界の深い理解とワークアラウンドの適用によって、AIエージェントの能力を最大限に牽引していくことが求められる。

#### **引用文献**

1. Use Claude Code in VS Code \- Claude Code Docs, 3月 13, 2026にアクセス、 [https://code.claude.com/docs/en/vs-code](https://code.claude.com/docs/en/vs-code)  
2. Issues · anthropics/claude-code \- GitHub, 3月 13, 2026にアクセス、 [https://github.com/anthropics/claude-code/issues](https://github.com/anthropics/claude-code/issues)  
3. Claude Code for VS Code \- Visual Studio Marketplace, 3月 13, 2026にアクセス、 [https://marketplace.visualstudio.com/items?itemName=anthropic.claude-code](https://marketplace.visualstudio.com/items?itemName=anthropic.claude-code)  
4. Connect to local MCP servers \- Model Context Protocol, 3月 13, 2026にアクセス、 [https://modelcontextprotocol.io/docs/develop/connect-local-servers](https://modelcontextprotocol.io/docs/develop/connect-local-servers)  
5. Connect to external tools with MCP \- Claude API Docs, 3月 13, 2026にアクセス、 [https://platform.claude.com/docs/en/agent-sdk/mcp](https://platform.claude.com/docs/en/agent-sdk/mcp)  
6. Connect Claude Code to tools via MCP, 3月 13, 2026にアクセス、 [https://code.claude.com/docs/en/mcp](https://code.claude.com/docs/en/mcp)  
7. VSCode (Win10) \+ Claude Code: chrome-devtools-mcp keeps asking permissions — how to auto-allow? : r/ClaudeAI \- Reddit, 3月 13, 2026にアクセス、 [https://www.reddit.com/r/ClaudeAI/comments/1olnp72/vscode\_win10\_claude\_code\_chromedevtoolsmcp\_keeps/](https://www.reddit.com/r/ClaudeAI/comments/1olnp72/vscode_win10_claude_code_chromedevtoolsmcp_keeps/)  
8. Setting Up MCP Servers in Claude Code: A Tech Ritual for the Quietly Desperate \- Reddit, 3月 13, 2026にアクセス、 [https://www.reddit.com/r/ClaudeAI/comments/1jf4hnt/setting\_up\_mcp\_servers\_in\_claude\_code\_a\_tech/](https://www.reddit.com/r/ClaudeAI/comments/1jf4hnt/setting_up_mcp_servers_in_claude_code_a_tech/)  
9. MCP server – Linear Docs, 3月 13, 2026にアクセス、 [https://linear.app/docs/mcp](https://linear.app/docs/mcp)  
10. Is it me or MCP servers don't work with VScode extension? : r/ClaudeCode \- Reddit, 3月 13, 2026にアクセス、 [https://www.reddit.com/r/ClaudeCode/comments/1rhj8k7/is\_it\_me\_or\_mcp\_servers\_dont\_work\_with\_vscode/](https://www.reddit.com/r/ClaudeCode/comments/1rhj8k7/is_it_me_or_mcp_servers_dont_work_with_vscode/)  
11. VSCode Extension: MCP tools not exposed to AI assistant despite ..., 3月 13, 2026にアクセス、 [https://github.com/anthropics/claude-code/issues/11448](https://github.com/anthropics/claude-code/issues/11448)  
12. Do MCP servers just suck with the VS code extension. What is the best way to get a GUI in Claude Code \- Reddit, 3月 13, 2026にアクセス、 [https://www.reddit.com/r/ClaudeCode/comments/1r8qkac/do\_mcp\_servers\_just\_suck\_with\_the\_vs\_code/](https://www.reddit.com/r/ClaudeCode/comments/1r8qkac/do_mcp_servers_just_suck_with_the_vs_code/)  
13. \[BUG\] MCP servers configured via CLI don't load in VSCode ..., 3月 13, 2026にアクセス、 [https://github.com/anthropics/claude-code/issues/24770](https://github.com/anthropics/claude-code/issues/24770)  
14. MCP configuration reference \- Visual Studio Code, 3月 13, 2026にアクセス、 [https://code.visualstudio.com/docs/copilot/reference/mcp-configuration](https://code.visualstudio.com/docs/copilot/reference/mcp-configuration)  
15. Troubleshoot Terminal launch failures \- Visual Studio Code, 3月 13, 2026にアクセス、 [https://code.visualstudio.com/docs/supporting/troubleshoot-terminal-launch](https://code.visualstudio.com/docs/supporting/troubleshoot-terminal-launch)  
16. Visual studio code integrated terminal path different than normal terminal \- Super User, 3月 13, 2026にアクセス、 [https://superuser.com/questions/1422185/visual-studio-code-integrated-terminal-path-different-than-normal-terminal](https://superuser.com/questions/1422185/visual-studio-code-integrated-terminal-path-different-than-normal-terminal)  
17. \[Bug\] Anthropic API Error: Cannot modify thinking blocks in assistant ..., 3月 13, 2026にアクセス、 [https://github.com/anthropics/claude-code/issues/12972](https://github.com/anthropics/claude-code/issues/12972)  
18. \[Bug\] Anthropic API Error: Cannot modify thinking blocks in assistant messages \#12959, 3月 13, 2026にアクセス、 [https://github.com/anthropics/claude-code/issues/12959](https://github.com/anthropics/claude-code/issues/12959)  
19. \[Bug\] Anthropic API Error: Tool result block missing corresponding tool\_use block \#13039, 3月 13, 2026にアクセス、 [https://github.com/anthropics/claude-code/issues/13039](https://github.com/anthropics/claude-code/issues/13039)  
20. MCP server connection fails silently on VS Code extension startup ..., 3月 13, 2026にアクセス、 [https://github.com/anthropics/claude-code/issues/25751](https://github.com/anthropics/claude-code/issues/25751)  
21. \[BUG\] .env variable substitution in .mcp.json works in Cursor but not VS Code \#14032, 3月 13, 2026にアクセス、 [https://github.com/anthropics/claude-code/issues/14032](https://github.com/anthropics/claude-code/issues/14032)  
22. Claude Code MCP Servers: How to Connect, Configure, and Use Them \- Builder.io, 3月 13, 2026にアクセス、 [https://www.builder.io/blog/claude-code-mcp-servers](https://www.builder.io/blog/claude-code-mcp-servers)  
23. Caught in the Hook: RCE and API Token Exfiltration Through Claude Code Project Files | CVE-2025-59536 | CVE-2026-21852, 3月 13, 2026にアクセス、 [https://research.checkpoint.com/2026/rce-and-api-token-exfiltration-through-claude-code-project-files-cve-2025-59536/](https://research.checkpoint.com/2026/rce-and-api-token-exfiltration-through-claude-code-project-files-cve-2025-59536/)  
24. \[BUG\] Claude Code For VS Code does not use mcp servers at all \#19054 \- GitHub, 3月 13, 2026にアクセス、 [https://github.com/anthropics/claude-code/issues/19054](https://github.com/anthropics/claude-code/issues/19054)  
25. Critical: No way to bypass MCP tool approval prompts in VSCode extension · Issue \#10801 · anthropics/claude-code \- GitHub, 3月 13, 2026にアクセス、 [https://github.com/anthropics/claude-code/issues/10801](https://github.com/anthropics/claude-code/issues/10801)  
26. Claude Code settings \- Claude Code Docs, 3月 13, 2026にアクセス、 [https://code.claude.com/docs/en/settings](https://code.claude.com/docs/en/settings)  
27. Claude Code — A Practical Guide to Automating Your Development Workflow, 3月 13, 2026にアクセス、 [https://henriquesd.medium.com/claude-code-a-practical-guide-to-automating-your-development-workflow-675714db08ed](https://henriquesd.medium.com/claude-code-a-practical-guide-to-automating-your-development-workflow-675714db08ed)  
28. Configuring Claude Code Extension with AWS Bedrock (And How You Can Avoid My Mistakes) | by Vasko Kelkocev, 3月 13, 2026にアクセス、 [https://aws.plainenglish.io/configuring-claude-code-extension-with-aws-bedrock-and-how-you-can-avoid-my-mistakes-090dbed5215b](https://aws.plainenglish.io/configuring-claude-code-extension-with-aws-bedrock-and-how-you-can-avoid-my-mistakes-090dbed5215b)  
29. Unable to start MCP servers in VS Code in WSL \- Stack Overflow, 3月 13, 2026にアクセス、 [https://stackoverflow.com/questions/79706687/unable-to-start-mcp-servers-in-vs-code-in-wsl](https://stackoverflow.com/questions/79706687/unable-to-start-mcp-servers-in-vs-code-in-wsl)  
30. \[SOLUTION\] Complete Claude Code Timeout Configuration Guide \- Verified Working \#5615, 3月 13, 2026にアクセス、 [https://github.com/anthropics/claude-code/issues/5615](https://github.com/anthropics/claude-code/issues/5615)  
31. Claude Code CLI vs VS Code extension: am I missing something here? \- Reddit, 3月 13, 2026にアクセス、 [https://www.reddit.com/r/ClaudeAI/comments/1pooqgp/claude\_code\_cli\_vs\_vs\_code\_extension\_am\_i\_missing/](https://www.reddit.com/r/ClaudeAI/comments/1pooqgp/claude_code_cli_vs_vs_code_extension_am_i_missing/)