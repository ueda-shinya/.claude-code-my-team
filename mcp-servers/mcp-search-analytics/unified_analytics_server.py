#!/usr/bin/env python3
"""
Unified Google Analytics MCP Server
サポートサイトは .env の ANALYTICS_SITES 変数で動的に管理する
"""

import json
import asyncio
import os
import re
import sys
from datetime import datetime, timedelta
from typing import Any, Dict, List, Optional

from mcp.server import Server
from mcp.server.stdio import stdio_server
from mcp.types import (
    Resource,
    Tool,
    TextContent,
)

from google.oauth2 import service_account
from googleapiclient.discovery import build
from googleapiclient.errors import HttpError
from google.analytics.data_v1beta import BetaAnalyticsDataClient
from google.analytics.data_v1beta.types import (
    RunReportRequest,
    Dimension,
    Metric,
    DateRange,
)

try:
    from dotenv import load_dotenv
except ImportError:
    print("Warning: python-dotenv not installed. Run: pip install python-dotenv", file=sys.stderr)
    def load_dotenv(**kwargs):
        pass


# ---------------------------------------------------------------------------
# 後方互換フォールバックマッピング
# キー(サイト名) → (GSC用フォールバック環境変数名, GA4用フォールバック環境変数名)
# プレフィックスなしの既存 .env 変数を新規サイトキー規約へ橋渡しする
# - officeueda: 旧来の GSC_SITE_URL / GA4_PROPERTY_ID を引き続き使用可能
# - ussaijo:    MEBELCENTER_GSC_URL / MEBELCENTER_GA4_PROPERTY_ID が旧来のキー名
#              （MEBELCENTER = ussaijo のプロジェクト別名称）
# ---------------------------------------------------------------------------
_FALLBACK_ENV: Dict[str, Dict[str, str]] = {
    'officeueda': {
        'gsc_url':        'GSC_SITE_URL',
        'ga4_property_id': 'GA4_PROPERTY_ID',
    },
    'ussaijo': {
        'gsc_url':        'MEBELCENTER_GSC_URL',
        'ga4_property_id': 'MEBELCENTER_GA4_PROPERTY_ID',
    },
}


def _load_sites_from_env() -> Dict[str, Dict[str, Optional[str]]]:
    """
    ANALYTICS_SITES 環境変数からサイト一覧を読み込み、
    各サイトの gsc_url / ga4_property_id を解決して返す。

    命名規約: <SITE_KEY_UPPER>_GSC_URL / <SITE_KEY_UPPER>_GA4_PROPERTY_ID
    例: site3 → SITE3_GSC_URL / SITE3_GA4_PROPERTY_ID

    既存サイト (officeueda / ussaijo) はフォールバック変数名も参照するため
    既存の .env を変更しなくても動作を継続できる。
    """
    sites_raw = os.environ.get('ANALYTICS_SITES', '')
    if not sites_raw.strip():
        # ANALYTICS_SITES 未設定時は後方互換のためデフォルト2サイトを使用
        site_keys = ['officeueda', 'ussaijo']
    else:
        seen = set()
        site_keys = []
        for s in sites_raw.split(','):
            k = s.strip()
            if not k:
                continue
            if not re.fullmatch(r'[A-Za-z_][A-Za-z0-9_]*', k):
                print(
                    f'[ERROR] ANALYTICS_SITES に不正なキー: {k!r}'
                    ' (英数字とアンダースコアのみ、先頭は英字/_)',
                    file=sys.stderr,
                )
                sys.exit(1)
            if k in seen:
                continue  # 重複は無視
            seen.add(k)
            site_keys.append(k)

    sites: Dict[str, Dict[str, Optional[str]]] = {}
    for key in site_keys:
        prefix = key.upper()
        # 1. 新規規約: <KEY_UPPER>_GSC_URL / <KEY_UPPER>_GA4_PROPERTY_ID
        gsc_url = os.environ.get(f'{prefix}_GSC_URL')
        ga4_id = os.environ.get(f'{prefix}_GA4_PROPERTY_ID')

        # 2. フォールバック: 旧来の環境変数名があればそちらを使用
        if not gsc_url and key in _FALLBACK_ENV:
            gsc_url = os.environ.get(_FALLBACK_ENV[key]['gsc_url'])
        if not ga4_id and key in _FALLBACK_ENV:
            ga4_id = os.environ.get(_FALLBACK_ENV[key]['ga4_property_id'])

        sites[key] = {
            'gsc_url': gsc_url,
            'ga4_property_id': ga4_id,
        }

    return sites


def _validate_site_env(sites: Dict[str, Dict[str, Optional[str]]]) -> None:
    """
    宣言された全サイトの環境変数が揃っているか検証する。
    欠落がある場合はどのサイトのどの変数が不足しているかを表示して sys.exit(1)。
    """
    missing: List[str] = []
    for key, cfg in sites.items():
        prefix = key.upper()
        if not cfg['gsc_url']:
            # どの変数名を期待しているかを表示（フォールバック含む）
            expected = f'{prefix}_GSC_URL'
            if key in _FALLBACK_ENV:
                expected += f' (または {_FALLBACK_ENV[key]["gsc_url"]})'
            missing.append(f'[{key}] {expected}')
        if not cfg['ga4_property_id']:
            expected = f'{prefix}_GA4_PROPERTY_ID'
            if key in _FALLBACK_ENV:
                expected += f' (または {_FALLBACK_ENV[key]["ga4_property_id"]})'
            missing.append(f'[{key}] {expected}')

    if missing:
        print('[ERROR] 以下の環境変数が未設定です:', file=sys.stderr)
        for item in missing:
            print(f'  - {item}', file=sys.stderr)
        sys.exit(1)


class UnifiedAnalyticsMCPServer:
    def __init__(self, credentials_path: str):
        """
        Initialize the Unified Analytics MCP Server

        Args:
            credentials_path: Path to Google service account JSON file
        """
        self.credentials_path = credentials_path

        # サイト一覧を環境変数から動的ロード
        self.sites = _load_sites_from_env()

        # 全サイトの環境変数が揃っているか検証（欠落時は sys.exit(1)）
        _validate_site_env(self.sites)

        self.gsc_service = None
        self.ga4_client = None
        self.server = Server('unified-analytics-mcp-server')

        # Setup MCP handlers
        self._setup_handlers()

    def _site_enum(self) -> List[str]:
        """Tool inputSchema の enum 用にサイトキー一覧を返す"""
        return list(self.sites.keys())

    def _default_site(self) -> str:
        """
        デフォルトサイトキーを返す。

        契約: ANALYTICS_SITES 環境変数の先頭に書かれたサイトがデフォルトになる。
        後方互換のため、ANALYTICS_SITES 未設定時は 'officeueda' が先頭になる。
        site パラメータが未指定のMCP呼び出しではこのサイトが使われる。
        """
        return self._site_enum()[0]

    def _site_field_schema(self) -> Dict[str, Any]:
        """全 Tool 共通の site フィールド定義を動的生成して返す"""
        keys = self._site_enum()
        return {
            'type': 'string',
            'enum': keys,
            'description': f'分析対象サイト: {" / ".join(keys)}',
            'default': keys[0],
        }

    def _setup_handlers(self):
        """Setup MCP protocol handlers"""

        @self.server.list_tools()
        async def handle_list_tools() -> List[Tool]:
            """List available analytics tools"""
            site_field = self._site_field_schema()
            return [
                # GSC Tools
                Tool(
                    name='gsc_search_analytics',
                    description='Get Google Search Console search analytics data',
                    inputSchema={
                        'type': 'object',
                        'properties': {
                            'site': site_field,
                            'start_date': {'type': 'string', 'description': 'Start date in YYYY-MM-DD format'},
                            'end_date': {'type': 'string', 'description': 'End date in YYYY-MM-DD format'},
                            'dimensions': {
                                'type': 'array',
                                'items': {'type': 'string'},
                                'description': 'Dimensions to group by (query, page, country, device, searchAppearance)'
                            },
                            'row_limit': {'type': 'integer', 'description': 'Maximum rows (default 1000)', 'default': 1000}
                        },
                        'required': ['start_date', 'end_date']
                    }
                ),
                Tool(
                    name='gsc_top_queries',
                    description='Get top performing search queries from GSC',
                    inputSchema={
                        'type': 'object',
                        'properties': {
                            'site': site_field,
                            'start_date': {'type': 'string', 'description': 'Start date in YYYY-MM-DD format'},
                            'end_date': {'type': 'string', 'description': 'End date in YYYY-MM-DD format'},
                            'limit': {'type': 'integer', 'description': 'Number of queries (default 50)', 'default': 50}
                        },
                        'required': ['start_date', 'end_date']
                    }
                ),

                # GA4 Tools
                Tool(
                    name='ga4_traffic_overview',
                    description='Get GA4 traffic overview with key metrics',
                    inputSchema={
                        'type': 'object',
                        'properties': {
                            'site': site_field,
                            'start_date': {'type': 'string', 'description': 'Start date in YYYY-MM-DD format'},
                            'end_date': {'type': 'string', 'description': 'End date in YYYY-MM-DD format'}
                        },
                        'required': ['start_date', 'end_date']
                    }
                ),
                Tool(
                    name='ga4_top_pages',
                    description='Get top performing pages from GA4',
                    inputSchema={
                        'type': 'object',
                        'properties': {
                            'site': site_field,
                            'start_date': {'type': 'string', 'description': 'Start date in YYYY-MM-DD format'},
                            'end_date': {'type': 'string', 'description': 'End date in YYYY-MM-DD format'},
                            'metric': {
                                'type': 'string',
                                'enum': ['sessions', 'screenPageViews', 'totalUsers'],
                                'description': 'Metric to sort by',
                                'default': 'screenPageViews'
                            },
                            'limit': {'type': 'integer', 'description': 'Number of pages', 'default': 20}
                        },
                        'required': ['start_date', 'end_date']
                    }
                ),
                Tool(
                    name='ga4_acquisition_report',
                    description='Get GA4 traffic acquisition data by source/medium',
                    inputSchema={
                        'type': 'object',
                        'properties': {
                            'site': site_field,
                            'start_date': {'type': 'string', 'description': 'Start date in YYYY-MM-DD format'},
                            'end_date': {'type': 'string', 'description': 'End date in YYYY-MM-DD format'},
                            'limit': {'type': 'integer', 'description': 'Number of sources', 'default': 25}
                        },
                        'required': ['start_date', 'end_date']
                    }
                ),

                # Combined Analysis Tools
                Tool(
                    name='combined_performance_report',
                    description='Combined GSC + GA4 performance analysis for a date range',
                    inputSchema={
                        'type': 'object',
                        'properties': {
                            'site': site_field,
                            'start_date': {'type': 'string', 'description': 'Start date in YYYY-MM-DD format'},
                            'end_date': {'type': 'string', 'description': 'End date in YYYY-MM-DD format'}
                        },
                        'required': ['start_date', 'end_date']
                    }
                ),
                Tool(
                    name='page_analysis',
                    description='Analyze specific page performance across GSC and GA4',
                    inputSchema={
                        'type': 'object',
                        'properties': {
                            'site': site_field,
                            'page_path': {'type': 'string', 'description': "Page path to analyze (e.g., '/blog/article')"},
                            'start_date': {'type': 'string', 'description': 'Start date in YYYY-MM-DD format'},
                            'end_date': {'type': 'string', 'description': 'End date in YYYY-MM-DD format'}
                        },
                        'required': ['page_path', 'start_date', 'end_date']
                    }
                )
            ]

        @self.server.call_tool()
        async def handle_call_tool(name: str, arguments: Dict[str, Any]) -> List[TextContent]:
            """Handle tool calls"""

            await self._ensure_services_initialized()

            try:
                if name == 'gsc_search_analytics':
                    result = await self._gsc_search_analytics(**arguments)
                elif name == 'gsc_top_queries':
                    result = await self._gsc_top_queries(**arguments)
                elif name == 'ga4_traffic_overview':
                    result = await self._ga4_traffic_overview(**arguments)
                elif name == 'ga4_top_pages':
                    result = await self._ga4_top_pages(**arguments)
                elif name == 'ga4_acquisition_report':
                    result = await self._ga4_acquisition_report(**arguments)
                elif name == 'combined_performance_report':
                    result = await self._combined_performance_report(**arguments)
                elif name == 'page_analysis':
                    result = await self._page_analysis(**arguments)
                else:
                    raise ValueError(f'Unknown tool: {name}')

                return [TextContent(type='text', text=json.dumps(result, indent=2))]

            except Exception as e:
                # 詳細エラー（内部パス等）はstderrにのみ出力し、ユーザーには汎用メッセージを返す
                print(f'[ERROR] Error in {name}: {str(e)}', file=sys.stderr)
                return [TextContent(type='text', text=f"ツール '{name}' の実行中にエラーが発生しました。詳細はサーバーログを確認してください。")]

        @self.server.list_resources()
        async def handle_list_resources() -> List[Resource]:
            """List available analytics resources"""
            resources = []
            for site in self._site_enum():
                for period, description in [
                    ('today', "Today's Analytics Dashboard"),
                    ('yesterday', "Yesterday's Analytics Dashboard"),
                    ('week', 'Weekly Analytics Dashboard'),
                    ('month', 'Monthly Analytics Dashboard')
                ]:
                    resources.append(
                        Resource(
                            uri=f'analytics://dashboard/{period}/{site}',
                            name=f'{description} - {site.title()}',
                            description=f'Complete analytics overview for {site} - {period}',
                            mimeType='application/json'
                        )
                    )
            return resources

        @self.server.read_resource()
        async def handle_read_resource(uri: str) -> str:
            """Read analytics resources"""

            await self._ensure_services_initialized()

            try:
                today = datetime.now().date()

                # Parse URI: analytics://dashboard/period/site
                parts = uri.split('/')
                if len(parts) < 5:
                    raise ValueError(f'Invalid resource URI format: {uri}')

                period = parts[3]
                site = parts[4]
                self._validate_site(site)

                # Calculate date range
                if period == 'today':
                    start_date = end_date = today.isoformat()
                elif period == 'yesterday':
                    yesterday = today - timedelta(days=1)
                    start_date = end_date = yesterday.isoformat()
                elif period == 'week':
                    start_date = (today - timedelta(days=7)).isoformat()
                    end_date = today.isoformat()
                elif period == 'month':
                    start_date = (today - timedelta(days=30)).isoformat()
                    end_date = today.isoformat()
                else:
                    raise ValueError(f'Unknown period: {period}')

                result = await self._combined_performance_report(
                    site=site,
                    start_date=start_date,
                    end_date=end_date
                )

                return json.dumps(result, indent=2)

            except Exception as e:
                # 詳細エラー（内部パス等）はstderrにのみ出力し、ユーザーには汎用メッセージを返す
                print(f'[ERROR] Error reading resource {uri}: {str(e)}', file=sys.stderr)
                return json.dumps({'error': 'リソースの読み込みに失敗しました。詳細はサーバーログを確認してください。'})

    async def _ensure_services_initialized(self):
        """Ensure both GSC and GA4 services are initialized"""
        if not self.gsc_service or not self.ga4_client:
            await self._initialize_services()

    async def _initialize_services(self):
        """Initialize both GSC and GA4 services with shared credentials"""
        try:
            print(f'[INFO] Initializing services with credentials from: {self.credentials_path}', file=sys.stderr)

            # Load credentials with both scopes
            credentials = service_account.Credentials.from_service_account_file(
                self.credentials_path,
                scopes=[
                    'https://www.googleapis.com/auth/webmasters.readonly',
                    'https://www.googleapis.com/auth/analytics.readonly'
                ]
            )

            # Initialize GSC service
            self.gsc_service = build('searchconsole', 'v1', credentials=credentials)
            print('[SUCCESS] GSC service initialized', file=sys.stderr)

            # Initialize GA4 client
            self.ga4_client = BetaAnalyticsDataClient(credentials=credentials)
            print('[SUCCESS] GA4 client initialized', file=sys.stderr)

        except Exception as e:
            error_msg = f'Failed to initialize services: {str(e)}'
            print(f'[ERROR] {error_msg}', file=sys.stderr)
            raise Exception(error_msg)

    def _validate_site(self, site: str) -> None:
        """siteパラメータの入力検証。不正な値の場合はValueErrorを送出する"""
        if site not in self.sites:
            raise ValueError(f'Unknown site: {site}. Valid sites: {self._site_enum()}')

    async def _gsc_search_analytics(self, start_date: str, end_date: str,
                                    site: Optional[str] = None,
                                    dimensions: Optional[List[str]] = None,
                                    row_limit: int = 1000) -> Dict[str, Any]:
        """Get GSC search analytics data"""

        if site is None:
            site = self._default_site()

        # siteパラメータの入力検証
        self._validate_site(site)
        # サイト固有のURLを取得
        site_url = self.sites[site]['gsc_url']

        request_body = {
            'startDate': start_date,
            'endDate': end_date,
            'rowLimit': row_limit
        }

        if dimensions:
            request_body['dimensions'] = dimensions

        try:
            request = self.gsc_service.searchanalytics().query(
                siteUrl=site_url,
                body=request_body
            )
            response = request.execute()

            return {
                'source': 'Google Search Console',
                'site': site,
                'site_url': site_url,
                'date_range': f'{start_date} to {end_date}',
                'total_rows': len(response.get('rows', [])),
                'dimensions': dimensions or [],
                'data': response.get('rows', [])
            }

        except HttpError as e:
            raise RuntimeError('GSC API error') from e

    async def _gsc_top_queries(self, start_date: str, end_date: str,
                               site: Optional[str] = None, limit: int = 50) -> Dict[str, Any]:
        """Get top queries from GSC"""
        if site is None:
            site = self._default_site()
        return await self._gsc_search_analytics(
            start_date=start_date,
            end_date=end_date,
            site=site,
            dimensions=['query'],
            row_limit=limit
        )

    async def _ga4_run_report(self, dimensions: List[str], metrics: List[str],
                              start_date: str, end_date: str,
                              site: Optional[str] = None, limit: int = 100) -> Dict[str, Any]:
        """Run a GA4 report"""

        if site is None:
            site = self._default_site()

        # siteパラメータの入力検証
        self._validate_site(site)
        # サイト固有のプロパティIDを取得
        property_id = self.sites[site]['ga4_property_id']

        try:
            request = RunReportRequest(
                property=f'properties/{property_id}',
                dimensions=[Dimension(name=dim) for dim in dimensions],
                metrics=[Metric(name=metric) for metric in metrics],
                date_ranges=[DateRange(start_date=start_date, end_date=end_date)],
                limit=limit
            )

            response = self.ga4_client.run_report(request=request)

            rows = []
            for row in response.rows:
                row_data = {}

                for i, dim_value in enumerate(row.dimension_values):
                    dim_name = dimensions[i] if i < len(dimensions) else f'dimension_{i}'
                    row_data[dim_name] = dim_value.value

                for i, metric_value in enumerate(row.metric_values):
                    metric_name = metrics[i] if i < len(metrics) else f'metric_{i}'
                    row_data[metric_name] = metric_value.value

                rows.append(row_data)

            return {
                'source': 'Google Analytics 4',
                'site': site,
                'property_id': property_id,
                'date_range': f'{start_date} to {end_date}',
                'dimensions': dimensions,
                'metrics': metrics,
                'row_count': len(rows),
                'data': rows
            }
        except Exception as e:
            raise RuntimeError('GA4 API error') from e

    async def _ga4_traffic_overview(self, start_date: str, end_date: str,
                                    site: Optional[str] = None) -> Dict[str, Any]:
        """Get GA4 traffic overview"""
        if site is None:
            site = self._default_site()
        metrics = [
            'sessions', 'totalUsers', 'newUsers', 'screenPageViews',
            'bounceRate', 'averageSessionDuration', 'sessionsPerUser'
        ]

        result = await self._ga4_run_report([], metrics, start_date, end_date, site, 1)

        if result['data']:
            overview = result['data'][0]
            result['overview'] = overview
            del result['data']

        return result

    async def _ga4_top_pages(self, start_date: str, end_date: str,
                             site: Optional[str] = None,
                             metric: str = 'screenPageViews', limit: int = 20) -> Dict[str, Any]:
        """Get top pages from GA4"""
        if site is None:
            site = self._default_site()
        dimensions = ['pagePath', 'pageTitle']
        metrics = [metric, 'sessions', 'totalUsers', 'bounceRate']

        return await self._ga4_run_report(dimensions, metrics, start_date, end_date, site, limit)

    async def _ga4_acquisition_report(self, start_date: str, end_date: str,
                                      site: Optional[str] = None, limit: int = 25) -> Dict[str, Any]:
        """Get GA4 acquisition data"""
        if site is None:
            site = self._default_site()
        dimensions = ['sessionSource', 'sessionMedium']
        metrics = ['sessions', 'totalUsers', 'newUsers', 'bounceRate', 'averageSessionDuration']

        return await self._ga4_run_report(dimensions, metrics, start_date, end_date, site, limit)

    async def _combined_performance_report(self, start_date: str, end_date: str,
                                           site: Optional[str] = None) -> Dict[str, Any]:
        """Generate combined GSC + GA4 performance report"""
        if site is None:
            site = self._default_site()

        # Get GSC data
        gsc_data = await self._gsc_search_analytics(
            start_date=start_date,
            end_date=end_date,
            site=site,
            dimensions=['query'],
            row_limit=20
        )

        # Get GA4 overview
        ga4_overview = await self._ga4_traffic_overview(start_date, end_date, site)

        # Get top pages from GA4
        ga4_pages = await self._ga4_top_pages(start_date, end_date, site, limit=10)

        # Get acquisition data
        ga4_acquisition = await self._ga4_acquisition_report(start_date, end_date, site, limit=10)

        return {
            'report_type': 'Combined Performance Report',
            'site': site,
            'date_range': f'{start_date} to {end_date}',
            'search_console': {
                'top_queries': gsc_data.get('data', [])[:10]
            },
            'google_analytics': {
                'overview': ga4_overview.get('overview', {}),
                'top_pages': ga4_pages.get('data', [])[:5],
                'top_sources': ga4_acquisition.get('data', [])[:5]
            }
        }

    async def _page_analysis(self, page_path: str, start_date: str, end_date: str,
                             site: Optional[str] = None) -> Dict[str, Any]:
        """Analyze specific page across both GSC and GA4"""
        if site is None:
            site = self._default_site()

        # GSC data for specific page
        gsc_page_data = await self._gsc_search_analytics(
            start_date=start_date,
            end_date=end_date,
            site=site,
            dimensions=['page', 'query'],
            row_limit=100
        )

        # Filter for the specific page
        page_queries = [
            row for row in gsc_page_data.get('data', [])
            if len(row.get('keys', [])) > 0 and row.get('keys', [''])[0] == page_path
        ]

        # GA4 data for specific page
        ga4_page_data = await self._ga4_run_report(
            dimensions=['pagePath'],
            metrics=['screenPageViews', 'sessions', 'totalUsers', 'bounceRate', 'averageSessionDuration'],
            start_date=start_date,
            end_date=end_date,
            site=site,
            limit=1000
        )

        # Filter for the specific page
        page_analytics = [
            row for row in ga4_page_data.get('data', [])
            if row.get('pagePath') == page_path
        ]

        return {
            'site': site,
            'page_path': page_path,
            'date_range': f'{start_date} to {end_date}',
            'search_console': {
                'queries_count': len(page_queries),
                'top_queries': page_queries[:10]
            },
            'google_analytics': {
                'page_data': page_analytics[0] if page_analytics else None
            }
        }


async def main():
    """Main server entry point"""

    print('[START] Starting Unified Analytics MCP Server...', file=sys.stderr)

    # Load environment variables from .env file
    load_dotenv()
    print('[SUCCESS] Environment variables loaded', file=sys.stderr)

    # Configuration - get from environment variables
    credentials_path = os.environ.get('ANALYTICS_CREDENTIALS_PATH')

    print(f'[INFO] Credentials path: {credentials_path}', file=sys.stderr)

    if not credentials_path:
        print('[ERROR] Error: ANALYTICS_CREDENTIALS_PATH environment variable is required', file=sys.stderr)
        sys.exit(1)

    if not os.path.exists(credentials_path):
        print(f'[ERROR] Error: Credentials file not found at {credentials_path}', file=sys.stderr)
        sys.exit(1)

    print('[SUCCESS] All configuration checks passed', file=sys.stderr)

    # Test credentials quickly
    try:
        credentials = service_account.Credentials.from_service_account_file(
            credentials_path,
            scopes=[
                'https://www.googleapis.com/auth/webmasters.readonly',
                'https://www.googleapis.com/auth/analytics.readonly'
            ]
        )
        print('[SUCCESS] Credentials loaded successfully', file=sys.stderr)
    except Exception as e:
        print(f'[ERROR] Error loading credentials: {e}', file=sys.stderr)
        sys.exit(1)

    # Create and run server
    print('[INFO] Creating analytics server...', file=sys.stderr)
    try:
        analytics_server = UnifiedAnalyticsMCPServer(credentials_path)
        configured_sites = analytics_server._site_enum()
        print('[SUCCESS] Analytics server created successfully', file=sys.stderr)
        print(f'[INFO] Configured sites: {", ".join(configured_sites)}', file=sys.stderr)
    except Exception as e:
        print(f'[ERROR] Error creating server: {e}', file=sys.stderr)
        sys.exit(1)

    # Run the server with proper MCP protocol
    print('[INFO] Starting MCP stdio server...', file=sys.stderr)
    try:
        async with stdio_server() as (read_stream, write_stream):
            print('[SUCCESS] MCP server is running and waiting for connections...', file=sys.stderr)
            print('[INFO] Server ready to receive requests from Claude Desktop', file=sys.stderr)

            # Run the server using the correct MCP pattern
            await analytics_server.server.run(
                read_stream,
                write_stream,
                analytics_server.server.create_initialization_options()
            )

    except KeyboardInterrupt:
        print('[INFO] Server stopped by user', file=sys.stderr)
    except Exception as e:
        print(f'[ERROR] Error running server: {e}', file=sys.stderr)
        print(f'[ERROR] Error type: {type(e).__name__}', file=sys.stderr)
        import traceback
        traceback.print_exc()
        sys.exit(1)


if __name__ == '__main__':
    try:
        asyncio.run(main())
    except Exception as e:
        print(f'[FATAL] Fatal error: {e}', file=sys.stderr)
        import traceback
        traceback.print_exc()
        sys.exit(1)
