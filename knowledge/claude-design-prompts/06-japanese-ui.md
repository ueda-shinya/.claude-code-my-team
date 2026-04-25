# 06. 日本語UI追加指定

日本語UIを生成させる際、これを追加指定しないと**フォント・行間・禁則処理が崩れる**。

## 英語ブロック（Claude Design に英語で渡す方が精度が出る）

```
Japanese typography requirements:
- font-family: "Hiragino Sans", "Hiragino Kaku Gothic ProN",
  "Noto Sans JP", Meiryo, sans-serif
- line-height: 1.8 for body text (not 1.4)
- letter-spacing: 0.04em for headings only
- font-feature-settings: "palt", "kern" for proportional Japanese
- Kinsoku processing: do not break lines before 。、）」』
- Mixed typesetting: use Latin font for ASCII numerals and
  Latin words, Japanese font for all CJK characters
```

## 日本語ブロック（参考）

```
日本語タイポグラフィの要件:
- フォントスタック: Hiragino Sans → Hiragino Kaku Gothic ProN
  → Noto Sans JP → Meiryo → sans-serif の順
- 本文の行間: 1.8（Westernの1.4ではなく）
- 字間: 見出しのみ 0.04em
- font-feature-settings: "palt", "kern"（プロポーショナル詰め）
- 禁則処理: 行頭に 。、）」』 が来ないように
- 混植: ASCII数字・英単語はラテンフォント、漢字・仮名は日本語フォント
```

## ポイント

- **行間 1.8 が肝**：Western の 1.4 のままだと日本語は窮屈に見える
- **palt 指定**：プロポーショナル詰めで読みやすさが大幅向上（見出しに必須）
- **混植**：英数字をHiragino系で出すとガタつくのでLatin系を当てる
- **明朝が必要なら**: `"Hiragino Mincho ProN", "Yu Mincho", serif` を別途指定

## セリフ系（読み物系メディア）

```
Japanese serif typography:
- font-family: "Hiragino Mincho ProN", "Yu Mincho", "MS PMincho", serif
- line-height: 1.8 to 2.0 for long-form reading
- font-size: 18px+ for body (article content)
- Article width: 620px max (note.com style)
```

出典: [kzhrknt/awesome-design-md-jp](https://github.com/kzhrknt/awesome-design-md-jp)
