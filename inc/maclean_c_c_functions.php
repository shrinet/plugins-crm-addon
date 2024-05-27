<?php
namespace MacleanCustomCode;

if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

class MacleanCCFunctions {
	
	public function __construct( $master ) {
		$this->master = $master;
		add_action( "admin_head", function() {
			?>
			<script>
				jQuery(document).on("click", "input[name='members_user_roles[]']", function( e ) {
					if ( jQuery(e.target).val() === "employee" && jQuery(e.target).is(":checked" ) ) {
						jQuery("#acf-field_5cf8049c7ab4a").attr("checked", true);
						jQuery("#acf-field_5cf804797ab69").attr("checked", true);
					}
					if ( jQuery(e.target).val() === "adminstrator" && jQuery(e.target).is(":checked" ) ){
						jQuery("#acf-field_5cf8049c7ab4a").attr("checked", true);
						jQuery("#acf-field_5cf804797ab69").attr("checked", true);
					}
					if ( jQuery(e.target).val() === "representative" && jQuery(e.target).is(":checked" ) ){
						jQuery("#acf-field_5cf804797ab69").attr("checked", true);
					}
				}); 
			</script>
			<?php
		});

		add_filter('acf/settings/remove_wp_meta_box', '__return_false');

		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		add_action('acf/init', array( $this, 'acf_init' ) );
		add_action('wp_ajax_maclean_pre_submit_validation', array( $this, 'account_pre_submit_validation'));
	}

	public function acf_init() {	
		acf_update_setting('google_api_key', 'xxx');
	}
	
	

	public function escape_quotes( $data ) {
		return str_replace( "\"", "&#34;", str_replace( "'", "&#39;", $data ) );
	}
	
	public function get_all_post_types() {
		global $wpdb;
		$post_types = $wpdb->get_col( "SELECT DISTINCT post_type FROM " . $wpdb->posts );
		return $post_types;	
	}
	
	public function err_log( $item ) {
		ob_start();
		var_dump( $item );
		error_log( ob_get_clean() );
	}
	
	public function flatten(array $array) {
		$return = array();
		array_walk_recursive($array, function($a) use (&$return) { $return[] = $a; });
		return $return;
	}

	public function array_search( $array, $search ) {
		$arr = array();
		foreach ( $array as $a ) {
			if ( strpos( $a, $search ) !== false ) {
				$arr[] = $a;
			}
		}
		return $arr;
	} 
	
	public function get_uri_segments() {
		return explode("/", parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
	}
	 
	public function get_uri_segment($n) {
		$segs = $this->get_uri_segments();
		return count($segs)>0&&count($segs)>=($n-1)?$segs[$n]:'';
	}

	public function get_date( $str = "", $str_is_timestamp = false ) {
		if ( $str !== "" ) {
			if ( !$str_is_timestamp ) {
				return date( "Y-m-d H:i:s", strtotime( $str ) );
			} else {
				return date( "Y-m-d H:i:s", $str );
			}
			
		}
		return date( "Y-m-d H:i:s" );
	}

	public function is_admin( $user ) {
		if ( in_array( "administrator", (array) $user->roles ) ) {
			return true;
		}
		return false;
	}

	public function send_maclean_email( $user_email, $subject, $body, $date = NULL ) {
		if ( $date === NULL ) {
			$date = $this->get_date();
		}		
		wp_mail( $user_email, $subject, $body, array('Content-Type: text/html; charset=UTF-8') );
	}

	public function send_maclean_account_email( $user, $subject, $body, $date = NULL ) {
		if ( !defined( "LIVE_SITE" ) || LIVE_SITE === false || strpos( $_SERVER[ "HTTP_HOST" ], "dev.newmacleanpower.com" ) === false ) {
			if ( !$this->is_admin(  $user  ) ) {
				return;
			}
		}
		if ( $date === NULL ) {
			$date = $this->get_date();
		}
		$subject = str_replace( "{{first_name}}", $user->first_name, $subject );
		$subject = str_replace( "{{last_name}}", $user->last_name, $subject );
		$subject = str_replace( "{{username}}", $user->user_login, $subject );
		$subject = str_replace( "{{date}}", $date, $subject );
		$subject = str_replace( "{{site_name}}", get_bloginfo( 'name' ), $subject );
		//$subject = str_replace( "{{organization}}", get_post( get_field( "organization", "user_" . $user->ID ) )->post_title, $subject );
	
		$body = str_replace( "{{first_name}}", $user->first_name, $body );
		$body = str_replace( "{{last_name}}", $user->last_name, $body );
		$body = str_replace( "{{username}}", $user->user_login, $body );
		$body = str_replace( "{{email}}", $user->user_email, $body );
		$body = str_replace( "{{date}}", $date, $body );
		$body = str_replace( "{{site_name}}", get_bloginfo( 'name' ), $body );
		//$body = str_replace( "{{organization}}", get_post( get_field( "organization", "user_" . $user->ID ) )->post_title, $body );
	
		$body = str_replace( "{{login_link}}", "<a href='" . home_url( "/login/" ) . "' target='_blank'>Login</a>", $body );
		$body = str_replace( "{{idle_account_link}}", "<a href='" . get_field( "account_information_confirmation_link", "user_" . $user->ID ) . "' target='_blank'>Login</a>", $body );
		$body = str_replace( "{{password_reset_link}}", "<a href='" . get_field( "password_reset_link", "user_" . $user->ID ) . "' target='_blank'>Reset Your Password</a>", $body );
		$body = str_replace( "{{forgot_password_link}}", "<a href='" . home_url( "/my-account/forgot-password/" ) . "' target='_blank'>Forgot Your Password</a>", $body );
		$body = str_replace( "{{account_information_confirmation_link}}", "<a href='" . get_field( "account_information_confirmation_link", "user_" . $user->ID ) . "' target='_blank'>Confirm Your Account Information</a>", $body );
		$body = str_replace( "{{member_confirmation_link}}", "<a href='" . get_field( "account_information_confirmation_link", "user_" . $user->ID ) . "' target='_blank'>Confirm Your Membership</a>", $body );
		$body = str_replace( "{{expiration_reason}}", get_field( "account_suspended_reason", "user_" . $user->ID ), $body );
		$body = str_replace( "{{reactivation_request_link}}", "<a href='" . get_field( "reactivation_request_link", "user_" . $user->ID ) . "' target='_blank'>Request Account Reactivation</a>", $body );
		$body = str_replace( "{{account_confirmation_link}}", "<a href='" . get_field( "account_information_confirmation_link", "user_" . $user->ID ) . "' target='_blank'>Confirm Your Account</a>", $body );
		$body = str_replace( "{{login_account_confirmation_link}}", "<a href='" . get_field( "account_information_confirmation_link", "user_" . $user->ID ) . "' target='_blank'>Login</a>", $body );
		wp_mail( $user->user_email, $subject, $body, array('Content-Type: text/html; charset=UTF-8') );
	}

	public function clean_data( $data, $allow_spaces = false ) {
		if ( $allow_spaces ) {
			return $this->clean_bom( preg_replace( '/[^A-Za-z0-9\,\:\.\;\/\-\*\\\(\)\[\]\{\}\+\?\&\_\%\|\#\=\s\ \<\>\^\@\!\~\`\"\'\ ]+/', "", $data) );
		}
        return $this->clean_bom( preg_replace( '/[^A-Za-z0-9\,\:\.\;\/\-\*\\\(\)\[\]\{\}\+\?\&\_\%\|\#\=\s\ \<\>\^\@\!\~\`\"\']+/', "", $data) );
	}

	public function clean_bom( $str ) {
		$bom = pack("CCC", 0xef, 0xbb, 0xbf);
		if (0 === strncmp($str, $bom, 3)) {
			$str = substr($str, 3);
		}
		return $str;
	}

	public function clean_data_strict( $data, $allow_spaces = false ) {
		if ( $allow_spaces ) {
			return $this->clean_bom( preg_replace( '/[^A-Za-z0-9 ]+/', "", $data) );
		}
        return $this->clean_bom( preg_replace( '/[^A-Za-z0-9]+/', "", $data) );
	} 

	public function clean_data_strict_leave_ast( $data, $allow_spaces = false ) {
		if ( $allow_spaces ) {
			return $this->clean_bom( preg_replace( '/[^A-Za-z0-9\* ]+/', "", $data) );
		}
        return $this->clean_bom( preg_replace( '/[^A-Za-z0-9\*]+/', "", $data) );
	}

	public function get_param( $key, $sourceURL ) {
		$url = parse_url( $sourceURL );
		if ( !isset( $url[ 'query' ] ) ) {
			return "";
		}
		parse_str( $url['query' ], $query_data );
		if ( isset( $query_data[ $key ] ) ) {
			return $query_data[$key ];
		}
		return "";
	}

	public function has_param( $key, $sourceURL ) {
		$url = parse_url( $sourceURL );
		if ( !isset( $url[ 'query' ] ) ) {
			return false;
		}
		parse_str( $url[ 'query' ], $query_data );
		if ( !isset( $query_data[ $key ] ) ) {
			return false;
		}
		return true;
	}

	public function remove_param( $key, $sourceURL ) { 
		$url = parse_url( $sourceURL );
		if ( !isset( $url[ 'query' ] ) ) {
			return $sourceURL;
		}
		parse_str( $url[ 'query' ], $query_data );
		if ( !isset( $query_data[ $key ] ) ) {
			return $sourceURL;
		}
		unset( $query_data[ $key ] );
		$url[ 'query' ] = http_build_query( $query_data );
		return unparse_url( $url );
	}

	public function unparse_url( $parsed_url ) { 
		$scheme   = isset( $parsed_url[ 'scheme' ] ) ? $parsed_url[ 'scheme' ] . '://' : ''; 
		$host     = isset( $parsed_url[ 'host' ] ) ? $parsed_url[ 'host' ] : ''; 
		$port     = isset( $parsed_url[ 'port' ] ) ? ':' . $parsed_url[ 'port' ] : ''; 
		$user     = isset( $parsed_url[ 'user' ] ) ? $parsed_url[ 'user' ] : ''; 
		$pass     = isset( $parsed_url[ 'pass' ] ) ? ':' . $parsed_url[ 'pass' ]  : ''; 
		$pass     = ( $user || $pass ) ? "$pass@" : ''; 
		$path     = isset( $parsed_url[ 'path' ] ) ? $parsed_url[ 'path' ] : ''; 
		$query    = isset( $parsed_url[ 'query' ] ) ? '?' . $parsed_url[ 'query' ] : ''; 
		$fragment = isset( $parsed_url[ 'fragment' ] ) ? '#' . $parsed_url[ 'fragment' ] : ''; 
		return "$scheme$user$pass$host$port$path$query$fragment"; 
	}

	public function array_diff_assoc($array1, $array2) {
		$this->err_log( $array1 );
		$this->err_log( $array2 );
		$difference=array();
		foreach($array1 as $key => $value) {
			if( is_array($value) ) {
				if( !isset($array2[$key]) || !is_array($array2[$key]) ) {
					$difference[$key] = $value;
				} else {
					$new_diff = $this->array_diff_assoc($value, $array2[$key]);
					if( !empty($new_diff) )
						$difference[$key] = $new_diff;
				}
			} else if( !array_key_exists($key,$array2) ) {
				$difference[$key] = $value;
			}
		}
		return $difference;
	}

	public function strrtrim($message, $strip) { 
		$lines = explode($strip, $message); 
		$last  = ''; 
		do { 
			$last = array_pop($lines); 
		} while (empty($last) && (count($lines))); 
		return implode($strip, array_merge($lines, array($last))); 
	} 

	public function write( $file, $content, $append = true ) {
        file_put_contents( $file, $content, $append );
    }
    
    public function write_files( $files, $override = false ) {
        foreach ( $files as $path => $content ) {
            $this->create_directory_from_file_path( $path );
            $this->write( $path, $content, !$override );   
        }
	}
	
	public function create_directory_from_file_path( $path ) {
        if ( !file_exists( trim( dirname( $path ) ) ) ) {
            mkdir( trim(dirname( $path )), 0777, true );
        }    
	}
	
	public function update_acf_repeater( $field, $data, $post_id ) {
        update_field( $field, $data, $post_id );
        $rObj = get_field_object($field, $post_id);
        update_post_meta( $post_id, "_" . $field, $rObj[ "key" ] );
        update_post_meta( $post_id, $field, count( $data ) );        
    } 
	
	public function add_toolset_repeater_field( $term, $field, $data ) {
        $ids = array();
        $ids_field = "_" . str_replace( "wpcf-", "", $field ) . "-sort-order";
        delete_term_meta( $term, $field );
        delete_term_meta( $term, $ids_field );
        foreach ( $data as $d ) {
            if ( $d === "" ) {
                continue;
            }
            $id = add_term_meta( $term, $field, $d );
            if ( $id !== false ) {
                $ids[] = $id;
            }
        }
        $id = update_term_meta( $term, $ids_field, $ids );
	}
	
	public function get_post( $title, $post_type, $search_meta_key_for_accounts = true, $search_meta_key_for_products = true ) {
		if ( $post_type === "product" && $search_meta_key_for_products ) {
			global $wpdb;
			$post_ids = $wpdb->get_col( "SELECT pm.post_id FROM $wpdb->postmeta as pm WHERE pm.meta_key = 'CatalogNumberCleaned' AND pm.meta_value = '$title' LIMIT 1;" );
			if ( count( $post_ids ) > 0 ) {
				return $post_ids[ 0 ];
			}
			return null;
		} else if ( $post_type === "account"  && $search_meta_key_for_accounts ) {
			global $wpdb;
			$post_ids = $wpdb->get_col( "SELECT p.id from $wpdb->posts AS p WHERE p.post_title LIKE '$title - %' LIMIT 1;" );
			if ( count( $post_ids ) > 0 ) {
				return $post_ids[ 0 ];
			}
			return null;
		}
		if ( ( $post = get_page_by_title( $title, OBJECT, $post_type ) ) !== null ) {
			return $post->ID;
		} 
		return null;
	}

	public function get_taxonomy_hier( $taxon, $children = array() ) {
        if ( count( $children ) === 0 ) {
            $children[] = $taxon->name;
        }
        if ( $taxon->parent > 0 ) {
            $parent = get_term( $taxon->parent );
            $children[] = $parent->name;
            return $this->get_taxonomy_hier( $parent, $children );
        } else {
            return str_replace( "\"", "\"\"", implode( " | ", array_reverse( $children ) ) );
        }
	}
	
	public function get_taxonomy_hierarchy( $taxonomy, $parent = 0, $name = "" ) {
        $taxonomy = is_array( $taxonomy ) ? array_shift( $taxonomy ) : $taxonomy;
        $args = array( 'parent' => $parent, 'hide_empty' => false, 'number' => 1 );
        if ( strlen( $name ) > 0 ) {
            $args[ "name" ] = $name;
        }
        $terms = get_terms( $taxonomy, $args );
        $children = array();
        foreach ( $terms as $term ){
            $term->children = $this->get_taxonomy_hierarchy( $taxonomy, $term->term_id );
            $children[ $term->term_id ] = $term;
        }
        return $children;
    }

    public function get_taxonomy_hierarchy_all( $taxonomy, $parent = 0, $name = "" ) {
        $taxonomy = is_array( $taxonomy ) ? array_shift( $taxonomy ) : $taxonomy;
        $args = array( 'hide_empty' => false, 'number' => 0 );
        if ( strlen( $name ) > 0 ) {
            $args[ "name" ] = $name;
        }
        $terms = get_terms( $taxonomy, $args );
        $children = array();
        foreach ( $terms as $term ){
            $term->children = $this->get_taxonomy_hierarchy( $taxonomy, $term->term_id );
            $children[ $term->term_id ] = $term;
        }
        return $children;
    }

	public function prepare( $data, $strip = false ) {
		if ( $strip ) {
			$data = str_replace( '"', '', str_replace( '\\', '', $data ) );
		}
		return array_map( function( $e ) { return esc_sql( trim( $this->clean_data( $e, true ) ) ); }, $data );
	}

	public function prepare_assoc( $data ) {
		$headers =  $this->prepare( array_keys( $data ) );
		$values =  $this->prepare( array_values( $data ) );
		$counter = 0;
		$data = array();
		foreach ( $headers as $header ) {
			if ( strlen( $header ) < 1 ) {
				continue;
			}
			$data[ $this->clean_data_strict( $header ) ] = $values[ $counter ];
			$counter++;
		}
		return $data;
	}

	public function save_term_permalink( $term, $permalink, $prev, $update ) {
        $url = get_term_meta( $term->term_id, 'permalink_customizer' );
        if ( empty( $url ) || 1 == $update ) {
          global $wpdb;
          $trailing_slash = substr( $permalink, -1 );
          if ( '/' == $trailing_slash ) {
            $permalink = rtrim( $permalink, '/' );
          }
          $set_permalink = $permalink;
          $qry = "SELECT * FROM $wpdb->termmeta WHERE meta_key = 'permalink_customizer' AND meta_value = '" . $permalink . "' AND term_id != " . $term->term_id . " OR meta_key = 'permalink_customizer' AND meta_value = '" . $permalink . "/' AND term_id != " . $term->term_id . " LIMIT 1";
          $check_exist_url = $wpdb->get_results( $qry );
          if ( ! empty( $check_exist_url ) ) {
            $i = 2;
            while (1) {
              $permalink = $set_permalink . '-' . $i;
              $qry = "SELECT * FROM $wpdb->termmeta WHERE meta_key = 'permalink_customizer' AND meta_value = '" . $permalink . "' AND term_id != " . $term->term_id . " OR meta_key = 'permalink_customizer' AND meta_value = '" . $permalink . "/' AND term_id != " . $term->term_id . " LIMIT 1";
              $check_exist_url = $wpdb->get_results( $qry );
              if ( empty( $check_exist_url ) ) break;
              $i++;
            }
          }
          if ( '/' == $trailing_slash ) {
            $permalink = $permalink . '/';
          }
    
          if ( strpos( $permalink, '/' ) === 0 ) {
            $permalink = substr( $permalink, 1 );
          }
        }
    
        update_term_meta( $term->term_id, 'permalink_customizer', $permalink );
    
        $taxonomy = 'category';
        if ( isset( $term->taxonomy ) && ! empty( $term->taxonomy ) ) {
          $taxonomy = $term->taxonomy;
        }
    
        if ( ! empty( $permalink ) && ! empty( $prev ) && $permalink != $prev  ) {
          $this->add_auto_redirect( $prev, $permalink, $taxonomy, $term->term_id );
        }
	}

	public function add_auto_redirect( $redirect_from, $redirect_to, $type, $id ) {
        $redirect_filter = apply_filters(
          'permalinks_customizer_auto_created_redirects', '__true'
        );
        if ( $redirect_from !== $redirect_to && '__true' === $redirect_filter ) {
          global $wpdb;
    
          $table_name = "{$wpdb->prefix}permalinks_customizer_redirects";
    
          $wpdb->query( $wpdb->prepare( "UPDATE $table_name SET enable = 0 " .
            " WHERE redirect_from = %s", $redirect_to
          ) );
    
          $post_perm = 'p=' . $id;
          $page_perm = 'page_id=' . $id;
          if ( 0 === strpos( $redirect_from, '?' ) ) {
            if ( false !== strpos( $redirect_from, $post_perm )
              || false !== strpos( $redirect_from, $page_perm ) ) {
              return;
            }
          }
    
          $find_red = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name " .
            " WHERE redirect_from = %s AND redirect_to = %s", $redirect_from,
            $redirect_to
          ) );
    
          if ( isset( $find_red ) && is_object( $find_red )
            && isset( $find_red->id ) ) {
    
            if ( isset( $find_red->enable ) && 0 == $find_red->enable ) {
              $wpdb->query( $wpdb->prepare( "UPDATE $table_name SET enable = 1 " .
                " WHERE id = %d", $find_red->id
              ) );
            }
          } else {
            $redirect_added = $wpdb->insert( $table_name, array(
              'redirect_from'   => $redirect_from,
              'redirect_to'     => $redirect_to,
              'type'            => $type,
              'redirect_status' => 'auto',
              'enable'          => 1,
            ));
          }
        }
	}   
	
	public function replace_term_tags( $term, $replace_tag ) {
        if ( false !== strpos( $replace_tag, '%name%' ) ) {
            $name        = sanitize_title( $term->name );
            $replace_tag = str_replace( '%name%', $name, $replace_tag );
        }

        if ( false !== strpos( $replace_tag, '%term_id%' ) ) {
            $replace_tag = str_replace( '%term_id%', $term->term_id, $replace_tag );
        }

        if ( false !== strpos( $replace_tag, '%slug%' ) ) {
            if ( ! empty( $term->slug ) ) {
                $replace_tag = str_replace( '%slug%', $term->slug, $replace_tag );
            } else {
                $name        = sanitize_title( $term->name );
                $replace_tag = str_replace( '%slug%', $name, $replace_tag );
            }
        }

        if ( false !== strpos( $replace_tag, '%parent_slug%' ) ) {
            $parents    = get_ancestors( $term->term_id, $term->taxonomy, 'taxonomy' );
            $term_names = '';
            if ( $parents && ! empty( $parents ) && count( $parents ) >= 1 ) {
            $parent     = get_term( $parents[0] );
            $term_names = $parent->slug . '/';
            }

            if ( ! empty( $term->slug ) ) {
                $term_names .= $term->slug;
            } else {
                $title       = sanitize_title( $term->name );
                $term_names .=  $title;
            }

            $replace_tag = str_replace( '%parent_slug%', $term_names, $replace_tag );
        }

        if ( false !== strpos( $replace_tag, '%all_parents_slug%' ) ) {
            $parents    = get_ancestors( $term->term_id, $term->taxonomy, 'taxonomy' );
            $term_names = '';
            if ( $parents && ! empty( $parents ) && count( $parents ) >= 1 ) {
            $i = count( $parents ) - 1;
            for ( $i; $i >= 0; $i-- ) {
                $parent      = get_term( $parents[$i] );
                $term_names .= $parent->slug . '/';
            }
            }

            if ( ! empty( $term->slug ) ) {
                $term_names .= $term->slug;
            } else {
                $title       = sanitize_title( $term->name );
                $term_names .=  $title;
            }

            $replace_tag = str_replace( '%all_parents_slug%', $term_names, $replace_tag );
        }

        return $replace_tag;
    }

	public function filter_out_files($files, $pattern = '(.+FILE\-IS\-RUNNING\.csv)') {
		$files = implode( "\n", $files );
		$files = preg_replace($pattern, "|:|", $files );
		$files = preg_replace(strtoupper($pattern), "|:|", $files );
		$files = preg_replace(strtolower($pattern), "|:|", $files );
		$files = explode( "\n", $files );
		$files = array_diff( $files, array( "|:|" ) );
		return $files;
	}

	public function filter_files($files, $pattern = '(.+FILE\-IS\-RUNNING\.csv)') {
		$returned_files = array();
		foreach ( $files as $file ) {
			if ( preg_match( strtoupper($pattern), $file ) === 1 || preg_match( strtolower($pattern), $file ) === 1 ) {
				$returned_files[] = $file;
			}
		}
		return $returned_files;
	}

	function array_to_csv($fields, $delimiter = ",", $enclosure = '"', $escape_char = "\\") {
		$buffer = fopen('php://temp', 'r+');
		fputcsv($buffer, $fields, $delimiter, $enclosure, $escape_char);
		rewind($buffer);
		$csv = fgets($buffer);
		fclose($buffer);
		return $csv;
	}

	public function account_pre_submit_validation(){
		$params = array();
        parse_str($_POST['form_data'], $params);
		$post_id = $params['post_ID'];

		$post_type = get_post_type($post_id);
	
		if ("account" != $post_type) {
			echo 'success'; 
			die();
			
		}
		if (isset($params['acf']['field_5cfd347604962'])) {
				$new_account_no = $params['acf']['field_5cfd347604962'];
		}
		global $wpdb;
		$accounts = $wpdb->get_col( "SELECT meta_value FROM wp_postmeta pm join wp_posts p on p.ID=pm.post_id WHERE meta_key = 'cust' and post_status= 'publish' and post_id<> '$post_id' " );
		foreach ($accounts as $a) {
				if($a==$new_account_no){
					echo 'Your account number ' . $a . ' is already exist, please choose a different one'; 
					die();
				}
		}
		echo 'success'; 
		die();
	}



}
