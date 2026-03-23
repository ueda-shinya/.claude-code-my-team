<?php
// QuestionsController.php
namespace OU\QuizChat\Admin;

if (!defined('ABSPATH')) { exit; }

use WP_REST_Request;
use WP_REST_Response;
use OU\QuizChat\Core\Options;

class QuestionsController
{
    const REST_NS = 'ouq/v1';

    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void
    {
        register_rest_route(self::REST_NS, '/questions', [
            [
                'methods'  => 'GET',
                'callback' => [$this, 'get_questions'],
                'permission_callback' => fn() => current_user_can(OUQ_CAP_EDIT),
            ],
            [
                'methods'  => 'POST',
                'callback' => [$this, 'save_questions'],
                'permission_callback' => fn() => current_user_can(OUQ_CAP_EDIT),
                'args' => [
                    'nonce' => ['required' => true],
                ],
            ],
        ]);
    }

    public function get_questions(WP_REST_Request $req): WP_REST_Response
    {
        $opt = new Options();
        $data = $opt->get('questions', []);
        $version = (int)($opt->get('questions_version', 0));
        $updated = (string)$opt->get('questions_updated_at', '');
        return new WP_REST_Response([
            'questions'   => is_array($data) ? array_values($data) : [],
            'version'     => $version,
            'updated_at'  => $updated,
        ], 200);
    }

    public function save_questions(WP_REST_Request $req): WP_REST_Response
    {
        // Nonce（管理UI専用）
        $nonce = (string)$req->get_param('nonce');
        if (!wp_verify_nonce($nonce, 'ouq_admin_questions')) {
            return new WP_REST_Response(['error' => 'invalid_nonce'], 403);
        }

        $body = json_decode($req->get_body(), true);
        if (!is_array($body)) {
            return new WP_REST_Response(['error' => 'invalid_body'], 400);
        }

        $incoming = $body['questions'] ?? null;
        $clientVersion = isset($body['version']) ? (int)$body['version'] : null;
        if (!is_array($incoming)) {
            return new WP_REST_Response(['error' => 'invalid_questions'], 400);
        }

        // 既存バージョンとの差異（楽観ロック）
        $opt = new Options();
        $currentVersion = (int)$opt->get('questions_version', 0);
        if ($clientVersion !== null && $clientVersion !== $currentVersion) {
            return new WP_REST_Response(['error' => 'version_conflict', 'current_version' => $currentVersion], 409);
        }

        // サニタイズ & 検証
        $questions = [];
        $order = 1;
        foreach ($incoming as $q) {
            $qid   = isset($q['id']) ? sanitize_text_field($q['id']) : wp_generate_uuid4();
            $text  = isset($q['text']) ? wp_kses($q['text'], []) : '';
            $type  = ($q['type'] ?? 'single') === 'multi' ? 'multi' : 'single';
            $reqd  = !empty($q['required']);
            $cap   = isset($q['score_cap']) && $q['score_cap'] !== '' ? max(0, (int)$q['score_cap']) : null;

            $choices = [];
            if (isset($q['choices']) && is_array($q['choices'])) {
                foreach ($q['choices'] as $c) {
                    $cid    = isset($c['id']) ? sanitize_text_field($c['id']) : wp_generate_uuid4();
                    $label  = isset($c['label']) ? sanitize_text_field($c['label']) : '';
                    $score  = isset($c['score']) ? (int)$c['score'] : 0;

                    if ($label === '') {
                        return new WP_REST_Response(['error' => 'empty_choice_label'], 400);
                    }
                    $choices[] = [
                        'id'    => $cid,
                        'label' => $label,
                        'score' => $score,
                    ];
                }
            }

            if ($text === '') {
                return new WP_REST_Response(['error' => 'empty_question_text'], 400);
            }
            if (count($choices) === 0) {
                return new WP_REST_Response(['error' => 'question_without_choices'], 400);
            }

            $questions[] = [
                'id'         => $qid,
                'order'      => $order++,
                'text'       => $text,
                'type'       => $type,
                'required'   => $reqd,
                'score_cap'  => $cap,
                'choices'    => $choices,
            ];
        }

        // 保存
        $now    = current_time('mysql');
        $newVer = $currentVersion + 1;
        $ok = $opt->update([
            'questions'            => $questions,
            'questions_version'    => $newVer,
            'questions_updated_at' => $now,
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
