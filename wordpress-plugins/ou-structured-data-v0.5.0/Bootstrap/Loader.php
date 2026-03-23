<?php
namespace OU\StructuredData\Bootstrap;

use OU\StructuredData\Admin\Settings\GeneralSettings;
use OU\StructuredData\Admin\Assets\Assets;
use OU\StructuredData\Admin\Preview\PreviewPage;
use OU\StructuredData\PublicSite\JsonLdRenderer;
use OU\StructuredData\App\BuildGraphUseCase;
use OU\StructuredData\Infra\Breadcrumb\Generators\ModelGenerator;
use OU\StructuredData\PublicSite\Shortcodes\FAQShortcode;

class Loader {
    public static function onActivate(): void {
        add_option('ou_schema_common', [
            'org' => [
                'name' => '',
                'url' => home_url('/'),
                'logo' => '',
                'logo_w' => '',
                'logo_h' => '',
                'telephone' => '',
                'inLanguage' => get_locale() === 'ja' ? 'ja' : 'en',
                'address' => ['ja' => ['postalCode'=>'','addressRegion'=>'','addressLocality'=>'','streetAddress'=>'','building'=>'','addressCountry'=>'JP']],
                'openingHoursSpecification' => [],
                'sameAs' => [],
                'contactPoint' => [],
            ],
            'website' => [
                'name' => get_bloginfo('name'),
                'url' => home_url('/'),
                'inLanguage' => get_locale() === 'ja' ? 'ja' : 'en',
                'alternateName' => '',
            ],
            'breadcrumb' => ['homeLabel' => 'HOME', 'useSiteTitle' => 0],
        ]);
    }
    public static function onDeactivate(): void { /* keep data by default */ }
    public function boot(): void {
        $settings = new GeneralSettings();
        $model = new ModelGenerator();
        $usecase = new BuildGraphUseCase(get_option('ou_schema_common', []), $model);
        $renderer = new JsonLdRenderer($usecase);
        $assets = new Assets();
        $preview = new PreviewPage($usecase);
        $faq = new FAQShortcode();

        add_action('admin_menu', function() use ($settings, $preview){ $settings->registerMenu(); $preview->registerMenu(); });
        add_action('admin_init', function() use ($settings){ $settings->registerSettings(); });
        add_action('admin_enqueue_scripts', function($hook) use ($assets){ $assets->enqueue($hook); });
        add_action('wp_head', function() use ($renderer){ $renderer->output(); }, 99);

        $faq->register();
        add_action('add_meta_boxes', [self::class, 'registerCompanyMeta']);
        add_action('save_post_page', [self::class, 'saveCompanyMeta']);
    }

    public static function registerCompanyMeta(): void {
        add_meta_box('ou-sd-company-type', 'OU Structured Data: ページタイプ', function($post){
            $val = get_post_meta($post->ID, '_ou_sd_page_type', true);
            echo '<label><input type="radio" name="_ou_sd_page_type" value="" '.checked($val,'',false).'/> 通常</label><br/>';
            echo '<label><input type="radio" name="_ou_sd_page_type" value="company" '.checked($val,'company',false).'/> 会社情報ページ（AboutPage）</label>';
        }, 'page', 'side', 'default');
    }
    public static function saveCompanyMeta($post_id): void {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        $val = isset($_POST['_ou_sd_page_type']) ? sanitize_text_field($_POST['_ou_sd_page_type']) : '';
        update_post_meta($post_id, '_ou_sd_page_type', $val);
    }
}
