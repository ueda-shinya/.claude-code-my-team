<?php
namespace OU\StructuredData\PublicSite\Shortcodes;

class FAQShortcode {
  public function register(): void {
    add_shortcode('ou_faq', [$this,'render']);
  }
  public function render($atts = [], $content = ''): string {
    $html = ''; $qas = [];
    if (!empty($content)) {
      preg_match_all('#\[q\](.*?)\[/q\]\s*\[a\](.*?)\[/a\]#is', $content, $m, PREG_SET_ORDER);
      if ($m) {
        $html .= '<div class="ou-faq">';
        foreach ($m as $pair) {
          $q = wp_kses_post($pair[1]);
          $a = wp_kses_post($pair[2]);
          $qas[] = ['q'=>wp_strip_all_tags($q),'a'=>wpautop($a)];
          $html .= '<div class="ou-faq-item"><div class="ou-faq-q"><strong>'.esc_html(wp_strip_all_tags($q)).'</strong></div><div class="ou-faq-a">'.wpautop($a).'</div></div>';
        }
        $html .= '</div>';
      }
    }
    if (!empty($qas)) {
      set_transient('ou_sd_faq_qas_'.get_the_ID(), $qas, HOUR_IN_SECONDS);
    }
    return $html;
  }
}
