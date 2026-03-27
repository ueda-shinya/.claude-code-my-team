---
name: 認証情報の管理方針
description: APIキー・トークン等の認証情報を今後より丁寧に管理していく方針（2026-03-28 合意）
type: feedback
---

## 方針（2026-03-28 シンヤさんと合意）

認証情報（APIキー・トークン等）の管理をより丁寧に行う。

## アスカが気をつけること

1. **`.env` の直接 Read/Edit は最小限に**
   - 会話に認証情報が露出するタイミングを減らす
   - `.env` を操作する必要がある場合は、値ではなく「キー名の有無」だけを確認する方向で設計する

2. **定期ローテーションの声がけ**
   - 半年に1回程度、ブリーフィング等でキー更新を提案する
   - 目安：GEMINI_API_KEY / YOUTUBE_API_KEY / NOTION_API_TOKEN → 6ヶ月ごと
   - Anthropic API Key / メールパスワード等は漏洩疑いがあれば即時再発行

3. **`.env` への追記はエディタで行う**
   - `echo 'KEY=VALUE' >> ~/.env` は改行漏れのリスクがあるため非推奨
   - 詳細は `knowledge/environment-setup/git-excluded-credentials.md` に記載済み

## 背景

- 会話ログ（`~/.claude/projects/*.jsonl`）にはローカルに認証情報が残る
- Git には上がっていない（`.gitignore` で除外済み）
- Anthropic サーバーにも会話は送信されているが、学習には使われない（APIポリシー）
- 今すぐ危険なレベルではないが、習慣として丁寧に扱う
