---
name: OneDrive接続コマンドは確認不要
description: OneDrive MCP接続に必要なコマンドはシンヤさんへの確認なしでアスカが実行してよい
type: feedback
originSessionId: ace84e55-8b91-4114-bdcc-341a4f6a91b4
---
OneDriveの**読み取り系**コマンドは、シンヤさんへの確認なしでアスカが直接実行してよい。

**確認不要（読み取り系）:** list_files, search_files, download_file, get_file_metadata
**確認必要（書き込み・削除・共有系）:** upload_file, create_sharing_link, generate_share_url、その他破壊的操作

**Why:** 閲覧系は毎回確認するのが冗長。日常的に使うためスムーズに実行したい。書き込み・共有系は影響範囲が大きいため従来通り確認する。
**How to apply:** mcp__onedrive__の読み取り系ツールのみ確認なしで即実行。それ以外は確認してから実行する。
