<?php
namespace PublishPress\Permissions;

class AdminLoadPro {
    function __construct() {
        add_filter('presspermit_default_options', [$this, 'defaultOptions']);
        add_filter('presspermit_netwide_options', [$this, 'netwideOptions']);
        add_filter('presspermit_option_captions', [$this, 'optionCaptions'], 20);
        add_filter('presspermit_option_sections', [$this, 'optionSections'], 20);
    }

    public function defaultOptions($options) {
        $options['display_branding'] = 1;
        return $options;
    }

    public function netwideOptions($netwide) {
        $netwide []= 'display_branding';
        return $netwide;
    }
 
    public function optionCaptions($captions)
    {
        $opt = [
            'display_branding' => __('Display PublishPress Branding in Admin', 'presspermit-pro'),
        ];

        return array_merge($captions, $opt);
    }

    public function optionSections($sections)
    {
        $new = [
            'admin' => ['display_branding'],
        ];

        $key = 'core';

        if (isset($sections[$key]['admin'])) {
            $sections[$key]['admin'] = array_merge($sections[$key]['admin'], $new['admin']);
        } else {
            $sections[$key] = (isset($sections[$key])) ? array_merge($sections[$key], $new) : $new;
        }

        return $sections;
    }
}
