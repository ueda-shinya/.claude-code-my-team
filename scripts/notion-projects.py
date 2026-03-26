#!/usr/bin/env python3
"""
Notion 案件・議事録管理スクリプト
使い方:
  python3 notion-projects.py list                      # 案件一覧
  python3 notion-projects.py add                       # 案件追加
  python3 notion-projects.py update <案件名キーワード>  # 案件更新
  python3 notion-projects.py minutes-add               # 議事録追加
  python3 notion-projects.py minutes-list              # 議事録一覧
  python3 notion-projects.py query <キーワード>         # 案件・議事録をまとめて検索
"""

import json
import os
import re
import ssl
import sys
import urllib.request
import urllib.error
from datetime import datetime

ENV_PATH = os.path.expanduser("~/.claude/.env")
UUID_RE = re.compile(r"^[0-9a-f]{8}-?[0-9a-f]{4}-?[0-9a-f]{4}-?[0-9a-f]{4}-?[0-9a-f]{12}$", re.I)
SSL_CTX = ssl.create_default_context()

STATUS_OPTIONS = ["提案中", "進行中", "完了", "保留", "失注"]
BIZTYPE_OPTIONS = ["Web制作", "AI", "その他"]


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
    return str(n) if n is not None else ""


# ---- 入力ヘルパー ----

def prompt(label, default=""):
    hint = f" [{default}]" if default else ""
    val = input(f"  {label}{hint}: ").strip()
    return val if val else default

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


# ---- 案件 ----

def page_to_project(page):
    p = page["properties"]
    return {
        "id": page["id"],
        "案件名": get_text(p, "案件名"),
        "顧客名": get_text(p, "顧客名"),
        "顧客管理No.": get_text(p, "顧客管理No."),
        "ステータス": get_select(p, "ステータス"),
        "事業種別": get_select(p, "事業種別"),
        "開始日": get_date(p, "開始日"),
        "完了日": get_date(p, "完了日"),
        "金額": get_number(p, "金額"),
        "概要": get_text(p, "概要"),
    }

def cmd_list(token, project_db_id):
    result = notion_request("POST", f"/databases/{project_db_id}/query", {
        "sorts": [{"property": "開始日", "direction": "descending"}]
    }, token=token)
    pages = result.get("results", [])
    if not pages:
        print("案件データがありません。")
        return
    print(f"\n{'案件名':24} {'顧客名':16} {'ステータス':8} {'事業種別':8} {'開始日':12}")
    print("-" * 74)
    for page in pages:
        p = page_to_project(page)
        print(f"{p['案件名'][:24]:24} {p['顧客名'][:16]:16} {p['ステータス']:8} {p['事業種別']:8} {p['開始日']:12}")
    print(f"\n合計 {len(pages)} 件")

def search_customer(keyword, token, crm_db_id):
    """顧客リストからキーワード検索してページIDを返す"""
    result = notion_request("POST", f"/databases/{crm_db_id}/query", {
        "filter": {"property": "会社名 / 屋号", "rich_text": {"contains": keyword}}
    }, token=token)
    pages = result.get("results", [])
    if not pages:
        return None, None
    if len(pages) == 1:
        p = pages[0]
        name = "".join(i.get("plain_text", "") for i in p["properties"].get("会社名 / 屋号", {}).get("title", []))
        return p["id"], name
    print(f"\n{len(pages)} 件見つかりました：")
    for i, p in enumerate(pages, 1):
        name = "".join(i2.get("plain_text", "") for i2 in p["properties"].get("会社名 / 屋号", {}).get("title", []))
        no = "".join(i2.get("plain_text", "") for i2 in p["properties"].get("管理No.", {}).get("rich_text", []))
        print(f"  {i}. [{no}] {name}")
    idx = input("  番号を選択: ").strip()
    try:
        p = pages[int(idx) - 1]
        name = "".join(i2.get("plain_text", "") for i2 in p["properties"].get("会社名 / 屋号", {}).get("title", []))
        return p["id"], name
    except (ValueError, IndexError):
        return None, None


def cmd_add(token, project_db_id, crm_db_id):
    print("\n--- 案件追加 ---")
    data = {
        "案件名": prompt("案件名（必須）"),
        "ステータス": prompt_choice("ステータス", STATUS_OPTIONS),
        "事業種別": prompt_choice("事業種別", BIZTYPE_OPTIONS),
        "開始日": prompt_date("開始日（YYYY-MM-DD）", datetime.today().strftime("%Y-%m-%d")),
        "完了日": prompt_date("完了日（YYYY-MM-DD）"),
        "金額": prompt("金額（数値のみ）"),
        "概要": prompt("概要"),
    }
    if not data["案件名"]:
        print("[ERROR] 案件名は必須です。")
        return

    # 顧客リレーション
    customer_page_id = None
    customer_name = ""
    kw = prompt("顧客名で検索（スキップ=Enter）")
    if kw:
        customer_page_id, customer_name = search_customer(kw, token, crm_db_id)
        if customer_page_id:
            print(f"  → {customer_name} に紐づけます")
        else:
            print("  → 顧客が見つからないためリレーションなしで登録します")

    props = {
        "案件名": {"title": [{"text": {"content": data["案件名"]}}]},
    }
    if customer_page_id:
        props["顧客"] = {"relation": [{"id": customer_page_id}]}
        props["顧客名"] = {"rich_text": [{"text": {"content": customer_name}}]}
    if data["ステータス"]:
        props["ステータス"] = {"select": {"name": data["ステータス"]}}
    if data["事業種別"]:
        props["事業種別"] = {"select": {"name": data["事業種別"]}}
    if data["開始日"]:
        props["開始日"] = {"date": {"start": data["開始日"]}}
    if data["完了日"]:
        props["完了日"] = {"date": {"start": data["完了日"]}}
    if data["金額"]:
        try:
            props["金額"] = {"number": int(data["金額"].replace(",", ""))}
        except ValueError:
            pass
    if data["概要"]:
        props["概要"] = {"rich_text": [{"text": {"content": data["概要"]}}]}

    notion_request("POST", "/pages", {
        "parent": {"database_id": project_db_id},
        "properties": props,
    }, token=token)
    print(f"\n追加しました: {data['案件名']}")

def cmd_update_project(keyword, token, project_db_id):
    result = notion_request("POST", f"/databases/{project_db_id}/query", {
        "filter": {"property": "案件名", "rich_text": {"contains": keyword}}
    }, token=token)
    pages = result.get("results", [])
    if not pages:
        print(f"「{keyword}」に一致する案件が見つかりません。")
        return
    if len(pages) == 1:
        page = pages[0]
    else:
        print(f"\n{len(pages)} 件見つかりました：")
        for i, p in enumerate(pages, 1):
            proj = page_to_project(p)
            print(f"  {i}. {proj['案件名']} [{proj['ステータス']}]")
        idx = input("  番号を選択: ").strip()
        try:
            page = pages[int(idx) - 1]
        except (ValueError, IndexError):
            print("[ERROR] 不正な番号です。")
            return

    current = page_to_project(page)
    print(f"\n--- 更新: {current['案件名']} ---")
    print("  変更する項目のみ入力してください（Enterでスキップ）\n")

    new_status = prompt_choice("ステータス", STATUS_OPTIONS, current["ステータス"])
    new_kanryo = prompt_date("完了日（YYYY-MM-DD）", current["完了日"])
    new_kingaku = prompt("金額", current["金額"])
    new_gaiyou = prompt("概要", current["概要"])

    props = {}
    if new_status:
        props["ステータス"] = {"select": {"name": new_status}}
    if new_kanryo:
        props["完了日"] = {"date": {"start": new_kanryo}}
    if new_kingaku:
        try:
            props["金額"] = {"number": int(new_kingaku.replace(",", ""))}
        except ValueError:
            pass
    if new_gaiyou:
        props["概要"] = {"rich_text": [{"text": {"content": new_gaiyou}}]}

    notion_request("PATCH", f"/pages/{page['id']}", {"properties": props}, token=token)
    print(f"\n更新しました: {current['案件名']}")


# ---- 議事録 ----

def page_to_minutes(page):
    p = page["properties"]
    return {
        "id": page["id"],
        "タイトル": get_text(p, "タイトル"),
        "案件名": get_text(p, "案件名"),
        "顧客名": get_text(p, "顧客名"),
        "日付": get_date(p, "日付"),
        "決定事項": get_text(p, "決定事項"),
        "宿題・TODO": get_text(p, "宿題・TODO"),
        "メモ": get_text(p, "メモ"),
    }

def search_project(keyword, token, project_db_id):
    """案件リストからキーワード検索してページIDを返す"""
    result = notion_request("POST", f"/databases/{project_db_id}/query", {
        "filter": {"property": "案件名", "rich_text": {"contains": keyword}}
    }, token=token)
    pages = result.get("results", [])
    if not pages:
        return None, None
    if len(pages) == 1:
        p = pages[0]
        name = "".join(i.get("plain_text", "") for i in p["properties"].get("案件名", {}).get("title", []))
        return p["id"], name
    print(f"\n{len(pages)} 件見つかりました：")
    for i, p in enumerate(pages, 1):
        name = "".join(i2.get("plain_text", "") for i2 in p["properties"].get("案件名", {}).get("title", []))
        status = p["properties"].get("ステータス", {}).get("select", {})
        print(f"  {i}. [{status.get('name', '')}] {name}")
    idx = input("  番号を選択: ").strip()
    try:
        p = pages[int(idx) - 1]
        name = "".join(i2.get("plain_text", "") for i2 in p["properties"].get("案件名", {}).get("title", []))
        return p["id"], name
    except (ValueError, IndexError):
        return None, None


def cmd_minutes_add(token, minutes_db_id, project_db_id):
    print("\n--- 議事録追加 ---")
    data = {
        "タイトル": prompt("タイトル（必須）"),
        "日付": prompt_date("日付（YYYY-MM-DD）", datetime.today().strftime("%Y-%m-%d")),
        "決定事項": prompt("決定事項"),
        "宿題・TODO": prompt("宿題・TODO"),
        "メモ": prompt("メモ"),
    }
    if not data["タイトル"]:
        print("[ERROR] タイトルは必須です。")
        return

    # 案件リレーション
    project_page_id = None
    project_name = ""
    kw = prompt("案件名で検索（スキップ=Enter）")
    if kw:
        project_page_id, project_name = search_project(kw, token, project_db_id)
        if project_page_id:
            print(f"  → {project_name} に紐づけます")
        else:
            print("  → 案件が見つからないためリレーションなしで登録します")

    props = {
        "タイトル": {"title": [{"text": {"content": data["タイトル"]}}]},
    }
    if project_page_id:
        props["案件"] = {"relation": [{"id": project_page_id}]}
        props["案件名"] = {"rich_text": [{"text": {"content": project_name}}]}
    if data["日付"]:
        props["日付"] = {"date": {"start": data["日付"]}}
    for key in ["決定事項", "宿題・TODO", "メモ"]:
        if data[key]:
            props[key] = {"rich_text": [{"text": {"content": data[key]}}]}

    notion_request("POST", "/pages", {
        "parent": {"database_id": minutes_db_id},
        "properties": props,
    }, token=token)
    print(f"\n追加しました: {data['タイトル']}")

def cmd_minutes_list(token, minutes_db_id):
    result = notion_request("POST", f"/databases/{minutes_db_id}/query", {
        "sorts": [{"property": "日付", "direction": "descending"}]
    }, token=token)
    pages = result.get("results", [])
    if not pages:
        print("議事録データがありません。")
        return
    print(f"\n{'日付':12} {'タイトル':28} {'案件名':20}")
    print("-" * 64)
    for page in pages:
        m = page_to_minutes(page)
        print(f"{m['日付']:12} {m['タイトル'][:28]:28} {m['案件名'][:20]:20}")
    print(f"\n合計 {len(pages)} 件")


# ---- クロス検索（アスカ向け） ----

def cmd_query(keyword, token, project_db_id, minutes_db_id):
    """「〇〇の件どうだったっけ？」に答えるための横断検索"""
    print(f"\n🔍 「{keyword}」で横断検索中...\n")

    # 案件検索
    proj_result = notion_request("POST", f"/databases/{project_db_id}/query", {
        "filter": {"or": [
            {"property": "案件名", "rich_text": {"contains": keyword}},
            {"property": "顧客名", "rich_text": {"contains": keyword}},
            {"property": "概要", "rich_text": {"contains": keyword}},
        ]}
    }, token=token)
    projects = [page_to_project(p) for p in proj_result.get("results", [])]

    # 議事録検索
    min_result = notion_request("POST", f"/databases/{minutes_db_id}/query", {
        "filter": {"or": [
            {"property": "タイトル", "rich_text": {"contains": keyword}},
            {"property": "案件名", "rich_text": {"contains": keyword}},
            {"property": "顧客名", "rich_text": {"contains": keyword}},
            {"property": "決定事項", "rich_text": {"contains": keyword}},
            {"property": "宿題・TODO", "rich_text": {"contains": keyword}},
            {"property": "メモ", "rich_text": {"contains": keyword}},
        ]}
    }, token=token)
    minutes = [page_to_minutes(p) for p in min_result.get("results", [])]

    if not projects and not minutes:
        print(f"「{keyword}」に関する案件・議事録は見つかりませんでした。")
        return

    if projects:
        print(f"## 案件 ({len(projects)} 件)")
        for p in projects:
            金額表示 = f" / ¥{int(p['金額']):,}" if p["金額"] else ""
            print(f"  [{p['ステータス']}] {p['案件名']}  顧客: {p['顧客名']}{金額表示}")
            if p["概要"]:
                print(f"    概要: {p['概要'][:80]}")
            print()

    if minutes:
        print(f"## 議事録 ({len(minutes)} 件)")
        for m in minutes:
            print(f"  {m['日付']}  {m['タイトル']}  案件: {m['案件名']}")
            if m["決定事項"]:
                print(f"    決定事項: {m['決定事項'][:80]}")
            if m["宿題・TODO"]:
                print(f"    TODO: {m['宿題・TODO'][:80]}")
            print()


# ---- エントリーポイント ----

def main():
    sys.stdout.reconfigure(encoding="utf-8")

    env = load_env()
    token = env.get("NOTION_API_TOKEN", "")
    project_db_id = env.get("NOTION_PROJECT_DB_ID", "")
    minutes_db_id = env.get("NOTION_MINUTES_DB_ID", "")
    crm_db_id = env.get("NOTION_CRM_DB_ID", "")

    if not token or not project_db_id or not minutes_db_id:
        print("[ERROR] .env に NOTION_API_TOKEN / NOTION_PROJECT_DB_ID / NOTION_MINUTES_DB_ID が必要です。")
        sys.exit(1)

    args = sys.argv[1:]
    if not args:
        print(__doc__)
        sys.exit(0)

    cmd = args[0]
    if cmd == "list":
        cmd_list(token, project_db_id)
    elif cmd == "add":
        cmd_add(token, project_db_id, crm_db_id)
    elif cmd == "update":
        if len(args) < 2:
            print("使い方: notion-projects.py update <案件名キーワード>")
            sys.exit(1)
        cmd_update_project(args[1], token, project_db_id)
    elif cmd == "minutes-add":
        cmd_minutes_add(token, minutes_db_id, project_db_id)
    elif cmd == "minutes-list":
        cmd_minutes_list(token, minutes_db_id)
    elif cmd == "query":
        if len(args) < 2:
            print("使い方: notion-projects.py query <キーワード>")
            sys.exit(1)
        cmd_query(args[1], token, project_db_id, minutes_db_id)
    else:
        print(f"[ERROR] 不明なコマンド: {cmd}")
        print(__doc__)
        sys.exit(1)

if __name__ == "__main__":
    main()
