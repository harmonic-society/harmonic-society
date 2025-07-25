<?php
/**
 * SEO関連の機能
 *
 * @package Corporate_SEO_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * メタタグの出力
 */
function corporate_seo_pro_meta_tags() {
    global $post;
    
    // メタディスクリプション
    $description = '';
    if ( is_singular() ) {
        $description = get_post_meta( $post->ID, '_corporate_seo_meta_description', true );
        if ( empty( $description ) ) {
            $description = wp_trim_words( strip_tags( $post->post_content ), 30, '...' );
        }
    } elseif ( is_category() || is_tag() ) {
        $description = term_description();
    } elseif ( is_home() || is_front_page() ) {
        $description = get_bloginfo( 'description' );
    }
    
    if ( ! empty( $description ) ) {
        echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
    }
    
    // OGPタグ
    if ( is_singular() ) {
        echo '<meta property="og:title" content="' . esc_attr( get_the_title() ) . '">' . "\n";
        echo '<meta property="og:type" content="article">' . "\n";
        echo '<meta property="og:url" content="' . esc_url( get_permalink() ) . '">' . "\n";
        
        if ( has_post_thumbnail() ) {
            $thumbnail_id = get_post_thumbnail_id();
            $thumbnail_url = wp_get_attachment_image_src( $thumbnail_id, 'large' );
            echo '<meta property="og:image" content="' . esc_url( $thumbnail_url[0] ) . '">' . "\n";
        }
        
        if ( ! empty( $description ) ) {
            echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
        }
    }
    
    echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";
    echo '<meta property="og:locale" content="ja_JP">' . "\n";
    
    // Twitter Card
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    if ( get_theme_mod( 'twitter_username' ) ) {
        echo '<meta name="twitter:site" content="@' . esc_attr( get_theme_mod( 'twitter_username' ) ) . '">' . "\n";
    }
    
    // Canonical URL
    if ( is_singular() ) {
        echo '<link rel="canonical" href="' . esc_url( get_permalink() ) . '">' . "\n";
    }
}
add_action( 'wp_head', 'corporate_seo_pro_meta_tags', 1 );

/**
 * robots.txtの最適化
 */
function corporate_seo_pro_robots_txt( $output, $public ) {
    if ( '1' == $public ) {
        $output .= "Sitemap: " . home_url( '/sitemap.xml' ) . "\n";
        $output .= "Sitemap: " . home_url( '/sitemap_index.xml' ) . "\n";
    }
    return $output;
}
add_filter( 'robots_txt', 'corporate_seo_pro_robots_txt', 10, 2 );

/**
 * XMLサイトマップのサポート
 */
function corporate_seo_pro_sitemap_support() {
    add_filter( 'wp_sitemaps_add_provider', function( $provider, $name ) {
        if ( 'users' === $name ) {
            return false;
        }
        return $provider;
    }, 10, 2 );
}
add_action( 'init', 'corporate_seo_pro_sitemap_support' );

/**
 * メタボックスの追加
 */
function corporate_seo_pro_add_meta_boxes() {
    add_meta_box(
        'corporate_seo_meta',
        __( 'SEO設定', 'corporate-seo-pro' ),
        'corporate_seo_pro_meta_box_callback',
        array( 'post', 'page', 'service' ),
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'corporate_seo_pro_add_meta_boxes' );

/**
 * メタボックスのコールバック
 */
function corporate_seo_pro_meta_box_callback( $post ) {
    wp_nonce_field( 'corporate_seo_pro_save_meta_box_data', 'corporate_seo_pro_meta_box_nonce' );
    
    $meta_description = get_post_meta( $post->ID, '_corporate_seo_meta_description', true );
    $meta_keywords = get_post_meta( $post->ID, '_corporate_seo_meta_keywords', true );
    $noindex = get_post_meta( $post->ID, '_corporate_seo_noindex', true );
    ?>
    <p>
        <label for="corporate_seo_meta_description"><?php _e( 'メタディスクリプション', 'corporate-seo-pro' ); ?></label>
        <textarea id="corporate_seo_meta_description" name="corporate_seo_meta_description" rows="3" style="width:100%;"><?php echo esc_textarea( $meta_description ); ?></textarea>
        <span class="description"><?php _e( '検索結果に表示される説明文です。160文字以内を推奨します。', 'corporate-seo-pro' ); ?></span>
    </p>
    <p>
        <label for="corporate_seo_meta_keywords"><?php _e( 'メタキーワード', 'corporate-seo-pro' ); ?></label>
        <input type="text" id="corporate_seo_meta_keywords" name="corporate_seo_meta_keywords" value="<?php echo esc_attr( $meta_keywords ); ?>" style="width:100%;">
        <span class="description"><?php _e( 'カンマ区切りでキーワードを入力してください。', 'corporate-seo-pro' ); ?></span>
    </p>
    <p>
        <label>
            <input type="checkbox" name="corporate_seo_noindex" value="1" <?php checked( $noindex, '1' ); ?>>
            <?php _e( '検索エンジンにインデックスさせない（noindex）', 'corporate-seo-pro' ); ?>
        </label>
    </p>
    <?php
}

/**
 * メタボックスの保存
 */
function corporate_seo_pro_save_meta_box_data( $post_id ) {
    if ( ! isset( $_POST['corporate_seo_pro_meta_box_nonce'] ) ) {
        return;
    }
    
    if ( ! wp_verify_nonce( $_POST['corporate_seo_pro_meta_box_nonce'], 'corporate_seo_pro_save_meta_box_data' ) ) {
        return;
    }
    
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    
    if ( isset( $_POST['corporate_seo_meta_description'] ) ) {
        update_post_meta( $post_id, '_corporate_seo_meta_description', sanitize_textarea_field( $_POST['corporate_seo_meta_description'] ) );
    }
    
    if ( isset( $_POST['corporate_seo_meta_keywords'] ) ) {
        update_post_meta( $post_id, '_corporate_seo_meta_keywords', sanitize_text_field( $_POST['corporate_seo_meta_keywords'] ) );
    }
    
    update_post_meta( $post_id, '_corporate_seo_noindex', isset( $_POST['corporate_seo_noindex'] ) ? '1' : '' );
}
add_action( 'save_post', 'corporate_seo_pro_save_meta_box_data' );

/**
 * noindexの処理
 */
function corporate_seo_pro_noindex() {
    if ( is_singular() ) {
        global $post;
        $noindex = get_post_meta( $post->ID, '_corporate_seo_noindex', true );
        if ( $noindex ) {
            echo '<meta name="robots" content="noindex,follow">' . "\n";
        }
    }
}
add_action( 'wp_head', 'corporate_seo_pro_noindex' );

/**
 * 画像の自動alt属性追加
 */
function corporate_seo_pro_auto_image_alt( $attr, $attachment ) {
    if ( empty( $attr['alt'] ) ) {
        $attr['alt'] = get_the_title( $attachment->ID );
    }
    return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'corporate_seo_pro_auto_image_alt', 10, 2 );

/**
 * タイトルタグのカスタマイズ
 */
function corporate_seo_pro_document_title_parts( $title ) {
    if ( is_home() || is_front_page() ) {
        unset( $title['tagline'] );
    }
    
    if ( is_category() ) {
        $title['title'] = single_cat_title( '', false ) . ' | カテゴリー';
    }
    
    if ( is_tag() ) {
        $title['title'] = single_tag_title( '', false ) . ' | タグ';
    }
    
    if ( is_search() ) {
        $title['title'] = '「' . get_search_query() . '」の検索結果';
    }
    
    if ( is_404() ) {
        $title['title'] = '404 ページが見つかりません';
    }
    
    return $title;
}
add_filter( 'document_title_parts', 'corporate_seo_pro_document_title_parts' );

/**
 * タイトルセパレーターの変更
 */
function corporate_seo_pro_document_title_separator( $sep ) {
    return '|';
}
add_filter( 'document_title_separator', 'corporate_seo_pro_document_title_separator' );