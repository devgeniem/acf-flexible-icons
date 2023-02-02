<?php
/*
Plugin Name: Advanced Custom Fields: Flexible Content Layout Icons
Plugin URI: https://github.com/devgeniem/acf-flexible-icons
Description: Add an icon for ACF Flexible Content Layouts.
Version: 0.0.2
Author: Miika Arponen / Geniem
Author URI: https://github.com/devgeniem
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

class ACF_Flexible_Icons {
	var $stylesheet;
	var $version;
	var $css = [];

	/*
	 * Hook actions and filters in place
	 */
	public function __construct() {
		add_action( "admin_enqueue_scripts", array( $this, "enqueue_scripts" ) );

		add_action( "wp_ajax_acf_fi_get_select_box", array( $this, "get_select_box" ) );

		add_action( "acf/render_field", array( $this, "get_layout_icons" ) );

		add_action( "acf/input/admin_footer", array( $this, "print_css" ) );
	}

	/*
	 * Get the select box via ajax
	 */
	function get_select_box() {
		$return = (object)[
			"options" => $this->get_icons()
		];

		if ( isset( $_REQUEST["layout_id"] ) && ctype_xdigit( $_REQUEST["layout_id"] ) && isset( $_REQUEST["id"] ) && is_numeric( $_REQUEST["id"] ) ) {
			$field_id = $_REQUEST["layout_id"];
			$post_id = $_REQUEST["id"];
		}
		else {
			wp_send_json_success( $return );
		}

		$post = get_post( $post_id );

		$post_obj = unserialize( $post->post_content );

		$found = false;

		foreach ( $post_obj["layouts"] as $obj ) {
			if ( $obj["key"] == $field_id ) {
				$found = true;
				break;
			}
		}

		if ( ! $found ) {
			wp_send_json_success( $return );
		}

		if ( is_array( $obj ) && isset( $obj["icon"] ) ) {
			if ( ! empty( $obj["icon"] ) ) {
				$return->icon = $obj["icon"];

				wp_send_json_success( $return );
			}
			else {
				wp_send_json_success( $return );
			}
		}
		else {
			wp_send_json_success( $return );
		}
	}

	/*
	 * Get Font Awesome icons
	 */
	function get_icons() {
		require_once ( dirname( __FILE__ ) . '/better-font-awesome-library/better-font-awesome-library.php' );

		$args = array(
			'version'				=> 'latest',
			'minified'				=> true,
			'remove_existing_fa'	=> false,
			'load_styles'			=> false,
			'load_admin_styles'		=> false,
			'load_shortcode'		=> false,
			'load_tinymce_plugin'	=> false
		);

		$bfa 		= Better_Font_Awesome_Library::get_instance( $args );
		$bfa_icons	= $bfa->get_icons();
		$bfa_prefix	= $bfa->get_prefix() . '-';
		$new_icons	= array();

		$this->stylesheet	= $bfa->get_stylesheet_url();
		$this->version		= $bfa->get_version();

		foreach ( $bfa_icons as $hex => $class ) {
			$unicode = '&#x' . ltrim( $hex, '\\') . ';';
			$new_icons[ $bfa_prefix . $class ] = $unicode . ' ' . $bfa_prefix . $class;
		}

		return $new_icons;
	}

	function enqueue_scripts() {
		wp_register_style('font-awesome', $this->stylesheet, array(), $this->version);

		wp_enqueue_style( array( 'font-awesome' ) );

		wp_register_script( "flexible-icon", plugin_dir_url( __FILE__ ) . "js/flexible-icon.js", ["jquery"], "0.0.1", true );

		// Register strings to translate
		$translations = array(
			"layout_icon" => "Layout icon",
			"acf_flexible_icon" => "Choose an icon"
		);

		wp_localize_script( "flexible-icon", "acf_flexible_icon", $translations );

		wp_enqueue_script( "flexible-icon" );
	}

	public function get_layout_icons( $field ) {
		global $wpdb, $post;

		if ( ! isset( $field["parent_layout"] ) ) {
			return;
		}

		if ( is_object( $post ) && isset( $post->ID ) ) {
			$post_id = $post->ID;
		}
		else {
			return;
		}

		$template = get_page_template_slug( $post_id );

		$content = $wpdb->get_var( $wpdb->prepare( "SELECT post_content FROM $wpdb->posts WHERE post_name = %s", $field["parent"] ));

		$content = unserialize( $content );

		$real_icons = $this->get_icons();

		$icons = array();

		if ( is_array( $content ) && isset( $content["layouts" ] ) ) {
			foreach ( $content["layouts"] as $layout ) {
				if ( ! isset( $layout["icon"] ) ) {
					continue;
				}

				$icons[ $layout["name"] ] = $layout["icon"];
			}
		}

		foreach ( $icons as $key => $icon ) {
			if ( empty( $icon ) ) {
				continue;
			}

			if ( isset( $real_icons[ $icon ] ) ) {
				$icon_array = explode( " ", $real_icons[ $icon ] );

				$real_icon = $this->convert_to_css( $icon_array[0] );
			}
			else {
				continue;
			}

			$this->css[] = "div.acf-fc-popup a[data-layout='". $key ."']:before { font-family: 'FontAwesome'; display: inline-block; content: '". $real_icon . "'; color: #ffffff; font-size: 20px; margin-right: 10px;  text-shadow: 2px 2px 4px #0085ba; }\n";
		}
	}

	public function print_css() {
		$this->css = array_unique( $this->css );

		echo "<style>\n";
		foreach ( $this->css as $css ) {
			echo $css;
		}
		echo "div.acf-fc-popup a { line-height: 20px; font-size: 16px; }\n";

		echo "a.acf-button[data-event='add-layout']:before { font-family: 'FontAwesome'; display: inline-block; content: '\\f067'; margin-right: 5px;  text-shadow: 1px 1px 3px black; }\n";
		echo "</style>\n";
	}

	private function convert_to_css( $entity ) {
		return str_replace( array( "&#x", ";"), array( "\\", ""), $entity );
	}
}

new ACF_Flexible_Icons();
