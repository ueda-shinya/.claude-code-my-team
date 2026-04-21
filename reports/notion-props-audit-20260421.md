# Notionプロパティ名監査レポート 2026-04-21 08:29

## サマリ
- 対象ディレクトリ: C:\Users\ueda-\.claude
- 検索対象拡張子: .md, .py
- スキャンファイル数: 904件
- 検出件数: 593件
- 対象ファイル数: 26件

## プロパティ名別集計

| プロパティ名 | 出現回数 | 出現ファイル数 |
|---|---|---|
| ステータス | 61 | 9 |
| メモ | 35 | 7 |
| タイトル | 21 | 5 |
| 担当 | 20 | 4 |
| 日付 | 16 | 6 |
| 種別 | 16 | 3 |
| 管理番号 | 14 | 3 |
| 発行日 | 13 | 2 |
| 投稿予定日 | 12 | 1 |
| 優先度 | 11 | 2 |
| 金額（税込） | 10 | 2 |
| 会社名 | 10 | 1 |
| 識別記号 | 10 | 1 |
| 件名 | 10 | 1 |
| 開始日 | 9 | 3 |
| 納品日 | 9 | 2 |
| 入金日 | 9 | 2 |
| 入金状況 | 9 | 2 |
| 対応レベル | 9 | 1 |
| 領域 | 9 | 1 |
| インプレッション数 | 9 | 1 |
| 真の原因に対する対策 | 8 | 1 |
| 通し番号 | 8 | 1 |
| いいね数 | 8 | 1 |
| クライアント | 8 | 1 |
| 対象環境 | 8 | 1 |
| 最終連絡日 | 7 | 1 |
| なぜ(1回目) | 7 | 1 |
| なぜ(2回目) | 7 | 1 |
| なぜ(3回目) | 7 | 1 |
| カテゴリ | 7 | 3 |
| 投稿タイトル | 7 | 1 |
| プラットフォーム | 7 | 1 |
| 真因カテゴリ | 6 | 1 |
| 真因（要約） | 6 | 1 |
| プロジェクト名 | 6 | 1 |
| フェーズ | 6 | 1 |
| ER | 6 | 1 |
| ブロッカー | 6 | 1 |
| 会社名 / 屋号 | 5 | 2 |
| 担当者名 | 5 | 1 |
| 事業種別 | 5 | 1 |
| KPI | 5 | 1 |
| 目標完了日 | 5 | 1 |
| リンク | 5 | 1 |
| 要約 | 5 | 1 |
| 番号 | 4 | 3 |
| LPモバイル離脱率% | 4 | 2 |
| 電話番号 | 4 | 1 |
| メールアドレス | 4 | 1 |
| 流入元 | 4 | 1 |
| 協力値引率 | 4 | 1 |
| object | 4 | 4 |
| 関連ファイル | 4 | 1 |
| PYTHONPATH | 4 | 4 |
| NOTION_API_TOKEN | 3 | 3 |
| NOTION_LEDGER_DB_ID | 3 | 3 |
| 顧客 | 3 | 2 |
| 対策実施日 | 3 | 1 |
| 金額 | 3 | 1 |
| リク検証 | 3 | 1 |
| カナタ判定 | 3 | 1 |
| おすすめ度 | 3 | 1 |
| 型 | 3 | 1 |
| 投稿内容案 | 3 | 1 |
| RT数 | 3 | 1 |
| 投稿内容 | 3 | 1 |
| idx | 3 | 1 |
| SCRIPT_NAME | 2 | 1 |
| NOTION_CRM_DB_ID | 2 | 2 |
| Normal | 2 | 1 |
| 作成日時 | 2 | 2 |
| 投稿日時 | 2 | 1 |
| カナタ判定理由 | 2 | 1 |
| 最終編集日時 | 2 | 1 |
| text | 1 | 1 |
| PHP_SELF | 1 | 1 |
| PHP_AUTH_USER | 1 | 1 |
| PHP_AUTH_PW | 1 | 1 |
| HTTP_AUTHORIZATION | 1 | 1 |
| REDIRECT_HTTP_AUTHORIZATION | 1 | 1 |
| LINE_WORKS_ASUKA_BOT_ID | 1 | 1 |
| LINE_WORKS_CLIENT_ID | 1 | 1 |
| LINE_WORKS_CLIENT_SECRET | 1 | 1 |
| LINE_WORKS_SERVICE_ACCOUNT | 1 | 1 |
| LINE_WORKS_PRIVATE_KEY_PATH | 1 | 1 |
| ALLOWED_USER_ID | 1 | 1 |
| ANTHROPIC_API_KEY | 1 | 1 |
| タスク名 | 1 | 1 |
| 名前 | 1 | 1 |
| 担当者 | 1 | 1 |
| PATH | 1 | 1 |
| 案件名 | 1 | 1 |
| properties | 1 | 1 |
| id | 1 | 1 |
| 実施可否 | 1 | 1 |
| 情報源 | 1 | 1 |

## ファイル別詳細

### knowledge\claude-code-cli\session-resume-troubleshooting.md
- L38: `text` — レスポンス処理 ([] アクセス)
  ```
  text = next((p['text'] for p in content if isinstance(p, dict) and p.get('type')=='text'), '')
  ```

### knowledge\wordpress-plugin\security.md
- L62: `SCRIPT_NAME` — 辞書アクセス（日本語/大文字開始キー）
  ```
  $script = $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '';
  ```
- L62: `PHP_SELF` — 辞書アクセス（日本語/大文字開始キー）
  ```
  $script = $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '';
  ```
- L65: `SCRIPT_NAME` — 辞書アクセス（日本語/大文字開始キー）
  ```
  $script = $_SERVER['SCRIPT_NAME'] ?? '';
  ```
- L136: `PHP_AUTH_USER` — 辞書アクセス（日本語/大文字開始キー）
  ```
  $user = $_SERVER['PHP_AUTH_USER'] ?? '';
  ```
- L137: `PHP_AUTH_PW` — 辞書アクセス（日本語/大文字開始キー）
  ```
  $pass = $_SERVER['PHP_AUTH_PW'] ?? '';
  ```
- L140: `HTTP_AUTHORIZATION` — 辞書アクセス（日本語/大文字開始キー）
  ```
  $auth = $_SERVER['HTTP_AUTHORIZATION']
  ```
- L141: `REDIRECT_HTTP_AUTHORIZATION` — 辞書アクセス（日本語/大文字開始キー）
  ```
  ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
  ```

### line-works-bot\scripts\server.py
- L36: `LINE_WORKS_ASUKA_BOT_ID` — 辞書アクセス（日本語/大文字開始キー）
  ```
  BOT_ID            = os.environ['LINE_WORKS_ASUKA_BOT_ID']
  ```
- L37: `LINE_WORKS_CLIENT_ID` — 辞書アクセス（日本語/大文字開始キー）
  ```
  CLIENT_ID         = os.environ['LINE_WORKS_CLIENT_ID']
  ```
- L38: `LINE_WORKS_CLIENT_SECRET` — 辞書アクセス（日本語/大文字開始キー）
  ```
  CLIENT_SECRET     = os.environ['LINE_WORKS_CLIENT_SECRET']
  ```
- L39: `LINE_WORKS_SERVICE_ACCOUNT` — 辞書アクセス（日本語/大文字開始キー）
  ```
  SERVICE_ACCOUNT   = os.environ['LINE_WORKS_SERVICE_ACCOUNT']
  ```
- L40: `LINE_WORKS_PRIVATE_KEY_PATH` — 辞書アクセス（日本語/大文字開始キー）
  ```
  PRIVATE_KEY_PATH  = os.path.expanduser(os.environ['LINE_WORKS_PRIVATE_KEY_PATH'])
  ```
- L41: `ALLOWED_USER_ID` — 辞書アクセス（日本語/大文字開始キー）
  ```
  ALLOWED_USER_ID   = os.environ['ALLOWED_USER_ID']
  ```
- L42: `ANTHROPIC_API_KEY` — 辞書アクセス（日本語/大文字開始キー）
  ```
  ANTHROPIC_API_KEY = os.environ['ANTHROPIC_API_KEY']
  ```
- L624: `ステータス` — filter/sort指定 (property: ...)
  ```
  'property': 'ステータス',
  ```
- L629: `開始日` — filter/sort指定 (property: ...)
  ```
  'sorts': [{'property': '開始日', 'direction': 'ascending'}]
  ```
- L654: `タイトル` — レスポンス処理 (.get())
  ```
  title_prop = props.get('タイトル') or props.get('タスク名') or props.get('名前') or {}
  ```
- L654: `タスク名` — レスポンス処理 (.get())
  ```
  title_prop = props.get('タイトル') or props.get('タスク名') or props.get('名前') or {}
  ```
- L654: `名前` — レスポンス処理 (.get())
  ```
  title_prop = props.get('タイトル') or props.get('タスク名') or props.get('名前') or {}
  ```

### scripts\audit-notion-props.py
- L77: `日付` — filter/sort指定 (property: ...)
  ```
  #   例: {'property': '日付', 'date': ...}
  ```
- L78: `ステータス` — filter/sort指定 (property: ...)
  ```
  #   例: "property": "ステータス"
  ```
- L85: `日付` — properties dict キー (page作成/更新)
  ```
  #   例: 'properties': { '日付': { 'date': ... } }
  ```
- L86: `タイトル` — レスポンス処理 ([] アクセス)
  ```
  #   例: properties["タイトル"] = ...
  ```
- L86: `タイトル` — 辞書アクセス（日本語/大文字開始キー）
  ```
  #   例: properties["タイトル"] = ...
  ```
- L94: `日付` — レスポンス処理 (.get())
  ```
  #   例: properties.get('日付')
  ```
- L95: `ステータス` — レスポンス処理 (.get())
  ```
  #   例: props.get("ステータス")
  ```
- L102: `日付` — レスポンス処理 ([] アクセス)
  ```
  #   例: p["日付"]
  ```
- L102: `日付` — 辞書アクセス（日本語/大文字開始キー）
  ```
  #   例: p["日付"]
  ```
- L103: `タイトル` — レスポンス処理 ([] アクセス)
  ```
  #   例: properties["タイトル"]
  ```
- L103: `タイトル` — 辞書アクセス（日本語/大文字開始キー）
  ```
  #   例: properties["タイトル"]
  ```
- L111: `優先度` — filter/sort指定 (property: ...)
  ```
  #   例: filters.append({'property': '優先度', ...})
  ```
- L111: `優先度` — filters.append() 内 property
  ```
  #   例: filters.append({'property': '優先度', ...})
  ```
- L120: `ステータス` — 辞書アクセス（日本語/大文字開始キー）
  ```
  #   例: item['ステータス'] / row['担当者'] / data['日付']
  ```
- L120: `担当者` — 辞書アクセス（日本語/大文字開始キー）
  ```
  #   例: item['ステータス'] / row['担当者'] / data['日付']
  ```
- L120: `日付` — 辞書アクセス（日本語/大文字開始キー）
  ```
  #   例: item['ステータス'] / row['担当者'] / data['日付']
  ```
- L122: `PATH` — 辞書アクセス（日本語/大文字開始キー）
  ```
  #   ※ 偽陽性（os.environ['PATH'] 等）は増えるが「広めに拾って人が確認」方針と整合
  ```

### scripts\chatwork-sync.py
- L473: `案件名` — filter/sort指定 (property: ...)
  ```
  'property': '案件名',
  ```

### scripts\fix-ledger-customer.py
- L19: `NOTION_API_TOKEN` — 辞書アクセス（日本語/大文字開始キー）
  ```
  TOKEN     = env['NOTION_API_TOKEN']
  ```
- L20: `NOTION_LEDGER_DB_ID` — 辞書アクセス（日本語/大文字開始キー）
  ```
  LEDGER_DB = env['NOTION_LEDGER_DB_ID']
  ```
- L21: `NOTION_CRM_DB_ID` — 辞書アクセス（日本語/大文字開始キー）
  ```
  CRM_DB    = env['NOTION_CRM_DB_ID']
  ```
- L105: `管理番号` — レスポンス処理 (.get())
  ```
  bangou_prop = props.get('管理番号') or props.get('番号') or {}
  ```
- L105: `番号` — レスポンス処理 (.get())
  ```
  bangou_prop = props.get('管理番号') or props.get('番号') or {}
  ```
- L129: `顧客` — properties dict キー (page作成/更新)
  ```
  'properties': {'顧客': {'relation': [{'id': crm_id}]}}
  ```

### scripts\ga4-backfill.py
- L422: `LPモバイル離脱率%` — レスポンス処理 ([] アクセス)
  ```
  props['LPモバイル離脱率%'] = {'number': round(lp_mobile_bounce, 1)}
  ```
- L422: `LPモバイル離脱率%` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['LPモバイル離脱率%'] = {'number': round(lp_mobile_bounce, 1)}
  ```

### scripts\ga4-notion-analysis.py
- L52: `日付` — filter/sort指定 (property: ...)
  ```
  'filter': {'property': '日付', 'title': {'equals': today}}
  ```

### scripts\ga4-report.py
- L426: `日付` — filter/sort指定 (property: ...)
  ```
  'filter': {'property': '日付', 'title': {'equals': _today}}
  ```
- L493: `LPモバイル離脱率%` — レスポンス処理 ([] アクセス)
  ```
  _props['LPモバイル離脱率%'] = {'number': round(_lp_mobile_bounce, 1)}
  ```
- L493: `LPモバイル離脱率%` — 辞書アクセス（日本語/大文字開始キー）
  ```
  _props['LPモバイル離脱率%'] = {'number': round(_lp_mobile_bounce, 1)}
  ```

### scripts\import-ledger.py
- L32: `NOTION_API_TOKEN` — 辞書アクセス（日本語/大文字開始キー）
  ```
  TOKEN       = env['NOTION_API_TOKEN']
  ```
- L33: `NOTION_LEDGER_DB_ID` — 辞書アクセス（日本語/大文字開始キー）
  ```
  LEDGER_DB   = env['NOTION_LEDGER_DB_ID']
  ```
- L34: `NOTION_CRM_DB_ID` — 辞書アクセス（日本語/大文字開始キー）
  ```
  CRM_DB      = env['NOTION_CRM_DB_ID']
  ```
- L113: `番号` — レスポンス処理 ([] アクセス)
  ```
  props['番号'] = {'title': [{'text': {'content': bangou}}]}
  ```
- L113: `番号` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['番号'] = {'title': [{'text': {'content': bangou}}]}
  ```
- L116: `種別` — レスポンス処理 ([] アクセス)
  ```
  props['種別'] = {'select': {'name': shubetsu}}
  ```
- L116: `種別` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['種別'] = {'select': {'name': shubetsu}}
  ```
- L119: `発行日` — レスポンス処理 ([] アクセス)
  ```
  props['発行日'] = {'date': {'start': hakkouhi}}
  ```
- L119: `発行日` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['発行日'] = {'date': {'start': hakkouhi}}
  ```
- L122: `顧客` — レスポンス処理 ([] アクセス)
  ```
  props['顧客'] = {'relation': [{'id': kokyaku_id}]}
  ```
- L122: `顧客` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['顧客'] = {'relation': [{'id': kokyaku_id}]}
  ```
- L126: `金額（税込）` — レスポンス処理 ([] アクセス)
  ```
  props['金額（税込）'] = {'number': float(kingaku)}
  ```
- L126: `金額（税込）` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['金額（税込）'] = {'number': float(kingaku)}
  ```
- L131: `メモ` — レスポンス処理 ([] アクセス)
  ```
  props['メモ'] = {'rich_text': [{'text': {'content': str(memo)[:2000]}}]}
  ```
- L131: `メモ` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['メモ'] = {'rich_text': [{'text': {'content': str(memo)[:2000]}}]}
  ```
- L134: `納品日` — レスポンス処理 ([] アクセス)
  ```
  props['納品日'] = {'date': {'start': nōhinbi}}
  ```
- L134: `納品日` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['納品日'] = {'date': {'start': nōhinbi}}
  ```
- L137: `入金日` — レスポンス処理 ([] アクセス)
  ```
  props['入金日'] = {'date': {'start': nyukinbi}}
  ```
- L137: `入金日` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['入金日'] = {'date': {'start': nyukinbi}}
  ```
- L140: `入金状況` — レスポンス処理 ([] アクセス)
  ```
  props['入金状況'] = {'select': {'name': nyukin_status}}
  ```
- L140: `入金状況` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['入金状況'] = {'select': {'name': nyukin_status}}
  ```
- L143: `担当` — レスポンス処理 ([] アクセス)
  ```
  props['担当'] = {'rich_text': [{'text': {'content': str(tanto)[:200]}}]}
  ```
- L143: `担当` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['担当'] = {'rich_text': [{'text': {'content': str(tanto)[:200]}}]}
  ```

### scripts\md-to-docx.py
- L43: `Normal` — 辞書アクセス（日本語/大文字開始キー）
  ```
  normal_style = doc.styles['Normal']
  ```
- L386: `Normal` — 辞書アクセス（日本語/大文字開始キー）
  ```
  para.style = self._doc.styles['Normal']
  ```

### scripts\notion-crm.py
- L176: `会社名 / 屋号` — レスポンス処理 ([] アクセス)
  ```
  props["会社名 / 屋号"] = {"title": [{"text": {"content": data["会社名"]}}]}
  ```
- L176: `会社名 / 屋号` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["会社名 / 屋号"] = {"title": [{"text": {"content": data["会社名"]}}]}
  ```
- L176: `会社名` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["会社名 / 屋号"] = {"title": [{"text": {"content": data["会社名"]}}]}
  ```
- L178: `担当者名` — レスポンス処理 ([] アクセス)
  ```
  props["担当者名"] = {"rich_text": [{"text": {"content": data["担当者名"]}}]}
  ```
- L178: `担当者名` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["担当者名"] = {"rich_text": [{"text": {"content": data["担当者名"]}}]}
  ```
- L178: `担当者名` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["担当者名"] = {"rich_text": [{"text": {"content": data["担当者名"]}}]}
  ```
- L180: `電話番号` — レスポンス処理 ([] アクセス)
  ```
  props["電話番号"] = {"phone_number": data["電話番号"]}
  ```
- L180: `電話番号` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["電話番号"] = {"phone_number": data["電話番号"]}
  ```
- L180: `電話番号` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["電話番号"] = {"phone_number": data["電話番号"]}
  ```
- L182: `メールアドレス` — レスポンス処理 ([] アクセス)
  ```
  props["メールアドレス"] = {"email": data["メールアドレス"]}
  ```
- L182: `メールアドレス` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["メールアドレス"] = {"email": data["メールアドレス"]}
  ```
- L182: `メールアドレス` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["メールアドレス"] = {"email": data["メールアドレス"]}
  ```
- L184: `ステータス` — レスポンス処理 ([] アクセス)
  ```
  props["ステータス"] = {"select": {"name": data["ステータス"]}}
  ```
- L184: `ステータス` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["ステータス"] = {"select": {"name": data["ステータス"]}}
  ```
- L184: `ステータス` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["ステータス"] = {"select": {"name": data["ステータス"]}}
  ```
- L186: `事業種別` — レスポンス処理 ([] アクセス)
  ```
  props["事業種別"] = {"select": {"name": data["事業種別"]}}
  ```
- L186: `事業種別` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["事業種別"] = {"select": {"name": data["事業種別"]}}
  ```
- L186: `事業種別` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["事業種別"] = {"select": {"name": data["事業種別"]}}
  ```
- L188: `担当` — レスポンス処理 ([] アクセス)
  ```
  props["担当"] = {"rich_text": [{"text": {"content": data["担当"]}}]}
  ```
- L188: `担当` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["担当"] = {"rich_text": [{"text": {"content": data["担当"]}}]}
  ```
- L188: `担当` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["担当"] = {"rich_text": [{"text": {"content": data["担当"]}}]}
  ```
- L190: `最終連絡日` — レスポンス処理 ([] アクセス)
  ```
  props["最終連絡日"] = {"date": {"start": data["最終連絡日"]}}
  ```
- L190: `最終連絡日` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["最終連絡日"] = {"date": {"start": data["最終連絡日"]}}
  ```
- L190: `最終連絡日` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["最終連絡日"] = {"date": {"start": data["最終連絡日"]}}
  ```
- L192: `流入元` — レスポンス処理 ([] アクセス)
  ```
  props["流入元"] = {"select": {"name": data["流入元"]}}
  ```
- L192: `流入元` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["流入元"] = {"select": {"name": data["流入元"]}}
  ```
- L192: `流入元` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["流入元"] = {"select": {"name": data["流入元"]}}
  ```
- L194: `メモ` — レスポンス処理 ([] アクセス)
  ```
  props["メモ"] = {"rich_text": [{"text": {"content": data["メモ"]}}]}
  ```
- L194: `メモ` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["メモ"] = {"rich_text": [{"text": {"content": data["メモ"]}}]}
  ```
- L194: `メモ` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["メモ"] = {"rich_text": [{"text": {"content": data["メモ"]}}]}
  ```
- L196: `識別記号` — レスポンス処理 ([] アクセス)
  ```
  props["識別記号"] = {"rich_text": [{"text": {"content": data["識別記号"]}}]}
  ```
- L196: `識別記号` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["識別記号"] = {"rich_text": [{"text": {"content": data["識別記号"]}}]}
  ```
- L196: `識別記号` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["識別記号"] = {"rich_text": [{"text": {"content": data["識別記号"]}}]}
  ```
- L198: `協力値引率` — レスポンス処理 ([] アクセス)
  ```
  props["協力値引率"] = {"rich_text": [{"text": {"content": data["協力値引率"]}}]}
  ```
- L198: `協力値引率` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["協力値引率"] = {"rich_text": [{"text": {"content": data["協力値引率"]}}]}
  ```
- L198: `協力値引率` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["協力値引率"] = {"rich_text": [{"text": {"content": data["協力値引率"]}}]}
  ```
- L241: `最終連絡日` — filter/sort指定 (property: ...)
  ```
  "sorts": [{"property": "最終連絡日", "direction": "descending"}]
  ```
- L251: `識別記号` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f"{row['識別記号']:6} {row['会社名'][:22]:22} {row['ステータス']:8} {row['事業種別']:8} {row['最終連絡日']:12}")
  ```
- L251: `会社名` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f"{row['識別記号']:6} {row['会社名'][:22]:22} {row['ステータス']:8} {row['事業種別']:8} {row['最終連絡日']:12}")
  ```
- L251: `ステータス` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f"{row['識別記号']:6} {row['会社名'][:22]:22} {row['ステータス']:8} {row['事業種別']:8} {row['最終連絡日']:12}")
  ```
- L251: `事業種別` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f"{row['識別記号']:6} {row['会社名'][:22]:22} {row['ステータス']:8} {row['事業種別']:8} {row['最終連絡日']:12}")
  ```
- L251: `最終連絡日` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f"{row['識別記号']:6} {row['会社名'][:22]:22} {row['ステータス']:8} {row['事業種別']:8} {row['最終連絡日']:12}")
  ```
- L271: `会社名` — 辞書アクセス（日本語/大文字開始キー）
  ```
  if not data["会社名"]:
  ```
- L279: `会社名` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f"\n追加しました: {data['会社名']}")
  ```
- L291: `会社名 / 屋号` — filter/sort指定 (property: ...)
  ```
  "filter": {"property": "会社名 / 屋号", "rich_text": {"contains": keyword}}
  ```
- L309: `識別記号` — 辞書アクセス（日本語/大文字開始キー）
  ```
  no = row["識別記号"] or "（未発行）"
  ```
- L310: `会社名` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f"  {i}. [{no}] {row['会社名']}")
  ```
- L318: `識別記号` — 辞書アクセス（日本語/大文字開始キー）
  ```
  if row["識別記号"]:
  ```
- L319: `識別記号` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f"\nこの顧客の識別記号は既に発行済みです: {row['識別記号']}")
  ```
- L323: `会社名` — 辞書アクセス（日本語/大文字開始キー）
  ```
  confirm = input(f"\n  識別記号 [{new_no}] を {row['会社名']} に発行しますか？ [y/N]: ").strip().lower()
  ```
- L329: `識別記号` — properties dict キー (page作成/更新)
  ```
  "properties": {"識別記号": {"rich_text": [{"text": {"content": new_no}}]}}
  ```
- L331: `会社名` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f"\n発行しました: [{new_no}] {row['会社名']}")
  ```
- L338: `会社名 / 屋号` — filter/sort指定 (property: ...)
  ```
  {"property": "会社名 / 屋号", "rich_text": {"contains": keyword}},
  ```
- L339: `担当者名` — filter/sort指定 (property: ...)
  ```
  {"property": "担当者名", "rich_text": {"contains": keyword}},
  ```
- L340: `メモ` — filter/sort指定 (property: ...)
  ```
  {"property": "メモ", "rich_text": {"contains": keyword}},
  ```
- L354: `会社名` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f"{short_id:10} {row['会社名'][:20]:20} {row['ステータス']:8} {row['最終連絡日']:12}")
  ```
- L354: `ステータス` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f"{short_id:10} {row['会社名'][:20]:20} {row['ステータス']:8} {row['最終連絡日']:12}")
  ```
- L354: `最終連絡日` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f"{short_id:10} {row['会社名'][:20]:20} {row['ステータス']:8} {row['最終連絡日']:12}")
  ```
- L372: `識別記号` — 辞書アクセス（日本語/大文字開始キー）
  ```
  no_display = f"  識別記号: {current['識別記号']}（発行済み・変更不可）\n" if current["識別記号"] else ""
  ```
- L372: `識別記号` — 辞書アクセス（日本語/大文字開始キー）
  ```
  no_display = f"  識別記号: {current['識別記号']}（発行済み・変更不可）\n" if current["識別記号"] else ""
  ```
- L373: `会社名` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f"\n--- 更新: {current['会社名']} ---")
  ```
- L378: `会社名` — 辞書アクセス（日本語/大文字開始キー）
  ```
  "会社名": prompt("会社名 / 屋号", current["会社名"]),
  ```
- L379: `担当者名` — 辞書アクセス（日本語/大文字開始キー）
  ```
  "担当者名": prompt("担当者名", current["担当者名"]),
  ```
- L380: `電話番号` — 辞書アクセス（日本語/大文字開始キー）
  ```
  "電話番号": prompt("電話番号", current["電話番号"]),
  ```
- L381: `メールアドレス` — 辞書アクセス（日本語/大文字開始キー）
  ```
  "メールアドレス": prompt("メールアドレス", current["メールアドレス"]),
  ```
- L382: `ステータス` — 辞書アクセス（日本語/大文字開始キー）
  ```
  "ステータス": prompt_choice("ステータス", STATUS_OPTIONS, current["ステータス"]),
  ```
- L383: `事業種別` — 辞書アクセス（日本語/大文字開始キー）
  ```
  "事業種別": prompt_choice("事業種別", BIZTYPE_OPTIONS, current["事業種別"]),
  ```
- L384: `担当` — 辞書アクセス（日本語/大文字開始キー）
  ```
  "担当": prompt("担当", current["担当"]),
  ```
- L385: `最終連絡日` — 辞書アクセス（日本語/大文字開始キー）
  ```
  "最終連絡日": prompt_date("最終連絡日（YYYY-MM-DD）", current["最終連絡日"]),
  ```
- L386: `流入元` — 辞書アクセス（日本語/大文字開始キー）
  ```
  "流入元": prompt_choice("流入元", SOURCE_OPTIONS, current["流入元"]),
  ```
- L387: `協力値引率` — 辞書アクセス（日本語/大文字開始キー）
  ```
  "協力値引率": prompt("協力値引率", current["協力値引率"]),
  ```
- L388: `メモ` — 辞書アクセス（日本語/大文字開始キー）
  ```
  "メモ": prompt("メモ", current["メモ"]),
  ```

### scripts\notion-kaizen.py
- L193: `object` — filter/sort指定 (property: ...)
  ```
  'filter': {'property': 'object', 'value': 'database'},
  ```
- L280: `ステータス` — 辞書アクセス（日本語/大文字開始キー）
  ```
  s = item['ステータス']
  ```
- L296: `タイトル` — filter/sort指定 (property: ...)
  ```
  'filter': {'property': 'タイトル', 'title': {'contains': partial_title}}
  ```
- L316: `タイトル` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  - {t["タイトル"]} [{t["ステータス"]}]')
  ```
- L316: `ステータス` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  - {t["タイトル"]} [{t["ステータス"]}]')
  ```
- L447: `対応レベル` — 辞書アクセス（日本語/大文字開始キー）
  ```
  level_str = item['対応レベル'] if item['対応レベル'] else '-'
  ```
- L447: `対応レベル` — 辞書アクセス（日本語/大文字開始キー）
  ```
  level_str = item['対応レベル'] if item['対応レベル'] else '-'
  ```
- L448: `領域` — 辞書アクセス（日本語/大文字開始キー）
  ```
  area_str = item['領域'] if item['領域'] else '-'
  ```
- L448: `領域` — 辞書アクセス（日本語/大文字開始キー）
  ```
  area_str = item['領域'] if item['領域'] else '-'
  ```
- L449: `日付` — 辞書アクセス（日本語/大文字開始キー）
  ```
  date_str = item['日付'] if item['日付'] else '-'
  ```
- L449: `日付` — 辞書アクセス（日本語/大文字開始キー）
  ```
  date_str = item['日付'] if item['日付'] else '-'
  ```
- L450: `真因カテゴリ` — 辞書アクセス（日本語/大文字開始キー）
  ```
  root_str = item['真因カテゴリ'] if item['真因カテゴリ'] else '-'
  ```
- L450: `真因カテゴリ` — 辞書アクセス（日本語/大文字開始キー）
  ```
  root_str = item['真因カテゴリ'] if item['真因カテゴリ'] else '-'
  ```
- L451: `真因（要約）` — 辞書アクセス（日本語/大文字開始キー）
  ```
  summary_str = f'\n      真因: {item["真因（要約）"]}' if item['真因（要約）'] else ''
  ```
- L451: `真因（要約）` — 辞書アクセス（日本語/大文字開始キー）
  ```
  summary_str = f'\n      真因: {item["真因（要約）"]}' if item['真因（要約）'] else ''
  ```
- L453: `真の原因に対する対策` — 辞書アクセス（日本語/大文字開始キー）
  ```
  countermeasure_str = '✓対策あり' if item['真の原因に対する対策'] else '対策なし'
  ```
- L455: `ステータス` — 辞書アクセス（日本語/大文字開始キー）
  ```
  f'[{item["ステータス"]}] {item["タイトル"]}'
  ```
- L455: `タイトル` — 辞書アクセス（日本語/大文字開始キー）
  ```
  f'[{item["ステータス"]}] {item["タイトル"]}'
  ```
- L479: `領域` — レスポンス処理 ([] アクセス)
  ```
  props['領域'] = {'select': {'name': area}}
  ```
- L479: `領域` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['領域'] = {'select': {'name': area}}
  ```
- L482: `真因カテゴリ` — レスポンス処理 ([] アクセス)
  ```
  props['真因カテゴリ'] = {'select': {'name': root_category}}
  ```
- L482: `真因カテゴリ` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['真因カテゴリ'] = {'select': {'name': root_category}}
  ```
- L485: `真因（要約）` — レスポンス処理 ([] アクセス)
  ```
  props['真因（要約）'] = rich_text_prop(root_summary)
  ```
- L485: `真因（要約）` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['真因（要約）'] = rich_text_prop(root_summary)
  ```
- L488: `関連ファイル` — レスポンス処理 ([] アクセス)
  ```
  props['関連ファイル'] = rich_text_prop(related)
  ```
- L488: `関連ファイル` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['関連ファイル'] = rich_text_prop(related)
  ```
- L492: `なぜ(1回目)` — レスポンス処理 ([] アクセス)
  ```
  props['なぜ(1回目)'] = rich_text_prop(why1)
  ```
- L492: `なぜ(1回目)` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['なぜ(1回目)'] = rich_text_prop(why1)
  ```
- L495: `なぜ(2回目)` — レスポンス処理 ([] アクセス)
  ```
  props['なぜ(2回目)'] = rich_text_prop(why2)
  ```
- L495: `なぜ(2回目)` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['なぜ(2回目)'] = rich_text_prop(why2)
  ```
- L498: `なぜ(3回目)` — レスポンス処理 ([] アクセス)
  ```
  props['なぜ(3回目)'] = rich_text_prop(why3)
  ```
- L498: `なぜ(3回目)` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['なぜ(3回目)'] = rich_text_prop(why3)
  ```
- L501: `真の原因に対する対策` — レスポンス処理 ([] アクセス)
  ```
  props['真の原因に対する対策'] = rich_text_prop(countermeasure)
  ```
- L501: `真の原因に対する対策` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['真の原因に対する対策'] = rich_text_prop(countermeasure)
  ```
- L532: `ステータス` — レスポンス処理 ([] アクセス)
  ```
  props['ステータス'] = {'select': {'name': new_status}}
  ```
- L532: `ステータス` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['ステータス'] = {'select': {'name': new_status}}
  ```
- L533: `ステータス` — 辞書アクセス（日本語/大文字開始キー）
  ```
  changes.append(f'ステータス: [{item["ステータス"]}] → [{new_status}]')
  ```
- L537: `対策実施日` — レスポンス処理 ([] アクセス)
  ```
  props['対策実施日'] = {'date': {'start': today_str}}
  ```
- L537: `対策実施日` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['対策実施日'] = {'date': {'start': today_str}}
  ```
- L541: `対応レベル` — レスポンス処理 ([] アクセス)
  ```
  props['対応レベル'] = {'select': {'name': new_level}}
  ```
- L541: `対応レベル` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['対応レベル'] = {'select': {'name': new_level}}
  ```
- L542: `対応レベル` — 辞書アクセス（日本語/大文字開始キー）
  ```
  changes.append(f'対応レベル: [{item["対応レベル"]}] → [{new_level}]')
  ```
- L545: `領域` — レスポンス処理 ([] アクセス)
  ```
  props['領域'] = {'select': {'name': new_area}}
  ```
- L545: `領域` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['領域'] = {'select': {'name': new_area}}
  ```
- L546: `領域` — 辞書アクセス（日本語/大文字開始キー）
  ```
  changes.append(f'領域: [{item["領域"]}] → [{new_area}]')
  ```
- L550: `なぜ(1回目)` — レスポンス処理 ([] アクセス)
  ```
  props['なぜ(1回目)'] = rich_text_prop(why1)
  ```
- L550: `なぜ(1回目)` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['なぜ(1回目)'] = rich_text_prop(why1)
  ```
- L551: `なぜ(1回目)` — 辞書アクセス（日本語/大文字開始キー）
  ```
  changes.append(f'なぜ(1回目): [{item["なぜ(1回目)"]}] → [{why1}]')
  ```
- L554: `なぜ(2回目)` — レスポンス処理 ([] アクセス)
  ```
  props['なぜ(2回目)'] = rich_text_prop(why2)
  ```
- L554: `なぜ(2回目)` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['なぜ(2回目)'] = rich_text_prop(why2)
  ```
- L555: `なぜ(2回目)` — 辞書アクセス（日本語/大文字開始キー）
  ```
  changes.append(f'なぜ(2回目): [{item["なぜ(2回目)"]}] → [{why2}]')
  ```
- L558: `なぜ(3回目)` — レスポンス処理 ([] アクセス)
  ```
  props['なぜ(3回目)'] = rich_text_prop(why3)
  ```
- L558: `なぜ(3回目)` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['なぜ(3回目)'] = rich_text_prop(why3)
  ```
- L559: `なぜ(3回目)` — 辞書アクセス（日本語/大文字開始キー）
  ```
  changes.append(f'なぜ(3回目): [{item["なぜ(3回目)"]}] → [{why3}]')
  ```
- L562: `真の原因に対する対策` — レスポンス処理 ([] アクセス)
  ```
  props['真の原因に対する対策'] = rich_text_prop(countermeasure)
  ```
- L562: `真の原因に対する対策` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['真の原因に対する対策'] = rich_text_prop(countermeasure)
  ```
- L563: `真の原因に対する対策` — 辞書アクセス（日本語/大文字開始キー）
  ```
  changes.append(f'真の原因に対する対策: [{item["真の原因に対する対策"]}] → [{countermeasure}]')
  ```
- L570: `タイトル` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'更新しました: {item["タイトル"]}')
  ```
- L582: `タイトル` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'== {item["タイトル"]} ==')
  ```
- L583: `対応レベル` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  対応レベル        : {item["対応レベル"] if item["対応レベル"] else "-"}')
  ```
- L583: `対応レベル` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  対応レベル        : {item["対応レベル"] if item["対応レベル"] else "-"}')
  ```
- L584: `日付` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  日付              : {item["日付"] if item["日付"] else "-"}')
  ```
- L584: `日付` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  日付              : {item["日付"] if item["日付"] else "-"}')
  ```
- L585: `領域` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  領域              : {item["領域"] if item["領域"] else "-"}')
  ```
- L585: `領域` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  領域              : {item["領域"] if item["領域"] else "-"}')
  ```
- L586: `真因カテゴリ` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  真因カテゴリ      : {item["真因カテゴリ"] if item["真因カテゴリ"] else "-"}')
  ```
- L586: `真因カテゴリ` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  真因カテゴリ      : {item["真因カテゴリ"] if item["真因カテゴリ"] else "-"}')
  ```
- L587: `真因（要約）` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  真因（要約）      : {item["真因（要約）"] if item["真因（要約）"] else "-"}')
  ```
- L587: `真因（要約）` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  真因（要約）      : {item["真因（要約）"] if item["真因（要約）"] else "-"}')
  ```
- L588: `ステータス` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  ステータス        : {item["ステータス"] if item["ステータス"] else "-"}')
  ```
- L588: `ステータス` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  ステータス        : {item["ステータス"] if item["ステータス"] else "-"}')
  ```
- L589: `関連ファイル` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  関連ファイル      : {item["関連ファイル"] if item["関連ファイル"] else "-"}')
  ```
- L589: `関連ファイル` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  関連ファイル      : {item["関連ファイル"] if item["関連ファイル"] else "-"}')
  ```
- L590: `作成日時` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  作成日時          : {item["作成日時"]}')
  ```
- L592: `なぜ(1回目)` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  なぜ(1回目)       : {item["なぜ(1回目)"] if item["なぜ(1回目)"] else "-"}')
  ```
- L592: `なぜ(1回目)` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  なぜ(1回目)       : {item["なぜ(1回目)"] if item["なぜ(1回目)"] else "-"}')
  ```
- L593: `なぜ(2回目)` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  なぜ(2回目)       : {item["なぜ(2回目)"] if item["なぜ(2回目)"] else "-"}')
  ```
- L593: `なぜ(2回目)` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  なぜ(2回目)       : {item["なぜ(2回目)"] if item["なぜ(2回目)"] else "-"}')
  ```
- L594: `なぜ(3回目)` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  なぜ(3回目)       : {item["なぜ(3回目)"] if item["なぜ(3回目)"] else "-"}')
  ```
- L594: `なぜ(3回目)` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  なぜ(3回目)       : {item["なぜ(3回目)"] if item["なぜ(3回目)"] else "-"}')
  ```
- L595: `真の原因に対する対策` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  真の原因に対する対策: {item["真の原因に対する対策"] if item["真の原因に対する対策"] else "-"}')
  ```
- L595: `真の原因に対する対策` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  真の原因に対する対策: {item["真の原因に対する対策"] if item["真の原因に対する対策"] else "-"}')
  ```
- L680: `タイトル` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'ブロックを追記しました: {item["タイトル"]}')
  ```
- L741: `ステータス` — 辞書アクセス（日本語/大文字開始キー）
  ```
  status = item['ステータス']
  ```
- L746: `対策実施日` — 辞書アクセス（日本語/大文字開始キー）
  ```
  date_str = item['対策実施日'] or item['日付']
  ```
- L746: `日付` — 辞書アクセス（日本語/大文字開始キー）
  ```
  date_str = item['対策実施日'] or item['日付']
  ```
- L755: `タイトル` — 辞書アクセス（日本語/大文字開始キー）
  ```
  verification_due.append((elapsed_days, item['タイトル'], item['対応レベル'], date_str))
  ```
- L755: `対応レベル` — 辞書アクセス（日本語/大文字開始キー）
  ```
  verification_due.append((elapsed_days, item['タイトル'], item['対応レベル'], date_str))
  ```
- L759: `タイトル` — 辞書アクセス（日本語/大文字開始キー）
  ```
  in_verification.append((item['タイトル'], item['対応レベル'], item['日付']))
  ```
- L759: `対応レベル` — 辞書アクセス（日本語/大文字開始キー）
  ```
  in_verification.append((item['タイトル'], item['対応レベル'], item['日付']))
  ```
- L759: `日付` — 辞書アクセス（日本語/大文字開始キー）
  ```
  in_verification.append((item['タイトル'], item['対応レベル'], item['日付']))
  ```

### scripts\notion-ledger.py
- L145: `管理番号` — filter/sort指定 (property: ...)
  ```
  "property": "管理番号",
  ```
- L158: `会社名 / 屋号` — filter/sort指定 (property: ...)
  ```
  "filter": {"property": "会社名 / 屋号", "rich_text": {"contains": keyword}}
  ```
- L165: `properties` — レスポンス処理 ([] アクセス)
  ```
  props = p["properties"]
  ```
- L169: `id` — レスポンス処理 ([] アクセス)
  ```
  return p["id"], name, code
  ```
- L235: `発行日` — filter/sort指定 (property: ...)
  ```
  "sorts": [{"property": "発行日", "direction": "descending"}]
  ```
- L246: `管理番号` — 辞書アクセス（日本語/大文字開始キー）
  ```
  f"{row['管理番号']:14} "
  ```
- L247: `種別` — 辞書アクセス（日本語/大文字開始キー）
  ```
  f"{row['種別']:6} "
  ```
- L248: `発行日` — 辞書アクセス（日本語/大文字開始キー）
  ```
  f"{row['発行日']:12} "
  ```
- L249: `金額（税込）` — 辞書アクセス（日本語/大文字開始キー）
  ```
  f"{row['金額（税込）']:14} "
  ```
- L250: `ステータス` — 辞書アクセス（日本語/大文字開始キー）
  ```
  f"{row['ステータス']:8}"
  ```
- L283: `金額` — 辞書アクセス（日本語/大文字開始キー）
  ```
  data["金額"] = prompt_amount("金額（税込）（数値のみ、例: 110000）")
  ```
- L285: `発行日` — 辞書アクセス（日本語/大文字開始キー）
  ```
  if not data["発行日"]:
  ```
- L291: `発行日` — 辞書アクセス（日本語/大文字開始キー）
  ```
  token, ledger_db_id, customer_code, data["発行日"]
  ```
- L303: `種別` — 辞書アクセス（日本語/大文字開始キー）
  ```
  if data["種別"]:
  ```
- L304: `種別` — レスポンス処理 ([] アクセス)
  ```
  props["種別"] = {"select": {"name": data["種別"]}}
  ```
- L304: `種別` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["種別"] = {"select": {"name": data["種別"]}}
  ```
- L304: `種別` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["種別"] = {"select": {"name": data["種別"]}}
  ```
- L305: `発行日` — 辞書アクセス（日本語/大文字開始キー）
  ```
  if data["発行日"]:
  ```
- L306: `発行日` — レスポンス処理 ([] アクセス)
  ```
  props["発行日"] = {"date": {"start": data["発行日"]}}
  ```
- L306: `発行日` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["発行日"] = {"date": {"start": data["発行日"]}}
  ```
- L306: `発行日` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["発行日"] = {"date": {"start": data["発行日"]}}
  ```
- L307: `件名` — 辞書アクセス（日本語/大文字開始キー）
  ```
  if data["件名"]:
  ```
- L308: `件名` — レスポンス処理 ([] アクセス)
  ```
  props["件名"] = {"rich_text": [{"text": {"content": data["件名"]}}]}
  ```
- L308: `件名` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["件名"] = {"rich_text": [{"text": {"content": data["件名"]}}]}
  ```
- L308: `件名` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["件名"] = {"rich_text": [{"text": {"content": data["件名"]}}]}
  ```
- L309: `ステータス` — 辞書アクセス（日本語/大文字開始キー）
  ```
  if data["ステータス"]:
  ```
- L310: `ステータス` — レスポンス処理 ([] アクセス)
  ```
  props["ステータス"] = {"select": {"name": data["ステータス"]}}
  ```
- L310: `ステータス` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["ステータス"] = {"select": {"name": data["ステータス"]}}
  ```
- L310: `ステータス` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["ステータス"] = {"select": {"name": data["ステータス"]}}
  ```
- L311: `納品日` — 辞書アクセス（日本語/大文字開始キー）
  ```
  if data["納品日"]:
  ```
- L312: `納品日` — レスポンス処理 ([] アクセス)
  ```
  props["納品日"] = {"date": {"start": data["納品日"]}}
  ```
- L312: `納品日` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["納品日"] = {"date": {"start": data["納品日"]}}
  ```
- L312: `納品日` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["納品日"] = {"date": {"start": data["納品日"]}}
  ```
- L313: `入金状況` — 辞書アクセス（日本語/大文字開始キー）
  ```
  if data["入金状況"]:
  ```
- L314: `入金状況` — レスポンス処理 ([] アクセス)
  ```
  props["入金状況"] = {"select": {"name": data["入金状況"]}}
  ```
- L314: `入金状況` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["入金状況"] = {"select": {"name": data["入金状況"]}}
  ```
- L314: `入金状況` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["入金状況"] = {"select": {"name": data["入金状況"]}}
  ```
- L315: `入金日` — 辞書アクセス（日本語/大文字開始キー）
  ```
  if data["入金日"]:
  ```
- L316: `入金日` — レスポンス処理 ([] アクセス)
  ```
  props["入金日"] = {"date": {"start": data["入金日"]}}
  ```
- L316: `入金日` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["入金日"] = {"date": {"start": data["入金日"]}}
  ```
- L316: `入金日` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["入金日"] = {"date": {"start": data["入金日"]}}
  ```
- L317: `金額` — 辞書アクセス（日本語/大文字開始キー）
  ```
  if data["金額"] is not None:
  ```
- L318: `金額（税込）` — レスポンス処理 ([] アクセス)
  ```
  props["金額（税込）"] = {"number": data["金額"]}
  ```
- L318: `金額（税込）` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["金額（税込）"] = {"number": data["金額"]}
  ```
- L318: `金額` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["金額（税込）"] = {"number": data["金額"]}
  ```
- L319: `メモ` — 辞書アクセス（日本語/大文字開始キー）
  ```
  if data["メモ"]:
  ```
- L320: `メモ` — レスポンス処理 ([] アクセス)
  ```
  props["メモ"] = {"rich_text": [{"text": {"content": data["メモ"]}}]}
  ```
- L320: `メモ` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["メモ"] = {"rich_text": [{"text": {"content": data["メモ"]}}]}
  ```
- L320: `メモ` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["メモ"] = {"rich_text": [{"text": {"content": data["メモ"]}}]}
  ```
- L326: `件名` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f"\n追加しました: {management_no}  {customer_name}  {data['件名']}")
  ```
- L333: `管理番号` — filter/sort指定 (property: ...)
  ```
  {"property": "管理番号", "title": {"contains": keyword}},
  ```
- L334: `件名` — filter/sort指定 (property: ...)
  ```
  {"property": "件名", "rich_text": {"contains": keyword}},
  ```
- L335: `メモ` — filter/sort指定 (property: ...)
  ```
  {"property": "メモ", "rich_text": {"contains": keyword}},
  ```
- L349: `管理番号` — 辞書アクセス（日本語/大文字開始キー）
  ```
  f"{row['管理番号']:14} "
  ```
- L350: `種別` — 辞書アクセス（日本語/大文字開始キー）
  ```
  f"{row['種別']:6} "
  ```
- L351: `発行日` — 辞書アクセス（日本語/大文字開始キー）
  ```
  f"{row['発行日']:12} "
  ```
- L352: `金額（税込）` — 辞書アクセス（日本語/大文字開始キー）
  ```
  f"{row['金額（税込）']:14} "
  ```
- L353: `ステータス` — 辞書アクセス（日本語/大文字開始キー）
  ```
  f"{row['ステータス']:8}"
  ```
- L367: `管理番号` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f"  {'管理番号':12}: {row['管理番号']}")
  ```
- L380: `管理番号` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f"\n--- 更新: {current['管理番号']} ---")
  ```
- L381: `管理番号` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f"  管理番号: {current['管理番号']}（変更不可）")
  ```
- L384: `ステータス` — 辞書アクセス（日本語/大文字開始キー）
  ```
  new_status = prompt_choice("ステータス", STATUS_OPTIONS, current["ステータス"])
  ```
- L385: `種別` — 辞書アクセス（日本語/大文字開始キー）
  ```
  new_type = prompt_choice("種別", TYPE_OPTIONS, current["種別"])
  ```
- L386: `件名` — 辞書アクセス（日本語/大文字開始キー）
  ```
  new_subject = prompt("件名", current["件名"])
  ```
- L387: `金額（税込）` — 辞書アクセス（日本語/大文字開始キー）
  ```
  new_amount_str = prompt("金額（税込）（数値のみ）", current["金額（税込）"].replace("¥", "").replace(",", "") if current["金額（税込）"] else...
  ```
- L387: `金額（税込）` — 辞書アクセス（日本語/大文字開始キー）
  ```
  new_amount_str = prompt("金額（税込）（数値のみ）", current["金額（税込）"].replace("¥", "").replace(",", "") if current["金額（税込）"] else...
  ```
- L388: `納品日` — 辞書アクセス（日本語/大文字開始キー）
  ```
  new_delivery = prompt_date("納品日（YYYY-MM-DD）", current["納品日"])
  ```
- L389: `入金状況` — 辞書アクセス（日本語/大文字開始キー）
  ```
  new_payment_status = prompt_choice("入金状況", PAYMENT_OPTIONS, current["入金状況"])
  ```
- L390: `入金日` — 辞書アクセス（日本語/大文字開始キー）
  ```
  new_payment_date = prompt_date("入金日（YYYY-MM-DD）", current["入金日"])
  ```
- L391: `メモ` — 辞書アクセス（日本語/大文字開始キー）
  ```
  new_memo = prompt("メモ", current["メモ"])
  ```
- L395: `ステータス` — レスポンス処理 ([] アクセス)
  ```
  props["ステータス"] = {"select": {"name": new_status}}
  ```
- L395: `ステータス` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["ステータス"] = {"select": {"name": new_status}}
  ```
- L397: `種別` — レスポンス処理 ([] アクセス)
  ```
  props["種別"] = {"select": {"name": new_type}}
  ```
- L397: `種別` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["種別"] = {"select": {"name": new_type}}
  ```
- L399: `件名` — レスポンス処理 ([] アクセス)
  ```
  props["件名"] = {"rich_text": [{"text": {"content": new_subject}}]}
  ```
- L399: `件名` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["件名"] = {"rich_text": [{"text": {"content": new_subject}}]}
  ```
- L402: `金額（税込）` — レスポンス処理 ([] アクセス)
  ```
  props["金額（税込）"] = {"number": int(new_amount_str.replace(",", ""))}
  ```
- L402: `金額（税込）` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["金額（税込）"] = {"number": int(new_amount_str.replace(",", ""))}
  ```
- L406: `納品日` — レスポンス処理 ([] アクセス)
  ```
  props["納品日"] = {"date": {"start": new_delivery}}
  ```
- L406: `納品日` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["納品日"] = {"date": {"start": new_delivery}}
  ```
- L408: `入金状況` — レスポンス処理 ([] アクセス)
  ```
  props["入金状況"] = {"select": {"name": new_payment_status}}
  ```
- L408: `入金状況` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["入金状況"] = {"select": {"name": new_payment_status}}
  ```
- L410: `入金日` — レスポンス処理 ([] アクセス)
  ```
  props["入金日"] = {"date": {"start": new_payment_date}}
  ```
- L410: `入金日` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["入金日"] = {"date": {"start": new_payment_date}}
  ```
- L412: `メモ` — レスポンス処理 ([] アクセス)
  ```
  props["メモ"] = {"rich_text": [{"text": {"content": new_memo}}]}
  ```
- L412: `メモ` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props["メモ"] = {"rich_text": [{"text": {"content": new_memo}}]}
  ```
- L419: `管理番号` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f"\n更新しました: {current['管理番号']}")
  ```
- L431: `管理番号` — filter/sort指定 (property: ...)
  ```
  {"property": "管理番号", "title": {"contains": keyword_or_id}},
  ```
- L432: `件名` — filter/sort指定 (property: ...)
  ```
  {"property": "件名", "rich_text": {"contains": keyword_or_id}},
  ```
- L433: `メモ` — filter/sort指定 (property: ...)
  ```
  {"property": "メモ", "rich_text": {"contains": keyword_or_id}},
  ```
- L436: `発行日` — filter/sort指定 (property: ...)
  ```
  "sorts": [{"property": "発行日", "direction": "descending"}]
  ```
- L448: `管理番号` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f"  {i}. {row['管理番号']:14} {row['発行日']:12} {row['ステータス']}")
  ```
- L448: `発行日` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f"  {i}. {row['管理番号']:14} {row['発行日']:12} {row['ステータス']}")
  ```
- L448: `ステータス` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f"  {i}. {row['管理番号']:14} {row['発行日']:12} {row['ステータス']}")
  ```

### scripts\notion-projects.py
- L166: `object` — filter/sort指定 (property: ...)
  ```
  'filter': {'property': 'object', 'value': 'database'},
  ```
- L248: `ステータス` — 辞書アクセス（日本語/大文字開始キー）
  ```
  s = item['ステータス']
  ```
- L258: `プロジェクト名` — filter/sort指定 (property: ...)
  ```
  'filter': {'property': 'プロジェクト名', 'title': {'contains': partial_name}}
  ```
- L281: `プロジェクト名` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  {i}. {item["プロジェクト名"]} [{item["ステータス"]}]')
  ```
- L281: `ステータス` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  {i}. {item["プロジェクト名"]} [{item["ステータス"]}]')
  ```
- L372: `ステータス` — filter/sort指定 (property: ...)
  ```
  'property': 'ステータス',
  ```
- L390: `プロジェクト名` — 辞書アクセス（日本語/大文字開始キー）
  ```
  f'{item["プロジェクト名"][:24]:24} '
  ```
- L391: `ステータス` — 辞書アクセス（日本語/大文字開始キー）
  ```
  f'{item["ステータス"]:8} '
  ```
- L392: `フェーズ` — 辞書アクセス（日本語/大文字開始キー）
  ```
  f'{item["フェーズ"][:20]:20} '
  ```
- L393: `担当` — 辞書アクセス（日本語/大文字開始キー）
  ```
  f'{item["担当"][:16]:16} '
  ```
- L394: `開始日` — 辞書アクセス（日本語/大文字開始キー）
  ```
  f'{item["開始日"]:12}'
  ```
- L413: `ステータス` — レスポンス処理 ([] アクセス)
  ```
  props['ステータス'] = {'select': {'name': status}}
  ```
- L413: `ステータス` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['ステータス'] = {'select': {'name': status}}
  ```
- L416: `フェーズ` — レスポンス処理 ([] アクセス)
  ```
  props['フェーズ'] = rich_text_prop(args.phase)
  ```
- L416: `フェーズ` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['フェーズ'] = rich_text_prop(args.phase)
  ```
- L418: `担当` — レスポンス処理 ([] アクセス)
  ```
  props['担当'] = rich_text_prop(args.assignee)
  ```
- L418: `担当` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['担当'] = rich_text_prop(args.assignee)
  ```
- L420: `メモ` — レスポンス処理 ([] アクセス)
  ```
  props['メモ'] = rich_text_prop(args.memo)
  ```
- L420: `メモ` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['メモ'] = rich_text_prop(args.memo)
  ```
- L422: `KPI` — レスポンス処理 ([] アクセス)
  ```
  props['KPI'] = rich_text_prop(args.kpi)
  ```
- L422: `KPI` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['KPI'] = rich_text_prop(args.kpi)
  ```
- L424: `開始日` — レスポンス処理 ([] アクセス)
  ```
  props['開始日'] = {'date': {'start': args.start_date}}
  ```
- L424: `開始日` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['開始日'] = {'date': {'start': args.start_date}}
  ```
- L426: `目標完了日` — レスポンス処理 ([] アクセス)
  ```
  props['目標完了日'] = {'date': {'start': args.goal_date}}
  ```
- L426: `目標完了日` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['目標完了日'] = {'date': {'start': args.goal_date}}
  ```
- L445: `ステータス` — レスポンス処理 ([] アクセス)
  ```
  props['ステータス'] = {'select': {'name': args.status}}
  ```
- L445: `ステータス` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['ステータス'] = {'select': {'name': args.status}}
  ```
- L447: `フェーズ` — レスポンス処理 ([] アクセス)
  ```
  props['フェーズ'] = rich_text_prop(args.phase)
  ```
- L447: `フェーズ` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['フェーズ'] = rich_text_prop(args.phase)
  ```
- L449: `担当` — レスポンス処理 ([] アクセス)
  ```
  props['担当'] = rich_text_prop(args.assignee)
  ```
- L449: `担当` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['担当'] = rich_text_prop(args.assignee)
  ```
- L451: `メモ` — レスポンス処理 ([] アクセス)
  ```
  props['メモ'] = rich_text_prop(args.memo)
  ```
- L451: `メモ` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['メモ'] = rich_text_prop(args.memo)
  ```
- L453: `KPI` — レスポンス処理 ([] アクセス)
  ```
  props['KPI'] = rich_text_prop(args.kpi)
  ```
- L453: `KPI` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['KPI'] = rich_text_prop(args.kpi)
  ```
- L455: `開始日` — レスポンス処理 ([] アクセス)
  ```
  props['開始日'] = {'date': {'start': args.start_date}}
  ```
- L455: `開始日` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['開始日'] = {'date': {'start': args.start_date}}
  ```
- L457: `目標完了日` — レスポンス処理 ([] アクセス)
  ```
  props['目標完了日'] = {'date': {'start': args.goal_date}}
  ```
- L457: `目標完了日` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['目標完了日'] = {'date': {'start': args.goal_date}}
  ```
- L464: `プロジェクト名` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'更新しました: {current["プロジェクト名"]}')
  ```
- L474: `プロジェクト名` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'\n=== {item["プロジェクト名"]} ===')
  ```
- L475: `ステータス` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'ステータス  : {item["ステータス"]}')
  ```
- L476: `フェーズ` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'フェーズ    : {item["フェーズ"]}')
  ```
- L477: `開始日` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'開始日      : {item["開始日"]}')
  ```
- L478: `目標完了日` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'目標完了日  : {item["目標完了日"]}')
  ```
- L479: `KPI` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'KPI         : {item["KPI"]}')
  ```
- L480: `担当` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'担当        : {item["担当"]}')
  ```
- L481: `メモ` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'メモ        : {item["メモ"]}')
  ```
- L528: `プロジェクト名` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'追記しました: {item["プロジェクト名"]}')
  ```

### scripts\notion-radar.py
- L402: `リンク` — filter/sort指定 (property: ...)
  ```
  'property': 'リンク',
  ```
- L530: `実施可否` — filter/sort指定 (property: ...)
  ```
  'property': '実施可否',
  ```
- L543: `投稿日時` — 辞書アクセス（日本語/大文字開始キー）
  ```
  items.sort(key=lambda x: x['投稿日時'] or '', reverse=True)
  ```
- L547: `投稿日時` — 辞書アクセス（日本語/大文字開始キー）
  ```
  date_str = item['投稿日時'] or '-'
  ```
- L548: `カテゴリ` — 辞書アクセス（日本語/大文字開始キー）
  ```
  category_str = f'[{item["カテゴリ"]}]' if item['カテゴリ'] else ''
  ```
- L548: `カテゴリ` — 辞書アクセス（日本語/大文字開始キー）
  ```
  category_str = f'[{item["カテゴリ"]}]' if item['カテゴリ'] else ''
  ```
- L549: `情報源` — 辞書アクセス（日本語/大文字開始キー）
  ```
  source_str = item['情報源'] or '-'
  ```
- L550: `リク検証` — 辞書アクセス（日本語/大文字開始キー）
  ```
  riku_str = item['リク検証'] or '-'
  ```
- L551: `カナタ判定` — 辞書アクセス（日本語/大文字開始キー）
  ```
  kanata_str = item['カナタ判定'] or '-'
  ```
- L552: `おすすめ度` — 辞書アクセス（日本語/大文字開始キー）
  ```
  recommend_str = item['おすすめ度'] or '-'
  ```
- L553: `要約` — 辞書アクセス（日本語/大文字開始キー）
  ```
  summary_short = item['要約'][:60] + '...' if len(item['要約']) > 60 else item['要約']
  ```
- L553: `要約` — 辞書アクセス（日本語/大文字開始キー）
  ```
  summary_short = item['要約'][:60] + '...' if len(item['要約']) > 60 else item['要約']
  ```
- L553: `要約` — 辞書アクセス（日本語/大文字開始キー）
  ```
  summary_short = item['要約'][:60] + '...' if len(item['要約']) > 60 else item['要約']
  ```
- L555: `タイトル` — 辞書アクセス（日本語/大文字開始キー）
  ```
  f'{date_str}  {category_str} {item["タイトル"]}\n'
  ```
- L560: `リンク` — 辞書アクセス（日本語/大文字開始キー）
  ```
  if item['リンク']:
  ```
- L561: `リンク` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  URL: {item["リンク"]}')
  ```
- L584: `通し番号` — filter/sort指定 (property: ...)
  ```
  'sorts': [{'property': '通し番号', 'direction': 'descending'}],
  ```
- L585: `通し番号` — filter/sort指定 (property: ...)
  ```
  'filter': {'property': '通し番号', 'number': {'is_not_empty': True}}
  ```
- L607: `通し番号` — filter/sort指定 (property: ...)
  ```
  'property': '通し番号',
  ```
- L674: `通し番号` — レスポンス処理 ([] アクセス)
  ```
  existing_type = existing_props['通し番号'].get('type', '')
  ```
- L674: `通し番号` — 辞書アクセス（日本語/大文字開始キー）
  ```
  existing_type = existing_props['通し番号'].get('type', '')
  ```
- L701: `通し番号` — filter/sort指定 (property: ...)
  ```
  'sorts': [{'property': '通し番号', 'direction': 'descending'}],
  ```
- L790: `通し番号` — レスポンス処理 ([] アクセス)
  ```
  props['通し番号'] = {'number': seq_num}
  ```
- L790: `通し番号` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['通し番号'] = {'number': seq_num}
  ```
- L793: `要約` — レスポンス処理 ([] アクセス)
  ```
  props['要約'] = rich_text_prop(args.summary)
  ```
- L793: `要約` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['要約'] = rich_text_prop(args.summary)
  ```
- L796: `リンク` — レスポンス処理 ([] アクセス)
  ```
  props['リンク'] = {'url': url_val}
  ```
- L796: `リンク` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['リンク'] = {'url': url_val}
  ```
- L803: `リク検証` — レスポンス処理 ([] アクセス)
  ```
  props['リク検証'] = {'select': {'name': args.riku_check}}
  ```
- L803: `リク検証` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['リク検証'] = {'select': {'name': args.riku_check}}
  ```
- L810: `カナタ判定` — レスポンス処理 ([] アクセス)
  ```
  props['カナタ判定'] = {'select': {'name': args.kanata_verdict}}
  ```
- L810: `カナタ判定` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['カナタ判定'] = {'select': {'name': args.kanata_verdict}}
  ```
- L813: `カナタ判定理由` — レスポンス処理 ([] アクセス)
  ```
  props['カナタ判定理由'] = rich_text_prop(args.kanata_reason)
  ```
- L813: `カナタ判定理由` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['カナタ判定理由'] = rich_text_prop(args.kanata_reason)
  ```
- L823: `おすすめ度` — レスポンス処理 ([] アクセス)
  ```
  props['おすすめ度'] = {'select': {'name': recommend_num_to_str(rec_num)}}
  ```
- L823: `おすすめ度` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['おすすめ度'] = {'select': {'name': recommend_num_to_str(rec_num)}}
  ```

### scripts\notion-sns.py
- L180: `object` — filter/sort指定 (property: ...)
  ```
  'filter': {'property': 'object', 'value': 'database'},
  ```
- L270: `ステータス` — 辞書アクセス（日本語/大文字開始キー）
  ```
  s = item['ステータス']
  ```
- L280: `投稿タイトル` — filter/sort指定 (property: ...)
  ```
  'filter': {'property': '投稿タイトル', 'title': {'contains': partial_name}}
  ```
- L303: `投稿タイトル` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  {i}. {item["投稿タイトル"]} [{item["ステータス"]}] {item["投稿予定日"]}')
  ```
- L303: `ステータス` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  {i}. {item["投稿タイトル"]} [{item["ステータス"]}] {item["投稿予定日"]}')
  ```
- L303: `投稿予定日` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  {i}. {item["投稿タイトル"]} [{item["ステータス"]}] {item["投稿予定日"]}')
  ```
- L427: `ステータス` — filter/sort指定 (property: ...)
  ```
  filters.append({'property': 'ステータス', 'select': {'equals': filter_status}})
  ```
- L427: `ステータス` — filters.append() 内 property
  ```
  filters.append({'property': 'ステータス', 'select': {'equals': filter_status}})
  ```
- L429: `プラットフォーム` — filter/sort指定 (property: ...)
  ```
  filters.append({'property': 'プラットフォーム', 'select': {'equals': filter_platform}})
  ```
- L429: `プラットフォーム` — filters.append() 内 property
  ```
  filters.append({'property': 'プラットフォーム', 'select': {'equals': filter_platform}})
  ```
- L431: `投稿予定日` — filter/sort指定 (property: ...)
  ```
  filters.append({'property': '投稿予定日', 'date': {'equals': filter_date}})
  ```
- L431: `投稿予定日` — filters.append() 内 property
  ```
  filters.append({'property': '投稿予定日', 'date': {'equals': filter_date}})
  ```
- L446: `投稿予定日` — 辞書アクセス（日本語/大文字開始キー）
  ```
  items.sort(key=lambda x: (x['投稿予定日'] or '', status_sort_key(x)))
  ```
- L453: `投稿タイトル` — 辞書アクセス（日本語/大文字開始キー）
  ```
  f'{item["投稿タイトル"][:20]:20} '
  ```
- L454: `投稿予定日` — 辞書アクセス（日本語/大文字開始キー）
  ```
  f'{item["投稿予定日"]:12} '
  ```
- L455: `プラットフォーム` — 辞書アクセス（日本語/大文字開始キー）
  ```
  f'{item["プラットフォーム"]:8} '
  ```
- L456: `カテゴリ` — 辞書アクセス（日本語/大文字開始キー）
  ```
  f'{item["カテゴリ"][:12]:12} '
  ```
- L457: `ステータス` — 辞書アクセス（日本語/大文字開始キー）
  ```
  f'{item["ステータス"]:10}'
  ```
- L477: `投稿予定日` — レスポンス処理 ([] アクセス)
  ```
  props['投稿予定日'] = {'date': {'start': args.date}}
  ```
- L477: `投稿予定日` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['投稿予定日'] = {'date': {'start': args.date}}
  ```
- L482: `プラットフォーム` — レスポンス処理 ([] アクセス)
  ```
  props['プラットフォーム'] = {'select': {'name': args.platform}}
  ```
- L482: `プラットフォーム` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['プラットフォーム'] = {'select': {'name': args.platform}}
  ```
- L484: `カテゴリ` — レスポンス処理 ([] アクセス)
  ```
  props['カテゴリ'] = {'select': {'name': args.category}}
  ```
- L484: `カテゴリ` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['カテゴリ'] = {'select': {'name': args.category}}
  ```
- L486: `型` — レスポンス処理 ([] アクセス)
  ```
  props['型'] = {'select': {'name': args.type}}
  ```
- L486: `型` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['型'] = {'select': {'name': args.type}}
  ```
- L488: `投稿内容案` — レスポンス処理 ([] アクセス)
  ```
  props['投稿内容案'] = rich_text_prop(args.draft)
  ```
- L488: `投稿内容案` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['投稿内容案'] = rich_text_prop(args.draft)
  ```
- L490: `メモ` — レスポンス処理 ([] アクセス)
  ```
  props['メモ'] = rich_text_prop(args.memo)
  ```
- L490: `メモ` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['メモ'] = rich_text_prop(args.memo)
  ```
- L512: `ステータス` — レスポンス処理 ([] アクセス)
  ```
  props['ステータス'] = {'select': {'name': args.status}}
  ```
- L512: `ステータス` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['ステータス'] = {'select': {'name': args.status}}
  ```
- L514: `いいね数` — レスポンス処理 ([] アクセス)
  ```
  props['いいね数'] = {'number': args.likes}
  ```
- L514: `いいね数` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['いいね数'] = {'number': args.likes}
  ```
- L516: `インプレッション数` — レスポンス処理 ([] アクセス)
  ```
  props['インプレッション数'] = {'number': args.impressions}
  ```
- L516: `インプレッション数` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['インプレッション数'] = {'number': args.impressions}
  ```
- L518: `RT数` — レスポンス処理 ([] アクセス)
  ```
  props['RT数'] = {'number': args.rts}
  ```
- L518: `RT数` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['RT数'] = {'number': args.rts}
  ```
- L520: `ER` — レスポンス処理 ([] アクセス)
  ```
  props['ER'] = {'number': args.er}
  ```
- L520: `ER` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['ER'] = {'number': args.er}
  ```
- L522: `投稿内容` — レスポンス処理 ([] アクセス)
  ```
  props['投稿内容'] = rich_text_prop(args.content)
  ```
- L522: `投稿内容` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['投稿内容'] = rich_text_prop(args.content)
  ```
- L524: `メモ` — レスポンス処理 ([] アクセス)
  ```
  props['メモ'] = rich_text_prop(args.memo)
  ```
- L524: `メモ` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['メモ'] = rich_text_prop(args.memo)
  ```
- L526: `投稿予定日` — レスポンス処理 ([] アクセス)
  ```
  props['投稿予定日'] = {'date': {'start': args.date}}
  ```
- L526: `投稿予定日` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['投稿予定日'] = {'date': {'start': args.date}}
  ```
- L533: `投稿タイトル` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'更新しました: {current["投稿タイトル"]}')
  ```
- L547: `投稿タイトル` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'\n=== {item["投稿タイトル"]} ===')
  ```
- L548: `投稿予定日` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'投稿予定日      : {item["投稿予定日"] or "-"}')
  ```
- L549: `プラットフォーム` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'プラットフォーム: {item["プラットフォーム"] or "-"}')
  ```
- L550: `カテゴリ` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'カテゴリ        : {item["カテゴリ"] or "-"}')
  ```
- L551: `型` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'型              : {item["型"] or "-"}')
  ```
- L552: `ステータス` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'ステータス      : {item["ステータス"] or "-"}')
  ```
- L553: `いいね数` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'いいね数        : {fmt_num(item["いいね数"])}')
  ```
- L554: `インプレッション数` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'インプレッション: {fmt_num(item["インプレッション数"])}')
  ```
- L555: `RT数` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'RT数            : {fmt_num(item["RT数"])}')
  ```
- L556: `ER` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'ER              : {fmt_num(item["ER"])}%' if item["ER"] is not None else f'ER              : -')
  ```
- L556: `ER` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'ER              : {fmt_num(item["ER"])}%' if item["ER"] is not None else f'ER              : -')
  ```
- L558: `投稿内容案` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(item['投稿内容案'] or '（未記入）')
  ```
- L560: `投稿内容` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(item['投稿内容'] or '（未記入）')
  ```
- L562: `メモ` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(item['メモ'] or '（なし）')
  ```
- L587: `投稿予定日` — filter/sort指定 (property: ...)
  ```
  {'property': '投稿予定日', 'date': {'on_or_after': start_date.isoformat()}},
  ```
- L588: `投稿予定日` — filter/sort指定 (property: ...)
  ```
  {'property': '投稿予定日', 'date': {'on_or_before': end_date.isoformat()}},
  ```
- L604: `プラットフォーム` — 辞書アクセス（日本語/大文字開始キー）
  ```
  pf = item['プラットフォーム'] or '不明'
  ```
- L610: `ステータス` — 辞書アクセス（日本語/大文字開始キー）
  ```
  posted = [i for i in items if i['ステータス'] == '投稿済み']
  ```
- L611: `インプレッション数` — 辞書アクセス（日本語/大文字開始キー）
  ```
  imp_list = [i['インプレッション数'] for i in posted if i['インプレッション数'] is not None]
  ```
- L611: `インプレッション数` — 辞書アクセス（日本語/大文字開始キー）
  ```
  imp_list = [i['インプレッション数'] for i in posted if i['インプレッション数'] is not None]
  ```
- L612: `ER` — 辞書アクセス（日本語/大文字開始キー）
  ```
  er_list = [i['ER'] for i in posted if i['ER'] is not None]
  ```
- L612: `ER` — 辞書アクセス（日本語/大文字開始キー）
  ```
  er_list = [i['ER'] for i in posted if i['ER'] is not None]
  ```
- L618: `いいね数` — 辞書アクセス（日本語/大文字開始キー）
  ```
  liked = [i for i in posted if i['いいね数'] is not None]
  ```
- L619: `いいね数` — 辞書アクセス（日本語/大文字開始キー）
  ```
  best = max(liked, key=lambda x: x['いいね数']) if liked else None
  ```
- L620: `いいね数` — 辞書アクセス（日本語/大文字開始キー）
  ```
  worst = min(liked, key=lambda x: x['いいね数']) if liked else None
  ```
- L636: `いいね数` — 辞書アクセス（日本語/大文字開始キー）
  ```
  likes_str = f'いいね{best["いいね数"]}'
  ```
- L637: `インプレッション数` — 辞書アクセス（日本語/大文字開始キー）
  ```
  imp_str = f', インプ{best["インプレッション数"]}' if best['インプレッション数'] is not None else ''
  ```
- L637: `インプレッション数` — 辞書アクセス（日本語/大文字開始キー）
  ```
  imp_str = f', インプ{best["インプレッション数"]}' if best['インプレッション数'] is not None else ''
  ```
- L638: `投稿タイトル` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'ベスト投稿: {best["投稿タイトル"]}（{likes_str}{imp_str}）')
  ```
- L641: `いいね数` — 辞書アクセス（日本語/大文字開始キー）
  ```
  likes_str = f'いいね{worst["いいね数"]}'
  ```
- L642: `インプレッション数` — 辞書アクセス（日本語/大文字開始キー）
  ```
  imp_str = f', インプ{worst["インプレッション数"]}' if worst['インプレッション数'] is not None else ''
  ```
- L642: `インプレッション数` — 辞書アクセス（日本語/大文字開始キー）
  ```
  imp_str = f', インプ{worst["インプレッション数"]}' if worst['インプレッション数'] is not None else ''
  ```
- L643: `投稿タイトル` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'ワースト投稿: {worst["投稿タイトル"]}（{likes_str}{imp_str}）')
  ```
- L648: `ステータス` — 辞書アクセス（日本語/大文字開始キー）
  ```
  s = item['ステータス'] or '不明'
  ```

### scripts\notion-tasks.py
- L222: `object` — filter/sort指定 (property: ...)
  ```
  'filter': {'property': 'object', 'value': 'database'},
  ```
- L318: `ステータス` — 辞書アクセス（日本語/大文字開始キー）
  ```
  s = item['ステータス']
  ```
- L337: `タイトル` — filter/sort指定 (property: ...)
  ```
  'filter': {'property': 'タイトル', 'title': {'contains': partial_title}}
  ```
- L357: `タイトル` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  - {t["タイトル"]} [{t["ステータス"]}]')
  ```
- L357: `ステータス` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  - {t["タイトル"]} [{t["ステータス"]}]')
  ```
- L520: `ステータス` — filter/sort指定 (property: ...)
  ```
  filters.append({'property': 'ステータス', 'select': {'equals': filter_status}})
  ```
- L520: `ステータス` — filters.append() 内 property
  ```
  filters.append({'property': 'ステータス', 'select': {'equals': filter_status}})
  ```
- L522: `種別` — filter/sort指定 (property: ...)
  ```
  filters.append({'property': '種別', 'select': {'equals': filter_type}})
  ```
- L522: `種別` — filters.append() 内 property
  ```
  filters.append({'property': '種別', 'select': {'equals': filter_type}})
  ```
- L524: `クライアント` — filter/sort指定 (property: ...)
  ```
  filters.append({'property': 'クライアント', 'select': {'equals': filter_client}})
  ```
- L524: `クライアント` — filters.append() 内 property
  ```
  filters.append({'property': 'クライアント', 'select': {'equals': filter_client}})
  ```
- L526: `優先度` — filter/sort指定 (property: ...)
  ```
  filters.append({'property': '優先度', 'select': {'equals': filter_priority}})
  ```
- L526: `優先度` — filters.append() 内 property
  ```
  filters.append({'property': '優先度', 'select': {'equals': filter_priority}})
  ```
- L528: `対象環境` — filter/sort指定 (property: ...)
  ```
  filters.append({'property': '対象環境', 'multi_select': {'contains': filter_env}})
  ```
- L528: `対象環境` — filters.append() 内 property
  ```
  filters.append({'property': '対象環境', 'multi_select': {'contains': filter_env}})
  ```
- L546: `優先度` — 辞書アクセス（日本語/大文字開始キー）
  ```
  priority_str = item['優先度'] if item['優先度'] else '-'
  ```
- L546: `優先度` — 辞書アクセス（日本語/大文字開始キー）
  ```
  priority_str = item['優先度'] if item['優先度'] else '-'
  ```
- L547: `対象環境` — 辞書アクセス（日本語/大文字開始キー）
  ```
  env_str = ','.join(item['対象環境']) if item['対象環境'] else '-'
  ```
- L547: `対象環境` — 辞書アクセス（日本語/大文字開始キー）
  ```
  env_str = ','.join(item['対象環境']) if item['対象環境'] else '-'
  ```
- L548: `クライアント` — 辞書アクセス（日本語/大文字開始キー）
  ```
  client_str = f'  [{item["クライアント"]}]' if item['クライアント'] else ''
  ```
- L548: `クライアント` — 辞書アクセス（日本語/大文字開始キー）
  ```
  client_str = f'  [{item["クライアント"]}]' if item['クライアント'] else ''
  ```
- L549: `担当` — 辞書アクセス（日本語/大文字開始キー）
  ```
  assignee_str = f'  担当:{item["担当"]}' if item['担当'] else ''
  ```
- L549: `担当` — 辞書アクセス（日本語/大文字開始キー）
  ```
  assignee_str = f'  担当:{item["担当"]}' if item['担当'] else ''
  ```
- L550: `種別` — 辞書アクセス（日本語/大文字開始キー）
  ```
  type_str = f'({item["種別"]})' if item['種別'] else ''
  ```
- L550: `種別` — 辞書アクセス（日本語/大文字開始キー）
  ```
  type_str = f'({item["種別"]})' if item['種別'] else ''
  ```
- L551: `メモ` — 辞書アクセス（日本語/大文字開始キー）
  ```
  memo_str = f'\n      メモ: {item["メモ"]}' if item['メモ'] else ''
  ```
- L551: `メモ` — 辞書アクセス（日本語/大文字開始キー）
  ```
  memo_str = f'\n      メモ: {item["メモ"]}' if item['メモ'] else ''
  ```
- L552: `ブロッカー` — 辞書アクセス（日本語/大文字開始キー）
  ```
  blocker_str = f'\n      ブロッカー: {item["ブロッカー"]}' if item['ブロッカー'] else ''
  ```
- L552: `ブロッカー` — 辞書アクセス（日本語/大文字開始キー）
  ```
  blocker_str = f'\n      ブロッカー: {item["ブロッカー"]}' if item['ブロッカー'] else ''
  ```
- L554: `ステータス` — 辞書アクセス（日本語/大文字開始キー）
  ```
  f'[{item["ステータス"]}] {item["タイトル"]}  '
  ```
- L554: `タイトル` — 辞書アクセス（日本語/大文字開始キー）
  ```
  f'[{item["ステータス"]}] {item["タイトル"]}  '
  ```
- L581: `対象環境` — レスポンス処理 ([] アクセス)
  ```
  props['対象環境'] = {
  ```
- L581: `対象環境` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['対象環境'] = {
  ```
- L586: `クライアント` — レスポンス処理 ([] アクセス)
  ```
  props['クライアント'] = {'select': {'name': client}}
  ```
- L586: `クライアント` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['クライアント'] = {'select': {'name': client}}
  ```
- L589: `担当` — レスポンス処理 ([] アクセス)
  ```
  props['担当'] = {'select': {'name': assignee}}
  ```
- L589: `担当` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['担当'] = {'select': {'name': assignee}}
  ```
- L592: `メモ` — レスポンス処理 ([] アクセス)
  ```
  props['メモ'] = rich_text_prop(memo)
  ```
- L592: `メモ` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['メモ'] = rich_text_prop(memo)
  ```
- L614: `ステータス` — レスポンス処理 ([] アクセス)
  ```
  props['ステータス'] = {'select': {'name': new_status}}
  ```
- L614: `ステータス` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['ステータス'] = {'select': {'name': new_status}}
  ```
- L615: `ステータス` — 辞書アクセス（日本語/大文字開始キー）
  ```
  changes.append(f'ステータス: [{item["ステータス"]}] → [{new_status}]')
  ```
- L618: `優先度` — レスポンス処理 ([] アクセス)
  ```
  props['優先度'] = {'select': {'name': new_priority}}
  ```
- L618: `優先度` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['優先度'] = {'select': {'name': new_priority}}
  ```
- L619: `優先度` — 辞書アクセス（日本語/大文字開始キー）
  ```
  changes.append(f'優先度: [{item["優先度"]}] → [{new_priority}]')
  ```
- L622: `担当` — レスポンス処理 ([] アクセス)
  ```
  props['担当'] = {'select': {'name': new_assignee}}
  ```
- L622: `担当` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['担当'] = {'select': {'name': new_assignee}}
  ```
- L623: `担当` — 辞書アクセス（日本語/大文字開始キー）
  ```
  changes.append(f'担当: [{item["担当"]}] → [{new_assignee}]')
  ```
- L626: `ブロッカー` — レスポンス処理 ([] アクセス)
  ```
  props['ブロッカー'] = rich_text_prop(new_blocker)
  ```
- L626: `ブロッカー` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['ブロッカー'] = rich_text_prop(new_blocker)
  ```
- L634: `タイトル` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'更新しました: {item["タイトル"]}')
  ```
- L646: `タイトル` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'== {item["タイトル"]} ==')
  ```
- L647: `種別` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  種別      : {item["種別"]}')
  ```
- L648: `ステータス` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  ステータス: {item["ステータス"]}')
  ```
- L649: `優先度` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  優先度    : {item["優先度"]}')
  ```
- L650: `カテゴリ` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  カテゴリ  : {item["カテゴリ"]}')
  ```
- L651: `対象環境` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  対象環境  : {", ".join(item["対象環境"]) if item["対象環境"] else "-"}')
  ```
- L651: `対象環境` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  対象環境  : {", ".join(item["対象環境"]) if item["対象環境"] else "-"}')
  ```
- L652: `クライアント` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  クライアント: {item["クライアント"] if item["クライアント"] else "-"}')
  ```
- L652: `クライアント` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  クライアント: {item["クライアント"] if item["クライアント"] else "-"}')
  ```
- L653: `担当` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  担当      : {item["担当"]}')
  ```
- L654: `ブロッカー` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  ブロッカー: {item["ブロッカー"] if item["ブロッカー"] else "-"}')
  ```
- L654: `ブロッカー` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  ブロッカー: {item["ブロッカー"] if item["ブロッカー"] else "-"}')
  ```
- L655: `開始日` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  開始日    : {item["開始日"] if item["開始日"] else "-"}')
  ```
- L655: `開始日` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  開始日    : {item["開始日"] if item["開始日"] else "-"}')
  ```
- L656: `最終編集日時` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  最終編集  : {item["最終編集日時"]}')
  ```
- L657: `作成日時` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'  作成日時  : {item["作成日時"]}')
  ```
- L658: `メモ` — 辞書アクセス（日本語/大文字開始キー）
  ```
  if item['メモ']:
  ```
- L660: `メモ` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(item['メモ'])
  ```
- L737: `タイトル` — 辞書アクセス（日本語/大文字開始キー）
  ```
  print(f'ブロックを追記しました: {item["タイトル"]}')
  ```
- L754: `ステータス` — 辞書アクセス（日本語/大文字開始キー）
  ```
  if item['ステータス'] in STATUS_ALERT_EXCLUDE:
  ```
- L757: `優先度` — 辞書アクセス（日本語/大文字開始キー）
  ```
  priority = item['優先度']
  ```
- L762: `最終編集日時` — 辞書アクセス（日本語/大文字開始キー）
  ```
  last_edited_str = item['最終編集日時']
  ```
- L778: `タイトル` — 辞書アクセス（日本語/大文字開始キー）
  ```
  alerts.append((priority, elapsed_days, item['タイトル'], item['ステータス']))
  ```
- L778: `ステータス` — 辞書アクセス（日本語/大文字開始キー）
  ```
  alerts.append((priority, elapsed_days, item['タイトル'], item['ステータス']))
  ```
- L849: `メモ` — レスポンス処理 ([] アクセス)
  ```
  props['メモ'] = rich_text_prop(old_memo)
  ```
- L849: `メモ` — 辞書アクセス（日本語/大文字開始キー）
  ```
  props['メモ'] = rich_text_prop(old_memo)
  ```

### scripts\update-ledger-schema.py
- L19: `NOTION_API_TOKEN` — 辞書アクセス（日本語/大文字開始キー）
  ```
  TOKEN     = env['NOTION_API_TOKEN']
  ```
- L20: `NOTION_LEDGER_DB_ID` — 辞書アクセス（日本語/大文字開始キー）
  ```
  LEDGER_DB = env['NOTION_LEDGER_DB_ID']
  ```
- L97: `メモ` — 辞書アクセス（日本語/大文字開始キー）
  ```
  for field in ['メモ']:
  ```
- L135: `管理番号` — レスポンス処理 (.get())
  ```
  bangou_prop = props.get('管理番号') or props.get('番号') or {}
  ```
- L135: `番号` — レスポンス処理 (.get())
  ```
  bangou_prop = props.get('管理番号') or props.get('番号') or {}
  ```
- L153: `管理番号` — レスポンス処理 ([] アクセス)
  ```
  update_props['管理番号'] = {'title': [{'text': {'content': new_bangou}}]}
  ```
- L153: `管理番号` — 辞書アクセス（日本語/大文字開始キー）
  ```
  update_props['管理番号'] = {'title': [{'text': {'content': new_bangou}}]}
  ```
- L155: `ステータス` — レスポンス処理 ([] アクセス)
  ```
  update_props['ステータス'] = {'select': {'name': status}}
  ```
- L155: `ステータス` — 辞書アクセス（日本語/大文字開始キー）
  ```
  update_props['ステータス'] = {'select': {'name': status}}
  ```

### skills\morning-briefing-weekly\SKILL.md
- L172: `日付` — filter/sort指定 (property: ...)
  ```
  'property': '日付',
  ```

### skills\morning-briefing\SKILL.md
- L173: `日付` — filter/sort指定 (property: ...)
  ```
  'property': '日付',
  ```

### skills\pptx-from-layouts\scripts\edit.py
- L48: `PYTHONPATH` — 辞書アクセス（日本語/大文字開始キー）
  ```
  env['PYTHONPATH'] = f"{claude_paths}:{pythonpath}" if pythonpath else claude_paths
  ```

### skills\pptx-from-layouts\scripts\generate.py
- L55: `PYTHONPATH` — 辞書アクセス（日本語/大文字開始キー）
  ```
  env['PYTHONPATH'] = f"{skill_paths}:{pythonpath}" if pythonpath else skill_paths
  ```

### skills\pptx-from-layouts\scripts\generate_pptx.py
- L2979: `idx` — レスポンス処理 ([] アクセス)
  ```
  remaining_phs = [p for p in body_phs if p['idx'] not in used_idxs]
  ```
- L3057: `idx` — レスポンス処理 ([] アクセス)
  ```
  body_phs = [p for p in body_phs if p['idx'] != largest['idx']]
  ```
- L3929: `idx` — レスポンス処理 ([] アクセス)
  ```
  body_phs = [p for p in body_phs if p['idx'] != largest['idx']]
  ```

### skills\pptx-from-layouts\scripts\profile.py
- L46: `PYTHONPATH` — 辞書アクセス（日本語/大文字開始キー）
  ```
  env['PYTHONPATH'] = f"{claude_paths}:{pythonpath}" if pythonpath else claude_paths
  ```

### skills\pptx-from-layouts\scripts\validate.py
- L50: `PYTHONPATH` — 辞書アクセス（日本語/大文字開始キー）
  ```
  env['PYTHONPATH'] = f"{claude_paths}:{pythonpath}" if pythonpath else claude_paths
  ```
