<?php
if (!defined('ABSPATH')) exit;


class OU_PMP_PDF
{
    public static function init()
    {
        // 物件保存時に試行（media_sheet_jpg未設定＆media_pdf有）
        add_action('acf/save_post', [__CLASS__, 'maybe_convert_pdf'], 30);
    }


    private static function can_convert_pdf()
    {
        if (!class_exists('Imagick')) return false;
        $formats = method_exists('Imagick', 'queryFormats') ? Imagick::queryFormats('PDF') : [];
        return in_array('PDF', (array)$formats, true);
    }


    public static function maybe_convert_pdf($post_id)
    {
        if (get_post_type($post_id) !== 'property') return;
        $jpg_id = (int)get_field('media_sheet_jpg', $post_id);
        $pdf_id = (int)get_field('media_pdf', $post_id);
        if ($jpg_id || !$pdf_id) return;
        if (!self::can_convert_pdf()) return; // 環境非対応なら何もしない


        $path = get_attached_file($pdf_id);
        if (!$path || !file_exists($path)) return;


        try {
            $im = new Imagick();
            $im->setResolution(160, 160);
            $im->readImage($path . '[0]'); // 1ページ目
            $im->setImageFormat('jpeg');
            $im->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
            $im->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
            $im->setImageCompression(Imagick::COMPRESSION_JPEG);
            $im->setImageCompressionQuality(82);
            $im->thumbnailImage(1600, 0);
            $blob = $im->getImageBlob();
            $im->clear();
            $im->destroy();


            $upload = wp_upload_bits('property_sheet_' . $post_id . '_' . time() . '.jpg', null, $blob);
            if (!empty($upload['error'])) return;
            $attach = [
                'post_mime_type' => 'image/jpeg',
                'post_title' => '媒体画像 自動生成',
                'post_content' => '',
                'post_status' => 'inherit'
            ];
            $attach_id = wp_insert_attachment($attach, $upload['file'], $post_id);
            require_once ABSPATH . 'wp-admin/includes/image.php';
            $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
            wp_update_attachment_metadata($attach_id, $attach_data);


            update_field('media_sheet_jpg', $attach_id, $post_id);
        } catch (Throwable $e) {
            // 失敗時は黙ってスキップ（管理者向けログに残したい場合はerror_logへ）
            // error_log('[OUPM] PDF convert failed: '.$e->getMessage());
        }
    }
}
