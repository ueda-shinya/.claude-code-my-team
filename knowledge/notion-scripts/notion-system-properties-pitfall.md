# Notion システムプロパティ参照の落とし穴

## 概要
Notion API の `page['properties']` には、`created_time` / `last_edited_time` などの**システムプロパティは自動で含まれない**。
DB のプロパティとして明示的に追加していない限り、これらはページの**トップレベル**にのみ存在する。

## 誤った実装（2026-04-21 発覚・修正済）
```python
# ❌ これは常に空文字を返す
props.get('作成日時', {}).get('created_time', '')
props.get('最終編集日時', {}).get('last_edited_time', '')
```

## 正しい実装
```python
# ✅ トップレベルから取得
page.get('created_time', '')         # "2026-04-21T10:00:00.000Z" 形式
page.get('last_edited_time', '')
```

## 影響範囲
- ヘルパー関数の引数を `props`（= `page['properties']`）ではなく **`page` 全体**に変更
- 関連する `page_to_item()` 等の呼び出し側も `page` を渡すよう修正

## ISO8601 パース
`datetime.fromisoformat(value.replace('Z', '+00:00'))` で Python 3.10 互換パース可能。
Python 3.11+ なら `'Z'` を直接受け付けるが、環境互換のため `replace` を保持推奨。

## 実害事例（2026-04-21）
- `notion-tasks.py --alerts` が `最終編集日時` を props 経由で取得しており常に空文字
- `if not last_edited_str: continue` で全件スキップされ、**経過日数アラートが完全に機能していなかった**
- 修正後、14件のアラートが正しく出力された

## 参考コミット
- `0dc13c0` kaizen Phase 1
- 発覚経路: kaizen Phase 0 R3（最高リスク層5ファイルの実DBスキーマ突合監査）
