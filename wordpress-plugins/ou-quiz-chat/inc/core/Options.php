<?php
// Options.php
namespace OU\QuizChat\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 設定アクセスの集中管理
 * - 既定値の補完
 * - 型安全なゲッター
 * - 後続のAdmin UIからここ経由でCRUD
 */
class Options
{
    /** @var array */
    private $cache;

    public function __construct()
    {
        $this->cache = get_option(OUQ_OPTION_KEY);
        if (!is_array($this->cache)) {
            $this->cache = [];
        }
        // 既定値とマージ（ブートストラップの有効化時にも補完しているが二重で安全に）
        $this->cache = $this->merge_defaults($this->cache);
    }

    /**
     * 既定値（ブートストラップと同内容に追従）
     */
    private function defaults(): array
    {
        return [
            'consent_counts_in_progress' => false,
            'policy_url'                 => '',
            'brand_color'                => '#2563eb',
            'user_copy_enabled'          => true,
            'admin_recipients'           => [],
            'utm_cookie_days'            => 90,
            'failed_queue_days'          => 30,
            'db_persist_enabled'         => false,
            'post_send_reservation'      => [
                'enabled'     => false,
                'url'         => '',
                'label'       => '無料相談を予約する',
                'note'        => '',
                'score_bands' => [],
            ],
            'pre_input_notice'           => [
                'enabled' => true,
                'text'    => '診断結果をメールでお送りします。続いてお名前とご連絡先の入力をお願いします。',
            ],
            'post_send_notice'           => [
                'success_enabled' => true,
                'success_text'    => '診断結果を送信しました。{email} 宛てのメールをご確認ください。届かない場合は迷惑メールもご確認ください。',
                'failure_enabled' => true,
                'failure_text'    => '送信に失敗しました。お手数ですが時間をおいて再度お試しください。',
            ],
            OUQ_UNINSTALL_FLAG           => false,
        ];
    }

    private function merge_defaults(array $current): array
    {
        $defaults = $this->defaults();
        // 深いマージ（一次配列のみ+特定キーは再帰）
        foreach ($defaults as $k => $v) {
            if (!array_key_exists($k, $current)) {
                $current[$k] = $v;
            } elseif (is_array($v) && is_array($current[$k])) {
                $current[$k] = $current[$k] + $v; // 欠損キーだけ補完
            }
        }
        return $current;
    }

    public function all(): array
    {
        return $this->cache;
    }

    public function get(string $key, $default = null)
    {
        return array_key_exists($key, $this->cache) ? $this->cache[$key] : $default;
    }

    public function get_flag(string $key): bool
    {
        return !empty($this->cache[$key]);
    }

    /**
     * ネストキーの取得（例：post_send_reservation.url）
     */
    public function get_dot(string $dotKey, $default = null)
    {
        $ref = $this->cache;
        foreach (explode('.', $dotKey) as $seg) {
            if (!is_array($ref) || !array_key_exists($seg, $ref)) {
                return $default;
            }
            $ref = $ref[$seg];
        }
        return $ref;
    }

    /**
     * 更新（Admin UIから使用予定）
     */
    public function update(array $values): bool
    {
        // サニタイズの集中実装ポイント（当面は生値、後段で厳密化）
        $merged = $this->cache;
        foreach ($values as $k => $v) {
            $merged[$k] = $v;
        }
        $this->cache = $this->merge_defaults($merged);
        return update_option(OUQ_OPTION_KEY, $this->cache, false);
    }
}
