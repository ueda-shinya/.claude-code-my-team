# Windows Python コーディングルール

Windows 環境（X:\Python310）でスクリプトを書く際の禁止事項と代替記法。

## Pythonインタープリタ

| ❌ 使わない | ✅ 代わりに使う | 理由 |
|---|---|---|
| `python3` | `sys.executable` | Windows では `python3` が別PATHを指すか未インストールの場合がある |
| `python` | `sys.executable` | 同上 |

```python
# ❌
subprocess.run(['python3', 'script.py'])

# ✅
subprocess.run([sys.executable, 'script.py'])
```

## 日付・時刻フォーマット

| ❌ 使わない | ✅ 代わりに使う | 理由 |
|---|---|---|
| `d.strftime('%-m月%-d日')` | `f'{d.month}月{d.day}日'` | `%-` はLinux専用。Windowsでは `ValueError` |
| `d.strftime('%-H時')` | `f'{d.hour}時'` | 同上 |

## プロセス操作

- ngrok・サーバープロセスを削除する前に、**ログを確認してどのPIDがアクティブか特定**してから操作する
- `taskkill /F /IM ngrok.exe` で全ngrokをまとめて停止できる
- server.py 起動時は冒頭で `taskkill` を実行して旧プロセスを自動クリーンアップする

## 実績のあるPythonパス（DESKTOP-R6S8S83）

- **使用Python**: `X:\Python310\python.exe`
- **インストール済みパッケージ**: flask, anthropic, requests, python-dotenv, PyJWT, pyngrok
- `C:\Users\ueda-\AppData\Local\Microsoft\WindowsApps\python3.12.exe` はVSCode拡張用であり、上記パッケージは未インストール

## 文字エンコーディング

| ❌ 使わない | ✅ 代わりに使う | 理由 |
|---|---|---|
| `¥{値}` / `¥` (U+00A5) をログ出力に使う | `{値}円` | Windows コンソール（CP932）では U+00A5 が `UnicodeEncodeError` になる場合がある |

```python
# ❌
logging.info(f"推定コスト: 約¥{cost:.1f}")

# ✅
logging.info(f"推定コスト: 約{cost:.1f}円")
```

## 更新履歴
- 2026-03-30: UnicodeEncodeError（¥記号）の注意点を追加（chatwork-sync.py の事例）
- 2026-03-28: 初版作成（LINE WORKS Bot Phase 1 開発時の教訓）
