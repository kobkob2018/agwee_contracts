<?php
/**
 * @package  AgweeContractsPlugin
 */
/*
Plugin Name: AgweeContracts Plugin
Plugin URI: http://AgweeContracts.com/plugin
Description: This is my first attempt on writing a custom Plugin for this amazing tutorial series.
Version: 1.0.0
Author: Yacov Kobkob Avraham
Author URI: http://AgweeContracts.com
License: GPLv2 or later
Text Domain: agwee-contracts-text
Domain Path: /languages/
*/
/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
Copyright 2005-2015 Automattic, Inc.
*/
defined( 'ABSPATH' ) or die( 'Hey, no entry here. ok?!' );
if ( !class_exists( 'AgweeContractsPlugin' ) ) {
	class AgweeContractsPlugin
	{
		public $pluginName;
		function __construct() {
			$this->pluginName = plugin_basename( __FILE__ );
		}
		function register() {
			if (!session_id()) {
				session_start();
			}
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueueAdminScripts' ) );
			add_action( 'admin_menu', array( $this, 'add_admin_pages' ) );
			add_action('init', array($this,'plugin_init')); 
			add_action('init', array( $this, 'add_ag_contract_form_post_type' ) );
			add_action('template_redirect',array( $this, 'agwee_scripts' ));
			add_filter( "plugin_action_links_$this->pluginName", array( $this, 'settings_link' ) );
			add_shortcode("ag_contract_form", array($this,"print_ag_contract_form"));
			add_shortcode("ag_contract_find", array($this,"print_ag_contract_find_page"));
			add_action( 'rest_api_init', function () {
			  register_rest_route( 'agwee_contracts/user', '/autolog/(?P<tempkey>\d+)', array(
				'methods' => 'GET',
				'callback' => array( $this, 'autolog_user' ),
				'permission_callback' => '__return_true',
			  ) );
			  register_rest_route( 'agwee_contracts/demo', '/contract/(?P<cid>\d+)', array(
				'methods' => 'GET',
				'callback' => array( $this, 'ag_contract_export_demo' ),
				'permission_callback' => '__return_true',
			  ) );			  
			} );
			
		}
		public function plugin_init() {			
			 load_textdomain( 'agwee-contracts-text' , WP_PLUGIN_DIR .'/'.dirname( plugin_basename( __FILE__ ) ) . '/languages/'. get_locale() .'.mo' );
		}
		public function print_ag_contract_find_page($atts){
			wp_enqueue_script(
				'jquery-validate',
				plugin_dir_url( __FILE__ ) . 'style/js/jquery.validate.js',
				array('jquery'),
				'1.10.0',
				true
			);			
			ob_start();			
			require_once plugin_dir_path( __FILE__ ) . 'inc/frontend/contract_functions.php';
			$agweeContracts_frontent = new AgweeContracts_frontend();
			$agweeContracts_frontent->init($this);
			$agweeContracts_frontent->work_contract_find();			
			return ob_get_clean();			
		}
		public function get_contract_demo_url($contract_id){
			return get_site_url()."/wp-json/agwee_contracts/demo/contract/$contract_id";
		}
		public function ag_contract_export_demo($data){

			require_once plugin_dir_path( __FILE__ ) . 'inc/frontend/contract_functions.php';
			$agweeContracts_frontent = new AgweeContracts_frontend();
			$agweeContracts_frontent->init($this);
			$agweeContracts_frontent->work_contract_export_demo($data['cid']);		
		}
		public function autolog_user($data) {
			print_r($data);
			echo "tempkey: ".$data['tempkey'];
		}		
		public function agwee_scripts() {
			//if(is_single() ) {

			//}
		}		
		
		public function print_ag_contract_form($atts){

			wp_enqueue_script(
				'jquery-validate',
				plugin_dir_url( __FILE__ ) . 'style/js/jquery.validate.js',
				array('jquery'),
				'1.10.0',
				true
			);			
			ob_start();			
			require_once plugin_dir_path( __FILE__ ) . 'inc/frontend/contract_functions.php';
			$agweeContracts_frontent = new AgweeContracts_frontend();
			$agweeContracts_frontent->init($this);
			if(isset($atts['id'])){
				$agweeContracts_frontent->work_contract_form($atts['id']);
			}
			else{
				$agweeContracts_frontent->work_contract_form();
			}
			return ob_get_clean();
		}
		
		public function add_ag_contract_form_post_type(){
			register_post_type('ag_contract_form',
				array(
					'labels'      => array(
						'name'          => __('Agwee Contract POST', 'agwee-contracts-text'),
						'singular_name' => __('Agwee Contract POST', 'agwee-contracts-text'),
					),
						'public'      => true,
						'has_archive' => true,
						'rewrite'     => array( 'slug' => 'ag_contract' ), // my custom slug
				)
			);
		}
		public function settings_link( $links ) {
			$settings_link = '<a href="admin.php?page=agweeContracts_plugin">Settings</a>';
			array_push( $links, $settings_link );
			return $links;
		}
		public function add_admin_pages_back() {
			add_menu_page( 'AgweeContracts Plugin', 'AgweeContracts', 'manage_options', 'agweeContracts_plugin', array( $this, 'admin_index' ), 'dashicons-store', 110 ); 
		}
		
function add_admin_pages(){
    add_menu_page('AgweeContracts Plugin',  __('Agwee Contracts', 'agwee-contracts-text'), 'edit_posts', 'agweeContracts_plugin', array($this,'admin_index') );
    add_submenu_page('agweeContracts_plugin', __('Submitted contracts', 'agwee-contracts-text'), __('Submitted contracts', 'agwee-contracts-text'), 'edit_posts', 'agweeContracts_plugin' );
    add_submenu_page('agweeContracts_plugin', __('Contract templates', 'agwee-contracts-text'), __('Contract templates', 'agwee-contracts-text'), 'edit_posts', '?page=agweeContracts_plugin&editor=list' );
}		
		
		public function admin_index() {
			require_once plugin_dir_path( __FILE__ ) . 'inc/admin/contracts_admin.php';
			$agweeContracts_admin = new AgweeContracts_admin();
			$agweeContracts_admin->init($this);			
		}
		/*
		protected function create_post_type() {
			add_action( 'init', array( $this, 'custom_post_type' ) );
		}
		
		function custom_post_type() {
			register_post_type( 'book', ['public' => true, 'label' => 'Books'] );
		}
		*/
		function enqueueAdminScripts() {
			// enqueue all our scripts
			//wp_enqueue_style( 'mypluginstyle', plugins_url( '/assets/mystyle.css', __FILE__ ) );
			//wp_enqueue_script( 'mypluginscript', plugins_url( '/assets/myscript.js', __FILE__ ) );
		}
		
		function activate() {
			require_once plugin_dir_path( __FILE__ ) . 'inc/admin/agweeContracts-plugin-activate.php';
			AgweeContractsActivate::activate();
		}
		public function include_required_file($relative_path){
			require_once(plugin_dir_path(__FILE__ ).$relative_path);
			return;
		}
		public function prepare_email_content_type_html(){
			add_filter( "wp_mail_content_type", array( $this, 'wpse_set_mail_content_type' ) );
		}
		public function wpse_set_mail_content_type(){
			return "text/html";
		}		
		
	}
	$agweeContractsPlugin = new AgweeContractsPlugin();
	$agweeContractsPlugin->register();
	// activation
	register_activation_hook( __FILE__, array( $agweeContractsPlugin, 'activate' ) );
	// deactivation
	require_once plugin_dir_path( __FILE__ ) . 'inc/admin/agweeContracts-plugin-deactivate.php';
	register_deactivation_hook( __FILE__, array( 'AgweeContractsDeactivate', 'deactivate' ) );
}