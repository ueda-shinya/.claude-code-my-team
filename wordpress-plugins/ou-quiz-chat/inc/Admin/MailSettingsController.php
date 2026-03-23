<?php
// inc/Admin/MailSettingsController.php
namespace OU\QuizChat\Admin;

if (!defined('ABSPATH')) { exit; }

use WP_REST_Request;
use WP_REST_Response;
use OU\QuizChat\Core\Options;

class MailSettingsController
{
    const REST_NS = 'ouq/v1';

    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void
    {
        register_rest_route(self::REST_NS, '/mail-settings', [
            [
                'methods'  => 'GET',
                'callback' => [$this, 'get_settings'],
                'permission_callback' => fn()=> current_user_can(OUQ_CAP_EDIT),
            ],
            [
                'methods'  => 'POST',
                'callback' => [$this, 'save_settings'],
                'permission_callback' => fn()=> current_user_can(OUQ_CAP_EDIT),
                'args' => ['nonce' => ['required'=>true]],
            ],
        ]);
    }

    public function get_settings(WP_REST_Request $req): WP_REST_Response
    {
        $opt = new Options();
        $data = [
            'admin_recipients' => $opt->get('admin_recipients', []),
            'from_name'        => (string)$opt->get('from_name', get_bloginfo('name')),
            'from_email'       => (string)$opt->get('from_email', 'no-reply@' . parse_url(home_url(), PHP_URL_HOST)),
            'user_copy_enabled'=> (bool)$opt->get_flag('user_copy_enabled'),
            'pre_input_notice' => [
                'text' => (string)$opt->get_dot('pre_input_notice.text', '診断結果をメールでお送りします。')
            ],
            'post_send_notice' => [
                'success_text' => (string)$opt->get_dot('post_send_notice.success_text', '診断結果を送信しました。{email} 宛のメールをご確認ください。'),
                'error_text'   => (string)$opt->get_dot('post_send_notice.error_text',   '送信に失敗しました。時間をおいて再度お試しください。')
            ],
            'post_send_reservation' => [
                'enabled' => (bool)$opt->get_dot('post_send_reservation.enabled', false),
                'url'     => (string)$opt->get_dot('post_send_reservation.url', ''),
                'label'   => (string)$opt->get_dot('post_send_reservation.label', '無料相談を予約する'),
            ],
        ];
        return new WP_REST_Response($data, 200);
    }

    public function save_settings(WP_REST_Request $req): WP_REST_Response
    {
        $nonce = (string)$req->get_param('nonce');
        if (!wp_verify_nonce($nonce, 'ouq_admin_mail')) {
            return new WP_REST_Response(['error'=>'invalid_nonce'], 403);
        }

        $body = json_decode($req->get_body(), true);
        if (!is_array($body)) {
            return new WP_REST_Response(['error'=>'invalid_body'], 400);
        }

        $recipients = [];
        if (!empty($body['admin_recipients']) && is_array($body['admin_recipients'])) {
            foreach ($body['admin_recipients'] as $m) {
                $m = sanitize_email($m);
                if ($m && is_email($m)) $recipients[] = $m;
            }
        }

        $from_name  = sanitize_text_field($body['from_name']  ?? get_bloginfo('name'));
        $from_email = sanitize_email($body['from_email'] ?? '');
        if (!$from_email || !is_email($from_email)) {
            $from_email = 'no-reply@' . parse_url(home_url(), PHP_URL_HOST);
        }

        $user_copy_enabled = !empty($body['user_copy_enabled']);

        $pre_text   = sanitize_text_field($body['pre_input_notice']['text'] ?? '診断結果をメールでお送りします。');
        $succ_text  = sanitize_text_field($body['post_send_notice']['success_text'] ?? '診断結果を送信しました。{email} 宛のメールをご確認ください。');
        $err_text   = sanitize_text_field($body['post_send_notice']['error_text']   ?? '送信に失敗しました。時間をおいて再度お試しください。');

        $res_enabled= !empty($body['post_send_reservation']['enabled']);
        $res_url    = esc_url_raw($body['post_send_reservation']['url'] ?? '');
        $res_label  = sanitize_text_field($body['post_send_reservation']['label'] ?? '無料相談を予約する');

        $opt = new Options();
        $ok  = $opt->update([
            'admin_recipients'                 => $recipients,
            'from_name'                        => $from_name,
            'from_email'                       => $from_email,
            'user_copy_enabled'                => $user_copy_enabled ? 1 : 0,
            'pre_input_notice'                 => ['text'=>$pre_text],
            'post_send_notice'                 => ['success_text'=>$succ_text, 'error_text'=>$err_text],
            'post_send_reservation'            => ['enabled'=>$res_enabled?1:0, 'url'=>$res_url, 'label'=>$res_label],
        ]);

        if (!$ok) return new WP_REST_Response(['error'=>'save_failed'], 500);
        return new WP_REST_Response(['ok'=>true], 200);
    }
}
