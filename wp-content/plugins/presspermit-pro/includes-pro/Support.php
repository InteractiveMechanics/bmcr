<?php

namespace PublishPress\Permissions;

class Support
{
    public static function supportUpload($args = [])
    {
        $request_vars = [
            'site' => site_url(''),
        ];

        global $wpdb;

        $pp = presspermit();
        $is_multisite = is_multisite();

        $ok = (array)$pp->getOption('support_data');

        $pp_config = [];
        $pp_old = [];

        $site_options = $pp->site_options;

        $options = [];
        foreach ([
                    'default_category', 'permalink_structure', '_bbp_default_role', '_bbp_private_forums',
                    '_bbp_use_wp_editor', '_bbp_allow_anonymous', '_bbp_allow_global_access', '_bbp_db_version',
                    'bp-active-components', 'users_can_register', 'comment_moderation', 'comment_registration',
                    'registration', 'default_role', 'db_version', 'enable_app', 'enable_xmlrpc', 'sticky_posts',
                    'initial_db_version',
                ] as $opt) {
            $options[$opt] = get_option($opt);
        }

        ksort($options);
        $pp_config['options'] = gzcompress(serialize($options));

        ksort($site_options);
        $pp_config['pp_options'] = gzcompress(serialize($site_options));

        $pp_config['rvy_options'] = gzcompress(
            serialize($wpdb->get_results(
                "SELECT option_name, option_value, option_id FROM $wpdb->options WHERE option_name LIKE 'rvy_%' ORDER BY option_name",
                ARRAY_N
            ))
        );

        if ($is_multisite) {
            $netwide_options = $pp->netwide_options;
            $net_options = $pp->net_options;

            if (is_array($net_options)) {
                ksort($net_options);
                if (!empty($net_options))
                    $pp_config['pp_net_options'] = gzcompress(serialize($net_options));
            }

            ksort($netwide_options);
            if (!empty($netwide_options))
                $pp_config['presspermit_netwide_options'] = gzcompress(serialize($netwide_options));


            $sitemeta_table = $wpdb->base_prefix . 'sitemeta';
            if ($rvy_net_options = $wpdb->get_results(
                "SELECT meta_key, meta_value, site_id, meta_id FROM $sitemeta_table WHERE meta_key LIKE 'rvy_%' ORDER BY meta_key",
                ARRAY_N
            )) {
                $pp_config['rvy_net_options'] = gzcompress(serialize($rvy_net_options));
            }
        }

        if (!empty($ok['wp_roles_types'])) {
            global $wp_post_types, $wp_taxonomies, $wp_post_statuses, $wp_roles;

            // strip labels, label_count props

            $pp_config['wp_roles'] = gzcompress(serialize($wp_roles));

            // strip out labels and some other properties for perf
            foreach (['wp_post_types', 'wp_taxonomies', 'wp_post_statuses'] as $var) {
                $wp_data = $$var;
                $arr = [];
                foreach (array_keys($wp_data) as $member) {
                    $arr[$member] = [];

                    foreach (array_keys(get_object_vars($wp_data[$member])) as $prop) {
                        if (!in_array($prop, ['labels', 'label_count', 'can_export', 'description'], true))
                            $arr[$member][$prop] = $wp_data[$member]->$prop;
                    }
                }
                $pp_config[$var] = gzcompress(serialize($arr));
            }
        }

        if (!empty($ok['theme'])) {
            $th = wp_get_theme();
            $theme_data = [];
            foreach (['name', 'title', 'version', 'parent_theme', 'template'] as $prop)
                $theme_data[$prop] = $th->$prop;

            $theme_data['errors'] = $th->errors();
            $pp_config['theme'] = gzcompress(serialize($theme_data));

            $pp_config['widgets'] = gzcompress(serialize((array)get_option('sidebars_widgets')));
        }

        if (file_exists(ABSPATH . 'wp-admin/includes/plugin.php'))
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');

        if (!empty($ok['active_plugins']) && function_exists('get_plugin_data')) {
            $active_plugins = [];
            foreach (wp_get_active_and_valid_plugins() as $file) {
                // reduce to relative path for privacy
                $slug = [];
                if ($part = @basename(dirname(dirname(dirname($file)))))
                    $slug[] = $part;

                if ($part = @basename(dirname(dirname($file))))
                    $slug[] = $part;

                if ($part = @basename(dirname($file)))
                    $slug[] = $part;

                $slug[] = basename($file);
                $slug = implode('/', $slug);

                $active_plugins[$slug] = array_diff_key(
                    get_plugin_data($file),
                    array_fill_keys(['Author', 'AuthorURI', 'TextDomain', 'DomainPath', 'Title', 'AuthorName', 'Description'], true)
                );
            }

            $pp_config['active_plugins'] = gzcompress(serialize($active_plugins));

            if (function_exists('get_dropins')) {
                $pp_config['dropins'] = gzcompress(serialize(get_dropins()));
            }
        }

        if (!empty($ok['installed_plugins']) && function_exists('get_plugins')) {
            if ($installed_plugins = get_plugins()) {
                foreach (array_keys($installed_plugins) as $key) {
                    $installed_plugins[$key] = array_diff_key(
                        $installed_plugins[$key],
                        array_fill_keys(['Author', 'AuthorURI', 'TextDomain', 'DomainPath', 'Title', 'AuthorName', 'Description', 'PluginURI', 'Network'], true)
                    );
                }
                $pp_config['installed_plugins'] = gzcompress(serialize($installed_plugins));
            }
        }

        // if uploading for a specific post or term
        if (!empty($args['term_taxonomy_id']) && !empty($ok['post_data'])) {
            $pp_config['term_data'] = gzcompress(
                serialize($wpdb->get_results($wpdb->prepare(
                    "SELECT term_taxonomy_id, taxonomy, term_id FROM $wpdb->term_taxonomy WHERE term_taxonomy_id = %d",
                    $args['term_taxonomy_id']
                ), ARRAY_A))
            );
        }

        if (!empty($args['post_id']) && !empty($ok['post_data'])) {
            $pp_config['post_data'] = gzcompress(
                serialize($wpdb->get_row($wpdb->prepare(
                    "SELECT ID, post_type, post_author, post_status, post_parent FROM $wpdb->posts WHERE ID = %d LIMIT 1",
                    $args['post_id']
                )))
            );

            $post_terms = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT tr.term_taxonomy_id, tr.object_id, tt.taxonomy, tt.term_id FROM $wpdb->term_relationships AS tr"
                    . " INNER JOIN $wpdb->term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id"
                    . " WHERE tr.object_id = %d ORDER BY tt.taxonomy, tt.term_taxonomy_id",
                    $args['post_id']
                ),
                ARRAY_A
            );

            $pp_config['post_terms'] = gzcompress(serialize($post_terms));

            if (!empty($ok['pp_permissions'])) {
                if (!empty($wpdb->pp_conditions) && !empty($pp_config['post_terms'])) {
                    $pp_config['pp_post_conditions'] = gzcompress(
                        serialize($wpdb->get_results(
                            "SELECT * FROM $wpdb->pp_conditions "
                            . " WHERE scope = 'term' AND item_id IN ('" . implode("','", array_keys($post_terms)) . "')"
                            . " ORDER BY item_source, item_id, attribute, scope",
                            ARRAY_N
                        ))
                    );
                }
            }
        }

        if (!empty($ok['pp_permissions'])) {
            $pp_config['ppc_roles'] = gzcompress(
                serialize($wpdb->get_results(
                    "SELECT assignment_id as id, agent_id AS agent, agent_type AS a_type, role_name as r, assigner_id as by_id"
                    . " FROM $wpdb->ppc_roles ORDER BY assignment_id DESC LIMIT 5000"
                ))
            );

            $pp_config['ppc_exceptions'] = gzcompress(
                serialize($wpdb->get_results(
                    "SELECT e.*, COUNT(i.eitem_id) AS count FROM $wpdb->ppc_exceptions AS e"
                    . " INNER JOIN $wpdb->ppc_exception_items AS i ON e.exception_id = i.exception_id"
                    . " GROUP BY e.exception_id ORDER BY e.exception_id DESC LIMIT 5000",
                    ARRAY_N
                ))
            );

            $pp_config['ppc_exception_items'] = gzcompress(
                serialize($wpdb->get_results(
                    "SELECT eitem_id AS eitem, exception_id AS eid, item_id AS item, assign_for AS a_for, inherited_from AS inh"
                    . " FROM $wpdb->ppc_exception_items ORDER BY eitem_id DESC LIMIT 10000",
                    ARRAY_N
                ))
            );
        }

        if (!empty($ok['pp_groups'])) {
            $pp_config['pp_groups'] = gzcompress(
                serialize($wpdb->get_results(
                    "SELECT ID, group_name AS gname, metagroup_id AS mid, metagroup_type AS mtype FROM $wpdb->pp_groups"
                    . " ORDER BY ID DESC LIMIT 1000",
                    ARRAY_N
                ))
            );

            if ($is_multisite) {
                $wpdb->pp_groups_netwide = $wpdb->base_prefix . 'pp_groups';
                if ($wpdb->get_var("SHOW TABLES LIKE '$wpdb->pp_groups_netwide'")) {
                    $pp_config['pp_net_groups'] = gzcompress(
                        serialize($wpdb->get_results(
                            "SELECT ID, group_name AS gname, metagroup_id AS mid, metagroup_type AS mtype"
                            . " FROM $wpdb->pp_groups_netwide ORDER BY ID DESC LIMIT 1000",
                            ARRAY_N
                        ))
                    );
                }
            }

            if (!empty($wpdb->pp_circles)) {
                $pp_config['pp_circles'] = gzcompress(
                    serialize($wpdb->get_results(
                        "SELECT * FROM $wpdb->pp_circles ORDER BY group_type, group_id",
                        ARRAY_N
                    ))
                );
            }
        }

        if (!empty($ok['pp_group_members'])) {
            $pp_config['pp_wp_group_members'] = gzcompress(
                serialize($wpdb->get_results(
                    "SELECT gm.group_id AS g, gm.user_id AS u FROM $wpdb->pp_group_members AS gm"
                    . " INNER JOIN $wpdb->pp_groups AS g ON gm.group_id = g.ID AND g.metagroup_type = 'wp_role'"
                    . " ORDER BY gm.add_date_gmt DESC LIMIT 5000",
                    ARRAY_N
                ))
            );

            $pp_config['pp_group_members'] = gzcompress(
                serialize($wpdb->get_results(
                    "SELECT gm.group_id AS g, gm.user_id AS u FROM $wpdb->pp_group_members AS gm"
                    . " INNER JOIN $wpdb->pp_groups AS g ON gm.group_id = g.ID AND g.metagroup_type != 'wp_role'"
                    . " ORDER BY gm.add_date_gmt DESC LIMIT 2500",
                    ARRAY_N
                ))
            );

            if ($is_multisite) {
                $wpdb->pp_group_members_netwide = $wpdb->base_prefix . 'pp_group_members';
                if ($wpdb->get_var("SHOW TABLES LIKE '$wpdb->pp_group_members_netwide'")) {
                    $pp_config['pp_net_group_members'] = gzcompress(
                        serialize($wpdb->get_results(
                            "SELECT group_id AS g, user_id AS u FROM $wpdb->pp_group_members_netwide LIMIT 2500",
                            ARRAY_N
                        ))
                    );
                }
            }
        }

        $wpdb->ppi_imported = $wpdb->prefix . 'ppi_imported';
        if (@$wpdb->get_var("SHOW TABLES LIKE '$wpdb->ppi_imported'")) {
            $wpdb->ppi_runs = $wpdb->prefix . 'ppi_runs';
            $wpdb->ppi_errors = $wpdb->prefix . 'ppi_errors';
        }

        if (!empty($wpdb->ppi_runs)) {
            if (!empty($ok['ppc_roles']) || !empty($ok['pp_imports'])) {
                if ($wpdb->get_var("SHOW TABLES LIKE '$wpdb->ppi_runs'")) {
                    $pp_old['ppi_runs'] = gzcompress(
                        serialize($wpdb->get_results(
                            "SELECT * FROM $wpdb->ppi_runs ORDER BY import_date",
                            ARRAY_N
                        ))
                    );
                }

                if ($wpdb->get_var("SHOW TABLES LIKE '$wpdb->ppi_errors'")) {
                    $pp_old['ppi_errors'] = gzcompress(
                        serialize($wpdb->get_results(
                            "SELECT * FROM $wpdb->ppi_errors ORDER BY run_id, ID",
                            ARRAY_N
                        ))
                    );
                }
            }

            if (!empty($ok['pp_imports'])) {
                if ($ppi_ver = (array)get_option('ppi_version')) {
                    $prefix = ($is_multisite) ? $wpdb->base_prefix : $wpdb->prefix;

                    if ($wpdb->query("SHOW COLUMNS FROM $wpdb->ppi_imported LIKE 'source_tbl'")) {
                        if (defined('PPI_LEGACY_UPLOAD') && $wpdb->query("SHOW COLUMNS FROM $wpdb->ppi_imported LIKE 'source_table'"))
                            $extra_cols = ", REPLACE( source_table, '$prefix', '' ), REPLACE( import_table, '$prefix', '' )";
                        else
                            $extra_cols = '';

                        $pp_old['ppi_imported'] = gzcompress(
                            serialize($wpdb->get_results(
                                "SELECT run_id, source_tbl AS src, source_id AS src_id, rel_id, import_tbl AS tbl, import_id AS to_id{$extra_cols}"
                                . " FROM $wpdb->ppi_imported ORDER BY run_id DESC, source_tbl, source_id LIMIT 25000",
                                ARRAY_N
                            ))
                        );
                    } else {
                        $pp_old['ppi_imported'] = gzcompress(
                            serialize($wpdb->get_results(
                                "SELECT run_id, REPLACE( source_table, '$prefix', '' ) AS src, source_id AS src_id, rel_id,"
                                . " REPLACE( import_table, '$prefix', '' ) AS tbl, import_id AS to_id"
                                . " FROM $wpdb->ppi_imported ORDER BY run_id DESC, source_table, source_id LIMIT 25000",
                                ARRAY_N
                            ))
                        );
                    }
                }
            }
        }

        if (!empty($ok['pp_imports'])) {
            // RS
            $wpdb->user2role2object_rs = $wpdb->prefix . 'user2role2object_rs';
            $wpdb->role_scope_rs = $wpdb->prefix . 'role_scope_rs';
            $wpdb->groups_rs = $wpdb->prefix . 'groups_rs';
            $wpdb->user2group_rs = $wpdb->prefix . 'user2group_rs';

            $pp_old['rs_options'] = gzcompress(
                serialize($wpdb->get_results(
                    "SELECT option_name AS name, option_value AS val, option_id AS id FROM $wpdb->options"
                    . " WHERE option_name LIKE 'scoper_%' ORDER BY option_name",
                    ARRAY_N
                ))
            );

            if ($is_multisite) {
                $pp_old['rs_net_options'] = gzcompress(
                    serialize($wpdb->get_results(
                        "SELECT meta_key AS mkey, meta_value AS val, site_id AS site, meta_id AS mid FROM $sitemeta_table"
                        . " WHERE meta_key LIKE 'scoper_%' ORDER BY meta_key",
                        ARRAY_N
                    ))
                );
            }

            if (!empty($ok['pp_permissions'])) {
                if ($wpdb->get_var("SHOW TABLES LIKE '$wpdb->user2role2object_rs'")) {
                    $pp_old['rs_wp_roles'] = gzcompress(
                        serialize($wpdb->get_results(
                            "SELECT assignment_id AS id, user_id, role_name AS r, date_limited AS dlim, content_date_limited AS clim"
                            . " FROM $wpdb->user2role2object_rs WHERE scope = 'blog' AND role_type = 'wp'"
                            . " ORDER BY assignment_id DESC LIMIT 5000",
                            ARRAY_N
                        ))
                    );

                    $pp_old['rs_site_roles'] = gzcompress(
                        serialize($wpdb->get_results(
                            "SELECT assignment_id AS id, user_id, group_id, role_name AS r, date_limited AS dlim, content_date_limited AS clim"
                            . " FROM $wpdb->user2role2object_rs WHERE scope = 'blog' ORDER BY assignment_id DESC LIMIT 2500",
                            ARRAY_N
                        ))
                    );

                    $pp_old['rs_term_roles'] = gzcompress(
                        serialize($wpdb->get_results(
                            "SELECT assignment_id AS id, user_id, group_id, role_name AS r, src_or_tx_name AS st_name,"
                            . " obj_or_term_id AS ot_id, assign_for AS a_for, inherited_from AS inh, date_limited AS dlim, content_date_limited AS clim"
                            . " FROM $wpdb->user2role2object_rs WHERE scope = 'term' ORDER BY assignment_id DESC LIMIT 1000",
                            ARRAY_N
                        ))
                    );

                    $pp_old['rs_obj_roles'] = gzcompress(
                        serialize($wpdb->get_results(
                            "SELECT assignment_id AS id, user_id, group_id, role_name AS r, obj_or_term_id AS ot_id,"
                            . " assign_for AS a_for, inherited_from AS inh, date_limited AS dlim FROM $wpdb->user2role2object_rs"
                            . " WHERE scope = 'object' ORDER BY assignment_id DESC LIMIT 10000",
                            ARRAY_N
                        ))
                    );
                }

                if ($wpdb->get_var("SHOW TABLES LIKE '$wpdb->role_scope_rs'")) {
                    $pp_old['rs_restrictions'] = gzcompress(
                        serialize($wpdb->get_results(
                            "SELECT requirement_id AS id, role_name AS r, topic AS topic, src_or_tx_name AS st_name,"
                            . " obj_or_term_id AS ot_id, max_scope, require_for AS r_for, inherited_from AS inh"
                            . " FROM $wpdb->role_scope_rs ORDER BY requirement_id DESC LIMIT 5000",
                            ARRAY_N
                        ))
                    );
                }
            }

            if (!empty($ok['pp_groups'])) {
                if ($wpdb->get_var("SHOW TABLES LIKE '$wpdb->groups_rs'")) {
                    $pp_old['rs_groups'] = gzcompress(
                        serialize($wpdb->get_results(
                            "SELECT ID, group_name AS name, group_meta_id AS gmid FROM $wpdb->groups_rs ORDER BY ID DESC LIMIT 500",
                            ARRAY_N
                        ))
                    );
                }

                if ($is_multisite) {
                    $wpdb->net_groups_rs = $wpdb->base_prefix . 'groups_rs';

                    if (!empty($ok['pp_groups']) && $wpdb->get_var("SHOW TABLES LIKE '$wpdb->net_groups_rs'")) {
                        $pp_old['rs_net_groups'] = gzcompress(
                            serialize($wpdb->get_results(
                                "SELECT ID, group_name AS name, group_meta_id AS gmid FROM $wpdb->net_groups_rs ORDER BY ID DESC LIMIT 500",
                                ARRAY_N
                            ))
                        );
                    }
                }
            }

            if (!empty($ok['pp_group_members']) && $wpdb->get_var("SHOW TABLES LIKE '$wpdb->user2group_rs'")) {
                $pp_old['rs_group_members'] = gzcompress(
                    serialize($wpdb->get_results(
                        "SELECT group_id AS gid, user_id AS uid FROM $wpdb->user2group_rs LIMIT 5000",
                        ARRAY_N
                    ))
                );
            }

            // PP One
            $wpdb->pp_roles = $wpdb->prefix . 'pp_roles';
            $wpdb->pp_conditions = $wpdb->prefix . 'pp_conditions';
            $wpdb->pp_circles = $wpdb->prefix . 'pp_circles';

            if (!empty($ok['pp_groups']) && empty($pp_config['pp_circles']) && $wpdb->get_var("SHOW TABLES LIKE '$wpdb->pp_circles'")) {
                $pp_old['pp_circles'] = gzcompress(
                    serialize($wpdb->get_results(
                        "SELECT * FROM $wpdb->pp_circles ORDER BY group_type, group_id",
                        ARRAY_N
                    ))
                );
            }

            if (!empty($ok['pp_permissions']) && $wpdb->get_var("SHOW TABLES LIKE '$wpdb->pp_roles'")) {
                $pp_old['pp1_site_roles'] = gzcompress(
                    serialize($wpdb->get_results(
                        "SELECT assignment_id AS id, group_type AS gtype, group_id AS gid, role_name AS r FROM $wpdb->pp_roles"
                        . " WHERE scope = 'site' ORDER BY assignment_id DESC LIMIT 5000",
                        ARRAY_N
                    ))
                );

                $pp_old['pp1_term_roles'] = gzcompress(
                    serialize($wpdb->get_results(
                        "SELECT assignment_id AS id, group_type AS gtype, group_id AS gid, role_name AS r, item_id,"
                        . " assign_for AS a_for, inherited_from AS inh FROM $wpdb->pp_roles"
                        . " WHERE scope = 'term' ORDER BY assignment_id DESC LIMIT 1000",
                        ARRAY_N
                    ))
                );

                $pp_old['pp1_obj_roles'] = gzcompress(
                    serialize($wpdb->get_results(
                        "SELECT assignment_id AS id, group_type AS gtype, group_id AS gid, role_name AS r, item_id,"
                        . " assign_for AS a_for, inherited_from AS inh FROM $wpdb->pp_roles"
                        . " WHERE scope = 'object' ORDER BY assignment_id DESC LIMIT 10000",
                        ARRAY_N
                    ))
                );
            }
        }

        if (!empty($ok['error_log'])) {
            $base_path = str_replace('\\', '/', ABSPATH);
            $base_path_back = str_replace('/', '\\', ABSPATH);

            if ($path = self::getErrorLogPath()) {
                $size = filesize($path);

                if (defined('PPI_ERROR_LOG_UPLOAD_LIMIT') && (PPI_ERROR_LOG_UPLOAD_LIMIT > 1000) && (PPI_ERROR_LOG_UPLOAD_LIMIT < 750000))
                    $limit = PPI_ERROR_LOG_UPLOAD_LIMIT;
                else
                    $limit = 125000;  // with typical compression rate of 15 and base64 encoding, compressed size ~ 10k

                if ($size) {
                    if ($size > $limit) {
                        $fp = fopen($path, 'r');
                        fseek($fp, $size - $limit);
                        $data = fread($fp, $size);
                    } else {
                        $data = file_get_contents($path);
                    }

                    // trim to relative paths for privacy
                    if ($base_path)
                        $data = str_replace($base_path, './', $data);

                    if ($base_path_back)
                        $data = str_replace($base_path_back, '.\\', $data);

                    $error_log = base64_encode(gzcompress($data));
                }
            }
        } else
            $error_log = '';

        $hashes = [];
        foreach (['pp_config', 'pp_old', 'error_log'] as $var) {
            if (!empty($$var)) {
                if ($is_multisite && ('error_log' != $var)) {
                    global $blog_id;
                    $_var = $$var;
                    $_var['site'] = $blog_id;
                }

                $$var = base64_encode(gzcompress(serialize($$var)));

                $hashes[$var] = md5($$var);
            }

            unset($var);
        }

        if ($response = self::callHome('config-check', $request_vars, $hashes))
            $need_data = json_decode($response);
        else
            $need_data = false;

        if ($need_data) {
            $post_data = [];

            // add each data array to $post_data unless config-check did not indicate it is needed
            if (in_array('pp_config', $need_data, true))
                $post_data['pp_config'] = $pp_config;

            if (in_array('pp_old', $need_data, true))
                $post_data['pp_old'] = $pp_old;

            if (in_array('error_log', $need_data, true))
                $post_data['error_log'] = $error_log;

            $response = self::callHome('config-upload', $request_vars, $post_data);
            return $response;
        } else {
            return -1;
        }
    }

    private static function get_remote_request_params(){
		$_key = presspermit()->getOption('edd_key');
		if (is_array($_key) && isset($_key['license_key']))
			$key = $_key['license_key'];
		else
			$key = '';
		
		global $wpdb;
		
		$data = array(
			'call' => 'pp',
			'key' => $key,
			'core' => PRESSPERMIT_VERSION,
			'wp' => get_bloginfo('version'),
			'is_ms' => is_multisite(),
			'php' => phpversion(),
			'mysql' => $wpdb->db_version(),
		);
		
		$data['bp'] = ( defined( 'BP_VERSION' ) ) ? BP_VERSION : 0;
		$data['bbp'] = ( function_exists( 'bbp_get_version' ) ) ? bbp_get_version() : 0;
		$data['rvy'] = ( defined( 'RVY_VERSION' ) ) ? RVY_VERSION : 0;
		$data['cme'] = ( defined( 'CAPSMAN_ENH_VERSION' ) ) ? CAPSMAN_ENH_VERSION : 0;
		
		$data['beta_updates'] = presspermit()->getOption('beta_updates');
		
		return $data;
    }

	private static function callHome( $request_topic, $request_vars = array(), $post_vars = false ) {
		$request_vars = array_merge( self::get_remote_request_params(), (array) $request_vars, array( 'PPServerRequest' => $request_topic ) ); //, 'site' => urlencode( get_option('siteurl') ) ) );

		$query = http_build_query( $request_vars, '', '&' );
		
		$args = array(
			'headers' => array(
				'Content-Type' => 'application/x-www-form-urlencoded; charset=' . get_option('blog_charset'),
				'User-Agent' => 'WordPress/' . get_bloginfo("version"),
				'Referer' => get_bloginfo("url")
			),
		);
		
		if ( 'config-info' == $request_topic )
			$args['timeout'] = 30;
		else
			$args['timeout'] = in_array( $request_topic, array( 'update-check', 'changelog' ) ) ? 8 : 20;
		
		if ( false !== $post_vars )
			$args['body'] = $post_vars;
		
		$server_response = wp_remote_post( 'http://presspermit.com/' . "index.php?$query", $args );
		
		$const = 'PP_DEBUG_' . strtoupper( str_replace( '-', '_', $request_topic ) );
		if ( is_admin() && defined( $const ) && constant( $const ) ) {
			if ( defined( 'PP_DEBUG' ) && ( 'var_dump' !== constant( $const ) ) ) {
				dump($server_response);
				agp_bt_die();
			} else {
				var_dump($server_response);
				die('--- PP TEST ---');
			}
		}
		
		$result = false;
		
		if ( is_wp_error($server_response) ) {
			// @todo
		} else {
			if ( isset($server_response['body']) ) {
				$start_tag = '<!--PPServer-->';
				$pos = strpos( $server_response['body'], $start_tag );
				if ( false !== $pos ) {
					$end_pos = strpos( $server_response['body'], '<!--End PPServer-->' );
					if ( $end_pos ) {
						$result = substr( $server_response['body'], $pos + strlen($start_tag), $end_pos - ( $pos + strlen($start_tag) ) );
						$result = maybe_unserialize($result);
					}
				}
			}
		}

		if ( 'config-upload' == $request_topic )
			return ( ! empty($server_response) && ! is_wp_error($server_response) );
		else
			return $result;
	}

    private static function getErrorLogPath()
    {
        $log_file = ini_get('error_log');
        return (empty($log_file) || !@is_readable($log_file)) ? false : $log_file;
    }
}
