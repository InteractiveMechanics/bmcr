<?php
function _presspermit_key_status($refresh = false) {
    $opt_val = presspermit()->getOption('edd_key');
    
    //if (!is_array($opt_val) || count($opt_val) < 2) {
    if (!$refresh && (!is_array($opt_val) || count($opt_val) < 2 || !isset($opt_val['license_key']))) {
        return false;
    } else {
        if ($refresh) {
            require_once(PRESSPERMIT_ABSPATH . '/includes-pro/library/Factory.php');
            $container      = \PublishPress\Permissions\Factory::get_container();
            $licenseManager = $container['edd_container']['license_manager'];

            $key = $licenseManager->sanitize_license_key($opt_val['license_key']);
            $status = $licenseManager->validate_license_key($key, PRESSPERMIT_EDD_ITEM_ID);

            if (!is_scalar($status)) {
                return false;
            }

            $opt_val['license_status'] = $status;
            presspermit()->updateOption('edd_key', $opt_val);

            if ('valid' == $status) {
                return true;
            } elseif('expired' == $status) {
                return 'expired';
            }
        } else {
            if ('valid' == $opt_val['license_status']) {
                return true;
            } elseif ('expired' == $opt_val['license_status']) {
                return 'expired';
            }
        }
    }

    return false;
}