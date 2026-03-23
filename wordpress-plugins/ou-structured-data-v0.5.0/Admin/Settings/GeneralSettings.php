<?php
namespace OU\StructuredData\Admin\Settings;

if (!defined('ABSPATH')) { exit; }

class GeneralSettings {
    private string $option_key = 'ou_schema_common';

    public function registerMenu(): void {
        add_menu_page(__('OU Structured Data','ou-structured-data'), __('OU Structured Data','ou-structured-data'), 'manage_options', 'ou-structured-data', [$this,'render'], 'dashicons-database', 81);
    }

    public function registerSettings(): void {
        register_setting('ou_sd_group', $this->option_key, ['type'=>'array','sanitize_callback'=>[$this,'sanitize'],'default'=>[]]);

        add_settings_section('ou_sd_common_org', __('Organization','ou-structured-data'), function(){ echo '<p>'.esc_html__('Common organization properties used across the site.','ou-structured-data').'</p>'; }, 'ou-structured-data');

        $o = get_option($this->option_key, []);
        $def = [
            'org' => [
                'name' => '',
                'url' => home_url('/'),
                'logo' => '',
                'logo_w' => '',
                'logo_h' => '',
                'telephone' => '',
                'inLanguage' => (get_locale()==='ja' ? 'ja' : 'en'),
                'address' => ['ja' => ['postalCode'=>'','addressRegion'=>'','addressLocality'=>'','streetAddress'=>'','building'=>'','addressCountry'=>'JP']],
                'openingHoursSpecification' => [],
                'sameAs' => [],
                'contactPoint' => [],
            ],
            'website' => [
                'name' => get_bloginfo('name'),
                'url' => home_url('/'),
                'inLanguage' => (get_locale()==='ja' ? 'ja' : 'en'),
                'alternateName' => '',
            ],
            'breadcrumb' => ['homeLabel' => 'HOME', 'useSiteTitle' => 0],
        ];
        if (!is_array($o)) { $o = []; }
        $o = array_replace_recursive($def, $o);
        $org = $o['org']; $website = $o['website']; $breadcrumb = $o['breadcrumb'];

        add_settings_field('org_name', __('Organization Name (business entity)','ou-structured-data'), function() use($org){ echo '<input type="text" name="ou_schema_common[org][name]" value="'.esc_attr($org['name']).'" class="regular-text" />'; }, 'ou-structured-data','ou_sd_common_org');
        add_settings_field('org_url', __('Organization URL','ou-structured-data'), function() use($org){ echo '<input type="url" name="ou_schema_common[org][url]" value="'.esc_attr($org['url']).'" class="regular-text code" />'; }, 'ou-structured-data','ou_sd_common_org');
        add_settings_field('org_logo', __('Logo (URL & size)','ou-structured-data'), function() use($org){
            echo '<input type="url" name="ou_schema_common[org][logo]" value="'.esc_attr($org['logo']).'" class="regular-text code" placeholder="https://…/logo.png" /><br/>';
            echo 'W <input type="number" min="0" step="1" name="ou_schema_common[org][logo_w]" value="'.esc_attr($org['logo_w']).'" class="small-text" /> ';
            echo 'H <input type="number" min="0" step="1" name="ou_schema_common[org][logo_h]" value="'.esc_attr($org['logo_h']).'" class="small-text" /> ';
        }, 'ou-structured-data','ou_sd_common_org');
        add_settings_field('org_tel', __('Telephone (E.164 recommended)','ou-structured-data'), function() use($org){ echo '<input type="text" name="ou_schema_common[org][telephone]" value="'.esc_attr($org['telephone']).'" class="regular-text" />'; }, 'ou-structured-data','ou_sd_common_org');

        add_settings_field('org_addr_ja', __('Address (JA)','ou-structured-data'), function() use($org){
            $ja = $org['address']['ja'];
            echo '<div>〒 <input type="text" name="ou_schema_common[org][address][ja][postalCode]" value="'.esc_attr($ja['postalCode']).'" class="small-text" /> ';
            echo '<input type="text" name="ou_schema_common[org][address][ja][addressRegion]" value="'.esc_attr($ja['addressRegion']).'" class="regular-text" /> ';
            echo '<input type="text" name="ou_schema_common[org][address][ja][addressLocality]" value="'.esc_attr($ja['addressLocality']).'" class="regular-text" /><br/>';
            echo '<input type="text" name="ou_schema_common[org][address][ja][streetAddress]" value="'.esc_attr($ja['streetAddress']).'" class="regular-text" style="width:60%" /> ';
            echo '<input type="text" name="ou_schema_common[org][address][ja][building]" value="'.esc_attr($ja['building']).'" class="regular-text" /> ';
            echo '<input type="hidden" name="ou_schema_common[org][address][ja][addressCountry]" value="JP" /></div>';
        }, 'ou-structured-data','ou_sd_common_org');

        add_settings_field('org_sameas', __('Social / sameAs (one URL per line)','ou-structured-data'), function() use($org){
            $txt = implode("\n", array_map('esc_url', $org['sameAs']));
            echo '<textarea name="ou_schema_common[org][sameAs]" class="large-text code" rows="4">'.esc_textarea($txt).'</textarea>';
        }, 'ou-structured-data','ou_sd_common_org');

        add_settings_field('org_contact', __('Contact Points','ou-structured-data'), function() use($org){
            $rows = $org['contactPoint'];
            echo '<table class="widefat striped"><thead><tr><th>Type</th><th>Telephone</th><th>URL</th><th>Languages (comma)</th></tr></thead><tbody>';
            for($i=0;$i<3;$i++){
                $r = $rows[$i] ?? [];
                $ct = $r['contactType'] ?? '';
                $tel= $r['telephone'] ?? '';
                $url= $r['url'] ?? '';
                $langs = $r['availableLanguage'] ?? [];
                $langs_str = is_array($langs) ? implode(',', $langs) : (is_string($langs) ? $langs : '');
                echo '<tr>';
                echo '<td><input type="text" name="ou_schema_common[org][contactPoint]['.$i.'][contactType]" value="'.esc_attr($ct).'" /></td>';
                echo '<td><input type="text" name="ou_schema_common[org][contactPoint]['.$i.'][telephone]" value="'.esc_attr($tel).'" /></td>';
                echo '<td><input type="url" name="ou_schema_common[org][contactPoint]['.$i.'][url]" value="'.esc_attr($url).'" class="regular-text code" /></td>';
                echo '<td><input type="text" name="ou_schema_common[org][contactPoint]['.$i.'][availableLanguage]" value="'.esc_attr($langs_str).'" placeholder="ja,en" /></td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }, 'ou-structured-data','ou_sd_common_org');

        add_settings_section('ou_sd_common_site', __('WebSite','ou-structured-data'), function(){ echo '<p>'.esc_html__('Site-level properties.','ou-structured-data').'</p>'; }, 'ou-structured-data');
        add_settings_field('site_name', __('Site Name','ou-structured-data'), function() use($website){ echo '<input type="text" name="ou_schema_common[website][name]" value="'.esc_attr($website['name']).'" class="regular-text" />'; }, 'ou-structured-data','ou_sd_common_site');
        add_settings_field('site_url', __('Site URL','ou-structured-data'), function() use($website){ echo '<input type="url" name="ou_schema_common[website][url]" value="'.esc_attr($website['url']).'" class="regular-text code" />'; }, 'ou-structured-data','ou_sd_common_site');
        add_settings_field('site_lang', __('Site Language','ou-structured-data'), function() use($website){ $v=$website['inLanguage']; echo '<select name="ou_schema_common[website][inLanguage]">'; foreach(['ja'=>'Japanese','en'=>'English'] as $k=>$label){ echo '<option value="'.esc_attr($k).'" '.selected($v,$k,false).'>'.esc_html($label).'</option>'; } echo '</select>'; }, 'ou-structured-data','ou_sd_common_site');
        add_settings_field('site_altname', __('Alternate Name','ou-structured-data'), function() use($website){ echo '<input type="text" name="ou_schema_common[website][alternateName]" value="'.esc_attr($website['alternateName']).'" class="regular-text" />'; }, 'ou-structured-data','ou_sd_common_site');

        add_settings_section('ou_sd_breadcrumb', __('Breadcrumb (basic)','ou-structured-data'), function(){ echo '<p>'.esc_html__('Only home label here; advanced sources come in M2.','ou-structured-data').'</p>'; }, 'ou-structured-data');
        add_settings_field('br_label', __('Home label','ou-structured-data'), function() use($breadcrumb){ echo '<input type="text" name="ou_schema_common[breadcrumb][homeLabel]" value="'.esc_attr($breadcrumb['homeLabel']).'" class="regular-text" />'; }, 'ou-structured-data','ou_sd_breadcrumb');
        add_settings_field('br_use_site', __('Use site title for first crumb','ou-structured-data'), function() use($breadcrumb){ $v=!empty($breadcrumb['useSiteTitle']); echo '<label><input type="checkbox" name="ou_schema_common[breadcrumb][useSiteTitle]" value="1" '.checked($v,true,false).' /> '.esc_html__('If checked, first item uses site title; otherwise uses Home label.','ou-structured-data').'</label>'; }, 'ou-structured-data','ou_sd_breadcrumb');
    }

    private function normalize_phone_jp(?string $raw): ?string {
        if (!$raw) return null;
        $s = preg_replace('/[^0-9+]/', '', $raw);
        if (!$s) return null;
        if (str_starts_with($s, '+')) return $s;
        if (str_starts_with($s, '0')) { $s = ltrim($s, '0'); if ($s==='') return null; return '+81' . $s; }
        return $s;
    }

    public function sanitize($input) {
        $errors = []; $notes = []; $out = ['org'=>[], 'website'=>[], 'breadcrumb'=>[]];
        $orgIn = is_array($input['org'] ?? null) ? $input['org'] : [];
        $out['org']['name'] = sanitize_text_field($orgIn['name'] ?? '');
        $out['org']['url']  = esc_url_raw($orgIn['url'] ?? '');
        $out['org']['logo'] = esc_url_raw($orgIn['logo'] ?? '');
        $out['org']['logo_w'] = (string) (isset($orgIn['logo_w']) ? intval($orgIn['logo_w']) : '');
        $out['org']['logo_h'] = (string) (isset($orgIn['logo_h']) ? intval($orgIn['logo_h']) : '');
        $out['org']['telephone'] = sanitize_text_field($orgIn['telephone'] ?? '');
        $norm = $this->normalize_phone_jp($out['org']['telephone']); if ($norm) { $out['org']['telephone_e164']=$norm; if ($out['org']['telephone']!==$norm) $notes[]='電話番号をE.164形式（+81…）に整形しました。'; }
        $olang = $orgIn['inLanguage'] ?? 'ja'; $out['org']['inLanguage']=in_array($olang,['ja','en'],true)?$olang:'ja';
        $ja = is_array($orgIn['address']['ja'] ?? null) ? $orgIn['address']['ja'] : [];
        $out['org']['address']['ja']=['postalCode'=>preg_replace('/[^0-9]/','',$ja['postalCode']??''),'addressRegion'=>sanitize_text_field($ja['addressRegion']??''),'addressLocality'=>sanitize_text_field($ja['addressLocality']??''),'streetAddress'=>sanitize_text_field($ja['streetAddress']??''),'building'=>sanitize_text_field($ja['building']??''),'addressCountry'=>'JP'];
        $sameAs = $orgIn['sameAs'] ?? []; if (is_string($sameAs)) { $urls=array_filter(array_map('trim', preg_split('/\r?\n/', $sameAs))); $urls=array_map('esc_url_raw',$urls); $out['org']['sameAs']=array_values(array_filter($urls)); } elseif (is_array($sameAs)) { $out['org']['sameAs']=array_values(array_filter($sameAs)); } else { $out['org']['sameAs']=[]; }
        $cps = is_array($orgIn['contactPoint'] ?? null) ? $orgIn['contactPoint'] : []; $normCps=[];
        foreach ($cps as $cp){ $ct=sanitize_text_field(is_array($cp)?($cp['contactType']??''):''); $tel=sanitize_text_field(is_array($cp)?($cp['telephone']??''):''); $tel_e=$this->normalize_phone_jp($tel); $url=esc_url_raw(is_array($cp)?($cp['url']??''):''); $langs=sanitize_text_field(is_array($cp)?($cp['availableLanguage']??''):''); $langsArr=array_filter(array_map('trim', explode(',', $langs))); if ($ct||$tel||$url){ $row=['contactType'=>$ct]; if($tel_e)$row['telephone']=$tel_e; if($url)$row['url']=$url; if($langsArr)$row['availableLanguage']=$langsArr; $normCps[]=$row; } }
        $out['org']['contactPoint']=$normCps;
        $siteIn = is_array($input['website'] ?? null) ? $input['website'] : [];
        $out['website']['name']=sanitize_text_field($siteIn['name'] ?? ''); $out['website']['url']=esc_url_raw($siteIn['url'] ?? ''); $out['website']['alternateName']=sanitize_text_field($siteIn['alternateName'] ?? ''); $sl=$siteIn['inLanguage'] ?? 'ja'; $out['website']['inLanguage']=in_array($sl,['ja','en'],true)?$sl:'ja';
        $h1=parse_url($out['org']['url']?:'', PHP_URL_HOST); $h2=parse_url($out['website']['url']?:'', PHP_URL_HOST); if ($h1 && $h2 && $h1!==$h2) { $errors[]='Organization URL と WebSite URL は同じドメインが推奨です。'; }
        $brIn = is_array($input['breadcrumb'] ?? null) ? $input['breadcrumb'] : []; $out['breadcrumb']['homeLabel']=sanitize_text_field($brIn['homeLabel'] ?? 'HOME'); $out['breadcrumb']['useSiteTitle']=!empty($brIn['useSiteTitle'])?1:0;
        foreach ($errors as $msg) add_settings_error('ou_sd_group','ou_sd_warn',esc_html($msg),'error'); foreach ($notes as $msg) add_settings_error('ou_sd_group','ou_sd_note',esc_html($msg),'updated');
        return $out;
    }

    public function render(): void {
        if (!current_user_can('manage_options')) return;
        echo '<div class="wrap"><h1>OU Structured Data</h1>';
        echo '<form action="options.php" method="post">'; settings_fields('ou_sd_group'); do_settings_sections('ou-structured-data'); submit_button(); echo '</form></div>';
    }
}
