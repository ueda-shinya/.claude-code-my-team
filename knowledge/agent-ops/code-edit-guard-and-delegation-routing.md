# SKILL.md / agents 編集時の委任ルーティング（物理ガード由来の経験則）

## 確認日: 2026-05-02

kaizen Phase 2-B 実装時に、カナタ（agent-builder）への SKILL.md 編集委任が `hooks/code-edit-guard.sh` 物理ガードでブロックされた事象から確立した委任ルーティングの経験則。

## 物理ガードの動作

`code-edit-guard.sh` は PreToolUse hook として Edit/Write を傍受し、以下のファイル拡張子＋コンテキストで動作する：

- `.py` `.js` `.ts` `.bat` `.sh` `.css` `.html` `.php` 等のコードファイル
- **`.md` 拡張子でも、内部に ` ```python ` / ` ```bash ` / ` ```sh ` / ` ```javascript ` / ` ```yaml ` 等のコードブロックを含むファイルへの編集**

サブエージェント識別が現状未実装のため、カナタ（agent-builder）として呼ばれていても hook は「Asuka が編集している」と判定してブロックする（exit 2）。

## 確立した委任ルーティング

| 編集対象 | 委任先 | 理由 |
|---|---|---|
| エージェント定義 `.ja.md` / `.md`（コードブロックなし・文章のみ） | カナタ（agent-builder） | 文書編集として物理ガード非該当 |
| エージェント定義 `.ja.md` / `.md`（コードブロックを含む） | **シュウ（backend-engineer）** | 物理ガードでカナタはブロック・CLAUDE.md L1095 規定 |
| SKILL.md（コードブロックを含む = 大半が該当） | **シュウ（backend-engineer）** | 同上 |
| SKILL.md（純テキストのみ・極めて稀） | カナタ（agent-builder） | 物理ガード非該当・適性高 |
| エージェント英訳同期（`.md` 側更新） | カナタ（agent-builder） | 翻訳作業が本職・コードブロックは原文の機械的コピー扱い |
| Python/Bash スクリプト（`.py` `.sh` 等） | シュウ（backend-engineer） | コーディング本職 |

## 判定の実務手順

1. アスカが編集対象ファイルを Read で開く
2. ファイル内に ` ``` ` ブロックがあるか目視確認
3. コードブロックあり → **シュウへ委任**
4. コードブロックなしの純テキスト .md → カナタへ委任可
5. 迷ったら**シュウへ寄せる**（過剰委任のコストは小さい・物理ガードで止まるコストは大きい）

## 委任失敗時のフォールバック

カナタに委任した結果「物理ガードでブロックされた」報告が返ってきた場合：
1. アスカは即座にシュウへ再委任する判断をする（カナタに hook 設定変更を試させない）
2. 依頼文に「Pre-Review Gate 通過済み（YYYY-MM-DD）」マーカーがあれば、シュウは再依頼として受託OK
3. 経緯を prereview-log.md または case メモに記録

## 関連ルール

- CLAUDE.md「Asuka Never Codes Directly」（アスカは編集しない）
- CLAUDE.md L1095「SKILL.md / agents/\*.md など `.md` 拡張子ファイルでも、内部のコードブロックを含む編集はシュウ委任」
- CLAUDE.md「Rina Pre-Review Gate」（依頼文先頭にマーカー必須）
- knowledge/claude-code-hooks/sub-agent-identification-challenge.md（サブエージェント識別の物理ガード課題）

## 改善候補（Notion P3 で登録済み）

- 物理ガードのサブエージェント識別機能追加（kaizen 候補）
- 識別が実装されればカナタが SKILL.md コードブロックを編集可能になる → 本ルーティングは将来的に簡素化可
