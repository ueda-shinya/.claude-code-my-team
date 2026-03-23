<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function jsonld_sd_output_jsonld() {
  if ( ! is_singular() ) return;

  global $post;
  $options = jsonld_sd_get_options();

  if ( ! in_array( get_post_type( $post ), $options['post_types'], true ) ) return;

  $title = get_the_title( $post );
  $url = get_permalink( $post );
  $datePublished = get_the_date( 'c', $post );
  $dateModified = get_the_modified_date( 'c', $post );
  $author = get_the_author_meta( 'display_name', $post->post_author );

  $excerpt = get_the_excerpt( $post );
  if ( empty( $excerpt ) && $options['use_excerpt_fallback'] ) {
    $content = strip_tags( strip_shortcodes( $post->post_content ) );
    $excerpt = mb_substr( trim( $content ), 0, 100 ) . '...';
  }

  $featured_image = get_the_post_thumbnail_url( $post, 'full' );
  if ( empty( $featured_image ) ) {
    if ( preg_match( '/<img.+?src=["\'](.+?)["\'].*?>/i', $post->post_content, $matches ) ) {
      $featured_image = $matches[1];
    } else {
      $featured_image = $options['default_image'];
    }
  }

  $logo_url = $options['logo_url'] ?: get_site_icon_url();

  $data = [
    '@context' => 'https://schema.org',
    '@type' => 'BlogPosting',
    'mainEntityOfPage' => [
      '@type' => 'WebPage',
      '@id' => $url,
    ],
    'headline' => $title,
    'description' => $excerpt,
    'image' => $featured_image,
    'author' => [
      '@type' => 'Person',
      'name' => $author,
    ],
    'publisher' => [
      '@type' => 'Organization',
      'name' => $options['publisher_name'],
      'logo' => [
        '@type' => 'ImageObject',
        'url' => $logo_url,
      ]
    ],
    'datePublished' => $datePublished,
    'dateModified' => $dateModified,
  ];

  if ($options['accessibility_flag'] === 'free') {
    $data['mainEntityOfPage']['isAccessibleForFree'] = true;
  } elseif ($options['accessibility_flag'] === 'paid') {
    $data['mainEntityOfPage']['isAccessibleForFree'] = false;
  }

  $data = apply_filters('jsonld_sd_structured_data', $data, $post);

  echo '<script type="application/ld+json">' . wp_json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>' . "\n";
}
add_action( 'wp_head', 'jsonld_sd_output_jsonld' );
