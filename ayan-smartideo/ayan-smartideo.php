<?php
/**
 * Plugin Name:       Smartideo 复刻版
 * Plugin URI:        https://github.com/JUYIKEJI/wordpress-smartideo
 * Description:       给 WordPress 文章加上在线视频支持：粘贴视频地址就能嵌进来，手机、平板上都是自适应播放。支持 YouTube、Vimeo、哔哩哔哩、优酷、腾讯视频，以及 mp4 / webm 直链。
 * Version:           1.0.0
 * Author:            JUYIKEJI
 * Author URI:        https://github.com/JUYIKEJI
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       ayan-smartideo
 * Requires at least: 5.0
 * Tested up to:      6.7
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'AYAN_SMARTIDEO_VERSION', '1.0.0' );

/* ------------------------------------------------------------------ */
/* 识别视频地址：认出是哪个站、视频编号是多少、嵌进去的地址是什么     */
/* ------------------------------------------------------------------ */

function ayan_smartideo_detect( $url ) {
    $url = trim( (string) $url );
    if ( '' === $url ) {
        return null;
    }

    // 直链视频文件：用浏览器自带的 HTML5 播放器
    if ( preg_match( '#\.(mp4|webm|ogv|ogg|m4v)(\?.*)?$#i', $url ) ) {
        return array( 'provider' => 'file', 'src' => $url );
    }

    // YouTube：watch、youtu.be、embed、shorts 都认
    if ( preg_match( '#(?:youtube\.com/(?:watch\?[^#]*v=|embed/|shorts/|v/)|youtu\.be/)([\w-]{6,})#i', $url, $m ) ) {
        return array(
            'provider' => 'youtube',
            'id'       => $m[1],
            'embed'    => 'https://www.youtube.com/embed/' . $m[1],
        );
    }

    // Vimeo
    if ( preg_match( '#vimeo\.com/(?:channels/[\w-]+/|groups/[\w-]+/videos/|album/\d+/video/|video/|)(\d+)#i', $url, $m ) ) {
        return array(
            'provider' => 'vimeo',
            'id'       => $m[1],
            'embed'    => 'https://player.vimeo.com/video/' . $m[1],
        );
    }

    // 哔哩哔哩：BV 号
    if ( preg_match( '#bilibili\.com/video/(BV[0-9A-Za-z]+)#i', $url, $m ) ) {
        return array(
            'provider' => 'bilibili',
            'id'       => $m[1],
            'embed'    => 'https://player.bilibili.com/player.html?bvid=' . $m[1],
        );
    }

    // 哔哩哔哩：av 号
    if ( preg_match( '#bilibili\.com/video/av(\d+)#i', $url, $m ) ) {
        return array(
            'provider' => 'bilibili',
            'id'       => $m[1],
            'embed'    => 'https://player.bilibili.com/player.html?aid=' . $m[1],
        );
    }

    // 优酷：v_show/id_xxx.html
    if ( preg_match( '#youku\.com/v_show/id_([A-Za-z0-9=_-]+)\.html#i', $url, $m ) ) {
        return array(
            'provider' => 'youku',
            'id'       => $m[1],
            'embed'    => 'https://player.youku.com/embed/' . $m[1],
        );
    }

    // 腾讯视频：取链接最后一段当 vid
    if ( preg_match( '#v\.qq\.com/x/(?:cover|page)/(?:[^/]+/)?([A-Za-z0-9]+)\.html#i', $url, $m ) ) {
        return array(
            'provider' => 'tencent',
            'id'       => $m[1],
            'embed'    => 'https://v.qq.com/txp/iframe/player.html?vid=' . $m[1],
        );
    }

    return null;
}

/* ------------------------------------------------------------------ */
/* 自动播放参数：每个站的写法不一样，这里按站拼                       */
/* ------------------------------------------------------------------ */

function ayan_smartideo_add_autoplay( $embed_url, $provider ) {
    $sep = ( false === strpos( $embed_url, '?' ) ) ? '?' : '&';
    switch ( $provider ) {
        case 'youtube':
            return $embed_url . $sep . 'autoplay=1&mute=1';
        case 'vimeo':
            return $embed_url . $sep . 'autoplay=1&muted=1';
        case 'bilibili':
            return $embed_url . $sep . 'autoplay=1';
        case 'youku':
        case 'tencent':
            return $embed_url . $sep . 'autoplay=true';
        default:
            return $embed_url . $sep . 'autoplay=1';
    }
}

/* ------------------------------------------------------------------ */
/* 生成嵌进去的播放器：短代码 [smartideo] 的真身                      */
/* ------------------------------------------------------------------ */

function ayan_smartideo_render( $atts ) {
    $atts = shortcode_atts(
        array(
            'src'      => '',
            'embed'    => '',
            'width'    => (int) get_option( 'ayan_smartideo_width', 640 ),
            'autoplay' => (int) get_option( 'ayan_smartideo_autoplay', 0 ),
            'ratio'    => '16:9',
        ),
        $atts,
        'smartideo'
    );

    $embed_url = trim( (string) $atts['embed'] );
    $src       = trim( (string) $atts['src'] );
    $is_file   = false;
    $provider  = '';

    if ( '' !== $embed_url ) {
        // 直接给嵌进去的地址：哪个站的嵌法都行，原样放进框里
        $embed_url = esc_url( $embed_url );
        $provider  = 'custom';
    } else {
        $video = ayan_smartideo_detect( $src );
        if ( ! $video ) {
            return '<!-- Smartideo：认不出这个视频地址：' . esc_html( $src ) . ' -->';
        }
        $provider = $video['provider'];
        if ( 'file' === $provider ) {
            $is_file   = true;
            $embed_url = esc_url( $video['src'] );
        } else {
            $embed_url = $video['embed'];
            if ( (int) $atts['autoplay'] ) {
                $embed_url = ayan_smartideo_add_autoplay( $embed_url, $provider );
            }
            $embed_url = esc_url( $embed_url );
        }
    }

    // 比例：默认 16:9，也可以写 4:3；框的高度按比例算出来
    $pad = 56.25;
    if ( preg_match( '#^(\d+)\s*:\s*(\d+)$#', trim( (string) $atts['ratio'] ), $rm ) && (int) $rm[1] > 0 && (int) $rm[2] > 0 ) {
        $pad = round( ( (int) $rm[2] / (int) $rm[1] ) * 100, 2 );
    }

    $max_width = max( 200, (int) $atts['width'] );
    $autoplay  = (int) $atts['autoplay'] ? ' autoplay muted' : '';

    // 样式直接写在标签上：不用等主题加载样式，放到哪儿都能播
    $wrap_style  = 'position:relative;width:100%;max-width:' . $max_width . 'px;margin:1em auto;padding-bottom:' . $pad . '%;height:0;overflow:hidden;background:#000;';
    $media_style = 'position:absolute;top:0;left:0;width:100%;height:100%;border:0;';

    if ( $is_file ) {
        $html = '<div class="ayan-smartideo" style="' . esc_attr( $wrap_style ) . '">'
            . '<video src="' . $embed_url . '" controls preload="metadata"' . $autoplay . ' style="' . esc_attr( $media_style ) . '"></video>'
            . '</div>';
    } else {
        $html = '<div class="ayan-smartideo" style="' . esc_attr( $wrap_style ) . '">'
            . '<iframe src="' . $embed_url . '" allowfullscreen allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" loading="lazy" style="' . esc_attr( $media_style ) . '"></iframe>'
            . '</div>';
    }

    return $html;
}
add_shortcode( 'smartideo', 'ayan_smartideo_render' );

/* ------------------------------------------------------------------ */
/* 单独成行的视频地址：自动转成播放器（比 WordPress 自带的嵌法先跑）  */
/* ------------------------------------------------------------------ */

function ayan_smartideo_autoembed( $content ) {
    if ( ! get_option( 'ayan_smartideo_autoembed', 1 ) ) {
        return $content;
    }

    $lines = explode( "\n", $content );
    foreach ( $lines as $i => $line ) {
        $trim = trim( $line );
        if ( '' === $trim ) {
            continue;
        }
        // 这一行如果含有 < 或 >，说明是 HTML，直接跳过
        if ( false !== strpos( $trim, '<' ) || false !== strpos( $trim, '>' ) ) {
            continue;
        }
        // 这一行就是一个纯链接吗
        if ( ! preg_match( '#^https?://\S+$#', $trim ) ) {
            continue;
        }
        if ( ! ayan_smartideo_detect( $trim ) ) {
            continue;
        }
        $lines[ $i ] = ayan_smartideo_render( array( 'src' => $trim ) );
    }

    return implode( "\n", $lines );
}
add_filter( 'the_content', 'ayan_smartideo_autoembed', 7 );

/* ------------------------------------------------------------------ */
/* 设置页：设置 → Smartideo                                           */
/* ------------------------------------------------------------------ */

function ayan_smartideo_register_settings() {
    register_setting(
        'ayan_smartideo',
        'ayan_smartideo_width',
        array(
            'type'              => 'integer',
            'default'           => 640,
            'sanitize_callback' => 'absint',
        )
    );
    register_setting(
        'ayan_smartideo',
        'ayan_smartideo_autoplay',
        array(
            'type'              => 'integer',
            'default'           => 0,
            'sanitize_callback' => 'absint',
        )
    );
    register_setting(
        'ayan_smartideo',
        'ayan_smartideo_autoembed',
        array(
            'type'              => 'integer',
            'default'           => 1,
            'sanitize_callback' => 'absint',
        )
    );
}
add_action( 'admin_init', 'ayan_smartideo_register_settings' );

function ayan_smartideo_settings_page() {
    add_options_page(
        'Smartideo 设置',
        'Smartideo',
        'manage_options',
        'ayan-smartideo',
        'ayan_smartideo_settings_html'
    );
}
add_action( 'admin_menu', 'ayan_smartideo_settings_page' );

function ayan_smartideo_settings_html() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <div class="wrap">
        <h1>Smartideo 设置</h1>
        <form method="post" action="options.php">
            <?php settings_fields( 'ayan_smartideo' ); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="ayan_smartideo_width">默认宽度</label></th>
                    <td><input name="ayan_smartideo_width" type="number" id="ayan_smartideo_width" value="<?php echo esc_attr( get_option( 'ayan_smartideo_width', 640 ) ); ?>" class="small-text"> 像素</td>
                </tr>
                <tr>
                    <th scope="row">自动播放</th>
                    <td>
                        <input type="hidden" name="ayan_smartideo_autoplay" value="0">
                        <label><input name="ayan_smartideo_autoplay" type="checkbox" value="1" <?php checked( get_option( 'ayan_smartideo_autoplay', 0 ), 1 ); ?>> 默认自动播放（浏览器一般要求静音才能自动播）</label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">自动嵌视频</th>
                    <td>
                        <input type="hidden" name="ayan_smartideo_autoembed" value="0">
                        <label><input name="ayan_smartideo_autoembed" type="checkbox" value="1" <?php checked( get_option( 'ayan_smartideo_autoembed', 1 ), 1 ); ?>> 文章里单独成行的视频地址，自动转成播放器</label>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <h2>用法</h2>
        <p>在文章里写短代码，src 后面填视频页面的地址：</p>
        <p><code>[smartideo src="https://www.youtube.com/watch?v=xxxx"]</code></p>
        <p><code>[smartideo src="https://www.bilibili.com/video/BVxxxx"]</code></p>
        <p>也可以直接把视频地址单独放一行，不用写短代码，插件会自动转成播放器。还支持直接给嵌进去的地址（哪个站的嵌法都行）：</p>
        <p><code>[smartideo embed="https://player.bilibili.com/player.html?bvid=BVxxxx"]</code></p>
        <p>支持的站：YouTube、Vimeo、哔哩哔哩、优酷、腾讯视频，以及 mp4 / webm 直链。</p>
    </div>
    <?php
}
