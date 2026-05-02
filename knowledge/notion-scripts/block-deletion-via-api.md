# Notion ブロック削除：API 直叩き手順

**作成日**: 2026-05-02
**背景**: gsc-ga4-analyzer 案件で、Notion 案件本文に追記された差し戻し対象ブロック（rev.2 / rev.2.1）を削除する必要があった。`notion-tasks.py` には追記機能のみで削除機能が存在しないため、API 直叩きで対応した。

## 結論

`notion-tasks.py` 等の自作スクリプトには現状ブロック削除機能がない。Notion ブロックを削除する場合は、以下のいずれかで対応する：

1. **Notion UI で手動削除**（シンヤさん作業）
2. **Notion API `DELETE /v1/blocks/{block_id}` を直叩き**（アスカが Bash + Python でセッション内完結）

## API 直叩きの手順

### ステップ1: 削除対象ページの子ブロック一覧を取得

```python
import os, json, urllib.request
from pathlib import Path

env_path = Path.home() / '.claude' / '.env'
env = {}
for line in env_path.read_text(encoding='utf-8').splitlines():
    if '=' in line and not line.lstrip().startswith('#'):
        k, _, v = line.partition('=')
        env[k.strip()] = v.strip().strip('"').strip("'")

token = env['NOTION_API_TOKEN']
page_id = '<対象ページのUUID>'

url = f'https://api.notion.com/v1/blocks/{page_id}/children?page_size=100'
req = urllib.request.Request(url, method='GET',
    headers={
        'Authorization': f'Bearer {token}',
        'Notion-Version': '2022-06-28',
    }
)
res = urllib.request.urlopen(req)
data = json.loads(res.read())
results = data.get('results', [])
for i, b in enumerate(results):
    btype = b['type']
    text = ''.join(t.get('plain_text','') for t in b.get(btype, {}).get('rich_text', []))
    print(f'[{i}] {b["id"]} ({btype}) {text[:80]}')
```

### ステップ2: 削除対象 block_id を特定して DELETE

```python
url = f'https://api.notion.com/v1/blocks/{block_id}'
req = urllib.request.Request(url, method='DELETE',
    headers={
        'Authorization': f'Bearer {token}',
        'Notion-Version': '2022-06-28',
    }
)
res = urllib.request.urlopen(req)
result = json.loads(res.read())
archived = result.get('archived') or result.get('in_trash')
print(f'archived={archived}')
```

## 重要な仕様

- **DELETE は完全消去ではなく archived（in_trash）化**：30日以内なら Notion UI から復元可能
- レスポンスの `archived` または `in_trash` フィールドで成功確認
- レート制限対策のため複数件削除時は `time.sleep(0.3)` を挟む

## 環境変数読み込みの注意

`.env` を `source` するシェル経由で `os.environ` にロードする方式は WSL/Git Bash で失敗する場合あり（パス区切り `/` がディレクトリと誤認される）。**Python 側で直接 `.env` をパースする方式が安全**：

```python
env_path = Path.home() / '.claude' / '.env'
env = {}
for line in env_path.read_text(encoding='utf-8').splitlines():
    if '=' in line and not line.lstrip().startswith('#'):
        k, _, v = line.partition('=')
        env[k.strip()] = v.strip().strip('"').strip("'")
```

## 利用ケース

- 案件本文に誤って追記されたブロックを削除したいとき
- 差し戻し対応で過去のリビジョンブロックを取り除きたいとき
- アスカが代行できる範囲（Notion UI 手動削除をシンヤさんに依頼せずに済む）

## 関連

- `notion-tasks.py --add-block`：追記専用、削除不可
- 案件本文の管理ガイドラインは `~/.claude/knowledge/notion-scripts/design-principles.md` を参照
