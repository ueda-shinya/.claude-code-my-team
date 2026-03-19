---
name: officeueda web事業コーディング方針
description: officeueda biz-webのサイト・LP制作時のコーディング方針・対応可能範囲
type: project
---

## コーディング標準方針

- サイト・LP制作時は**JSON-LDを必ず入れる**（指示不要）
- ページの種類に応じてtypeを選択: LocalBusiness / WebPage / FAQPage / Product / Service など
- JSON-LDを入れることでSEO・GMC・リッチリザルトをまとめて対応できる

## GMC（Google Merchant Center）対応

- 特殊な対応は不要。正確なJSON-LDを書けばGMCが自動で解釈する
- GMC掲載対象の商品・サービスの場合は Product / Service schema の必須フィールドを網羅する
- 必須フィールド: name / image / description / sku / offers（price・priceCurrency・availability・url）
- 今回（2026-03-19 officeueda LP）はGMC対象外のため非対応
