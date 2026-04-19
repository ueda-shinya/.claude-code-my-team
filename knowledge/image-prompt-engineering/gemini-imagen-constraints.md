# Gemini 画像生成モデルの選定と制約

## デフォルトモデル（2026-04-18確定）

### 第一選択: `gemini-3.1-flash-image-preview`

- **エンドポイント**: `https://generativelanguage.googleapis.com/v1beta/models/gemini-3.1-flash-image-preview:generateContent?key=$GEMINI_API_KEY`
- **リクエスト形式**: generateContent（`contents[].parts[].text` + `generationConfig.responseModalities: ["IMAGE"]` + `generationConfig.imageConfig`）
- **レスポンス**: `candidates[0].content.parts[]` → `inlineData.data` を base64 decode
- **長所**:
  - 指示追従性が高い（特に「文字を生成しない」「オブジェクトを除外する」等の否定指示に強い）
  - `4:5` を含む幅広いアスペクト比対応（1:1 / 16:9 / 9:16 / 4:3 / 3:4 / 2:3 / 3:2 / 4:5 / 5:4 / 21:9 等）
  - 指定アスペクト比で正確に出力される
- **短所**: 出力ファイルサイズが大きい（2-3MB程度、Imagenは1MB程度）

### 第二選択: `imagen-4.0-generate-001`（フォールバック専用）

- **エンドポイント**: `https://generativelanguage.googleapis.com/v1beta/models/imagen-4.0-generate-001:predict?key=$GEMINI_API_KEY`
- **リクエスト形式**: predict（`instances[0].prompt` + `parameters.sampleCount` + `parameters.aspectRatio`）
- **レスポンス**: `predictions[0].bytesBase64Encoded` を base64 decode
- **使用タイミング**: gemini-3.1-flash-image-preview が不調・不可の場合のみ
- **制約**:
  - `3:4` を指定しても実際の出力は **896x1280（7:10）** になる（API側の内部丸め）
  - `4:5` は非対応
  - 文字排除の指示に弱い（「紙」「画面」等を含むシーンでは文字を勝手に生成しがち）
- **確認**: 出力実体はPNG形式（`\x89PNG`ヘッダー）。`.webp` 拡張子で保存してもブラウザは表示可能

## 選定履歴

- **2026-04-17まで**: `imagen-4.0-generate-001` を使用していた
- **2026-04-18**: 配布用スワイプLP生成でslide-04の「文字が画面/紙に入る」問題が5回連続で発生。`gemini-3.1-flash-image-preview` に切り替えて解決。以降こちらをデフォルトに格上げ

## Preview版の廃止・仕様変更リスク（重要）

`gemini-3.1-flash-image-preview` はモデル名に `preview` を含むプレビュー版。Google のプレビュー版は予告なく廃止・レート変更・仕様変更が行われる前例あり。以下の運用ルールを適用する。

### 生存確認
- **トリガー**: 月初および朝のブリーフィング時にアスカが生存確認を実施（`curl` で軽量リクエストを1回送る）
- **確認コマンド例**:
  ```bash
  curl -s -X POST "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.1-flash-image-preview:generateContent?key=$GEMINI_API_KEY" \
    -H "Content-Type: application/json" \
    -d '{"contents":[{"parts":[{"text":"test"}]}],"generationConfig":{"responseModalities":["IMAGE"],"imageConfig":{"aspectRatio":"1:1","imageSize":"1K"}}}' | head -c 200
  ```
  → `"candidates"` を含めばOK、`"error"` を含めば要対処

### エラー時の自動フォールバック条件
以下のいずれかが発生した場合、**即座に `imagen-4.0-generate-001` にフォールバック**してシンヤさんに報告：
- HTTP 404（モデル廃止）
- HTTP 400 `MODEL_NOT_FOUND` / `PERMISSION_DENIED`
- `"error.status": "UNAVAILABLE"`
- 3回連続リトライでタイムアウト

### GA版リリース時の移行
- Googleが `gemini-3.1-flash-image`（Preview外れた版）をリリースしたら、本knowledgeとCLAUDE.md両方を更新
- 移行確認のため、最初の5案件は新旧並行生成で比較

## MCP と CLI のモデル差に関する注意

- MCPツール `mcp__gemini-image__gemini-generate-image` が内部で呼ぶモデルは不明（現時点では `~/.config/gemini-mcp/` 配下の設定に依存）
- MCPが Imagen 4.0 系を呼んでいる場合、文字混入問題が再発する可能性あり
- **重要案件（テキスト後載せ・否定指示を含む）では CLI 強制ルート**で生成すること：
  - テキスト後載せのLP/スライド
  - 「文字を生成しない」「特定オブジェクトを除外する」等の否定指示を含むプロンプト
  - `gemini-3.1-flash-image-preview` の強みを活かしたい案件
