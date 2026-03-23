<?php
// 管理画面:admin-setting.php

// 管理画面に設定ページを追加する関数
function officeueda_add_settings_page()
{
    add_menu_page(
        'オフィスウエダ 使用プラグイン一覧', // 管理メニュータイトル
        'オフィスウエダ 使用プラグイン一覧', //テスト用トップメニュー
        'manage_options', //manage_options
        'test_top_menu', //test_top_menu
        'officeueda_settings_page_html', //test_menu_contents
        null, //dashicons-calendar
        99
    );
}
add_action('admin_menu', 'officeueda_add_settings_page');

// 設定ページのHTMLを出力する関数
function officeueda_settings_page_html()
{
?>
    <div class="wrap" style="margin-left: 30px;">
        <h2>推奨テーマ</h2>
        <ul>
            <li><a href="" rel="nofollow noopener noreferrer" target="_blank">準備中</a></li>
            <li><a href="https://af.moshimo.com/af/c/click?a_id=4254077&p_id=3885&pc_id=9646&pl_id=53798&url=https%3A%2F%2Fswell-theme.com%2F" rel="nofollow noopener noreferrer" target="_blank" referrerpolicy="no-referrer-when-downgrade">SWELL(買い切り)</a><img src="//i.moshimo.com/af/i/impression?a_id=4254077&p_id=3885&pc_id=9646&pl_id=53798" width="1" height="1" style="border:none;" alt=""></li>
            <li><a href="https://vws.vektor-inc.co.jp/?vwaf=1258" rel="nofollow noopener noreferrer" target="_blank">Lightning (サブスク)</a></li>
        </ul>

        <h2>使用プラグイン一覧</h2>
        <section style="margin-left: 30px;">
            <h3>必須プラグイン</h3>
            <ul>
                <li><a href="https://eastcoder.com/code/wp-multibyte-patch/" rel="nofollow noopener noreferrer" target="_blank">WP Multibyte Patch(日本語環境で正しく動作させる)</a></li>
            </ul>
        </section>
        <section style="margin-left: 30px;">
            <h3>バックアップ用プラグイン</h3>
            推奨
            <ul>
                <li><a href="https://ja.wordpress.org/plugins/wpvivid-backuprestore/" rel="nofollow noopener noreferrer" target="_blank">WPvivid – 移行、バックアップ、ステージング</a></li>
                <li>wpvividbackup-cleaner / WPvividのバックアップデータ全削除 </li>
            </ul>
            <ul>
                <li><a href="https://ja.wordpress.org/plugins/all-in-one-wp-migration/" rel="nofollow noopener noreferrer" target="_blank">All-in-One WP Migration(バックアップ)</a></li>
            </ul>
        </section>
        <section style="margin-left: 30px;">
            <h3>構築時必要(リリース時削除)</h3>
            <ul>
                <li><a href="https://ja.wordpress.org/plugins/simple-page-ordering/" rel="nofollow noopener noreferrer" target="_blank">Simple Page Ordering(投稿の並び替え)</a></li>
                <li><a href="https://ja.wordpress.org/plugins/duplicate-post/" rel="nofollow noopener noreferrer" target="_blank">Yoast Duplicate Post(固定ページ・投稿複写)</a></li>
            </ul>
        </section>

        <section style="margin-left: 30px;">
            <h3>SEOに関係するプラグイン</h3>
            推奨
            <ul>
                <li><a href="https://ja.wordpress.org/plugins/all-in-one-seo-pack/" rel="nofollow noopener noreferrer" target="_blank">All in One SEO / roboto.txt等の編集もできる</a></li>
            </ul>

            Swell推奨
            <ul>
                <li><a href="https://ja.wordpress.org/plugins/xml-sitemap-feed/" rel="nofollow noopener noreferrer" target="_blank">XML Sitemap & Google News(サイトマップ作製)</a></li>
                <li><a href="https://ja.wordpress.org/plugins/google-site-kit/" rel="nofollow noopener noreferrer" target="_blank">Site Kit by Google(Google ツールのセットアップ)</a></li>
                <li><a href="https://ja.wordpress.org/plugins/seo-simple-pack/" rel="nofollow noopener noreferrer" target="_blank">SEO SIMPLE PACK</a></li>
            </ul>
        </section>

        <section style="margin-left: 30px;">
            <h3>Contact Form7 関係</h3>
            <ul>
                <li><a href="https://ja.wordpress.org/plugins/contact-form-7/" rel="nofollow noopener noreferrer" target="_blank">Contact Form 7(お問い合わせページ)</a></li>
                <li><a href="https://ja.wordpress.org/plugins/flamingo/" rel="nofollow noopener noreferrer" target="_blank">Flamingo(お問い合わせ内容のバックアップ)</a></li>
                <li><a href="https://ja.wordpress.org/plugins/contact-form-7-multi-step-module/" rel="nofollow noopener noreferrer" target="_blank">Contact Form 7 Multi-Step Forms(サンクスメール他)</a></li>
                <li>contact-form-scroll-enhancer / 確認画面の戻るで指定したIDまでスクロールする </li>
                <li><a href="https://ja.wordpress.org/plugins/wp-mail-smtp/" rel="nofollow noopener noreferrer" target="_blank"></a>WordPress Mail SMTP / メールの到達率を上げる・受信できない等で使用</li>
            </ul>
        </section>
        <section style="margin-left: 30px;">
            <h3>Swell専用プラグイン</h3>
            <ul>
                <li><a href="https://webcre.tech/customheader-plugin/" rel="nofollow noopener noreferrer" target="_blank">SWELL Custom Header(ヘッダーCTA)</a></li>
                <li>swell-custom-header-sns-enhancer / SWELL Custom Header のSNSリンクを新しいタブで開くようにする</li>
            </ul>
        </section>
        <section style="margin-left: 30px;">
            <h3>特殊プラグイン</h3>
            <ul>
                <li><a href="https://ja.wordpress.org/plugins/wp-optimize/" rel="nofollow noopener noreferrer" target="_blank"></a>WP-Optimize / データベースクリーン</li>
                <li>change-admin-email / 管理メールアドレス強制変更 </li>
                <li>htaccess-cache-control / キャッシュ無効化 </li>
                <li>disable-image-resizing / リサイズする機能無効化</li>
            </ul>
        </section>

        <!-- <li><a href="" rel="nofollow noopener noreferrer" target="_blank"></a></li> -->
    </div>
<?php
}
