# Notion select option を rename する 3段階マイグレーションパターン

**対象**: Notion DB の select / multi_select プロパティで、既存オプション名を変更したいケース

## 問題

Notion API は **select option の rename を直接サポートしていない**。

### 失敗するパターン（やっても効かない）

```python
# DB PATCH で既存 id + 新しい name を指定 → 反映されない（API は 200 を返すが）
{
    'properties': {
        'クライアント': {
            'select': {
                'options': [
                    {'id': 'c1a907a5-...', 'name': '株式会社COMPASS', 'color': 'brown'},  # 元: 'コンパス'
                ]
            }
        }
    }
}
```

→ レスポンスは成功 (200 OK) を返すが、**実際には旧 name のまま**。

### notion-tasks.py の --update も同じ罠

```bash
notion-tasks.py --update '部分タイトル' --client '存在しない値'
# 「更新しました」と表示されるが、実際には変更されない
# 副作用: 指定しなかった他フィールド（優先度等）が勝手に P3-今月中 等のデフォルト値に書き換わる
```

→ kaizen 登録済（2026-04-25）。修正待ちの間は本パターンで回避する。

## 解決：3段階マイグレーション

### Step 1: 新オプションを追加（旧オプションは残す）

DB PATCH で options 配列に新値を追加。既存値はすべて含めたまま。

```python
payload = {
    'properties': {
        'クライアント': {
            'select': {
                'options': [
                    {'name': 'officeueda'},  # 既存
                    {'name': 'inada-ryota'},  # 既存
                    # ... 他の既存オプション全部 ...
                    {'name': 'コンパス'},  # 旧（一旦残す）
                    {'name': '株式会社COMPASS', 'color': 'brown'},  # 新規追加
                ]
            }
        }
    }
}
```

### Step 2: 各ページを新値に付け替え

旧値を持つページを query で取得し、page PATCH で新値に変更。

```python
# query
{'filter': {'property': 'クライアント', 'select': {'equals': 'コンパス'}}}

# page PATCH
{'properties': {'クライアント': {'select': {'name': '株式会社COMPASS'}}}}
```

### Step 3: 旧オプションを options 配列から除外

DB PATCH で options 配列から旧値を抜く。これで select の選択肢が消えてキレイになる。

```python
payload = {
    'properties': {
        'クライアント': {
            'select': {
                'options': [
                    {'name': 'officeueda'},
                    {'name': 'inada-ryota'},
                    # ... 他の既存オプション全部 ...
                    # ↓ 'コンパス' を含めない（これで選択肢から除外される）
                    {'name': '株式会社COMPASS'},
                ]
            }
        }
    }
}
code, res = api('PATCH', f'/databases/{db_id}', payload)
```

## 注意

- **Step 2 を完了する前に Step 3 に進むと、旧値を持つページの select が空になる**（破壊的）
- query で対象件数を確認してから付け替え、付け替え後に「旧値残: 0 件」を確認してから Step 3 へ進むこと
- **eventual consistency 対策**: Notion API は付け替え直後の query 結果に反映遅延が出ることがある。Step 2 → Step 3 の境界では、以下のいずれかで安全側に倒すこと：
  - 数秒〜十数秒待ってから再度 query で 0 件確認（最低 1 回再 query する）
  - または、Step 2 を「query で取得した全 page_id をループ付け替え」とし、付け替え対象を ID 直指定で完全消化してから query で念押し確認
- 関連 DB（議事録 DB の rich_text 顧客名など）にも同名の文字列が散らばっている場合は別途修正が必要（select 名寄せでは追従しない）

## 実例

- 2026-04-25: Notion 案件管理 DB の `クライアント` select で「コンパス」→「株式会社COMPASS」を実行
- 対象 5 ページを付け替え、旧オプション削除まで完了
- 同時に議事録 DB ページ（rich_text タイプ）の「顧客名」「タイトル」も page PATCH で個別更新
