# /sync スキル

GitHub リポジトリと `.claude` 設定を同期します。

## 使い方

- `/sync` → 状態確認 + 変更があれば push
- `/sync pull` → GitHub から最新を取得（別PCで変更した後に使う）
- `/sync push` → 現在の変更を GitHub へ push

## 手順

### 状態確認（引数なしの場合）

```bash
cd ~/.claude
git status --short
git log origin/main..HEAD --oneline  # pushされていないコミット
git log HEAD..origin/main --oneline  # pullが必要なコミット
```

状態を確認して以下を報告する：
- ローカルに未コミットの変更があるか
- GitHub より先行しているか（push が必要）
- GitHub に新しいコミットがあるか（pull が必要）

変更がある場合は、コミット内容を確認して `git add` → `git commit` → `git push` を実行する。

### pull の場合

```bash
cd ~/.claude
git pull origin main
```

完了後「最新の状態に更新しました」と報告する。

### push の場合

```bash
cd ~/.claude
git add -A
git status --short
```

変更ファイルを確認後、コミットメッセージを生成して `git commit` → `git push` を実行する。

## 注意事項

- `.credentials.json` など認証情報はコミットしない（.gitignore で管理済み）
- push 前に必ず変更内容をシンヤさんに確認する
- コンフリクトが発生した場合は、内容をシンヤさんに報告して判断を仰ぐ
