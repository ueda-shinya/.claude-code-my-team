📌 WordPress プラグイン仕様
JavaScriptの動作はそのまま

画像の通常読み込み完了後に、遅延読み込み（lazyload）画像の優先ロードを開始。

ビューポートに近い画像から順にロードする。

loading="lazy" を削除し、強制リロードを実施。

WordPressに適した設計

wp_enqueue_script() を使用して、lazyload-prioritize.js を読み込む。

wp_enqueue_scripts にフックし、適切なページでスクリプトを出力。

セキュリティ対策として、直接アクセス防止 (defined('ABSPATH') のチェック)。

