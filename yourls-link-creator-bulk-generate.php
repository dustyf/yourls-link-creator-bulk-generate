<?php
/**
* Plugin Name: Yourls Link Creator Bulk Generate
* Plugin URI:  http://dustyf.com
* Description: Bullk generate Yourls URLs for existing posts
* Version:     1.0.0
* Author:      Dustin Filippini
* Author URI:  http://dustyf.com
* Donate link: http://dustyf.com
* License:     GPLv2
* Text Domain: yourls-link-creator-bulk-generate
 * Domain Path: /languages
 */

/**
 * Copyright (c) 2015 Dustin Filippini (email : dusty@dustyf.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */


/**
 * Main initiation class
 */
class Yourls_Link_Creator_Bulk_Generate {

	const VERSION = '1.0.0';

	protected $url      = '';
	protected $path     = '';
	protected $basename = '';
	protected static $single_instance = null;

	public $generated = array();
	public $had_yourl = array();
	public $error = array();

	/**
	 * Creates or returns an instance of this class.
	 * @since  0.1.0
	 * @return Yourls_Link_Creator_Bulk_Generate A single instance of this class.
	 */
	public static function get_instance() {
		if ( null === self::$single_instance ) {
			self::$single_instance = new self();
		}

		return self::$single_instance;
	}

	/**
	 * Sets up our plugin
	 * @since  1.0.0
	 */
	protected function __construct() {
		$this->basename = plugin_basename( __FILE__ );
		$this->url      = plugin_dir_url( __FILE__ );
		$this->path     = plugin_dir_path( __FILE__ );

		$this->plugin_classes();
		$this->hooks();
	}

	/**
	 * Attach other plugin classes to the base plugin class.
	 * @since 1.0.0
	 */
	function plugin_classes() {
		// Attach other plugin classes to the base plugin class.
		// $this->admin = new YLCBG_Admin( $this );
	}

	/**
	 * Add hooks and filters
	 * @since 1.0.0
	 */
	public function hooks() {
		register_activation_hook( __FILE__, array( $this, '_activate' ) );
		register_deactivation_hook( __FILE__, array( $this, '_deactivate' ) );

		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'load-tools_page_yourls-link-creator-bulk-generate', array( $this, 'process_post' ) );
	}

	/**
	 * Activate the plugin
	 * @since  1.0.0
	 */
	function _activate() {
		// Make sure any rewrite functionality has been loaded
		flush_rewrite_rules();
	}

	/**
	 * Deactivate the plugin
	 * Uninstall routines should be in uninstall.php
	 * @since  1.0.0
	 */
	function _deactivate() {}

	/**
	 * Init hooks
	 * @since  1.0.0
	 * @return null
	 */
	public function init() {
			load_plugin_textdomain( 'yourls-link-creator-bulk-generate', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Magic getter for our object.
	 *
	 * @since  1.0.0
	 * @param string $field
	 * @throws Exception Throws an exception if the field is invalid.
	 * @return mixed
	 */
	public function __get( $field ) {
		switch ( $field ) {
			case 'version':
				return self::VERSION;
			case 'basename':
			case 'url':
			case 'path':
				return $this->$field;
			default:
				throw new Exception( 'Invalid '. __CLASS__ .' property: ' . $field );
		}
	}

	/**
	 * Adds a menu to the tools Menu Section
	 */
	function admin_menu() {

		add_management_page( __( 'Yourls Link Creator Bulk Generate', 'yourls-link-creator-bulk-generate' ), __( 'Yourls Bulk Generate', 'yourls-link-creator-bulk-generate' ), 'manage_options', 'yourls-link-creator-bulk-generate', array( $this, 'admin_page' ) );

	}

	/**
	 * Handles display of the admin page for the plugin.
	 */
	function admin_page() {

		// If Yourls Link Creator plugin doesn't exist, let the user know and don't show the admin page.
		if ( ! class_exists( 'YOURLSCreator' ) ) {
			echo '<div class="wrap">';
			echo '<h2>' . __( 'Yourls Link Creator Bulk Generate', 'yourls-link-creator-bulk-generate' ) . '</h2>';
			echo '<div class="error">' . __( 'This plugin requires the <a href="https://wordpress.org/plugins/yourls-link-creator/">Yourls Link Creator</a> plugin. Please install and activate this plugin.', 'yourls-link-creator-bulk-generate' ) . '</div>';
			echo '</div>';
			return;
		}

		?>
		<div class="wrap">
			<h2><?php _e( 'Yourls Link Creator Bulk Generate', 'yourls-link-creator-bulk-generate' ); ?></h2>
			<?php do_action( 'yourls_link_creator_bulk_generate_notice' ); ?>
			<div class="postbox">
				<div class="inside">
					<p><?php _e( 'Select post types below and click Generate to create Yourls URLs for all existing posts. Be patient, this may take some time if you have a lot of posts. To enable more post types in Yourls, visit the Yourls Link Creator Settings.', 'yourls-link-creator-bulk-generate' ); ?></p>
					<form name="yourls_link_creator_bulk_generate" action="" method="POST">
						<h4><? _e( 'Select Post Types to Generate Yourls For', 'yourls-link-creator-bulk-generate' ); ?></h4>
						<p>
							<?php
							$post_types = array( 'post' );
							$option = get_option( 'yourls_options' );
							if ( isset( $option['typ'] ) ) {
								foreach ( $option['typ'] as $type ) {
									$post_types[] = $type;
								}
							}
							foreach ( $post_types as $post_type ) {
								echo '<label for="ylcbg_' . esc_attr( $post_type ) . '"><input id="ylcbg_' . esc_attr( $post_type ) . '" type="checkbox" name="ylcbg_post_types[]" value="' . esc_attr( $post_type ) . '"' . checked( $post_type, 'post', false ) . '> ' . esc_attr( $post_type ) . '</label><br />';
							}
							?>
						</p>

						<?php echo wp_nonce_field( 'ylcbg_nonce', 'ylcbg_nonce' ); ?>
						<button type="submit" name="ylcbg_submit"><?php _e( 'Generate!', 'yourls-link-creator-bulk-generate' ); ?></button>
					</form>
				</div>
			</div>
		</div>
		<?php

	}

	/**
	 * Checks if a post already has a Yourls URL in meta.
	 *
	 * @param  int    $post_id The ID of the Post.
	 * @return mixed  The Yourls URL if true, otherwise false.
	 */
	private function has_yourl( $post_id ) {

		return get_post_meta( $post_id, '_yourls_url', true );

	}

	/**
	 * Gets posts for looping through and creating Yourls.
	 *
	 * @param  array $post_types An array of post types to create Yourls for.
	 * @return array Array of Post objects.
	 */
	private function get_posts( $post_types = array() ) {

		// If no post type is passed, default to only posts.
		if ( ! $post_types ) {
			$post_types = array( 'post' );
		}
		$args = array(
			'post_type'              => $post_types,
			'posts_per_page'         => 99999,
			'no_found_rows'          => true,
			'update_post_term_cache' => false
		);
		$posts = get_posts( $args );
		return $posts;

	}

	/**
	 * Loops through all posts, checks if they have a Yourls URL, and upates them if they don't.
	 *
	 * @param array $post_types An array of post type keys.
	 * @return bool Returns true.
	 */
	public function generate_yourls( $post_types = array() ) {

		$posts = $this->get_posts( $post_types );

		foreach ( $posts as $post ) {
			if ( ! $this->has_yourl( $post->ID ) ) {
				$update = wp_update_post( $post, true );

				if ( ! is_wp_error( $update ) ) {
					$this->generated[] = $post->ID;
				} else {
					$this->error[] = $post->ID;
				}
			} else {
				$this->had_yourl[] = absint( $post->ID );
			}
		}

		return true;

	}

	/**
	 * Displays an error message if no post types were selected upon submission.
	 */
	public function no_post_types() {

		echo '<div class="error">' . __( 'Please select at least one post type to continue.', 'yourls_link_creator_bulk_generate_notice' ) . '</div>';
	}

	/**
	 * Displays a message stating the process has complted and gives the user some info.
	 */
	public function completed_message() {

		$generated_count = count( $this->generated );
		$had_yourl = count( $this->had_yourl );
		$error = count( $this->error );
		$all_count = $generated_count + $had_yourl + $error;

		echo '<div class="updated">';

		echo '<h3' . __( 'Bulk generate operation completed.', 'yourls_link_creator_bulk_generate_notice' ) . '</h3>';

		echo '<p>' . sprintf( __( '%1$s posts processed. %2$s new Yourls were generated. %3$s had existing Yourls. %4$s errors.', 'yourls_link_creator_bulk_generate_notice' ), $all_count, $generated_count, $had_yourl, $error ) . '</p>';

		echo '<h4>' . __( 'Post IDs With Errors', 'yourls_link_creator_bulk_generate_notice' ) . '</h4>';
		echo '<p>';
		if ( $this->error ) {
			foreach ( $this->error as $err ) {
				echo esc_html( $err ) . ' ';
			}
		} else {
			echo __( 'No errors during process.', 'yourls_link_creator_bulk_generate_notice' );
		}

		echo '<h4>' . __( 'Post IDs With Generated Yourls', 'yourls_link_creator_bulk_generate_notice' ) . '</h4>';
		echo '<p>';
		if ( $this->generated ) {
			foreach ( $this->generated as $gen ) {
				echo esc_html( $gen ) . ' ';
			}
		} else {
			echo __( 'No Yourls generated.', 'yourls_link_creator_bulk_generate_notice' );
		}

		echo '<h4>' . __( 'Post IDs With Existing Yourl', 'yourls_link_creator_bulk_generate_notice' ) . '</h4>';
		echo '<p>';
		if ( $this->had_yourl ) {
			foreach ( $this->had_yourl as $had ) {
				echo esc_html( $had ) . ' ';
			}
		} else {
			echo __( 'No posts had an existing Yourl.', 'yourls_link_creator_bulk_generate_notice' );
		}

		echo '</div>';
	}

	/**
	 * Process the form $_POST to do the stuff we need to do.
	 */
	function process_post() {

		if ( ! empty( $_POST ) && check_admin_referer( 'ylcbg_nonce', 'ylcbg_nonce' ) ) {
			if ( ! isset( $_POST['ylcbg_post_types'] ) ) {
				add_action( 'yourls_link_creator_bulk_generate_notice', array( $this, 'no_post_types' ) );
				return;
			}
			$post_types = array_map( 'esc_attr', $_POST['ylcbg_post_types'] );

			$this->generate_yourls( $post_types );
			add_action( 'yourls_link_creator_bulk_generate_notice', array( $this, 'completed_message' ) );

			return;

		}

	}

}

/**
 * Grab the Yourls_Link_Creator_Bulk_Generate object and return it.
 * Wrapper for Yourls_Link_Creator_Bulk_Generate::get_instance()
 */
function yourls_link_creator_bulk_generate() {
	return Yourls_Link_Creator_Bulk_Generate::get_instance();
}

// Kick it off
yourls_link_creator_bulk_generate();
