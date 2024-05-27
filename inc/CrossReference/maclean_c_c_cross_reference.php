<?php
namespace MacleanCustomCode\CrossReference;

if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

class MacleanCCCrossReference {
	
	public function __construct( $master ) {
		$this->master = $master;
		$this->setup_actions();
		$this->setup_filters();
		$this->setup_menus();
		$this->setup_shortcodes();
	}
	
	public function setup_actions() {
		add_action( "wp_enqueue_scripts", array( $this, "enqueue_scripts" ) );
	}
	
	public function setup_filters() {
	}	
	
	public function setup_menus() {
	}
	
	public function setup_shortcodes() {
		add_shortcode( "cross_reference_form", array( $this, "cross_reference_form" ) );
		add_shortcode( "cross_reference_results", array( $this, "cross_reference_results" ) );
	}

	public function enqueue_scripts() {
        wp_enqueue_script( "maclean-cross-reference-script", $this->master->url  . "assets/shared/js/cross-reference-functions.js", array( "jquery", "maclean-functions-script" ) );
	}

	public function cross_reference_form() {
		ob_start();
		$val = "";
		if ( isset( $_GET ) && array_key_exists( 'part_number', $_GET ) ) {
			$val = $_GET[ 'part_number' ];
		}
		?>
			<form class="search-form x-ref-search" action="<?php echo home_url( '/cross-reference/' ); ?>" method="GET">
				<input class="search x-ref" type="search" name="part_number" placeholder="Part # or Catalog #" value="<?php echo $val; ?>" isp_ignore>
				<button class="search-submit x-ref-submit">Search Products</button>
			</form>
		<?php
		return ob_get_clean();
	}

	public function cross_reference_results() {
		if ( isset( $_GET ) && array_key_exists( "part_number", $_GET ) ) {
			$part_number = $this->master->functions->clean_data_strict_leave_ast($_GET[ "part_number" ]);
			global $wpdb;
			if ( ($parts_posts = $wpdb->get_results( $wpdb->prepare("SELECT cr.*, pm.post_id FROM mp_cross_reference AS cr LEFT JOIN wp_postmeta AS pm ON pm.meta_value = cr.cleaned_catalog_no AND pm.meta_key = 'CatalogNumberCleaned' AND pm.meta_value != '' WHERE cr.cleaned_part_no LIKE '%s'", '%' . str_replace(":::", "%", $wpdb->esc_like( str_replace("*", ":::", $part_number) ) ) . '%') )) !== null ) {
				$count = 0;
				$string = "";
				foreach ($parts_posts as $part ) {	
					$val = $this->get_table_row( $part_number, $part );
					if ( $val !== "" ) {
						$count++;
						$string .= $val;
					}
				}
				if ( $count === 0 ) {
					return "<h3>No Comparable Parts Found</h3>";
				}
				ob_start();
				?>
					<span class="results_found">
						<span class="results_found_count"><?php echo $count . ( ($count > 1 || $count === 0) ? ' Results' : ' Result' ); ?></span> Found
					</span>
				<?php			
				$string = ob_get_clean() . $this->get_table_header() . $string . $this->get_table_footer();
				return $string;
			} else {
				return "<h3>No Comparable Parts Found</h3>";
			}
			
		}
		return "<h3>Please Search For A Part</h3>";
	}

	private function get_table_header() {
		ob_start();
		?>
			<table class="datatable-cross-reference">
				<thead>                    
					<tr>
						<th>Part Number</th>
						<th>Manufacturer</th>
						<th>MPS Catalog Number</th>
						<th>MPS Description</th>
						<th>Compatibility Of Cross</th>
						<th>Notes</th>
						<th>Go To Product Page</th>
					</th>
				</thead>
				<tbody>	
		<?php
		return ob_get_clean();
	}

	private function get_table_footer() {
		ob_start();
		?>                   
				</tbody>
			</table>
		<?php
		return ob_get_clean();
	}

	private function get_table_row( $part_number, $data ) {
		$part_id = $data->post_id;
		if ( $part_id !== NULL ) {
			$part = get_post( $part_id );
			$term = get_term( $this->master->products->get_the_lowest_category( $part_id )->parent, "product_cat" );
			$link_url = get_term_link( $term->term_id, "product_cat" );
			if ( is_wp_error( $link_url ) ) {
				$link_url = "#";
			}
			$meta = get_post_meta( $part_id, "PartPDFGuid", true );
			if ( $meta !== false && $meta !== null && $meta !== "" ) {
				$catalog_cell = "<a target='_blank' href='" . home_url( "/wp-content/uploads/categories/$meta" ) . "'>" . get_the_title( $part_id ) . "</a>";
			} else {
				$catalog_cell = get_the_title( $part_id );
			}
			ob_start();
			?>
				<tr>
					<td><?php echo $data->partnumber; ?></td>
					<td><?php echo $data->manufacturer; ?></td>
					<td><?php echo $catalog_cell; ?></td>
					<td><?php echo $term->name; ?></td>
					<td><?php echo $data->compatibilityofcross; ?></td>
					<td><?php echo $data->notes; ?></td>
					<td><?php echo $link_url !== "#" ? ("<a class='button' href='" . $link_url . "' target='_blank'>Go To Product Page</a>") : ""; ?></td>
				</tr>
			<?php
			return ob_get_clean();
		} else {
			ob_start();
			?>
				<tr>
					<td><?php echo $data->partnumber; ?></td>
					<td><?php echo $data->manufacturer; ?></td>
					<td><?php echo $data->mpscatalognumber; ?></td>
					<td>-</td>
					<td><?php echo $data->compatibilityofcross; ?><?php echo strlen($data->compatibilityofcross) === 0 ? "-" : ""; ?></td>
					<td><?php echo $data->notes; ?></td>
					<td></td>
				</tr>
			<?php
			return ob_get_clean();
		}
	}
}