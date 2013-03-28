<?php
/*
 * Plugin Name: Color Style Options
 * Plugin URI: trepmal.com
 * Description: Easy per-post color settings. 
 * Version: 2013.03.29
 * Author: Kailey Lampert
 * Author URI: kaileylampert.com
 * License: GPLv2 or later
 * TextDomain: color-style-options
 * DomainPath: lang
 * Network: false
 */


$color_style_options = new Color_Style_Options();

class Color_Style_Options {

	/**
	 * Color_Style_Options::__construct
	 * 
	 * Get hooked in
	 *
	 * @return void
	 */
	function __construct() {
		//general
		add_filter( 'plugin_action_links_'. plugin_basename( __FILE__ ), array( &$this, 'plugin_action_links' ), 10, 4 );

		//options page
		add_action( 'admin_menu', array( &$this, 'menu' ) );
		add_action( 'wp_ajax_fetch_uniqid', array( &$this, 'fetch_uniqid_cb' ) );

		//meta box
		add_action( 'add_meta_boxes', array( &$this, 'setup_box' ) );
		add_filter( 'is_protected_meta', array( &$this, 'is_protected_meta' ), 10, 2 );
		add_action( 'save_post', array( &$this, 'save_box' ), 10, 2 );

		//output
		add_action( 'wp_head', array( &$this, 'wp_head' ) );
	}

	/**
	 * Color_Style_Options::plugin_action_links
	 * 
	 * Set up admin menu
	 *
	 * @param array $actions Existing plugin actions
	 * @param string $plugin_file Plugin file path, relative to plugins directory
	 * @param array $plugin_data Plugin header data
	 * @param string $context 'mustuse' 'dropins' 'recently_activated' 'active' 'inactive' or 'all'
	 * @return array Plugin actions
	 */
	function plugin_action_links( $actions, $plugin_file, $plugin_data, $context )  {
		// don't show config link if plugin isn't active
		if ( ! is_plugin_active( $plugin_file ) ) return $actions;

		$url = admin_url( '/themes.php?page=' . __CLASS__ );
		$text = __( 'Configure', 'color-style-options' );
		$actions['config'] = "<a href='$url'>$text</a>";
		return $actions;
	}

	/**
	 * Color_Style_Options::menu
	 * 
	 * Set up admin menu
	 *
	 * @return void
	 */
	function menu() {
		add_theme_page( __( 'Color Style Options', 'color-style-options' ), __( 'Color Style Options', 'color-style-options' ), 'edit_posts', __CLASS__, array( &$this, 'page' ) );
	}

	/**
	 * Color_Style_Options::page
	 * 
	 * Output admin page
	 *
	 * @return void
	 */
	function page() {
		wp_enqueue_style( 'color-style-options', plugins_url( 'color-style-options.css', __FILE__ ) );
		wp_enqueue_script( 'color-style-options', plugins_url( 'color-style-options.js', __FILE__ ), array('jquery', 'jquery-ui-sortable'), 1, true );

		$heading_html = '<p><input class="cso_name heading" readonly="readonly" type="text" value="'. __( 'Style Name', 'color-style-options' ) .'" /> <input class="cso_style heading" readonly="readonly" type="text" value="'. __( 'Style Rule', 'color-style-options' ) .'" /></p>';

		// $row_html = '<p><input class="cso_name" type="text" name="pairs[%id%][style_name]" value="Name" /> <input class="cso_style" type="text" name="pairs[%id%][style]" value="#content { background: %color% }" /> <span class="cso_remove hide-if-no-js">&times;</span></p>';
		$row_html = '<p><input class="cso_name" type="text" name="pairs[%id%][style_name]" value="'. __( 'Name', 'color-style-options' ) .'" /> <textarea class="cso_style" name="pairs[%id%][style]" rows="1">#content { background: %color% }</textarea> <span class="cso_remove hide-if-no-js">&times;</span></p>';

		//make it easy to add new rows
		wp_localize_script( 'color-style-options', 'cso', array(
			'ajaxurl' => admin_url('admin-ajax.php'),
			'loading' => admin_url('images/loading.gif'),
			'row_html' => $row_html
		) );

		if ( isset( $_POST['cso_save'] ) ) { // if saving

			if ( wp_verify_nonce( $_POST['cso_save'], __FILE__ ) ) {
				if ( ! isset( $_POST['pairs'] ) ) {
					// update_option( 'color_style_options', array( array() ) );
					$pairs = false;
				} else {
					$pairs = $_POST['pairs'];

					foreach( $pairs as $key => $pair ) {
						// remove any marked for deletion
						if ( 'x' == $pair['style_name'] ) {
							unset( $pairs[ $key ] );
							continue;
						}
						// sanitize
						$pair = array_map( 'strip_tags', $pair );
						// prepare
						$pairs[$key] = array( $pair['style_name'], $pair['style'] );
					}
				}

				// save it
				update_option( 'color_style_options', $pairs );
				echo '<div class="updated"><p>';
				_e( 'Saved!', 'color-style-options' );
				echo '</p></div>';

			} else {
				echo '<div class="error"><p>'.
				_e( 'Error. Nonce check failed.', 'color-style-options' );
				echo '</p></div>';
			}

		}// end if saving
		?><div class="wrap">
		<h2><?php _e('Color Style Options', 'color-style-options' ); ?></h2>

		<?php echo $heading_html; ?>
		<form method="post">
		<?php 
			$pairs = get_option( 'color_style_options', false );

			// if nothing saved, show starter row
			if ( ! $pairs ) {
				echo str_replace( '[%id%]', '['. uniqid() .']', $row_html );
			} else {
				foreach( $pairs as $id => $pair ) {
					list( $name, $style ) = $pair;
					$_row_html = str_replace( '[%id%]', "[$id]", $row_html );
					$_row_html = str_replace( 'value="'. __( 'Name', 'color-style-options' ) .'"', "value='$name'", $_row_html );
					$_row_html = str_replace( '#content { background: %color% }', $style, $_row_html );
					echo $_row_html;
				}
			}

		?>
		<p>
			<?php wp_nonce_field( __FILE__, 'cso_save') ?>
			<?php submit_button( __( 'Save', 'color-style-options' ), 'primary', 'save', false ); ?>
			<?php submit_button( __( 'Add Style', 'color-style-options' ), 'small', 'cso_add_row', false, array('class' => 'hide-if-no-js' ) ); ?>
			<span class="description"><?php
				printf( __( 'Use %s as the placeholder in the style rule.', 'color-style-options' ), '<code>%color%</code>' );
			?></span>
			<span class="description hide-if-js"><?php
				printf( __( 'To delete, change the label to %s.', 'color-style-options' ), '<code>x</code>' );
			?></span>
		</p>
		<span class="description unsaved hidden"><?php _e( 'You have unsaved changes.', 'color-style-options' ); ?></span>
		</form>
		</div><?php
	}

	/**
	 * Color_Style_Options::fetch_uniqid_cb
	 * 
	 * Ajax callback
	 * For my OCD, make sure all elements get keyed the same way
	 *
	 * @return void
	 */
	function fetch_uniqid_cb() {
		die( uniqid() );
	}

	/**
	 * Color_Style_Options::setup_box
	 * 
	 * Setup meta box(es)
	 *
	 * @return void
	 */
	function setup_box() {
		$screens = apply_filters( 'cso_screens', array( 'post' ) );
		foreach( $screens as $s )
		add_meta_box( 'color_options', __( 'Color Options', 'color-style-options' ), array( &$this, 'meta_box_contents' ), $s, 'normal' );
	}

	/**
	 * Color_Style_Options::meta_box_contents
	 * 
	 * Output to meta box
	 *
	 * @return void
	 */
	function meta_box_contents() {

		wp_nonce_field( __FILE__, 'cso_meta_save');

		$pairs = get_option( 'color_style_options', false );
		if ( ! $pairs ) return;

		wp_enqueue_script('wp-color-picker');
		wp_enqueue_style('wp-color-picker');
		wp_enqueue_script( 'color-style-options', plugins_url( 'color-style-options.js', __FILE__ ), array('jquery','wp-color-picker'), 1, true );

		foreach( $pairs as $id => $pair ) {
			list( $name ) = $pair;
			$saved_value = get_post_meta( get_the_ID(), 'cso_'.$id, true );
			?>
			<p><label style="line-height: "><?php echo $name; ?></label><br />
			<input type="text" class="cso_colorpick" name="cso[<?php echo $id; ?>]" value="<?php echo $saved_value; ?>" />
			</p><?php
		}

	}

	/**
	 * Color_Style_Options::is_protected_meta
	 * 
	 * Hide our meta from the Custom Fields box
	 * is_protected_meta is better than the underscore prefix since it makes the meta available after plugin deactivation
	 *
	 * @param bool $protected Original protected status
	 * @param string $meta_key Meta key being checked
	 * @return bool True for protect, False for no
	 */
	function is_protected_meta( $protected, $meta_key ) {
		if ( strpos( $meta_key, 'cso_' ) === 0 ) return true;
		return $protected;
	}

	/**
	 * Color_Style_Options::save_box
	 * 
	 * Save data from custom meta box
	 *
	 * @param int $post_id Post ID
	 * @param object $post Post Object
	 * @return void
	 */
	function save_box( $post_id, $post ) {

		// make sure everything is in order
		if ( ! isset( $_POST['cso_meta_save'] ) ) //make sure our custom value is being sent
			return;
		if ( ! wp_verify_nonce( $_POST['cso_meta_save'], __FILE__ ) ) //verify intent
			return;
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) //no auto saving
			return;
		if ( ! current_user_can( 'edit_post', $post_id ) ) //verify permissions
			return;

		// make sure the values aren't harmful
		$colors = $_POST['cso'];
		$colors = array_map( 'trim', $colors );
		$colors = array_map( 'strip_tags', $colors );

		// save
		foreach( $colors as $n => $c )
			update_post_meta( $post_id, "cso_$n", $c );

	}

		/**
		 * Color_Style_Options::verify_color
		 * 
		 * Verify color value
		 *
		 * @param string $input Possible value
		 * @return string Color value
		 */
		function verify_color( $input ) {
			$input = trim( $color, '#' );
			//make hex-like for is_numeric test
			$test = "0x$input";
			if ( ! is_numeric( $test ) )
				return 'transparent';
			return "#$input";
		}

	/**
	 * Color_Style_Options::wp_head
	 * 
	 * Output styles to front end
	 *
	 * @return void
	 */
	function wp_head() {

		if ( ! is_singular() ) return;

		// bail if no colors to show
		$pairs = get_option( 'color_style_options', false );
		if ( ! $pairs ) return;

		echo "<style>\n";
		foreach( $pairs as $id => $pair ) {
			list( $name, $style ) = $pair;
			$color = get_post_meta( get_the_ID(), "cso_$id", true );
			if ( empty( $color ) ) continue;
			echo str_replace( '%color%', $color, $style ) . "\n";
		}
		echo "</style>\n";

	}
}

//eof