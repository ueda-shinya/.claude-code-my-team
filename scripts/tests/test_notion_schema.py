#!/usr/bin/env python
# -*- coding: utf-8 -*-
"""
test_notion_schema.py — notion_schema.py の実DB整合性テスト

各DBクラスの定数が実Notion DBのプロパティ名・型と一致することを検証する。

実行方法（Windows）:
    python scripts/tests/test_notion_schema.py
"""

import json
import os
import ssl
import sys
import unittest
import urllib.request
import urllib.error

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

SCRIPTS_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
if SCRIPTS_DIR not in sys.path:
    sys.path.insert(0, SCRIPTS_DIR)

from notion_schema import (
    TasksDB, KaizenDB, CrmDB, LedgerDB, MinutesDB,
    ProjectsDB, RadarDB, SnsDB,
)

DB_DEFINITIONS = [
    {
        'cls': TasksDB,
        'expected_types': {
            TasksDB.TITLE:      'title',
            TasksDB.TYPE:       'select',
            TasksDB.STATUS:     'select',
            TasksDB.PRIORITY:   'select',
            TasksDB.CATEGORY:   'select',
            TasksDB.ENV:        'multi_select',
            TasksDB.CLIENT:     'select',
            TasksDB.ASSIGNEE:   'select',
            TasksDB.BLOCKER:    'rich_text',
            TasksDB.MEMO:       'rich_text',
            TasksDB.START_DATE: 'date',
        },
    },
    {
        'cls': KaizenDB,
        'expected_types': {
            KaizenDB.TITLE:               'title',
            KaizenDB.LEVEL:               'select',
            KaizenDB.DATE:                'date',
            KaizenDB.IMPLEMENTATION_DATE: 'date',
            KaizenDB.AREA:                'select',
            KaizenDB.ROOT_CATEGORY:       'select',
            KaizenDB.ROOT_SUMMARY:        'rich_text',
            KaizenDB.STATUS:              'select',
            KaizenDB.RELATED_FILES:       'rich_text',
            KaizenDB.WHY_1:               'rich_text',
            KaizenDB.WHY_2:               'rich_text',
            KaizenDB.WHY_3:               'rich_text',
            KaizenDB.COUNTERMEASURE:      'rich_text',
        },
    },
    {
        'cls': CrmDB,
        'expected_types': {
            CrmDB.COMPANY_NAME:      'title',
            CrmDB.CONTACT_NAME:      'rich_text',
            CrmDB.PHONE:             'phone_number',
            CrmDB.EMAIL:             'email',
            CrmDB.STATUS:            'select',
            CrmDB.BUSINESS_TYPE:     'select',
            CrmDB.ASSIGNEE:          'rich_text',
            CrmDB.LAST_CONTACT_DATE: 'date',
            CrmDB.SOURCE:            'select',
            CrmDB.MEMO:              'rich_text',
            CrmDB.IDENTIFIER:        'rich_text',
            CrmDB.DISCOUNT_RATE:     'rich_text',
            CrmDB.TRADE_HISTORY:     'relation',
        },
    },
    {
        'cls': LedgerDB,
        'expected_types': {
            LedgerDB.MANAGEMENT_NO:  'title',
            LedgerDB.STATUS:         'select',
            LedgerDB.TYPE:           'select',
            LedgerDB.ISSUE_DATE:     'date',
            LedgerDB.SUBJECT:        'rich_text',
            LedgerDB.DELIVERY_DATE:  'date',
            LedgerDB.PAYMENT_STATUS: 'select',
            LedgerDB.PAYMENT_DATE:   'date',
            LedgerDB.AMOUNT:         'number',
            LedgerDB.MEMO:           'rich_text',
            LedgerDB.CUSTOMER:       'relation',
            LedgerDB.COLLECTOR:      'rich_text',
        },
    },
    {
        'cls': MinutesDB,
        'expected_types': {
            MinutesDB.TITLE:        'title',
            MinutesDB.DATE:         'date',
            MinutesDB.PROJECT:      'relation',
            MinutesDB.PROJECT_NAME: 'rich_text',
            MinutesDB.CUSTOMER:     'rich_text',
            MinutesDB.DECISIONS:    'rich_text',
            MinutesDB.TODO:         'rich_text',
            MinutesDB.MEMO:         'rich_text',
        },
    },
    {
        'cls': ProjectsDB,
        'expected_types': {
            ProjectsDB.TITLE:      'title',
            ProjectsDB.STATUS:     'select',
            ProjectsDB.PHASE:      'rich_text',
            ProjectsDB.START_DATE: 'date',
            ProjectsDB.GOAL_DATE:  'date',
            ProjectsDB.KPI:        'rich_text',
            ProjectsDB.ASSIGNEE:   'rich_text',
            ProjectsDB.MEMO:       'rich_text',
        },
    },
    {
        'cls': RadarDB,
        'expected_types': {
            RadarDB.TITLE:          'title',
            RadarDB.POST_DATE:      'date',
            RadarDB.CATEGORY:       'select',
            RadarDB.SUMMARY:        'rich_text',
            RadarDB.LINK:           'url',
            RadarDB.SEQ_NO:         'number',
            RadarDB.SOURCE:         'select',
            RadarDB.RIKU_VERIFIED:  'select',
            RadarDB.KANATA_VERDICT: 'select',
            RadarDB.KANATA_REASON:  'rich_text',
            RadarDB.RECOMMEND:      'select',
            RadarDB.STATUS:         'select',
        },
    },
    {
        'cls': SnsDB,
        'expected_types': {
            SnsDB.TITLE:           'title',
            SnsDB.SCHEDULED_DATE:  'date',
            SnsDB.PLATFORM:        'select',
            SnsDB.STATUS:          'select',
            SnsDB.DRAFT_CONTENT:   'rich_text',
            SnsDB.CONTENT:         'rich_text',
            SnsDB.LIKES:           'number',
            SnsDB.IMPRESSIONS:     'number',
            SnsDB.RETWEETS:        'number',
            SnsDB.ENGAGEMENT_RATE: 'number',
        },
    },
]

ENV_PATH = os.path.expanduser('~/.claude/.env')
SSL_CTX = ssl.create_default_context()


def load_env() -> dict:
    if not os.path.exists(ENV_PATH):
        raise FileNotFoundError(f'.env が見つかりません: {ENV_PATH}')
    env = {}
    with open(ENV_PATH, encoding='utf-8') as f:
        for line in f:
            line = line.strip()
            if line and not line.startswith('#') and '=' in line:
                k, v = line.split('=', 1)
                env[k.strip()] = v.strip().strip('"').strip("'")
    return env


def fetch_db_schema(db_id: str, token: str) -> dict:
    url = f'https://api.notion.com/v1/databases/{db_id}'
    headers = {
        'Authorization': f'Bearer {token}',
        'Notion-Version': '2022-06-28',
    }
    req = urllib.request.Request(url, headers=headers, method='GET')
    try:
        with urllib.request.urlopen(req, context=SSL_CTX, timeout=30) as res:
            data = json.loads(res.read())
    except urllib.error.HTTPError as e:
        try:
            err = json.loads(e.read().decode('utf-8'))
            msg = err.get('message', '')
        except (json.JSONDecodeError, UnicodeDecodeError):
            msg = 'レスポンス解析不可'
        raise RuntimeError(f'Notion API エラー {e.code}: {msg}') from e
    except urllib.error.URLError as e:
        raise RuntimeError(f'接続エラー: {e.reason}') from e

    props = data.get('properties', {})
    return {name: info.get('type', '') for name, info in props.items()}


# Notion DB定数クラスで「定数ではない特殊属性」を明示除外
_META_ATTRS = {'ENV_KEY'}


def get_class_prop_names(cls) -> list:
    names = []
    for attr in dir(cls):
        if attr.startswith('_') or attr in _META_ATTRS:
            continue
        val = getattr(cls, attr)
        # 文字列値のクラス属性のみを対象（メソッド・関数・その他型を除外）
        if isinstance(val, str):
            names.append(val)
    return names


class TestNotionSchema(unittest.TestCase):
    """notion_schema.py の定数が実DBと整合しているかを検証するテスト"""

    env: dict = {}
    token: str = ''

    @classmethod
    def setUpClass(cls):
        cls.env = load_env()
        cls.token = cls.env.get('NOTION_API_TOKEN', '')
        if not cls.token:
            raise RuntimeError(
                '.env に NOTION_API_TOKEN が設定されていません。'
            )


def _make_test_method(db_def: dict):
    cls_obj = db_def['cls']
    expected_types = db_def['expected_types']
    cls_name = cls_obj.__name__

    def test_method(self):
        db_id = self.env.get(cls_obj.ENV_KEY, '')
        if not db_id:
            self.skipTest(f'.env に {cls_obj.ENV_KEY} が設定されていません。')
            return

        try:
            actual = fetch_db_schema(db_id, self.token)
        except RuntimeError as e:
            self.fail(f'{cls_name}: APIエラー — {e}')
            return

        prop_names = get_class_prop_names(cls_obj)
        missing = [name for name in prop_names if name not in actual]
        self.assertEqual(
            missing, [],
            msg=(
                f'{cls_name}: 以下のプロパティが実DBに存在しません。\n'
                f'  missing: {missing}\n'
                f'  実DBのプロパティ: {sorted(actual.keys())}'
            ),
        )

        # expected_types 網羅性の自己検証（M-2対応・prop_names を再利用）
        class_props = set(prop_names)
        expected_props = set(expected_types.keys())
        untyped = class_props - expected_props
        self.assertEqual(
            untyped, set(),
            msg=f'{cls_name}: expected_types に型定義がないプロパティ: {sorted(untyped)}',
        )

        type_errors = []
        for prop_name, expected_type in expected_types.items():
            actual_type = actual.get(prop_name, '')
            if actual_type != expected_type:
                type_errors.append(
                    f'  {prop_name!r}: 期待={expected_type!r}, 実際={actual_type!r}'
                )
        self.assertEqual(
            type_errors, [],
            msg=(
                f'{cls_name}: プロパティ型の不一致が検出されました。\n'
                + '\n'.join(type_errors)
            ),
        )

    test_method.__name__ = f'test_{cls_name.lower()}'
    test_method.__doc__ = f'{cls_name} の定数が実DBプロパティと一致すること'
    return test_method


for _db_def in DB_DEFINITIONS:
    _method = _make_test_method(_db_def)
    setattr(TestNotionSchema, _method.__name__, _method)


if __name__ == '__main__':
    print('=' * 60)
    print('notion_schema.py 実DB整合性テスト')
    print(f'対象DB数: {len(DB_DEFINITIONS)}')
    print('=' * 60)
    unittest.main(verbosity=2)
