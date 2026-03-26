#!/usr/bin/env python3
"""
Notion 見積・請求台帳スクリプト
使い方:
  python3 notion-ledger.py list                        # 一覧表示
  python3 notion-ledger.py add                         # 対話形式で追加（管理番号は自動採番）
  python3 notion-ledger.py search <キーワード>          # 検索（件名・顧客名・メモ）
  python3 notion-ledger.py show <ページID>             # 詳細表示
  python3 notion-ledger.py update <キーワード>         # 対話形式で更新

【管理番号ルール】
  - 形式: YYMMDD + 識別記号 + - + 版数（例: 220824A-1）
  - 識別記号は CRM（顧客リスト）の「識別記号」フィールドから取得
  - 版数は同日・同顧客の既存レコード数 + 1 で自動決定
  - 一度発行した管理番号は変更不可
"""

import json
import os
import re
import ssl
import sys
import urllib.request
import urllib.error
from datetime import datetime

# ---- 設定 ----
ENV_PATH = os.path.expanduser("~/.claude/.env")
UUID_RE = re.compile(r"^[0-9a-f]{8}-?[0-9a-f]{4}-?[0-9a-f]{4}-?[0-9a-f]{4}-?[0-9a-f]{12}$", re.I)
SSL_CTX = ssl.create_default_context()

TYPE_OPTIONS = ["見積書", "請求書", "領収書"]
STATUS_OPTIONS = ["見積り", "着手中", "納品済み", "請求済み", "入金済み", "キャンセル"]
PAYMENT_OPTIONS = ["未入金", "入金済み", "一部入金"]


def load_env():
    env = {}
    if not os.path.exists(ENV_PATH):
        return env
    with open(ENV_PATH, encoding="utf-8") as f:
        for line in f:
            line = line.strip()
            if line and not line.startswith("#") and "=" in line:
                k, v = line.split("=", 1)
                env[k.strip()] = v.strip().strip('"').strip("'")
    return env


def notion_request(method, path, data=None, token=None):
    url = f"https://api.notion.com/v1{path}"
    body = json.dumps(data).encode("utf-8") if data is not None else None
    headers = {
        "Authorization": f"Bearer {token}",
        "Notion-Version": "2022-06-28",
        "Content-Type": "application/json",
    }
    req = urllib.request.Request(url, data=body, headers=headers, method=method)
    try:
        with urllib.request.urlopen(req, context=SSL_CTX, timeout=30) as res:
            return json.loads(res.read())
    except urllib.error.HTTPError as e:
        try:
            err = json.loads(e.read().decode("utf-8"))
            msg = err.get("message", "")
        except Exception:
            msg = "レスポンス解析不可"
        print(f"[ERROR] {e.code}: {msg}")
        sys.exit(1)
    except urllib.error.URLError as e:
        print(f"[ERROR] 接続エラー: {e.reason}")
        sys.exit(1)


# ---- プロパティ変換 ----

def get_text(props, key):
    p = props.get(key, {})
    t = p.get("type", "")
    if t == "title":
        return "".join(i.get("plain_text", "") for i in p.get("title", []))
    if t == "rich_text":
        return "".join(i.get("plain_text", "") for i in p.get("rich_text", []))
    return ""

def get_select(props, key):
    s = props.get(key, {}).get("select")
    return s["name"] if s else ""

def get_date(props, key):
    d = props.get(key, {}).get("date")
    return d["start"] if d else ""

def get_number(props, key):
    n = props.get(key, {}).get("number")
    return n if n is not None else None

def get_relation_ids(props, key):
    """リレーションフィールドからページIDリストを返す"""
    return [r["id"] for r in props.get(key, {}).get("relation", [])]

def fetch_customer_name(token, page_id):
    """CRMページIDから顧客名を取得する（show コマンド用）"""
    try:
        page = notion_request("GET", f"/pages/{page_id}", token=token)
        return get_text(page.get("properties", {}), "会社名 / 屋号")
    except Exception:
        return ""

def page_to_row(page):
    p = page.get("properties", {})
    amount = get_number(p, "金額（税込）")
    return {
        "id": page["id"],
        "管理番号": get_text(p, "管理番号"),
        "種別": get_select(p, "種別"),
        "発行日": get_date(p, "発行日"),
        "金額（税込）": f"¥{int(amount):,}" if amount is not None else "",
        "件名": get_text(p, "件名"),
        "納品日": get_date(p, "納品日"),
        "入金状況": get_select(p, "入金状況"),
        "入金日": get_date(p, "入金日"),
        "ステータス": get_select(p, "ステータス"),
        "メモ": get_text(p, "メモ"),
        "_customer_ids": get_relation_ids(p, "顧客"),
    }


# ---- 管理番号採番 ----

def generate_management_no(token, ledger_db_id, customer_code, issue_date):
    """
    管理番号を自動採番する。
    形式: YYMMDD + 識別記号 + - + 版数
    版数は同日・同顧客の既存レコード数 + 1
    """
    yy = issue_date[2:4]  # YYYY-MM-DD → YY
    mm = issue_date[5:7]
    dd = issue_date[8:10]
    prefix = f"{yy}{mm}{dd}{customer_code}"

    # 既存レコードから同じプレフィックスの件数をカウント
    result = notion_request("POST", f"/databases/{ledger_db_id}/query", {
        "filter": {
            "property": "管理番号",
            "title": {"starts_with": prefix}
        }
    }, token=token)
    existing_count = len(result.get("results", []))
    version = existing_count + 1
    return f"{prefix}-{version}"


# ---- 顧客検索（CRMから） ----

def search_customer(keyword, token, crm_db_id):
    result = notion_request("POST", f"/databases/{crm_db_id}/query", {
        "filter": {"property": "会社名 / 屋号", "rich_text": {"contains": keyword}}
    }, token=token)
    pages = result.get("results", [])
    if not pages:
        return None, None, None

    def extract_customer(p):
        props = p["properties"]
        name = "".join(i.get("plain_text", "") for i in props.get("会社名 / 屋号", {}).get("title", []))
        code_items = props.get("識別記号", {}).get("rich_text", [])
        code = "".join(i.get("plain_text", "") for i in code_items)
        return p["id"], name, code

    if len(pages) == 1:
        return extract_customer(pages[0])

    print(f"\n{len(pages)} 件見つかりました：")
    for i, p in enumerate(pages, 1):
        _, name, code = extract_customer(p)
        code_display = f"[{code}]" if code else "[識別記号未発行]"
        print(f"  {i}. {code_display} {name}")
    idx = input("  番号を選択: ").strip()
    try:
        return extract_customer(pages[int(idx) - 1])
    except (ValueError, IndexError):
        return None, None, None


# ---- 入力ヘルパー ----

def prompt(label, default=""):
    hint = f" [{default}]" if default else ""
    val = input(f"  {label}{hint}: ").strip()
    return val if val else default

def prompt_date(label, default=""):
    while True:
        val = prompt(label, default)
        if not val:
            return val
        try:
            datetime.strptime(val, "%Y-%m-%d")
            return val
        except ValueError:
            print("  [ERROR] YYYY-MM-DD 形式で入力してください。")

def prompt_choice(label, options, default=""):
    print(f"  {label}")
    for i, opt in enumerate(options, 1):
        print(f"    {i}. {opt}")
    hint = f" [{default}]" if default else " [スキップ=Enter]"
    val = input(f"  番号を入力{hint}: ").strip()
    if not val:
        return default
    try:
        idx = int(val) - 1
        if 0 <= idx < len(options):
            return options[idx]
    except ValueError:
        pass
    return default

def prompt_amount(label, default=""):
    while True:
        val = prompt(label, default)
        if not val:
            return None
        try:
            return int(val.replace(",", "").replace("¥", ""))
        except ValueError:
            print("  [ERROR] 数値で入力してください（例: 110000）")


# ---- コマンド ----

def cmd_list(token, ledger_db_id):
    result = notion_request("POST", f"/databases/{ledger_db_id}/query", {
        "sorts": [{"property": "発行日", "direction": "descending"}]
    }, token=token)
    pages = result.get("results", [])
    if not pages:
        print("台帳データがありません。")
        return
    print(f"\n{'管理番号':14} {'種別':6} {'発行日':12} {'金額（税込）':14} {'ステータス':8}")
    print("-" * 60)
    for page in pages:
        row = page_to_row(page)
        print(
            f"{row['管理番号']:14} "
            f"{row['種別']:6} "
            f"{row['発行日']:12} "
            f"{row['金額（税込）']:14} "
            f"{row['ステータス']:8}"
        )
    print(f"\n合計 {len(pages)} 件")


def cmd_add(token, ledger_db_id, crm_db_id):
    print("\n--- 台帳レコード追加 ---")

    # 顧客選択（識別記号が必要なため必須）
    kw = prompt("顧客名で検索（必須）")
    if not kw:
        print("[ERROR] 顧客名を入力してください。")
        return
    customer_page_id, customer_name, customer_code = search_customer(kw, token, crm_db_id)
    if not customer_page_id:
        print("[ERROR] 顧客が見つかりません。CRM に登録してから再実行してください。")
        return
    if not customer_code:
        print(f"[ERROR] {customer_name} の識別記号が未発行です。")
        print("  先に `notion-crm.py issue-no` で識別記号を発行してください。")
        return
    print(f"  → {customer_name}（識別記号: {customer_code}）")

    data = {
        "種別": prompt_choice("種別", TYPE_OPTIONS),
        "発行日": prompt_date("発行日（YYYY-MM-DD）", datetime.today().strftime("%Y-%m-%d")),
        "件名": prompt("件名"),
        "ステータス": prompt_choice("ステータス", STATUS_OPTIONS),
        "納品日": prompt_date("納品日（YYYY-MM-DD、スキップ=Enter）"),
        "入金状況": prompt_choice("入金状況", PAYMENT_OPTIONS),
        "入金日": prompt_date("入金日（YYYY-MM-DD、スキップ=Enter）"),
        "メモ": prompt("メモ"),
    }
    data["金額"] = prompt_amount("金額（税込）（数値のみ、例: 110000）")

    if not data["発行日"]:
        print("[ERROR] 発行日は必須です。")
        return

    # 管理番号自動採番
    management_no = generate_management_no(
        token, ledger_db_id, customer_code, data["発行日"]
    )
    print(f"\n  管理番号（自動採番）: {management_no}")
    confirm = input("  この内容で登録しますか？ [y/N]: ").strip().lower()
    if confirm != "y":
        print("キャンセルしました。")
        return

    props = {
        "管理番号": {"title": [{"text": {"content": management_no}}]},
        "顧客": {"relation": [{"id": customer_page_id}]},
    }
    if data["種別"]:
        props["種別"] = {"select": {"name": data["種別"]}}
    if data["発行日"]:
        props["発行日"] = {"date": {"start": data["発行日"]}}
    if data["件名"]:
        props["件名"] = {"rich_text": [{"text": {"content": data["件名"]}}]}
    if data["ステータス"]:
        props["ステータス"] = {"select": {"name": data["ステータス"]}}
    if data["納品日"]:
        props["納品日"] = {"date": {"start": data["納品日"]}}
    if data["入金状況"]:
        props["入金状況"] = {"select": {"name": data["入金状況"]}}
    if data["入金日"]:
        props["入金日"] = {"date": {"start": data["入金日"]}}
    if data["金額"] is not None:
        props["金額（税込）"] = {"number": data["金額"]}
    if data["メモ"]:
        props["メモ"] = {"rich_text": [{"text": {"content": data["メモ"]}}]}

    notion_request("POST", "/pages", {
        "parent": {"database_id": ledger_db_id},
        "properties": props,
    }, token=token)
    print(f"\n追加しました: {management_no}  {customer_name}  {data['件名']}")


def cmd_search(keyword, token, ledger_db_id):
    result = notion_request("POST", f"/databases/{ledger_db_id}/query", {
        "filter": {
            "or": [
                {"property": "管理番号", "title": {"contains": keyword}},
                {"property": "件名", "rich_text": {"contains": keyword}},
                {"property": "メモ", "rich_text": {"contains": keyword}},
            ]
        }
    }, token=token)
    pages = result.get("results", [])
    if not pages:
        print(f"「{keyword}」に一致するレコードは見つかりませんでした。")
        return
    print(f"\n「{keyword}」の検索結果 {len(pages)} 件:")
    print(f"{'管理番号':14} {'種別':6} {'発行日':12} {'金額（税込）':14} {'ステータス':8}")
    print("-" * 60)
    for page in pages:
        row = page_to_row(page)
        print(
            f"{row['管理番号']:14} "
            f"{row['種別']:6} "
            f"{row['発行日']:12} "
            f"{row['金額（税込）']:14} "
            f"{row['ステータス']:8}"
        )


def cmd_show(keyword_or_id, token, ledger_db_id):
    page = resolve_page(keyword_or_id, token, ledger_db_id)
    if not page:
        return
    row = page_to_row(page)
    # 顧客名をCRMから取得
    customer_name = ""
    if row["_customer_ids"]:
        customer_name = fetch_customer_name(token, row["_customer_ids"][0])
    print("\n--- 台帳詳細 ---")
    print(f"  {'管理番号':12}: {row['管理番号']}")
    print(f"  {'顧客名':12}: {customer_name}")
    for k in ["種別", "発行日", "金額（税込）", "件名", "納品日", "入金状況", "入金日", "ステータス", "メモ"]:
        print(f"  {k:12}: {row[k]}")
    print(f"\n  ページID: {row['id']}")


def cmd_update(keyword, token, ledger_db_id):
    page = resolve_page(keyword, token, ledger_db_id)
    if not page:
        return
    current = page_to_row(page)

    print(f"\n--- 更新: {current['管理番号']} ---")
    print(f"  管理番号: {current['管理番号']}（変更不可）")
    print("  変更する項目のみ入力してください（Enterでスキップ）\n")

    new_status = prompt_choice("ステータス", STATUS_OPTIONS, current["ステータス"])
    new_type = prompt_choice("種別", TYPE_OPTIONS, current["種別"])
    new_subject = prompt("件名", current["件名"])
    new_amount_str = prompt("金額（税込）（数値のみ）", current["金額（税込）"].replace("¥", "").replace(",", "") if current["金額（税込）"] else "")
    new_delivery = prompt_date("納品日（YYYY-MM-DD）", current["納品日"])
    new_payment_status = prompt_choice("入金状況", PAYMENT_OPTIONS, current["入金状況"])
    new_payment_date = prompt_date("入金日（YYYY-MM-DD）", current["入金日"])
    new_memo = prompt("メモ", current["メモ"])

    props = {}
    if new_status:
        props["ステータス"] = {"select": {"name": new_status}}
    if new_type:
        props["種別"] = {"select": {"name": new_type}}
    if new_subject:
        props["件名"] = {"rich_text": [{"text": {"content": new_subject}}]}
    if new_amount_str:
        try:
            props["金額（税込）"] = {"number": int(new_amount_str.replace(",", ""))}
        except ValueError:
            print("  [WARN] 金額の形式が不正なためスキップしました。")
    if new_delivery:
        props["納品日"] = {"date": {"start": new_delivery}}
    if new_payment_status:
        props["入金状況"] = {"select": {"name": new_payment_status}}
    if new_payment_date:
        props["入金日"] = {"date": {"start": new_payment_date}}
    if new_memo:
        props["メモ"] = {"rich_text": [{"text": {"content": new_memo}}]}

    if not props:
        print("\n変更なし。")
        return

    notion_request("PATCH", f"/pages/{page['id']}", {"properties": props}, token=token)
    print(f"\n更新しました: {current['管理番号']}")


def resolve_page(keyword_or_id, token, ledger_db_id):
    """管理番号・件名・顧客名・ページIDで1件に絞る"""
    # フルUUIDの場合は直接取得
    if UUID_RE.match(keyword_or_id):
        return notion_request("GET", f"/pages/{keyword_or_id}", token=token)

    result = notion_request("POST", f"/databases/{ledger_db_id}/query", {
        "filter": {
            "or": [
                {"property": "管理番号", "title": {"contains": keyword_or_id}},
                {"property": "件名", "rich_text": {"contains": keyword_or_id}},
                {"property": "メモ", "rich_text": {"contains": keyword_or_id}},
            ]
        },
        "sorts": [{"property": "発行日", "direction": "descending"}]
    }, token=token)
    pages = result.get("results", [])
    if not pages:
        print(f"「{keyword_or_id}」に一致するレコードが見つかりません。")
        return None
    if len(pages) == 1:
        return pages[0]

    print(f"\n{len(pages)} 件見つかりました：")
    for i, p in enumerate(pages, 1):
        row = page_to_row(p)
        print(f"  {i}. {row['管理番号']:14} {row['発行日']:12} {row['ステータス']}")
    idx = input("  番号を選択: ").strip()
    try:
        return pages[int(idx) - 1]
    except (ValueError, IndexError):
        print("[ERROR] 不正な番号です。")
        return None


# ---- エントリーポイント ----

def main():
    sys.stdout.reconfigure(encoding="utf-8")

    env = load_env()
    token = env.get("NOTION_API_TOKEN", "")
    ledger_db_id = env.get("NOTION_LEDGER_DB_ID", "")
    crm_db_id = env.get("NOTION_CRM_DB_ID", "")

    if not token or not ledger_db_id:
        print("[ERROR] .env に NOTION_API_TOKEN / NOTION_LEDGER_DB_ID が設定されていません。")
        sys.exit(1)

    args = sys.argv[1:]
    if not args:
        print(__doc__)
        sys.exit(0)

    cmd = args[0]
    if cmd == "list":
        cmd_list(token, ledger_db_id)
    elif cmd == "add":
        if not crm_db_id:
            print("[ERROR] .env に NOTION_CRM_DB_ID が設定されていません。")
            sys.exit(1)
        cmd_add(token, ledger_db_id, crm_db_id)
    elif cmd == "search":
        if len(args) < 2:
            print("使い方: notion-ledger.py search <キーワード>")
            sys.exit(1)
        cmd_search(args[1], token, ledger_db_id)
    elif cmd == "show":
        if len(args) < 2:
            print("使い方: notion-ledger.py show <管理番号またはキーワード>")
            sys.exit(1)
        cmd_show(args[1], token, ledger_db_id)
    elif cmd == "update":
        if len(args) < 2:
            print("使い方: notion-ledger.py update <管理番号またはキーワード>")
            sys.exit(1)
        cmd_update(args[1], token, ledger_db_id)
    else:
        print(f"[ERROR] 不明なコマンド: {cmd}")
        print(__doc__)
        sys.exit(1)


if __name__ == "__main__":
    main()
