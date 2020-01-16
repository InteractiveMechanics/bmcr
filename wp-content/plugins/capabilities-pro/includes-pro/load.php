<?php
require_once(PUBLISHPRESS_CAPS_ABSPATH . '/includes-pro/classes/Pro.php');

add_action('init', function(){
    if (!empty($_REQUEST['publishpress_caps_ajax_settings'])) {
        include_once(PUBLISHPRESS_CAPS_ABSPATH . '/includes-pro/pro-activation-ajax.php');
    }
});

function publishpress_caps_pro() {
    return \PublishPress\Capabilities\Pro::instance();
}
