---
name: claude-design-prompt
description: Claude Design プロダクト本体（claude.ai/design）に貼り付ける入力テキストを生成するスキル。コピペ可能なプロンプト文字列を出力する。「Claude Design用に〇〇のプロンプト作って」「claude.ai/design のプロンプト欲しい」「Claude Designのスライドプロンプト組み立てて」など、**「Claude Design」または「claude.ai/design」と「プロンプト」の両方が明示された依頼**でのみ起動する。Claude Code 内でUI実装するスキル（frontend-design）や LP制作フロー全体（lp-create）とは別物。
---

# Claude Design プロンプト構築スキル

## 起動条件（厳格・両方必須）

以下の **2条件すべて** を満たすときのみ起動する。OR ではなく AND 判定。

- **条件1（必須）**: 依頼文に「Claude Design」または「claude.ai/design」が明示されている
- **条件2（必須）**: 依頼文に「プロンプト」または「prompt」が含まれる

どちらか1つだけでは起動しない。

### 起動しないケース（誤発動防止）

- 「LPを作って」「スライドを作って」のみ → `/lp-create` `/slide-create` の領分
- 「LPデザインして」「スライドデザインして」 → `lp-design-system` の領分
- 「frontend-design 使って実装して」「frontend-design でLP作って」 → `frontend-design` スキルの領分
- 「LPのプロンプト作って」「ダッシュボードのプロンプト欲しい」（"Claude Design" 言及なし）→ シンヤさんに用途確認してから決定（自動起動しない）
- 「Claude Designでアートワーク作って」「Claude Designでスライド作って」（"プロンプト"なし）→ 通常のClaude Design使い方相談として対応（本スキルは起動しない）
- 「画像生成して」「ロゴ作って」 → `nano-banana` の領分
- 「Claude Codeで実装して」 → 通常のコーディング委任（シュウ）

判断に迷う場合はシンヤさんに「Claude Design 本体に貼るプロンプトですか？それとも別の用途ですか？」と確認する。

## 参照ナレッジ（必読）

本スキルは `~/.claude/knowledge/claude-design-prompts/` を参照して動作する。**ナレッジ更新時はスキル本体は変更不要**。

- [00-anti-slop-block.md](../../knowledge/claude-design-prompts/00-anti-slop-block.md)
- [08-quality-checklist.md](../../knowledge/claude-design-prompts/08-quality-checklist.md)（送信前自己検証用）
- [01-base-template.md](../../knowledge/claude-design-prompts/01-base-template.md)
- [02-lp-hero.md](../../knowledge/claude-design-prompts/02-lp-hero.md)
- [03-slide-deck.md](../../knowledge/claude-design-prompts/03-slide-deck.md)
- [04-dashboard.md](../../knowledge/claude-design-prompts/04-dashboard.md)
- [05-mobile-app.md](../../knowledge/claude-design-prompts/05-mobile-app.md)
- [06-japanese-ui.md](../../knowledge/claude-design-prompts/06-japanese-ui.md)
- [07-design-md-recipes.md](../../knowledge/claude-design-prompts/07-design-md-recipes.md)
- [99-anti-patterns.md](../../knowledge/claude-design-prompts/99-anti-patterns.md)

## 実行フロー

### Step 1: ヒアリング

シンヤさんから不足情報を聞き取る。**既に依頼文で明確な情報は質問しない**。聞くのは下記7項目のうち**未確定のもののみ**。

1. **用途**（必須）: LP / スライド / ダッシュボード / モバイルアプリ / 単一セクション（ヒーロー・FAQ・価格表等） / その他
2. **製品・サービス名**（必須）: 何のためのデザインか
3. **対象ユーザー**（必須）: ペルソナ・職種・状況
4. **トーン**（必須）: 信頼感 / モダン / ボールド / エディトリアル / ミニマル 等の方向性
5. **言語**（必須）: 日本語UI / 英語UI / 両方
6. **ブランド指定**: カラー（hex）・フォント・既存DESIGN.mdの有無
7. **バリエーション要求**: 1案 / 2-3バリエーション

### Step 2: プロンプト組み立て

以下の順で連結する。

```
[Step 2-1] アンチスロップブロック（00-anti-slop-block.md より）
        ↓
[Step 2-2] 用途別テンプレート（02〜05 より該当ファイル選択）
        ↓
[Step 2-3] (日本語UIなら) Japanese typography ブロック（06-japanese-ui.md）
        ↓
[Step 2-4] (DESIGN.md指定があれば) リミックス指示（07-design-md-recipes.md）
        ↓
[Step 2-5] (バリエーション要求あれば) "Show me 2-3 alternative designs..."
```

### Step 3: 品質チェックリスト自己検証（必須）

組み立てた最終プロンプトを、シンヤさんに提示する**前**に [08-quality-checklist.md](../../knowledge/claude-design-prompts/08-quality-checklist.md) の **必須チェック全項目** を順に検証する。

- 検証はナレッジ08が **単一ソース（正典）**。SKILL.md には項目を再掲しない（二重管理回避）
- 該当条件付きセクション（日本語UI / モバイル / ブランド一貫性 / クォータ管理 等）は、ヒアリング結果に照らして該当時のみ検証
- 不合格項目があれば Step 2 に戻り補完。**チェックリスト不合格のまま Step 4 に進まない**
- 検証実施の証跡として、Step 5 の補足アドバイス末尾に `[自己検証] 必須N項目クリア / 該当条件付きM項目クリア` を必ず付与する

※ 本自己検証は Claude Design 入力プロンプトの品質確保用。コード成果物のサクラレビュー、マーケ成果物のレンレビュー等の **Deliverable Quality Gate を代替するものではない**。

### Step 4: 出力

シンヤさんに**コピペ可能なコードブロック**として最終プロンプトを提示する。

- 言語: 英語版を基本とする（Claude Design の出力精度が高いため）
- 日本語版併記: シンヤさんの依頼が日本語で来た場合は、両方提示
- 補足: 「このプロンプトを claude.ai/design に貼り付けてください」と明記

### Step 5: 補足アドバイス（証跡マーカーは必須・補足は任意）

**自己検証マーカーは必ず出力する**（Step 3 の証跡）。補足アドバイスは該当時のみ任意で追加。

以下のいずれかに該当するなら、プロンプト出力後に1〜2行で補足する。

- 添付ファイル併用が有効な場合: 「メッセージング文書／競合サイトURL／既存LPスクショを併せてアップロードすると精度が上がります」
- DESIGN.md活用が有効な場合: 「ブランド一貫性が必要なら DESIGN.md 化を推奨。`07-design-md-recipes.md` 参照」
- 苦手領域を回避: 「写真・ベクターイラストは別途 Midjourney/DALL-E 等で生成して取り込みを推奨」
- バリエーション要求した方が良い場合: 「初稿1案より、2-3案要求の方がトークン効率が良いです」

## 出力フォーマット

### 入力例
> 「Claude Design用に、SaaSのLPプロンプト作って。ターゲットはRevOpsリーダー、信頼感あるトーンで、日本語UI」

### 出力構造（アスカの返答に含めるもの）

1. **前置きの1文**: 「以下のプロンプトを claude.ai/design に貼り付けてください」
2. **最終プロンプト本文を単一のコードブロックで提示**。中身は以下を**実際に全文展開**して連結したもの（`[...省略]` 等の省略表記は使わない）:
   - **冒頭**: `00-anti-slop-block.md` の `<frontend_aesthetics>` ブロックを全文展開
   - **本文**: 用途別テンプレ（02〜05のいずれか該当ファイル）にヒアリング結果を変数置換した本文
   - **日本語UIなら**: `06-japanese-ui.md` の `Japanese typography requirements` ブロックを全文展開
   - **DESIGN.md指定があれば**: `07-design-md-recipes.md` のリミックス指示
   - **末尾（バリエーション要求あれば）**: `Show me 2-3 alternative designs with different positioning angles.`
3. **補足アドバイス（コードブロック外、通常テキスト）**: 該当する場合のみ1〜2行で
   - メッセージング文書や既存サイトのスクショ併用が有効な場合
   - DESIGN.md活用が有効な場合
   - 苦手領域回避（写真・イラストは別ツール）の注意
   - バリエーション要求の有効性

### 重要な制約

- アスカは出力時に **`[...省略]` 等の省略表記を使わない**。アンチスロップブロック等は必ず**全文展開**して提示する（シンヤさんが二度手間にならないため）
- 最終プロンプトは1つのコードブロックに収める（複数に分割しない）
- 補足はコードブロックの外に置く（コピペ対象外であることを明示）

## 既存スキルとの住み分け

| スキル | 役割 | 違い |
|---|---|---|
| **claude-design-prompt** (本スキル) | claude.ai/design 本体に貼るプロンプト生成 | プロダクト本体への入力テキスト作成 |
| `frontend-design` (Anthropic公式) | Claude Code 内でUIコード生成 | Claude Code 環境で動作 |
| `lp-create` | LP制作フロー全体（マーケ→デザイン→実装） | フローオーケストレーション |
| `lp-design-system` | LPセクション/スライドのビジュアルデザイン設計 | デザイン仕様書の作成 |
| `slide-create` | スライド原稿作成（Genspark/Gamma用） | スライド原稿の生成 |

**衝突しない設計**: 本スキルは「**Claude Design 本体への入力テキスト**」だけを生成する。Claude Code 内での実装、デザイン仕様書、フロー全体管理には踏み込まない。

## 注意

- 細部数値（DESIGN.md実例の値・コミュニティリポの主張等）は採用前に GitHub で原文確認推奨
- Claude Design は研究プレビュー段階のため、推奨プロンプトは2026-04-25時点のもの
- ナレッジ側 (`~/.claude/knowledge/claude-design-prompts/`) を更新すれば本スキル出力も自動的に最新化される
