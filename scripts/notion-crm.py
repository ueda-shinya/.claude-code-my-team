#!/usr/bin/env python3
"""
Notion CRM - 顧客リスト管理スクリプト
使い方:
  python3 notion-crm.py list                        # 一覧表示
  python3 notion-crm.py add                         # 対話形式で追加
  python3 notion-crm.py search <キーワード>          # 検索
  python3 notion-crm.py update <ページID>           # 対話形式で更新
  python3 notion-crm.py show <ページID>             # 詳細表示
  python3 notion-crm.py issue-no                   # 管理No.を発行する

【運用ルール】
  - 顧客追加時に管理No.は発行しない
  - 管理No.は見積書・請求書・領収書の発行が必要になったタイミングで発行する
  - 発行は `issue-no` コマンドを使い、重複チェック済みの番号が自動採番される
  - 採番順序: A→Z → AA→ZZ → AAA→ZZZ（最大18,278件）
"""

import json
import os
import re
import ssl
import string
import sys
import urllib.request
import urllib.error
from datetime import datetime

# ---- 設定 ----
ENV_PATH = os.path.expanduser("~/.claude/.env")
UUID_RE = re.compile(r"^[0-9a-f]{8}-?[0-9a-f]{4}-?[0-9a-f]{4}-?[0-9a-f]{4}-?[0-9a-f]{12}$", re.I)
SSL_CTX = ssl.create_default_context()

STATUS_OPTIONS = ["見込み", "商談中", "成約", "失注", "既存"]
BIZTYPE_OPTIONS = ["Web制作", "AI", "その他"]
SOURCE_OPTIONS = ["Instagram", "紹介", "HP問い合わせ", "その他"]


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


def next_management_no(token, db_id):
    """現在の最大管理No.を取得し、次のIDを返す"""
    result = notion_request_raw("POST", f"/databases/{db_id}/query", {}, token)
    pages = result.get("results", [])
    existing = set()
    for page in pages:
        items = page["properties"].get("管理No.", {}).get("rich_text", [])
        code = "".join(i.get("plain_text", "") for i in items).strip()
        if code:
            existing.add(code)

    # A→Z→AA→AZ→BA→ZZ→AAA... の順で次の未使用IDを返す
    LETTERS = string.ascii_uppercase
    for length in range(1, 5):
        import itertools
        for combo in itertools.product(LETTERS, repeat=length):
            candidate = "".join(combo)
            if candidate not in existing:
                return candidate
    raise RuntimeError("管理No.の上限に達しました")


def notion_request_raw(method, path, data, token):
    """グローバルHEADERSを使わず直接tokenを指定するバージョン（next_management_no用）"""
    import ssl as _ssl
    url = f"https://api.notion.com/v1{path}"
    body = json.dumps(data).encode("utf-8") if data is not None else None
    headers = {
        "Authorization": f"Bearer {token}",
        "Notion-Version": "2022-06-28",
        "Content-Type": "application/json",
    }
    req = urllib.request.Request(url, data=body, headers=headers, method=method)
    ctx = _ssl.create_default_context()
    with urllib.request.urlopen(req, context=ctx, timeout=30) as res:
        return json.loads(res.read())


def validate_page_id(pid):
    if not UUID_RE.match(pid):
        print(f"[ERROR] 不正なページID形式: {pid}")
        sys.exit(1)
    return pid


# ---- API ヘルパー ----

def notion_request(method, path, data=None, token=None, db_id=None):
    url = f"https://api.notion.com/v1{path}"
    body = json.dumps(data).encode("utf-8") if data else None
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
        except (json.JSONDecodeError, UnicodeDecodeError):
            msg = "レスポンス解析不可"
        print(f"[ERROR] {e.code}: {msg}")
        sys.exit(1)
    except urllib.error.URLError as e:
        print(f"[ERROR] 接続エラー: {e.reason}")
        sys.exit(1)


# ---- プロパティ変換 ----

def extract_text(prop):
    if not prop:
        return ""
    if prop.get("type") == "title":
        items = prop.get("title", [])
    elif prop.get("type") == "rich_text":
        items = prop.get("rich_text", [])
    else:
        return ""
    return "".join(i.get("plain_text", "") for i in items)

def extract_select(prop):
    if not prop:
        return ""
    s = prop.get("select")
    return s["name"] if s else ""

def extract_date(prop):
    if not prop:
        return ""
    d = prop.get("date")
    return d["start"] if d else ""

def extract_phone(prop):
    return prop.get("phone_number", "") if prop else ""

def extract_email(prop):
    return prop.get("email", "") if prop else ""

def page_to_row(page):
    p = page.get("properties", {})
    return {
        "id": page["id"],
        "会社名": extract_text(p.get("会社名 / 屋号")),
        "担当者名": extract_text(p.get("担当者名")),
        "電話番号": extract_phone(p.get("電話番号")),
        "メールアドレス": extract_email(p.get("メールアドレス")),
        "ステータス": extract_select(p.get("ステータス")),
        "事業種別": extract_select(p.get("事業種別")),
        "担当": extract_text(p.get("担当")),
        "最終連絡日": extract_date(p.get("最終連絡日")),
        "流入元": extract_select(p.get("流入元")),
        "協力金率": extract_text(p.get("協力金率")),
        "管理No.": extract_text(p.get("管理No.")),
        "メモ": extract_text(p.get("メモ")),
    }

def build_properties(data):
    props = {}
    if data.get("会社名"):
        props["会社名 / 屋号"] = {"title": [{"text": {"content": data["会社名"]}}]}
    if data.get("担当者名"):
        props["担当者名"] = {"rich_text": [{"text": {"content": data["担当者名"]}}]}
    if data.get("電話番号"):
        props["電話番号"] = {"phone_number": data["電話番号"]}
    if data.get("メールアドレス"):
        props["メールアドレス"] = {"email": data["メールアドレス"]}
    if data.get("ステータス"):
        props["ステータス"] = {"select": {"name": data["ステータス"]}}
    if data.get("事業種別"):
        props["事業種別"] = {"select": {"name": data["事業種別"]}}
    if data.get("担当"):
        props["担当"] = {"rich_text": [{"text": {"content": data["担当"]}}]}
    if data.get("最終連絡日"):
        props["最終連絡日"] = {"date": {"start": data["最終連絡日"]}}
    if data.get("流入元"):
        props["流入元"] = {"select": {"name": data["流入元"]}}
    if data.get("メモ"):
        props["メモ"] = {"rich_text": [{"text": {"content": data["メモ"]}}]}
    if data.get("管理No."):
        props["管理No."] = {"rich_text": [{"text": {"content": data["管理No."]}}]}
    if data.get("協力金率"):
        props["協力金率"] = {"rich_text": [{"text": {"content": data["協力金率"]}}]}
    return props


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


# ---- コマンド ----

def cmd_list(token, db_id):
    result = notion_request("POST", f"/databases/{db_id}/query", {
        "sorts": [{"property": "最終連絡日", "direction": "descending"}]
    }, token=token, db_id=db_id)
    pages = result.get("results", [])
    if not pages:
        print("顧客データがありません。")
        return
    print(f"\n{'管理No.':6} {'会社名':22} {'ステータス':8} {'事業種別':8} {'最終連絡日':12}")
    print("-" * 62)
    for page in pages:
        row = page_to_row(page)
        print(f"{row['管理No.']:6} {row['会社名'][:22]:22} {row['ステータス']:8} {row['事業種別']:8} {row['最終連絡日']:12}")
    print(f"\n合計 {len(pages)} 件")


def cmd_add(token, db_id):
    print("\n--- 顧客追加 ---")
    print("  ※ 管理No.は見積書・請求書・領収書の発行時に発行します（ここでは不要）\n")
    data = {
        "会社名": prompt("会社名 / 屋号（必須）"),
        "担当者名": prompt("担当者名"),
        "電話番号": prompt("電話番号"),
        "メールアドレス": prompt("メールアドレス"),
        "ステータス": prompt_choice("ステータス", STATUS_OPTIONS),
        "事業種別": prompt_choice("事業種別", BIZTYPE_OPTIONS),
        "担当": prompt("担当"),
        "最終連絡日": prompt_date("最終連絡日（YYYY-MM-DD）", datetime.today().strftime("%Y-%m-%d")),
        "流入元": prompt_choice("流入元", SOURCE_OPTIONS),
        "協力金率": prompt("協力金率（例: 10%）"),
        "メモ": prompt("メモ"),
    }
    if not data["会社名"]:
        print("[ERROR] 会社名は必須です。")
        return
    props = build_properties(data)
    result = notion_request("POST", "/pages", {
        "parent": {"database_id": db_id},
        "properties": props,
    }, token=token)
    print(f"\n追加しました: {data['会社名']}")


def cmd_issue_no(token, db_id):
    """管理No.を発行する（見積書・請求書・領収書の発行時に使用）"""
    print("\n--- 管理No. 発行 ---")
    keyword = prompt("会社名で検索")
    if not keyword:
        print("[ERROR] 会社名を入力してください。")
        return

    result = notion_request("POST", f"/databases/{db_id}/query", {
        "filter": {"property": "会社名 / 屋号", "rich_text": {"contains": keyword}}
    }, token=token)
    pages = result.get("results", [])
    if not pages:
        print(f"「{keyword}」に一致する顧客が見つかりません。")
        return

    # 既に管理No.があるものを除外
    candidates = []
    for page in pages:
        row = page_to_row(page)
        candidates.append(row)

    if len(candidates) == 1:
        row = candidates[0]
    else:
        print(f"\n{len(candidates)} 件見つかりました：")
        for i, row in enumerate(candidates, 1):
            no = row["管理No."] or "（未発行）"
            print(f"  {i}. [{no}] {row['会社名']}")
        idx = input("  番号を選択: ").strip()
        try:
            row = candidates[int(idx) - 1]
        except (ValueError, IndexError):
            print("[ERROR] 不正な番号です。")
            return

    if row["管理No."]:
        print(f"\nこの顧客の管理No.は既に発行済みです: {row['管理No.']}")
        return

    new_no = next_management_no(token, db_id)
    confirm = input(f"\n  管理No. [{new_no}] を {row['会社名']} に発行しますか？ [y/N]: ").strip().lower()
    if confirm != "y":
        print("キャンセルしました。")
        return

    notion_request("PATCH", f"/pages/{row['id']}", {
        "properties": {"管理No.": {"rich_text": [{"text": {"content": new_no}}]}}
    }, token=token)
    print(f"\n発行しました: [{new_no}] {row['会社名']}")


def cmd_search(keyword, token, db_id):
    result = notion_request("POST", f"/databases/{db_id}/query", {
        "filter": {
            "or": [
                {"property": "会社名 / 屋号", "rich_text": {"contains": keyword}},
                {"property": "担当者名", "rich_text": {"contains": keyword}},
                {"property": "メモ", "rich_text": {"contains": keyword}},
            ]
        }
    }, token=token)
    pages = result.get("results", [])
    if not pages:
        print(f"「{keyword}」に一致する顧客は見つかりませんでした。")
        return
    print(f"\n「{keyword}」の検索結果 {len(pages)} 件:")
    print(f"{'ID':10} {'会社名':20} {'ステータス':8} {'最終連絡日':12}")
    print("-" * 55)
    for page in pages:
        row = page_to_row(page)
        short_id = row["id"].replace("-", "")[:8]
        print(f"{short_id:10} {row['会社名'][:20]:20} {row['ステータス']:8} {row['最終連絡日']:12}")


def cmd_show(page_id, token, db_id):
    page_id = resolve_id(page_id, token, db_id)
    page = notion_request("GET", f"/pages/{page_id}", token=token)
    row = page_to_row(page)
    print("\n--- 顧客詳細 ---")
    for k, v in row.items():
        if k == "id":
            continue
        print(f"  {k:12}: {v}")


def cmd_update(page_id, token, db_id):
    page_id = resolve_id(page_id, token, db_id)
    page = notion_request("GET", f"/pages/{page_id}", token=token)
    current = page_to_row(page)
    print(f"\n--- 更新: {current['会社名']} ---")
    print("  変更する項目のみ入力してください（Enterでスキップ）\n")
    data = {
        "会社名": prompt("会社名 / 屋号", current["会社名"]),
        "担当者名": prompt("担当者名", current["担当者名"]),
        "電話番号": prompt("電話番号", current["電話番号"]),
        "メールアドレス": prompt("メールアドレス", current["メールアドレス"]),
        "ステータス": prompt_choice("ステータス", STATUS_OPTIONS, current["ステータス"]),
        "事業種別": prompt_choice("事業種別", BIZTYPE_OPTIONS, current["事業種別"]),
        "担当": prompt("担当", current["担当"]),
        "最終連絡日": prompt_date("最終連絡日（YYYY-MM-DD）", current["最終連絡日"]),
        "流入元": prompt_choice("流入元", SOURCE_OPTIONS, current["流入元"]),
        "メモ": prompt("メモ", current["メモ"]),
    }
    props = build_properties(data)
    notion_request("PATCH", f"/pages/{page_id}", {"properties": props}, token=token)
    print(f"\n更新しました: {page_id}")


def resolve_id(short_or_full, token, db_id):
    """8文字のショートIDをフルUUIDに解決する"""
    if len(short_or_full) > 8:
        validate_page_id(short_or_full)
        return short_or_full
    result = notion_request("POST", f"/databases/{db_id}/query", {}, token=token)
    matches = [
        page["id"]
        for page in result.get("results", [])
        if page["id"].replace("-", "").startswith(short_or_full)
    ]
    if len(matches) == 1:
        return matches[0]
    if len(matches) > 1:
        print(f"[ERROR] ID '{short_or_full}' が複数のレコードに一致します。フルIDで指定してください:")
        for m in matches:
            print(f"  {m}")
        sys.exit(1)
    print(f"[ERROR] ID '{short_or_full}' に一致するレコードが見つかりません。")
    sys.exit(1)


# ---- エントリーポイント ----

def main():
    sys.stdout.reconfigure(encoding="utf-8")

    env = load_env()
    token = env.get("NOTION_API_TOKEN", "")
    db_id = env.get("NOTION_CRM_DB_ID", "")

    if not token or not db_id:
        print("[ERROR] .env に NOTION_API_TOKEN / NOTION_CRM_DB_ID が設定されていません。")
        sys.exit(1)

    args = sys.argv[1:]
    if not args:
        print(__doc__)
        sys.exit(0)

    cmd = args[0]
    if cmd == "list":
        cmd_list(token, db_id)
    elif cmd == "add":
        cmd_add(token, db_id)
    elif cmd == "search":
        if len(args) < 2:
            print("使い方: notion-crm.py search <キーワード>")
            sys.exit(1)
        cmd_search(args[1], token, db_id)
    elif cmd == "show":
        if len(args) < 2:
            print("使い方: notion-crm.py show <ページID>")
            sys.exit(1)
        cmd_show(args[1], token, db_id)
    elif cmd == "update":
        if len(args) < 2:
            print("使い方: notion-crm.py update <ページID>")
            sys.exit(1)
        cmd_update(args[1], token, db_id)
    elif cmd == "issue-no":
        cmd_issue_no(token, db_id)
    else:
        print(f"[ERROR] 不明なコマンド: {cmd}")
        print(__doc__)
        sys.exit(1)

if __name__ == "__main__":
    main()
