<?php
namespace MacleanCustomCode\Accounts;

if ( ! defined( "ABSPATH" ) ) {
	exit( "Direct script access denied." );
}

class MacleanCCAccounts {

	public function __construct( $master = NULL ) {
		$this->master = $master;
		if ( $master !== NULL ) {
			$this->init();
		}
		if ( array_key_exists( "expire_accounts_auto", $_GET ) && $_GET[ "expire_accounts_auto" ] === "true" ) {
			do_action('maclean_check_representatives');
		}
		if ( array_key_exists( "send_emails_auto", $_GET ) && $_GET[ "send_emails_auto" ] === "true" ) {
			do_action( 'maclean_send_emails' );
		}
	}

	public function set_master( $master ) {
		$this->master = $master;
		$this->init();
	} 

	public function init() {
		$this->setup_actions();
		$this->setup_filters();
		$this->setup_menus();
		$this->setup_shortcodes();
		if ( !wp_next_scheduled( "maclean_send_emails" ) ) {
			wp_schedule_event( time() + 60*10, "5min", "maclean_send_emails" );
		}
		if ( !wp_next_scheduled( "maclean_check_representatives" ) ) {
			wp_schedule_event( time(), "daily", "maclean_check_representatives" );
		}
		$this->remove_user_roles();
		$this->set_constants();
		$this->add_user_roles();
	}

	public function add_user_roles() {
        foreach ( $this->roles as $role => $slug ) {
            $edit = strpos( $role, "**CAN_EDIT**" ) !== false;
            add_role(
                $slug,
                __( str_replace( "**CAN_EDIT**", "", $role ) ),
                array(
                    'read'         =>   true,
                    'edit_posts'   =>   $edit,
                    'delete_posts' =>   $edit,
                )
            );
        }
    }

    public function remove_user_roles() {
    }

    public function set_constants() {
        $this->roles = array(
            "Representative" => "representative",
			"AE Support**CAN_EDIT**" => "ae_support"
        );
    }

	public function setup_actions() {

		add_action( "wp_enqueue_scripts", array( $this, "enqueue_scripts" ) );
		add_action( "admin_enqueue_scripts", array( $this, "admin_enqueue_scripts" ) );

		add_action( "admin_menu", array( $this, "admin_menus" ) );

		add_action( 'maclean_check_representatives', array( $this, 'check_representatives' )  );

		add_action( 'maclean_send_emails', array( $this, 'send_emails_automated' )  );

		add_action( 'profile_update', array( $this, 'profile_updated'), 10, 2 );

		// representative requests
		add_action( "wp_ajax_nopriv_add_representative_request_submission", array( $this, "add_representative_request_post" ) );
		add_action( "wp_ajax_add_representative_request_submission", array( $this, "add_representative_request_post" ) );

		add_action( "wp_ajax_get_export_content", array( $this, "get_exporter_content" ) );

		add_action( "post_updated", array( $this, "representative_request_published" ), 10, 3 );
		add_action( "save_post", array( $this, "save_posts" ), 10, 1 );

		add_action( "wp_head", function() {
			if ( is_account_page() && ($_SERVER[ "REQUEST_URI" ] === "/my-account/" || $_SERVER[ "REQUEST_URI" ] === "/my-account"|| $_SERVER[ "REQUEST_URI" ] === "my-account" ) ) {
				wp_redirect( home_url( "/my-account/edit-account/" ) );
				exit(); 
			}
		});

		// account automation
		add_action( "after_password_reset", array( $this, "after_password_reset" ), 10, 2 );
	}

	public function profile_updated( $user_id, $old_user_data ) {
		if ( ( time() - get_option( "update_time_$user_id" ) ) < 60 ) {
			return;
		}
		update_option("update_time_$user_id", time() );
		update_field( 'modify_date', date( 'Y-m-d H:i:s' ), "user_$user_id" );
		$user_suspended = get_field( "is_suspended", "user_" . $user_id ) === true;
		$field_2 = get_field( "send_account_has_expired_notice", "user_" . $user_id ) == true;
		$user = get_user_by( "ID", $user_id );
		if ( $field_2 && $user_suspended ) {
			$this->toggle_suspension( $user, true );
			$field = get_field( "reactivation_request_link", "user_" . $user->ID );
			if ( $field === false || $field === "" || $field === null ) {
				update_field( "reactivation_request_link", $this->get_request_reactivation_link( $user->ID ), "user_" . $user->ID );
			}
			update_field( "send_account_has_expired_notice", false, "user_" . $user_id );
		} else {
			update_field( "send_account_has_expired_notice", false, "user_" . $user_id );
		}
		$field_2 = get_field( "send_account_information_confirmation_notice", "user_" . $user_id ) == true;
		if ( $field_2 && !$user_suspended ) {
			$this->toggle_confirmation( $user, true );
			$field = get_field( "account_information_confirmation_link", "user_" . $user->ID );
			if ( $field === false || $field === "" || $field === null ) {
				update_field( "account_information_confirmation_link", $this->get_account_confirmation_link( $user->ID ), "user_" . $user->ID );
			}
			update_field( "send_account_information_confirmation_notice", false, "user_" . $user_id );
		} else {
			update_field( "send_account_information_confirmation_notice", false, "user_" . $user_id );
		}
		$field_2 = get_field( "send_password_reset_notice", "user_" . $user_id ) === true;
		if ( $field_2 && !$user_suspended ) {
			$this->toggle_password( $user, true );
			$field = get_field( "password_reset_link", "user_" . $user->ID );
			if ( $field === false || $field === "" || $field === null ) {
				update_field( "password_reset_link", $this->get_password_reset_link( $user->ID ), "user_" . $user->ID );
			}
			update_field( "send_password_reset_notice", false, "user_" . $user_id );
		} else {
			update_field( "send_password_reset_notice", false, "user_" . $user_id );
		}
		$field_2 = get_field( "send_account_reactivation_confirmation_notice", "user_" . $user_id ) == true;
		if ( $field_2 && !$user_suspended ) {
			$this->toggle_reactivate( $user, true );
			$field = get_field( "reactivation_request_link", "user_" . $user->ID );
			if ( $field === false || $field === "" || $field === null ) {
				update_field( "reactivation_request_link", $this->get_request_reactivation_link( $user->ID ), "user_" . $user->ID );
			}
			update_field( "send_account_reactivation_confirmation_notice", false, "user_" . $user_id );
		} else {
			update_field( "send_account_reactivation_confirmation_notice", false, "user_" . $user_id );
		}
	}

	public function setup_filters() {
		// account automation
		add_filter( "wp_authenticate_user", array( $this, "authenticate_user" ), 30, 2 );
		add_filter( "woocommerce_login_redirect", array( $this, "woo_login_redirect" ), 10, 2 );
		add_filter( "login_redirect", array( $this, "login_redirect" ), 10, 3 );
		add_filter( 'wp_maclean_background_process_email_cron_interval', function( ) {
			return 1;
		}, 10, 0 );
		add_filter( 'wp_maclean_background_process_cron_interval', function( ) {
			return 1;
		}, 10, 0 );
	}

	public function setup_menus() {
	}

	public function setup_shortcodes() {
		// representative request form
		add_shortcode( "representative_request_form", array( $this, "representative_request_form" ) );

		// export representatives
		add_shortcode( "representative_exporter", array( $this, "representative_exporter" ) );

		//login form
		add_shortcode( "login_form_sc", array( $this, "login_form" ) );
		add_shortcode( "login_form_header_text", array( $this, "login_form_header_text" ) );
		add_shortcode( "login_form_footer_text", array( $this, "login_form_footer_text" ) );
		add_shortcode( "login_form_footer_url", array( $this, "login_form_footer_url" ) );
	}

	public function enqueue_scripts() {
        wp_enqueue_script( "maclean-accounts-script", $this->master->url  . "assets/shared/js/account-functions.js", array( "jquery", "maclean-functions-script" ) );
	}

	public function admin_enqueue_scripts() {
		wp_enqueue_script( "maclean-functions-script", $this->master->url  . "assets/shared/js/maclean-functions.js", array( "jquery" ) );
        wp_enqueue_script( "maclean-admin-accounts-script", $this->master->url  . "assets/shared/js/account-admin-functions.js", array( "jquery", "maclean-functions-script" ) );
	}

	public function admin_menus() {
		add_users_page( "Representative Exporter", "Representative Exporter", "edit_posts", 'rep-exporter', array( $this, "rep_export_menu" ) );
	}

	public function account_action_header_text() {
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			return $user->user_nicename;
		} else {
			return "Login";
		}
	}

	public function account_action_header_url() {
		if ( is_user_logged_in() ) {
			return home_url( '/my-account/' );
		} else {
			return  home_url( '/login/?redirect_to=' . $_SERVER[ "REQUEST_URI" ] );
		}
	}

	public function login_form_header_text(){
		if ( !is_user_logged_in() ) {
			return "Log In";
		} else {
			return "Log Out";
		}
	}

	public function login_form_footer_url(){
		if ( is_user_logged_in() ) {
			return "";
		} else {
			return home_url( "/register/" );
		}
	}

	public function login_form_footer_text(){
		if ( is_user_logged_in() ) {
			ob_start();
			?>
				<script class="hide_my_parent_login_form_footer">
					jQuery(document).ready( function() {
						jQuery(".hide_my_parent_login_form_footer").parent().hide();
					});
				</script>
			<?php
			ob_get_clean();
		} else {
			return "Register For An Account";
		}
	}

	public function login_form() {
        if(isset($_SERVER['HTTP_REFERER'])) {
            $redirect_to = $_SERVER[ "HTTP_REFERRER" ];
        } else {
            $redirect_to = home_url( '/my-account/' );
        }
		if ( strpos( $redirect_to, home_url() ) === false ) {
			$redirect_to = home_url( '/my-account/' );
		}
		if ( ( isset( $_GET ) && array_key_exists( "redirect_to", $_GET ) ) ) {
			$redirect_to = $_GET[ "redirect_to" ];
		}
		ob_start();
		if ( !is_user_logged_in() ) {
			echo do_shortcode( '[fl_builder_insert_layout slug="login-form-header" type="fl-builder-template" site="1"]' );
			?>
			<style>
				.forgot-password-link:hover {
					color: #c10230 !important;
				}
				.forgot-password-link {
					color: white !important;
				}
			</style>
			<form name="loginform" id="loginform" action="<?php echo wp_login_url() . '?wpe-login=true'; ?>" method="post">
				<p>
					<label for="user_login"><?php _e( 'Email Address' ); ?><br />
					<input type="text" name="log" id="user_login" class="input" value="" size="20" /></label>
				</p>
				<p>
					<label for="user_pass"><?php _e( 'Password' ); ?><br />
					<input type="password" name="pwd" id="user_pass" class="input" value="" size="20" /></label>
				</p>
				<?php
				/**
				 * Fires following the 'Password' field in the login form.
				 *
				 * @since 2.1.0
				 */
				do_action( 'login_form' );
				?>
				<p class="forgetmenot" style="width: 50%; float: left;"><label for="rememberme"><input name="rememberme" type="checkbox" id="rememberme" value="forever" /> <?php esc_html_e( 'Remember Me' ); ?></label></p>
				<p class="forgetmenot" style="width: 50%; float: left;"><a class="forgot-password-link" href="<?php echo home_url( "/wp-login.php?action=lostpassword"); ?>"><?php esc_html_e( 'Forgot Password' ); ?></a></p>
				<p class="submit">
					<input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e('Log In'); ?>" />
					<input type="hidden" name="redirect_to" value="<?php echo home_url( "/" ) . $_SERVER["REQUEST_URI" ]; ?>" />
				</p>
			</form>
		<?php
		echo do_shortcode( '[fl_builder_insert_layout slug="login-form-footer" type="fl-builder-template" site="1"]' );
		} else {
			if ( !is_admin() && strpos( $_SERVER[ "REQUEST_URI" ], "?fl_builder" ) === false ) {
				wp_redirect( home_url( '/mpservicenet/' ) );
				exit();
			}			
		}
		return ob_get_clean();
	}

	public function rep_export_menu() {
		?>
		<div>
			<div>
				<style>
					.download_representatives_btn {
						/* CHANGE THE BUTTON */
						margin: 0;
						background-color: rgba(0, 0, 0, 0);
						color: #c10330;
						font-family: oswald;
						min-width: 110px;
					}
					.divTable {
						overflow-x: auto;
						margin-bottom: 15px;
						border-radius: 5px;
					}
					.divTableRow {
						border: 1px solid black !important;
					}
					.divTableBody {
						max-width: 1400px;
						width: 100%;
						font-size: 12px;
						color: black;
						display: table;
						overflow-x: auto;
					}

					.divTableBody .divTableRow.divTableHeader {
						display: table-header-group;
						border-bottom: 1px solid #190000 !important;
						text-align: left;
					}
					.divTableBody .divTableHeader .divTableCell {
						font-size: 24px;
						font-family: Oswald, sans-serif;
					}
					.divTableBody .divTableHeader .divTableCell {
						vertical-align: bottom;
					}
					.divTableBody .divTableCell button.button.alt {
						padding-left: 15px;
						padding-right: 15px;
						min-width: 150px;
						margin-left: 10px;
					}
					.divTableRow.header {
						/* ROUND THE HEADER CORNERS */
						border-radius: 8px 8px 0 0;
					}
					.divTableBody .divTableRow:last-of-type {
						/* ROUND THE LAST ROW CORNERS */
						border-radius: 0 0 8px 8px;
					}

					.divTableBody .divTableRow.header {
						/* SET THE HEADER */
						background-color: #cacfd2 !important;
						font-family: Oswald, sans-serif;
						padding-top: 10px;
						padding-bottom: 10px;
						text-align: right;
					}

					.divTableBody .divTableRow.header:after {
						/* BUFFER THE BUTTON SPACE IN THE TABLE */
						content: "";
						min-width: 70px;
					}

					.divTableBody .divTableRow {
						/* SET THE ROW BACKGROUND */
						background-color: #eeeeee;
						display: table-row;
					}

					.divTableBody .divTableRow:nth-of-type(odd) {
						/* STRIPE THE ROWS */
						background-color: #e0e1e2;
					}
					.divTableBody .divTableCell:first-child {
						padding-left: 50px;
					}
					.divTableBody .divTableCell {
						border: none;
						display: table-cell;
						text-align: left;
						padding: 15px 10px;
						vertical-align: middle;
						font-size: 16px;
					}
					.divTableCell .cart {
						display: flex;
						justify-content: space-evenly;
						align-items: center;
					}

					.divTableBody .mcl-card-btn {
						/* CHANGE THE BUTTON */
						margin: 0;
						background-color: rgba(0, 0, 0, 0);
						color: #c10330;
						font-family: oswald;
						min-width: 110px;
					}

					.divTableBody .mcl-card-btn:hover {
						/* MICRO INTERACTION */
						text-decoration: underline;
					}
					.additional {
						margin-bottom: 20px;
					}
					.images {
						margin-bottom: 20px;
					}
				</style>
				<h2>View / Export Representatives</h2>
				<?php echo do_shortcode( "[representative_exporter]" ); ?>
			</div>
		</div>
		<?php
	}

	public function get_exporter_content() {
		$user = wp_get_current_user();
		if ( is_user_logged_in() && !in_array( "administrator", (array) $user->roles ) ) {
			echo "Access Denied";
			die();
		}
		$check = "accounts";
		$value = "";
		if ( array_key_exists( "account_id", $_POST ) ) {
			$value = $_POST[ "account_id" ];
		} else {
			$check = "groups";
			$value = $_POST[ "account_group" ];
		}
		$should_combine_groups = $_POST[ "should_combine_groups" ] === "true";
		ob_start();
		$this->get_rep_exporter_table_content( $value, $check, $should_combine_groups );
		echo ob_get_clean();
		die();
	}

	public function representative_exporter() {
		$user = wp_get_current_user();
		if ( is_user_logged_in() && !in_array( "administrator", (array) $user->roles ) ) {
			return "Access Denied";
		}
		ob_start();
		?>
			<button class="download_representatives_btn">Download Representatives As CSV</button>
			<select id="account_number_filter">
				<option value="">Select Account</option>
				<?php
					$accnts = array();
					foreach ( get_posts( array( "post_status" => "publish", "post_type" => "account", "posts_per_page" => -1 ) ) as $account ) {
						$account_number = get_field( "cust", $account->ID );
						$accnts[$account->post_title] = '<option value="' . $account->ID . '">' . $account_number . ' - ' . $account->post_title . '</option>';
					}
					ksort( $accnts );
					echo implode("", array_values( $accnts ) );
				?>
			</select>
			<select id="account_group_filter">
				<option value="">Select Account Grouping</option>
				<?php
					$accnt_groupings = array();
					foreach ( get_posts( array( "post_status" => "publish", "post_type" => "account_grouping", "posts_per_page" => -1 ) ) as $account_grouping ) {
						$accnt_groupings[$account_grouping->post_title] = '<option value="' . $account_grouping->ID . '">' . $account_grouping->post_title . '</option>';
					}
					ksort( $accnt_groupings );
					echo implode("", array_values( $accnt_groupings ) );
				?>
			</select>
			<select id="should_combine_accounts_filter">
				<option value="false">Should Combine Account Groupings Into Accounts Column?</option>
				<option value="false">NO</option>
				<option value="true">YES</option>			
			</select>
			<?php $this->get_rep_exporter_table_content(); ?>
			<button class="download_representatives_btn">Download Representatives As CSV</button>
		<?php
		return ob_get_clean();
	}

	public function get_rep_exporter_table_content( $value = "", $check = "", $should_combine_groups = false ) {
		$users = get_users();
		if ( !is_array( $users ) ) {
			$users = array();
		}
		$user_data = array();
		?>
			<div class="exporter_content">
				<div class="divTable">
					<div class="divTableBody">
						<div class="divTableRow divTableHeader divTablePrimaryHeader">
							<div class="divTableCell">Email Address</div>
							<div class="divTableCell">First Name</div>
							<div class="divTableCell">Last Name</div>
							<div class="divTableCell">Account Numbers</div>
							<div class="divTableCell">Account Groups</div>
							<div class="divTableCell">Active</div>
							<div class="divTableCell">Verified</div>
							<div class="divTableCell">Expiration Date</div>
							<div class="divTableCell">Is Admin (doesn't expire)</div>
							<div class="divTableCell">Last Login Date</div>
							<div class="divTableCell">Edit User</div>
						</div>
						<?php foreach ( $users as $user ) {
							$date = get_field( "last_login_date", "user_" . $user->ID );
							$is_admin = in_array( 'administrator', (array) $user->roles ) ? "YES" : "NO";
							$exp_date = get_field( "expiration_date", "user_" . $user->ID );
							$verified = get_field( "is_verified", "user_" . $user->ID ) ? "YES" : "NO";
							$active = get_field( "is_active", "user_" . $user->ID ) ? "YES" : "NO";
							if ( $date === false || $date === null ) {
								$date = "Has Not Logged In";
							}
							if ( $check !== "" ) {
								if ( $check === "accounts" ) {
									$checked = $this->master->representatives->post_account_ids( "user_" . $user->ID,  true );
								} else {
									$checked = get_field( "account_groupings", "user_" . $user->ID );
								}	
							} else {
								$checked = array();
							}
												
							$date = get_field( "last_login_date", "user_" . $user->ID );
							if ( $date === false || $date === null ) {
								$date = "Has Not Logged In";
							}
							if ( !is_array( $checked ) ) {
								$checked = array();
							}
							if ( $value !== "" && !in_array( $value, $checked ) ) {
								continue;
							}
							$accnt_numbers = $this->master->representatives->display_account_numbers( "user_" . $user->ID, $should_combine_groups, false, 'accounts', $value, $check );
							$account_groups = $this->master->representatives->display_account_groupings( "user_" . $user->ID, 'account_groupings', false, $value, $check );
							$user_data[] = array(
								"email"    => $this->master->functions->escape_quotes( $user->user_email ),
								"first name" => $this->master->functions->escape_quotes( $user->first_name ),
								"last name" => $this->master->functions->escape_quotes( $user->last_name ),
								"account numbers" => str_replace( "</b>", "", str_replace( "<b style=&#39;color: red; font-size: 1.35em;&#39;>", "", $this->master->functions->escape_quotes( $accnt_numbers ) ) ),
								"account groups" => str_replace( "</b>", "", str_replace( "<b style=&#39;color: red; font-size: 1.35em;&#39;>", "", str_replace( "<br/><br/>", ", ", $this->master->functions->escape_quotes( $account_groups ) ) ) ),
								"last login date" => $date,
								"is active" => $active,
								"is verified" => $verified,
								"expiration date" => $exp_date,
								"is admin" => $is_admin,
							);
							?>
							<div class="divTableRow">
								<div class="divTableCell"><?php echo $user->user_email; ?></div>
								<div class="divTableCell"><?php echo $user->first_name; ?></div>
								<div class="divTableCell"><?php echo $user->last_name; ?></div>
								<div class="divTableCell"><?php echo $accnt_numbers; ?></div>
								<div class="divTableCell"><?php echo $account_groups; ?></div>
								<div class="divTableCell"><?php echo $active; ?></div>
								<div class="divTableCell"><?php echo $verified; ?></div>
								<div class="divTableCell"><?php echo $exp_date; ?></div>
								<div class="divTableCell"><?php echo $is_admin; ?></div>
								<div class="divTableCell"><?php echo $date; ?></div>
								<div class="divTableCell"><a href="<?php echo get_edit_user_link( $user->ID ); ?>">Edit</a></div>
							</div>
						<?php } ?>
						<?php if ( count( $users ) === 0 ) {
							echo $this->get_no_representatives();
						} ?>
						<div class="divTableRow divTableHeader">
							<div class="divTableCell">Email Address</div>
							<div class="divTableCell">First Name</div>
							<div class="divTableCell">Last Name</div>
							<div class="divTableCell">Account Numbers</div>
							<div class="divTableCell">Account Groups</div>
							<div class="divTableCell">Active</div>
							<div class="divTableCell">Verified</div>
							<div class="divTableCell">Expiration Date</div>
							<div class="divTableCell">Is Admin (doesn't expire)</div>
							<div class="divTableCell">Last Login Date</div>
							<div class="divTableCell">Edit User</div>
						</div>
					</div>
				</div>
				<script>
					var maclean_user_data = JSON.parse('<?php echo json_encode( $user_data ); ?>');
				</script>
			</div>
		<?php
	}

	public function no_representatives() {
		echo $this->get_no_representatives();
		die();
	}

	public function get_no_representatives() {
		ob_start();
		?>
			<div class="divTableRow noRepresentatives">
				<div class="divTableCell"></div>
				<div class="divTableCell"></div>
				<div class="divTableCell"></div>
				<div class="divTableCell"></div>
				<div class="divTableCell">No</div>
				<div class="divTableCell"></div>
				<div class="divTableCell">Representatives</div>
				<div class="divTableCell"></div>
				<div class="divTableCell"></div>
				<div class="divTableCell"></div>
				<div class="divTableCell"></div>
			</div>
		<?php
		return ob_get_clean();
	}

	public function no_sub_representatives() {
		echo $this->get_no_sub_representatives();
		die();
	}

	public function get_no_sub_representatives() {
		ob_start();
		?>
			<div class="divTableRow noRepresentatives">
				<div class="divTableCell"></div>
				<div class="divTableCell"></div>
				<div class="divTableCell"></div>
				<div class="divTableCell"></div>
				<div class="divTableCell">No</div>
				<div class="divTableCell"></div>
				<div class="divTableCell">Representatives</div>
				<div class="divTableCell"></div>
				<div class="divTableCell"></div>
				<div class="divTableCell"></div>
				<div class="divTableCell"></div>
			</div>
		<?php
		return ob_get_clean();
	}

	public function after_password_reset( $user, $new_pass ) {
		$this->confirm_password_reset( $user );
	}

	public function woo_login_redirect( $redirect, $user ) {
		$this->confirm_idle_account_login( $user );
		if ( strpos( $redirect, "/login/?redirect_to=" ) !== false ) {
			return urldecode(str_replace( "/login/?redirect_to=", "", $redirect ));
		}
		return $redirect;
	}

	public function login_redirect( $redirect_to, $requested_redirect_to, $user ) {
		$this->confirm_idle_account_login( $user );
		if ( strpos( $redirect_to, "/login/?redirect_to=" ) !== false ) {
			return urldecode(str_replace( "/login/?redirect_to=", "", $redirect_to ));
		}
		return $redirect_to;
	}

	public function authenticate_user( $user, $password ) {
		if ( in_array( 'administrator', (array) $user->roles ) ) {
			return $user;
		}
		$return = $this->authenticate_check( $user );
		if ( !( $return instanceof \WP_User ) ) {
			return $return;
		}
		if ( get_field( "is_suspended", "user_" . $user->ID ) === true ) {
			return new \WP_Error( "broke", "Account Is Suspended: " . get_field( "account_suspended_reason", "user_" . $user->ID ) . " ". do_shortcode( '[fl_builder_insert_layout slug="account-suspended-message" type="fl-builder-template" site="1"]' ));
		}
		return $user;
	}

	public function check_user( $task_item ) {
		$user = get_user_by( "ID", $task_item[ "value" ] );
		$this->authenticate_check( $user );
	}

	public function should_send( $user, $type ) {
		return get_user_meta( $user->ID, 'send_' . $type . '_notice', true ) == true && get_user_meta( $user->ID, 'has_sent_' . $type . '_notice', true ) == false;
	}

	public function send_emails_if_necessary( $task_item ) {
		$user = get_user_by( "ID", $task_item[ "value" ] );
		if ( $this->should_send( $user, '30_day' ) ) {
			$this->toggle_30_day( $user, false );
			$subject  = get_field( "30_day_notice_subject", 'option' );
			$body = get_field( "30_day_notice_body", 'option' );
			$this->master->functions->send_maclean_account_email( $user, $subject, $body, $this->master->functions->get_date(), get_user_meta( $user->ID, "send_notice_reason", true ) );
			update_field( "reactivation_request_link", "", "user_" . $user->ID );
			update_user_meta( $user->ID, "has_sent_30_day_notice", true );
		}
		if ( $this->should_send( $user, '7_day' ) ) {
			$this->toggle_7_day( $user, false );
			$subject  = get_field( "7_day_notice_subject", 'option' );
			$body = get_field( "7_day_notice_body", 'option' );
			$this->master->functions->send_maclean_account_email( $user, $subject, $body, $this->master->functions->get_date(), get_user_meta( $user->ID, "send_notice_reason", true ) );
			update_field( "reactivation_request_link", "", "user_" . $user->ID );
			update_user_meta( $user->ID, "has_sent_7_day_notice", true );
		}
		if ( $this->should_send( $user, '1_day' ) ) {
			$this->toggle_1_day( $user, false );
			$subject  = get_field( "1_day_notice_subject", 'option' );
			$body = get_field( "1_day_notice_body", 'option' );
			$this->master->functions->send_maclean_account_email( $user, $subject, $body, $this->master->functions->get_date(), get_user_meta( $user->ID, "send_notice_reason", true ) );
			update_field( "reactivation_request_link", "", "user_" . $user->ID );
			update_user_meta( $user->ID, "has_sent_1_day_notice", true );
		}
		// if ( $this->should_send( $user, 'confirmation' ) ) {
		// 	$this->toggle_confirmation( $user, false );
		// 	$subject  = get_field( "confirmation_notice_subject", 'option' );
		// 	$body = get_field( "confirmation_notice_body", 'option' );
		// 	$this->master->functions->send_maclean_account_email( $user, $subject, $body, $this->master->functions->get_date());
		// 	update_field( "account_information_confirmation_link", "", "user_" . $user->ID );
		// 	update_user_meta( $user->ID, "has_sent_confirmation_notice", true );
		// }
		if ( $this->should_send( $user, 'suspension' ) ) {
			$this->toggle_suspension( $user, false );
			$subject  = get_field( "suspension_notice_subject", 'option' );
			$body = get_field( "suspension_notice_body", 'option' );
			$this->master->functions->send_maclean_account_email( $user, $subject, $body, $this->master->functions->get_date());
			update_field( "reactivation_request_link", "", "user_" . $user->ID );
			update_user_meta( $user->ID, "has_sent_suspension_notice", true );
		}
		if ( $this->should_send( $user, 'password' ) ) {
			$this->toggle_password( $user, false );
			$subject  = get_field( "password_notice_subject", 'option' );
			$body = get_field( "password_notice_body", 'option' );
			$this->master->functions->send_maclean_account_email( $user, $subject, $body, $this->master->functions->get_date());
			update_field( "password_reset_link", "", "user_" . $user->ID );
			update_user_meta( $user->ID, "has_sent_password_notice", true );
		}
		if ( $this->should_send( $user, 'reactivate' ) ) {
			$this->toggle_reactivate( $user, false );
			$subject  = get_field( "reactivate_notice_subject", 'option' );
			$body = get_field( "reactivate_notice_body", 'option' );
			$this->master->functions->send_maclean_account_email( $user, $subject, $body, $this->master->functions->get_date());
			update_field( "reactivation_request_link", "", "user_" . $user->ID );
			update_user_meta( $user->ID, "has_sent_reactivate_notice", true );
		}
		if ( $this->should_send( $user, 'activate' ) ) {
			$this->toggle_activate( $user, false );
			$subject  = get_field( "activation_notice_subject", 'option' );
			$body = get_field( "activation_notice_body", 'option' );
			$this->master->functions->send_maclean_account_email( $user, $subject, $body, $this->master->functions->get_date());
			update_field( "account_information_confirmation_link", "", "user_" . $user->ID );
			update_user_meta( $user->ID, "has_sent_confirmation_notice", true );
		}
	}

	public function get_days( $current_time, $checked_time ) {
		$subbed = ( $checked_time - $current_time );
		$day = (60*60*24);
		return (($subbed + ($day - ($subbed % $day))) / $day);
	}

	public function authenticate_check( $user ) {	
		$has_updated = false;
		$send_expiration_email = false;
		$user_suspended = get_field( "is_suspended", "user_" . $user->ID ) === true;
		$user_suspended_original = $user_suspended;
		$is_verified = get_field( "is_verified", "user_" . $user->ID ) === true;
		if ( $user_suspended ) {
			return new \WP_Error( 'denied', __( get_field( "account_suspended_reason", "user_" . $user->ID ) ) . " " . do_shortcode( '[fl_builder_insert_layout slug="account-suspended-message" type="fl-builder-template" site="1"]' ));
		} else if ( !$user_suspended && !$is_verified ) {
			return new \WP_Error( 'denied', __( "Please click the link in your email to confirm your account before logging in." ) );
		} else {
			$time = time();
			$user_expires_date = strtotime( get_field( "expiration_date", "user_" . $user->ID ) );
			$days = $this->get_days( $time, $user_expires_date );
			if ( $days ===30 ) {
				$field = get_field( "reactivation_request_link", "user_" . $user->ID );
				if ( $field === false || $field === "" || $field === null ) {
					update_field( "reactivation_request_link", $this->get_request_reactivation_link( $user->ID ), "user_" . $user->ID );
				}
				$val = get_user_meta( $user->ID, 'has_sent_30_day_notice', true );
				if ( $val != true ) {
					$this->toggle_reason( $user, "expiration_date" );
					$this->toggle_30_day( $user, true );
					//$this->toggle_7_day( $user, false );
					//$this->toggle_1_day( $user, false );
				}				
			} else if ( $days === 7 ) {
				$field = get_field( "reactivation_request_link", "user_" . $user->ID );
				if ( $field === false || $field === "" || $field === null ) {
					update_field( "reactivation_request_link", $this->get_request_reactivation_link( $user->ID ), "user_" . $user->ID );
				}
				$val = get_user_meta( $user->ID, 'has_sent_7_day_notice', true );
				if ( $val != true ) {
					$this->toggle_reason( $user, "expiration_date" );
					//$this->toggle_30_day( $user, false );
					$this->toggle_7_day( $user, true );
					//$this->toggle_1_day( $user, false );
				}
			} else if ( $days === 1 ) {
				$field = get_field( "reactivation_request_link", "user_" . $user->ID );
				if ( $field === false || $field === "" || $field === null ) {
					update_field( "reactivation_request_link", $this->get_request_reactivation_link( $user->ID ), "user_" . $user->ID );
				}
				$val = get_user_meta( $user->ID, 'has_sent_1_day_notice', true );
				if ( $val != true ) {
					$this->toggle_reason( $user, "expiration_date" );
					//$this->toggle_30_day( $user, false );
					//$this->toggle_7_day( $user, false );
					$this->toggle_1_day( $user, true );
				}
			} else if ( $days <= 0 ){
				$this->toggle_reason( $user, "expiration_date" );
				$this->suspend_representative( $user, "Your Account Has Expired" );
				$send_expiration_email = false;
				$date_1_month_plus = $user_expires_date + (60*60*24*30);
				if ( $time <= $date_1_month_plus ) {
					$send_expiration_email = true;
				}
				error_log( $send_expiration_email ?"Y":"N" );
				$val = get_user_meta( $user->ID, 'has_sent_suspension_notice', true );
				if ( $val != true ) {
					$this->toggle_reason( $user, "expiration_date" );					
					$this->toggle_suspension( $user, $send_expiration_email );
				}				
			}
			return $user;
		}
		
	}
	
	public function check_representatives() {
		$users = get_users( array( "role__not_in" => "administrator" ) );
		foreach ( $users as $user ) {
			$this->master->background_process->add_to_queue( get_class( $this ), "check_user", $user->ID );
		}
		$this->master->background_process->send_queue();
	}

	public function send_emails_automated() {
		$users = get_users();
		foreach ( $users as $user ) {
			$this->master->background_process_email->add_to_queue( get_class( $this ), "send_emails_if_necessary", $user->ID );
		}
		$this->master->background_process_email->send_queue();
	}

	public function add_pass_reset( $user_ids ) {
		foreach ( $user_ids as $user_id ) {
			$this->master->background_process->add_to_queue( get_class( $this ), "reset_pass", $user_id );
		}
		$this->master->background_process->send_queue();
	}

	public function reset_pass( $task_item ) {
		$field = get_field( "password_reset_link", "user_" . $task_item[ "value" ] );
        if ( $field === false || $field === NULL || $field === "" ) {
			$user = get_user_by( "ID", $task_item[ "value" ] );
            update_field( "password_reset_link", $this->get_password_reset_link( $user->ID ), "user_" . $user->ID );
            $this->master->functions->send_maclean_account_email( $user, get_field( "password_reset_email_subject", "option" ), get_field( "password_reset_email_body", "option" ) );
        }
	}

	public function update_maps_field( $address, $field_name, $lat_long, $object_id ) {
		$parts = explode( ", ", str_replace( "{", "", str_replace( "}", "", $lat_long ) ) );
		if ( count( $parts ) > 0 ) {
			$value = array("address" => $address, "lat" => $parts[0], "lng" => $parts[1], "zoom" => "14");
			update_field($field_name, $value, $object_id);
		}
	}

	public function confirm_idle_account_login( $user ) {
		if ( is_wp_error( $user ) ) {
			return;
		}
		update_field( "last_login_date", $this->master->functions->get_date(), "user_" . $user->ID );
	}

	public function confirm_password_reset( $user ) {
		if ( is_wp_error( $user ) ) {
			return;
		}
		update_field( "password_reset_link", "", "user_" . $user->ID );
	}

	public function suspend_representative( $user, $reason ) {
		if ( is_wp_error( $user ) ) {
			return;
		}
		update_field( "is_suspended", "1", "user_" . $user->ID );
		update_field( "account_suspended_reason", $reason, "user_" . $user->ID );
		update_field( "is_verified", false, "user_" . $user->ID );
		update_field( "is_active", false, "user_" . $user->ID );
		update_field( "reactivation_request_link", $this->get_request_reactivation_link( $user->ID ), "user_" . $user->ID );
	}

	public function process_reactivate_account_request( $user_id ) {
		$user = get_user_by( "ID", $user_id );
		$account_request_type = "Reactivate Existing User Account";
		$first_name = $user->first_name;
		$last_name = $user->last_name;
		$email = $user->user_email;
		$title = "REACTIVATE: $org $email $first_name $last_name";
		if ( ($post = get_page_by_title( "$title", OBJECT, "rep_request" ) ) !== NULL ) {
			return do_shortcode( '[fl_builder_insert_layout slug="account-reactivation-request-already-received" type="fl-builder-template" site="1"]' );
		}
		$uar_id = wp_insert_post(
			array(
				"post_title" => $title,
				"post_status" => "draft",
				"post_type" => "rep_request"
			)
		);
		update_field( "representatives_to_reactivate", array( $user_id ), $uar_id );
		update_field( "request_type", $account_request_type, $uar_id );
		$this->send_reac_request_admin_emails( $uar_id, $first_name, $last_name, $email, "" );
		return true;
	}

	public function reactivate_account( $user ) {
		if ( is_wp_error( $user ) ) {
			return;
		}
		$date = $this->master->functions->get_date();
		update_field( 'modify_date', $date, "user_$user_id" );
		update_field( "account_reactivation_hash", "", "user_" . $user->ID );
		update_field( "reactivation_request_link", "", "user_" . $user->ID );
		update_field( "is_verified", true, "user_" . $user->ID );
		update_field( "is_suspended", false, "user_" . $user->ID );
		update_field( "is_active", true, "user_" . $user->ID );
		update_field( 'modify_date', $date, "user_$user->ID" );
		update_field( "expiration_date", $this->master->functions->get_date( "+6 months" ), "user_" . $user->ID );
		$this->toggle_reactivate( $user, true );
		$this->reset_automation_fields( $user );
	}

	public function activate_account( $user ) {
		if ( is_wp_error( $user ) ) {
			return;
		}
		$date = $this->master->functions->get_date();
		update_field( "account_reactivation_hash", "", "user_" . $user->ID );
		update_field( "reactivation_request_link", "", "user_" . $user->ID );
		update_field( "is_verified", false, "user_" . $user->ID );
		update_field( "is_active", true, "user_" . $user->ID );
		update_field( "is_suspended", false, "user_" . $user->ID );
		update_field( 'modify_date', $date, "user_$user->ID" );
		update_field( 'create_date', $date, "user_$user->ID" );
		update_field( "expiration_date", $this->master->functions->get_date( "+6 months" ), "user_" . $user->ID );
		$field = get_field( "account_information_confirmation_link", "user_" . $user->ID );
		if ( $field === false || $field === "" || $field === null ) {
			update_field( "account_information_confirmation_link", $this->get_account_confirmation_link( $user->ID ), "user_" . $user->ID );
		}
		$this->toggle_activate( $user, true );
		$this->toggle_confirmation( $user, true );
		$this->reset_automation_fields( $user );
	}

	public function reset_automation_fields( $user ) {
		$this->toggle_30_day( $user );
		update_user_meta( $user->ID, "has_sent_30_day_notice", false );
		$this->toggle_7_day( $user );
		update_user_meta( $user->ID, "has_sent_7_day_notice", false );
		$this->toggle_1_day( $user );
		update_user_meta( $user->ID, "has_sent_1_day_notice", false );
	}

	public function toggle_30_day( $user, $should_send = false ) {
		// if ( $should_send ) {
		// 	update_user_meta( $user->ID, "has_sent_30_day_notice", $should_send );
		// }
		update_user_meta( $user->ID, "send_30_day_notice", $should_send );
		update_user_meta( $user->ID, "has_sent_30_day_notice", !$should_send );
	}

	public function toggle_7_day( $user, $should_send = false ) {
		// if ( $should_send ) {
		// 	update_user_meta( $user->ID, "has_sent_7_day_notice", $should_send );
		// }
		update_user_meta( $user->ID, "send_7_day_notice", $should_send );
		update_user_meta( $user->ID, "has_sent_7_day_notice", !$should_send );
	}

	public function toggle_1_day( $user, $should_send = false ) {
		// if ( $should_send ) {
		// 	update_user_meta( $user->ID, "has_sent_1_day_notice", $should_send );
		// }
		update_user_meta( $user->ID, "send_1_day_notice", $should_send );
		update_user_meta( $user->ID, "has_sent_1_day_notice", !$should_send );
	}

	public function toggle_password( $user, $should_send = false ) {
		if ( $should_send ) {
			update_user_meta( $user->ID, "has_sent_password_notice", $should_send );
		}
		update_user_meta( $user->ID, "send_password_notice", $should_send );
		update_user_meta( $user->ID, "has_sent_password_notice", !$should_send );
	}

	public function toggle_confirmation( $user, $should_send = false ) {
		if ( $should_send ) {
			update_user_meta( $user->ID, "has_sent_confirmation_notice", $should_send );
		}
		update_user_meta( $user->ID, "send_confirmation_notice", $should_send );
		update_user_meta( $user->ID, "has_sent_confirmation_notice", !$should_send );
	}

	public function toggle_reason( $user, $reason ) {
		update_user_meta( $user->ID, "send_notice_reason", $reason );
	}

	public function toggle_suspension( $user, $should_send = false ) {
		if ( $should_send ) {
			update_user_meta( $user->ID, "has_sent_suspension_notice", $should_send );
		}
		update_user_meta( $user->ID, "send_suspension_notice", $should_send );
		update_user_meta( $user->ID, "has_sent_suspension_notice", !$should_send );
	}

	public function toggle_reactivate( $user, $should_send = false ) {
		if ( $should_send ) {
			update_user_meta( $user->ID, "has_sent_reactivate_notice", $should_send );
		}
		update_user_meta( $user->ID, "send_reactivate_notice", $should_send );
		update_user_meta( $user->ID, "has_sent_reactivate_notice", !$should_send );
	}	

	public function toggle_activate( $user, $should_send = false ) {
		if ( $should_send ) {
			update_user_meta( $user->ID, "has_sent_activate_notice", $should_send );
		}
		update_user_meta( $user->ID, "send_activate_notice", $should_send );
		update_user_meta( $user->ID, "has_sent_activate_notice", !$should_send );
	}	

	public function get_request_reactivation_link( $user_id ) {
		$hash = get_field( "account_reactivation_hash", "user_" . $user_id );
		if ( $hash === false || $hash === "" || $hash === NULL || empty( $hash ) ) {
			$hash = wp_hash_password( time() . "$user_id" );
			update_field( "account_reactivation_hash", $hash, "user_" . $user_id );
		}
		return home_url( "/register/?reactivate=true&user_id=$user_id&code=" . $hash );
	}

	public function get_account_confirmation_link( $user_id ) {
		$hash = get_field( "account_confirmation_hash", "user_" . $user_id );
		if ( $hash === false || $hash === "" || $hash === null ) {
			$hash = wp_hash_password( time() . "$user_id" );
			update_field( "account_confirmation_hash", $hash, "user_" . $user_id );
		}
		return home_url( "/register/?confirm=true&user_id=$user_id&code=" . $hash );
	}

	public function get_password_reset_link( $user_id ) {
		$user = get_user_by( "ID", $user_id );
		if ( !is_wp_error( $user ) ) {
			$code = get_password_reset_key( $user );
			return add_query_arg( array( "key" => $code, "user" => $user_id ), home_url( "/wp-login.php?action=rp&key=" ) . "$code&login=" . $user->user_login );
		}
		return "";
	}

	public function reset_password( $user, $send_email = true ) {
		$user_id = $user->ID;
		if ( is_wp_error( $user ) ) {
			return;
		}
		$updated = false;
		$field = get_field( "password_reset_link", "user_" . $user_id );
		if ( $field === false || $field === "" || $field === null ) {
			update_field( "password_reset_link", $this->get_password_reset_link( $user->ID ), "user_" . $user_id );
			update_field( "password_reset_date", $this->master->functions->get_date(), "user_" . $user_id );
			$updated = true;
		}
		$this->toggle_password( $user, $send_email );
	}


    public function representative_request_form() {
        if ( is_user_logged_in() && strpos( $_SERVER[ "REQUEST_URI" ], "?fl_builder" ) === false && ! current_user_can( "administrator" ) && strpos( $_SERVER[ "REQUEST_URI" ], "reactivate=true" ) === false ) {
            wp_redirect( home_url( "/mpservicenet/" ) );
            exit();
        } else if ( isset( $_GET ) && array_key_exists( "reactivate", $_GET ) && $_GET[ "reactivate" ] === "true" ) {
            $user_id = intval( $_GET[ "user_id" ] );
            $hash    = $_GET[ "code" ];
            if ( get_field( "account_reactivation_hash", "user_" . $user_id ) === $hash ) {
                $ret = $this->process_reactivate_account_request( $user_id );
                if ( $ret !== true ) {
                    return $ret;
                }
                return do_shortcode( '[fl_builder_insert_layout slug="account-reactivation-request-success" type="fl-builder-template" site="1"]');
            } else {
                return do_shortcode( '[fl_builder_insert_layout slug="account-reactivation-request-invalid" type="fl-builder-template" site="1"]' );
            }
        }  else if ( isset( $_GET ) && array_key_exists( "confirm", $_GET ) && $_GET[ "confirm" ] === "true" ) {
            $user_id = intval( $_GET[ "user_id" ] );
            $hash    = $_GET[ "code" ];
            if ( get_field( "account_confirmation_hash", "user_" . $user_id ) === $hash ) {
				update_field( "is_verified", true, "user_" . $user_id );
				update_field( "account_confirmation_hash", "", "user_" . $user_id );
				update_field( "account_information_confirmation_link", "", "user_" . $user_id );
				wp_redirect( home_url( "/login/" ) );
				exit();
            } else {
                return do_shortcode( '[fl_builder_insert_layout slug="account-reactivation-request-invalid" type="fl-builder-template" site="1"]' );
            }
        } else {
            $password_min_length = 8;
            ob_start();
            ?>
            <style>
                #representative_request_form .error {
                    color: red;
                }
            </style>
            <h1 class="submit-heading"><?= __( 'Submit An Application For An Account' ); ?></h1>
            <form method="post" action="#" id="representative_request_form">
                <div class="first-name">
                    <label for="first_name">
                        <span class="label"><?= __( 'First Name' ); ?></span>
                        <span class="error" style="display: none;"><?= __( 'Invalid first name.' ); ?></span>
                    </label>
                    <input required type="text" name="first_name" id="first_name" />
                </div>
                <div class="last-name">
                    <label for="last_name">
                        <span class="label"><?= __( 'Last Name' ); ?></span>
                        <span class="error" style="display: none;"><?= __( 'Invalid last name.' ); ?></span>
                    </label>
                    <input required type="text" name="last_name" id="last_name" />
                </div>
                <div class="email">
                    <label for="email">
                        <span class="label"><?= __( 'Email' ); ?></span>
                        <span class="error" style="display: none;"><?= __( 'Invalid email address.' ); ?></span>
                    </label>
                    <input required type="email" name="email" id="email" />
                </div>
                <div class="password primary">
                    <label for="password_primary">
                        <span class="label"><?= __( 'Password' ); ?></span>
                        <span class="error" style="display: none;"><?= __( 'Passwords must match.' ); ?></span>
                    </label>
                    <input required type="password" name="password_primary" minlength="<?= $password_min_length ?>"
                           id="password_primary"/>
                </div>
                <div class="password secondary">
                    <label for="password_secondary">
                        <span class="label"><?= __( 'Confirm Password' ); ?></span>
                        <span class="error" style="display: none;"><?= __( 'Passwords must match.' ); ?></span>
                    </label>
                    <input required type="password" name="password_secondary" minlength="<?= $password_min_length ?>"
                           id="password_secondary"/>
                </div>
                <div class="phone">
                    <label for="phone">
                        <span class="label"><?= __( 'Phone Number' ); ?></span>
                        <span class="error" style="display: none;"><?= __( 'Invalid phone number.' ); ?></span>
                    </label>
                    <input required type="text" name="phone" id="phone" />
                </div>
                <div class="address">
                    <div class="city">
                        <label for="city">
                            <span class="label"><?= __( 'City' ); ?></span>
                            <span class="error" style="display: none;"><?= __( 'Invalid city.' ); ?></span>
                        </label>
                        <input required type="text" name="city" id="city"/>
                    </div>
                    <div class="state">
                        <label for="state">
                            <span class="label"><?= __( 'State' ); ?></span>
                            <span class="error" style="display: none;"><?= __( 'Invalid state.' ); ?></span>
                        </label>
                        <input required type="text" name="state" id="state"/>
                    </div>
                    <div class="zip">
                        <label for="zip">
                            <span class="label"><?= __( 'Zip' ); ?></span>
                            <span class="error" style="display: none;"><?= __( 'Invalid zipcode.' ); ?></span>
                        </label>
                        <input required type="text" name="zip" id="zip"/>
                    </div>
                    <div class="country">
                        <label for="country">
                            <span class="label"><?= __( 'Country' ); ?></span>
                            <span class="error" style="display: none;"><?= __( 'Invalid country.' ); ?></span>
                        </label>
                        <input required type="text" name="country" id="country"/>
                    </div>
                </div>
                <div class="hear-about-us">
                    <label for="hear_about_us"><?= __( 'How Did You Hear About Us?' ); ?></label>
                    <select required name="hear_about_us" id="hear_about_us">
                        <option value="" selected disabled><?= __( 'Select...' ); ?></option>
                        <option value="Google Search"><?= __( 'Google Search' ); ?></option>
                        <option value="Sales Rep"><?= __( 'Sales Rep' ); ?></option>
                        <option value="Tradeshow"><?= __( 'Tradeshow' ); ?></option>
                        <option value="LinkedIn / Social Media"><?= __( 'LinkedIn / Social Media' ); ?></option>
                        <option value="Referral"><?= __( 'Referral' ); ?></option>
                        <option value="Other"><?= __( 'Other' ); ?></option>
                    </select>
                </div>
                <div class="sales-rep-agency">
                    <label for="sales_rep_agency">
                        <span class="label"><?= __('Sales Rep Agency'); ?></span>
                    </label>
                    <input type="text" name="sales_rep_agency" id="sales_rep_agency"
                           placeholder="<?= __( 'Optional' ); ?>"/>
                </div>
                <div class="sales-rep-contact-name">
                    <label for="sales_rep_contact_name">
                        <span class="label"><?= __('Sales Rep Contact Name'); ?></span>
                    </label>
                    <input type="text" name="sales_rep_contact_name" id="sales_rep_contact_name"
                           placeholder="<?= __( 'Optional' ); ?>"/>
                </div>
                <div class="controls">
                    <button type="submit" id="representative_edit_accounts_form_submit">
                        <?= __( 'Submit For Approval' ); ?>
                    </button>
                </div>
            </form>
            <div class="messages" id="rep_request_messages" style="display: none;">
                <div class="success" style="display: none;">
					<p><?php echo do_shortcode( '[fl_builder_insert_layout slug="application-submitted-message" type="fl-builder-template" site="1"]' ); ?></p>
                </div>
                <div class="error" style="display: none;">
                    <p id="error_message"></p>
                </div>
            </div>
            <?php
            return ob_get_clean();
        }
	}

 	// AUTO
    public function representative_request_published( $post_id, $new_post, $old_post ) {
        global $wpdb;

        // only apply to rep_request posts changed from draft -> publish
        if ( ! (
            $new_post->post_type === 'rep_request'
            && $old_post->post_status === 'draft'
            && $new_post->post_status === 'publish'
        ) ) {
            return;
        }

        $date = $this->master->functions->get_date();

        $password_hash              = get_field( 'password_hash', $post_id );
		$first_name                 = get_field( 'first_name', $post_id );
		$last_name                  = get_field( 'last_name', $post_id );
		$email                      = get_field( 'email', $post_id );
        $website                    = get_field( 'website', $post_id );
        $phone_number               = get_field( 'phone_number', $post_id );
        $rep_city                   = get_field( 'rep_city', $post_id );
        $rep_state                  = get_field( 'rep_state', $post_id );
        $rep_zipcode                = get_field( 'rep_zipcode', $post_id );
        $country                    = get_field( 'country', $post_id );
        $does_business_with_maclean = get_field( 'does_business_with_maclean', $post_id );
        $does_business_with_other   = get_field( 'does_business_with_other', $post_id );
        $how_did_you_hear_about_us  = get_field( 'how_did_you_hear_about_us', $post_id );
        $sales_rep_agency           = get_field( 'sales_rep_agency', $post_id );
        $sales_rep_contact_name     = get_field( 'sales_rep_contact_name', $post_id );

        $user_id      = null;
        $request_type = get_field( 'request_type', $post_id );

        if ( $request_type === 'Create New User Account' || $request_type === NULL ) {
            if ( get_user_by( 'email', $email ) === false ) {
                $user_id = wp_insert_user(
                    array(
						'user_login'    => $email,
						'first_name'    => $first_name,
						'last_name'     => $last_name,
                        'user_pass'     => 'temporary',
                        'user_email'    => $email,
                        'user_nicename' => $email,
                        'user_url'      => $website,
                        'role'          => 'member'
                    )
                );

                if ( is_integer( $user_id ) ) {
                    // update real password
                    $wpdb->update(
                        $wpdb->users,
                        array( 'user_pass' => $password_hash ),
                        array( 'ID' => $user_id )
                    );

                    // set fields from rep request
                    update_field( 'phone_number', $phone_number, "user_$user_id" );
                    update_field( 'rep_city', $rep_city, "user_$user_id" );
                    update_field( 'rep_state', $rep_state, "user_$user_id" );
                    update_field( 'rep_zipcode', $rep_zipcode, "user_$user_id" );
                    update_field( 'country', $country, "user_$user_id" );
                    update_field( 'does_business_with_maclean', $does_business_with_maclean, "user_$user_id" );
                    update_field( 'does_business_with_other', $does_business_with_other, "user_$user_id" );
                    update_field( 'how_did_you_hear_about_us', $how_did_you_hear_about_us, "user_$user_id" );
                    update_field( 'sales_rep_agency', $sales_rep_agency, "user_$user_id" );
                    update_field( 'sales_rep_contact_name', $sales_rep_contact_name, "user_$user_id" );

                    $user = get_user_by( 'ID', $user_id );
                    $this->activate_account( $user );
                }
            }
        } else if ( $request_type === 'Reactivate Existing User Account' ) {
			$user_id = get_field( 'representatives_to_reactivate', $post_id );
            $user = get_user_by( 'ID', $user_id );
            if ( !is_wp_error( $user ) ) {
                $user_id = $user->ID;
                $this->reactivate_account( $user );
            }
		}

        // delete old post and postmeta
        $wpdb->query( "DELETE FROM {$wpdb->prefix}postmeta WHERE post_id = $post_id" );
        $wpdb->query( "DELETE FROM {$wpdb->prefix}posts WHERE id = $post_id" );

        // redirect
        if ( !$user_id ) {
			wp_redirect( admin_url( "/edit.php?post_type=rep_request" ) );
			exit();
		} else {
			wp_redirect( admin_url( "/user-edit.php?user_id=$user_id" ) );
			exit();
		}
    }

	public function save_posts( $post_id ) {
		$post = get_post( $post_id );
		if ( $post !== null ) {
			if ( $post->post_status === "publish" && $post->post_type === "account_grouping" ) {
				global $wpdb;
				$field = get_field( "account_wildcard", $post->ID);
				$field_2 = get_field( "account_number_wildcard", $post->ID);
				$accounts = get_field( "accounts", $post->ID );
				if ( !is_array( $accounts ) ) {
					$accounts = array();
				} else {
					$accounts = $this->get_accounts_by_name_to_id( $wpdb->get_results( $wpdb->prepare("SELECT id, post_title FROM $wpdb->posts WHERE post_type = 'account' AND id IN (" . implode( ",", $accounts ) . ")", "") ) );
				}
				if ( ( strpos( $field_2, "REMOVE" ) === false && strpos( $field, "REMOVE" ) === false ) && ( ( $field !== null && $field !== "" && $field !== "" ) || ( $field_2 !== null && $field_2 !== "" && $field_2 !== "" ) ) ) {
					$accounts = $accounts + $this->get_accounts_by_name_to_id( $wpdb->get_results( $wpdb->prepare("SELECT p.id, p.post_title FROM $wpdb->posts as p inner join $wpdb->postmeta as pm on p.id = pm.post_id WHERE p.post_status = 'publish' AND pm.meta_key = 'customername' AND pm.meta_value LIKE '%s'", str_replace( "|:|", "%", $wpdb->esc_like( str_replace("%", "|:|", $field ) ) ) ) ) );
					$accounts = $accounts + $this->get_accounts_by_name_to_id( $wpdb->get_results( $wpdb->prepare("SELECT p.id, p.post_title FROM $wpdb->posts as p inner join $wpdb->postmeta as pm on p.id = pm.post_id WHERE p.post_status = 'publish' AND pm.meta_key = 'cust' AND pm.meta_value LIKE '%s'", str_replace( "|:|", "%", $wpdb->esc_like( str_replace("%", "|:|", $field_2 ) ) ) ) ) );
				} else if ( strtoupper( $field ) === "REMOVE ALL" || strtoupper( $field_2 ) === "REMOVE ALL" ) {
					$accounts = array();
				} else if ( strpos( strtoupper( $field ), "REMOVE: " ) !== false || strpos( strtoupper( $field_2 ), "REMOVE: " ) !== false ) {
					$field = str_replace( "REMOVE: ", "", $field );
					$field_2 = str_replace( "REMOVE: ", "", $field_2 );
					if ( $field !== null && $field !== "" && $field !== "" ) {
						$queried_accounts = $this->get_accounts_by_name_to_id( $wpdb->get_results( $wpdb->prepare("SELECT p.id, p.post_title FROM $wpdb->posts as p inner join $wpdb->postmeta as pm on p.id = pm.post_id WHERE p.post_status = 'publish' AND pm.meta_key = 'customername' AND pm.meta_value LIKE '%s'", str_replace( "|:|", "%", $wpdb->esc_like( str_replace("%", "|:|", $field ) ) ) ) ) );
						$accounts = $this->master->functions->array_diff_assoc( $accounts, $queried_accounts );
					}
					if ( $field_2 !== null && $field_2 !== "" && $field_2 !== "" ) {
						$queried_accounts = $this->get_accounts_by_name_to_id( $wpdb->get_results( $wpdb->prepare("SELECT p.id, p.post_title FROM $wpdb->posts as p inner join $wpdb->postmeta as pm on p.id = pm.post_id WHERE p.post_status = 'publish' AND pm.meta_key = 'cust' AND pm.meta_value LIKE '%s'", str_replace( "|:|", "%", $wpdb->esc_like( str_replace("%", "|:|", $field_2 ) ) ) ) ) );
						$accounts = $this->master->functions->array_diff_assoc( $accounts, $queried_accounts );
					}					
				}
				ksort( $accounts );
				$accounts = array_unique( array_values( $accounts ) );
				update_field( "accounts", $accounts, $post->ID );
				update_field( "account_wildcard", "", $post->ID );
				update_field( "account_number_wildcard", "", $post->ID );
			}
		}
	}

	public function get_accounts_by_name_to_id( $array ) {
		$returned_array = array();
		foreach ( $array as $sub_object ) {
			$returned_array[ $sub_object->post_title ] = $sub_object->id;
		}
		return $returned_array;
	}

	public function add_representative_request_post() {
        // sanitize fields
        $first_name            = sanitize_text_field( $_POST[ 'first_name' ] ?? '' );
        $last_name             = sanitize_text_field( $_POST[ 'last_name' ] ?? '' );
        $email                 = sanitize_text_field( $_POST[ 'email' ] ?? '' );
        $password_plaintext    = sanitize_text_field( $_POST[ 'password_primary' ] ?? '' );
        $phone_number          = sanitize_text_field( $_POST[ 'phone' ] ?? '' );
        $city                  = sanitize_text_field( $_POST[ 'city' ] ?? '' );
        $state                 = sanitize_text_field( $_POST[ 'state' ] ?? '' );
        $zip                   = sanitize_text_field( $_POST[ 'zip' ] ?? '' );
        $country               = sanitize_text_field( $_POST[ 'country' ] ?? '' );
        $hear_about_us         = sanitize_text_field( $_POST[ 'hear_about_us' ] ?? '' );
        $rep_agency            = sanitize_text_field( $_POST[ 'sales_rep_agency' ] ?? '' );
        $rep_name              = sanitize_text_field( $_POST[ 'sales_rep_contact_name' ] ?? '' );

        $post_title = "NEW: $email";

        $post = get_page_by_title( $post_title, OBJECT, 'rep_request' );

		$post_id = 0;
        $success = true;
		$message = '';
		
		if ( ($user = get_user_by( 'email', $email )) !== false ) {
			$success = false;
            $message = str_replace("{{login_link_url}}", home_url( '/login/' ), str_replace("{{contact_support_link_url}}", home_url( '/contact-us/' ), get_field( 'req_error_user_exists', 'option' ) ) );
		} else if ( $post ) {
			$post_id = $post->ID;
            // existing post
            $success = false;
            $message = str_replace("{{login_link_url}}", home_url( '/login/' ), str_replace("{{contact_support_link_url}}", home_url( '/contact-us/' ), get_field( 'req_error_already_submitted', 'option' ) ) );
        } else {
            // create new post
            $post_id = wp_insert_post( array(
                'post_title'  => $post_title,
                'post_status' => 'draft',
                'post_type'   => 'rep_request'
            ) );

            $password_hash = wp_hash_password( $password_plaintext );

            // update fields
            if ( $post_id ) {
                update_field( 'first_name', $first_name, $post_id );
                update_field( 'last_name', $last_name, $post_id );
                update_field( 'password_hash', $password_hash, $post_id );
                update_field( 'email', $email, $post_id );
                update_field( 'phone_number', $phone_number, $post_id );
                update_field( 'rep_city', $city, $post_id );
                update_field( 'rep_state', $state, $post_id );
                update_field( 'rep_zipcode', $zip, $post_id );
                update_field( 'country', $country, $post_id );
                update_field( 'how_did_you_hear_about_us', $hear_about_us, $post_id );
                update_field( 'sales_rep_agency', $rep_agency, $post_id );
                update_field( 'sales_rep_contact_name', $rep_name, $post_id );
            } else {
                $success = false;
                $message = str_replace("{{login_link_url}}", home_url( '/login/' ), str_replace("{{contact_support_link_url}}", home_url( '/contact-us/' ), get_field( 'req_error_server_error', 'option' ) ) );
            }
		}
		if ( $success ) {
			// send admin user email
			$this->send_new_request_admin_emails( $post_id, $first_name, $last_name, $email, $city );
		}		
        echo json_encode( array(
            'message' => $message,
            'success' => $success
        ) );
        die();
	}
	
	public function send_new_request_admin_emails( $post_id, $first_name, $last_name, $email, $city ) {
		$emails = get_field( "member_email_recipients", "option" );
		$body = get_field( "new_member_email_body", "option" ) . "<br/><a href='" . admin_url( "post.php?post=$post_id&action=edit" ) . "'>VIEW REQUEST</a><br/><br/>$first_name $last_name<br/>$email<br/>$city";
		$subject = get_field( "new_member_email_subject", "option" );
		$this->master->functions->send_maclean_email( $emails, $subject, $body );
	}

	public function send_reac_request_admin_emails( $post_id, $first_name, $last_name, $email, $city ) {
		$emails = get_field( "member_email_recipients", "option" );
		$body = get_field( "reac_member_email_body", "option" ) . "<br/><a href='" . admin_url( "post.php?post=$post_id&action=edit" ) . "'>VIEW REQUEST</a><br/><br/>$first_name $last_name<br/>$email<br/>$city";
		$subject = get_field( "reac_member_email_subject", "option" );
		$this->master->functions->send_maclean_email( $emails, $subject, $body );
	}
}