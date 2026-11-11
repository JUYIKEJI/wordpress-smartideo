<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

delete_option( 'ayan_smartideo_width' );
delete_option( 'ayan_smartideo_autoplay' );
delete_option( 'ayan_smartideo_autoembed' );
