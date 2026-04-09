"""OneDrive MCP ヘルパースクリプト

onedrive-mcp バイナリを JSON-RPC で呼び出し、VS Code拡張からでも
OneDrive にアクセスできるようにする。Windows/Mac 両対応。

使い方:
    python onedrive.py list [フォルダパス]
    python onedrive.py search <クエリ>
    python onedrive.py download <リモートパス> [保存先ディレクトリ]
    python onedrive.py metadata <ファイルパス>
    python onedrive.py auth   # トークン再認証

認証トークン:
    Windows: %USERPROFILE%\\.config\\onedrive-mcp\\token_cache.json
    Mac:     ~/.config/onedrive-mcp/token_cache.json
             （Mac は keyring に保存される場合もある）
"""
import subprocess
import json
import sys
import os
from pathlib import Path

# --- パス解決（クロスプラットフォーム） ---
VENV_DIR = Path.home() / ".claude" / "onedrive-mcp-venv"

if sys.platform == "win32":
    BINARY = VENV_DIR / "Scripts" / "onedrive-mcp.exe"
else:
    BINARY = VENV_DIR / "bin" / "onedrive-mcp"

CACHE_FILE = Path.home() / ".config" / "onedrive-mcp" / "token_cache.json"


def _mcp_call(tool_name: str, arguments: dict) -> dict:
    proc = subprocess.Popen(
        [str(BINARY)],
        stdin=subprocess.PIPE,
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
    )

    def send(msg):
        proc.stdin.write((json.dumps(msg) + "\n").encode())
        proc.stdin.flush()

    def recv():
        line = proc.stdout.readline()
        if not line:
            # MCPサーバーが異常終了した場合、stderrを読んでエラーを報告
            stderr_output = proc.stderr.read().decode(errors="replace").strip()
            raise RuntimeError(
                f"MCPサーバーが予期せず終了しました。stderr: {stderr_output or '(なし)'}"
            )
        return json.loads(line)

    try:
        send({
            "jsonrpc": "2.0", "id": 1, "method": "initialize",
            "params": {
                "protocolVersion": "2024-11-05",
                "capabilities": {},
                "clientInfo": {"name": "asuka", "version": "1.0"},
            },
        })
        recv()
        send({"jsonrpc": "2.0", "method": "notifications/initialized", "params": {}})
        send({
            "jsonrpc": "2.0", "id": 2, "method": "tools/call",
            "params": {"name": tool_name, "arguments": arguments},
        })
        resp = recv()
    finally:
        # 正常・異常どちらの場合もプロセスをクリーンアップ
        try:
            proc.stdin.close()
        except Exception:
            pass
        try:
            proc.wait(timeout=5)
        except subprocess.TimeoutExpired:
            proc.kill()
            proc.wait()
    return resp


def cmd_list(folder_path: str = "/"):
    resp = _mcp_call("list_files", {"folder_path": folder_path})
    content = resp.get("result", {}).get("content", [])
    for c in content:
        print(c.get("text", ""))


def cmd_search(query: str):
    resp = _mcp_call("search_files", {"query": query})
    content = resp.get("result", {}).get("content", [])
    for c in content:
        print(c.get("text", ""))


def cmd_download(remote_path: str, save_directory: str = ""):
    args = {"remote_path": remote_path}
    if save_directory:
        args["save_directory"] = save_directory
    resp = _mcp_call("download_file", args)
    content = resp.get("result", {}).get("content", [])
    for c in content:
        print(c.get("text", ""))


def cmd_metadata(file_path: str):
    resp = _mcp_call("get_file_metadata", {"file_path": file_path})
    content = resp.get("result", {}).get("content", [])
    for c in content:
        print(c.get("text", ""))


def cmd_auth():
    """トークンを再認証する（ファイルキャッシュに保存）

    Windows: keyring が失敗するためファイルに保存
    Mac:     keyring（macOS Keychain）が使えるが、ファイルへのフォールバックも機能する
    """
    # site-packages を動的に解決
    import glob
    if sys.platform == "win32":
        sp = str(VENV_DIR / "Lib" / "site-packages")
        if sp not in sys.path:
            sys.path.insert(0, sp)
    else:
        sp_pattern = str(VENV_DIR / "lib" / "python*" / "site-packages")
        for sp in glob.glob(sp_pattern):
            if sp not in sys.path:
                sys.path.insert(0, sp)

    import msal

    CLIENT_ID = "14d82eec-204b-4c2f-b7e8-296a70dab67e"
    TENANT = "organizations"
    SCOPES = ["Files.ReadWrite", "User.Read"]

    cache = msal.SerializableTokenCache()
    if CACHE_FILE.exists():
        cache.deserialize(CACHE_FILE.read_text(encoding="utf-8"))

    app = msal.PublicClientApplication(
        CLIENT_ID,
        authority=f"https://login.microsoftonline.com/{TENANT}",
        token_cache=cache,
    )

    # サイレント認証を試みる
    accounts = app.get_accounts()
    if accounts:
        result = app.acquire_token_silent(SCOPES, account=accounts[0])
        if result and "access_token" in result:
            CACHE_FILE.parent.mkdir(parents=True, exist_ok=True)
            CACHE_FILE.write_text(cache.serialize(), encoding="utf-8")
            print("既存トークンで認証成功", file=sys.stderr)
            return

    # デバイスコードフロー
    flow = app.initiate_device_flow(scopes=SCOPES)
    if "user_code" not in flow:
        print(f"Device flow failed: {flow.get('error_description', 'Unknown')}", file=sys.stderr)
        sys.exit(1)

    print(f"\nTo sign in, visit: {flow['verification_uri']}", file=sys.stderr)
    print(f"Enter code: {flow['user_code']}\n", file=sys.stderr)

    result = app.acquire_token_by_device_flow(flow)
    if "access_token" not in result:
        print(f"Auth failed: {result.get('error_description', 'Unknown')}", file=sys.stderr)
        sys.exit(1)

    CACHE_FILE.parent.mkdir(parents=True, exist_ok=True)
    CACHE_FILE.write_text(cache.serialize(), encoding="utf-8")
    print(f"Authentication successful. Token saved to: {CACHE_FILE}", file=sys.stderr)


def main():
    args = sys.argv[1:]
    if not args:
        print(__doc__)
        sys.exit(1)

    cmd = args[0]
    if cmd == "list":
        cmd_list(args[1] if len(args) > 1 else "/")
    elif cmd == "search":
        if len(args) < 2:
            print("使い方: onedrive.py search <クエリ>", file=sys.stderr)
            sys.exit(1)
        cmd_search(args[1])
    elif cmd == "download":
        if len(args) < 2:
            print("使い方: onedrive.py download <リモートパス> [保存先ディレクトリ]", file=sys.stderr)
            sys.exit(1)
        cmd_download(args[1], args[2] if len(args) > 2 else "")
    elif cmd == "metadata":
        if len(args) < 2:
            print("使い方: onedrive.py metadata <ファイルパス>", file=sys.stderr)
            sys.exit(1)
        cmd_metadata(args[1])
    elif cmd == "auth":
        cmd_auth()
    else:
        print(f"Unknown command: {cmd}", file=sys.stderr)
        sys.exit(1)


if __name__ == "__main__":
    main()
