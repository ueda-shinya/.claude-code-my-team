#!/usr/bin/env python
# -*- coding: utf-8 -*-
"""
notion_schema.py — Notion DBプロパティ名の単一ソース定義モジュール

各 Notion DB のプロパティ名を定数クラスで集約管理する。
スクリプト内でプロパティ名を直接ハードコードする代わりに、
このモジュールの定数を参照することでプロパティ名変更時の修正箇所を一元化する。

使い方:
    from notion_schema import TasksDB, KaizenDB, CrmDB, LedgerDB, MinutesDB
    props = {'property': TasksDB.STATUS, 'select': {'equals': '進行中'}}

新しいDBを追加する場合:
    1. 下部の「新DB追加テンプレート」コメントを参考にクラスを定義する
    2. test_notion_schema.py の DB_DEFINITIONS にエントリを追加する
    3. 実DBのスキーマと一致していることをテストで確認する
"""

import sys

# Windows環境での文字化け対策
if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')


# ============================================================
# TASKS DB — 案件管理
# ============================================================
class TasksDB:
    """案件管理DB（NOTION_TASKS_DB_ID）のプロパティ名定数"""

    ENV_KEY = 'NOTION_TASKS_DB_ID'

    TITLE      = 'タイトル'
    TYPE       = '種別'
    STATUS     = 'ステータス'
    PRIORITY   = '優先度'
    CATEGORY   = 'カテゴリ'
    ENV        = '対象環境'
    CLIENT     = 'クライアント'
    ASSIGNEE   = '担当'
    BLOCKER    = 'ブロッカー'
    MEMO       = 'メモ'
    START_DATE = '開始日'


# ============================================================
# KAIZEN DB — なぜなぜ分析
# ============================================================
class KaizenDB:
    """なぜなぜ分析DB（NOTION_KAIZEN_DB_ID）のプロパティ名定数"""

    ENV_KEY = 'NOTION_KAIZEN_DB_ID'

    TITLE          = 'タイトル'
    LEVEL          = '対応レベル'
    DATE           = '日付'
    AREA           = '領域'
    ROOT_CATEGORY  = '真因カテゴリ'
    ROOT_SUMMARY   = '真因（要約）'
    STATUS         = 'ステータス'
    RELATED_FILES  = '関連ファイル'
    WHY_1          = 'なぜ(1回目)'
    WHY_2          = 'なぜ(2回目)'
    WHY_3          = 'なぜ(3回目)'
    COUNTERMEASURE = '真の原因に対する対策'


# ============================================================
# CRM DB — 顧客管理
# ============================================================
class CrmDB:
    """顧客管理DB（NOTION_CRM_DB_ID）のプロパティ名定数"""

    ENV_KEY = 'NOTION_CRM_DB_ID'

    COMPANY_NAME      = '会社名 / 屋号'
    CONTACT_NAME      = '担当者名'
    PHONE             = '電話番号'
    EMAIL             = 'メールアドレス'
    STATUS            = 'ステータス'
    BUSINESS_TYPE     = '事業種別'
    ASSIGNEE          = '担当'
    LAST_CONTACT_DATE = '最終連絡日'
    SOURCE            = '流入元'
    MEMO              = 'メモ'
    IDENTIFIER        = '識別記号'
    DISCOUNT_RATE     = '協力値引率'
    TRADE_HISTORY     = '取引履歴'


# ============================================================
# LEDGER DB — 台帳（見積・請求・領収書）
# ============================================================
class LedgerDB:
    """台帳DB（NOTION_LEDGER_DB_ID）のプロパティ名定数"""

    ENV_KEY = 'NOTION_LEDGER_DB_ID'

    MANAGEMENT_NO  = '管理番号'
    STATUS         = 'ステータス'
    TYPE           = '種別'
    ISSUE_DATE     = '発行日'
    SUBJECT        = '件名'
    DELIVERY_DATE  = '納品日'
    PAYMENT_STATUS = '入金状況'
    PAYMENT_DATE   = '入金日'
    AMOUNT         = '金額（税込）'
    MEMO           = 'メモ'
    CUSTOMER       = '顧客'
    COLLECTOR      = '回収者'


# ============================================================
# MINUTES DB — 議事録
# ============================================================
class MinutesDB:
    """議事録DB（NOTION_MINUTES_DB_ID）のプロパティ名定数"""

    ENV_KEY = 'NOTION_MINUTES_DB_ID'

    TITLE        = 'タイトル'
    DATE         = '日付'
    PROJECT      = '案件'
    PROJECT_NAME = '案件名'
    CUSTOMER     = '顧客名'
    DECISIONS    = '決定事項'
    TODO         = '宿題・TODO'
    MEMO         = 'メモ'


# ============================================================
# PROJECTS DB — プロジェクト管理
# ============================================================
class ProjectsDB:
    """プロジェクト管理DB（NOTION_PROJECTS_DB_ID）のプロパティ名定数"""

    ENV_KEY = 'NOTION_PROJECTS_DB_ID'

    TITLE      = 'プロジェクト名'
    STATUS     = 'ステータス'
    PHASE      = 'フェーズ'
    START_DATE = '開始日'
    GOAL_DATE  = '目標完了日'
    KPI        = 'KPI'
    ASSIGNEE   = '担当'
    MEMO       = 'メモ'


# ============================================================
# RADAR DB — Claude Code レーダー
# ============================================================
class RadarDB:
    """Claude Code レーダーDB（NOTION_RADAR_DB_ID）のプロパティ名定数"""

    ENV_KEY = 'NOTION_RADAR_DB_ID'

    TITLE          = 'タイトル'
    POST_DATE      = '投稿日時'
    CATEGORY       = 'カテゴリ'
    SUMMARY        = '要約'
    LINK           = 'リンク'
    SEQ_NO         = '通し番号'
    SOURCE         = '情報源'
    RIKU_VERIFIED  = 'リク検証'
    KANATA_VERDICT = 'カナタ判定'
    KANATA_REASON  = 'カナタ判定理由'
    RECOMMEND      = 'おすすめ度'
    STATUS         = '実施可否'


# ============================================================
# SNS DB — SNS投稿管理
# ============================================================
class SnsDB:
    """SNS投稿管理DB（NOTION_SNS_DB_ID）のプロパティ名定数"""

    ENV_KEY = 'NOTION_SNS_DB_ID'

    TITLE           = '投稿タイトル'
    SCHEDULED_DATE  = '投稿予定日'
    PLATFORM        = 'プラットフォーム'
    STATUS          = 'ステータス'
    DRAFT_CONTENT   = '投稿内容案'
    CONTENT         = '投稿内容'
    LIKES           = 'いいね数'
    IMPRESSIONS     = 'インプレッション数'
    RETWEETS        = 'RT数'
    ENGAGEMENT_RATE = 'ER'


# ============================================================
# 新DB追加テンプレート（コメントアウト済み）
# ============================================================
# class NewDB:
#     """新しいDB名（NOTION_NEW_DB_ID）のプロパティ名定数"""
#
#     ENV_KEY = 'NOTION_NEW_DB_ID'
#
#     TITLE  = 'タイトル'
#     STATUS = 'ステータス'
#     MEMO   = 'メモ'
#
# ※追加後は tests/test_notion_schema.py の DB_DEFINITIONS にも追記すること


# ============================================================
# 全DBクラスのレジストリ
# ============================================================
ALL_DB_CLASSES = [
    TasksDB,
    KaizenDB,
    CrmDB,
    LedgerDB,
    MinutesDB,
    ProjectsDB,
    RadarDB,
    SnsDB,
]


def get_db_class_by_env_key(env_key: str):
    """環境変数名から対応するDBクラスを返す。見つからなければ None"""
    for cls in ALL_DB_CLASSES:
        if cls.ENV_KEY == env_key:
            return cls
    return None
