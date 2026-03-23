<?php
// ResultsController.php
namespace OU\QuizChat\Admin;

if (!defined('ABSPATH')) { exit; }

use WP_REST_Request;
use WP_REST_Response;
use OU\QuizChat\Core\Options;

class ResultsController
{
    const REST_NS = 'ouq/v1';

    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void
    {
        register_rest_route(self::REST_NS, '/results', [
            [
                'methods'  => 'GET',
                'callback' => [$this, 'get_results'],
                'permission_callback' => fn() => current_user_can(OUQ_CAP_EDIT),
            ],
            [
                'methods'  => 'POST',
                'callback' => [$this, 'save_results'],
                'permission_callback' => fn() => current_user_can(OUQ_CAP_EDIT),
                'args' => [
                    'nonce' => ['required' => true],
                ],
            ],
        ]);
    }

    public function get_results(WP_REST_Request $req): WP_REST_Response
    {
        $opt = new Options();
        $bands   = $opt->get('result_bands', []);
        $version = (int)($opt->get('result_bands_version', 0));
        $updated = (string)$opt->get('result_bands_updated_at', '');
        return new WP_REST_Response([
            'result_bands' => is_array($bands) ? array_values($bands) : [],
            'version'      => $version,
            'updated_at'   => $updated,
        ], 200);
    }

    public function save_results(WP_REST_Request $req): WP_REST_Response
    {
        $nonce = (string)$req->get_param('nonce');
        if (!wp_verify_nonce($nonce, 'ouq_admin_results')) {
            return new WP_REST_Response(['error' => 'invalid_nonce'], 403);
        }

        $body = json_decode($req->get_body(), true);
        if (!is_array($body)) {
            return new WP_REST_Response(['error' => 'invalid_body'], 400);
        }

        $incoming      = $body['result_bands'] ?? null;
        $clientVersion = isset($body['version']) ? (int)$body['version'] : null;
        if (!is_array($incoming)) {
            return new WP_REST_Response(['error' => 'invalid_result_bands'], 400);
        }

        $opt            = new Options();
        $currentVersion = (int)$opt->get('result_bands_version', 0);
        if ($clientVersion !== null && $clientVersion !== $currentVersion) {
            return new WP_REST_Response(['error' => 'version_conflict', 'current_version' => $currentVersion], 409);
        }

        // サニタイズ & 検証
        $bands = [];
        $order = 1;
        foreach ($incoming as $b) {
            $id    = isset($b['id']) ? sanitize_text_field($b['id']) : wp_generate_uuid4();
            $min   = isset($b['min']) ? (int)$b['min'] : 0;
            $max   = isset($b['max']) ? (int)$b['max'] : 0;
            $title = isset($b['title']) ? sanitize_text_field($b['title']) : '';
            $summary = isset($b['summary']) ? wp_kses($b['summary'], []) : '';

            $recommend_pages    = [];
            $recommend_features = [];

            if (!empty($b['recommend_pages']) && is_array($b['recommend_pages'])) {
                foreach ($b['recommend_pages'] as $p) {
                    $recommend_pages[] = sanitize_text_field($p);
                }
            }
            if (!empty($b['recommend_features']) && is_array($b['recommend_features'])) {
                foreach ($b['recommend_features'] as $f) {
                    $recommend_features[] = sanitize_text_field($f);
                }
            }

            $timeline = isset($b['estimate_timeline']) ? sanitize_text_field($b['estimate_timeline']) : '';
            $budget   = isset($b['estimate_budget'])   ? sanitize_text_field($b['estimate_budget'])   : '';
            $cta_label= isset($b['cta_label'])         ? sanitize_text_field($b['cta_label'])         : '';
            $cta_url  = isset($b['cta_url'])           ? esc_url_raw($b['cta_url'])                   : '';

            if ($title === '') {
                return new WP_REST_Response(['error' => 'empty_title'], 400);
            }
            if ($min > $max) {
                return new WP_REST_Response(['error' => 'min_gt_max'], 400);
            }

            $bands[] = [
                'id'                  => $id,
                'order'               => $order++,
                'min'                 => $min,
                'max'                 => $max,
                'title'               => $title,
                'summary'             => $summary,
                'recommend_pages'     => $recommend_pages,
                'recommend_features'  => $recommend_features,
                'estimate_timeline'   => $timeline,
                'estimate_budget'     => $budget,
                'cta_label'           => $cta_label,
                'cta_url'             => $cta_url,
            ];
        }

        // 帯の重複チェック（min/maxレンジ重なり防止）
        usort($bands, fn($a,$b)=>($a['min']<=>$b['min']) ?: ($a['max']<=>$b['max']));
        for ($i=1; $i<count($bands); $i++) {
            if ($bands[$i-1]['max'] >= $bands[$i]['min']) {
                return new WP_REST_Response(['error' => 'range_overlap'], 400);
            }
        }

        $now    = current_time('mysql');
        $newVer = $currentVersion + 1;
        $ok = $opt->update([
            'result_bands'            => $bands,
            'result_bands_version'    => $newVer,
            'result_bands_updated_at' => $now,
        ]);
        if (!$ok) {
            return new WP_REST_Response(['error' => 'save_failed'], 500);
        }

        return new WP_REST_Response([
            'ok'         => true,
            'version'    => $newVer,
            'updated_at' => $now,
        ], 200);
    }
}
