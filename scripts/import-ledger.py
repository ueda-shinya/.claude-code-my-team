#!/usr/bin/env python3
"""見積・請求台帳 Excel → Notion インポートスクリプト

【注意】このスクリプトは 2026-03-26 の初回一括インポート用ワンショットスクリプトです。
再実行する場合は、Notion DB 上の既存レコードとの重複チェックを必ず事前に行ってください。
（重複チェックなしで実行すると全件が二重登録されます）
"""

import openpyxl
import json
import urllib.request
import urllib.parse
import ssl
import os
import re
import sys
import time
from datetime import datetime

sys.stdout.reconfigure(encoding='utf-8')

# --- 設定 ---
EXCEL_PATH = os.path.expanduser('~/.claude/reports/管理台帳.xlsx')
ENV_PATH   = os.path.expanduser('~/.claude/.env')

def load_env():
    env = {}
    with open(ENV_PATH, encoding='utf-8') as f:
        for line in f:
            line = line.strip()
            if line and not line.startswith('#') and '=' in line:
                k, v = line.split('=', 1)
                env[k.strip()] = v.strip()
    return env

env = load_env()
TOKEN       = env['NOTION_API_TOKEN']
LEDGER_DB   = env['NOTION_LEDGER_DB_ID']
CRM_DB      = env['NOTION_CRM_DB_ID']
HEADERS     = {
    'Authorization': f'Bearer {TOKEN}',
    'Notion-Version': '2022-06-28',
    'Content-Type': 'application/json',
}
ctx = ssl.create_default_context()

def notion_request(method, path, body=None, timeout=60):
    url = f'https://api.notion.com/v1{path}'
    data = json.dumps(body).encode() if body else None
    req = urllib.request.Request(url, data=data, headers=HEADERS, method=method)
    with urllib.request.urlopen(req, context=ctx, timeout=timeout) as res:
        return json.loads(res.read())

# --- 顧客リスト（CRM）を取得して 会社名→page_id マップを作る ---
def get_crm_map():
    crm_map = {}
    cursor = None
    while True:
        body = {'page_size': 100}
        if cursor:
            body['start_cursor'] = cursor
        res = notion_request('POST', f'/databases/{CRM_DB}/query', body)
        for page in res.get('results', []):
            props = page['properties']
            title_prop = props.get('会社名 / 屋号', {})
            title_arr = title_prop.get('title', [])
            name = ''.join(t.get('plain_text','') for t in title_arr).strip()
            if name:
                crm_map[name] = page['id']
        if not res.get('has_more'):
            break
        cursor = res.get('next_cursor')
    return crm_map

print('顧客リストを取得中...')
crm_map = get_crm_map()
print(f'  → {len(crm_map)} 件取得')

# --- 管理番号変換: YYMMDD記号-版数 → YYMMDD記号-版数 (版数のゼロ埋め除去のみ) ---
def convert_bangou(old):
    if not old or not isinstance(old, str):
        return old
    # 例: 220824A-01 → 220824A-1
    m = re.match(r'^(\d{6})([A-Z]{1,3})-(\d+)$', old.strip())
    if m:
        yymmdd, shikibetsu, version = m.group(1), m.group(2), m.group(3)
        ver = str(int(version))  # 01 → 1
        return f'{yymmdd}{shikibetsu}-{ver}'
    return old  # 変換できない場合はそのまま

# --- 種別を判定 ---
def determine_shubetsu(e_val, has_seikyu, has_ryoshu, has_mitsu):
    if isinstance(e_val, str):
        e_lower = e_val.strip()
        if e_lower == '領収書':
            return '領収書'
        if e_lower in ('見積り', '見積書', '概算', '参考'):
            return '見積書'
    if has_seikyu:
        return '請求書'
    if has_ryoshu:
        return '領収書'
    return '請求書'  # デフォルト

# --- 日付変換 ---
def to_iso(val):
    if isinstance(val, datetime):
        return val.strftime('%Y-%m-%d')
    if isinstance(val, str) and val.strip() not in ('-', ''):
        return None  # 変換できない文字列は無視
    return None

# --- Notionプロパティ構築 ---
def build_props(bangou, shubetsu, hakkouhi, kokyaku_id, kingaku, memo, nōhinbi, nyukinbi, nyukin_status, tanto):
    props = {}

    if bangou:
        props['管理番号'] = {'title': [{'text': {'content': bangou}}]}

    if shubetsu:
        props['種別'] = {'select': {'name': shubetsu}}

    if hakkouhi:
        props['発行日'] = {'date': {'start': hakkouhi}}

    if kokyaku_id:
        props['顧客'] = {'relation': [{'id': kokyaku_id}]}

    if kingaku is not None:
        try:
            props['金額（税込）'] = {'number': float(kingaku)}
        except (TypeError, ValueError):
            pass

    if memo:
        props['メモ'] = {'rich_text': [{'text': {'content': str(memo)[:2000]}}]}

    if nōhinbi:
        props['納品日'] = {'date': {'start': nōhinbi}}

    if nyukinbi:
        props['入金日'] = {'date': {'start': nyukinbi}}

    if nyukin_status:
        props['入金状況'] = {'select': {'name': nyukin_status}}

    if tanto:
        props['担当'] = {'rich_text': [{'text': {'content': str(tanto)[:200]}}]}

    return props

# --- Excel読み込み ---
wb = openpyxl.load_workbook(EXCEL_PATH)
ws = wb.active

rows_data = []
for row in ws.iter_rows(min_row=2, values_only=True):
    if not row[0]:
        continue  # 管理番号なしはスキップ

    old_bangou = str(row[0]).strip()
    hakkouhi_raw = row[1]
    company = str(row[2]).strip() if row[2] else ''
    kingaku = row[3]
    e_val   = row[4]
    memo_f  = row[5]  # F列メモ
    nōhinbi_raw = row[6]
    # H列: 納品書発行日 (row[7])
    seikyu_date_raw  = row[8]  # I列: 請求書発行日
    ryoshu_date_raw  = row[9]  # J列: 領収書発行日 = 入金日
    memo_k  = row[10]  # K列メモ
    tanto   = row[15]  # P列: 代表/担当者

    # 変換
    bangou   = convert_bangou(old_bangou)
    hakkouhi = to_iso(hakkouhi_raw)
    if not hakkouhi:
        # 発行日が'-'の場合は請求書発行日を代用
        hakkouhi = to_iso(seikyu_date_raw)

    has_seikyu = to_iso(seikyu_date_raw) is not None
    has_ryoshu = to_iso(ryoshu_date_raw) is not None
    has_mitsu  = isinstance(e_val, str) and e_val.strip() in ('見積り', '見積書', '概算', '参考')

    shubetsu   = determine_shubetsu(e_val, has_seikyu, has_ryoshu, has_mitsu)
    nōhinbi    = to_iso(nōhinbi_raw)
    nyukinbi   = to_iso(ryoshu_date_raw)  # 領収書日 = 入金日
    nyukin_status = '入金済み' if nyukinbi else '未入金'

    # メモ合成（E列がメモ的内容のもの + F列 + K列）
    memo_parts = []
    if isinstance(e_val, str) and e_val.strip() not in (
        '-', '', '請求書', '領収書', '見積り', '見積書', '概算', '参考', '発注書'
    ):
        memo_parts.append(e_val.strip())
    if memo_f:
        memo_parts.append(str(memo_f).strip())
    if memo_k:
        memo_parts.append(str(memo_k).strip())
    memo = ' / '.join(filter(None, memo_parts)) or None

    # 顧客リストのpage_id検索（完全一致→部分一致）
    kokyaku_id = crm_map.get(company)
    if not kokyaku_id:
        # 部分一致で検索
        for k, v in crm_map.items():
            if company in k or k in company:
                kokyaku_id = v
                break

    rows_data.append({
        'old_bangou': old_bangou,
        'bangou': bangou,
        'shubetsu': shubetsu,
        'hakkouhi': hakkouhi,
        'company': company,
        'kokyaku_id': kokyaku_id,
        'kingaku': kingaku,
        'memo': memo,
        'nōhinbi': nōhinbi,
        'nyukinbi': nyukinbi,
        'nyukin_status': nyukin_status,
        'tanto': tanto,
    })

print(f'対象レコード数: {len(rows_data)}')
matched = sum(1 for r in rows_data if r['kokyaku_id'])
print(f'  顧客リスト紐付き: {matched} 件 / 未紐付き: {len(rows_data) - matched} 件')

# 確認
print('\n最初の5件プレビュー:')
for r in rows_data[:5]:
    print(f"  {r['old_bangou']} → {r['bangou']} / {r['shubetsu']} / {r['company']} / ¥{r['kingaku']} / {r['hakkouhi']} / 顧客:{r['kokyaku_id'] and '○' or '×'}")

# --- Notionに登録 ---
print('\nNotion登録開始...')
ok = 0
err = 0
err_list = []

for i, r in enumerate(rows_data):
    props = build_props(
        bangou        = r['bangou'],
        shubetsu      = r['shubetsu'],
        hakkouhi      = r['hakkouhi'],
        kokyaku_id    = r['kokyaku_id'],
        kingaku       = r['kingaku'],
        memo          = r['memo'],
        nōhinbi       = r['nōhinbi'],
        nyukinbi      = r['nyukinbi'],
        nyukin_status = r['nyukin_status'],
        tanto         = r['tanto'],
    )
    body = {
        'parent': {'database_id': LEDGER_DB},
        'properties': props,
    }
    try:
        notion_request('POST', '/pages', body, timeout=30)
        ok += 1
        if (i+1) % 20 == 0:
            print(f'  [{i+1}/{len(rows_data)}] 登録済み...')
        time.sleep(0.35)  # レートリミット対策
    except Exception as e:
        err += 1
        err_list.append(f"{r['old_bangou']}: {e}")
        time.sleep(0.5)

print(f'\n完了: 成功 {ok} / エラー {err}')
if err_list:
    print('エラー一覧:')
    for e in err_list[:10]:
        print(f'  {e}')
