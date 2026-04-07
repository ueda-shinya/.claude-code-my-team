# cmux セットアップガイド（初心者向け）

> 対象：Claude Code は使い慣れているが、cmux は初めての方  
> 対応OS：macOS 14（Sonoma）以降 / Apple Silicon・Intel 両対応  
> 作成日：2026-04-07

---

## STEP 0：事前確認

まず Mac の OS バージョンを確認してください。

```
Appleメニュー → このMacについて → macOS のバージョン確認
```

**macOS 14（Sonoma）以上でないと動きません。**

---

## STEP 1：インストール

Homebrew でインストールするのが一番簡単です。

```bash
brew tap manaflow-ai/cmux
brew install --cask cmux
```

> Homebrew が入っていない場合は先に https://brew.sh からインストール

インストールできたら起動してみましょう。

```
⌘Space → "cmux" と入力 → Enter
```

縦タブのサイドバーが付いたターミナルが開けば成功です。

---

## STEP 2：CLI コマンドを使えるようにする

cmux を Claude Code と連携させるには、`cmux` コマンドをターミナルから呼び出せる状態にする必要があります。

```bash
sudo ln -sf "/Applications/cmux.app/Contents/Resources/bin/cmux" /usr/local/bin/cmux
```

パスワードを聞かれたら Mac のログインパスワードを入力してください。

確認：

```bash
which cmux
# → /usr/local/bin/cmux と表示されれば OK
```

---

## STEP 3：macOS の通知を許可する

通知機能を使うために、システム設定を開きます。

```
システム設定 → 通知 → cmux → 「通知を許可」をオン
```

---

## STEP 4：Claude Code との連携設定

cmux v0.63.2 以降は **自動で連携が有効**になっていることが多いです。  
ただし明示的に設定しておくと確実です。

`~/.claude/settings.json` をテキストエディタで開いて、以下を追加してください。  
（ファイルが存在しない場合は新規作成）

```json
{
  "hooks": {
    "SessionStart": [
      {
        "type": "command",
        "command": "cmux claude-hook session-start"
      }
    ],
    "Notification": [
      {
        "type": "command",
        "command": "cmux claude-hook notification"
      }
    ],
    "Stop": [
      {
        "type": "command",
        "command": "cmux claude-hook stop"
      }
    ]
  }
}
```

**各設定の意味：**

| hook | タイミング | cmux の動作 |
|---|---|---|
| SessionStart | Claude Code 起動時 | タブに「実行中 ⚡️」表示 |
| Notification | 入力待ち・許可待ち時 | タブが光る＋「🔔 入力が必要」通知 |
| Stop | セッション終了時 | ステータスをクリア |

---

## STEP 5：基本的な使い方

### ワークスペース（タブ）の作り方

- サイドバーの「＋」ボタン でタブ追加
- タブには自動で **git ブランチ名・カレントディレクトリ・PR ステータス** が表示される

### よく使うショートカット

| 操作 | キー |
|---|---|
| 縦に画面分割 | `⌘D` |
| 横に画面分割 | `⌘⇧D` |
| ペイン間を移動 | `⌥⌘ + 矢印` |
| 通知パネルを開く | `⌘⇧I` |
| 未読タブにジャンプ | `⌘⇧U` |
| サイドバーを隠す | `⌘B` |
| コマンドパレット | `⌘⇧P` |
| 設定をリロード | `⌘⇧,` |

---

## 実際の使い方イメージ

1. cmux を起動
2. タブを3〜5個作る
3. 各タブで `cd プロジェクトフォルダ && claude` を実行
4. それぞれに別々の指示を出す
5. あとは別の作業をしながら待つ
6. どれかが止まったら → タブが光る・通知が来る → 確認して対応

---

## よくあるトラブル

### 通知が来ない
→ STEP 3 の macOS 通知設定を確認  
→ cmux を再起動してみる

### `cmux claude-hook` がエラーになる
→ STEP 2 のシンボリックリンクが未作成  
→ `which cmux` で確認

### hooks が動いていない感じがする
→ `~/.claude/settings.json` の JSON が正しく書けているか確認（カンマの付け忘れに注意）

### 今フォーカスしているタブへの通知が来ない
→ 既知のバグです（Issue #963）。別タブを見ているときは通知されます

---

## アップデート方法

```bash
brew upgrade --cask cmux
```

---

## 参考リンク

- [cmux 公式サイト](https://cmux.com/)
- [cmux 公式ドキュメント](https://term.cmux.dev/)
- [Claude Code 連携ガイド](https://www.mintlify.com/manaflow-ai/cmux/integrations/claude-code)
- [GitHub リポジトリ](https://github.com/manaflow-ai/cmux)
