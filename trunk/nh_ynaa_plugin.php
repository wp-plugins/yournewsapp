<?php
/*
Plugin Name: Blappsta Plugin
Version: 0.8.4.1

Plugin URI: http://wordpress.org/plugins/yournewsapp/
Description: Blappsta your blog. your app. - The Wordpress Plugin for Blappsta App
Author: Nebelhorn Medien GmbH
Author URI: http://www.nebelhorn.com
Min WP Version: 3.0
License: GPL2
*/

if(isset($_GET['debug'])&& $_GET['debug']==2){
	error_reporting(-1);
}
else {
	error_reporting(0);
}
//Version Number
//Temp fix folder problem
global $nh_ynaa_version;
$nh_ynaa_version = "0.8.4.1";
global $nh_ynaa_db_version;
$nh_ynaa_db_version=1.2;

//Hook for loading
global $nh_menu_hook_ynaa;

//Query vars
define('QUERY_VARS_YNAA','ynaa');


require_once('classes/error_trap.php');

require_once('classes/lang.php');


if(!class_exists('NH_YNAA_Plugin'))
{
    class NH_YNAA_Plugin
    {
		/*
		* For easier overriding we declared the keys
		* here as well as our tabs array which is populated
		* when registering settings
		*/
		private $general_settings_key = 'nh_ynaa_general_settings';	//App Setting like color etc.
		private $menu_settings_key = 'nh_ynaa_menu_settings';		//App Menu Settings
		private $teaser_settings_key = 'nh_ynaa_teaser_settings';	//App Teaser Settings
		private $push_settings_key = 'nh_ynaa_push_settings';		//App Push Settings
		private $categories_settings_key = 'nh_ynaa_categories_settings';		//App Push Settings
		private $homepreset_settings_key = 'nh_ynaa_homepreset_settings';		//App Homepreset Settings
		private $css_settings_key = 'nh_ynaa_css_settings';		//CSS settings
		private $plugin_options_key = 'nh_ynaa_plugin_options';		//Plugin Settings
		private $plugin_settings_tabs = array();					//All Tabs for the Plugin
		public $appmenus_pre = array();								//Vordefinerte App Menüs

		private $requesvar ; // Define Get POST Requst Var


		public $tabs = array(
			// The assoc key represents the ID
			// It is NOT allowed to contain spaces
			 'EXAMPLE' => array(
				 'title'   => 'TEST ME!'
				,'content' => 'FOO'
			 )
		);



		/*
		*Konstanten
		*/
		private $logo_image_width;
		private $logo_image_height;

        /**
         * Construct the plugin object
         */
        public function __construct($logo_image_width=472,$logo_image_height=80)
        {
			$this->logo_image_width = $logo_image_width;
			$this->logo_image_height = $logo_image_height;

			$this->nh_set_request_var();

			//Action Initial App and Set WP Options
			add_action( 'init', array( &$this, 'nh_ynaa_load_settings' ) );
			//update routine
			add_action('init',array(&$this, 'nh_ynaa_update_routine'),1);
			//Action on Plugin Setting Page
			add_action( 'admin_init', array( &$this, 'nh_ynaa_register_general_settings' ) );

			add_action( 'admin_init', array( &$this, 'nh_ynaa_register_menu_settings' ) );

			add_action( 'admin_init', array( &$this, 'nh_ynaa_register_homepreset_settings' ) );
			add_action( 'admin_init', array( &$this, 'nh_ynaa_register_categories_settings' ) );
			add_action( 'admin_init', array( &$this, 'nh_ynaa_register_teaser_settings' ) );
			add_action( 'admin_init', array( &$this, 'nh_ynaa_register_push_settings' ) );
			add_action( 'admin_init', array( &$this, 'nh_ynaa_register_css_settings' ) );

			//add_action( 'admin_init', array( &$this, 'nh_ynaa_qrcode_page' ) );

			//Action to add Menu in Settings
			add_action( 'admin_menu', array( &$this, 'nh_ynaa_add_admin_menus' ) );

			//Action Load JS Script & Css Style Files
			add_action( 'admin_enqueue_scripts', array(&$this, 'nh_ynaa_scripts' ));

			//Action Ad Meta Box in Post for sen Push and to select if Post shown in App

			add_action( 'add_meta_boxes', array(&$this,'nh_ynaa_add_custom_box' ));

			//Action Save if Post visible in  App
			add_action( 'save_post', array(&$this,'nh_ynaa_save_postdata' ));

			//Action Ajax Update Teaser Settings
			add_action('wp_ajax_ny_ynaa_teaser_action', array(&$this,'ny_ynaa_teaser_action'));

			//Action Ajax searcg
			add_action('wp_ajax_nh_search_action', array(&$this,'nh_search_action'));

			//Action Ajax Send Push
			add_action('wp_ajax_ny_ynaa_push_action', array(&$this,'ny_ynaa_push_action'));

			//Action Ajax location
			add_action('wp_ajax_nh_ynaa_googlemap_action', array(&$this,'nh_ynaa_google_action'));
			add_action("wp_ajax_nopriv_nh_ynaa_googlemap_action", array(&$this,"nh_must_login"));

			//Add new Blog in Multisite
			add_action( 'wpmu_new_blog', array(&$this,'nh_new_blog'),100,6);

			add_action('update_option_nh_ynaa_menu_settings', array($this,'nh_update_option_ynaa_menu_settings'),10,2);
			add_action('update_option_nh_ynaa_general_settings', array($this,'nh_update_option_ynaa_general_settings'),10,2);
			add_action('update_option_nh_ynaa_css_settings', array($this,'nh_update_option_ynaa_css_settings'),10,2);
			add_action('update_option_nh_ynaa_teaser_settings', array($this,'nh_update_option_ynaa_teaser_settings'),10,2);
			add_action('update_option_nh_ynaa_homepreset_settings', array($this,'nh_update_option_ynaa_homepreset_settings'),10,2);
			add_action('update_option_nh_ynaa_push_settings', array($this,'nh_update_option_ynaa_push_settings'),10,2);
			add_action('update_option_nh_ynaa_categories_settings', array($this,'nh_update_option_ynaa_categories_settings'),10,2);


        } // END public function __construct



		/**
		* SET up all REquest, POST , GET VAr Name
		*/
		private function nh_set_request_var($prefix = ''){
			if(isset($_GET['nh_prefix'])) $prefix = $_GET['nh_prefix'].'_';
			$this->requesvar['id']= $prefix.'id';
			$this->requesvar['option']= $prefix.'option';
			$this->requesvar['ts']= $prefix.'ts';
			$this->requesvar['sorttype']= $prefix.'sorttype';
			$this->requesvar['post_id']= $prefix.'post_id';
			$this->requesvar['post_ts']= $prefix.'post_ts';
			$this->requesvar['limit']= $prefix.'limit';
			$this->requesvar['offset']= $prefix.'offset';
			$this->requesvar['n']= $prefix.'n';
			$this->requesvar['action']= $prefix.'action';
			$this->requesvar['key']= $prefix.'key';
			$this->requesvar['comment']= $prefix.'comment';
			$this->requesvar['name']= $prefix.'name';
			$this->requesvar['email']= $prefix.'email';
			$this->requesvar['comment_id']= $prefix.'comment_id';
			$this->requesvar['cat_include']=$prefix.'cat_include';
			$this->requesvar['meta']=$prefix.'meta';

			//App Infos
			$this->requesvar['lang']= $prefix.'lang';
			$this->requesvar['b']= $prefix.'b';
			$this->requesvar['h']= $prefix.'h';
			$this->requesvar['pl']= $prefix.'pl';
			$this->requesvar['av']= $prefix.'av';
			$this->requesvar['d']= $prefix.'d';

			//Backend
			$this->requesvar['tab']= $prefix.'tab';
		}// END unction nh_set_request_var

		/**
		* Active Multisite
		*/
		public static function nh_ynaa_activate($networkwide) {
			global $wpdb;

			if (function_exists('is_multisite') && is_multisite()) {
				// check if it is a network activation - if so, run the activation function for each blog id
				if ($networkwide) {
							$old_blog = $wpdb->blogid;
					// Get all blog ids
					$blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
					foreach ($blogids as $blog_id) {
						switch_to_blog($blog_id);
						NH_YNAA_Plugin::_nh_ynaa_activate();

					}
					switch_to_blog($old_blog);
					return;
				}
			}
			NH_YNAA_Plugin::_nh_ynaa_activate();
			NH_YNAA_Plugin::nh_update_db_check();
		}// END public static function nh_ynaa_activate

		/**
		 * Crete new BLOG
		 *
		 */
		public static function nh_ynaa_add_blog($blog_id, $user_id){
			switch_to_blog($blog_id);
			NH_YNAA_Plugin::_nh_ynaa_activate();
		}

        /**
         * Activate the plugin
         */
        public static function _nh_ynaa_activate()
        {
			//ADD version nummer to WP options
            global $nh_ynaa_version;
			//Preset app menu
			$menu_array[0] = array('title'=>__('Browse','nh-ynaa'),'status'=>1,'pos'=>1, 'id'=>0, 'type'=>'app', 'type_text'=>'App');
			//$menu_array[1] = array('title'=>__('Subscription','nh-ynaa'),'status'=>1,'pos'=>2, 'id'=>-99, 'type'=>'app', 'type_text'=>'App');
			$menu_array[2] = array('title'=>__('Notifications','nh-ynaa'),'status'=>1,'pos'=>3, 'id'=>2, 'type'=>'pushCenter', 'type_text'=>__('Pushcenter', 'nh-ynaa'));
			$ts = time();

			$nh_ynaa_menu_settings = array('menu'=>$menu_array,'ts'=>$ts);
			//$menu_array[2] = array('title'=>__('Map','nh-ynaa'),'status'=>1,'pos'=>3, 'id'=>-98, 'type'=>'map', 'type_text'=>'App');
			//$menu_array[5] = array('title'=>__('Events','nh-ynaa'),'status'=>1,'pos'=>3, 'id'=>1, 'type'=>'app', 'type_text'=>'App');

			//Main Pre Setting for App
			include('include/default_css.php');
			/*foreach(self::$lang_de as $k=>$v){
				$lang_en[$k]=$k;

			}*/
			$lang = 'en';
			if(get_bloginfo('language')=='de_DE') $lang='de';
			if('open'==get_option('default_comment_status')) $comments =1;
			else $comments =0;

			/* check if Avada theme is active */
			$is_avada = 0;
			$current_theme = wp_get_theme();
			if($current_theme->Name === "Avada") {
				/* by default, use avada portfolio categories */
				$is_avada = 1;
			}
			$nh_ynaa_general_settings=(array('sort'=>1,'c1'=>'#808080', 'cm'=>'#808080','c2'=>'#ffffff', 'cn'=>'#f2f2f2', 'ct'=>'#c0c0c0', 'ch'=>'#808080', 'csh'=>'#000000','ts'=>$ts, 'comments'=>$comments, 'logo'=> plugins_url( 'img/placeholder.png' , __FILE__ ), 'lang_array'=>$lang_en, 'lang'=>$lang, 'homescreentype'=>1, 'sorttype'=> 'recent' , 'min-img-size-for-resize'=>100, 'theme'=>1, 'avada-categories'=>$is_avada, 'showFeatureImageInPost'=>1));
			$nh_ynnn_css_settings = array('css'=> $css, $ts=>$ts);

			//Preset teaser

			$nh_ynaa_teaser_settings = array('ts'=>0,'teaser'=>false);

			//ADD Options in Wp-Option table
			$ts_setting = get_option( 'nh_ynaa_css_settings_ts' );
			if(!$ts_setting || is_null($ts_setting)){
				update_option('nh_ynaa_css_settings', $nh_ynnn_css_settings);
				update_option('nh_ynaa_css_settings_ts', $ts);
			}
			update_option('nh_ynaa_plugin_version', $nh_ynaa_version);
			$ts_setting = get_option( 'nh_ynaa_general_settings' );
			if(!$ts_setting || is_null($ts_setting)){

				update_option('nh_ynaa_general_settings', $nh_ynaa_general_settings);
			}
			$ts_setting = get_option( 'nh_ynaa_general_settings_ts' );
			if(!$ts_setting || is_null($ts_setting)){
				update_option('nh_ynaa_general_settings_ts', $ts);

			}
			$ts_setting = get_option( 'nh_ynaa_menu_settings' );
			if(!$ts_setting || is_null($ts_setting)){

			update_option('nh_ynaa_menu_settings', $nh_ynaa_menu_settings);
			}
			$ts_setting = get_option( 'nh_ynaa_menu_settings_ts' );
			if(!$ts_setting || is_null($ts_setting)){

				update_option('nh_ynaa_menu_settings_ts', $ts);
			}


			$ts_setting = get_option( 'nh_ynaa_teaser_settings' );
			if(!$ts_setting || is_null($ts_setting)){

				$args = array(
					'numberposts' => 3,
					'offset' => 0,
					'orderby' => 'post_date',
					'order' => 'DESC',
					'post_type' => 'post',
					'post_status' => 'publish' );

				$recent_posts = wp_get_recent_posts( $args, ARRAY_A );
				$nh_ynaa_teaser_settings['limit'] = 3;
				$nh_ynaa_teaser_settings['source'] = 'recent';

				if($recent_posts){
					foreach($recent_posts as $recent){
						$nh_ynaa_teaser_settings['teaser'][]=$recent["ID"];
					}
				}

				add_option('nh_ynaa_teaser_settings', $nh_ynaa_teaser_settings);
			}
			$ts_setting = get_option( 'nh_ynaa_teaser_settings_ts' );
			if(!$ts_setting || is_null($ts_setting)){


				add_option('nh_ynaa_teaser_settings_ts', $ts);
			}

			$nh_ynaa_homepreset_settings['ts'] = $ts;
			$args = array(
				'type'                     => 'post',

				'orderby'                  => 'name',
				'order'                    => 'ASC',
				'hide_empty'               => 1,
				'hierarchical'             => 1,
				'taxonomy'                 => NH_YNAA_Plugin::nh_find_taxonomies_with_avada($is_avada)
			);
			$categories = get_categories( $args );
			$nh_ynaa_categories_settings = array();
			//$nh_ynaa_categories_settings['items'][-1] = array('img'=>'','cat_name'=>__('Events'));
			//$nh_ynaa_categories_settings['items'][-1] = array('img'=>'','cat_name'=>__('Locations'));
			if($categories){
				$i=1;
				foreach($categories as $category){
					 $nh_ynaa_homepreset_settings['items'][] = array('img'=>'', 'title'=>$category->name, 'allowRemove'=>1, 'id' => $category->term_id, 'type'=>'cat', 'id2'=>$i);
					 $nh_ynaa_categories_settings[$category->term_id] = array('img'=>'', 'cat_name'=>$category->name, 'cat_order'=>'date-desc', 'hidecat'=>0, "usecatimg"=>0);
					 $i++;
				}
				$nh_ynaa_categories_settings['ts'] = $ts;
			}
			$nh_ynaa_homepreset_settings['homescreentype']=0;


			$ts_setting = get_option( 'nh_ynaa_homepreset_settings' );
			if(!$ts_setting || is_null($ts_setting)){

				update_option('nh_ynaa_homepreset_settings', $nh_ynaa_homepreset_settings);
			}
			$ts_setting = get_option( 'nh_ynaa_homepreset_settings_ts' );
			if(!$ts_setting || is_null($ts_setting)){

				update_option('nh_ynaa_homepreset_settings_ts', $ts);
			}
			$ts_setting = get_option( 'nh_ynaa_push_settings' );
			if(!$ts_setting || is_null($ts_setting)){

				update_option('nh_ynaa_push_settings', array('pushshow'=>1));
			}
			$ts_setting = get_option( 'nh_ynaa_push_settings_ts' );
			if(!$ts_setting || is_null($ts_setting)){

				update_option('nh_ynaa_push_settings_ts', $ts);
			}
			$ts_setting = get_option( 'nh_ynaa_categories_settings' );
			if(!$ts_setting || is_null($ts_setting)){

				update_option('nh_ynaa_categories_settings', $nh_ynaa_categories_settings);
			}
			$ts_setting = get_option( 'nh_ynaa_categories_settings_ts' );
			if(!$ts_setting || is_null($ts_setting)){

				update_option('nh_ynaa_categories_settings_ts', $ts);
			}
			$ts_setting = get_option( 'nh_ynaa_articles_ts' );
			if(!$ts_setting || is_null($ts_setting)){

				update_option('nh_ynaa_articles_ts', $ts);
			}


        } // END public static function nh_ynaa_activate


		/**
		 * Add Location Table
		 */
		 static function nh_add_db_tables(){
			 global $wpdb;
			 global $nh_ynaa_db_version;
			 $installed_ver = get_option( "nh_ynaa_db_version" );
			 $table_name = $wpdb->prefix . "nh_locations";
			 if( $installed_ver != $nh_ynaa_db_version ) {
				$sql = "CREATE TABLE `$table_name` (
								`location_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
								`post_id` BIGINT(20) UNSIGNED NOT NULL,
								`blog_id` BIGINT(20) UNSIGNED NOT NULL,
								`location_slug` VARCHAR(200) NOT NULL,
								`location_name` TEXT NOT NULL,
								`location_owner` BIGINT(20) UNSIGNED NOT NULL DEFAULT '0',
								`location_address` VARCHAR(200) NOT NULL,
								`location_town` VARCHAR(200) NOT NULL,
								`location_state` VARCHAR(200) NOT NULL,
								`location_postcode` VARCHAR(10) NOT NULL,
								`location_region` VARCHAR(200) NOT NULL,
								`location_country` CHAR(2) NOT NULL,
								`location_latitude` FLOAT(10,6) NOT NULL,
								`location_longitude` FLOAT(10,6) NOT NULL,
								`post_content` LONGTEXT NOT NULL,
								`location_status` INT(1) NOT NULL,
								`location_private` TINYINT(1) NOT NULL DEFAULT '0',
								`location_stamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
								`location_update_stamp` DATETIME NOT NULL,
								`location_pintype` VARCHAR(50) NOT NULL DEFAULT 'red',
								PRIMARY KEY (`location_id`),
								INDEX `location_state` (`location_state`),
								INDEX `location_region` (`location_region`),
								INDEX `location_country` (`location_country`),
								INDEX `post_id` (`post_id`),
								INDEX `blog_id` (`blog_id`)
							);
							";
				require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
				dbDelta( $sql );
				update_option( "nh_ynaa_db_version", $nh_ynaa_db_version );
			 }
		 }


		 /*
		 * Plugin DB check
		 */
		 static function nh_update_db_check() {
			global $nh_ynaa_db_version;

			if (get_option( 'nh_ynaa_db_version' ) != $nh_ynaa_db_version) {
				NH_YNAA_Plugin::nh_add_db_tables();
			}

			global $nh_ynaa_version;
			$nh_ynaa_version_old =  get_option( 'nh_ynaa_plugin_version' );
			if($nh_ynaa_version_old != $nh_ynaa_version){
			  if(ini_get('allow_url_fopen')){
          $content = @file_get_contents('http://www.blappsta.com?bas=extra_infos&url='.urlencode(get_bloginfo('url')));
          if($content){
            $json=json_decode($content,true);
            update_option('nh_ynaa_blappsta', $json);
            update_option('nh_ynaa_blappsta_ts', time());
          }
        }
			}
			if(!$nh_ynaa_version_old || $nh_ynaa_version_old <'0.7.2'){
				$general_settings_old =  get_option( 'nh_ynaa_general_settings' );

				if($general_settings_old['css']){
					update_option('nh_ynaa_css_settings', array('ts'=>$general_settings_old['ts'], 'css'=>$general_settings_old['css']));
					update_option('nh_ynaa_css_settings_ts', $general_settings_old['ts']);
				}
			}
			if(!$nh_ynaa_version_old || $nh_ynaa_version_old <'0.7.5'){
				if(!get_option( 'nh_ynaa_teaser_settings_ts' ))	{
					$teaser_settings_old =  get_option( 'nh_ynaa_teaser_settings' );
					$teaser_settings_old['limit'] = 4;
					$teaser_settings_old['source'] = 'indi';
					if(!$teaser_settings_old['ts'])	$teaser_settings_old['ts']=time();
					update_option('nh_ynaa_teaser_settings', $teaser_settings_old);
					update_option('nh_ynaa_teaser_settings_ts', $teaser_settings_old['ts']);
				}

				if(!get_option( 'nh_ynaa_homepreset_settings_ts' ))	{
					$general_settings_old =  get_option( 'nh_ynaa_general_settings' );
					$homepreset_settings_old =  get_option( 'nh_ynaa_homepreset_settings' );
					$homepreset_settings_old['homescreentype'] = $general_settings_old['homescreentype'];
					$homepreset_settings_old['sorttype'] = $general_settings_old['sorttype'];


					if(!$homepreset_settings_old['ts'])	$homepreset_settings_old['ts']=time();
					update_option('nh_ynaa_homepreset_settings', $homepreset_settings_old);
					update_option('nh_ynaa_homepreset_settings_ts', $homepreset_settings_old['ts'] );
				}

				if(!get_option( 'nh_ynaa_general_settings_ts' ))	{
					$general_settings_old =  get_option( 'nh_ynaa_general_settings' );
					if(!$general_settings_old['ts']){
						$general_settings_old['ts'] = 0;
					}
					update_option('nh_ynaa_general_settings_ts', $general_settings_old['ts']);

				}




			}
			if($nh_ynaa_version_old != $nh_ynaa_version  )		update_option( "nh_ynaa_plugin_version", $nh_ynaa_version );




		}


		/**
		 * Deative multisite
		*/
		public static function nh_ynaa_deactivate($networkwide) {
			global $wpdb;

			if (function_exists('is_multisite') && is_multisite()) {
				// check if it is a network activation - if so, run the activation function
				// for each blog id
				if ($networkwide) {
					$old_blog = $wpdb->blogid;
					// Get all blog ids
					$blogids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");
					foreach ($blogids as $blog_id) {
						switch_to_blog($blog_id);
						NH_YNAA_Plugin::_nh_ynaa_deactivate();
					}
					switch_to_blog($old_blog);
					return;
				}
			}
			NH_YNAA_Plugin::_nh_ynaa_deactivate();
		} // END public static function nh_ynaa_deactivate

        /**
         * Deactivate the plugin
         */
        public static function _nh_ynaa_deactivate()
        {
            //DELETE all  from WP options
		/*	delete_option('nh_ynaa_plugin_version');
			delete_option('nh_ynaa_general_settings');
			delete_option('nh_ynaa_menu_settings');
			delete_option('nh_ynaa_homepreset_settings');
			delete_option('nh_ynaa_teaser_settings');
//			delete_option('nh_ynaa_events_settings');
			delete_option('nh_ynaa_push_settings');
			delete_option('nh_ynaa_categories_settings');*/

        } // END public static function nh_ynaa_deactivate


		/*
		 * Loads the general, menu, teaser and push settings from
		 * the database into their respective arrays. Uses
		 * array_merge to merge with default values if they're
		 * missing.
		 * And Setup default App Menu settings
		 */
		function nh_ynaa_load_settings() {

			$this->general_settings = (array) get_option( $this->general_settings_key );
			$this->menu_settings = (array) get_option( $this->menu_settings_key );
			$this->homepreset_settings = (array) get_option( $this->homepreset_settings_key );
			$this->teaser_settings = (array) get_option( $this->teaser_settings_key );
			$this->push_settings = (array) get_option( $this->push_settings_key );
			$this->categories_settings = (array) get_option($this->categories_settings_key);
			$this->css_settings = (array) get_option($this->css_settings_key);

			// Merge with defaults
			$this->general_settings = array_merge( array(
				'general_option' => __('General value', 'nh-ynaa')
			), $this->general_settings );

			$this->menu_settings = array_merge( array(
				'menu_option' => __('Menu value','nh-ynaa')
			), $this->menu_settings );

			$this->teaser_settings = array_merge( array(
				'teaser_option' => __('Teaser value','nh-ynaa')
			), $this->teaser_settings );

			$this->push_settings = array_merge( array(
				'push_option' => __('Push value','nh-ynaa')
			), $this->push_settings );

			$this->css_settings = array_merge( array(
				'css_settings' => __('CSS','nh-ynaa')
			), $this->css_settings );

			//set app menu
			$this->appmenus_pre[0] = array('title'=>__('Browse','nh-ynaa'),'status'=>1,'pos'=>1, 'id'=>0, 'type'=>'app', 'type_text'=>'App', 'link-typ'=>'cat');
			//$this->appmenus_pre[1] = array('title'=>__('Subscription','nh-ynaa'),'status'=>1,'pos'=>2, 'id'=>-99, 'type'=>'app', 'type_text'=>'App', 'link-typ'=>'cat');

			if(isset($this->general_settings['social_fbid'],$this->general_settings['social_fbsecretid'],$this->general_settings['social_fbappid']))
				$this->appmenus_pre[3] = array('title'=>__('Facebook','nh-ynaa'),'status'=>1,'pos'=>3, 'id'=>-2, 'type'=>'fb', 'type_text'=>'Facebook', 'link-typ'=>'fb');

			if($this->general_settings['eventplugin'])
				$this->appmenus_pre[5] = array('title'=>__('Events','nh-ynaa'),'status'=>0,'pos'=>5, 'id'=>-1, 'type'=>'events', 'type_text'=>'App');

			if(isset($this->general_settings['location']))
				$this->appmenus_pre[6] = array('title'=>__('Map','nh-ynaa'),'status'=>1,'pos'=>6, 'id'=>-98, 'type'=>'map', 'type_text'=>__('App', 'nh-ynaa'), 'link-typ'=>'cat');

			$this->appmenus_pre[7] = array('title'=>__('Extern URL','nh-ynaa'),'status'=>1,'pos'=>7, 'id'=>-3, 'type'=>'webview', 'type_text'=>__('URL', 'nh-ynaa'), 'link-typ'=>'cat');

			$this->appmenus_pre[-96] = array('title'=>__('Notifications','nh-ynaa'),'status'=>1,'pos'=>8, 'id'=>-96, 'type'=>'pushCenter', 'type_text'=>__('Pushcenter', 'nh-ynaa'), 'link-typ'=>'cat');



		} // END  function nh_ynaa_load_settings()


		/**
		 *Update routine
		*/
		function nh_ynaa_update_routine(){
			//$this->general_settings[''];
		}

		/*
		* Multisite loade Settings for new blog
		*/
		public static function nh_new_blog( $domain, $path, $title, $user_id, $meta, $site_id ) {

			global $wpdb;
			$old_blog = $wpdb->blogid;
			update_option('nh_ynaa_general_settings_uma', "$domain, $path, $title, $user_id, $meta, $site_id");
			switch_to_blog($domain);
			NH_YNAA_Plugin::_nh_ynaa_activate();
			switch_to_blog($old_blog);
			/*
			 if ( ! function_exists( 'is_plugin_active_for_network' ) )
    			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
			  if (is_plugin_active_for_network('nh_ynaa/nh_ynaa_plugin.php')) {

				switch_to_blog($domain);
				NH_YNAA_Plugin::_nh_ynaa_activate();
				switch_to_blog($old_blog);
			}*/
		}//END  nh_new_blog


		/*
		 * Registers the general settings via the Settings API,
		 * appends the setting to the tabs array of the object.
		 */
		function nh_ynaa_qrcode_page() {
			$this->plugin_settings_tabs['qrcode'] = __('QR-Code', 'nh-ynaa');

		} // END function nh_ynaa_qrcode_page


		/*
		 * Registers the homepreset settings via the Settings API,
		 * appends the setting to the tabs array of the object.
		 */
		function nh_ynaa_register_homepreset_settings() {
			$this->plugin_settings_tabs[$this->homepreset_settings_key] = __('Homepresets', 'nh-ynaa');
			register_setting( $this->homepreset_settings_key, $this->homepreset_settings_key );

			//Homepreset
			//add_settings_section( 'app_homepreset_settings', __('App Homepreset Settings<br><span>(Only if in startscreen view categories selected)</span>', 'nh-ynaa'), array( &$this, 'nh_ynaa_homepreset_settings_desc' ), $this->homepreset_settings_key );
			add_settings_section( 'app_homepreset_settings', null, array( &$this, 'nh_ynaa_homepreset_settings_desc' ), $this->homepreset_settings_key );
		}	// END function nh_ynaa_register_homepreset_settings

		/*
		 * Registers the general settings via the Settings API,
		 * appends the setting to the tabs array of the object.
		 */
		function nh_ynaa_register_general_settings() {
			$this->plugin_settings_tabs[$this->general_settings_key] = __('App Settings', 'nh-ynaa');

			register_setting( $this->general_settings_key, $this->general_settings_key ,array(&$this,'nh_ynaa_validate_setting'));

			//Logo
			add_settings_section( 'logo_setting', __('Logo', 'nh-ynaa'), array( &$this, 'nh_ynaa_section_general_logo' ), $this->general_settings_key );
			add_settings_field( 'ynaa-logo', __('Select Logo', 'nh-ynaa'). ' ('.$this->logo_image_width.'x'.$this->logo_image_height.')', array( &$this, 'nh_ynaa_field_general_option_logo' ), $this->general_settings_key, 'logo_setting', array('field'=>'logo') );
			//THEME
			add_settings_section( 'theme_setting', __('Theme', 'nh-ynaa'), array( &$this, 'nh_ynaa_section_general_theme' ), $this->general_settings_key );
			add_settings_field( 'ynaa-theme', __('Select theme', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_theme_select' ), $this->general_settings_key, 'theme_setting', array('field'=>'theme') );

			//Color
			add_settings_section( 'app_settings', __('Color And Style Settings', 'nh-ynaa'), array( &$this, 'nh_ynaa_section_general_desc' ), $this->general_settings_key );
			add_settings_field( 'ynaa-c1', __('Primary Color', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_option_color' ), $this->general_settings_key, 'app_settings' , array('field'=>'c1'));
			add_settings_field( 'ynaa-c2', __('Secondary Color', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_option_color' ), $this->general_settings_key, 'app_settings', array('field'=>'c2') );

			add_settings_field( 'ynaa-cn', __('Navigation Bar Color', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_option_color' ), $this->general_settings_key, 'app_settings', array('field'=>'cn') );
			add_settings_field( 'ynaa-cm', __('Menu Text Color', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_option_color' ), $this->general_settings_key, 'app_settings', array('field'=>'cm') );
			add_settings_field( 'ynaa-ch', __('Title Color', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_option_color' ), $this->general_settings_key, 'app_settings', array('field'=>'ch') );
			//add_settings_field( 'ynaa-csh', __('Title 2 Color', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_option_color' ), $this->general_settings_key, 'app_settings', array('field'=>'csh') );
			add_settings_field( 'ynaa-ct', __('Continuous Text Color', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_option_color' ), $this->general_settings_key, 'app_settings', array('field'=>'ct') );
		//

			add_settings_field( 'ynaa-min-img-size-for-resize', __('Maximum width for images so they won‘t scale up (in px)', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_option_input' ), $this->general_settings_key, 'app_settings' , array('field'=>'min-img-size-for-resize'));

			//Hidden Fields
			add_settings_field( 'ynaa-ts', null, array( &$this, 'nh_ynaa_field_general_option_hidden' ), $this->general_settings_key, 'app_settings', array('field'=>'ts') );
			//Social Network
			add_settings_section( 'social_settings', __('Facebook Feed', 'nh-ynaa'), array( &$this, 'nh_ynaa_section_general_social' ), $this->general_settings_key );
			add_settings_field( 'ynaa-social_fbsecretid', __('Facebook App Secret', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_social' ), $this->general_settings_key, 'social_settings', array('field'=>'social_fbsecretid') );
			add_settings_field( 'ynaa-social_fbappid', __('Facebook App ID', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_social' ), $this->general_settings_key, 'social_settings', array('field'=>'social_fbappid') );
			add_settings_field( 'ynaa-social_fbid', __('Facebook Page ID', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_social' ), $this->general_settings_key, 'social_settings', array('field'=>'social_fbid') );

			//Extras
			global $nh_ynaa_db_version;
			add_settings_section( 'extra_settings', __('Extras', 'nh-ynaa'), array( &$this, 'nh_ynaa_section_general_extra' ), $this->general_settings_key );
			add_settings_field( 'ynaa-lang', __('Language', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_language' ), $this->general_settings_key, 'extra_settings' , array('field'=>'lang'));
			add_settings_field( 'ynaa-showFeatureImageInPost', __('Activate feature image in post view', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_extra_sort' ), $this->general_settings_key, 'extra_settings' , array('field'=>'showFeatureImageInPost'));
			add_settings_field( 'ynaa-extra', __('Allow comments in App', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_extra_sort' ), $this->general_settings_key, 'extra_settings' , array('field'=>'comments'));
			//add_settings_field( 'ynaa-homescreentype', __('Startscreen view', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_homescreentype' ), $this->general_settings_key, 'extra_settings' , array('field'=>'homescreentype'));
			//add_settings_field( 'ynaa-sorttype', __('Startscreen articles sorty by <br><span>(Only if startscreen view is articles or pages)</span>', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_sorttype' ), $this->general_settings_key, 'extra_settings' , array('field'=>'sorttype'));
			//add_settings_field( 'ynaa-sort', __('Group by date', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_extra_sort' ), $this->general_settings_key, 'extra_settings' , array('field'=>'sort'));


			add_settings_field( 'ynaa-eventplugin', __('Select your Event Manager:', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_eventplugin' ), $this->general_settings_key, 'extra_settings' , array('field'=>'eventplugin'));
			if (get_option( 'nh_ynaa_db_version' ) == $nh_ynaa_db_version) {
				add_settings_field( 'ynaa-location', __('Enable locations and activate location metabox in posts', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_extra_sort' ), $this->general_settings_key, 'extra_settings' , array('field'=>'location'));
			 }
			 add_settings_field( 'ynaa-gadgetry', __('Use image from Gadgetry-Theme', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_extra_sort' ), $this->general_settings_key, 'extra_settings' , array('field'=>'gadgetry'));
			 add_settings_field( 'ynaa-avada-categories', __('Avada Portfolio Categories', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_extra_sort' ), $this->general_settings_key, 'extra_settings', array('field'=>'avada-categories') );
			 add_settings_field( 'ynaa-gaTrackID', __('Google Analytics Tracking ID', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_social' ), $this->general_settings_key, 'extra_settings' , array('field'=>'gaTrackID'));
			 
			 // Debug Modus

			 add_settings_section( 'advanced_modus', __('Advanced', 'nh-ynaa'), array( &$this, 'nh_ynaa_section_general_extra' ), $this->general_settings_key );

			 add_settings_field( 'ynaa-blank_lines', __('Remove blank lines', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_extra_sort' ), $this->general_settings_key, 'advanced_modus' , array('field'=>'blank_lines'));

			 add_settings_field( 'ynaa-utf8', __('Enable UTF8 encode', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_extra_sort' ), $this->general_settings_key, 'advanced_modus' , array('field'=>'utf8'));
			 add_settings_field( 'ynaa-json_embedded', __('Use embedded JSON', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_extra_sort' ), $this->general_settings_key, 'advanced_modus' , array('field'=>'json_embedded'));
			 add_settings_field( 'ynaa-domcontent', __('Disable dom convert', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_extra_sort' ), $this->general_settings_key, 'advanced_modus' , array('field'=>'domcontent'));
			 add_settings_field( 'ynaa-debug', __('Enable debug mode', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_extra_sort' ), $this->general_settings_key, 'advanced_modus' , array('field'=>'debug'));

			//add_settings_field( 'ynaa-order_value', __('Order posts on overview page by', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_extra_order' ), $this->general_settings_key, 'extra_settings' , array('field'=>'order_value'));




		} //END  function nh_ynaa_register_general_settings()

		/*
		 * Validate Genere Settings Form
		 * @$plugin_options array
		 * return @$plugin_options array
		 */
		function nh_ynaa_validate_setting($plugin_options){
			//check uploade file
			$keys = array_keys($_FILES);
			$i = 0;
			foreach ( $_FILES as $image ) {
				// if a files was upload
				if ($image['size']) {
					// if it is an image
					if ( preg_match('/(jpg|jpeg|png|gif)$/', $image['type']) ) {
						$override = array('test_form' => false);
						// save the file, and store an array, containing its location in $file
						$file = wp_handle_upload( $image, $override );
						if(function_exists('wp_get_image_editor')){
							$img = wp_get_image_editor( $file['file'] ); // Return an implementation that extends <tt>WP_Image_Editor</tt>
							if ( ! is_wp_error( $img ) ) {
								$img->resize( $this->logo_image_width, $this->logo_image_height, true );
								$f = $img->save( $file['file']);
							}
							$plugin_options[$keys[$i]] = dirname($file['url']).'/'.basename($f['path']);
						}
						elseif($file){
							$plugin_options[$keys[$i]] = $file['url'];
						}
					}
					else {       // Not an image.
						$plugin_options[$keys[$i]] = $this->general_settings_key['logo'];
						// Die and let the user know that they made a mistake.
						 add_settings_error( 'app_settings', 'invalid-logo-image-file', _('Select your Logo.' ));
					}
				}
				// Else, the user didn't upload a file.
				// Retain the image that's already on file.
				else {
					$options = get_option($this->general_settings_key);
					$plugin_options[$keys[$i]] = $options[$keys[$i]];
				}
				$i++;
			}
			return $plugin_options;
		} // END function nh_ynaa_validate_setting

		/*
		 * Registers the Menu settings and appends the
		 * key to the plugin settings tabs array.
		 */
		function nh_ynaa_register_menu_settings() {
			$this->plugin_settings_tabs[$this->menu_settings_key] = __('Menu','nh-ynaa');
			register_setting( $this->menu_settings_key, $this->menu_settings_key );

			//Menu
			add_settings_section( 'app_menu_settings', __('App Menu Settings', 'nh-ynaa'), array( &$this, 'nh_ynaa_menu_settings_desc' ), $this->menu_settings_key );

		} //END  function nh_ynaa_register_menu_settings()

		/*
		 * Registers the Teaser settings and appends the
		 * key to the plugin settings tabs array.
		 */
		function nh_ynaa_register_teaser_settings() {
			$this->plugin_settings_tabs[$this->teaser_settings_key] = __('Teaser','nh-ynaa');
			register_setting( $this->teaser_settings_key, $this->teaser_settings_key );
			//Teaser
			add_settings_section( 'app_teaser_settings', __('App Teaser Settings', 'nh-ynaa'), array( &$this, 'nh_ynaa_teaser_settings_desc' ), $this->teaser_settings_key );

		} //END  function nh_ynaa_register_teaser_settings()


		/* Register categories tab */
		function nh_ynaa_register_categories_settings(){

			$this->plugin_settings_tabs[$this->categories_settings_key] = __('Categories','nh-ynaa');
			register_setting( $this->categories_settings_key, $this->categories_settings_key );
			//categories
			add_settings_section( 'categories_settings', __('Categories Settings', 'nh-ynaa'), array( &$this, 'nh_ynaa_categories_settings_desc' ), $this->categories_settings_key );

		} //END function nh_ynaa_register_categories_settings

		/*
		 *
		*/
		function nh_ynaa_field_general_theme_select($field){


			?>
			<select  id="nh_language" name="<?php echo $this->general_settings_key; ?>[<?php echo $field['field']; ?>]" class="nh-floatleft">
                    	<option value="0">Everest</option>
                        <option value="1" <?php if($this->general_settings[$field['field']]=='1') echo ' selected'; ?>>Nebelhorn</option>
                        <!--<option value="2" <?php if($this->general_settings[$field['field']]=='2') echo ' selected'; ?>><?php _e('Hallasan','nh-bas'); ?></option>-->
                        <!--<option value="3" <?php if($this->general_settings[$field['field']]=='3') echo ' selected'; ?>><?php _e('Piz Palü','nh-bas'); ?></option>-->
                    </select>
           <?php
		   echo '<div class="helptext padding5">'.(__('Select your app theme.','nh-ynaa')).'</div>';
		}

		/*
		 * Registers the Push settings and appends the
		 * key to the plugin settings tabs array.
		 */
		function nh_ynaa_register_push_settings() {
			$this->plugin_settings_tabs[$this->push_settings_key] = __('Push & iBeacon','nh-ynaa');
			register_setting( $this->push_settings_key, $this->push_settings_key );
			//Push
			add_settings_section( 'app_push_settings', __('App Push Settings', 'nh-ynaa'), array( &$this, 'nh_ynaa_push_settings_desc' ), $this->push_settings_key );

			add_settings_field( 'ynaa-appkey', __('App Key', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_push_option' ), $this->push_settings_key, 'app_push_settings' , array('field'=>'appkey'));
			add_settings_field( 'ynaa-pushsecret', __('PUSHSECRET', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_push_option' ), $this->push_settings_key, 'app_push_settings' , array('field'=>'pushsecret'));
			//add_settings_field( 'ynaa-pushurl', __('PUSHURL', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_push_option' ), $this->push_settings_key, 'app_push_settings' , array('field'=>'pushurl'));
			add_settings_field( 'ynaa-pushshow', __('Show Push Metabox', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_push_checkbox' ), $this->push_settings_key, 'app_push_settings' , array('field'=>'pushshow'));
      add_settings_field( 'ynaa-autopush', __('Automatic Push send', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_push_checkbox' ), $this->push_settings_key, 'app_push_settings' , array('field'=>'autopush'));
			//Timestamp
			add_settings_field( 'ynaa-ts', null, array( &$this, 'nh_ynaa_field_push_hidden' ), $this->push_settings_key, 'app_push_settings', array('field'=>'ts') );

			//iBeacon
			add_settings_section( 'app_ibeacon_settings', __('iBeacon Settings', 'nh-ynaa'), array( &$this, 'nh_ynaa_ibeacon_settings_desc' ), $this->push_settings_key );
			add_settings_field( 'ynaa-ts', null, array( &$this, 'nh_ynaa_field_general_option_hidden' ), $this->general_settings_key, 'app_ibeacon_settings', array('field'=>'ts') );
			add_settings_field( 'ynaa-uuid', __('UUID ', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_push_option' ), $this->push_settings_key, 'app_ibeacon_settings' , array('field'=>'uuid'));
			add_settings_field( 'ynaa-welcome', __('Welcome text ', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_push_option_textarea' ), $this->push_settings_key, 'app_ibeacon_settings' , array('field'=>'welcome'));
			add_settings_field( 'ynaa-silent', __('Silent intervall (sec) ', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_push_option' ), $this->push_settings_key, 'app_ibeacon_settings' , array('field'=>'silent'));
			$i=0;
			/*if(isset($this->push_settings['ibeacon']) && is_array($this->push_settings['ibeacon']) && count($this->push_settings['ibeacon'])>0){
				foreach($this->push_settings['ibeacon'] as $becon) {
					add_settings_field( 'ynaa-ibeacon', __('iBeacon', 'nh-ynaa').(' '.($i+1)), array( &$this, 'nh_ynaa_field_ibeacon_content_option' ), $this->push_settings_key, 'app_ibeacon_settings' , array('field'=>ibeacon, 'key'=>$i));
					$i++;
				}
			}*/
			//add_settings_field( 'ynaa-ibeacon', __('iBeacon ', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_ibeacon_content_option' ), $this->push_settings_key, 'app_ibeacon_settings' , array('field'=>ibeacon));


		} //END  function nh_ynaa_register_push_settings()

		/*
		 * Registers the Push settings and appends the
		 * key to the plugin settings tabs array.
		 */
		function nh_ynaa_register_css_settings() {
			$this->plugin_settings_tabs[$this->css_settings_key] = __('CSS','nh-ynaa');
			register_setting( $this->css_settings_key, $this->css_settings_key );

			add_settings_section( 'css_settings', __('CSS Style settings', 'nh-ynaa'), array( &$this, 'nh_ynaa_css_settings_desc' ), $this->css_settings_key );


			add_settings_field( 'ynaa-css', __('CSS Style', 'nh-ynaa').'<br>'.__('<span style="font-weight:normal;">Define here your CSS style for the content in the app.</span>','nh-ynaa'), array( &$this, 'nh_ynaa_field_general_option_css' ), $this->css_settings_key, 'css_settings' , array('field'=>'css'));
			//Timestamp
			add_settings_field( 'ynaa-ts', null, array( &$this, 'nh_ynaa_field_css_hidden' ), $this->css_settings_key, 'css_settings', array('field'=>'ts') );
		}//END  function nh_ynaa_register_css_settings()


		/*
		 * The following methods provide descriptions
		 * for their respective sections, used as callbacks
		 * with add_settings_section
		 */
	 	/*function nh_ynaa_section_grcode(){
			echo '<div>88888</div>';
		}*/
		function nh_ynaa_section_general_logo() {  }
		function nh_ynaa_section_general_theme() {  }
		function nh_ynaa_section_general_social() { }
		function nh_ynaa_section_general_extra() { }
		function nh_ynaa_section_general_desc() {  }
		function nh_ynaa_push_settings_desc() { _e('Please enter the push settings here that you have received from Team Blappsta after you have ordered your app. Only when these fields have been filled in correctly will you be able to send push notifications to your community. Please consider: If you wish to send a push notification you need to edit the post it shall be according to. There in the menus you will find the "Push Metabox".','nh-ynaa');  }
		function nh_ynaa_ibeacon_settings_desc() { }
		function nh_ynaa_css_settings_desc() { _e('Here you can add CSS statements tthat are applied to the start view of your app. The standard CSS commands which have been defined for the website will not be regarded in your app.', 'nh-ynaa');  }
		/*function nh_ynaa_events_settings_desc() {
			do_accordion_sections( 'nav-menus', 'side', null );
			$ynaa_menu = '';
			include('include/events.php');
		}*/
		function nh_ynaa_menu_settings_desc() {
			if (function_exists('do_accordion_sections')) {
				do_accordion_sections( 'nav-menus', 'side', null );
			}
			$ynaa_menu = '';
			include('include/menu.php');


		}
		function nh_ynaa_homepreset_settings_desc() {
			if (function_exists('do_accordion_sections')) {
				do_accordion_sections( 'nav-menus', 'side', null );
			}
			$ynaa_menu = '';
			include('include/homepreset.php');

		}
		function nh_ynaa_teaser_settings_desc() {

			global $options;
			foreach ($options as $value) {
			    if (get_option($value['id']) === FALSE) {
			        $$value['id'] = $value['std'];
			    }
			    else {
			        $$value['id'] = get_option( $value['id'] );
			    }
			}
			if (function_exists('do_accordion_sections')) {
				do_accordion_sections( 'nav-menus', 'side', null );
			}
			$ynaa_menu = '';
			include('include/teaser.php');
		}

		function nh_ynaa_categories_settings_desc(){
			$categories = @get_categories(array('orderby'=>'name', 'order'=>'ASC', 'hide_empty'=>0, 'taxonomy' => $this->nh_find_taxonomies()));
			if($categories){
				echo '<p>'.__('Here you can specify the names of the categories in the app individually. Assign categories to the default images that are displayed in the app, should a post or page have no post image. You can also set or define whether the category in the app should be hidden or not the sort order here.', 'nh-ynaa').'</p>';
        echo '<input type="hidden" name="'.$this->categories_settings_key.'[ts]" id="'.$this->categories_settings_key.'_ts" value="'.time().'" />';
				echo '<div id="categorie-div-con" class="categorie-div-con"><ul>';
				foreach($categories as $category){
					 //var_dump($this->categories_settings);
					 //var_dump($this->categories_settings[$category->term_id]);
					 if(!$this->categories_settings[$category->term_id]['cat_name']) $this->categories_settings[$category->term_id]['cat_name']= $category->cat_name;

		
            				$deactivated_cat_class = '';
            				$deactivated_cat_title = '';
            				if($this->categories_settings[$category->term_id]['hidecat']) {
            					$deactivated_cat_class = '-hidden';
								$deactivated_cat_title = __('This category is deactivated in the app', 'nh-ynaa');
            				}
           			?>
			
            		<li class="cat<?php echo $deactivated_cat_class; ?>">
            			
                    	<div class="image-div" id="<?php echo 'image-div'.$category->term_id;  ?>" style="background-image:url('<?php echo $this->categories_settings[$category->term_id]['img'] ?>')" data-link="<?php echo $category->term_id;  ?>" >
                        	<div class="ttitle ttitle<?php echo $deactivated_cat_class; ?>" title="<?php echo $deactivated_cat_title; ?>"><?php echo ($this->categories_settings[$category->term_id]['cat_name']); if($deactivated_cat_class != '') echo '<span>'.__('Category is not visible in the app!','nh-ynaa').'</span>'; ?></div>
                        </div>
                        <div>
                        	<div><a id="upload_image_button<?php echo $category->term_id; ?>" class="upload_image_button" href="#" name="<?php echo $this->categories_settings_key; ?>_items_<?php echo $category->term_id; ?>_img" data-image="<?php echo '#image-div'.$category->term_id;  ?>"   ><?php _e('Set default image for category','nh-ynaa'); ?></a>
           											<input type="hidden" value="<?php echo $this->categories_settings[$category->term_id]['img'] ?>" id="<?php echo $this->categories_settings_key; ?>_items_<?php echo $category->term_id; ?>_img" name="<?php echo $this->categories_settings_key; ?>[<?php echo $category->term_id; ?>][img]" data-id="image-div<?php echo $category->term_id; ?>" data-link="<?php echo $category->term_id;  ?>" /></div>


                            <?php  echo '<div class="reset-cat-img-link-cont" id="reset-cat-img-link-cont_'.$category->term_id.'">';
								if($this->categories_settings[$category->term_id]['img']) echo '<a href="'.$category->term_id.'" class="reset-cat-img-link">'.(__('Reset image', 'nh-ynaa')).'</a>'; else echo '<br>';
								echo '</div>'; ?>
                            <div>

                            	<div class="margin-botton"><input type="text" class="cat-name-input" value="<?php echo $this->categories_settings[$category->term_id]['cat_name']; ?>" name="<?php echo $this->categories_settings_key; ?>[<?php echo $category->term_id; ?>][cat_name]" placeholder="<?php echo $category->name; ?>"></div>
                                <div class="margin-botton"><label><?php _e('Order posts in category:', 'nh-ynaa'); ?></label>
                                <select name="<?php echo $this->categories_settings_key; ?>[<?php echo $category->term_id; ?>][cat_order]" id="cat_order">
                                	<option  value="date-desc" <?php if($this->categories_settings[$category->term_id]['cat_order'] == 'date-desc') echo ' selected '; ?>><?php _e('Recent posts','nh-ynaa') ?></option>
                                    <option value="date-asc" <?php if($this->categories_settings[$category->term_id]['cat_order'] == 'date-asc') echo ' selected '; ?>><?php _e('Oldest posts','nh-ynaa') ?></option>
                                    <option value="alpha-asc" <?php if($this->categories_settings[$category->term_id]['cat_order'] == 'alpha-asc') echo ' selected '; ?>><?php _e('Alphabetically','nh-ynaa') ?></option>
                                </select>
                                </div>
                            	<div class="margin-botton hide-cat-div"><?php _e('Hide this category and all posts in this category in the app:','nh-ynaa'); ?><br>
                                <?php
									if($this->categories_settings[$category->term_id]['hidecat']){
										$yesradio = 'checked';
										$noradio = '';
									}
									else{
										$yesradio = '';
										$noradio = 'checked';
									}
								?>
									<input type="radio" id="hidecat1<?php echo $category->term_id; ?>" name="<?php echo $this->categories_settings_key.'['.$category->term_id.'][hidecat]';?>" <?php echo $yesradio; ?> value="1"> <label for="hidecat1<?php echo $category->term_id; ?>"><?php _e('Yes', 'nh-ynaa');  ?></label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                    <input type="radio" id="hidecat0<?php echo $category->term_id; ?>" name="<?php echo $this->categories_settings_key.'['.$category->term_id.'][hidecat]';?>" value="0" <?php echo $noradio; ?>><label for="hidecat0<?php echo $category->term_id; ?>"><?php _e('No', 'nh-ynaa'); ?></label>
								</div>
                                <div class="use-cat-image-div">
                                	<div class="margin-botton">
                                		<input type="radio" value="0"  name="<?php echo $this->categories_settings_key.'['.$category->term_id.'][usecatimg]';?>" id="use_cat_image1" <?php if(!isset($this->categories_settings[$category->term_id]['usecatimg']) || !$this->categories_settings[$category->term_id]['usecatimg']) echo 'checked'; ?>> <label for="use_cat_image0"><?php _e('Use post image on homescreen', 'nh-ynaa'); ?></label>
                                    </div>
                                    <div class="margin-botton">
                                   	 <input type="radio" value="1"  name="<?php echo $this->categories_settings_key.'['.$category->term_id.'][usecatimg]';?>" id="use_cat_image1" <?php if($this->categories_settings[$category->term_id]['usecatimg'] ) echo 'checked'; ?>> <label for="use_cat_image1"><?php _e('Use category image on homescreen', 'nh-ynaa'); ?></label>
                                     </div>

                                </div>

                            	<div class="margin-botton  show-subcat-div">
							<?php
								if(get_categories(array('hide_empty'=>0, 'child_of'=>$category->term_id, 'taxonomy'=>$category->taxonomy))){
									if($this->categories_settings[$category->term_id]['showsub']){
										$yesradio = 'checked';
										$noradio = '';
										$hidethisdiv = "";
									}
									else{
										$yesradio = '';
										$noradio = 'checked';
										$hidethisdiv = "hidethisdiv";
									}
									echo '<div class="margin-botton">';
									 _e('Show subcategories overview:', 'nh-ynaa');
									 echo '<br><input type="radio" name="'.$this->categories_settings_key.'['.$category->term_id.'][showsub]" value="1" id="yesradio_'.$category->term_id.'" '.$yesradio.' class="showoverviewposts" data-catid="'.$category->term_id.'" /><label for="yesradio_'.$category->term_id.'">'; _e('Yes', 'nh-ynaa');
									 echo '</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="'.$this->categories_settings_key.'['.$category->term_id.'][showsub]" value="0" id="noradio_'.$category->term_id.'" '.$noradio.' class="showoverviewposts" data-catid="'.$category->term_id.'" /><label for="noradio_'.$category->term_id.'">'; _e('No', 'nh-ynaa');
									 echo '</label>';
									echo '</div>';
								//SUb categories overview show post


									if($this->categories_settings[$category->term_id]['showoverviewposts']){
										$yesradioshowoverviewposts = 'checked';
										$noradioshowoverviewposts = '';


									}
									else{
										$yesradioshowoverviewposts = '';
										$noradioshowoverviewposts = 'checked';

									}
									echo '<div id="showoverviewposts'.$category->term_id.'" class="categorieovervie_sub '.$hidethisdiv.'">';
									_e('Show posts under subcategories overview', 'nh-ynaa');
									 echo '<br><input type="radio" name="'.$this->categories_settings_key.'['.$category->term_id.'][showoverviewposts]" value="1" id="yesshowoverviewpostsradio_'.$category->term_id.'" '.$yesradioshowoverviewposts.' /><label for="yesradio_'.$category->term_id.'">'; _e('Yes', 'nh-ynaa');
									 echo '</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="'.$this->categories_settings_key.'['.$category->term_id.'][showoverviewposts]" value="0" id="noshowoverviewpostsradio_'.$category->term_id.'" '.$noradioshowoverviewposts.' /><label for="noradio_'.$category->term_id.'">'; _e('No', 'nh-ynaa');
									 echo '</label>';
									 echo '</div>';


								}

							 ?>
                             	</div>
                        	</div>
                        </div>

                    </li>
            <?php
				}
				echo '</ul></div><div class="clear"></div>';

            }
			else {
				_e('No categories.', 'nh-ynaa');
			}
			if($this->general_settings['eventplugin'] || $this->general_settings['location']){
			echo '<h3>App Extras</h3>';
			echo '<div id="extras-div-con"  class="categorie-div-con"><ul>';
			//Events
			if($this->general_settings['eventplugin']){
					$category->term_id = -1;

					if(!$this->categories_settings[$category->term_id]['cat_name']) $this->categories_settings[$category->term_id]['cat_name']= __('Events','nh-ynaa');
				?>
					<li>

						<div class="image-div" id="<?php echo 'image-div'.$category->term_id;;  ?>" style="background-image:url('<?php echo $this->categories_settings[$category->term_id]['img'] ?>')" data-link="-1" >
                        	<div class="ttitle"><?php echo ($this->categories_settings[$category->term_id]['cat_name']); ?></div>
                        </div>
                        <div><a id="upload_image_button<?php echo $category->term_id; ?>" class="upload_image_button" href="#" name="<?php echo $this->categories_settings_key; ?>_items_<?php echo $category->term_id; ?>_img" data-image="<?php echo '#image-div'.$category->term_id;  ?>"   ><?php _e('Set default image for events','nh-ynaa'); ?></a>
           											<input type="hidden" value="<?php echo $this->categories_settings[$category->term_id]['img'] ?>" id="<?php echo $this->categories_settings_key; ?>_items_<?php echo $category->term_id; ?>_img" name="<?php echo $this->categories_settings_key; ?>[<?php echo $category->term_id; ?>][img]" data-id="image-div<?php echo $category->term_id; ?>" data-link="<?php echo $category->term_id;  ?>" /></div>
                        <?php
						echo '<div id="reset-cat-img-link-cont_'.$category->term_id.'">';
							if($this->categories_settings[$category->term_id]['img']) echo '<a href="'.$category->term_id.'" class="reset-cat-img-link">'.(__('Reset image', 'nh-ynaa')).'</a>'; else echo '<br>';
						echo '</div>'; ?>
                        <div><input type="text" class="cat-name-input" value="<?php echo $this->categories_settings[$category->term_id]['cat_name']; ?>" name="<?php echo $this->categories_settings_key; ?>[<?php echo $category->term_id; ?>][cat_name]"></div>

					</li>
               <?php
			}
			//Map
			if($this->general_settings['location']){
					$category->term_id = -98;

					if(!$this->categories_settings[$category->term_id]['cat_name']) $this->categories_settings[$category->term_id]['cat_name']= __('Locations','nh-ynaa');
				?>
					<li>

						<div class="image-div" id="<?php echo 'image-div'.$category->term_id;;  ?>" style="background-image:url('<?php echo $this->categories_settings[$category->term_id]['img'] ?>')" data-link="-1" >
                        	<div class="ttitle"><?php echo ($this->categories_settings[$category->term_id]['cat_name']); ?></div>
                        </div>
                        <div><a id="upload_image_button<?php echo $category->term_id; ?>" class="upload_image_button" href="#" name="<?php echo $this->categories_settings_key; ?>_items_<?php echo $category->term_id; ?>_img" data-image="<?php echo '#image-div'.$category->term_id;  ?>"   ><?php _e('Set default image for location','nh-ynaa'); ?></a>
           											<input type="hidden" value="<?php echo $this->categories_settings[$category->term_id]['img'] ?>" id="<?php echo $this->categories_settings_key; ?>_items_<?php echo $category->term_id; ?>_img" name="<?php echo $this->categories_settings_key; ?>[<?php echo $category->term_id; ?>][img]" data-id="image-div<?php echo $category->term_id; ?>" data-link="<?php echo $category->term_id;  ?>" /></div>
                        <?php
						echo '<div id="reset-cat-img-link-cont_'.$category->term_id.'">';
							if($this->categories_settings[$category->term_id]['img']) echo '<a href="'.$category->term_id.'" class="reset-cat-img-link">'.(__('Reset image', 'nh-ynaa')).'</a>'; else echo '<br>';
						echo '</div>'; ?>
                        <div><input type="text" class="cat-name-input" value="<?php echo $this->categories_settings[$category->term_id]['cat_name']; ?>" name="<?php echo $this->categories_settings_key; ?>[<?php echo $category->term_id; ?>][cat_name]"></div>

					</li>
               <?php
			}
			echo '</ul></div>';

			echo '<div class="clear"></div>';
			}
			//var_dump($this->general_settings);
		}
		function section_menu_desc() {
			 _e('Set the app menu.','nh-ynaa');
		}



		/*
		 * General Option field callback logo
		 */
		function nh_ynaa_field_general_option_logo($field) {
			?>
			<input type="file" name="<?php echo $field['field']; ?>"   />
			<?php if($this->general_settings['logo']) echo '<img src="'.(esc_attr( $this->general_settings['logo'])).'" align="middle" width="'.((int) ($this->logo_image_width/2)).'" height="'.((int) ($this->logo_image_height/2)).'" />'; ?>

			<?php
		} // END function nh_ynaa_field_general_option_logo

		/*
		 * General Option field callback color
		 */
		function nh_ynaa_field_general_option_color($field) {
			?>
			<input type="text" name="<?php echo $this->general_settings_key; ?>[<?php echo $field['field']; ?>]" value="<?php echo esc_attr( $this->general_settings[$field['field']] ); ?>" class="my-color-field" /><?php
            switch($field['field']){
				case 'c1': echo '<div class="helptext">'.(__('Color of main elements, e.g. dog ears, menu button, time stamps, category names, empty tiles, bullet points, social icons, etc.','nh-ynaa')).'</div>'; break;
				case 'c2': echo '<div class="helptext">'.(__('Color of further elements, e.g. homescreen and posts background, elements of the commentary section','nh-ynaa')).'</div>'; break;
				case 'cn': echo '<div class="helptext">'.(__('Color of title bar, navigation bar, quicknavigation bar','nh-ynaa')).'</div>'; break;
				case 'cm': echo '<div class="helptext">'.(__('Text color in main menu and navigation menus (browse, subscribe, etc.)','nh-ynaa')).'</div>'; break;
				case 'ch': echo '<div class="helptext">'.(__('Text color of post headlines','nh-ynaa')).'</div>'; break;
				case 'csh': echo '<div class="helptext">'.(__('Text color of post sub headlines','nh-ynaa')).'</div>'; break;
				case 'ct': echo '<div class="helptext">'.(__('Text color of continuous text','nh-ynaa')).'</div>'; break;
				default: break;
			}
			?>
			<?php
		}

		/*
		 * General Option field callback color
		 */
		function nh_ynaa_field_general_option_input($field) {
			?>
			<input type="number" name="<?php echo $this->general_settings_key; ?>[<?php echo $field['field']; ?>]" value="<?php if(isset($this->general_settings[$field['field']]))echo esc_attr( $this->general_settings[$field['field']] ); else echo 100; ?>" class="my-input-field nh-floatleft" /><?php
			switch($field['field']){
				case 'min-img-size-for-resize': echo '<div class="helptext">'.(__('Images wider than this will be scaled up to full display width','nh-ynaa')).'</div>'; break;

				default: break;
			}

			?>
			<?php
		}



		/*
		 * General Option field callback CSS
		 */
		function nh_ynaa_field_general_option_css($field) {

			?>
			<textarea id="css_textarea" name="<?php echo $this->css_settings_key; ?>[<?php echo $field['field']; ?>]" class="nh-floatleft"><?php echo esc_attr( $this->css_settings[$field['field']] ); ?></textarea>
			<?php
			//echo '<div class="helptext">'.(__('Define here your CSS style for the content in the app.','nh-ynaa')).'</div>';
		} //END function nh_ynaa_field_general_option_css


		/*
		 * General Option field hidden
		 */
		function nh_ynaa_field_general_option_hidden($field) {
			?>
			<input type="hidden" name="<?php echo $this->general_settings_key; ?>[<?php echo $field['field']; ?>]" value="<?php echo time(); ?>" />
			<?php
		} // END function nh_ynaa_field_general_option_hidden


		/*
		 * css Option field hidden
		 */
		function nh_ynaa_field_css_hidden($field) {
			?>
			<input type="hidden" name="<?php echo $this->css_settings_key; ?>[<?php echo $field['field']; ?>]" value="<?php echo time(); ?>" />
			<?php
		} // END function nh_ynaa_field_general_option_hidden

		/**
		 * css Option field hidden
		 */
		function nh_ynaa_field_push_hidden($field) {
			?>
			<input type="hidden" name="<?php echo $this->push_settings_key; ?>[<?php echo $field['field']; ?>]" value="<?php echo time(); ?>" />
			<?php
		} // END function nh_ynaa_field_general_option_hidden

		/*
		 * General Option field social callback
		 */
		function nh_ynaa_field_general_social($field) {
			?>
			<input type="text" name="<?php echo $this->general_settings_key; ?>[<?php echo $field['field']; ?>]" value="<?php echo $this->general_settings[$field['field']]; ?>" class="nh-floatleft" />
			<?php
			switch($field['field']){
				case 'gaTrackID': echo '<div class="helptext">'.(__('Enter your Google Analytics Mobile App tracking ID here. You get it in your Google Analytics account.','nh-ynaa')).'</div>'; break;
				default: break;
			}
		} // END function nh_ynaa_field_general_social

		/*
		 * General Option field hidden
		 */
		function nh_ynaa_field_menu_option_hidden($field) {
			?>
			<input type="hidden" name="<?php echo $this->menu_settings_key; ?>[<?php echo $field['field']; ?>]" value="<?php echo time(); ?>" />
			<?php
		} // END function nh_ynaa_field_menu_option_hidden


		/*
		 * push  Option field callback
		 */
		function nh_ynaa_field_push_option($field) {
			?>
			<input type="text" name="<?php echo $this->push_settings_key; ?>[<?php echo $field['field']; ?>]" value="<?php echo esc_attr( $this->push_settings[$field['field']] ); ?>" class="extraweit" />
			<?php
		} //END function nh_ynaa_field_push_option

		function nh_ynaa_field_push_checkbox($field) {
			if(esc_attr( $this->push_settings[$field['field']])=='1') $check = ' checked="checked" ';
			else $check = '';
			?>
			<input type="checkbox" name="<?php echo $this->push_settings_key; ?>[<?php echo $field['field']; ?>]" id="<?php echo 'id_'.$field; ?>" <?php echo $check; ?> value="1"  class="my-input-field nh-floatleft" />
			<?php
			switch($field['field']){
        case 'autopush': echo '<div class="helptext">'.(__('Automatic sending of push notifications in the first publication of a post.','nh-ynaa')).'</div>'; break;
        default: break;
      }
		} //END function nh_ynaa_field_push_checkbox

		/*
		 * push  Option field callback testarea
		 */
		function nh_ynaa_field_push_option_textarea($field) {
			?>
            <textarea name="<?php echo $this->push_settings_key; ?>[<?php echo $field['field']; ?>]" class="extraweit"><?php echo esc_attr( $this->push_settings[$field['field']] ); ?></textarea>

			<?php
		} //END function nh_ynaa_field_push_option

		/*
		 * push  Option ibeacon callback
		 */
		function nh_ynaa_field_ibeacon_content_option($field) {
				//var_dump($field, $this->push_settings[$field['field']]);
			?>
            <fieldset><legend>iBeacon 1</legend>
				<label>Major</label><input type="text" name="<?php echo $this->push_settings_key; ?>[<?php echo $field['field']; ?>][0][major]" value="<?php if(isset($this->push_settings[$field['field']][0]['major'])) echo esc_attr( $this->push_settings[$field['field']][0]['major'] ); ?>" class="extraweit" /><br>
				<label>Major2</label><input type="text" name="<?php echo $this->push_settings_key; ?>[<?php echo $field['field']; ?>][1][major]" value="<?php echo esc_attr( $this->push_settings[$field['field']][1]['major'] ); ?>" class="extraweit" />

            </fieldset>
			<?php
		} //END function nh_ynaa_field_push_option

		/*
		 * QR-Code Tab content
		*/
		function nh_the_qrcode_tab_content(){
		//	echo '<h3>'.__('QR-Code for Download Your App','nh-ynaa').'</h3>';
		//	echo '<p>'.__('To use this QR-Code for your News App. You have to install the yournewsapp from Appstore from Nebelhorn Medien.','nh-ynaa').'</p>';
		//	echo '<img src="https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=ynb://?url='.get_site_url().'&choe=UTF-8" alt="yna://?url='.get_site_url().'" />';
			//echo '<div>yna://?url='.get_site_url().'</div>';
		} //END function nh_the_qrcode_tab_content

		/*
    * Home  content
    */
                function nh_the_home_content(){

					echo '<div class="headercont clearfix">';
          echo '<div class="blappsta-plugin-header">';
					echo '<p>'.__('With this plugin you can deploy your own native iOS (iPhone) and Android app showing the content of your WordPress installation.<br />To get a preview on what the app would look like, please follow these steps:', 'nh-ynaa').'</p>';
					echo '<ul class="howtolist">';
            echo '<li>'.__('First of all download and install our <b>Blappsta Preview App</b> from the <a href="https://itunes.apple.com/de/app/blappsta-preview/id912390326?mt=8" target="_blank"  style="text-decoration:none;">Apple App Store</a> or from <a href="https://play.google.com/store/apps/details?id=com.blappsta.blappstaappcheck" target="_blank"   style="text-decoration:none;">Google Play&trade; Store</a>','nh-ynaa').'.</li>';
            echo '<li>'.__('Start the <b>Blappsta Preview App</b> and enter your blog’s URL or simply scan the QR-code below with our integrated scanner.', 'nh-ynaa');
            //echo '<li>'.__('Scan the QR code with this app or enter your blog URL in the app.','nh-ynaa').'';

            echo '</li>';

             echo '<li>'.__('Of course all of the settings can be changed at any time. A simple „pull to refresh“ suffices in order to take over the settings in the app.','nh-ynaa');
           echo '</ul>';
          echo '<div>';
          echo '<p>'.__('If you like the app, please register on our website <a href="http://www.blappsta.com/sign-up" target="_blank"  style="text-decoration:none;">www.blappsta.com</a>.', 'nh-ynaa').'</p>';
          echo '<p>'.__('If you have any questions contact us: <a href="mailto:support@blappsta.com"  style="text-decoration:none;">support@blappsta.com</a>', 'nh-ynaa').'</p>';
          echo '</div>';
					echo '</div>';
					echo '<div style="display:inline-block; width:315px; ">';
          echo '<h4 style="margin:0; padding-top:10px;">1. '.__('Download Blappsta Preview:','nh-ynaa').'</h4>';
          echo '<a href="https://itunes.apple.com/us/app/blappsta-preview/id912390326?mt=8&uo=4" target="itunes_store" style="display:inline-block;overflow:hidden;background:url(https://linkmaker.itunes.apple.com/htmlResources/assets/en_us//images/web/linkmaker/badge_appstore-lrg.png) no-repeat;width:135px;height:40px; padding-top:1px; margin-right:10px;@media only screen{background-image:url(https://linkmaker.itunes.apple.com/htmlResources/assets/en_us//images/web/linkmaker/badge_appstore-lrg.svg);}"></a>';
          //echo '<br />';
          echo '<a href="https://play.google.com/store/apps/details?id=com.blappsta.blappstaappcheck" data-hover=""><img src="https://developer.android.com/images/brand/en_app_rgb_wo_45.png" alt="Android app on Google Play"  ></a>';

          echo '<div>';
          echo '<h4  style="margin:0; padding-top:10px;">2. '.__('Blappsta Preview QR-Code','nh-ynaa').'</h4>';
          echo '<p style="margin:0;">'.__('Scan this QR-Code with our Blappsta Preview App to see your app in action.', 'nh-ynaa').'</p>';
          echo '<div>';
          echo '<a href="https://chart.googleapis.com/chart?chs=125x125&cht=qr&chl=yba://?url='.get_site_url().'&choe=UTF-8"><img width="125" src="https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl='.get_site_url().'&choe=UTF-8" alt="'.get_site_url().'" title="'.get_site_url().'" /></a>';
          echo '</div>';
          echo '</div>';

          echo '</div>';
          echo '</div>';
          echo '<div class="clear"></div>';

					if(substr(get_bloginfo('language'),0,2)=='de'){
					?>
                    <div>
                    	<script type="text/javascript" src="//assets.zendesk.com/external/zenbox/v2.6/zenbox.js"></script>
<style type="text/css" media="screen, projection">
  @import url(//assets.zendesk.com/external/zenbox/v2.6/zenbox.css);
</style>
<script type="text/javascript">
  if (typeof(Zenbox) !== "undefined") {
    Zenbox.init({
      dropboxID:   "20262591",
      url:         "https://blappsta.zendesk.com",
      tabTooltip:  "Support",
      tabImageURL: "https://p3.zdassets.com/external/zenbox/images/tab_de_support_right.png",
      tabColor:    "#ff8000",
      tabPosition: "Right"
    });
  }
</script>
</div>
					<?php
					}
					else {
					?>
                    <div>
                    	<script type="text/javascript" src="//assets.zendesk.com/external/zenbox/v2.6/zenbox.js"></script>
<style type="text/css" media="screen, projection">
  @import url(//assets.zendesk.com/external/zenbox/v2.6/zenbox.css);
</style>
<script type="text/javascript">
  if (typeof(Zenbox) !== "undefined") {
    Zenbox.init({
      dropboxID:   "20262561",
      url:         "https://blappsta.zendesk.com",
      tabTooltip:  "Support",
      tabImageURL: "https://p3.zdassets.com/external/zenbox/images/tab_support_right.png",
      tabColor:    "#ff8000",
      tabPosition: "Right"
    });
  }
</script>
                    </div>
                    <?php
					}
				/*	echo '<div class="headercont clearfix">';
                    echo '<p>'.__('With this plugin you can deploy your own native iOS (iPhone) and Android app containing the content of this Wordpress installation.','nh-ynaa').'<br>';
					echo __('To get a preview on what the app would look like, please follow these steps:','nh-ynaa').'</p>';
					echo '<ul class="howtolist">';
						echo '<li>'.__('First of all download and install the <a href="https://itunes.apple.com/de/app/yourblogapp-yournewsapp/id815084293?mt=8" target="_blank">yourBlogApp test app</a> from the Apple AppStore','nh-ynaa').'</li>';
						echo '<li>'.__('Scan the QR code and open the link on your smartphone. Other than scanning the QR you can type the following link into your smartphone’s browser: ','nh-ynaa').'yba://?url='.get_site_url().'';

						echo '</li>';

                    	echo '<li>'.__('By opening this link the test app will be reconfigured and filled with the content from your Wordpress site. Change the look and feel of your app by modifying the settings, adding your logo, customizing the startscreen and changing the overall style.','nh-ynaa').'</li>';
						echo '<li>'.__('If you like the app, please register on our website <a href="http://www.your-news-app.com" target="_blank">www.your-news-app.com</a>. We will then create the app for you and upload it to the app stores!','nh-ynaa').'</li>';
						echo '<li>'.__('If you have any questions contact us: ','nh-ynaa').'<a href="mailto:support@yournewsapp.de">support@yournewsapp.de</a>'.'</li>';
					echo '</ul>';
					echo '<div>';
					echo '<a href="https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=yba://?url='.get_site_url().'&choe=UTF-8"><img width="100px" src="https://chart.googleapis.com/chart?chs=100x100&cht=qr&chl=yba://?url='.get_site_url().'&choe=UTF-8" alt="yba://?url='.get_site_url().'" /></a> <br>';
					echo '</div>';
					echo '</div>';
					echo '<div class="clear"></div>';
					echo '<p>'.__('In the following tabs you can modify the appearance and functions of the app. With our <a href="https://itunes.apple.com/de/app/yourblogapp-yournewsapp/id815084293?mt=8" target="_blank">yourBlogApp test app</a> you can see what your app would look like.', 'nh-ynaa');
					*/
                } //END function nh_the_home_content


		/*
		 * Start screen View
		*/
		/*function nh_ynaa_field_general_homescreentype($field){
			?>
			<select id="nh_homescreentype" name="<?php echo $this->general_settings_key; ?>[<?php echo $field['field']; ?>]" class="nh-floatleft">
                    	<option value="0"><?php _e('Categories', 'nh-ynaa'); ?></option>
                        <option value="1" <?php if($this->general_settings[$field['field']]=='1') echo ' selected'; ?>><?php _e('Articles', 'nh-ynaa'); ?></option>
                        <option value="2" <?php if($this->general_settings[$field['field']]=='2') echo ' selected'; ?>><?php _e('Pages', 'nh-ynaa'); ?></option>
                    </select>
           <?php
		    echo '<div class="helptext padding5">'.(__('Select your startscreen view for your app.','nh-ynaa')).'</div>';
		}*/

		/*
		 * LAngugae
		*/
		function nh_ynaa_field_general_language($field){


			?>
			<select  id="nh_language" name="<?php echo $this->general_settings_key; ?>[<?php echo $field['field']; ?>]" class="nh-floatleft">
                    	<option value="en"><?php _e('English', 'nh-ynaa'); ?></option>
                        <option value="fr" <?php if($this->general_settings[$field['field']]=='fr') echo ' selected'; ?>><?php _e('French', 'nh-ynaa'); ?></option>
                        <option value="nl" <?php if($this->general_settings[$field['field']]=='nl') echo ' selected'; ?>><?php _e('Dutch', 'nh-ynaa'); ?></option>
                        <option value="de" <?php if($this->general_settings[$field['field']]=='de') echo ' selected'; ?>><?php _e('German', 'nh-ynaa'); ?></option>
                        <option value="it" <?php if($this->general_settings[$field['field']]=='it') echo ' selected'; ?>><?php _e('Italian', 'nh-ynaa'); ?></option>
                        <option value="pt" <?php if($this->general_settings[$field['field']]=='pt') echo ' selected'; ?>><?php _e('Portuguese', 'nh-ynaa'); ?></option>
                        <option value="ru" <?php if($this->general_settings[$field['field']]=='ru') echo ' selected'; ?>><?php _e('Russian', 'nh-ynaa'); ?></option>
                        <option value="es" <?php if($this->general_settings[$field['field']]=='es') echo ' selected'; ?>><?php _e('Spanish', 'nh-ynaa'); ?></option>
                    </select>
           <?php
		   echo '<div class="helptext padding5">'.(__('Interface and dialogue language','nh-ynaa')).'</div>';
		}

		/**
		* articles ort by
		*/
		function nh_ynaa_field_general_sorttype($field){
			//var_dump($this->general_settings[$field['field']]);
			?>
            <select   <?php if(!$this->general_settings['homescreentype'] || !isset($this->general_settings['homescreentype'])) echo 'disabled'; ?>  id="nh_sorttype" name="<?php echo $this->general_settings_key; ?>[<?php echo $field['field']; ?>]" class="nh-floatleft">
                    	<option value="date-desc" <?php if($this->general_settings[$field['field']]=='date-desc') echo ' selected'; ?>><?php _e('Recent posts', 'nh-ynaa'); ?></option>
                        <option value="date-asc" <?php if($this->general_settings[$field['field']]=='date-asc') echo ' selected'; ?>><?php _e('Oldest posts', 'nh-ynaa'); ?></option>
                        <option value="alpha-asc" <?php if($this->general_settings[$field['field']]=='alpha-asc') echo ' selected'; ?>><?php _e('Alphabetically', 'nh-ynaa'); ?></option>
                        <!--<option value="popular" <?php if($this->general_settings[$field['field']]=='popular') echo ' selected'; ?>><?php _e('Most popular posts', 'nh-ynaa'); ?></option> -->
                    </select>
            <?php
			 echo '<div class="helptext padding5">'.(__('Post order on starscreen.','nh-ynaa')).'</div>';
		}

		/*
		 * Event  Option field callback
		 */
		function nh_ynaa_field_general_eventplugin($field) {
			$events_plugins_names = array(1=>'Events Manager');
			$events_plugins = array(1=>'events-manager/events-manager.php');
			//$eventmanager = false;
			foreach($events_plugins as $k=>$events_plugin){
				if (is_plugin_active($events_plugin)) {
				  $aktiveventplugis[$k] = $events_plugin;
				 // $eventmanager = true;
				}
			}
			if($aktiveventplugis && count($aktiveventplugis) > 0){
				?>
                	<select id="eventplugin" name="<?php echo $this->general_settings_key; ?>[<?php echo $field['field']; ?>]" class="nh-floatleft">
                    	<option value="0"><?php _e('Please select'); ?></option>
                        <?php
						foreach($aktiveventplugis as $k=>$eventplugin){
							$checked= '';
							if($this->general_settings[$field['field']]==$k) $checked= ' selected="selected" ';
							echo '<option value="'.$k.'" '.$checked.'>'.$events_plugins_names[$k].'</option>';
						}
						?>
                    </select>
                <?php
				 echo '<div class="helptext">'.(__('This is a Blappsta business feature.','nh-ynaa')).' '.(__('You can select here a event plugin to show events in your app.','nh-ynaa')).'</div>';
			}
			else {
				_e('No supported Plugin installed. Please Install the Plugin Event Manager.', 'nh-ynaa');
				echo ' <a href="http://wordpress.org/plugins/events-manager/" target="_blank">';
				_e('Plugin Directory', 'nh-ynaa');
				echo '</a>';
			}
		} // END function nh_ynaa_field_general_eventplugin

		/*
		 * Check if Support Event MAnager is installed
		 * Return boolean
		*/
		function nh_ynaa_check_eventmanager(){
			$events_plugins_names = array(1=>'Events Manager');
			$events_plugins = array(1=>'events-manager/events-manager.php');
			//$eventmanager = false;
			foreach($events_plugins as $k=>$events_plugin){
				if (is_plugin_active($events_plugin)) {
				  $aktiveventplugis[$k] = $events_plugin;
				 // $eventmanager = true;
				}
			}
			if($aktiveventplugis && count($aktiveventplugis) > 0) return true;
			else return false;
		} // END function nh_ynaa_check:eventmanager()

		/*
		 * sort  Option field callback
		 */
		function nh_ynaa_field_general_extra_sort($field) {
			if(esc_attr( $this->general_settings[$field['field']])=='1') $check = ' checked="checked" ';
			else $check = '';
			?>
			<input value="1" type="checkbox" name="<?php echo $this->general_settings_key; ?>[<?php echo $field['field']; ?>]" id="<?php echo $this->general_settings_key; ?>[<?php echo $field['field']; ?>]" <?php echo $check; ?> class="nh-floatleft" />
			<?php
			switch($field['field']){
				case 'location': echo '<div class="helptext padding0">'.(__('This is a Blappsta business feature.','nh-ynaa')).(__('Activate this checkbox if they want under your posts show a map with a location.', 'nh-ynaa')).'</div>'; break;
				case 'sort' : echo '<div class="helptext padding0">'.(__('Create separators for periods of time between posts<br>(only if post order within categories is set to "chronologically")', 'nh-ynaa')).'</div>'; break;
				case 'comments' : echo '<div class="helptext padding0">'.(__('Turn the comments section beneath posts on or off.', 'nh-ynaa')).'</div>'; break;
				case 'gadgetry': echo '<div class="helptext padding0">'.(__('Activate the checkbox if you use gadgetry theme image as post featured image.', 'nh-ynaa')).'</div>'; break;
				case 'json_embedded': echo '<div class="helptext padding0">'.(__('Activate the checkbox if you get the tip "Recent content could not be accessed. Please connect your device to the internet and try again." in the app emulator.', 'nh-ynaa')).'</div>'; break;
				case 'utf8': echo '<div class="helptext padding0">'.(__('Activate this checkbox if the content is not displayed correctly coded.', 'nh-ynaa')).'</div>'; break;
				case 'domcontent': echo '<div class="helptext padding0">'.(__('Activate this checkbox if you don\'t see any content in the detail view.', 'nh-ynaa')).'</div>'; break;
				case 'debug': echo '<div class="helptext padding0">'.(__('Activate the checkbox if you have any problems with the app, this help us to find out the error.', 'nh-ynaa')).'</div>'; break;
				case 'blank_lines': echo '<div class="helptext padding0">'.(__('Activate the checkbox if you have to many blank lines on your content page in the app.', 'nh-ynaa')).'</div>'; break;
				case 'showFeatureImageInPost': echo '<div class="helptext padding0">'.(__('Active this checkbox in order to bind in the feature image in post view.', 'nh-ynaa')).'</div>'; break;
				case 'avada-categories': echo '<div class="helptext padding0">'.(__('Treat Avada portfolio categories as normal WordPress categories', 'nh-ynaa')).'</div>'; break;
				default: break;
			}
		}



		/*
		* Order option field backup
		*/
		function nh_ynaa_field_general_extra_order($field){
			if(esc_attr( $this->general_settings[$field['field']])=='1') $check = ' checked="checked" ';
			else $check = '';

			?>
            <select id="<?php echo $this->general_settings_key; ?>[<?php echo $field['field']; ?>]" name="<?php echo $this->general_settings_key; ?>[<?php echo $field['field']; ?>]">
            	<option value="date"><?php _e('date','nh-ynaa'); ?></option>
                <option <?php if($this->general_settings[$field['field']]=='alphabetical') echo 'selected'; ?> value="alphabetical"><?php _e('alphabetical','nh-ynaa'); ?></option>
                <option <?php if($this->general_settings[$field['field']]=='random') echo 'selected'; ?> value="random"><?php _e('random','nh-ynaa'); ?></option>
            </select>
            <?php


		}



		/*
		 * Advanced Option field callback, same as above.
		 */
		function field_menu_option() {
			?>
			<input type="text" name="<?php echo $this->menu_settings_key; ?>['menu']" value="<?php echo esc_attr( $this->menu_settings['menu'] ); ?>" />

			<?php
		}

		/*
		 * Called during admin_menu, adds an options
		 * page under Settings called My Settings, rendered
		 * using the nh_ynaa_plugin_options_page method.
		 */
		function nh_ynaa_add_admin_menus() {
			global $nh_menu_hook_ynaa;
			$nh_menu_hook_ynaa = add_options_page( 'Blappsta Plugin', 'Blappsta Plugin', 'manage_options', $this->plugin_options_key, array( &$this, 'nh_ynaa_plugin_options_page' ) );
			add_action("load-{$nh_menu_hook_ynaa}",array(&$this,'nh_create_help_screen'));
		}



		/*
		* Function to create Help
		*/
		public function nh_create_help_screen() {
		$this->nh_get_blappsta_extra();
			//var_dump($_POST);
		if($_GET['settings-updated']==='true'){

			if($_GET['tab']==='nh_ynaa_css_settings'){
				$ts =  get_option( 'nh_ynaa_css_settings_ts' );
				if($this->css_settings['ts']!=$ts){
					update_option('nh_ynaa_css_settings_ts', $this->css_settings['ts']);
				}
				//var_dump($this->css_settings, $this->css_settings['css_settings']);
			}
			elseif($_GET['tab']==='nh_ynaa_teaser_settings'){
				$ts =  get_option( 'nh_ynaa_teaser_settings_ts' );
				if($this->teaser_settings['ts']!=$ts){
					update_option('nh_ynaa_teaser_settings_ts', $this->teaser_settings['ts']);
				}
			}
			elseif($_GET['tab']==='nh_ynaa_general_settings'|| !isset($_GET['tab'])){
				$ts =  get_option( 'nh_ynaa_general_settings_ts' );
				if($this->general_settings['ts']!=$ts){
					update_option('nh_ynaa_general_settings_ts', $this->general_settings['ts']);
				}
			}
			elseif($_GET['tab']==='nh_ynaa_menu_settings'){
				$ts =  get_option( 'nh_ynaa_menu_settings_ts' );
				if($this->menu_settings['ts']!=$ts){
					update_option('nh_ynaa_menu_settings_ts', $this->menu_settings['ts']);
					update_option('nh_ynaa_general_settings_ts', $this->menu_settings['ts']);
				}
			}
			elseif($_GET['tab']==='nh_ynaa_homepreset_settings'){
				$ts =  get_option( 'nh_ynaa_homepreset_settings_ts' );
				if($this->homepreset_settings['ts']!=$ts){
					update_option('nh_ynaa_homepreset_settings_ts', $this->homepreset_settings['ts']);
					update_option('nh_ynaa_general_settings_ts', $this->homepreset_settings['ts']);
				}
			}
			elseif($_GET['tab']==='nh_ynaa_categories_settings'){
				$ts =  get_option( 'nh_ynaa_categories_settings_ts' );
				if($this->categories_settings['ts']!=$ts){
					update_option('nh_ynaa_categories_settings_ts', $this->categories_settings['ts']);
				}
			}
			elseif($_GET['tab']==='nh_ynaa_push_settings'){

				$ts =  get_option( 'nh_ynaa_push_settings_ts' );

				if($this->push_settings['ts']!=$ts){

					update_option('nh_ynaa_push_settings_ts', $this->push_settings['ts']);
				}
			}


		}
 		if(!class_exists('WP_Screen')) return;
		/**
		 * Create the WP_Screen object against your admin page handle
		 * This ensures we're working with the right admin page
		 */
		$this->admin_screen = WP_Screen::get($this->admin_page);

		/**
		 * Content specified inline
		 */
		$this->admin_screen->add_help_tab(
			array(
				'title'    => __('Help'),
				'id'       => 'help_tab',
				'content'  => '<p>'.__('For help visit our website <a href="http://www.your-news-app.com/">www.your-news-app.com/</a>.').'</p>',
				'callback' => false
			)
		);

		/**
		 * Content generated by callback
		 * The callback fires when tab is rendered - args: WP_Screen object, current tab
		 */
		/*$this->admin_screen->add_help_tab(
			array(
				'title'    => 'Info on this Page',
				'id'       => 'page_info',
				'content'  => '',
				'callback' => create_function('','echo "<p>This is my generated content.</p>";')
			)
		);*/

		/*$this->admin_screen->set_help_sidebar(
			'<p>This is my help sidebar content.</p>'
		);*/

		/*$this->admin_screen->add_option(
			'per_page',
			array(
				'label' => 'Entries per page',
				'default' => 20,
				'option' => 'edit_per_page'
			)
		);

		$this->admin_screen->add_option(
			'layout_columns',
			array(
				'default' => 3,
				'max' => 5
			)
		);*/

		/**
		 * This option will NOT show up
		 */
		/*$this->admin_screen->add_option(
			'invisible_option',
			array(
				'label'	=> 'I am a custom option',
				'default' => 'wow',
				'option' => 'my_option_id'
			)
		);*/

		/**
		 * But old-style metaboxes still work for creating custom checkboxes in the option panel
		 * This is a little hack-y, but it works
		 */
		/*add_meta_box(
			'my_meta_id',
			'My Metabox',
			array(&$this,'create_my_metabox'),
			$this->admin_page
		);*/
	}


		/*
		 * Plugin Options page rendering goes here, checks
		 * for active tab and replaces key with the related
		 * settings key. Uses the nh_ynaa_plugin_options_tabs method
		 * to render the tabs.
		 */
		function nh_ynaa_plugin_options_page() {
			if(!current_user_can('manage_options'))
			{
				wp_die(__('You do not have sufficient permissions to access this page.'));
			}
			$tab = isset( $_GET[$this->requesvar['tab']] ) ? $_GET[$this->requesvar['tab']] : $this->general_settings_key;

			?>
			<div class="wrap">
				<!--<div id="icon-options-general" class="icon32"><br/></div>-->
				<h2><?php _e('Settings for Blappsta Plugin','nh-ynaa'); ?></h2>
				<?php
					$this->nh_the_home_content();
                    $this->nh_ynaa_plugin_options_tabs();
				if($tab != 'qrcode'){
				 ?>
				<form method="post" action="options.php" enctype="multipart/form-data" id="nh_ynaa_form" class="<?php echo $tab; ?>">
					<?php wp_nonce_field( 'update-options' ); ?>
					<?php settings_fields( $tab ); ?>
					<?php do_settings_sections( $tab ); ?>
					<div class="stickyBottom" id="ynaa_stickyBottom">
					
						<?php submit_button(); ?>
					
					</div>
					
					<div style="margin-bottom:25px"></div>
				</form>
                <?php if($tab == $this->general_settings_key) $this->nh_ynaa_simulator(); ?>
                <?php }
				else{
					$this->nh_the_qrcode_tab_content();
				}?>
			</div>

			<?php
		}



		/*
		 * Renders our tabs in the plugin options page,
		 * walks through the object's tabs array and prints
		 * them one by one. Provides the heading for the
		 * nh_ynaa_plugin_options_page method.
		 */
		function nh_ynaa_plugin_options_tabs() {
			$current_tab = isset( $_GET[$this->requesvar['tab']] ) ? $_GET[$this->requesvar['tab']] : $this->general_settings_key;

			screen_icon();
			echo '<h2 class="nav-tab-wrapper" id="ynaa_nav_tab">';
			foreach ( $this->plugin_settings_tabs as $tab_key => $tab_caption ) {
				$active = $current_tab == $tab_key ? 'nav-tab-active' : '';
				echo '<a class="nav-tab ' . $active . '" href="?page=' . $this->plugin_options_key . '&tab=' . $tab_key . '">' . $tab_caption . '</a>';
			}
			echo '</h2>';
		}
		



		/*
		 *Load  Scripts and Styles
		*/
		function nh_ynaa_scripts( $hook_suffix ) {

			//wp_enqueue_script( 'ynaa-script-post-edit', plugins_url('js/ynaa-post-edit.js', __FILE__ ), array( 'jquery' ), '1.0', true );
			//wp_localize_script( 'ynaa-script-post-edit', 'ajax_object',
			//		array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'we_value' => 1234 ) );

			wp_enqueue_script('jquery');
			wp_enqueue_script('jquery-ui-core');
			wp_enqueue_script('jquery-ui-tabs');
			wp_enqueue_script('jquery-ui-accordion');
			wp_enqueue_script('jquery-ui-sortable');
			if($hook_suffix =='post-new.php' || $hook_suffix =='post.php'){
				wp_register_script( "ynaa-script-post-edit", plugins_url('js/ynaa-post-edit.js', __FILE__ ), array('jquery') );
   				wp_localize_script( 'ynaa-script-post-edit', 'myAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ), 'ajaxdata'=>array($hook_suffix)));

   				wp_enqueue_script( 'jquery' );
   				wp_enqueue_script( 'ynaa-script-post-edit' );
				wp_enqueue_style( 'ynaa-style-post-edit', plugins_url('css/ynaa_style_post_edit.css', __FILE__ ) , array(),'1.0');
			}
   			global $nh_menu_hook_ynaa;

			// exit function if not on my own options page!
			// $my_menu_hook_akt is generated when creating the options page, e.g.,
			// $my_menu_hook_akt = add_menu_page(...), add_submenu_page(...), etc


			if ($hook_suffix != $nh_menu_hook_ynaa) return;
			wp_enqueue_style( 'ynaa-style', plugins_url('css/ynaa_style.css', __FILE__ ) , array(),'1.0');
			// first check that $hook_suffix is appropriate for your admin page
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script('media-upload');
			wp_enqueue_script('thickbox');

			wp_enqueue_script( 'ynaa-script-handle', plugins_url('js/ynaa.js', __FILE__ ), array( 'jquery', 'jquery-ui-core', 'jquery-ui-tabs', 'jquery-ui-accordion', 'jquery-ui-sortable', 'jquery-ui-draggable','wp-color-picker', 'media-upload','thickbox' ), '1.0', true );


			wp_enqueue_style('thickbox');


			$data = array('general_settings_key'=>$this->general_settings_key, 'menu_settings_key'=>$this->menu_settings_key, 'teaser_settings_key' => $this->teaser_settings_key, 'homepreset_settings_key'=>$this->homepreset_settings_key, 'delete2'=>__('Delete'), 'catText'=>__('Set default image for category','nh-ynaa') , 'allowremoveText' => __('Allow hide on Startscreen','nh-ynaa'), 'color01'=>$this->general_settings['c1'] , 'ajax_url' => admin_url( 'admin-ajax.php' ) );
			wp_localize_script('ynaa-script-handle', 'php_data', $data);
			//wp_localize_script( 'ynaa-script-handle', 'ajax_object',  array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
			if( 'index.php' != $hook_suffix ) return;	// Only applies to dashboard panel

			wp_enqueue_script( 'ynaa_push-script', plugins_url( 'js/ynaa_push.js', __FILE__ ), array('jquery'));

			// in javascript, object properties are accessed as ajax_object.ajax_url, ajax_object.we_value
			wp_localize_script( 'ynaa_push-script', 'ajax_object',
					array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'we_value' => 1234 ) );

			/**/


		}


		/**
		 * Output Json
		 */
		public function nh_ynaa_template_redirect() {

			$ynaa_var = get_query_var('ynaa');
			//remove action
			remove_action('init', 'dsq_request_handler');
			remove_action('dsq_sync_forum', 'dsq_sync_forum');
			remove_action('parse_request', 'dsq_parse_query');
			remove_action('the_posts', 'dsq_maybe_add_post_ids');
			remove_action('loop_end', 'dsq_loop_end');
			remove_action('wp_footer', 'dsq_output_footer_comment_js');
			remove_action('pre_comment_on_post', 'dsq_pre_comment_on_post');
			remove_filter('plugin_action_links', 'dsq_plugin_action_links', 10);

			$header = 'Content-Type: application/json; charset=utf-8';
			$start = '';
			$end = '';
			if($this->general_settings['json_embedded']) {
				$header = 'Content-Type: text/plain; charset=UTF-8';
				$start = '[#NH_BLAPPSTA_START#]';
				$end = '[#NH_BLAPPSTA_END#]';
			}
			if($ynaa_var=='settings' || $ynaa_var==$_GET['nh_prefix'].'_settings'){
				header($header);
				echo $start;
				echo(json_encode($this->nh_ynaa_settings()));
				echo $end;

			}
			elseif($ynaa_var=='homepresets' || $ynaa_var==$_GET['nh_prefix'].'_homepresets'){
				header($header);
				echo $start;
				echo(json_encode($this->nh_ynaa_homepresets()));
				echo $end;
			}
			elseif($ynaa_var=='teaser' || $ynaa_var==$_GET['nh_prefix'].'_teaser'){
				header($header);
				echo $start;
				echo(json_encode($this->nh_ynaa_teaser()));
				echo $end;
			}
			elseif($ynaa_var=='categories' || $ynaa_var==$_GET['nh_prefix'].'_categories'){
				header($header);
				echo $start;
				echo(json_encode($this->nh_ynaa_categories()));
				echo $end;
			}
			elseif($ynaa_var=='articles' || $ynaa_var==$_GET['nh_prefix'].'_articles'){
				header($header);
				echo $start;
				echo(json_encode($this->nh_ynaa_articles()));
				echo $end;
			}
			elseif($ynaa_var=='article' || $ynaa_var==$_GET['nh_prefix'].'_article'){
				header($header);
				echo $start;
				//add_action('wp_head', array($this,'nh_buffer_start'));
				//add_action('wp_footer', array($this,'nh_buffer_end'));
				//ob_start(array($this,"nh_op_callback"));

				//remove_action( 'wp', 'wp_donottrack_ob_setup' );
				echo(json_encode($this->nh_ynaa_article()));
				echo $end;
				//$out1 = ob_get_contents();

				//ob_end_clean();
				//ob_end_flush();
				//echo substr($out1,strpos($out1,'<body>')+6,100);
			}
			elseif($ynaa_var=='events' || $ynaa_var==$_GET['nh_prefix'].'_events'){
				header($header);
				echo $start;
				echo(json_encode($this->nh_ynaa_events()));
				echo $end;
			}
			elseif($ynaa_var=='event' || $ynaa_var==$_GET['nh_prefix'].'_event'){
				header($header);
				echo $start;
				echo(json_encode($this->nh_ynaa_event()));
				echo $end;
			}
			elseif($ynaa_var=='social' || $ynaa_var==$_GET['nh_prefix'].'_social'){
				header($header);
				echo $start;
				echo(json_encode($this->nh_ynaa_social()));
				echo $end;
			}
			elseif($ynaa_var=='comments' || $ynaa_var==$_GET['nh_prefix'].'_comments'){
				header($header);
				echo $start;
				echo(json_encode($this->nh_ynaa_comments()));
				echo $end;
			}
			elseif($ynaa_var=='ibeacon' || $ynaa_var==$_GET['nh_prefix'].'_ibeacon'){
				header($header);
				echo $start;
				echo(json_encode($this->nh_ynaa_ibeacon()));
				echo $end;
			}
			elseif($ynaa_var=='locations' || $ynaa_var==$_GET['nh_prefix'].'_locations'){
				header($header);
				echo $start;
				echo(json_encode($this->nh_ynaa_locations()));
				echo $end;
			}
			elseif($ynaa_var=='content' || $ynaa_var==$_GET['nh_prefix'].'_content'){
				header('Content-Type: text/html;charset=UTF-8');
				echo ($this->nh_ynaa_content());

			}
			elseif($ynaa_var=='yna_settings' || $ynaa_var==$_GET['nh_prefix'].'_yna_settings' ){
				header($header);
				echo $start;
				echo(json_encode($this->nh_ynaa_yna_settings()));
				echo $end;
			}
			elseif($ynaa_var){
				header($header);
				echo $start;
				echo(json_encode(array('error'=>$this->nh_ynaa_errorcode(11))));
				echo $end;
			}
			else {
				header($header);
				echo $start;
				echo(json_encode(array('error'=>$this->nh_ynaa_errorcode())));
				echo $end;
			}
			exit();
		} // END public function nh_ynaa_template_redirect()

		/**
		 * Return Error Array
		 */
		private function nh_ynaa_errorcode($er=10){
			$errorarray = array();
			$errorarray['url']="http://".$_SERVER['HTTP_HOST'].'/?'.$_SERVER['QUERY_STRING'];
     // global $wpdb;
     // $wpdb->insert('temp',array( 'text'=>serialize(getallheaders())));

			//$errorarray['header'] = getallheaders();
			switch($er){
				case 0: $errorarray['error_code']= 0; $errorarray['error_message']='No Error'; break;
				case 11: $errorarray['error_code']= 11; $errorarray['error_message']='Unknown controller'; break;
				case 12: $errorarray['error_code']= 12; $errorarray['error_message']='No settings saved'; break;
				case 13: $errorarray['error_code']= 13; $errorarray['error_message']='Setting is empty'; break;
				case 14: $errorarray['error_code']= 14; $errorarray['error_message']='Menu is empty'; break;
				case 15: $errorarray['error_code']= 15; $errorarray['error_message']='No ID'; break;
				case 16: $errorarray['error_code']= 16; $errorarray['error_message']='No items for this category'; break;
				case 17: $errorarray['error_code']= 17; $errorarray['error_message']='No item whith this ID'; break;
				case 18: $errorarray['error_code']= 18; $errorarray['error_message']='No teaser set'; break;
				case 19: $errorarray['error_code']= 19; $errorarray['error_message']='No app items for this category'; break;
				case 20: $errorarray['error_code']= 20; $errorarray['error_message']='No categories'; break;
				case 21: $errorarray['error_code']= 21; $errorarray['error_message']='No Items in Categories'; break;
				case 22: $errorarray['error_code']= 22; $errorarray['error_message']='No events'; break;
				case 23: $errorarray['error_code']= 23; $errorarray['error_message']='No homepreset'; break;
				case 24: $errorarray['error_code']= 24; $errorarray['error_message']='Unknown social network'; break;
				case 25: $errorarray['error_code']= 25; $errorarray['error_message']='Facebook IDs required'; break;
				case 26: $errorarray['error_code']= 26; $errorarray['error_message']='No Facebook SDK'; break;
				case 27: $errorarray['error_code']= 27; $errorarray['error_message']='Facebook Error'; break;
				case 28: $errorarray['error_code']= 28; $errorarray['error_message']='Facebook query empty'; break;
				case 29: $errorarray['error_code']= 29; $errorarray['error_message']='Comments closed'; break;
				case 30: $errorarray['error_code']= 30; $errorarray['error_message']='Missed required value'; break;
				case 31: $errorarray['error_code']= 31; $errorarray['error_message']='email invalid'; break;
				case 32: $errorarray['error_code']= 32; $errorarray['error_message']='key already exists'; break;
				case 33: $errorarray['error_code']= 33; $errorarray['error_message']='No UUID'; break;
				case 34: $errorarray['error_code']= 34; $errorarray['error_message']='No location activ'; break;
				case 35: $errorarray['error_code']= 35; $errorarray['error_message']='This category ist now inactive for the app'; break;
				case 36: $errorarray['error_code']= 36; $errorarray['error_message']='No more itemes'; break;
				case 36: $errorarray['error_code']= 37; $errorarray['error_message']='Unknown teaser typ'; break;
				default: $errorarray['error_code']= 10; $errorarray['error_message']='Unknown Error'; break;
			}
			return ($errorarray);
		} // END private function errorcode()

		/**
		 * Return Setting Array
		 */
		private function nh_ynaa_settings(){

			//$returnarray['error']=$this->errorcode(0);



			/*
			* Debug Modus
			*/
			if($this->general_settings['debug'] && $this->general_settings['debug'] && $this->general_settings['debug'] ==1 && $_GET['debug']==1){
				global $wpdb;
				$returnarray['debug']['active']=1;
				$returnarray['debug']['homescreentype']=$this->general_settings['homescreentype'];
				$returnarray['debug']['sorttype']=$this->general_settings['sorttype'];
				$upload_dir = wp_upload_dir();
				$returnarray['upload_dir[baseurl]'] = $upload_dir['baseurl'];

			}

			if(!get_option($this->general_settings_key))   {
				//echo 'Keine settings';
				$returnarray['error']=$this->nh_ynaa_errorcode(13);
			}
			elseif(!get_option($this->menu_settings_key))   {
				//echo 'Keine Menu';
				$returnarray['error']=$this->nh_ynaa_errorcode(14);
			}
			else {
				if($_GET[$this->requesvar['ts']]) $ts= $_GET[$this->requesvar['ts']];
				else $ts = 0;


				$returnarray['error']=$this->nh_ynaa_errorcode(0);
				$returnarray['url']=get_bloginfo('url');
				global $nh_ynaa_version;
				$returnarray['plugin_version']=$nh_ynaa_version;
				$returnarray['wpversion']=get_bloginfo('version');
				$returnarray['wpcharset']=get_bloginfo('charset');
				$returnarray['db_charset']=DB_CHARSET;
				$returnarray['wphtml_type']=get_bloginfo('html_type');

				$ts_general =  get_option( 'nh_ynaa_general_settings_ts' );

				//FAls ts nich definiert

				//if(!$ts_general) $ts = -1;
				if($ts<$ts_general){
					/* IBeacon */
					if($this->push_settings['uuid']){
						/*
						$returnarray['ibeacon']['uuid']=$this->push_settings['uuid'];
						if($this->push_settings['welcome']) $returnarray['ibeacon']['welcome']=$this->push_settings['welcome'];
						if($this->push_settings['silent']) $returnarray['ibeacon']['silent']=$this->push_settings['silent'];
					    */
						$ib = $this->nh_ynaa_ibeacon();
						$returnarray['ibeacon'] = $ib['ibeacon'];
					}

					//if($this->general_settings['ts']>$this->menu_settings['ts'])$ts=$this->general_settings['ts'];
					//else $ts=$this->menu_settings['ts'];
					$ts = $ts_general;
					if($this->general_settings['sort']) {
						$returnarray['sort']=1;
						//$returnarray['order']=1;
					}
					else {
						$returnarray['sort']=0;
					}
          if(isset($this->general_settings['showFeatureImageInPost'])) {
            $returnarray['showFeatureImageInPost']=(int)$this->general_settings['showFeatureImageInPost'];
            //$returnarray['order']=1;
          }
					$returnarray['homescreentype'] = 0;
					if($this->homepreset_settings['homescreentype']){
						//App kann nur mit der 1 was anfangen
						//$returnarray['homescreentype']=(int) $this->homepreset_settings['homescreentype'];

						//App kann nichts damit anfangen, daher muss immer recent stehen
						if($this->homepreset_settings['homescreentype']==1 || $this->homepreset_settings['homescreentype']==2)
						$returnarray['homescreentype'] = 1;
						if($this->homepreset_settings['sorttype'])$returnarray['sorttype']=$this->homepreset_settings['sorttype'];
						else $returnarray['sorttype']='recent';
						$returnarray['sorttype']='recent';

					}
					else $returnarray['homescreentype']=0;

					//echo $ts;

					$lang = new NH_YNAA_Language;
					if(!$this->general_settings['lang']) $this->general_settings['lang']= 'en';
					$returnarray['lang']=$this->general_settings['lang'];
        			$returnarray['lang_array'] = $lang->getTranslation($this->general_settings['lang']);
					/*if($this->general_settings['lang'] == 'de'){
						$returnarray['lang']='de';
						$returnarray['lang_array'] = self::$lang_de;
					}
					else {
						$returnarray['lang']='en';
						foreach(self::$lang_de as $k=>$v){
							$lang_en[$k]=$k;

						}
						$returnarray['lang_array'] = $lang_en;
					}*/
					if($this->general_settings['theme']) $returnarray['theme'] = (int) $this->general_settings['theme'];
					else $returnarray['theme']=0;
					if(!$this->general_settings['cm'])$this->general_settings['cm'] =$this->general_settings['c1'];
					$returnarray['changes']=1;
					$returnarray['color-01']=($this->general_settings['c1']);
					$returnarray['color-02']=$this->general_settings['c2'];
					$returnarray['color-navbar']=$this->general_settings['cn'];
					$returnarray['color-menu']=$this->general_settings['cm'];
					$returnarray['color-text']=$this->general_settings['ct'];
					$returnarray['color-headline']=$this->general_settings['ch'];
					//$returnarray['color-subheadline']=$this->general_settings['csh'];

					if($this->general_settings['logo'])$returnarray['logoUrl']=$this->general_settings['logo'];
					else $returnarray['logoUrl']='';
					$returnarray['hasCategories']=1;
					$returnarray['menuIsSectioned']=0;
					$returnarray['categories']=1;
					$returnarray['allowreorder']=1;

					if($this->general_settings['gaTrackID'])$returnarray['gaTrackID']=$this->general_settings['gaTrackID'];
					if($this->general_settings['comments'])	$returnarray['comments']=$this->general_settings['comments'];
					else $returnarray['comments']=0;
					//$returnarray['style']='<style type="text/css">body { color:#'.$this->general_settings['ct'].';}'.($this->general_settings['css']).'</style>';

					if($this->menu_settings['menu']){
						//var_dump($this->menu_settings);
						foreach($this->menu_settings['menu'] as $k=>$ar){
							if($ar['status']==0) continue;
							else {
								$post_date = 0;
								//echo  $ar['title'].'<br>';
								if($ar['type'] != 'app' && $ar['type'] != 'cat' && $ar['type'] != 'fb' && $ar['type'] != 'map' && $ar['type'] != 'webview' && $ar['type'] != 'events' && $ar['type'] != 'pushCenter' ){
									//echo  $ar['title'];
									//echo get_post_status($ar['item_id']);
									//.get_post_status($ar['item_id']."\r\n";
									if(get_post_status($ar['item_id']) != 'publish') {
										//echo $ar['item_id'].':'.get_post_status($ar['item_id']).$ar['title']."\r\n";
										continue;
									}
									/*else {
										$get_postdata = get_postdata($ar['item_id'] );
										$post_date = @strtotime($get_postdata['Date']);
									}*/
								}
								unset($tempmenu);
								if($ar['id']==-99 && ($this->homepreset_settings['homescreentype']== '1' || $this->homepreset_settings['homescreentype']== '2' )) continue;

								$tempmenu['pos'] =  $ar['pos'];
								$tempmenu['type'] =  $ar['type'];
								$tempmenu['id'] =  (int)$ar['id'];
								$tempmenu['title'] =  $ar['title'];
								$tempmenu['ts']= $this->menu_settings['ts'];
								$tempmenu['post_date']= $post_date;
								if(isset($ar['content']))$tempmenu['content'] = $ar['content'];
								if(isset($ar['item_id']))$tempmenu['item_id'] = (int)$ar['item_id'];
								if(isset($ar['url'])){

									$tempmenu['url'] = $ar['url'];
								}

								$returnarray['menu'][] = $tempmenu;
								//array_push($returnarray['menu'],$tempmenu);
							}
						}

						unset($tempmenu);

						/*$tempmenu['pos'] =  $ar['pos']+1;
						$tempmenu['type'] =  'events';
						$tempmenu['id'] =  $ar['id']+1;
						$tempmenu['title'] =  'Events';
						//if(isset($ar['content']))$tempmenu['content'] = $ar['content'];
						//if(isset($ar['item_id']))$tempmenu['item_id'] = $ar['item_id'];
						$returnarray['menu'][] = $tempmenu;*/
					}
					else {
						//$returnarray['menu']['error']=$this->nh_ynaa_errorcode(14);
					}


				}
				else{
					$returnarray['changes']=0;
				}

				$returnarray['timestamp']=$ts;
			}

			return array('settings'=>$returnarray);
		} // END private function settings()



		/**
		 * Return Homepresets Array
		 */
		private function nh_ynaa_homepresets(){
			$returnarray['error']=$this->nh_ynaa_errorcode(0);
			if(!get_option($this->homepreset_settings_key))   {
				//echo 'Keine settings';
				$returnarray['error']=$this->nh_ynaa_errorcode(23);
			}
			else {
				if($_GET[$this->requesvar['ts']]) $ts= $_GET[$this->requesvar['ts']];
				else $ts = 0;

				/*if(($this->general_settings['homescreentype'] && $this->general_settings['sorttype']) || ($_GET[$this->requesvar['option']]=1 && $_GET[$this->requesvar['sorttype']]) ){
					// The Query
					$args = array('post_status'=>'publish' , 'post_type'=>'post', 'nopaging'=>true);
					$the_query = new WP_Query( $args );

					// The Loop
					if ( $the_query->have_posts() ) {
						$i=1;
						while ( $the_query->have_posts() ) {
							$the_query->the_post();
							$cat_id = '';
							$cat_id_array = $this->nh_getpostcategories($the_query->post->ID);

							if($cat_id_array) $cat_id = $cat_id_array[0];
							$img = $this->nh_getthumblepic($the_query->post->ID);
							$returnarray['items'][]=array('pos'=>$i, "type"=>get_post_type(), 'allowRemove'=> 1, 'id'=> $the_query->post->ID, 'cat_id'=>$cat_id, 'cat_id_array'=>$cat_id_array,  'title'=>get_the_title(), 'img'=>$img, 'post_id'=>$the_query->post->ID, 'timestamp'=>strtotime($the_query->post->post_modified), 'publish_timestamp' =>strtotime($the_query->post->post_date), 'showsubcategories'=>0);

							$i++;
						}

					} else {
						// no posts found
					}
					// Restore original Post Data
					wp_reset_postdata();
				}
				else*/

				$ts_homepreset = -1;
				$returnarray['changes']=0;
				$ts_homepreset=  get_option( 'nh_ynaa_homepreset_settings_ts' );
				//if(!$ts_homepreset) $ts = -1;
				if($ts<$ts_homepreset) {
					$returnarray['changes']=1;
					$ts = $ts_homepreset;
					if(($_GET[$this->requesvar['meta']] && $_GET[$this->requesvar['cat_include']]) ||$this->homepreset_settings['homescreentype']==3){
						$categoris = ($this->nh_ynaa_categories());
						//var_dump($categoris);
						if($categoris['categories']['ass_cats'] && is_array($categoris['categories']['ass_cats']) && count($categoris['ass_cats']>0)){
							$i=1;
							$allowRemove=0;
							$cat_id = 0;
							$img = '';

							foreach($categoris['categories']['ass_cats'] as $k=>$cat){
								$item["pos"] = $i;
								$item["type"] = $cat['type'];
								$item["id"] = (string)$cat['id'];
								$item["cat_id"] = $cat['id'];
								$item["title"] = $cat['title'];
								if($cat['post_img'] && empty($cat['use_cat_img']))$item["img"] = $cat['post_img'];
								else $item["img"] = $cat['img'];
								$item["post_id"] = $cat['post_id'];
								$item["timestamp"] = $cat['post_ts'];
								$item["publish_timestamp"] = $cat['publish_timestamp'];
								$item["post_date"] = $cat['publish_timestamp'];
								$item["showsubcategories"] = $cat['showsubcategories'];
								$item["url"] = '';
								$returnarray['items'][]=$item;
								unset($item);
								$i++;
							}
						}
						else{
							$returnarray['error']=$this->nh_ynaa_errorcode(23);
						}
					}
					elseif($this->homepreset_settings['homescreentype']==1 || $this->homepreset_settings['homescreentype']==2){
						$returnarray['homescreentype']=(int)$this->homepreset_settings['homescreentype'];
						$returnarray['sorttype']=$this->homepreset_settings['sorttype'];
					}
					else{
						if($this->homepreset_settings['items']){
							$returnarray['changes']=1;
							/*if($ts<$this->homepreset_settings['ts']) {
								$returnarray['changes']=1;
								$ts = $this->homepreset_settings['ts'];
							}*/
							$i=1;
							//Facebook
							/*if(isset($this->general_settings['social_fbid'],$this->general_settings['social_fbsecretid'],$this->general_settings['social_fbappid'])){
								if(require_once('facebook-php-sdk-master/src/facebook.php')){
									$config = array(
									  'appId' => $this->general_settings['social_fbappid'],
									  'secret' => $this->general_settings['social_fbsecretid'],
									  'fileUpload' => false // optional
									);
									$facebook = new Facebook($config);
									$access_token = $facebook->getAccessToken();
									if( $access_token){
										$returnarray['error']=$this->nh_ynaa_errorcode(0);
										$items = file_get_contents('https://graph.facebook.com/'.$this->general_settings['social_fbid'].'/feed?access_token='.$access_token.'&format=json&limit=1');
										if($items){
											$items= json_decode($items,true);
										//var_dump($items['data']);
											$returnarray['items'][]=array('pos'=>$i, 'type' => 'fb', 'allowRemove'=> 1, 'id'=> -2, 'cat_id'=>-2,  'title'=>__('Facebook','nh-ynaa'), 'img'=>$items['data'][0]['picture'], 'post_id'=>$items['data'][0]['id'], 'timestamp'=>strtotime($items['data'][0]['created_time']), 'publish_timestamp' =>strtotime($items['data'][0]['created_time']));
											$i++;
										}
									}
									else{
										$returnarray['error']=$this->nh_ynaa_errorcode(27);
									}
								}
								else {
									$returnarray['error']=$this->nh_ynaa_errorcode(26);
								}
							}*/
							if(is_array($this->homepreset_settings['items']) && count($this->homepreset_settings['items'])>0){

								/*foreach($this->homepreset_settings['items'] as $hp){
									if(($hp['type'] == 'cat' || $hp['type'] == 'fb' || $hp['type'] == 'events' || $hp['type'] == 'map'   ) && $hp['img']){

										$categorys[$hp['id']]['img'] =   $hp['img'];
									}
								}*/
								//var_dump($this->homepreset_settings['items'] );
								foreach($this->homepreset_settings['items'] as $hp){
									//var_dump($hp);
									$post_title= '';
									if($hp['type'] == 'map' && !$this->general_settings['location']) { continue; }
									if($hp['type'] == 'cat') {
										if($this->categories_settings[$hp['id']]['hidecat'] || !$this->nh_is_category($hp['id'])) { continue; }
									}
									if($hp['type'] != 'cat' && $hp['type'] != 'fb' && $hp['type'] != 'map' && $hp['type'] != 'webview' && $hp['type'] != 'events' && $hp['type']!='pushCenter' ){
										if(get_post_status($hp['id']) != 'publish') continue;
									}
									if($hp['allowRemove']) $allowRemove = 1; else $allowRemove=0;
									$cat_id = 0;
									$img = '';
									$items['articles']['items'][0]['id'] = '';
									$items['articles']['items'][0]['timestamp'] = 0;
									$items['articles']['items'][0]['publish_timestamp'] = 0;
									$items['articles']['items'][0]['url'] = '';
									if($hp['type'] == 'cat'){
										$cat_id	= (int) $hp['id'];
										$items = ($this->nh_ynaa_articles($hp['id'],1,'full'));
										$items['articles']['items'][0]['url'] = '';
										if($items['articles']['items'][0]['thumb']) {
											$img = $items['articles']['items'][0]['thumb'];
										}
										elseif($this->categories_settings[$cat_id]['img']){
											$img = $this->categories_settings[$cat_id]['img'];
										}
										elseif($hp['img']) $img = $hp['img'];

										if($this->categories_settings[$cat_id]['usecatimg']) $img = $this->categories_settings[$cat_id]['img'];


									}
									elseif($hp['type'] == 'fb'){
										$cat_id	= (int)  $hp['id'];
										$fb = $this->nh_ynaa_get_fbcontent(1);
										if($fb){
											if($this->general_settings['debug'] ==1 && $_GET['debug']==1){
													var_dump($fb);
											}
											$fb = json_decode($fb,true);
											$items['articles']['items'][0]['id']=$fb['data'][0]['id'];
											$items['articles']['items'][0]['timestamp']=strtotime($fb['data'][0]['created_time']);
											$items['articles']['items'][0]['publish_timestamp']=strtotime($fb['data'][0]['created_time']);
											$img = $fb['data'][0]['picture'];
										}
										if(!$img &&  $this->categories_settings[-2]['img']) $img = $this->categories_settings[-2]['img'];
										if(!$img) $img = $hp['img'];

									}
									elseif($hp['type'] == 'map'){

										$cat_id	= (int)  $hp['id'];
										//$location = $this->nh_ynaa_locations(1);

										//if($location){
											//	var_dump($location);
											$items['articles']['items'][0]['id']=$cat_id;
											$items['articles']['items'][0]['timestamp']=time();
											$items['articles']['items'][0]['publish_timestamp']=time();
											$img = '';
										//}

										if(!$img &&  $this->categories_settings[-98]['img']) $img = $this->categories_settings[-98]['img'];
										if(!$img) $img = $hp['img'];

									}
									elseif($hp['type'] == 'webview'){
										$cat_id	= (int)  $hp['id'];
										//var_dump($hp);
										//$location = $this->nh_ynaa_locations(1);

										//if($location){
											//	var_dump($location);
											$items['articles']['items'][0]['id']=($cat_id)-(100+$hp['id2']);
											if($hp['url'] &&  (substr($hp['url'],0,7) != 'http://')) $hp['url'] = 'http://'.$hp['url'];
											$items['articles']['items'][0]['url']=$hp['url'];

											$items['articles']['items'][0]['timestamp']=time();
											$items['articles']['items'][0]['publish_timestamp']=time();
											$img = '';

										//}

										//if(!$img &&  $this->categories_settings[-98]['img']) $img = $this->categories_settings[-98]['img'];
										if(!$img && $hp['img']) $img = $hp['img'];

									}
									elseif($hp['type'] == 'events'){

										$cat_id	= (int)  $hp['id'];
										$img = '';
										if($this->categories_settings[-1]['img']) $img = $this->categories_settings[-1]['img'];
										$_GET[$this->requesvar['id']] = $hp['id'];
										$event = $this->nh_ynaa_events(1);
										if($event){
											$items['articles']['items'][0]['id']=$event['events']['items'][0]['id'];
											$items['articles']['items'][0]['timestamp']=$event['events']['items'][0]['timestamp'];
											$items['articles']['items'][0]['publish_timestamp']=$event['events']['items'][0]['publish_timestamp'];
											if(!$img)$img = $event['events']['items'][0]['thumb'];
											$post_title = $event['events']['items'][0]['title'];
											//$items['articles']['items'][0]['publish_timestamp']= $event;
										}
										/*$event = $this->nh_ynaa_events($hp['id']);
										if($event){
											$items['articles']['items'][0]['id']=$event['events']['items'][0]['id'];
											$items['articles']['items'][0]['timestamp']=$event['events']['items'][0]['timestamp'];
											$items['articles']['items'][0]['publish_timestamp']=$event['events']['items'][0]['publish_timestamp'];
											$img = $event['events']['items'][0]['thumb'];
										}*/

										//if(!$img) $img = $hp['img'];

									}
									elseif($hp['type'] == 'event'){

										$cat_id	= (int)  $hp['id'];
										$img = '';
										$_GET[$this->requesvar['id']] = $hp['id'];
										$event = $this->nh_ynaa_event();
										//var_dump($event);
										if($event){
											$items['articles']['items'][0]['id']=(int)  $hp['id'];
											$items['articles']['items'][0]['timestamp']=$event['event']['timestamp'];
											$items['articles']['items'][0]['publish_timestamp']=$event['event']['publish_timestamp'];
											$post_title= ($event['event']['title']);
											$img = $event['event']['thumb'];
											//$items['articles']['items'][0]['publish_timestamp']= $event;
										}
										/*$event = $this->nh_ynaa_events($hp['id']);
										if($event){
											$items['articles']['items'][0]['id']=$event['events']['items'][0]['id'];
											$items['articles']['items'][0]['timestamp']=$event['events']['items'][0]['timestamp'];
											$items['articles']['items'][0]['publish_timestamp']=$event['events']['items'][0]['publish_timestamp'];
											$img = $event['events']['items'][0]['thumb'];
										}*/
										if(!$img &&  $this->categories_settings[-1]['img']) $img = $this->categories_settings[-1]['img'];
										if(!$img) $img = $hp['img'];

									}
									elseif($hp['type'] == 'pushCenter'){

										$cat_id	= 0;

									}
									else {
										$post_categories = wp_get_post_categories($hp['id'] );
										if($post_categories){
											foreach($post_categories as $c){
												$cat_id =  $c ;
												break;
											}
										}
										$items['articles']['items'][0]['id'] = $hp['id'];
										$img = $this->nh_getthumblepic($hp['id']);
										if((!$img) && isset($categorys[$hp['id']]['img'])){
											 $img = $categorys[$hp['id']]['img'];
										}
										$p = wp_get_single_post($hp['id']);
										if($p){
											//var_dump($p);
											$items['articles']['items'][0]['timestamp'] = strtotime($p->post_modified);
											$items['articles']['items'][0]['publish_timestamp'] = strtotime($p->post_date);
											$post_title= html_entity_decode($p->post_title);
										}

										$hp['type'] = 'article';
									}
									$showsub = 0;

									if($cat_id && $this->categories_settings[$cat_id]['showsub']) $showsub=1;
									$returnarray['items'][]=array('pos'=>$i, 'type' => $hp['type'], 'allowRemove'=> $allowRemove, 'id'=> (int)$hp['id'], 'cat_id'=>$cat_id,  'title'=>html_entity_decode($hp['title']), 'img'=>$img,'post_title'=> $post_title, 'post_id'=>$items['articles']['items'][0]['id'], 'timestamp'=>$items['articles']['items'][0]['timestamp'], 'publish_timestamp' =>$items['articles']['items'][0]['publish_timestamp'], 'showsubcategories'=>$showsub, 'url'=>$items['articles']['items'][0]['url']);
									$i++;

								}

							}
							else {

								$returnarray['error']=$this->nh_ynaa_errorcode(23);
							}


							if(!isset($returnarray['items'])){
								$returnarray['error']=$this->nh_ynaa_errorcode(23);
							}
						}
						else{
							$returnarray['error']=$this->nh_ynaa_errorcode(23);
						}
					}
				}
			}

			$returnarray['timestamp']=$ts;
			return array('homepresets'=>$returnarray);
		} // END private function homepresets()

		/**
		 * Return Teaser Array
		 */
		private function nh_ynaa_teaser(){

			$returnarray['error']=$this->nh_ynaa_errorcode(0);
			if(!get_option($this->teaser_settings_key))   {
				//echo 'Keine settings';
				$returnarray['error']=$this->nh_ynaa_errorcode(18);
			}
			else {

				if($_GET[$this->requesvar['ts']]) $ts= $_GET[$this->requesvar['ts']];
				else $ts = 0;
				$returnarray['changes']=0;
				$ts_getoption = get_option( 'nh_ynaa_teaser_settings_ts' );

				if($ts<$ts_getoption) {
					$returnarray['changes']=1;
					$ts = $ts_getoption;
				}
				if($_GET[$this->requesvar['cat_include']]){
					$cat_include = explode(',',$_GET[$this->requesvar['cat_include']]);
				}
				//var_dump($cat_include);
				//var_dump($this->teaser_settings);
				if(isset($_GET[$this->requesvar['meta']])){
				 // echo "META";
          			$catitems = ($this->nh_ynaa_articles(0,3,'full'));
          			// var_dump($catitems);
              		if($catitems && is_array($catitems)){

                	if(isset($catitems['articles']['error']['error_code']) && $catitems['articles']['error']['error_code']!= 0){
                		$returnarray['error']=$this->nh_ynaa_errorcode(18);
                	}
                	else {

                  if($catitems['articles']['items']){
                    foreach($catitems['articles']['items'] as $item){

                      $item['apectFill'] = 1;
                      $item['post_ts'] = $item['timestamp'];
                      $item['id'] = $item['post_id'];
                      $item['thumb'] = $item['img'];
                      if($ts < $item['post_ts']) {
                        $returnarray['changes']=1;
                        $ts = $item['post_ts'];
                      }
                      $returnarray['items'][] =   $item;
                    }
                  }
                  else $returnarray['error']=$this->nh_ynaa_errorcode(18);


                }

              }
              else {
                $returnarray['error']=$this->nh_ynaa_errorcode(18);
              }
				}
       			 elseif((!isset($this->teaser_settings['source']) || $this->teaser_settings['source']=='indi') && $this->teaser_settings['teaser']){

					if(is_array($this->teaser_settings['teaser']) && count($this->teaser_settings['teaser'])>0){
						$teasers = $this->teaser_settings['teaser'];
						$i = 1;
						foreach($teasers as $k=>$teaser){
							if($k && $k=='type') continue;

							if($teasers['type'][$k]=='cat'){
								$item = $this->ny_ynaa_teaser_action($teaser,'cat');
								//var_dump($item);
								$returnarray['items'][]=array('pos'=>(int)$i, 'apectFill'=>1, 'type' => 'cat', 'id'=> (int) $teaser, 'title'=> $item['title'], 'thumb'=>$item['img'], 'cat_id'=>(int) $teaser, 'post_ts'=>0, 'post_date'=>0);
								$i++;
								continue;
							}


							$p = wp_get_single_post($teaser);

							if($p){
								//var_dump($p);
								if( strtotime($p->post_modified) > $ts){
									$returnarray['changes']=1;
									$ts = strtotime($p->post_modified);
								}
								$category = get_the_category($teaser);

								if($_GET[$this->requesvar['meta']]){
									if(!$category[0]->term_id || is_null($category[0]->term_id)) continue;
									if($cat_include && !in_array($category[0]->term_id,$cat_include)) continue;
								}
								if(get_post_type($teaser)=='event') $category[0]->term_id=0;
								$posttitle = str_replace(array("\\r","\\n","\r", "\n"),'',trim(html_entity_decode(strip_tags(do_shortcode($p->post_title)), ENT_NOQUOTES, 'UTF-8')));
								$returnarray['items'][]=array('pos'=>(int)$i, 'apectFill'=>1, 'type' => get_post_type($teaser), 'id'=> (int) $teaser, 'title'=> $posttitle, 'thumb'=>$this->nh_getthumblepic($teaser, 'full'), 'cat_id'=>$category[0]->term_id, 'post_ts'=>strtotime($p->post_modified), 'post_date'=>strtotime($p->post_date));
								$i++;
								unset($category);
							}
						}

					}
					else {

						$returnarray['error']=$this->nh_ynaa_errorcode(18);
					}


				}
				elseif(isset($this->teaser_settings['source']) && $this->teaser_settings['source']!='indi' ){

					if(!$this->teaser_settings['limit']){

						$returnarray['error']=$this->nh_ynaa_errorcode(18);
					}
					else{

						if($this->teaser_settings['source']=='cat' && !$this->teaser_settings['source']){

							$returnarray['error']=$this->nh_ynaa_errorcode(18);
						}
						elseif($this->teaser_settings['source']=='cat'){

							$catitems = ($this->nh_ynaa_articles($this->teaser_settings['cat'],$this->teaser_settings['limit'],'full'));

							if($catitems && is_array($catitems)){

								if(isset($catitems['articles']['error']['error_code']) && $catitems['articles']['error']['error_code']!= 0)
								$returnarray['error']=$this->nh_ynaa_errorcode(18);
								else {

									if($catitems['articles']['items']){
										foreach($catitems['articles']['items'] as $item){
											$item['cat_id']=(int)$this->teaser_settings['cat'];
											$item['apectFill'] = 1;
											$item['post_ts'] = $item['timestamp'];
											if($ts < $item['post_ts']) {
												$returnarray['changes']=1;
												$ts = $item['post_ts'];
											}
											$returnarray['items'][] = 	$item;
										}
									}
									else $returnarray['error']=$this->nh_ynaa_errorcode(18);


								}

							}
							else {
								$returnarray['error']=$this->nh_ynaa_errorcode(18);
							}
						}
						elseif($this->teaser_settings['source']=='recent'){

							//var_dump($this->teaser_settings['cat'],$this->teaser_settings['limit']);
							$_GET[$this->requesvar['option']]=1;
							$_GET[$this->requesvar['sorttype']]='recent';
							$_GET[$this->requesvar['limit']]=$this->teaser_settings['limit'];
							$this->homepreset_settings['sorttype']='date-desc';
							$catitems = ($this->nh_ynaa_articles(0,$this->teaser_settings['limit'],'full'));

							if($catitems && is_array($catitems)){

								if(isset($catitems['articles']['error']['error_code']) && $catitems['articles']['error']['error_code']!= 0)
								$returnarray['error']=$this->nh_ynaa_errorcode(18);
								else {

									if($catitems['articles']['items']){
										foreach($catitems['articles']['items'] as $item){

											$item['apectFill'] = 1;
											$item['post_ts'] = $item['timestamp'];
											$item['id'] = $item['post_id'];
											$item['thumb'] = $item['img'];
											if($ts < $item['post_ts']) {
												$returnarray['changes']=1;
												$ts = $item['post_ts'];
											}
											$returnarray['items'][] = 	$item;
										}
									}
									else $returnarray['error']=$this->nh_ynaa_errorcode(18);


								}

							}
							else {
								$returnarray['error']=$this->nh_ynaa_errorcode(18);
							}
						}
						else{
							$returnarray['error']=$this->nh_ynaa_errorcode(37);
						}
					}

				}
				else {
					$returnarray['error']=$this->nh_ynaa_errorcode(18);
				}
				$returnarray['timestamp']=$ts;

			}
			if($returnarray['changes']==0 && isset($returnarray['items'])) {
				unset($returnarray['items']);

			}
			return array('teaser'=>$returnarray);
		} // END private function teaser()

		/**
		 * Return Categories Array

		 */
		private function nh_ynaa_categories(){

			$returnarray['error']=$this->nh_ynaa_errorcode(0);
			$returnarray['changes']=0;
			//$returnarray['uma']['ts']=time();
			//$returnarray['uma']['current_time']=current_time('timestamp');


			$hide_empty = 1;
      if(isset($this->general_settings['debug'])&& $this->general_settings['debug'] ==1 && isset($_GET['debug']) && $_GET['debug']==1){
        $hide_empty = 0;
      }


			$args=array(
			  'orderby' => 'name',
			  'order' => 'asc',
			  'hide_empty'=>0,
			  'taxonomy' => $this->nh_find_taxonomies()
			);
			if(isset($_GET[$this->requesvar['meta']]) && isset($_GET[$this->requesvar['cat_include']])){
				$args['include']=$_GET['cat_include'];
			}
			if(isset($_GET[$this->requesvar['ts']])) {
				$ts= $_GET[$this->requesvar['ts']];
			}
			else {
				$ts = 0;
			}
			$categories = @get_categories( $args );
			$i=0;
			$parent = array();
			$cat = array();

			if($categories && is_array($categories) && count($categories)>0){
				/*$homepresets = $this->nh_ynaa_homepresets();
				//var_dump($homepresets);
				//echo '<hr>';
				if($homepresets ["homepresets"]['items']){
					foreach($homepresets ["homepresets"]['items'] as $item){
							$hp[$item['cat_id']]['img'] =  $item['img'];
					}
				}*/
				$ass_cats = array();
				//var_dump($hp);
				//echo '<hr>';

				foreach ( $categories as $category ) {

					if($this->categories_settings[$category->term_id]['hidecat']) continue;
					if($this->categories_settings[$category->term_id]['cat_name']) $category->name = $this->categories_settings[$category->term_id]['cat_name'];


					//For Sub categories

					$post_thumbnail_image[0]='';
					$post_id='';
					$allowRemove = 1;

					if($ts < get_option( 'nh_ynaa_categories_settings_ts' ))
					{
						$returnarray['changes']=1;
						$ts = get_option( 'nh_ynaa_categories_settings_ts' );
					}

					$items = ($this->nh_ynaa_articles($category->term_id,1));
         // $returnarray['uma' ]['cat_item'][]=$items;
					$allcategories[$category->term_id]['title']= htmlspecialchars_decode($category->name);
					$allcategories[$category->term_id]['pos']=$i;
					if($items['articles']['items']){
						//var_dump($items['articles']['items']);
						//echo '<hr>';
						if($category->parent)$parent[$category->term_id]=$category->parent;

						if($ts<=$items['articles']['items'][0]['timestamp']) {
							$returnarray['changes']=1;
							$ts = $items['articles']['items'][0]['timestamp'];
						}
						//echo $items['articles']['items'][0]['thumb'].'<br>';
						if(!$items['articles']['items'][0]['thumb'] || is_null($items['articles']['items'][0]['thumb']) || $items['articles']['items'][0]['thumb'] == 'null') {
							$items['articles']['items'][0]['thumb']='';
							//echo $items['articles']['items'][0]['thumb'].'1<br>';
							/*if($this->categories_settings[$category->term_id]['img']) $items['articles']['items'][0]['thumb'] =$this->categories_settings[$category->term_id]['img'];
							else*/
							//if($hp[$category->term_id]['img']) $items['articles']['items'][0]['thumb'] = $hp[$category->term_id]['img'];
						}
						if(!$this->categories_settings[$category->term_id]['img'] || is_null($this->categories_settings[$category->term_id]['img']))
						{
							$this->categories_settings[$category->term_id]['img']='';

						}
            if($this->categories_settings[$category->term_id]['usecatimg']){
              $use_cat_img = 1;
            }
            else $use_cat_img = 0;
						$cat[$category->term_id]=array('pos'=>$i, 'type'=>'cat', 'id'=> (int) $category->term_id, 'parent_id'=> $category->parent, 'title'=>htmlspecialchars_decode($category->name), 'post_img'=>$items['articles']['items'][0]['thumb'], 'img'=>$this->categories_settings[$category->term_id]['img'], 'post_id'=>$items['articles']['items'][0]['id'] ,'post_date'=>$items['articles']['items'][0]['publish_timestamp'], 'post_ts'=>$items['articles']['items'][0]['timestamp'] ,'allowRemove'=> $allowRemove, 'itemdirekt'=>1, 'use_cat_img'=> $use_cat_img );

						//$ass_cats[$category->term_id] = array('img'=>'');
						if($this->categories_settings[$category->term_id]['showsub']){
							$cat[$category->term_id]['showsubcategories']=1;
							if($this->categories_settings[$category->term_id]['showoverviewposts']) {
								 $cat[$category->term_id]['showoverviewposts'] = 1;
							}
							else $cat[$category->term_id]['showoverviewposts'] = 0;
							//$ass_cats[$category->term_id]['showsubcategories']=1;
						}
						else {
							$cat[$category->term_id]['showsubcategories']=0;
							$cat[$category->term_id]['showoverviewposts'] = 0;
						}

						$ass_cats[$category->term_id] = array('showsubcategories'=>$cat[$category->term_id]['showsubcategories'], 'showoverviewposts'=>$cat[$category->term_id]['showoverviewposts'],'img'=>$this->categories_settings[$category->term_id]['img'], 'pos'=>$i, 'type'=>'cat', 'id'=> (int) $category->term_id, 'parent_id'=>$category->parent, 'title'=>htmlspecialchars_decode($category->name), 'post_img'=>$items['articles']['items'][0]['thumb'], 'post_id'=>$items['articles']['items'][0]['id'] ,'publish_timestamp'=>$items['articles']['items'][0]['publish_timestamp'],'post_date'=>$items['articles']['items'][0]['publish_timestamp'],'post_ts'=>$items['articles']['items'][0]['timestamp'] ,'allowRemove'=> $allowRemove, 'itemdirekt'=>1, 'use_cat_img'=> $use_cat_img   );
						//$ass_cats[$category->term_id] = array('img'=>'http://yna.nebelhorn.com/wp-content/uploads/2014/02/image-653473-breitwandaufmacher-ixpz-300x111.jpg');

						$i++;
						unset($items);
					}
				}



				//Categories in Subcategories
				if(count($parent)>0){
					asort($parent);

					foreach($parent as $k=>$v){
						if(!$cat[$v] || (!(isset($cat[$v]['itemdirekt'])) &&  ($cat[$v]['post_ts'] < $cat[$k]['post_ts']))){

							$cat[$v]=$cat[$k];
							$cat[$v]['pos']=$allcategories[$v]['pos'];
							$cat[$v]['pos']=0;
							$cat[$v]['id']=$v;

							$cat[$v]['parent_id']=0;
							$cat[$v]['title']=$allcategories[$v]['title'];
							unset($cat[$v]['itemdirekt']);
						}

						/*if(isset($cat[$v]['subcategories'])){
							$pos = max(array_keys($cat[$v]['subcategories']));
						}
						else $pos= -1;*/
						/*if(($pos != '') || $pos ===0 ) { $pos++; }
						else {$pos=0;}
						$cat[$k]['pos']=$pos;*/
						//if($this->categories_settings[$k]['showsub'])$cat[$k]['showsubcategories']=1;
						$cat[$v]['subcategories'][]=$cat[$k];
						unset($cat[$k]);

					}
				}

				//$returnarray['items']= $cat;
				if($cat && count($cat)>0){
					foreach($cat as $k=>$v)
					$returnarray['items'][] = $v;

				}



			}
			else {
				$returnarray['error']=$this->nh_ynaa_errorcode(20);
			}

			//Events
			if(!isset($_GET[$this->requesvar['meta']]) && isset($this->general_settings['eventplugin']) ){

				$items = $this->nh_ynaa_events(1);
				if($items['events']['items']){
					if($ts<=$items['events']['items'][0]['timestamp']) {
						$returnarray['changes']=1;
						$ts = $items['events']['items'][0]['timestamp'];
					}
					$event_im = '';
					if(!isset($items['events']['items'][0]['thumb'])) $items['events']['items'][0]['thumb'] = '';
					//if(!$items['events']['items'][0]['thumb'] && $hp[-1]['img']) $items['events']['items'][0]['thumb'] = $hp[-1]['img'];
					//if(!$items['events']['items'][0]['thumb'] && $this->categories_settings[-1]['img']) $items['events']['items'][0]['thumb'] = $this->categories_settings[-1]['img'];
					if(isset($this->categories_settings[-1]['img'])) $event_im = $this->categories_settings[-1]['img'];
          if(!isset($this->categories_settings[-1]['cat_name'])) $$this->categories_settings[-1]['cat_name']= __('Events','nh-ynaa');
					$returnarray['items'][]=array('pos'=>$i, 'type'=>'events', 'id'=> -1, 'title'=>$this->categories_settings[-1]['cat_name'], 'img'=>$items['events']['items'][0]['thumb'], 'post_id'=>$items['events']['items'][0]['id'] ,'post_ts'=>$items['events']['items'][0]['timestamp'] ,'allowRemove'=> $allowRemove);
					$ass_cats[-1]=array('pos'=>$i, 'type'=>'events', 'id'=> -1, 'title'=>$this->categories_settings[-1]['cat_name'], 'img'=>$event_im, 'post_img'=>$items['events']['items'][0]['thumb'], 'post_id'=>$items['events']['items'][0]['id'] ,'post_ts'=>$items['events']['items'][0]['timestamp'] ,'allowRemove'=> $allowRemove);
					$i++;
					unset($items);
				}

			}

			//KArte

			if(!$_GET[$this->requesvar['meta']] && isset($this->general_settings['location']) ){
				//$hp[-98]['img'] = 'http://yna.nebelhorn.com/wp-content/uploads/2014/03/images.jpg';
				$map_img = '';
				if(isset($this->categories_settings[-98]['img'])) $map_img = $this->categories_settings[-98]['img'];
        if(!isset($this->categories_settings[-98]['cat_name'])) $$this->categories_settings[-98]['cat_name']= __('Map','nh-ynaa');
				//if(!$hp[-98]['img'] || $hp[-98]['img']==NULL || $hp[-98]['img']=='null') $hp[-98]['img']='';
				$returnarray['items'][]=array('pos'=>$i, 'type'=>'map', 'id'=> -98, 'title'=>$this->categories_settings[-98]['cat_name'], 'img'=>$map_img, 'allowRemove'=> 1);
				$ass_cats[-98]=array('pos'=>$i, 'type'=>'map', 'id'=> -98, 'title'=>$this->categories_settings[-98]['cat_name'],'img'=>$map_img, 'allowRemove'=> 1);
				$i++;
			}


			//Facebook
			$fb = $this->nh_ynaa_get_fbcontent(1);

			if($fb ){
				if(isset($fb['error']) && is_array($fb['error']) && $fb['error']['error_code']!=25){
					$returnarray['error'] = $fb['error'];

				}
				elseif(!isset($fb['error']['error_code'])) {
					$fb_img = '';
					$fb = json_decode($fb,true);
					if(!$fb['data'][0]['picture']) $fb['data'][0]['picture'] = '';
					//if(!$fb['data'][0]['picture'] && $hp[-2]['img']) $fb['data'][0]['picture'] = $hp[-2]['img'];
					if($this->categories_settings[-2]['img']) $fb_img =  $this->categories_settings[-2]['img'];
				 	$returnarray['items'][]=array('pos'=>$i, 'type'=>'fb', 'id'=> -2, 'title'=>__('Facebook','nh-ynaa'), 'img'=>$fb['data'][0]['picture'], 'post_id'=>$fb['data'][0]['id'] ,'post_ts'=>strtotime($fb['data'][0]['created_time']) ,'allowRemove'=> 1);
					$ass_cats[-2]=array('pos'=>$i, 'type'=>'fb', 'id'=> -2, 'title'=>__('Facebook','nh-ynaa'), 'img' => $fb_img, 'post_img'=>$fb['data'][0]['picture'], 'post_id'=>$fb['data'][0]['id'] ,'post_ts'=>strtotime($fb['data'][0]['created_time']) ,'allowRemove'=> 1);
					$i++;
				}
			}
			if($ass_cats && count($ass_cats)>0)
			$returnarray['ass_cats'] = $ass_cats;

      $returnarray['timestamp']= (int) get_option('nh_ynaa_categories_settings_ts');

			return array('categories'=>$returnarray);
		} // END private function categories()

		/**
		 * Return Aricles Array
		 */
		private function nh_ynaa_articles($id=0, $lim=0, $size=false){
			//var_dump($_GET);
			$allowRemove=1;
			//$returnarray['uma']['info_Articles_start'] = $id.'start nh_ynaa_articles'.$_GET[$this->requesvar['id']];
			//$returnarray['uma']['categories_settings'] = $this->categories_settings;
			$returnarray['error']=$this->nh_ynaa_errorcode(0);
				if(isset($_GET[$this->requesvar['id']]) || $id){
					if(( $id))$tempid= $id;
					else $tempid= $_GET[$this->requesvar['id']];
         // $returnarray['uma']['idwurdeübergeben'] = $tempid;
					if($_GET[$this->requesvar['cat_include']])	$cat_include = explode(',',$_GET[$this->requesvar['cat_include']]);
					if($this->categories_settings[$tempid]['hidecat'] || ($_GET[$this->requesvar['meta']] && ($cat_include && !in_array($tempid, $cat_include)))) {
					  //$returnarray['uma']['$this->categories_settings[$tempid][\'hidecat\']'] = $this->categories_settings[$tempid]['hidecat'];
            //$returnarray['uma']['$cat_include'] = $cat_include;
						$returnarray['changes']=1;
						$returnarray['timestamp']=time();
						//$returnarray['uma']['info_Articles'] = 'Die Kategorie wurde deaktiviert';
						$returnarray['error']=$this->nh_ynaa_errorcode(35);
						return array('articles'=>$returnarray);
					}
				}

        if(!empty($_GET[$this->requesvar['meta']]) && !isset($_GET[$this->requesvar['id']]) && !$id){
          $_GET[$this->requesvar['option']]=1;
          $_GET[$this->requesvar['sorttype']] = 'date-desc';
		  $this->homepreset_settings['sorttype']='date-desc';
          $_GET[$this->requesvar['limit']]=$lim;
        }

				if(($_GET[$this->requesvar['option']]==1 && $_GET[$this->requesvar['sorttype']]) ){
				  //$returnarray['uma']['switch'][] = '($_GET[$this->requesvar[\'option\']]==1 && $_GET[$this->requesvar[\'sorttype\']])';
					// The Query
					$returnarray['changes']=0;
					if($_GET[$this->requesvar['ts']])$returnarray['timestamp']=$_GET[$this->requesvar['ts']];
					else $returnarray['timestamp']=0;

					$timestamp = get_option( 'nh_ynaa_articles_ts' );
					if($returnarray['timestamp']<$timestamp){
						$returnarray['changes']=1;
						$returnarray['timestamp']= $timestamp;
					}

					$img_size = 'thumbnail';
					if($size)$img_size = $size;

					if(isset($_GET[$this->requesvar['id']])) $args['cat'] =$_GET[$this->requesvar['id']];
					elseif($id) $args['cat'] =$id;

					if(isset($_GET[$this->requesvar['limit']])) {
						$args['posts_per_page'] =$_GET[$this->requesvar['limit']];
						if(isset($_GET[$this->requesvar['offset']])){
							$args['offset'] =$_GET[$this->requesvar['offset']];
						}
					}
					else{
						$args ['nopaging'] = true;
					}
					$hidecat = array();
					if($this->categories_settings){

						foreach($this->categories_settings as $cat_id => $cat){
							//var_dump($cat);

							if($cat['hidecat']) $hidecat[] = $cat_id * -1;
						}


					}

					if(!empty($_GET[$this->requesvar['cat_include']])){
						$cat_include = explode(',',$_GET[$this->requesvar['cat_include']]);
						foreach ($cat_include as $key => $value) {
							$hidecat[] = $value;

						}
					}
					if($hidecat) {

							$args['cat']=implode($hidecat,',');;
						}

					if($this->homepreset_settings['sorttype']=='alpha-asc'){
						$args ['orderby'] = 'title';
						$args ['order'] = 'ASC';
					}
					elseif($this->homepreset_settings['sorttype']=='date-asc'){
						$args ['order'] = 'ASC';
						//$args ['orderby'] = 'post_date';
					}
		          elseif($this->homepreset_settings['sorttype']=='date-desc'){
		            $args ['order'] = 'DESC';
					  //$args ['orderby'] = 'post_date';
		          }

					if($this->homepreset_settings['homescreentype'] == 2) {
						$args ['post_type'] = 'page';
					}
					else $args ['post_type'] = 'post';

					$args ['post_status'] = 'publish';


					//var_dump($args);

					$the_query = new WP_Query( $args );

					// The Loop
					if ( $the_query->have_posts() ) {
						$i=1;
						while ( $the_query->have_posts() ) {
							
							
							$the_query->the_post();

							//var_dump($the_query->post->ID);

							//Hide POSTS
							$_nh_ynaa_meta_keys = (get_post_meta( $the_query->post->ID, '_nh_ynaa_meta_keys', true ));
							if($_nh_ynaa_meta_keys){
								$_nh_ynaa_meta_keys = unserialize($_nh_ynaa_meta_keys);
								if($_nh_ynaa_meta_keys && is_array($_nh_ynaa_meta_keys)){
									if(is_null($_nh_ynaa_meta_keys['s'])) {
										//var_dump ($_nh_ynaa_meta_keys);
										continue;
									}
								}

							}
							$cat_id = 0;
							$cat_id_array = $this->nh_getpostcategories($the_query->post->ID);

							if($cat_id_array) $cat_id = (int) $cat_id_array[0];
							$img = $this->nh_getthumblepic($the_query->post->ID);

              				$thumbnail = $this->nh_getthumblepic($the_query->post->ID,$img_size);
							$images = $this->nh_getthumblepic_allsize($the_query->post->ID);

							$post_type = get_post_type();

							//Weil die App sonst nicht zu recht muss type auf post gesetzt werden
							$post_type = 'article';
							$posttitle = str_replace(array("\\r","\\n","\r", "\n"),'',trim(html_entity_decode(strip_tags(do_shortcode($the_query->post->post_title)), ENT_NOQUOTES, 'UTF-8')));
							if($this->general_settings['theme']=3) $excerpt = get_the_excerpt() ;
							else $excerpt='';
							
							$returnarray['items'][]=array('pos'=>$i, "type"=>$post_type, 'allowRemove'=> 1, 'cat_id'=>$cat_id, 'cat_id_array'=>$cat_id_array,  'title'=> $posttitle, 'img'=>$img, 'thumb' => $thumbnail, 'images'=>$images, 'post_id'=>$the_query->post->ID, 'timestamp'=>strtotime($the_query->post->post_modified), 'publish_timestamp' =>strtotime($the_query->post->post_date),'post_date' =>strtotime($the_query->post->post_date), 'showsubcategories'=>0, 'excerpt'=>html_entity_decode(str_replace('[&hellip;]', '',$excerpt)));
							
							if(strtotime($the_query->post->post_modified) > $returnarray['timestamp']) {
								$returnarray['changes']=1;
								$returnarray['timestamp']= strtotime($the_query->post->post_modified);
							}
							$i++;
						}

					} else {
						$returnarray['error']=$this->nh_ynaa_errorcode(36);
					}
					// Restore original Post Data
					wp_reset_postdata();
					return array('articles'=>$returnarray);
				}
				elseif(isset($_GET[$this->requesvar['id']]) || $id) {

				$returnarray['changes']=0;
				//PostID
				//If Post ID Check if is ist the newest Post and if hat changes
				if(isset($_GET[$this->requesvar['post_id']]) && isset($_GET[$this->requesvar['post_ts']])){
					$break = false;
					$orderby = 'date';
					$order = 'DESC';
					if($this->categories_settings[$cid]['cat_order']){
						switch($this->categories_settings[$cid]['cat_order']){
							case 'alpha-asc': $orderby = 'title';	$order = 'ASC'; break;
							case 'date-asc': $orderby = 'date';	$order = 'ASC'; break;
							default: $orderby = 'date';	$order = 'DESC'; break;
						}
					}

					$latest_cat_post = new WP_Query( array('posts_per_page' => 1, 'post_type'=>'any', 'orderby' => $orderby, 'order'=>$order , 'category__in' => array($_GET[$this->requesvar['id']])));
					//var_dump($latest_cat_post);

					if( $latest_cat_post->have_posts() ) {
						if($latest_cat_post->posts[0]->ID == $_GET[$this->requesvar['post_id']]){
							$break = true;
							if(strtotime($latest_cat_post->posts[0]->post_modified)>$_GET[$this->requesvar['post_ts']]){
								$ts = strtotime($latest_cat_post->posts[0]->post_modified);
								$returnarray['changes']=1;
								//var_dump($this->categories_settings[$_GET[$this->requesvar['id']]]);
						/*		if ( has_post_thumbnail($latest_cat_post->posts[0]->ID)) {
									$post_thumbnail_image=wp_get_attachment_image_src(get_post_thumbnail_id($latest_cat_post->posts[0]->ID), 'original');
								}
								/*elseif($this->categories_settings[$_GET[$this->requesvar['id']]]['img']){
									$post_thumbnail_image=array($this->categories_settings[$_GET[$this->requesvar['id']]]['img']);
								}*/
						/*		else {
									$post_thumbnail_image=array();
								}
								*/

								$post_thumbnail_image[0] = $this->nh_getthumblepic($latest_cat_post->posts[0]->ID,'original');
								$images = $this->nh_getthumblepic_allsize($latest_cat_post->posts[0]->ID);
								$posttitle = str_replace(array("\\r","\\n","\r", "\n"),'',trim(html_entity_decode(strip_tags(do_shortcode($latest_cat_post->posts[0]->post_title)), ENT_NOQUOTES, 'UTF-8')));
								$returnarray['items'][] = array('pos'=>1, 'id'=>$latest_cat_post->posts[0]->ID,'title'=>$posttitle,'timestamp'=>strtotime($latest_cat_post->posts[0]->post_modified),'type'=>$latest_cat_post->posts[0]->post_type, 'thumb'=> ($post_thumbnail_image[0]), 'images'=>$images, 'publish_timestamp'=> strtotime($latest_cat_post->posts[0]->post_date), 'post_date'=> strtotime($latest_cat_post->posts[0]->post_date));
								//$returnarray['items'][]=array('pos'=>1, 'type' => $post->post_type, 'allowRemove'=> $allowRemove, 'id'=> $category->term_id, 'parent_id'=>0, 'title'=>$category->name, 'img'=>$post_thumbnail_image[0], 'post_id'=>$latest_cat_post->post->ID );
							}
							else {
								$ts = $_GET[$this->requesvar['post_ts']];
							}
						}


					}
					else{
						$break = true;
						$returnarray['error']=$this->nh_ynaa_errorcode(16);
						$ts = time();
						$returnarray['items'][] = array();

					}
					if($break) {
						$returnarray['orderby']=$orderby;
						$returnarray['order']=$order;
						$returnarray['timestamp']=$ts;
						$returnarray['error']=$this->nh_ynaa_errorcode(0);
						return array('articles'=>$returnarray);
					}
				}

				//Kategorie ID
				if($id) {
					$cid = $id;
					if($lim) $limit = $lim;
					else $limit=999;
				}
				else  {$cid = $_GET[$this->requesvar['id']];
					//LIMIT
					if($_GET[$this->requesvar['limit']]) {
						$limit=$_GET[$this->requesvar['limit']];
					}
					else {
						$limit = 999;
					}
				}

				//Timestamp
				if($_GET[$this->requesvar['ts']]) {
					$ts= $_GET[$this->requesvar['ts']];
					//Immer cahnges true
					$ts=0;
					$ts_string = date('Y-m-d H:i:s',$ts);
				}
				else {
					$ts = 0;
					$ts_string = date('0000-00-00 00:00:00');
				}
				//WP Query
				global $wpdb;
				$table_posts = $wpdb->prefix . "posts";
				$table_term_relationships = $wpdb->prefix . "term_relationships";
				/*$post_ids = $wpdb->get_col( $wpdb->prepare( "select * from $table_posts p
								left join $table_term_relationships tr on tr.object_id=p.ID
								where p.post_status='publish' and tr.term_taxonomy_id=$cid
								and p.post_modified>'$ts_string'
								order by p.post_modified desc
								$limit",'%d'));
				*/

				//Order by post_modified
				/*$post_ids = $wpdb->get_col( $wpdb->prepare( "select * from $table_posts p
								left join $table_term_relationships tr on tr.object_id=p.ID
								where p.post_status='publish' and tr.term_taxonomy_id=$cid
								order by p.post_modified desc
								LIMIT 999",'%d'));
				*/
				//Order by post_modified

				$post_ids = false;
				if(!$post_ids){

					$orderby = 'date';
					$order = 'DESC';
					if($this->categories_settings[$cid]['cat_order']){
						switch($this->categories_settings[$cid]['cat_order']){
							case 'alpha-asc': $orderby = 'title';	$order = 'ASC'; break;
							case 'date-asc': $orderby = 'date';	$order = 'ASC'; break;
							default: $orderby = 'date';	$order = 'DESC'; break;
						}
					}

					$args = array('posts_per_page'   => -1, 'category' => $cid, 'orderby' => $orderby ,	'order' => $order);
					$posts_array = get_posts( $args );

					if($posts_array){
						foreach($posts_array as $po){
							$post_ids[] = $po->ID;

						}
					}
				}
				//$post_ids = false;
				if(!$post_ids){
					//var_dump($this->categories_settings);
					$orderby = 'date';
					$order = 'DESC';
					if($this->categories_settings[$cid]['cat_order']){
						switch($this->categories_settings[$cid]['cat_order']){
							case 'alpha-asc': $orderby = 'title';	$order = 'ASC'; break;
							case 'date-asc': $orderby = 'date';	$order = 'ASC'; break;
							default: $orderby = 'date';	$order = 'DESC'; break;
						}
					}
					$post_ids = $wpdb->get_col( $wpdb->prepare( "select p.ID from $table_posts p
								left join $table_term_relationships tr on tr.object_id=p.ID
								where p.post_status='publish' and tr.term_taxonomy_id=$cid
								order by p.post_$orderby $order
								LIMIT 1999",'%d'));
				}
				if($post_ids){
					$returnarray['error']=$this->nh_ynaa_errorcode(0);
					$returnarray['orderby']= $orderby;
					$returnarray['order']= $order;
					$i=1;
					foreach($post_ids as $pid){
						if(isset($limit) && count($returnarray['items'])>=$limit) break;
						$postmeta = unserialize(get_post_meta( $pid, '_nh_ynaa_meta_keys', true ));
						if($postmeta  && $postmeta['s']!='on') continue;
						$post = wp_get_single_post($pid);
						if($ts < strtotime($post->post_modified)) {
							$ts = strtotime($post->post_modified);
							$returnarray['changes']=1;
						}
						if(!$size) $size = 'medium';
						$post_thumbnail_image[0] = $this->nh_getthumblepic($post->ID,$size);
						
						$images = $this->nh_getthumblepic_allsize($post->ID);
					/*	if ( has_post_thumbnail($post->ID)) {
							$post_thumbnail_image=wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'medium');
						}
						/*elseif($this->categories_settings[$_GET[$this->requesvar['id']]]['img']){
							$post_thumbnail_image=array($this->categories_settings[$_GET[$this->requesvar['id']]]['img']);
						}*/
					/*	else $post_thumbnail_image[0] = '';
						*/
						//echo esc_url($post_thumbnail_image[0]);
						$posttitle = str_replace(array("\\r","\\n","\r", "\n"),'',trim(html_entity_decode(strip_tags(do_shortcode($post->post_title)), ENT_NOQUOTES, 'UTF-8')));
						$returnarray['items'][] = array('pos'=>$i, 'id'=>$post->ID,'title'=>$posttitle,'timestamp'=>strtotime($post->post_modified),'type'=>$post->post_type, 'thumb'=> ($post_thumbnail_image[0]), 'images'=>$images, 'publish_timestamp'=> strtotime($post->post_date), 'post_date'=> strtotime($post->post_date));
						$i++;
					}
					if(!($returnarray['items'])){
						$returnarray['error']=$this->nh_ynaa_errorcode(19);
						$ts = time();
					}

				}
				else{
					$returnarray['error']=$this->nh_ynaa_errorcode(16);
					$ts = time();
				}

				$returnarray['timestamp']=$ts;
			}
			else {
				$returnarray['error']=$this->nh_ynaa_errorcode(15);
			}
		   return array('articles'=>$returnarray);

		} // END private function articles()

		/**
		 * Return Aricle Array
		 */
		private function nh_ynaa_article(){
			if(isset($_GET[$this->requesvar['id']])){
				//backup main post
				global $post, $wpdb;
				$stored_post = clone $post;
				$cid = $_GET[$this->requesvar['id']];
				$returnarray['error']=$this->nh_ynaa_errorcode(0);

				$post1 = get_post( $cid);

				if($_GET[$this->requesvar['ts']]) $ts= $_GET[$this->requesvar['ts']];
				else $ts = 0;

				/*
				* Debug Modus
				*/
				if($this->general_settings['debug'] ==1 && $_GET['debug']==1){
					var_dump($post1);
				}

				if($post1 && $post1->post_status=='publish'){



					$post = $post1;
					setup_postdata( $post1 );

					$returnarray['id'] = get_the_ID();
					$returnarray['error']['postid']=$returnarray['id'] ;
					$returnarray['timestamp'] = strtotime(get_the_date('Y-m-d').' '.get_the_modified_time());
					$returnarray['timestamp'] = strtotime($post->post_modified);
					if($ts<$returnarray['timestamp']) {
						$ts = $returnarray['timestamp'];
						$post_thumbnail_image[0] = $this->nh_getthumblepic($returnarray['id'],'large');


						$returnarray['title'] = str_replace(array("\\r","\\n","\r", "\n"),'',trim(html_entity_decode(strip_tags(do_shortcode($post->post_title)), ENT_NOQUOTES, 'UTF-8')));

				            if(isset($this->general_settings['showFeatureImageInPost'])) {
				            $returnarray['showFeatureImageInPost']=(int)$this->general_settings['showFeatureImageInPost'];
				            //$returnarray['order']=1;
				          }

						//$returnarray['']['title']
						//$content = '<html><head><title>'.get_bloginfo('name').'</title><body><div id="nh_ynaa__app_content">'.$post->post_content.'</div></body></html>';
						/*$content = $post->post_content;
						$content =do_shortcode( $content );
						//$content = '<html><head><meta charset="utf-8" /><title>'.get_bloginfo('name').'</title><style type="text/css">'.$this->general_settings['css'].';}</style></head><body><div id="nh_ynaa__app_content">'.$content.'</div></body></html>';
						//$content = '<head><meta charset="utf-8" /></head><body><div id="nh_ynaa__app_content">'.$content.'</div></body>';
						$post_content = $post->post_content;
						$returnarray['uma']['sql']="SELECT post_content FROM $wpdb->posts WHERE ID=".$returnarray['id']." LIMIT 1 ";
						$returnarray['uma']['post_content']= $post_content;
						$post_content =nl2br($post_content);
						//$post_content = str_replace("\r\n\r\n",'<br \>',$post_content);
						//$post_content = preg_replace('/[\x00-\x1F\x80-\xFF]/', '',$post_content);
						$returnarray['uma']['post_content_replace']= $post_content;


						$content = preg_replace('/[\x00-\x1F\x80-\xFF]/', '',$content);
						$this->general_settings['css'] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '',$this->general_settings['css']);

						if(strpos('<html><head><meta charset="utf-8"></head>')) $content = str_replace('<html><head><meta charset="utf-8"></head>','<html><head><meta charset="utf-8"><style type="text/css">'.($this->general_settings['css']).' body{color:'.$this->general_settings['ct'].';}</style></head>',$content);
						elseif(strpos('<html>'))
						$content = str_replace('<html>','<html><head><meta charset="utf-8"><style type="text/css">'.$this->general_settings['css'].' body{color:'.$this->general_settings['ct'].';}</style></head>',$content);
						else $content = '<!doctype html><html><head><meta charset="utf-8"><style type="text/css">'.$this->general_settings['css'].' body{color:'.$this->general_settings['ct'].';}</style></head><body>'.$content.'</body></html>';

						//$content = '<style type="text/css">'.preg_replace('/[\x00-\x1F\x80-\xFF]/', '',$this->general_settings['css']).' body{color:'.$this->general_settings['ct'].';}</style>'.preg_replace('/[\x00-\x1F\x80-\xFF]/', '',$content).'<style type="text/css">'.preg_replace('/[\x00-\x1F\x80-\xFF]/', '',$this->general_settings['css']).' body{color:'.$this->general_settings['ct'].';}</style>';


						//echo $content;
						//$content = $post->post_content;*/
						//$returnarray['content'] = '<html><head><style type="text/css">'.$this->general_settings['css'].';}</style></head><body>'.$content.'</body></html>';
						if($this->general_settings['debug'] ==1 && $_GET['debug']==1){
							$returnarray['debug']['min-img-size-for-resize'] = $this->general_settings['min-img-size-for-resize'];
						}
						if(isset($_GET[$this->requesvar['av']]) && (($_GET[$this->requesvar['pl']]=='ios' && $_GET[$this->requesvar['av']]>=1.7) || ($_GET[$this->requesvar['pl']]=='android' && $_GET[$this->requesvar['av']]>1.3))){
						}
						else{

						$queried_post = get_post($returnarray['id']);
						$content = $queried_post->post_content;
						if($this->general_settings['debug'] ==1 && $_GET['debug']==1){
							$returnarray['debug']['post_content']=$content;
						}
						$content = apply_filters('the_content', $content);
						if($this->general_settings['debug'] ==1 && $_GET['debug']==1){
							$returnarray['debug']['apply_filters(post_content)']=$content;
						}
						$content = str_replace(']]>', ']]&gt;', $content);
						$content = str_replace("\r\n",'\n',$content);
						//$content = utf8_encode($content);

						$content = preg_replace('/[\x00-\x1F\x80-\x9F]/u', '',$content);
						$search = array('src="//', "src='//");
						$replace = array('src="http://', "src='http://");
						$content = str_replace($search, $replace,$content);
						if($this->general_settings['debug'] ==1 && $_GET['debug']==1){
							$returnarray['debug']['strrepale(]]>\r\n/[\x00-\x1F\x80-\x9F]/u,post_content)']=$content;
						}
						//$returnarray['uma']['post_content']= $content;
						//$returnarray['uma']['post_content_htmlentities']= htmlentities($content,null,"UTF-8");

						//Für nicht utf8
						$content = $this->nh_ynaa_get_appcontent($content);
						if($this->general_settings['debug'] ==1 && $_GET['debug']==1){
							$returnarray['debug']['nh_ynaa_get_appcontent(post_content)']=$content;
						}
						//$returnarray['uma']['post_content_after_nh_ynaa_get_appcontent']= $content;
						//$content = preg_replace('/[\x00-\x1F\x80-\xFF]/', '',$content);
						if($this->css_settings['css'])$this->general_settings['css'] = $this->css_settings['css'];
						$this->general_settings['css'] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '',$this->general_settings['css']);
						//$content = (str_replace('<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">','<!doctype html>',$content));
						$active_plugins = get_option('active_plugins');
						if(strpos($content,'<html><head><meta charset="utf-8"></head>')){
							$content = str_replace('<html><head><meta charset="utf-8"></head>','<html data-html="html1"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><link href="http://necolas.github.io/normalize.css/3.0.1/normalize.css" rel="stylesheet" type="text/css"><style type="text/css">'.$this->general_settings['css'].' body{color:'.$this->general_settings['ct'].';}</style></head>',$content);
						}
						elseif(strpos($content,'<html>')) {

							if(get_bloginfo('url') == 'http://www.automotiveit.eu' || get_bloginfo('url') == 'http://automotiveit.eu' || get_bloginfo('url') == 'http://www.bailazu.de' || (is_array($active_plugins) && in_array('wpseo/wpseo.php',$active_plugins)) ){
								$content = str_replace('<html>','<html data-html="html2a"><head><meta name="viewport" content="width=device-width"><link href="http://necolas.github.io/normalize.css/3.0.1/normalize.css" rel="stylesheet" type="text/css"><style type="text/css">'.$this->general_settings['css'].' body{color:'.$this->general_settings['ct'].';}</style></head>',$content);
							}
							else {
								//$content = str_replace('<html>','<html data-html="html2"><head><meta name="viewport" content="width=device-width"><link href="http://necolas.github.io/normalize.css/3.0.1/normalize.css" rel="stylesheet" type="text/css"><style type="text/css">'.$this->general_settings['css'].' body{color:'.$this->general_settings['ct'].';}</style></head>',$content);
								$content = str_replace('<html>','<html data-html="html2b"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><link href="http://necolas.github.io/normalize.css/3.0.1/normalize.css" rel="stylesheet" type="text/css"><style type="text/css">'.$this->general_settings['css'].' body{color:'.$this->general_settings['ct'].';}</style></head>',$content);
							}
						}
						else {
							$content = '<!doctype html><html data-html="html3"><meta charset="utf-8"><meta name="viewport" content="width=device-width"><link href="http://necolas.github.io/normalize.css/3.0.1/normalize.css" rel="stylesheet" type="text/css"><style type="text/css">'.$this->general_settings['css'].' body{color:'.$this->general_settings['ct'].';}</style></head><body>'.$content.'</body></html>';
						}

						$content = str_replace('<script type="text/javascript">aop_around(document.body, \'appendChild\'); aop_around(document.body, \'insertBefore\'); </script>','',$content);
						$content = str_replace(array("<body>\r\n","<body>\r","<body>\n"),'<body>',$content);

						//$returnarray['uma']['post_content_0']= $content;
						$returnarray['content']=$content;
						}
						//$returnarray['uma']['content']=$content;
						$returnarray['changes']=1;
						$returnarray['type']=get_post_type();
						$returnarray['format']='html';
						$returnarray['post_date'] = strtotime($post->post_date);

						$returnarray['sharelink']= esc_url( get_permalink());
						$returnarray['comment_status'] = $post->comment_status;
						$args = array(
							'post_id' => $returnarray['id'], // use post_id, not post_ID
							'status' => 'approve',
							'count' => true //return only the count
						);
						$comments_count = get_comments($args);
						$returnarray['comments_count']=$comments_count;
						$categories = get_the_category($returnarray['id']);
						if($categories){
							foreach($categories as $category) {
								$returnarray['catid'] = $category->term_id;
								break;
							}
						}
						/*if(!$post_thumbnail_image[0] && $returnarray['catid'] && $this->categories_settings[$returnarray['catid']['img']])
						$post_thumbnail_image[0] = $this->categories_settings[$returnarray['catid']['img']];
						*/
						$returnarray['img']= array('src'=>$post_thumbnail_image[0]);
						$returnarray['thumb'][]= $post_thumbnail_image[0];

						//karte temp
						$returnarray['location']=0;
						if($this->general_settings['location']){


							$postmeta_location = (get_post_meta( $returnarray['id'], '_nh_ynaa_location', true));
							$postmeta_location_stamp = (get_post_meta( $returnarray['id'], 'nh_location_update_stamp', true));
							$nh_ynaa_location_id = (get_post_meta($returnarray['id'], 'nh_ynaa_location_id', true));
							if($postmeta_location){
								$postmeta_location = unserialize($postmeta_location);
								if(!is_null($postmeta_location['location_latitude'])){
									$returnarray['location']=1;
									if(!$postmeta_location['location_pintype']) $postmeta_location['location_pintype'] = 'red';
									$returnarray['location_info']=array("title"=>$postmeta_location['location_name'],"lat"=>$postmeta_location['location_latitude'],"lng"=>$postmeta_location['location_longitude'], "address"=>$postmeta_location['location_address'],  "id"=>$nh_ynaa_location_id, 'ts'=>$postmeta_location_stamp, 'cat_id'=>$returnarray['catid'], 'pintype'=>$postmeta_location['location_pintype']);
								}
							}
						}
						if($post->post_type=='location' && $this->general_settings['eventplugin'] && $this->general_settings['eventplugin']==1){

							$table = $wpdb->prefix;
							$loc = $wpdb->get_row( $wpdb->prepare( "
									select  l.location_name, l.location_address, l.location_town, l.location_state, l.location_postcode, l.location_region, l.location_country, l.location_latitude, l.location_longitude
									from ".$table."em_locations l
									WHERE 	post_id = %d", $post->ID));
								if($loc){
								$returnarray['location']=1;
								$location_info['title']= $returnarray['title'];
								$location_info['lat'] = (float)$loc->location_latitude;
								$location_info['lng'] = (float)$loc->location_longitude;
								$location_info['address'] = $loc->location_address;
								$location_info['id'] = $post->ID;
								$location_info['ts'] = $post->post_modified;
								$location_info['cat_id'] = 0;
								$location_info['pintype'] = 'red';
								$returnarray['location_info']= $location_info;
								}
						}



					}
					else {
						$returnarray['changes']=0;
					}
					wp_reset_postdata();

				}
				else {
					$returnarray['error']=$this->nh_ynaa_errorcode(17);

					$returnarray['id'] = $_GET[$this->requesvar['id']];
				}

				if($stored_post) $post = clone $stored_post;

			}
			else {
				$returnarray['error']=$this->nh_ynaa_errorcode(15);
			}
			//var_dump($returnarray['content']);
			//exit(0);
			//unset($returnarray['content']);
			return (array('article'=>$returnarray));


		} // END private function article()


		/**
		* Return $content HTML
		*/
		private function nh_ynaa_content(){
			$content = '';
			if($_GET[$this->requesvar['id']]){
				$queried_post = get_post($_GET[$this->requesvar['id']]);
				$content = $queried_post->post_content;

				$hook='the_content';
				if(get_bloginfo('url')=='http://lovemypetbook.com'){
					remove_all_filters( $hook);
				}
				if($this->general_settings['debug'] ==1 && $_GET['debug']==1 && $_GET['filter']=='the_content'){
				 	/*global $wp_filter;

					print '<pre>';
					print_r( $wp_filter[$hook] );
					print '</pre>';*/
				}
				$content = apply_filters('the_content', $content);
				//echo '1:'.$content;
				$content = str_replace(']]>', ']]&gt;', $content);
				$search = array("\r\n", 'src="//', "src='//");
				$replace = array("\n",'src="http://', "src='http://");
				$content = str_replace($search, $replace,$content);
				if(get_bloginfo('url')=='http://lovemypetbook.com'){
					$content = str_replace("\n",'',$content);
				}
				//echo '2:'.$content;
				//$content = preg_replace('/[\x00-\x1F\x80-\xFF]/', '',$content);
				$content = preg_replace('/[\x00-\x1F\x80-\x9F]/u', '',$content);
       $content = str_replace(array("\n","\r", "\t", chr(10),chr(13),'\n'),'',$content);

				//echo '4:'.$content;
				$content = $this->nh_ynaa_get_appcontent($content);
				//echo '5:'.$content;
				$plugins_url = plugins_url();
				$css = '
            @import url("'.($plugins_url).'/yournewsapp/fonts/stylesheet.css");
          body {
            font-family:"Roboto Condensed",Roboto, Helvetica, sans-serif;
            text-align:justify;
            margin:0;
            padding:0;
          }
          /*img:not(.nh-img-space) {
            width: 100% ;
            margin-bottom: 10px;
            height: auto ;
          }*/
          img.nh-img-space{
            background: url("'.($plugins_url).'/yournewsapp/img/2-1.gif") no-repeat center;
            background-size: cover;
          }
          img.wp-smiley, img.nh-no-resize {
            width:auto;
          }

          ul, ol{
            margin:0 0 0 20px;
          }
          iframe {
              width:100% !important;
          }
          img {
            width:100%;
			height: auto;
          }

          ';
				if($this->css_settings['css']) $this->general_settings['css'] = $this->css_settings['css'];
				$this->general_settings['css'] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '',$this->general_settings['css']);
				$this->general_settings['css'] = str_replace('../fonts/stylesheet.css',plugins_url( 'fonts/stylesheet.css' , __FILE__ ),$this->general_settings['css']);
				$css ='<style type="text/css">'.$css.$this->general_settings['css'].'body{color:'.$this->general_settings['ct'].'}</style>';

				if(strpos($content,'<html><head><meta charset="utf-8"></head>')){
							$content = str_replace('<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /></head>','<html data-html="html1"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><link href="http://necolas.github.io/normalize.css/3.0.1/normalize.css" rel="stylesheet" type="text/css">'.$css.'</head>',$content);
						}
				elseif(strpos($content,'<html>')) {
							//if(get_bloginfo('url') == 'http://www.automotiveit.eu' || get_bloginfo('url') == 'http://automotiveit.eu'  || (is_array($active_plugins) && in_array('wpseo/wpseo.php',$active_plugins)) ){
								//$content = str_replace('<html>','<html data-html="html2a"><head><meta name="viewport" content="width=device-width"><link href="http://necolas.github.io/normalize.css/3.0.1/normalize.css" rel="stylesheet" type="text/css"><style type="text/css">'.$this->general_settings['css'].' body{color:'.$this->general_settings['ct'].';}</style></head>',$content);
							//}
							//elseif( get_bloginfo('url') == 'http://www.bailazu.de'){
								//$content = utf8_encode(html_entity_decode($content));
								//$returnarray['uma']['utf8_encode_html_entity_decode_content']= $content;

								//$content = str_replace('<html>','<html data-html="html2a"><head><meta name="viewport" content="width=device-width"><link href="http://necolas.github.io/normalize.css/3.0.1/normalize.css" rel="stylesheet" type="text/css"><style type="text/css">'.$this->general_settings['css'].' body{color:'.$this->general_settings['ct'].';}</style></head>',$content);
							//}
							//else {
								//$content = str_replace('<html>','<html data-html="html2"><head><meta name="viewport" content="width=device-width"><link href="http://necolas.github.io/normalize.css/3.0.1/normalize.css" rel="stylesheet" type="text/css"><style type="text/css">'.$this->general_settings['css'].' body{color:'.$this->general_settings['ct'].';}</style></head>',$content);
								$content = str_replace('<html>','<html data-html="html2b"><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /><meta name="viewport" content="width=device-width"><link href="http://necolas.github.io/normalize.css/3.0.1/normalize.css" rel="stylesheet" type="text/css">'.$css.'</head>',$content);

							//}
						}
						else {
							$content = '<!doctype html><html data-html="html3"><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /><meta name="viewport" content="width=device-width"><link href="http://necolas.github.io/normalize.css/3.0.1/normalize.css" rel="stylesheet" type="text/css">'.$css.'</head><body>'.$content.'</body></html>';
						}
			}
			return $content;
		}

		/**
		 * Return Social
		*/
		private function nh_ynaa_social(){
			$returnarray['error']=$this->nh_ynaa_errorcode(24);
			if($_GET[$this->requesvar['n']]=='fb'){
				if($_GET[$this->requesvar['limit']]) $limit= $_GET[$this->requesvar['limit']];
				else $limit=50;
				$fb= $this->nh_ynaa_get_fbcontent($limit);
				if($fb){
					if(isset($fb['error']) && is_array($fb['error'])){
						$returnarray['error'] = $fb['error'];

					}
					else {
						$returnarray['error']=$this->nh_ynaa_errorcode(0);
						$returnarray['fb']=json_decode($fb);
					}
				}

			}

			return (array('social'=>$returnarray));
		} // END Funktion nh_ynaa_social

		/**
		 * Return comments
		*/
		private function nh_ynaa_comments(){
			$returnarray['error']=$this->nh_ynaa_errorcode(0);
			$returnarray['changes']=1;
			if($_GET[$this->requesvar['ts']])$returnarray['ts']=$_GET[$this->requesvar['ts']];
			else $returnarray['ts']=0;
			if($_GET[$this->requesvar['id']]){
				global $wpdb;
				$table_comments = $wpdb->prefix . "comments";
				//$table_comments_meta = $wpdb->prefix . "comments_meta";

				if($_GET[$this->requesvar['action']]=='add' ){
					if(!$_REQUEST[$this->requesvar['key']] || (!$_REQUEST[$this->requesvar['comment']] || trim($_REQUEST[$this->requesvar['comment']]) =='') || !$_REQUEST[$this->requesvar['name']] || !$_REQUEST[$this->requesvar['email']]  ) $returnarray['error']=$this->nh_ynaa_errorcode(30);
					elseif(!is_email($_REQUEST[$this->requesvar['email']])){
						$returnarray['error']=$this->nh_ynaa_errorcode(31);
					}
					else{
						$commentkey = $wpdb->get_var( "SELECT meta_id FROM $wpdb->commentmeta WHERE meta_key = 'ckey' AND meta_value = '".trim($_REQUEST[$this->requesvar['key']])."' LIMIT 1" );
						if($commentkey) $returnarray['error']=$this->nh_ynaa_errorcode(32);
						else {
							$ts = time();
							$ts = current_time('timestamp');
							$comment_parent = 0;
							//$wpdb->insert('temp',array('text'=>serialize($_REQUEST)), array('%s'));
							if($_REQUEST[$this->requesvar['comment_id']]) $comment_parent = $_REQUEST[$this->requesvar['comment_id']];
							$commentdata = array(
								'comment_post_ID' => $_GET[$this->requesvar['id']],
								 'comment_author' => urldecode(trim($_REQUEST[$this->requesvar['name']])),
								 'comment_author_email' =>trim($_REQUEST[$this->requesvar['email']]),
								 'comment_author_url' => 'http://',
								 'comment_content' => urldecode(trim($_REQUEST[$this->requesvar['comment']])),
								 'comment_type' => '',
								'comment_parent' => $comment_parent,
								'user_id' => 0,
								'comment_author_IP' => $_SERVER['REMOTE_ADDR'],
								'comment_agent' => $_SERVER['HTTP_USER_AGENT'],
								'comment_date' => date('Y-m-d H:i:s',$ts),
								'comment_approved' => 0
							);
							if($newcommentid = wp_insert_comment($commentdata)){
								add_comment_meta( $newcommentid, 'ckey', trim($_REQUEST[$this->requesvar['key']]) );
								$returnarray['error']=$this->nh_ynaa_errorcode(0);
								$returnarray['ts']=$ts;
								$returnarray['comment_id']=$newcommentid;
								$returnarray['changes']=1;
								$returnarray['status']=__('Comment is in review', 'nh-ynaa');
							}
							else $returnarray['error']=$this->nh_ynaa_errorcode(31);
						}
					}
				}
				else{
					$post_7 = get_post($_GET[$this->requesvar['id']]);
					if($post_7->comment_status == 'open'){
						$returnarray['comment_status']=$post_7->comment_status;
						$args = array(
							'post_id' => $_GET[$this->requesvar['id']], // use post_id, not post_ID
							'status' => 'approve',
							'count' => true //return only the count
						);
						$comments_count = get_comments($args);
						$returnarray['comments_count']=$comments_count;
						$comment = array();
						$returnarray['items'] = array();
						if($comments_count>0){

							$args = array(
								'post_id' => $_GET[$this->requesvar['id']], // use post_id, not post_ID
								'status' => 'approve',
								'$order' => 'ASC'

							);

							$comments = $wpdb->get_results( "SELECT *   FROM $wpdb->comments WHERE comment_approved=1 AND comment_parent=0 AND comment_post_id=".$_GET[$this->requesvar['id']]."  ORDER BY comment_date_gmt DESC ", ARRAY_A  );
							if($comments){
								foreach($comments as $com){
									$parrent_com[$com['comment_ID']][] = $com;


								}
							}

							$comments = $wpdb->get_results( "SELECT *   FROM $wpdb->comments WHERE comment_approved=1 AND comment_parent!=0 AND comment_post_id=".$_GET[$this->requesvar['id']]."   ORDER BY comment_date_gmt ASC ", ARRAY_A  );
							if($comments){
								foreach($comments as $com){
									if(array_key_exists($com['comment_parent'],$parrent_com))
										$parrent_com[$com['comment_parent']][] = $com;
								}
							}

							if($parrent_com){
								$pos = 0;

								foreach($parrent_com as $ar){
									//var_dump($ar);
									$temparray=array();
									$pos++;
									$temparray['pos']=$pos;

									$temparray['id']=$ar[0]['comment_ID'];
									$temparray['text']=html_entity_decode($ar[0]['comment_content']);
									$temparray['timestamp']=strtotime($ar[0]['comment_date']);

									if($temparray['timestamp']>$returnarray['ts']) {
										$returnarray['ts'] = $temparray['timestamp'];
										$returnarray['changes']=1;
									}

									$temparray['datum']=date('d.m.Y, H:i',$temparray['timestamp']);

									$temparray['author']['name']= $ar[0]['comment_author'];
									$temparray['author']['id']=$ar[0]['user_id'];
									$temparray['author']['email'] = $ar[0]['comment_author_email'];
									$temparray['author']['img']=get_avatar($ar[0]['comment_author_email'],32);
                  if($temparray['author']['img']){
									 $temparray['author']['img'] = substr($temparray['author']['img'],strpos($temparray['author']['img'],'src=')+5);
									 $temparray['author']['img'] = substr($temparray['author']['img'],0,strpos($temparray['author']['img'],'\''));
                  }
                  else $temparray['author']['img']='';
									if(count($ar)>1){
										$pos2 = 0;
										//$temparray2 = array();
										$temp = array();
										foreach($ar as $k=>$ar2){
											if($k==0) continue;
											 $pos2++;
											 $temp['pos']=$pos2;
											 $temp['id'] = $ar2['comment_ID'];
											 $temp['parrent_id'] = $ar[0]['comment_ID'];
											 $temp['text'] =html_entity_decode($ar2['comment_content']);
											 $temp['timestamp'] =strtotime($ar2['comment_date']);
											 if($temp['timestamp']>$returnarray['ts']) {
												 $returnarray['ts'] = $temp['timestamp'];
												 $returnarray['changes']=1;
											 }
											 $temp['datum'] = date('d.m.Y, H:i',$temp['timestamp']);
											 $temp['author']['name']= $ar2['comment_author'];
											$temp['author']['id']=$ar2['user_id'];

											$temp['author']['email'] = $ar2['comment_author_email'];
											$temp['author']['img']=get_avatar($ar2['comment_author_email'],30);
											$temp['author']['img'] = substr($temp['author']['img'],strpos($temp['author']['img'],'src=')+5);
											 $temp['author']['img'] = substr($temp['author']['img'],0,strpos($temp['author']['img'],'\''));
											 $temparray['subitems'][] =$temp;

										}
									}
									$returnarray['items'][]=$temparray;

								}
							}

							if($returnarray['changes']!=1){
								unset($returnarray['items']);
							}

						}
					}
					else {
						$returnarray['error']=$this->nh_ynaa_errorcode(29);
					}
				}

			}

			else {
				$returnarray['error']=$this->nh_ynaa_errorcode(15);
			}

			return (array('comments'=>$returnarray));
		} // END Funktion nh_ynaa_comments

		/**
		 * Return iBEacon Settings
		*/
		private function nh_ynaa_ibeacon(){

			//$returnarray['error']=$this->nh_ynaa_errorcode(0);
			if(!$this->push_settings['uuid']){
				$returnarray['error']=$this->nh_ynaa_errorcode(33);
			}
			else{
				$returnarray['uuid']=$this->push_settings['uuid'];
				if($this->push_settings['welcome']) $returnarray['welcome']=$this->push_settings['welcome'];
				if($this->push_settings['silent']) $returnarray['silent']=$this->push_settings['silent'];
				/*$returnarray['uuid'] ='B9407F30-F5F8-466E-AFF9-25556B57FE6D' ;
				$returnarray['silent'] =60 ;*/
				$returnarray['identifier'] ='Beacon1' ;
				//$returnarray['welcome'] ='Willkommen bei der Frankfurter Buchmesse.' ;
				$returnarray['content'][] =array('major'=>50658, 'minor'=>42436, 'silentInterval'=>60, 'proximity'=>'CLProximityNear', 'message'=>'Willkommen bei Oettinger.', 'contentArray'=>array(7,44 )) ;
				$returnarray['content'][] =array('major'=>20535, 'minor'=>33212, 'silentInterval'=>60, 'proximity'=>'CLProximityNear', 'message'=>'Willkommen bei Dressler.', 'contentArray'=>array(7,37)) ;
			}
			return (array('ibeacon'=>$returnarray));
		}
		// END private function nh_ynaa_ibeacon

		/**
		 * Return Locations
		*/
		private function nh_ynaa_locations($limit=0){

			$returnarray['error']=$this->nh_ynaa_errorcode(0);
			$returnarray['changes']=1;
			$returnarray['ts'] = 0;
			if($_GET[$this->requesvar['ts']])	$returnarray['ts']=$_GET[$this->requesvar['ts']];

			if(!$this->general_settings['location']){
				$returnarray['error']=$this->nh_ynaa_errorcode(34);
			}
			else{

				$lo_args = array(
					'meta_query' => array(
							array(
								'key' => 'nh_ynaa_location_id'/*,
								'value' => '',
								'compare' => '!='*/
							)
						),
					'posts_per_page'=>-1
				);

				$lo_query = new WP_Query( $lo_args );
				while ( $lo_query->have_posts() ) : $lo_query->the_post();
					$id = get_the_ID();
					//var_dump($lo_query->post);
					//echo '<hr>';
					$postmeta = (get_post_meta( $id, '_nh_ynaa_location', true));

					if($postmeta){
						$nh_location_update_stamp = (get_post_meta( $id, 'nh_location_update_stamp', true));

						if(strtotime($nh_location_update_stamp)>$returnarray['ts']) {

							$returnarray['ts'] = strtotime($nh_location_update_stamp);
						}
						$postmeta = unserialize($postmeta);
						$cats = get_the_category();
						//var_dump($cats);
						if($cats) {
							$cat_id = $cats[0]->term_id;
							if(!$postmeta['location_pintype']) $postmeta['location_pintype']='red';
							$returnarray['items'][]=
								array("title"=>$postmeta['location_name'],
								"lat"=>$postmeta['location_latitude'],
								"lng"=>$postmeta['location_longitude'],
								"address"=>$postmeta['location_address'],
								'pintype'=>$postmeta['location_pintype'],
								"id"=> $id,
								'posts'=>array(array('post_id'=>$id, 'type'=>$lo_query->post->post_type, 'cat_id'=>$cat_id))

							);
						}
						//var_dump($returnarray['items']);
						//echo '<hr>';

						if($limit==1) break;
					}

				endwhile;
                wp_reset_postdata();
				//var_dump($lo_query);

				$homepresets = $this->nh_ynaa_homepresets();
				//var_dump($homepresets);

				$returnarray['img'] = '';
				$returnarray['title'] = __('Map','nh-ynaa');
				if($homepresets ["homepresets"]['items']){
					foreach($homepresets ["homepresets"]['items'] as $item){
							if($item['cat_id'] != -98) continue;
							else {
								if($item['img'])$returnarray['img'] = $item['img'];
								$returnarray['title'] = $item['title'];
								break;
							}

					}
				}

			}
			return (array('locations'=>$returnarray));
		}
		// END private function nh_ynaa_ibeacon

		/**
		* Return iframe with Emulator
		*/
		private function nh_ynaa_simulator(){
		  ?>
      <div id="nh-simulator">
        <h3><?php _e('How to use the Blappsta PREVIEW app','nh-ynaa'); ?></h3>

        <div>
          <iframe width="400" height="223" src="//www.youtube-nocookie.com/embed/Ng6xlcZr7Uw" frameborder="0" allowfullscreen></iframe>
        </div>
       </div>
		  <?php
		  /*
		  ?>
		  <div id="nh-simulator">
		    <h3><?php _e('Blappsta Preview QR-Code','nh-ynaa'); ?></h3>
		    <p><?php _e('Scan this QR-Code with our Blappsta Preview App to see your app in action.', 'nh-ynaa');?></p>
		    <div>
		      <?php echo '<a href="https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=yba://?url='.get_site_url().'&choe=UTF-8"><img width="200px" src="https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl='.get_site_url().'&choe=UTF-8" alt="'.get_site_url().'" title="'.get_site_url().'" /></a>';
          ?>
		    </div>
		   </div>
		  <?php
      */
      /*
			?>
            <div id="nh-simulator">
            <h3><?php _e('Emulator','nh-ynaa'); ?></h3>
            <iframe src="https://app.io/p6Kgfi?params=%7B%22customURLString%22%3A%22<?php echo(get_site_url()); ?>%22%7D&#038;autoplay=true&#038;orientation=portrait&#038;device=iphone5" height="607px" width="291px" frameborder="0" allowtransparency="true" scrolling="no"></iframe><!--
            <img src="<?php echo plugins_url( 'img/simulator_default.jpg' , __FILE__ ); ?>" alt="" />--></div>

            <?php
       */
		}// END private function nh_ynaa_simulator


		/**
		 * Return  Settings for YNA Admin page
		*/
		private function nh_ynaa_yna_settings(){

			$returnarray['error']=$this->nh_ynaa_errorcode(0);
			$returnarray['bloginfo']['name']=get_bloginfo('name');
			$returnarray['bloginfo']['language']=get_bloginfo('language');
			return (array('yna_settings'=>$returnarray));
		}
		// END private function nh_ynaa_yna_settings



		/**
		 * Return Event Array
		 */
		private function nh_ynaa_events($lim=0){

				$weekdays = array(__('Sunday'), __('Monday'), __('Tuesday'), __('Wednesday'), __('Thursday'), __('Friday'), __('Saturday'));
				//WP Query
				global $wpdb;
				$table_em_events = $wpdb->prefix . "em_events";
				$table_em_locations = $wpdb->prefix . "em_locations";
				if($lim ) {
					$limit = " LIMIT $lim ";
					$limit2 = $lim;
				}
				elseif($_GET[$this->requesvar['limit']] ) {
					$limit = " LIMIT ".$_GET[$this->requesvar['limit']]." ";
					$limit2 = $_GET[$this->requesvar['limit']];
				}
				else $limit = " LIMIT 9999 ";

				$returnarray['changes']=0;
				//PostID
				//If Post ID Check if is ist the newest Post and if hat changes
				if(isset($_GET[$this->requesvar['post_id']]) && isset($_GET[$this->requesvar['post_ts']])){
					$break = false;
					$latest_cat_post = new WP_Query( array('posts_per_page' => 1, 'post_type' => 'event'));
					//var_dump($latest_cat_post);
					if( $latest_cat_post->have_posts() ) : while( $latest_cat_post->have_posts() ) : $latest_cat_post->the_post();
						if($latest_cat_post->post->ID == $_GET[$this->requesvar['post_id']]){
							$break = true;
							$i = 1;


							if(strtotime($latest_cat_post->post->post_modified)>$_GET[$this->requesvar['post_ts']]){
								$ts = strtotime($latest_cat_post->post->post_modified);
								$returnarray['changes']=1;

								if ( has_post_thumbnail()) {
									$post_thumbnail_image=wp_get_attachment_image_src(get_post_thumbnail_id($post_id), 'original');
								}
								else {
									$post_thumbnail_image="";
								}
								$event = $wpdb->get_row( $wpdb->prepare( "
									select event_id, e.post_id, event_slug, event_status, event_name, event_start_time, event_end_time, event_all_day, event_start_date, event_end_date, e.post_content, e.location_id,event_category_id, event_attributes, event_date_modified,
									l.location_name, l.location_address, l.location_town, l.location_state, l.location_postcode, l.location_region, l.location_country, l.location_latitude, l.location_longitude
									from $table_em_events e
									left join $table_em_locations l on l.location_id=e.location_id
									WHERE e.post_id=".$latest_cat_post->post->ID."
									", array('%d', '$d', '%s', '%d', '%s', '%s', '%s', '%d','%d', '%d', '%s', '%d', '%d', '%d', '%s')));
								if($event) {
									//$returnarray['uma']['start_ts_gmt']=get_gmt_from_date($event->event_start_date.' '.$event->event_start_time);
									$start_ts = strtotime($event->event_start_date.' '.$event->event_start_time);
									$end_ts = strtotime($event->event_end_date.' '.$event->event_end_time);
									if(!$event->location_latitude || $event->location_latitude== null || $event->location_latitude=='null' || $event->location_latitude=='0.000000') $event->location_latitude =  0;
									else $event->location_latitude = (float) $event->location_latitude ;
									if(!$event->location_longitude || $event->location_longitude== null || $event->location_longitude=='null' || $event->location_longitude=='0.000000') $event->location_longitude =  0;
									else $event->location_longitude = (float) $event->location_longitude ;

									$latest_cat_post->post->post_title = str_replace(array("\\r","\\n","\r", "\n"),'',trim(html_entity_decode(strip_tags(do_shortcode($latest_cat_post->post->post_title)), ENT_NOQUOTES, 'UTF-8')));
									$returnarray['items'][] = array(
										'uma'=>array('start_ts_gmt',get_gmt_from_date($event->event_start_date.' '.$event->event_start_time), 'test'=>1),
										'pos'=>$i,
										'id'=>$latest_cat_post->post->ID,
										'title'=>($latest_cat_post->post->post_title),
										'timestamp'=>strtotime($latest_cat_post->post->post_modified),
										'post_date'=>strtotime($latest_cat_post->post->post_date),
										'type'=>$latest_cat_post->post->post_type,
										'thumb'=> ($post_thumbnail_image[0]),
										'publish_timestamp'=> strtotime($latest_cat_post->post->post_date),
										'event_id'=>$event->event_id,
										'subtitle' => '',
										'start_date' => $event->event_start_date,
										'end_date' => $event->event_end_date,
										'start_time' => $event->event_start_time,
										'end_time' => $event->event_end_time,
										'start_ts' => $start_ts,
										'end_ts' => $end_ts,
										'day' =>  $event->event_all_day,
										'swd' => $weekdays[date('w',$start_ts)],
										'ewd' => $weekdays[date('w',$end_ts)],
										//$returnarray['start_time'] .= (__(' Uhr'));
										//$returnarray['end_time'] .= (__(' Uhr'));
										//'thumb' => $post_thumbnail_image[0],
										'img' => $post_thumbnail_image[0],
										'location' => $event->location_name,
										'plz' => $event->location_postcode,
										'city' => $event->location_town,
										'country' => $event->location_country,
										'zip' => $event->location_postcode,
										'address' => $event->location_address,
										'street' => $event->location_address,
										'region' => $event->location_region,
										'province' => $event->location_region,
										'extra' => '',
										'lat' => $event->location_latitude,
										'lng' => $event->location_longitude,
										'short_text' => $post->post_excerpt,
										'sharelink'=> esc_url( get_permalink($post->ID))


							  		 );
								}
								else {
									$break = true;
									$returnarray['error']=$this->nh_ynaa_errorcode(22);
									$ts = time();
									$returnarray['items'][] = array();
								}

							}
							else {
								$ts = $_GET[$this->requesvar['post_ts']];
							}
						}
						else {
							break;
						}

					endwhile;
					else:
						$break = true;
						$returnarray['error']=$this->nh_ynaa_errorcode(22);
						$ts = time();
						$returnarray['items'][] = array();
					endif;
					if($break) {
						$returnarray['timestamp']=$ts;
						return array('events'=>$returnarray);
					}
				}

				//Timestamp
				if($_GET[$this->requesvar['ts']]) {
					$ts= $_GET[$this->requesvar['ts']];
					$ts_string = date('Y-m-d H:i:s',$ts);
				}
				else {
					$ts = 0;
					$ts_string = date('0000-00-00 00:00:00');
				}



				//Order by post_date
				$events = $wpdb->get_results( $wpdb->prepare( "
							select event_id, e.post_id, event_slug, event_status, event_name, event_start_time, event_end_time, event_all_day, event_start_date, event_end_date, e.post_content, e.location_id,event_category_id, event_attributes, event_date_modified,
							l.location_name, l.location_address, l.location_town, l.location_state, l.location_postcode, l.location_region, l.location_country, l.location_latitude, l.location_longitude
							from $table_em_events e
							left join $table_em_locations l on l.location_id=e.location_id
							WHERE e.event_status=1 AND e.recurrence=0 AND (e.event_start_date >='".date('Y-m-d')."' OR e.event_end_date>='".date('Y-m-d')."')
							ORDER BY e.event_start_date, e.event_start_time
							$limit", array('%d', '$d', '%s', '%d', '%s', '%s', '%s', '%d','%d', '%d', '%s', '%d', '%d', '%d', '%s')));

				$i=1;
				if($events){
					foreach($events as $event){
						if(isset($limit2) && count($returnarray['items'])>=$limit2) break;
						$postmeta = unserialize(get_post_meta( $event->post_id, '_nh_ynaa_meta_keys', true ));
						if($postmeta  && $postmeta['s']!='on') continue;
						$post = wp_get_single_post($event->post_id);
						if($ts < strtotime($post->post_modified)) {
							$ts = strtotime($post->post_modified);
							$returnarray['changes']=1;
						}
						if ( has_post_thumbnail($post->ID)) {
							$post_thumbnail_image=wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'medium');
							$post_thumbnail_image_full=wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'full');
						}
						else {
							$post_thumbnail_image[0] = '';
							$post_thumbnail_image_full[0] = '';
						}
						$start_ts = strtotime($event->event_start_date.' '.$event->event_start_time);
						$end_ts = strtotime($event->event_end_date.' '.$event->event_end_time);
						if(!$event->location_latitude || $event->location_latitude== null || $event->location_latitude=='null' || $event->location_latitude=='0.000000') $event->location_latitude =  0;
						else $event->location_latitude = (float) $event->location_latitude ;
						if(!$event->location_longitude || $event->location_longitude== null || $event->location_longitude=='null' || $event->location_longitude=='0.000000') $event->location_longitude =  0;
						else $event->location_longitude = (float) $event->location_longitude ;
						$post->post_title = str_replace(array("\\r","\\n","\r", "\n"),'',trim(html_entity_decode(strip_tags(do_shortcode($post->post_title)), ENT_NOQUOTES, 'UTF-8')));
						$returnarray['items'][] = array(
							'uma'=>array('start_ts_gmt',get_gmt_from_date($event->event_start_date.' '.$event->event_start_time), 'test'=>2),
							'pos'=>$i,
							'id'=>$post->ID,
							'title'=>($post->post_title),
							'timestamp'=>strtotime($post->post_modified),
							'post_date'=>strtotime($post->post_date),
							'type'=>$post->post_type,
							'thumb'=> ($post_thumbnail_image[0]),
							'publish_timestamp'=> strtotime($post->post_date),
							'event_id'=>$event->event_id,
							'subtitle' => '',
							'start_date' => date('d.m.Y',$start_ts),
							'end_date' => date('d.m.Y',$end_ts),
							'start_time' => date('H:i',$start_ts),
							'end_time' => date('H:i',$end_ts),
							'start_ts' => $start_ts,
							'end_ts' => $end_ts,
							'day' =>  $event->event_all_day,
							'swd' => $weekdays[date('w',$start_ts)],
							'ewd' => $weekdays[date('w',$end_ts)],
							//$returnarray['start_time'] .= (__(' Uhr'));
							//$returnarray['end_time'] .= (__(' Uhr'));
							//'thumb' => $post_thumbnail_image[0],
							'img' => $post_thumbnail_image_full[0],
							'location' => $event->location_name,
							'town' => $event->location_town,
							'city' => $event->location_town,
							'country' => $event->location_country,
							'zip' => $event->location_postcode,
							'plz' => $event->location_postcode,
							'address' => $event->location_address,
							'street' => $event->location_address,
							'region' => $event->location_region,
							'province' => $event->location_region,
							'extra' => '',
							'lat' => $event->location_latitude,
							'lng' => $event->location_longitude,
							'short_text' => htmlspecialchars_decode($post->post_excerpt),
							'sharelink'=> esc_url( get_permalink($post->ID))
					   );
						$i++;

					}
				}
				else{
					$returnarray['error']=$this->nh_ynaa_errorcode(22);
					$ts = time();
				}


			$returnarray['timestamp']=$ts;
			return array('events'=>$returnarray);

		} // END private function nh_ynaa_events()

		/*
		 * Function to get event details
		*/
		private function nh_ynaa_event() {
			if(isset($_GET[$this->requesvar['id']])){
				if($_GET[$this->requesvar['ts']]) $ts= $_GET[$this->requesvar['ts']];
				else $ts = 0;
				$weekdays = array(__('Sunday'), __('Monday'), __('Tuesday'), __('Wednesday'), __('Thursday'), __('Friday'), __('Saturday'));
				$returnarray['changes']=0;
				//WP Query
				global $wpdb;
				$table_em_events = $wpdb->prefix . "em_events";
				$table_em_locations = $wpdb->prefix . "em_locations";

				$event = $wpdb->get_row( $wpdb->prepare( "
					select event_id, e.post_id, event_slug, event_status, event_name, event_start_time, event_end_time, event_all_day, event_start_date, event_end_date, e.post_content, e.location_id,event_category_id, event_attributes, event_date_modified,
					l.location_name, l.location_address, l.location_town, l.location_state, l.location_postcode, l.location_region, l.location_country, l.location_latitude, l.location_longitude
					from $table_em_events e
					left join $table_em_locations l on l.location_id=e.location_id
					WHERE e.post_id=".$_GET[$this->requesvar['id']]."
					", array('%d', '$d', '%s', '%d', '%s', '%s', '%s', '%d','%d', '%d', '%s', '%d', '%d', '%d', '%s')));

				if($event) {
					$post = wp_get_single_post($event->post_id);
					if($post){
						if($ts < strtotime($post->post_modified)) {
							$ts = strtotime($post->post_modified);
							$returnarray['changes']=1;
						}
						$postmeta = unserialize(get_post_meta( $event->post_id, '_nh_ynaa_meta_keys', true ));
						if($postmeta  && $postmeta['s']!='on') {
							$returnarray['error']=$this->nh_ynaa_errorcode(15);
						}
						else {
							 $returnarray['error']=$this->nh_ynaa_errorcode(0);
							if ( has_post_thumbnail($post->ID)) {
								$post_thumbnail_image=wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'medium');
							}
							else $post_thumbnail_image[0] = '';
							$start_ts = strtotime($event->event_start_date.' '.$event->event_start_time);
							$end_ts = strtotime($event->event_end_date.' '.$event->event_end_time);
							//$content = '<div id="nh_ynaa__app_content">'.$post->post_content.'</div>';
							//$content = $this->nh_ynaa_get_appcontent($content);
							//$content = '<style type="text/css">'.$this->general_settings['css'].' body{color:'.$this->general_settings['ct'].';}</style>'.$content.'<style type="text/css">'.$this->general_settings['css'].' body{color:'.$this->general_settings['ct'].';}</style>';
							//$content = str_replace(PHP_EOL,null,$content);

							$returnarray['id']=$post->ID;
							if(isset($_GET[$this->requesvar['av']]) && (($_GET[$this->requesvar['pl']]=='ios' && $_GET[$this->requesvar['av']]>=1.7) || ($_GET[$this->requesvar['pl']]=='android' && $_GET[$this->requesvar['av']]>1.3))){
							}
							else{
								$queried_post = get_post($returnarray['id']);
								$content = $queried_post->post_content;
								$content = apply_filters('the_content', $content);
								$content = str_replace(']]>', ']]&gt;', $content);
								$content = str_replace("\r\n",'\n',$content);
								//$returnarray['uma']['post_content_-1']= $content;
								$content = preg_replace('/[\x00-\x1F\x80-\x9F]/u', '',$content);
								$content = $this->nh_ynaa_get_appcontent($content);
								//$content = preg_replace('/[\x00-\x1F\x80-\xFF]/', '',$content);
								if($this->css_settings['css'])$this->general_settings['css'] = $this->css_settings['css'];
								$this->general_settings['css'] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '',$this->general_settings['css']);
								$content = (str_replace('<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">','<!doctype html>',$content));


								if(strpos($content,'<html><head><meta charset="utf-8"></head>'))
									$content = str_replace('<html><head><meta charset="utf-8"></head>','<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><link href="http://necolas.github.io/normalize.css/3.0.1/normalize.css" rel="stylesheet" type="text/css"><style type="text/css">'.$this->general_settings['css'].' body{color:'.$this->general_settings['ct'].';}</style></head>',$content);
								elseif(strpos($content,'<html>'))
									$content = str_replace('<html>','<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><link href="http://necolas.github.io/normalize.css/3.0.1/normalize.css" rel="stylesheet" type="text/css"><style type="text/css">'.$this->general_settings['css'].' body{color:'.$this->general_settings['ct'].';}</style></head>',$content);
									else $content = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><link href="http://necolas.github.io/normalize.css/3.0.1/normalize.css" rel="stylesheet" type="text/css"><style type="text/css">'.$this->general_settings['css'].' body{color:'.$this->general_settings['ct'].';}</style></head><body>'.$content.'</body></html>';
								$returnarray['text']=$content;

							}


							if(!$event->location_latitude || $event->location_latitude== null || $event->location_latitude=='null' || $event->location_latitude=='0.000000') $event->location_latitude =  0;
							else $event->location_latitude = (float) $event->location_latitude ;
							if(!$event->location_longitude || $event->location_longitude== null || $event->location_longitude=='null' || $event->location_longitude=='0.000000') $event->location_longitude =  0;
							else $event->location_longitude = (float) $event->location_longitude ;

							$post->post_title = str_replace(array("\\r","\\n","\r", "\n"),'',trim(html_entity_decode(strip_tags(do_shortcode($post->post_title)), ENT_NOQUOTES, 'UTF-8')));
							$returnarray['title']=($post->post_title);
							$returnarray['post_date']=strtotime($post->post_date);
							$returnarray['timestamp']=strtotime($post->post_modified);
							$returnarray['type']='post';
							$returnarray['thumb']= ($post_thumbnail_image[0]);
							$returnarray['publish_timestamp']= strtotime($post->post_date);
							$returnarray['event_id']=$event->event_id;
							$returnarray['subtitle'] = '';
							$returnarray['start_date'] = date('d.m.Y',$start_ts);
							$returnarray['end_date'] = date('d.m.Y',$end_ts);
							$returnarray['start_time'] = date('H:i',$start_ts);
							$returnarray['end_time'] = date('H:i',$end_ts);
							$returnarray['start_ts'] = $start_ts;
							$returnarray['end_ts'] = $end_ts;
							$returnarray['day'] =  $event->event_all_day;
							$returnarray['swd'] = $weekdays[date('w',$start_ts)];
							$returnarray['ewd'] = $weekdays[date('w',$end_ts)];
							$returnarray['sharelink'] = esc_url( get_permalink($post->ID));

							//$returnarray['start_time'] .= (__(' Uhr'));
							//$returnarray['end_time'] .= (__(' Uhr'));
							//$returnarray['thumb'] = $post_thumbnail_image[0];
							$returnarray['images']= array($post_thumbnail_image[0]);
							$returnarray['location'] = $event->location_name;
							$returnarray['town'] = $event->location_town;
							$returnarray['city'] = $event->location_town;
							$returnarray['country'] = $event->location_country;
							$returnarray['zip'] = $event->location_postcode;
							$returnarray['address'] = $event->location_address;
							$returnarray['street'] = $event->location_address;
							$returnarray['region'] = $event->location_region;
							$returnarray['province'] = $event->location_region;
							$returnarray['extra'] = '';
							$returnarray['lat'] = $event->location_latitude;
							$returnarray['lng'] = $event->location_longitude;
							$returnarray['short_text'] = htmlspecialchars_decode($post->post_excerpt);

							/*$returnarray = array(
								'id'=>$post->ID,
								'title'=>htmlspecialchars_decode($post->post_title),
								'timestamp'=>strtotime($post->post_modified),
								'type'=>'post',
								'thumb'=> ($post_thumbnail_image[0]),
								'publish_timestamp'=> strtotime($post->post_date),
								'event_id'=>$event->event_id,
								'subtitle' => '',
								'start_date' => $event->event_start_date,
								'end_date' => $event->event_end_date,
								'start_time' => $event->event_start_time,
								'end_time' => $event->event_end_time,
								'start_ts' => $start_ts,
								'end_ts' => $end_ts,
								'day' =>  $event->event_all_day,
								'swd' => $weekdays[date('w',$start_ts)],
								'ewd' => $weekdays[date('w',$end_ts)],
								//$returnarray['start_time'] .= (__(' Uhr'));
								//$returnarray['end_time'] .= (__(' Uhr'));
								//'thumb' => $post_thumbnail_image[0],
								'images'=> array($post_thumbnail_image[0]),
								'location' => $event->location_name,
								'town' => $event->location_postcode,
								'city' => $event->location_postcode,
								'country' => $event->location_country,
								'zip' => $event->location_postcode,
								'address' => $event->location_address,
								'street' => $event->location_address,
								'region' => $event->location_region,
								'province' => $event->location_region,
								'extra' => '',
								'lat' => $event->location_latitude,
								'lng' => $event->location_longitude,
								'short_text' => htmlspecialchars_decode($post->post_excerpt),
								'text'=>$content


						  );*/



						}
					}
					else{
						$returnarray['error']=$this->nh_ynaa_errorcode(22);
						$ts = time();
						$returnarray['items'][] = array();
					}
				}
				else {
					$returnarray['error']=$this->nh_ynaa_errorcode(22);
					$ts = time();
					$returnarray['items'][] = array();
				}
			}
			else {
				$returnarray['error']=$this->nh_ynaa_errorcode(15);
			}

			return array('event'=>$returnarray);
		}// End private function nh_ynaa_event

		/*
		 * Function to get event details
		*/
		private function nh_ynaa_lang() {
			$returnarray = array();

			return array('lang'=>$returnarray);
		}// END private function nh_ynaa_lang


		/**
			Function to prepare Content for App
			return Formatet HTML
		*/
		private function nh_ynaa_get_appcontent($html){
			//echo $html;
			if($this->general_settings['domcontent'])$html =  '<!doctype html><html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /></head><body class="blappsta_ok">'.$html.'</body></html>';
			else{
				$libxml_previous_state = libxml_use_internal_errors(true);
				$dom = new DOMDocument();
				$caller = new ErrorTrap(array($dom, 'loadHTML'));
				$caller->call($html);
				if ( !$caller->ok()) {
			  		$html='<!doctype html><html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /></head><body class="blappsta_ok">'.$html.'</body></html>';
				}
				else {
					if($this->general_settings['utf8'])	$html = mb_convert_encoding($html, 'html-entities', 'utf-8');

					$dom->validateOnParse = true;
					$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
					$dom->preserveWhiteSpace = false;


					// dirty fix
					foreach ($dom->childNodes as $item)
						if ($item->nodeType == XML_PI_NODE)
							$dom->removeChild($item); // remove hack

					$dom->encoding = 'UTF-8'; // insert proper


					$imgElements  = $dom->getElementsByTagName("img");
					if(!isset($this->general_settings['min-img-size-for-resize'])) $this->general_settings['min-img-size-for-resize'] = 100;
	       // echo $this->general_settings['min-img-size-for-resize'];
					 $upload_dir = wp_upload_dir();
						foreach ($imgElements as $imgElement) {

						if(strpos($imgElement->getAttribute('class'),'wp-smiley')!== false) continue;
				          if($imgElement->hasAttribute('width')) {

				            $imgElement->removeAttribute('width');

				          }




						$src = $imgElement->getAttribute('src');
						if($upload_dir['baseurl'] == substr($src,0,strlen($upload_dir['baseurl']))){
						  //echo $upload_dir['basedir'].substr($src,strlen($upload_dir['baseurl'])).'<br>';
							if($this->general_settings['min-img-size-for-resize']){

	              				$src_o = $src;
								$src = $upload_dir['basedir'].substr($src,strlen($upload_dir['baseurl']));
	              				if(file_exists($src)){
									list($w, $h) = @getimagesize($src);


								 	if(isset($w) ){
								   		if($w < $this->general_settings['min-img-size-for-resize'] ) {
								   			$imgclass=' nh-no-resize ';
							                  if($imgElement->hasAttribute('class')){
							                    $imgclass = $imgElement->getAttribute('class').$imgclass;
							                  }
							                  $imgElement->setAttribute('class',$imgclass);
								   			continue;
								   		}
						                 /*
						                  $imgclass=' nh-img-space ';
						                  if($imgElement->hasAttribute('class')){
						                    $imgclass .= $imgElement->getAttribute('class');
						                  }
						                  $imgElement->setAttribute('class',$imgclass);
						                  */
										else {
						                 //$imgElement->setAttribute('width','100%');
						                 if($imgElement->hasAttribute('height'))$imgElement->removeAttribute('height');
										}

						                 /*if(!(strpos($imgclass, 'no-lazy'))){
													     //var_dump($w,$imgclass);
													       $imgElement->setAttribute('src','http://yna.nebelhorn.com/wp-content/plugins/yournewsapp/img/1.png');
													       $imgElement->setAttribute('data-nh-src',$src_o);
						                     if($_GET[$this->requesvar['b']]){
						                        $imgElement->setAttribute('width',$_GET[$this->requesvar['b']]);
						                      $imgElement->setAttribute('height',(int)($_GET[$this->requesvar['b']]*$h/$w));
						                     }
						                 }*/


	               					}
	              				}
	              				else continue;
							}
						}
						elseif( ini_get('allow_url_fopen') ) {

							if($this->general_settings['min-img-size-for-resize']){
								if(@getimagesize(($src))){

	  							list($w, $h) = @getimagesize(($src)); //var_dump($w);
	  							if(isset($w)){
	  							  if( $w < $this->general_settings['min-img-size-for-resize'] ) {
	  							  	$imgclass=' nh-no-resize ';
					                  if($imgElement->hasAttribute('class')){
					                    $imgclass = $imgElement->getAttribute('class').$imgclass;
					                  }
					                  $imgElement->setAttribute('class',$imgclass);
									  continue;
	  							  }
	                  /*
	                  elseif(!(strpos($imgclass, 'no-lazy'))){
	  							   $imgElement->setAttribute('src','http://yna.nebelhorn.com/wp-content/plugins/yournewsapp/img/1.png');
	                  $imgElement->setAttribute('data-nh-src',$src);
	                  $imgElement->setAttribute('width',$_GET[$this->requesvar['b']]);
	                 $imgElement->setAttribute('height',(int)($_GET[$this->requesvar['b']]*$w/$h));
	  								//list($w, $h) = getimagesize($src);
	  								//$imgElement->setAttribute('title','uma05');


	                  }*/
								 else{
	                  				//$imgElement->setAttribute('width','100%');
	                 				if($imgElement->hasAttribute('height'))$imgElement->removeAttribute('height');
								 }
	  						}
			              }
			              else continue;
							}
						}
						//echo $imgElement->getAttribute("src").'<hr>';



						if($imgElement->parentNode->nodeName != 'a'){
							$clone = $imgElement->cloneNode(false);
							/*$newE = $dom->createElement('a');
							$newE->setAttribute('href', $imgElement->getAttribute("src"));
							$newE->appendChild ($clone);*/
							$newEdiv = $dom->createElement('div');
							$newEdiv->appendChild ($clone);
							$imgElement->parentNode->replaceChild($newEdiv,$imgElement);
						}
					}


				//iframe tag src replace // with http://
					$iframeElements  = $dom->getElementsByTagName("iframe");
					foreach ($iframeElements as $iframeElement) {
						$src = $iframeElement->getAttribute('src');
						/*if(substr($src,0,2)=='//'){
							$iframeElement->setAttribute('src','http:'.$src);
						}*/
					}

					$divElements  = $dom->getElementsByTagName("div");
					foreach ($divElements as $divElement) {
						if($divElement->hasAttribute('style'))$divElement->removeAttribute('style');
					}


					/*//$headdom = $dom->createElement('head','<title>'..'</title>');
					$newEStyle = $dom->createElement('style',($this->general_settings['css']).' body { color:'.$this->general_settings['ct'].';}'.'');
					$newEStyle->setAttribute('type','text/css');
					//$dom->appendChild($newEStyle);

					$htmltags = $dom->getElementsByTagName ('html');
					foreach ($htmltags as $htmltag) {
						$htmltag->appendChild($newEStyle);
						break;
					}*/

					$html = $dom->saveHTML();
				}
				if(!isset($this->general_settings['blank_lines']) && !$this->general_settings['blank_lines']){

					$html = nl2br($html);
					$htmlsup = substr($html,0,strpos($html,'<body>'));
					$htmlsup = str_replace(array('<br />', '<br>'),'',$htmlsup);
					$html = substr($html,strpos($html,'<body>'),-7);
					$html = $htmlsup.$html;
				 }

			}
			/*else{
				$html = '<!doctype html>
						<html>
						<head>
						<meta charset="utf-8">
						</head>
						<body>'.
						$html
						.'</body>
						</html>';
			}*/
			//echo strpos('<html>',$html);
			//$html = '<!doctype html>'.substr($html,strpos('<html>',$html)+6);

			/* Loding imges response */
			//$html = str_replace('</body>','<script src="http://www.blappsta.com/wp-content/plugin/"></script></body>',$html);
			//$jquery = '<script src="http://code.jquery.com/jquery-2.1.0.min.js" type="text/javascript"></script>';
      /*$jquery .= '<script type="text/javascript">
        $( document ).ready(function() {

          $("img.nh-img-space").each(function(i, obj) {
            var $ob = $(obj);
            $ob.attr("src",$ob.attr("data-nh-src"));
            //$ob.css("background-image","url(\'"+($ob.attr("data-nh-src"))+"\')");

          });
          //$("img.nh-img-space").css("background-image","url(\'"+($("img.nh-img-space").attr("data-nh-src"))+"\')");

      });
      </script>';*/
			//$html = str_replace('</body>',$jquery.'</body>',$html);


      		//JQUERY insert
      		//$jquery = '<script src="http://code.jquery.com/jquery-2.1.0.min.js" type="text/javascript"></script>';

      		//$html = str_replace('</body>',$jquery.'</body>',$html);
      		//Blappsta extra
			$blappsta_extra = get_option( 'nh_ynaa_blappsta' );
			if(is_array($blappsta_extra)){
				//var_dump($blappsta_extra['app']['extra']['app_extra_js']);
				if($blappsta_extra['app']['extra']['app_extra_css']){

					$html = str_replace('</body>','<style type="text/css">'.stripslashes ($blappsta_extra['app']['extra']['app_extra_css']).'</style></body>',$html);
				}
				if($blappsta_extra['app']['extra']['app_extra_js']){
					$html = str_replace('</body>',stripslashes ($blappsta_extra['app']['extra']['app_extra_js']).'</body>',$html);
				}
			}
			return ($html);
		}//END private function nh_ynaa_get_appcontent

		/**
		 *Function to get Facebook content
		*/
		private function nh_ynaa_get_fbcontent($limit=50){
			//echo 'fb';
			if(!$_GET[$this->requesvar['meta']] && (isset($this->general_settings['social_fbid'],$this->general_settings['social_fbsecretid'],$this->general_settings['social_fbappid']) && ($this->general_settings['social_fbid'] != '' && $this->general_settings['social_fbsecretid'] != '' && $this->general_settings['social_fbappid'] != '' ))){
				//echo 'fb3';
				if(!class_exists('Facebook')) {
					include_once('facebook-php-sdk-master/src/facebook.php');
				}
				if(class_exists('Facebook')){
					//echo 'fb4';
					$config = array(
					  'appId' => $this->general_settings['social_fbappid'],
					  'secret' => $this->general_settings['social_fbsecretid'],
					  'fileUpload' => false // optional
					);
					$facebook = new Facebook($config);
					$access_token = $facebook->getAccessToken();
					if( $access_token){
					$limit2 =$limit;
					if($limit==1) $limit2 =50;
						$url = 'https://graph.facebook.com/'.$this->general_settings['social_fbid'].'/feed?access_token='.$access_token.'&format=json&type=post&limit='.$limit2;
						$items = $this->nh_ynaa_get_data($url,$limit);
						if($items){

								$returnarray=$items;
						}
						else {
							$returnarray['error']=$this->nh_ynaa_errorcode(28);
						}
					}
					else {
						$returnarray['error']=$this->nh_ynaa_errorcode(27);
					}
				}
				else {
					$returnarray['error']=$this->nh_ynaa_errorcode(26);
					//echo 'fb5';
				}
			}
			else {
				$returnarray['error']=$this->nh_ynaa_errorcode(25);
			}
			//echo 'fb2';
			return $returnarray;
		} //END function nh_ynaa_get_fbcontent

		/**
		 * Function get extra data from blappsta.com
		 * set wp option variable
		 */
		 private function nh_get_blappsta_extra(){
		 	$ts =  get_option( 'nh_ynaa_blappsta_ts' );
		 	if(!$ts || date('Ymd',$ts)<date('Ymd') || $_GET['update_bas']=='true') {
		 		//var_dump(get_bloginfo('url'));
				$content = '';
        if(ini_get('allow_url_fopen')){
  				$content = @file_get_contents('http://www.blappsta.com?bas=extra_infos&url='.urlencode(get_bloginfo('url')));
  				if($content){
  					$json=json_decode($content,true);
  					update_option('nh_ynaa_blappsta', $json);
  					update_option('nh_ynaa_blappsta_ts', time());


  				}
        }
			}
		 }// END private function nh_get_blappsta_extra

		/*
			*gets the data from a URL
			* @$url String url
			* return strin content
		*/
		function nh_ynaa_get_data($url,$limit,$offset=0) {
			$data = false;
      $items = false;
			//$url = $url.'&offset='.$offset;
			if(ini_get('allow_url_fopen')){
			 $items = file_get_contents($url);
      }
			if($items){
				$data=$items;
			}
			else {
				if(function_exists('curl_version')){
					$ch = curl_init();
					$timeout = 25;
					curl_setopt($ch, CURLOPT_URL, $url);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
					$items = curl_exec($ch);
					curl_close($ch);
					if($items){
						$data=$items;
					}
				}
			}
			if($limit==1 && $data){
				$datatemp = json_decode($data,true);
				//var_dump($datatemp);
				foreach($datatemp['data'] as $temp){


					if($temp['message']) {
						$data = (json_encode(array('data'=>array($temp),'paging'=>$datatemp['paging'])));

						//var_dump($temp);

						break;
					}
				}
				/*while($datatemp['data'][0]['message'] ){
					$data = $this->nh_ynaa_get_data($url,1,$offset++);
				}*/
			}
			return $data;
		}

		//UPDATE Option timestamps
		function nh_update_option_ynaa_general_settings($old, $new){
			$ts = $new['ts'];
			update_option('nh_ynaa_general_settings_ts', $ts);
		}
		function nh_update_option_ynaa_menu_settings($old, $new){
			$ts = $new['ts'];
			update_option('nh_ynaa_menu_settings_ts', $ts);
			update_option('nh_ynaa_general_settings_ts', $ts);
		}
		function nh_update_option_ynaa_css_settings($old, $new){
			$ts = $new['ts'];
			update_option('nh_ynaa_css_settings_ts', $ts);
			update_option('nh_ynaa_general_settings_ts', $ts);
		}
		function nh_update_option_ynaa_teaser_settings($old, $new){
			$ts = $new['ts'];
			update_option('nh_ynaa_teaser_settings_ts', $ts);
		}
		function nh_update_option_ynaa_homepreset_settings($old, $new){
			$ts = $new['ts'];
			update_option('nh_ynaa_homepreset_settings_ts', $ts);
			update_option('nh_ynaa_general_settings_ts', $ts);
			update_option('nh_ynaa_articles_ts',$ts);
		}
		function nh_update_option_ynaa_push_settings($old, $new){
			$ts = $new['ts'];
			update_option('nh_ynaa_push_settings_ts', $ts);
		}
		function nh_update_option_ynaa_categories_settings($old, $new){
			$ts = $new['ts'];
			update_option('nh_ynaa_categories_settings_ts', $ts);
			update_option('nh_ynaa_general_settings_ts', $ts);
			update_option('nh_ynaa_homepreset_settings_ts', $ts);
		}






		/**
		 * Adds a box to the main column on the Post and Page edit screens.
		 */
		function nh_ynaa_add_custom_box() {
			if($pushsettings = get_option($this->push_settings_key)){
				if($pushsettings['pushshow'] ){
					$post_types = get_post_types();
					foreach( $post_types as $post_type ){
								if( !in_array( $post_type, array( 'attachment', 'revision', 'nav_menu_item' ) ) ){
									$screens[] = $post_type;
								}
					}

					foreach ( $screens as $screen ) {

						add_meta_box(
							'nh_ynaa_sectionid',
							__( 'Blappsta Plugin extras', 'nh_ynaa' ),
							array($this,'nh_ynaa_inner_custom_box'),
							$screen, 'side', 'default'
						);
					}
				}
			}

			if($generalsettings = get_option($this->general_settings_key)){
				global $nh_ynaa_db_version;
				 if (get_option( 'nh_ynaa_db_version' ) == $nh_ynaa_db_version) {
					if($generalsettings['location'] ){
					$post_types = get_post_types();
					foreach( $post_types as $post_type ){
								if( !in_array( $post_type, array( 'attachment', 'revision', 'nav_menu_item', 'events' ) ) ){
									$screens[] = $post_type;
								}
					}

					foreach ( $screens as $screen ) {

						add_meta_box(
							'nh_ynaa_locationid',
							__( 'Blappsta Plugin locations', 'nh_ynaa' ),
							array($this,'nh_ynaa_inner_location_box'),
							$screen, 'normal', 'default'
						);
					}
				}
				 }
			}
		}

		/**
		 * Prints the box content.
		 *
		 * @param WP_Post $post The object for the current post/page.
		 */
		function nh_ynaa_inner_location_box( $post ) {

		  // Add an nonce field so we can check for it later.
		  wp_nonce_field( 'nh_ynaa_inner_location_box', 'nh_ynaa_inner_location_box_nonce' );

		  /*
		   * Use get_post_meta() to retrieve an existing value
		   * from the database and use the value for the form.
		   */
		  $value = unserialize(get_post_meta( $post->ID, '_nh_ynaa_location', true));
		   $ynaa_location_id = (get_post_meta( $post->ID, 'nh_ynaa_location_id', true));
		 // var_dump($value);
		  $required = '';
		 ?>
         <div id="nh-location-data" class="nh-location-data">
			<div id="nh_location_coordinates" style=" display:none;">
				<input id="nh_location_latitude" name="nh_location_latitude" type="hidden" value="<?php echo $value['location_latitude']; ?>" size="15" >
				<input id="nh_location_longitude" name="nh_location_longitude" type="hidden" value="<?php echo $value['location_longitude']; ?>" size="15" >

			</div>

			<table class="nh-location-data">
				<tbody>
                	<tr class="nh-location-data-name">
                    	<th><?php _e('Location Name','nh-ynaa'); ?>:</th>
                        <td><input id="nh_location_id" name="nh_location_id" type="hidden" value="<?php echo $ynaa_location_id; ?>" size="15"><input type="hidden" value="0" name="nh_location_del" id="nh_location_del">
                        <input type="hidden" value="0" name="nh_location_name_change" id="nh_location_name_change"><input type="hidden" value="0" name="nh_location_change" id="nh_location_change">
				<span role="status" aria-live="polite" class="ui-helper-hidden-accessible"></span><input id="nh_location_name" type="text" name="nh_location_name" value="<?php echo esc_attr(stripslashes($value['location_name'])); ?>" class="ui-autocomplete-input"><?php echo $required; ?>
				<br>
				<em id="nh-location-search-tip" style="display: none;"><?php _e( 'Create a location or start typing to search a previously created location.', 'nh-ynaa' )?></em>
				<em id="nh-location-reset" style="display:none;"><?php _e('You cannot edit saved locations here.', 'nh-ynaa'); ?> <a href="#"><?php _e('Reset this form to create a location or search again.', 'nh-ynaa')?></a></em>
			</td>
 		</tr>
		<tr class="nh-location-data-address">
			<th><?php _e ( 'Address:', 'nh-ynaa' )?>&nbsp;</th>
			<td>
				<input id="nh_location_address" type="text" name="nh_location_address" value="<?php echo esc_attr($value['location_address'], ENT_QUOTES); ; ?>" class="blurlocation" /><?php echo $required; ?>
			</td>
		</tr>
		<tr class="nh-location-data-town">
			<th><?php _e ( 'City/Town:', 'nh-ynaa' )?>&nbsp;</th>
			<td>
				<input id="nh_location_town" type="text" name="nh_location_town" value="<?php echo esc_attr($value['location_town'], ENT_QUOTES); ?>" class="blurlocation" /><?php echo $required; ?>
			</td>
		</tr>
		<!--<tr class="nh-location-data-state">
			<th><?php _e ( 'State/County:', 'nh-ynaa' )?>&nbsp;</th>
			<td>
				<input id="nh_location-state" type="text" name="nh_location_state" value="<?php echo esc_attr($value['location_state'], ENT_QUOTES); ?>" class=" blurlocation" />
			</td>
		</tr>-->
		<tr class="nh-location-data-postcode">
			<th><?php _e ( 'Postcode:', 'nh-ynaa' )?>&nbsp;</th>
			<td>
				<input id="nh_location_postcode" type="text" name="nh_location_postcode" value="<?php echo esc_attr($value['location_postcode'], ENT_QUOTES); ?>" class=" blurlocation" />

			</td>
		</tr>
	<!--	<tr class="nh-location-data-region">
			<th><?php _e ( 'Region:', 'nh-ynaa' )?>&nbsp;</th>
			<td>
				<input id="nh_location_region" type="text" name="nh_location_region" value="<?php echo esc_attr($value['location_region'], ENT_QUOTES); ?>" class=" blurlocation" />
			</td>
		</tr>
		-->
        <tr>
        	<th><?php _e('Pin color'); ?></th>
            <td><select name="nh_location_pintype" id="nh_location_pintype" class="">
            	<option value="red" <?php if($value['location_pintype']=='red') echo 'selected'; ?>><?php _e('red'); ?></option>
                <option value="green" <?php if($value['location_pintype']=='green') echo 'selected'; ?>><?php _e('green'); ?></option>
                <option value="purple" <?php if($value['location_pintype']=='purple') echo 'selected'; ?>><?php _e('purple'); ?></option>

            </select>
            </td>
        </tr>
        <tr>
        	<th></th>
            <td><a href="#del" id="reset_location"><?php _e('Reset this form to create a location.', 'nh-ynaa'); ?></a></td>
        </tr>
	</tbody>
    </table>

			<div class="nh-location-map-container">
                <div id='nh-map-404'  class="nh-location-map-404" style="display:none;">
                    <p><em><?php _e ( 'Location not found', 'nh-ynaa' ); ?></em></p>
                </div>
                <div id='nh-map' class="nhm-location-map-content" style="float:left;">
                <div style="width: 400px" id="googlemapdiv"><?php if($value['location_latitude'] && $value['location_longitude']) { ?><iframe id="googlemapiframe" width="400" height="400" src="http://maps.google.de/maps?hl=de&q=<?php echo urlencode($value['location_address']).','. urlencode($value['location_postcode']).'+'. urlencode($value['location_town']).' ('.urlencode($value['location_name']).')'; ?>&ie=UTF8&t=&iwloc=A&output=embed" frameborder="0" scrolling="no" marginheight="0" marginwidth="0"></iframe><?php } ?></div>
                </div>
            </div>
			<br style="clear:both;">
		</div>
         <?php
		 /* echo '<div><label for="nh_ynaa_location_name">'.__('Location name','nh-ynaa:').'</label>';
		  	echo '<input type="text" value="'.$value['nh_ynaa_location_name'].'" id="nh_ynaa_location_name" name="nh_ynaa_location_name" required class="required" maxlength="250" >';
			echo '<input type="checkbox" id="nh_ynaa_" name="nh_ynaa_visible_app" ';
			if($value) {
				if($value['s']) echo ' checked="checked" ';
			}
			else echo ' checked="checked" ';
			echo ' />&nbsp;&nbsp;';
			_e( "Show in App", 'nh-ynaa' );

		  echo '</label></div> ';
		  echo '<hr>';
		  echo '<div><label for="nh_ynaa_pushtext">';
			_e( "Push Text", 'nh-ynaa' );

			echo '<br /><textarea style="width:100%" id="nh_ynaa_pushtext" name="nh_ynaa_pushtext" maxlength="120"></textarea>';


		  echo '</label></div> ';

		  echo '<div><label for="nh_ynaa_sendpush">';


			echo '<input type="button" value="'.__('Send Push').'" id="nh_ynaa_sendpush" />';


		  echo '</label></div> ';*/

		}

		/**
		 * Prints the box content.
		 *
		 * @param WP_Post $post The object for the current post/page.
		 */
		function nh_ynaa_inner_custom_box( $post ) {

		  // Add an nonce field so we can check for it later.
		  wp_nonce_field( 'nh_ynaa_inner_custom_box', 'nh_ynaa_inner_custom_box_nonce' );

		  /*
		   * Use get_post_meta() to retrieve an existing value
		   * from the database and use the value for the form.
		   */
		  $value = unserialize(get_post_meta( $post->ID, '_nh_ynaa_meta_keys', true));

		  echo '<div><label for="nh_ynaa_visible_app">';
			echo '<input type="checkbox" id="nh_ynaa_visible_app" name="nh_ynaa_visible_app" ';
			if($value) {
				if($value['s']) echo ' checked="checked" ';
			}
			else echo ' checked="checked" ';
			echo ' />&nbsp;&nbsp;';
			_e( "Show in App", 'nh-ynaa' );

		  echo '</label></div> ';
		  echo '<hr>';
		  echo '<div><label for="nh_ynaa_pushtext">';
			_e( "Push Text", 'nh-ynaa' );

			echo '<br /><textarea style="width:100%" id="nh_ynaa_pushtext" name="nh_ynaa_pushtext" maxlength="120">'.$post->post_title.'</textarea>';


		  echo '</label></div> ';

		  echo '<div><label for="nh_ynaa_sendpush">';


			echo '<input type="button" value="'.__('Send Push','nh-ynaa').'" id="nh_ynaa_sendpush" />';


		  echo '</label></div><div id="nh-push-dialog" title="Push"><span style="display:none;">'.__('Please wait...','nh-ynaa').'</span></div> ';

		}

		/**
		 * When the post is saved, saves our custom data.
		 *
		 * @param int $post_id The ID of the post being saved.
		 */
		function nh_ynaa_save_postdata( $post_id ) {

		  /*
		   * We need to verify this came from the our screen and with proper authorization,
		   * because save_post can be triggered at other times.
		   */
		  if($pushsettings = get_option($this->push_settings_key)){
				if($pushsettings['pushshow'] ){
				  // Check if our nonce is set.
				  if ( ! isset( $_POST['nh_ynaa_inner_custom_box_nonce'] ) )
					return $post_id;

				  $nonce = $_POST['nh_ynaa_inner_custom_box_nonce'];

				  // Verify that the nonce is valid.
				  if ( ! wp_verify_nonce( $nonce, 'nh_ynaa_inner_custom_box' ) )
					  return $post_id;
				}
		  }

		  if($generalsettings = get_option($this->general_settings_key)){
				if($generalsettings['location'] ){
				  // Check if our nonce is set.
				  if ( ! isset( $_POST['nh_ynaa_inner_location_box_nonce'] ) )
					return $post_id;

				  $nonce = $_POST['nh_ynaa_inner_location_box_nonce'];

				  // Verify that the nonce is valid.
				  if ( ! wp_verify_nonce( $nonce, 'nh_ynaa_inner_location_box' ) )
					  return $post_id;
				}
		  }

		  // If this is an autosave, our form has not been submitted, so we don't want to do anything.
		  if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			  return $post_id;

		  // Check the user's permissions.
		  if ( 'page' == $_POST['post_type'] ) {

			if ( ! current_user_can( 'edit_page', $post_id ) )
				return $post_id;

		  } else {

			if ( ! current_user_can( 'edit_post', $post_id ) )
				return $post_id;
		  }

		  /* OK, its safe for us to save the data now. */

		  // Sanitize user input.
		  if($pushsettings = get_option($this->push_settings_key)){
				if($pushsettings['pushshow'] ){

				  $appdata['s'] = ($_POST['nh_ynaa_visible_app'] );

				  // Update the meta field in the database.
				  update_post_meta( $post_id, '_nh_ynaa_meta_keys', serialize($appdata) );
		  	}
		  }

		  if($generalsettings = get_option($this->general_settings_key)){
			   //update_post_meta( $post_id, '_nh_ynaa_location_name', $_POST['nh_ynaa_visible_app'].'123'. $_POST['nh_location_name'] );
			   if($_POST['nh_location_del'] && !$_POST['nh_location_change'] ){
				   if($_POST['nh_location_id']){
						global $wpdb;
						global $blog_id;
						$table_name = $wpdb->prefix ."nh_locations";
						$wpdb->update($table_name,array('location_status'=>0, 'location_update_stamp'=>date('Y-m-d H:i:s')),array( 'location_id' => $_POST['nh_location_id'] ),array('%d'), array('%d') );
						delete_post_meta( $post_id, 'nh_ynaa_location_id');
						delete_post_meta( $post_id, '_nh_ynaa_location');

				   }

			   }
				elseif(isset($generalsettings['location']) && $generalsettings['location'] ){
					if((!isset($_POST['nh_location_change']) || !$_POST['nh_location_change'])) return $post_id;
					//if((!isset($_POST['nh_location_change']) || !$_POST['nh_location_change']) &&(!isset($_POST['nh_location_address']) || $_POST['nh_location_address']=='') && (!isset($_POST['nh_location_address']) || $_POST['nh_location_address'] = '') && (!isset($_POST['nh_location_name'])|| $_POST['nh_location_name']=='') && (!isset($_POST['nh_location_postcode'])|| $_POST['nh_location_postcode']=='')) return $post_id;
					global $wpdb;
					global $blog_id;
					$table_name = $wpdb->prefix ."nh_locations";
					// update_post_meta( $post_id, '_nh_ynaa_location_name',$table_name.'123'. $_POST['nh_location_name'] );

					$adresse = '';
					$data['location_address'] = '';
					$format[] = '%s';
					if($_POST['nh_location_address']) {
						$data['location_address'] = $_POST['nh_location_address'];
						$adresse .= $data['location_address'].',';
					}


					$data['location_town'] = '';
					$format[] = '%s';
					if($_POST['nh_location_town']) {
						$data['location_town'] = $_POST['nh_location_town'];
						$adresse .= $data['location_town'].',';

					}

					$data['location_state'] = '';
					$format[] = '%s';
					if( $_POST['nh_location_state']) {
						$data['location_state'] = $_POST['nh_location_state'];
						$adresse .= $data['location_state'].',';
					}

					$data['location_postcode'] = '';
					$format[] = '%s';
					if( $_POST['nh_location_postcode']) {
						$data['location_postcode'] = $_POST['nh_location_postcode'];
						$adresse .= $data['location_postcode'].',';
					}

					$data['location_region'] = '';
					$format[] = '%s';
					if( $_POST['nh_location_region']) {
						$data['location_region'] = $_POST['nh_location_region'];
						$adresse .= $data['location_region'].',';
					}

					$data['location_country'] = 'DE';
					$format[] = '%s';
					$adresse .= $data['location_country'];

					$data['location_update_stamp'] = date('Y-m-d H:i:s');
					$format[] = '%s';


					if($_POST['nh_location_change']){
						$cord = $this->getLatLng($adresse);
						if($cord && is_array($cord)){
							$data['location_latitude'] = $cord['lat'];
							$format[] = '%s';
							$data['location_longitude'] = $cord['lng'];
							$format[] = '%s';
						/*
							$data['post_content'] = serialize($cord);
							$format[] = '%s';*/


						}
					}
					$data['location_name']= ($_POST['nh_location_name']);
					$format[] = '%s';
					$data['location_slug']= sanitize_title_with_dashes($_POST['nh_location_name']);
					$format[] = '%s';
					$data['blog_id'] = $blog_id;
					$format[]='%d';
					$data['post_id']=$post_id;
					$format[]= '%d';
					$data['location_owner'] = get_current_user_id();
					$format[]= '%d';
					$data['location_pintype'] = 'red';
					$format[] = '%s';
					if( $_POST['nh_location_pintype']) {
						$data['location_pintype'] = $_POST['nh_location_pintype'];
					}


					$data['location_status'] =1;
					$format[] = '%d';

					if($_POST['nh_location_id']){
						if($_POST['nh_location_change']) {
							$wpdb->update($table_name,$data,array( 'location_id' =>$_POST['nh_location_id'] ),$format, array('%d') );
							update_post_meta( $post_id, '_nh_ynaa_location', mysql_real_escape_string(serialize($data)));
							update_post_meta( $post_id, 'nh_location_update_stamp', $data['location_update_stamp']);
						}
						//elseif($_POST['nh_location_change']){
						//	$wpdb->update($table_name,$data,array( 'location_id' => 1 ),$format, array('%d') );
						//	update_post_meta( $post_id, '_nh_ynaa_location', serialize($data));
						//}
					}
					else {
						$wpdb->insert($table_name,$data,$format);
						$data['id'] = $wpdb->insert_id;
						add_post_meta( $post_id, 'nh_ynaa_location_id', $data['id']);
						add_post_meta( $post_id, 'nh_location_update_stamp', $data['location_update_stamp']);
						add_post_meta( $post_id, '_nh_ynaa_location', mysql_real_escape_string(serialize($data)));
					}



				}
		  }

		}

		/**
		*Teaser set img post ID etc
		*/
		function ny_ynaa_teaser_action($tpid=0, $type= ''){
			if($tpid)$_POST['tpid']= $tpid;
			if($type)$_POST['type']= $type;
			$result['type'] = "success";
			if($_POST['tpid'] && $_POST['type']=='cat'){
				$result['tpid']= $_POST['tpid'];
				$category = $this->nh_get_category($_POST['tpid']);

				if($category) {
					//$result['uma']['$category']= $category;
					$result['error'] = 0;
					$result['type'] = 'cat';
					$cat = $this->categories_settings[$_POST['tpid']];
					if($cat){
						if($cat['hidecat']==1) $result['error'] = 1;;
						$result['title'] = $cat['cat_name']  ;
						if($cat['usecatimg']==1 && !empty($cat['img'])) {
							$result['img']= $cat['img']  ;
						}
						else {
							$post = $this->nh_wp_get_recent_posts(1,$_POST['tpid']);
							//$result['uma']['$post_id']=$post;
							//$result['uma']['$post[0]']=$post[0];
							//$result['uma']['$post[0]->ID']=$post[0]->ID;
							//$result['uma']['$post[0]->[ID]']=$post[0]['ID'];
							$result['img']= $this->nh_getthumblepic($post[0]['ID'],'full');  ;

						}
						$result['type'] = 'cat';
					}
					else {
						$result['title'] = $category->name;
						$post = $this->nh_wp_get_recent_posts(1,$_POST['tpid']);
						//$result['uma']['$post_id']=$post;
						//$result['uma']['$post[0]']=$post[0];
						//$result['uma']['$post[0]->ID']=$post[0]->ID;
						//$result['uma']['$post[0]->[ID]']=$post[0]['ID'];
						$result['img']= $this->nh_getthumblepic($post[0]['ID'],'full');  ;


					}
				}

			}
			elseif($_POST['tpid']){
				$result['tpid']= $_POST['tpid'];
				$post = get_post($_POST['tpid']);
				if($post) $result['error'] = 0;
				$result['title'] = strip_tags(get_the_title($_POST['tpid']));
				$result['img']= $this->nh_getthumblepic($_POST['tpid']);
				$result['type'] = get_post_type($_POST['tpid']);
				//$result['allowremoveText']= __('Allow hide on Startscreen','nh-ynaa');
				//$result['catText']= __('Set default image for category','nh-ynaa');
			}
			else $result['error'] = __('No ID');

			if($tpid){
				return 	$result;
			}
			$result = json_encode($result);
			echo $result;
			die();

		}

		function nh_wp_get_recent_posts( $limit = 1, $category = 0, $type='post',  $orderby ='post_date', $order = 'DESC') {
			$args = array(
				    'numberposts' => $limit,
				     'post_status' => 'publish',
				     'post_type' => $type,
				     'category'=>$category,
				     'orderby' => $orderby,
    				'order' => $order,
    				'offset' => 0
				    );
			$recent_posts = wp_get_recent_posts( $args, ARRAY_A );
			return $recent_posts;
		}

		/**
		*Ajax search
		*/
		function nh_search_action() {
			global $wpdb; // this is how you get access to the database

			if(trim($_POST['s'])){
				if($_POST['pt']) $post_type = $_POST['pt'];
				else $post_type = 'post';
				if($_POST['mid']) $menu_id = $_POST['mid'];
				else $menu_id = '1';


				$search_query = new WP_Query();
				$results = $search_query->query('s='.trim($_POST['s'].'&post_type='.$post_type));

				if($results) {
					foreach($results as $p){
						$temp = "";
						$shorttitle = $this->shortenText($p->post_title);
						$temp .= '<li>';
						$temp .=  '<input type="hidden" value="'.$post_type.'" name="type-menu-item-'.$post_type.$menu_id.'" id="type-menu-item-'.$post_type.$menu_id.'" >';

						$temp .=  '<input type="hidden" value="html" name="link-typ-menu-item-'.$post_type.$menu_id.'" id="link-type-menu-item-'.$post_type.$menu_id.'">';
						$temp .=  '<input type="hidden" value="'.$shorttitle.'" name="title-menu-item-'.$post_type.$menu_id.'" id="title-menu-item-'.$post_type.$menu_id.'">';
						$temp .=  '<label class="menu-item-title">';
						$temp .=  '<input type="checkbox" value="'.$p->ID.'" name="menu-item-'.$post_type.$menu_id.'" class="menu-item-checkbox" /> ';
						$temp .=  $shorttitle.'</label>';
						$temp .=  '</li>';
						echo $temp;
						$menu_id++;
					}
				}
				else _e('No posts found.');
			}
			else {
				_e('Error');
			}

			die(); // this is required to return a proper result

		}

		/**
		* Functin get thumble pic
		*/
		function nh_getthumblepic($id, $size='full'){
		  //if(isset($_GET['ynaa'])&& ($_GET['ynaa']=='teaser' || $_GET['ynaa']=='nh_teaser' )) $size='full';
			$url ='';
			if($id){
				if($this->general_settings['gadgetry']){
					$gadgetry_tfuse_post_options = get_post_meta($id,'gadgetry_tfuse_post_options',true);
					if($this->general_settings['debug'] ==1 && $_GET['debug']==1)
						var_dump($gadgetry_tfuse_post_options);
					//$gadgetry_tfuse_post_options = unserialize($gadgetry_tfuse_post_options);
					if(is_array($gadgetry_tfuse_post_options)){
						if( $gadgetry_tfuse_post_options['gadgetry_single_image'])$post_thumbnail_image[0] = $gadgetry_tfuse_post_options['gadgetry_single_image'];
						elseif( $gadgetry_tfuse_post_options['gadgetry_thumbnail_image'])$post_thumbnail_image[0] = $gadgetry_tfuse_post_options['gadgetry_thumbnail_image'];
					}
					else {
						if ( has_post_thumbnail($id)) {
							$post_thumbnail_image=wp_get_attachment_image_src(get_post_thumbnail_id($id), $size);
						}
						else $post_thumbnail_image[0] = '';
					}

				}
				else{
					if ( has_post_thumbnail($id)) {
						$post_thumbnail_image=wp_get_attachment_image_src(get_post_thumbnail_id($id), $size);
					}
					else $post_thumbnail_image[0] = '';
				}
				$url = $post_thumbnail_image[0];
			}
			return esc_url($url);
		}

		/**
		* Functin get thumble pic
		*/
		function nh_getthumblepic_allsize($id){
			$urls =array();
			$sizes =array('o'=>'original', 't'=>'thumbnail', 'm'=>'medium', 'l'=>'large', 'f'=>'full');
			if($id){
				if($this->general_settings['gadgetry']){
					$gadgetry_tfuse_post_options = get_post_meta($id,'gadgetry_tfuse_post_options',true);
					if(is_array($gadgetry_tfuse_post_options)){
						if( $gadgetry_tfuse_post_options['gadgetry_single_image'])$urls['o'] = $gadgetry_tfuse_post_options['gadgetry_single_image'];
						if( $gadgetry_tfuse_post_options['gadgetry_thumbnail_image'])$urls['t']  = $gadgetry_tfuse_post_options['gadgetry_thumbnail_image'];
					}
					else {
						if ( has_post_thumbnail($id)) {
							foreach ($sizes as $k => $size) {
								$urls[$k]=wp_get_attachment_image_src(get_post_thumbnail_id($id), $size);
								$urls[$k]=$urls[$k][0];
							}

						}

					}
				}
				else{
					if ( has_post_thumbnail($id)) {
						foreach ($sizes as $k => $size) {
								$urls[$k]=wp_get_attachment_image_src(get_post_thumbnail_id($id), $size);
								$urls[$k]=$urls[$k][0];
							}
					}

				}
			}
			return $urls;
		}


		/**
		* Functin get categories
		*/
		function nh_getpostcategories($id){
			$cat =array();
			if($id){
				$cats = get_the_category($id);
				if($cats){
					foreach($cats as $c){
						$cat[] = $c->term_id;
					}
				}
			}
			return ($cat);
		}

		/*
		* Check login
		*/
		function nh_must_login() {
		   echo "You must log in";
		   die();
		}


		/*
		Google map load
		*/
		function nh_ynaa_google_action() {
			$result['type'] = "success";
			  $result['vote_count'] = '$new_vote_count';
			$result = json_encode($result);
			  echo $result;
			  exit();
			  die();
		   if ( !wp_verify_nonce( $_REQUEST['nonce'], "my_user_vote_nonce")) {
			  exit("No naughty business please");
		   }

		   $vote_count = get_post_meta($_REQUEST["post_id"], "votes", true);
		   $vote_count = ($vote_count == '') ? 0 : $vote_count;
		   $new_vote_count = $vote_count + 1;

		   $vote = update_post_meta($_REQUEST["post_id"], "votes", $new_vote_count);

		   if($vote === false) {
			  $result['type'] = "error";
			  $result['vote_count'] = $vote_count;
		   }
		   else {
			  $result['type'] = "success";
			  $result['vote_count'] = $new_vote_count;
		   }

		   if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
			  $result = json_encode($result);
			  echo $result;
		   }
		   else {
			  header("Location: ".$_SERVER["HTTP_REFERER"]);
		   }

		   die();

		}


		/**
		*PUSH Funktion
		*/
		function ny_ynaa_push_action() {

			if(!($this->push_settings['appkey']) || $this->push_settings['appkey'] == '') { _e('No Appkey.', 'nh-ynaa'); die(); }
			if(!($this->push_settings['pushsecret']) || $this->push_settings['pushsecret'] == '') {_e('No Push Secret Key.', 'nh-ynaa');die(); }
			if(!($this->push_settings['pushurl']) || $this->push_settings['pushurl'] == '') { _e('No Push Url.', 'nh-ynaa'); die(); }

			define('APPKEY', esc_attr( $this->push_settings['appkey'] )); // App Key
			define('PUSHSECRET', esc_attr( $this->push_settings['pushsecret'] )); // Master Secret
			define('PUSHURL', esc_attr( $this->push_settings['pushurl'] ));
			$device_types = array('ios', 'android');
			//$device_types = array('ios');
			$cat = '';
			if($_POST['push_cat']) $cat = (implode(',',$_POST['push_cat']));
			$url= 'http://www.blappsta.com/';
			$qry_str = '?bas=push&pkey='.APPKEY.'&pmkey='.PUSHSECRET.'&url='.get_bloginfo('url').'&nhcat='.$cat.'&id='.$_POST['push_post_id'].'&push_text='.urlencode($_POST['push_text']);
			if(ini_get('allow_url_fopen')){
				//echo ('http://www.blappsta.com/?bas=push&pkey='.APPKEY.'&pmkey='.PUSHSECRET.'&url='.get_bloginfo('url').'&cat='.$cat.'&id='.$_POST['push_post_id'].'&push_text='.$_POST['push_text']);
				echo (file_get_contents($url.(($qry_str)).'&nh_mode=fgc'));
			}
			elseif(function_exists('curl_version')){
				$ch = curl_init();
				// Set query data here with the URL
				curl_setopt($ch, CURLOPT_URL, $url . $qry_str.'&nh_mode=curl');

				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_TIMEOUT, '3');
				$push_response = trim(curl_exec($ch));
				curl_close($ch);
				echo($push_response);
			}
			else {
				/*echo '<script type="text/javascript">';
				echo 'window.open("'.$url.$qry_str.'&nh_mode=js");';
				echo '</script>';*/
				echo 'nomodul';
				echo ' '.$url . ($qry_str).'&nh_mode=js';
				//_e('Error: No supported Modul installed.', 'nh-ynaa');
			}
			die();
			return;
		}

    /**
    *PUSH Funktion have to combine both
    */
    function ny_ynaa_push_action2($postid) {

      if(!($this->push_settings['appkey']) || $this->push_settings['appkey'] == '') {
           //_e('No Appkey.', 'nh-ynaa');
           return 2;
      }
      if(!($this->push_settings['pushsecret']) || $this->push_settings['pushsecret'] == '') {
        //_e('No Push Secret Key.', 'nh-ynaa');
        return 3;
      }
      if(!($this->push_settings['pushurl']) || $this->push_settings['pushurl'] == '') {
        // _e('No Push Url.', 'nh-ynaa');
        return 4;
      }

      define('APPKEY', esc_attr( $this->push_settings['appkey'] )); // App Key
      define('PUSHSECRET', esc_attr( $this->push_settings['pushsecret'] )); // Master Secret
      define('PUSHURL', esc_attr( $this->push_settings['pushurl'] ));
      $device_types = array('ios', 'android');
      //$device_types = array('ios');
      $cat = wp_get_post_categories($postid);
      if($cat){
        $cat = implode(',',$cat);
      }
      $url= 'http://www.blappsta.com/';
      $qry_str = '?bas=push&pkey='.APPKEY.'&pmkey='.PUSHSECRET.'&url='.get_bloginfo('url').'&nhcat='.$cat.'&id='.$postid.'&push_text='.urlencode(get_the_title($postid));
      //return $qry_str;
      if(ini_get('allow_url_fopen')){
        $blappsta_return = (file_get_contents($url.(($qry_str)).'&nh_mode=fgc'));

      }
      elseif(function_exists('curl_version')){
        $ch = curl_init();
        // Set query data here with the URL
        curl_setopt($ch, CURLOPT_URL, $url . $qry_str.'&nh_mode=curl');

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, '3');
        $push_response = trim(curl_exec($ch));
        curl_close($ch);
        $blappsta_return=($push_response);
      }
      else {
        return 5;
      }
      if($blappsta_return){
        $blappsta_return = @json_decode($blappsta_return,true);
        if(isset($blappsta_return['push status']['error']['error_code'])){
          return $blappsta_return['push status']['error']['error_code'];
        }
        else return 7;
      }
      else return 6;

    }

		/*
		 * Function to get Lan and LAt
		*/
		function getLatLng($address){


			$address = str_replace(" ", "+", $address);

	 		$url='http://maps.googleapis.com/maps/api/geocode/json?address='.$address.'&sensor=false';
      if(ini_get('allow_url_fopen')){
  			$source = file_get_contents($url);
  			$obj = json_decode($source);
  			if($obj != null){
  				$LATITUDE = $obj->results[0]->geometry->location->lat;
  				$LONGITUDE = $obj->results[0]->geometry->location->lng;
  			}else{
  				$LATITUDE = 0;
  				$LONGITUDE = 0;
  			}
      }
      else{
          $LATITUDE = 0;
          $LONGITUDE = 0;
      }
  		return array('lat'=>$LATITUDE,'lng'=>$LONGITUDE);

		}/* END function getLatLon() */

		/**
		 * Helpfunction for short text
		*/
		public function shortenText($text, $limit=25) { // Function name ShortenText
			$chars_limit = $limit; // Character length
			$chars_text = strlen($text);
			if ($chars_text > $chars_limit) {
				$text = $text." ";
				$text = substr($text,0,$chars_limit);
				$text = substr($text,0,strrpos($text,' '));
				$text = $text."...";
			} // Ellipsis
			 return $text;
		}  // END public function shortenText()

		public function ReSizeImagesInHTML($HTMLContent,$MaximumWidth,$MaximumHeight) {

			// find image tags
			preg_match_all('/<img[^>]+>/i',$HTMLContent, $rawimagearray,PREG_SET_ORDER);

			// put image tags in a simpler array
			$imagearray = array();
			for ($i = 0; $i < count($rawimagearray); $i++) {
				array_push($imagearray, $rawimagearray[$i][0]);
			}

			// put image attributes in another array
			$imageinfo = array();
			foreach($imagearray as $img_tag) {

				preg_match_all('/(src|width|height)=("[^"]*")/i',$img_tag, $imageinfo[$img_tag]);
			}

			// combine everything into one array
			$AllImageInfo = array();
			foreach($imagearray as $img_tag) {

				$ImageSource = str_replace('"', '', $imageinfo[$img_tag][2][0]);
				$OrignialWidth = str_replace('"', '', $imageinfo[$img_tag][2][1]);
				$OrignialHeight = str_replace('"', '', $imageinfo[$img_tag][2][2]);

				$NewWidth = $OrignialWidth;
				$NewHeight = $OrignialHeight;
				$AdjustDimensions = "F";

				if($OrignialWidth > $MaximumWidth) {
					$diff = $OrignialWidth-$MaximumHeight;
					$percnt_reduced = (($diff/$OrignialWidth)*100);
					$NewHeight = floor($OrignialHeight-(($percnt_reduced*$OrignialHeight)/100));
					$NewWidth = floor($OrignialWidth-$diff);
					$AdjustDimensions = "T";
				}

				if($OrignialHeight > $MaximumHeight) {
					$diff = $OrignialHeight-$MaximumWidth;
					$percnt_reduced = (($diff/$OrignialHeight)*100);
					$NewWidth = floor($OrignialWidth-(($percnt_reduced*$OrignialWidth)/100));
					$NewHeight= floor($OrignialHeight-$diff);
					$AdjustDimensions = "T";
				}

				$thisImageInfo = array('OriginalImageTag' => $img_tag , 'ImageSource' => $ImageSource , 'OrignialWidth' => $OrignialWidth , 'OrignialHeight' => $OrignialHeight , 'NewWidth' => $NewWidth , 'NewHeight' => $NewHeight, 'AdjustDimensions' => $AdjustDimensions);
				array_push($AllImageInfo, $thisImageInfo);
			}

			// build array of before and after tags
			$ImageBeforeAndAfter = array();
			for ($i = 0; $i < count($AllImageInfo); $i++) {

				if($AllImageInfo[$i]['AdjustDimensions'] == "T") {
					$NewImageTag = str_ireplace('width="' . $AllImageInfo[$i]['OrignialWidth'] . '"', 'width="' . $AllImageInfo[$i]['NewWidth'] . '"', $AllImageInfo[$i]['OriginalImageTag']);
					$NewImageTag = str_ireplace('height="' . $AllImageInfo[$i]['OrignialHeight'] . '"', 'height="' . $AllImageInfo[$i]['NewHeight'] . '"', $NewImageTag);

					$thisImageBeforeAndAfter = array('OriginalImageTag' => $AllImageInfo[$i]['OriginalImageTag'] , 'NewImageTag' => $NewImageTag);
					array_push($ImageBeforeAndAfter, $thisImageBeforeAndAfter);
				}
			}

			// execute search and replace
			for ($i = 0; $i < count($ImageBeforeAndAfter); $i++) {
				$HTMLContent = str_ireplace($ImageBeforeAndAfter[$i]['OriginalImageTag'],$ImageBeforeAndAfter[$i]['NewImageTag'], $HTMLContent);
			}

			return $HTMLContent;

			}

		//Action on publish posts
		public function nh_ynaa_publish_posts($ID=0, $post=null ){
		  if($ID){
         global $nh_push_return;
  		  if($this->push_settings['autopush'] && !get_post_meta( $ID, 'nh_blappsta_send_push', true )){

  		    $nh_push_return = $this->ny_ynaa_push_action2($ID);
          add_post_meta( $ID, 'nh_blappsta_send_push', time(),true );

        }
        add_filter('redirect_post_location',array($this,'nh_add_get_var2'));
       }
		}


    function nh_add_get_var($loc) {
       return add_query_arg( 'nh_pm', 1, $loc );
      }

    function nh_add_get_var2($loc) {
      global $nh_push_return;
       return add_query_arg( 'nh_pm', $nh_push_return, $loc );
      }

		/**
		 * Find taxonomies from which to retrieve categories. Will use multiple taxonomies depending on setup.
		 * Method is static so it can be called from activation hook.
		 */
		static function nh_find_taxonomies_with_avada($use_avada) {
			$result = array('category');
			if($use_avada) {
				$result[] = 'portfolio_category';
			}
			return $result;
		}

		public function nh_find_taxonomies() {
			$is_avada_active = wp_get_theme()->Name === 'Avada';
			/* refer to static method */
			return NH_YNAA_Plugin::nh_find_taxonomies_with_avada($this->general_settings['avada-categories'] && $is_avada_active);
		}

		/**
		 * Find category info using get_term(). Will use multiple taxonomies depending on setup.
		 * @see get_term()
		 */
		function nh_get_category($cat_id) {
			/* try wordpress category first */
			$result = get_term((int)$cat_id, 'category');
			if($result) {
				return $result;
			}

			/* or, try Avada portfolio category next */
			if(taxonomy_exists('portfolio_category')) {
				return get_term((int)$cat_id, 'portfolio_category');
			}

			/* couldn't find category */
			return null;
		}

		/**
		 * Check whether this is a category using term_exists(). Will use multiple taxonomies depending on setup.
		 * @see term_exists()
		 */
		function nh_is_category($cat_id) {
			/* try wordpress category first */
			$result = term_exists((int)$cat_id, 'category');
			if($result) {
				return true;
			}

			/* or, try Avada portfolio category next */
			if($this->general_settings['avada-categories']) {
				return term_exists((int)$cat_id, 'portfolio_category');
			}

			/* couldn't find category */
			return false;
		}

    //Admin notice
    public function nh_ynaa_admin_notice(){
      if(isset($_GET['nh_pm'])){
        echo '<div class="updated"><p>';
        switch($_GET['nh_pm']){
          case 0: _e( 'Push send successful.', 'nh-ynaa' ); break;
          default: _e ('Unknown error sending the push message.','nh-ynaa');  echo ' (Error code:'.$_GET['nh_pm'].')';  break;
        }

        echo '</p></div>';

      }
    }


    } // END class NH YNAA Plugin
} // END if(!class_exists('NH_YNAA_Plugin))

if(class_exists('NH_YNAA_Plugin'))
{
    // Installation and uninstallation hooks
    register_activation_hook(__FILE__, array('NH_YNAA_Plugin', 'nh_ynaa_activate'));
    register_deactivation_hook(__FILE__, array('NH_YNAA_Plugin', 'nh_ynaa_deactivate'));

	//add_action( 'wpmu_new_blog', array('NH_YNAA_Plugin','nh_new_blog'),100,6);




    // instantiate the plugin class
    $nh_ynaa = new NH_YNAA_Plugin();
	add_action( 'plugins_loaded',array($nh_ynaa,'nh_update_db_check'));

  //add Notice
   add_action('admin_notices', array($nh_ynaa,'nh_ynaa_admin_notice'));

	//publish Post
	add_action('publish_post',array($nh_ynaa,'nh_ynaa_publish_posts'));

	// Add a link to the settings page onto the plugin page
	if(isset($nh_ynaa))
	{
		// Add the settings link to the plugins page
		function nh_ynaa_plugin_settings_link($links)
		{
			$settings_link = '<a href="options-general.php?page=nh_ynaa_plugin_options">'.(__('Settings')).'</a>';
			array_unshift($links, $settings_link);
			return $links;
		}

		$plugin = plugin_basename(__FILE__);
		add_filter("plugin_action_links_$plugin", 'nh_ynaa_plugin_settings_link');

		//Add Query vars
		function nh_ynaa_add_query_vars_filter( $vars ){
			  $vars[] = QUERY_VARS_YNAA;
			  return $vars;
		}
		add_filter( 'query_vars', 'nh_ynaa_add_query_vars_filter' );
		if(!empty($_GET[QUERY_VARS_YNAA])) {
			add_action('template_redirect', array($nh_ynaa, 'nh_ynaa_template_redirect'),1);
		}
	}
}


function nh_ynaa_load_textdomain()
{
	load_plugin_textdomain('nh-ynaa', FALSE, dirname( plugin_basename( __FILE__ ) ) . '/lang');

}
add_action( 'plugins_loaded', 'nh_ynaa_load_textdomain');


add_action( 'admin_footer', 'nh_action_javascript' );
function nh_action_javascript() {
	global $post;
	$cat = wp_get_post_categories($post->ID);
	if($cat){
		$cat = implode(',',$cat);
	}

?>
<script type="text/javascript" >
	<?php
	if(isset($post->ID)) {
	?>
jQuery(document).ready(function($) {

	//alert('<?php echo $post->ID;  ?>');

	// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php

	$('#nh_ynaa_sendpush').click(function(e) {
		if(<?php if($post->post_status== 'publish') echo 1; else echo 0;  ?>)	{
			if($('#nh_ynaa_pushtext').val()=='') alert('<?php _e('Insert Pushtext!', 'nh-ynaa'); ?>');
			else {
				$(this).prop('disabled', true);
				//alert('<?php _e('Pleas wait!'); ?>');
				jQuery('#nh-push-dialog span').show();
				jQuery.ajax({
					 type : "post",
					 url : ajaxurl,
					 //dataType:"json",
					 data : {action: "ny_ynaa_push_action", push_post_id:<?php echo $post->ID; ?>, push_cat:[<?php echo $cat; ?>] , push_text:$('#nh_ynaa_pushtext').val()},
					 success: function(data,textStatus,jqXHR ) {
						jQuery('#nh-push-dialog span').hide();

						if(data.substr(0,7)=='nomodul'){
							//alert("window open"+ data+data.substr(8));
							//window.open(data.substr(8));
							jQuery.get( data.substr(8), function( data2 ) {
							 // jQuery( ".result" ).html( data );
							  alert( "Load was performed." );
							});
							alert("Push send success.");

						}
						else{

							if(data && data.indexOf("Send successful")!=-1) alert('Push send success.');
							else alert(data);
						}
						$('#nh_ynaa_sendpush').prop('disabled', false);
						 //console.log(data);
					 }
				  })   ;
			}
		}
		else alert('<?php _e('You have to publish the Post first.!', 'nh-ynaa'); ?>');
		//alert('Got this from the server: ' + e);
	});
});
<?php
}
?>
</script>
<?php

}


add_action('wp_ajax_nh_search_action', 'nh_search_action');

add_action( 'wp_enqueue_scripts', 'nh_blappsta_add_stylesheet' );
function nh_blappsta_add_stylesheet() {

wp_enqueue_style( 'blappsta-style-front', plugins_url('css/blappsta.css', __FILE__ ) , array(),'1.0');

}
//Widget
include ('classes/nh-widget.php');
// register widget
add_action('widgets_init', create_function('', 'return register_widget("NH_Blappsta_Widget");'));

//add_action('wp_ajax_nopriv_my_action', 'my_action_callback');



//add_action('wp_ajax_ny_ynaa_push_action', 'ny_ynaa_push_action');
//add_action('wp_ajax_nopriv_my_action', 'my_action_callback');

/*Nur für OPEL */

add_action( 'wp_enqueue_scripts', 'my_enqueue' );
function my_enqueue() {
  /*  if( 'index.php' != $hook ) {
  // Only applies to dashboard panel
  return;
    }
    */
 

  // in JavaScript, object properties are accessed as ajax_object.ajax_url, ajax_object.we_value
  wp_localize_script( 'ajax-script', 'ajax_object',
            array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'we_value' => 1234 ) );
}

// Same handler function...
add_action( 'wp_ajax_my_action', 'my_action_callback' );
add_action( 'wp_ajax_nopriv_my_action', 'my_action_callback' );
function my_action_callback() {

      define('APPKEY', 'DqGr_G2SR42eWPqmnfh6Fw'); // App Key
      define('PUSHSECRET', 'qLyftFGzTfeDjFbhjphcNw'); // Master Secret
     // define('PUSHURL', esc_attr( $this->push_settings['pushurl'] ));
      $device_types = array('ios', 'android');
      //$device_types = array('ios');
      $cat = 1;
      $url= 'http://www.blappsta.com/';
      ;

      $qry_str = '?bas=push&pkey='.APPKEY.'&pmkey='.PUSHSECRET.'&url=http://projekte.nebelhorn.com/ooh&nhcat='.$cat.'&id='.$_POST['id'].'&push_text='.urlencode($_POST['text']);
      if(ini_get('allow_url_fopen')){
        //echo ('http://www.blappsta.com/?bas=push&pkey='.APPKEY.'&pmkey='.PUSHSECRET.'&url='.get_bloginfo('url').'&cat='.$cat.'&id='.$_POST['push_post_id'].'&push_text='.$_POST['push_text']);
        echo (file_get_contents($url.(($qry_str)).'&nh_mode=fgc'));
      }
      die();

}


?>