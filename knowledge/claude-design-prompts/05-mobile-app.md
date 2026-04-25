# 05. モバイルアプリUI

## 標準テンプレート（英語）

```
Build an animated mobile app prototype for a [app type] app.
Dimensions: 390x844px (iPhone 15 Pro).
Theme: [light/dark] with [accent color] accents ([#hex]).
[Screen] elements:
- [Element 1 with behavior]
- [Element 2 with behavior]
- [Element 3 with behavior]
Animations:
- [Animation 1: from state A to state B in [duration]]
- [Animation 2: stagger pattern]
- Button hover: scale to 1.05 with 200ms ease
Minimum touch target: 44px on all interactive elements.
```

## 標準テンプレート（日本語）

```
[アプリ種別]アプリのアニメーション付きモバイルプロトタイプを作成してください。
サイズ: 390×844px（iPhone 15 Pro想定）
テーマ: [ライト/ダーク]、[アクセント色名]アクセント [#hex]
[画面名]の要素:
- [要素1とその挙動]
- [要素2とその挙動]
- [要素3とその挙動]
アニメーション:
- [アニメ1: A状態からB状態へ、所要時間]
- [アニメ2: スタガーパターン]
- ボタンホバー: 1.05倍スケール、200msイーズ
タッチターゲット: 全インタラクティブ要素を44px以上に
```

## 実例（フィットネスアプリ）

```
Build an animated mobile app prototype for a fitness tracking app.
Dimensions: 390x844px (iPhone 15 Pro).
Theme: dark with electric blue accents (#0066FF).
Home screen elements:
- Daily step counter that animates counting up from 0 on load
- Progress ring showing % of daily goal
- Three quick-action buttons (Log Workout / Nutrition / Sleep)
Animations:
- Counter counts up from 0 to 8,432 in 1.2s
- Progress ring draws in on load
- Each card fades in and slides up, staggered 150ms apart
- Button hover: scale to 1.05 with 200ms ease
Minimum touch target: 44px on all interactive elements.
```

## ポイント

- iPhone 15 Pro 想定なら 390×844px、Android 標準なら 412×915px
- タッチターゲット 44px は WCAG 推奨値（指定しないと小さくなる）
- カウンター系は「from 0 to N in 1.2s」のように初期値・終了値・所要時間まで指定
- スタガーアニメ（複数要素を時間差で動かす）は「150ms apart」のように間隔を明示

出典: [MindStudio - Claude Design Prototypes](https://www.mindstudio.ai/blog/claude-design-animated-prototypes-slide-decks)
