<?php
/**
 * Plugin Name: Redvolver CleanACF
 * Plugin URI: http://redvolver.it/
 * Description: Wordpress Plugin to clean unused ACF field.
 * Version: 0.0.1
 * Author: Redvolver
 * Author URI: Wordpress Plugin to clean unused ACF field
 * Text Domain: redvolver
 * Domain Path: /languages/
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'RVCLEANACF_VERSION', '0.0.1' );

if ( ! class_exists( 'Redvolver_Cleanacf' ) ) :

	class Redvolver_Cleanacf {

		private static $instance;

		private $fieldKeys = array();

		/* Constructor */

		public function __construct() {

			add_action( 'plugins_loaded', array( $this, 'add_hooks' ) );

			// Load Translation
			load_plugin_textdomain( 'redvolver' );

			// Plugin Activation/Deactivation
			register_activation_hook( __FILE__, array( $this, 'plugin_activation' ) );
			register_deactivation_hook( __FILE__, array( $this, 'plugin_deactivation' ) );
			//$this->capability = apply_filters( 'regenerate_thumbs_cap', 'manage_options' );
			//include( 'includes/class-rv-db.php' );
		}

		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		public function add_hooks() {
			// Actions
			add_action( 'init', array( $this, 'init' ) );
			
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
			add_action( 'admin_init', array( $this, 'rv_settings' ) );
			
			$options = get_option( 'rvcleanacf_options' );
			if ( !empty($options) && !empty($options['rvcleanacf_onsave']) ) {
				add_action('acf/save_post', array($this, 'cleanACF'),1 );
			}

		}

		public function plugin_activation( ) {
		}

		public function plugin_deactivation( ) {
		}

		public function init() {
			//TODO WPCLI or other integration
		}

		public function admin_menu() {
			$this->tool_menu_id = add_management_page( __( 'Clean ACF', 'redvolver' ), __( 'Clean ACF', 'redvolver' ), 'manage_options', 'rv-cleanacf', array($this, 'rv_cleantool') );
			$this->admin_menu_id = add_options_page( __( 'Clean ACF Settings', 'redvolver' ), __( 'Clean ACF Settings', 'redvolver' ), 'manage_options', 'rv-cleanacf', array($this, 'rv_settings_interface') );
		}

		public function admin_enqueue_scripts( $hook ) {

		}

		public function rv_cleantool() {

		}

		public function rv_settings() {
			
			register_setting( 'rvcleanacf', 'rvcleanacf_options' );

			add_settings_section(
				'rvcleanacf_section',
				__( 'Redvolver Clean ACF', 'redvolver' ),
				array($this,'rvcleanacf_section_callback'),
				'rvcleanacf'
			);

			add_settings_field(
				'rvcleanacf_onsave',
				__( 'Clean ACF On Save', 'redvolver' ),
				array($this,'rvcleanacf_onsave_callback'),
				'rvcleanacf',
				'rvcleanacf_section'
			);

			add_settings_field(
				'rvcleanacf_deleteall', 
				__( 'Clean all ACF Post Meta Before Save', 'redvolver' ),
				array($this,'rvcleanacf_delete_callback'),
				'rvcleanacf',
				'rvcleanacf_section'
			);

		    
 

		}

		public function rvcleanacf_section_callback( $args ) {
		 ?>
		 <p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Redvolver Clean ACF Settings', 'redvolver' ); ?></p>
		 <?php
		}

		public function rvcleanacf_onsave_callback( $args ) {
			$options = get_option( 'rvcleanacf_options' );

			if ( empty($options['rvcleanacf_onsave']) ) $options['rvcleanacf_onsave'] =  false;

		    $html = '<input type="checkbox" id="rvcleanacf_onsave" name="rvcleanacf_options[rvcleanacf_onsave]" value="1"' . checked( 1, $options['rvcleanacf_onsave'], false ) . '/>';
		    $html .= '<label for="rvcleanacf_onsave">'.__('Clean On Post save','redvolver').'</label>';

		    echo $html;
		}

		public function rvcleanacf_delete_callback( $args ) {
			$options = get_option( 'rvcleanacf_options' );

			if ( empty($options['rvcleanacf_deleteall']) ) $options['rvcleanacf_deleteall'] =  false;

		    $html = '<input type="checkbox" id="rvcleanacf_deleteall" name="rvcleanacf_options[rvcleanacf_deleteall]" value="1"' . checked( 1, $options['rvcleanacf_deleteall'], false ) . '/>';
		    $html .= '<label for="rvcleanacf_deleteall">'.__('Clean all ACF Post Meta Before Save','redvolver').'</label>';

		    echo $html;
		}

		public function rv_settings_interface() {
			?>
				<form action="options.php" method="post">
				 <?php
				 settings_fields( 'rvcleanacf' );
				 do_settings_sections( 'rvcleanacf' );
				 submit_button( 'Save Settings' );
				 ?>
				 </form>
			<?
		}

		public function cleanACF ($postId) {
			
			$options = get_option( 'rvcleanacf_options' );

			$this->getAcfFieldKeys($postId);

			$acfmeta = $this->getAllACFMeta($postId);

			if (!$acfmeta) return false;

			if (!empty( $options['rvcleanacf_deleteall'] ) ) {
				$this->deleteAll($acfmeta,$postId);
			}else{
				$this->checkDelete($acfmeta,$postId);
			}
			
		}

		private function checkDelete($acfmeta,$postId) {
			foreach ($acfmeta as $key => $value) {
				
				if ( is_array($value) ) {
					$field = $value['meta_value'];

					$realtitle = $this->getPostByName($field);

					if ($realtitle) {
						if ( $realtitle['post_status'] == 'publish' ) {
							$realname = substr($value['meta_key'], 1);

							if ($realtitle['post_excerpt'] != $realname ) {
								$multifield = $this->flexcheck( $value['meta_key'] );
								if ( $multifield ) {
									$realname = substr($value['meta_key'], 1);
									delete_post_meta($postId,$value['meta_key']);
									delete_post_meta($postId,$realname);
								}
							}
						}
					}
				}
			}
		}

		private function flexcheck($check) {

			$names = $this->fieldName;
			foreach ($names as $name) {
				if ( preg_match("/^_".$name."_/", $check )) {
					return false;
				}
			}
			return true;

		}

		private function deleteAll($acfmeta,$postId) {
			foreach ($acfmeta as $key => $value) {
				
				if (is_array($value)) {
					$field = $value['meta_value'];
					$realname = substr($value['meta_key'], 1);
					delete_post_meta($postId,$value['meta_key']);
					delete_post_meta($postId,$realname);
				}
			}
		}

		
		private function getAcfFieldKeys($postId) {
		    $aFieldObjects = get_field_objects($postId);
		    
		    array_walk_recursive($aFieldObjects, function($val, $key) {
		      if ($key === 'key' && substr($val, 0, 6) === 'field_') {
		        $this->fieldKeys[] = $val;
		      }
		    });

		    array_walk_recursive($aFieldObjects, function($val, $key) {
		      if ($key === 'name' && $val != '' ) {
		        $this->fieldName[] = $val;
		      }
		    });
		}

		

		private function getAllACFMeta($postId) {
			global $wpdb;
			$results = $wpdb->get_results("
				SELECT meta_key,meta_value
				FROM $wpdb->postmeta
				WHERE meta_value LIKE 'field_%'
					AND post_id = $postId
			",ARRAY_A);
			
			if( empty($results) ) return false;

			foreach ($results as $key => $value) {
				if (is_array($value)) {
					$this->keyCheck[] = $value['meta_key'];
				}
			}

			return $results;
		}


		private function getPostByName($title) {
			global $wpdb;
			$results = $wpdb->get_results("
				SELECT ID,post_excerpt,post_status,post_content
				FROM $wpdb->posts
				WHERE post_name = '$title'
				 AND post_type = 'acf-field'
			",ARRAY_A);
	
			if( empty($results) ) return false;

			return $results[0];
		}

		
	}
endif;

/**
 * Init Plugin
 */
Redvolver_Cleanacf::get_instance();