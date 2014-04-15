<?php
/*
Plugin Name: NH YNAA Plugin
Version: 0.5.1
Plugin URI: http://wordpress.org/plugins/yournewsapp/
Description: yourBlogApp/yourNewsApp - The Wordpress Plugin for yourBlogApp/yourNewsApp
Author: Nebelhorn Medien GmbH
Author URI: http://www.nebelhorn.com
Min WP Version: 3.0
License: GPL2
*/

//Version Number
global $nh_ynaa_version;
$nh_ynaa_version = "0.5.1";
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
		private $plugin_options_key = 'nh_ynaa_plugin_options';		//Plugin Settings
		private $plugin_settings_tabs = array();					//All Tabs for the Plugin
		public $appmenus_pre = array();								//Vordefinerte App Men�s
		
		
		public $tabs = array(
			// The assoc key represents the ID
			// It is NOT allowed to contain spaces
			 'EXAMPLE' => array(
				 'title'   => 'TEST ME!'
				,'content' => 'FOO'
			 )
		);
		
		/*public static $lang_de = array(
			'Menu'=>'Menü',
			'Please wait...'=>'Bitte warten…',
			'The data are updated' => 'Die Daten werden aktualisiert',
			'More' => 'Mehr',
			'all-day' => 'ganztägig',
			'Tip'=>'Hinweis',
			'This feed has been deleted' => 'Dieser Feed wurde gelöscht',
			'The event has been removed from the calendar.'=>'Die Veranstaltung wurde aus dem Kalender entfernt.',
			'The event was added to the calendar.' => 'Die Veranstaltung wurde dem Kalender hinzugefügt.',
			'Today'=>'Heute',
			'Yesterday' => 'Gestern',
			'The day before yesterday' =>'Vorgestern',
			'This week' =>'Diese Woche',
			'Last week'=>'Letzte Woche',
			'The week before last' =>'Vorletzte Woche',
			'Last month'=>'Letzter Monat',
			'This month' => 'Dieser Monat',
			'Second last month' =>'Vorletzter Monat',
			'Before two months' => 'Vorvorletzter Monat',
			'This year' => 'Dieses Jahr',
			'Last year' => 'Letztes Jahr',
			'Older than last year' => 'Älter als letztes Jahr',
			'Tomorrow' => 'Morgen',
			'The day after tomorrow' => 'Übermorgen',
			'Next week' => 'Nächste Woche',
			'The week after next' =>'Übernächste Woche',
			'Next month' => 'Nächster Monat',
			'Over the next month' => 'Übernächster Monat',
			'Over two months' => 'Überübernächster Monat',
			'Next year' => 'Nächstes Jahr',
			'Later next year' => 'Später als Nächstes Jahr',
			'Cancel' => 'Abbrechen',
			'Finished' => 'Fertig',
			'Comment'=>'Kommentar',
			'Show' =>'Anzeigen',
			'Comments'=>'Kommentare',
			'required' =>'erforderlich',
			'Name' => 'Name',
			'The e-mail address is not correct' => 'Die E-Mail-Adresse ist nicht korrekt',
			'Please enter your name.' => 'Bitte gib deinen Namen an.',
			'Please enter your comment.' => 'Bitte gib deinen Kommentar an.',
			'Comments are being loaded ...' => 'Kommentare werden geladen...',
			'Clock'=>'Uhr',
			'Welcome to'=>'Willkommen bei', 
			'There was an error.' => 'Es ist ein Fehler aufgetreten.',
			'Redeem'=>'Einlösen',
			'Add event to calendar'=>'Veranstaltung zum Kalender hinzufügen',
			'Add to calendar'=>"Zum Kalender hinzufügen",
			'Remove event from calendar' => "Veranstaltung vom Kalender entfernen",
			'from'=>"von",
			'to' => 'bis',
			'starting at' => 'ab',
			'Replay' => 'Antwort',
			'You have the location services for the app disabled. You can turn them back on in the settings of the device.'=>'Sie haben die Ortungsdienste für die App deaktiviert. Sie können diese in den Einstellungen des Geräts wieder aktivieren.',
			'Login'=>'Anmelden',
			'Logout'=>'Abmelden',
			'Username' => 'Benutzername',
			'Password' => 'Passwort',
			'The input is incomplete' => 'Die Eingabe ist unvollständig',
			'Thanks' => 'Danke'
			

		);*/
				
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
			add_action( 'wpmu_new_blog', array(&$this,'nh_new_blog'));    
			
        } // END public function __construct

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
						NH_YNAA_Plugin::nh_update_db_check();
					}
					switch_to_blog($old_blog);
					return;
				}   
			} 
			NH_YNAA_Plugin::_nh_ynaa_activate();      
			NH_YNAA_Plugin::nh_update_db_check();
		}// END public static function nh_ynaa_activate
		
        /**
         * Activate the plugin
         */
        public static function _nh_ynaa_activate()
        {
			//ADD version nummer to WP options
            global $nh_ynaa_version;	
			//Preset app menu
			$menu_array[0] = array('title'=>__('Browse','nh-ynaa'),'status'=>1,'pos'=>1, 'id'=>0, 'type'=>'app', 'type_text'=>'App');
			$menu_array[1] = array('title'=>__('Subscription','nh-ynaa'),'status'=>1,'pos'=>2, 'id'=>1, 'type'=>'app', 'type_text'=>'App');
		//	$menu_array[2] = array('title'=>__('Map','nh-ynaa'),'status'=>1,'pos'=>3, 'id'=>-98, 'type'=>'map', 'type_text'=>'App');
	//		$menu_array[5] = array('title'=>__('Events','nh-ynaa'),'status'=>1,'pos'=>3, 'id'=>1, 'type'=>'app', 'type_text'=>'App');
			$nh_ynaa_menu_settings = array('menu'=>$menu_array,'ts'=>time());
			
			//Main Pre Setting for App
			include('include/default_css.php');
			/*foreach(self::$lang_de as $k=>$v){
				$lang_en[$k]=$k;
				
			}*/
			$lang = 'en';
			if(get_bloginfo('language')=='de_DE') $lang='de';
			$nh_ynaa_general_settings=(array('sort'=>1,'c1'=>'#3677a0', 'cm'=>'#3677a0','c2'=>'#ffffff', 'cn'=>'#ffffff', 'ct'=>'#000000', 'ch'=>'#000000', 'csh'=>'#000000','ts'=>time(), 'css'=> $css,'logo'=>'', 'comments'=>0, 'logo'=> plugins_url( 'img/yba_yna_yca_applogo.png' , __FILE__ ), 'lang_array'=>$lang_en, 'lang'=>$lang, 'homescreentype'=>0 ));
			
			
			//Preset teaser
			$nh_ynaa_teaser_settings = array('ts'=>0,'teaser'=>false);
			
			//ADD Options in Wp-Option table
			update_option('nh_ynaa_plugin_version', $nh_ynaa_version);	
			add_option('nh_ynaa_general_settings', $nh_ynaa_general_settings);	
			add_option('nh_ynaa_menu_settings', $nh_ynaa_menu_settings);
			
			$args = array(
				'numberposts' => 3,
				'offset' => 0,				
				'orderby' => 'post_date',
				'order' => 'DESC',				
				'post_type' => 'post',
				'post_status' => 'publish' );
		
			$recent_posts = wp_get_recent_posts( $args, ARRAY_A );
			
			if($recent_posts){
				foreach($recent_posts as $recent){
					$nh_ynaa_teaser_settings['teaser'][]=$recent["ID"];
				}
			}
			add_option('nh_ynaa_teaser_settings', $nh_ynaa_teaser_settings);	
				
			$nh_ynaa_homepreset_settings = array($ts = 0);
			$args = array(
				'type'                     => 'post',
	
				'orderby'                  => 'name',
				'order'                    => 'ASC',
				'hide_empty'               => 1,
				'hierarchical'             => 1,
				'taxonomy'                 => 'category'
			
			); 
			$categories = get_categories( $args );
			if($categories){
				$i=1;
				foreach($categories as $category){
					 $nh_ynaa_homepreset_settings['items'][] = array('img'=>'', 'title'=>$category->name, 'allowRemove'=>1, 'id' => $category->term_id, 'type'=>'cat', 'id2'=>$i);
					 //$nh_ynaa_categories_settings['items'][] = array('img'=>'', 'title'=>$category->name, );
					 $i++;
				}
			}
			add_option('nh_ynaa_homepreset_settings', $nh_ynaa_homepreset_settings);	
				
			add_option('nh_ynaa_push_settings', array());	
			add_option('nh_ynaa_categories_settings', array());		
			
		
				
			
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
			
			//set app menu
			$this->appmenus_pre[0] = array('title'=>__('Browse','nh-ynaa'),'status'=>1,'pos'=>1, 'id'=>0, 'type'=>'app', 'type_text'=>'App', 'link-typ'=>'cat');
			$this->appmenus_pre[1] = array('title'=>__('Subscription','nh-ynaa'),'status'=>1,'pos'=>2, 'id'=>-99, 'type'=>'app', 'type_text'=>'App', 'link-typ'=>'cat');
			
			if(isset($this->general_settings['social_fbid'],$this->general_settings['social_fbsecretid'],$this->general_settings['social_fbappid'])) $this->appmenus_pre[3] = array('title'=>__('Facebook','nh-ynaa'),'status'=>1,'pos'=>3, 'id'=>-2, 'type'=>'fb', 'type_text'=>'Facebook', 'link-typ'=>'fb');
			$this->appmenus_pre[5] = array('title'=>__('Events','nh-ynaa'),'status'=>0,'pos'=>3, 'id'=>-1, 'type'=>'events', 'type_text'=>'App');
			if(isset($this->general_settings['location'])) $this->appmenus_pre[6] = array('title'=>__('Map','nh-ynaa'),'status'=>1,'pos'=>3, 'id'=>-98, 'type'=>'map', 'type_text'=>__('App', 'ynaa'), 'link-typ'=>'cat');
			$this->appmenus_pre[6] = array('title'=>__('Extern URL','nh-ynaa'),'status'=>1,'pos'=>6, 'id'=>-3, 'type'=>'webview', 'type_text'=>'URL', 'link-typ'=>'cat');
			
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
		function nh_new_blog($blog_id, $user_id, $domain, $path, $site_id, $meta ) {
			global $wpdb;
			if (is_plugin_active_for_network('nh_ynaa/nh_ynaa_plugin.php')) {
				$old_blog = $wpdb->blogid;
				switch_to_blog($blog_id);
				$this->_nh_ynaa_activate();
				switch_to_blog($old_blog);
			}
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
			add_settings_section( 'app_homepreset_settings', __('App Homepreset Settings<br><span>(Only if in startscreen view categories selected)</span>', 'nh-ynaa'), array( &$this, 'nh_ynaa_homepreset_settings_desc' ), $this->homepreset_settings_key );
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
			add_settings_field( 'ynaa-logo', __('Select Logo', 'nh-ynaa'). '('.$this->logo_image_width.'x'.$this->logo_image_height.')', array( &$this, 'nh_ynaa_field_general_option_logo' ), $this->general_settings_key, 'logo_setting', array('field'=>'logo') );
			//Color
			add_settings_section( 'app_settings', __('Color And Style Settings', 'nh-ynaa'), array( &$this, 'nh_ynaa_section_general_desc' ), $this->general_settings_key );
			add_settings_field( 'ynaa-c1', __('Primary Color', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_option_color' ), $this->general_settings_key, 'app_settings' , array('field'=>'c1'));
			add_settings_field( 'ynaa-c2', __('Secondary Color', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_option_color' ), $this->general_settings_key, 'app_settings', array('field'=>'c2') );
			
			add_settings_field( 'ynaa-cn', __('Navigation Bar Color', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_option_color' ), $this->general_settings_key, 'app_settings', array('field'=>'cn') );
			add_settings_field( 'ynaa-cm', __('Menu Text Color', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_option_color' ), $this->general_settings_key, 'app_settings', array('field'=>'cm') );
			add_settings_field( 'ynaa-ch', __('Title 1 Color', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_option_color' ), $this->general_settings_key, 'app_settings', array('field'=>'ch') );
			add_settings_field( 'ynaa-csh', __('Title 2 Color', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_option_color' ), $this->general_settings_key, 'app_settings', array('field'=>'csh') );
			add_settings_field( 'ynaa-ct', __('Flowing Text Color', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_option_color' ), $this->general_settings_key, 'app_settings', array('field'=>'ct') );
			add_settings_field( 'ynaa-css', __('CSS Style', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_option_css' ), $this->general_settings_key, 'app_settings' , array('field'=>'css'));
			//Hidden Fields
			add_settings_field( 'ynaa-ts', null, array( &$this, 'nh_ynaa_field_general_option_hidden' ), $this->general_settings_key, 'app_settings', array('field'=>'ts') );
			//Social Network
			add_settings_section( 'social_settings', __('Social Network', 'nh-ynaa'), array( &$this, 'nh_ynaa_section_general_social' ), $this->general_settings_key );			
			add_settings_field( 'ynaa-social_fbsecretid', __('Facebook App Secret', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_social' ), $this->general_settings_key, 'social_settings', array('field'=>'social_fbsecretid') );
			add_settings_field( 'ynaa-social_fbappid', __('Facebook App ID', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_social' ), $this->general_settings_key, 'social_settings', array('field'=>'social_fbappid') );
			add_settings_field( 'ynaa-social_fbid', __('Facebook page ID', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_social' ), $this->general_settings_key, 'social_settings', array('field'=>'social_fbid') );
			
			//Extras
			add_settings_section( 'extra_settings', __('Extras', 'nh-ynaa'), array( &$this, 'nh_ynaa_section_general_extra' ), $this->general_settings_key );
			add_settings_field( 'ynaa-lang', __('Language', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_language' ), $this->general_settings_key, 'extra_settings' , array('field'=>'lang'));
			add_settings_field( 'ynaa-homescreentype', __('Startscreen view', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_homescreentype' ), $this->general_settings_key, 'extra_settings' , array('field'=>'homescreentype'));
			add_settings_field( 'ynaa-sorttype', __('Startscreen articles sorty by <br><span>(Only if startscreen view is articles)</span>', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_sorttype' ), $this->general_settings_key, 'extra_settings' , array('field'=>'sorttype'));
			
			global $nh_ynaa_db_version;			
			 if (get_option( 'nh_ynaa_db_version' ) == $nh_ynaa_db_version) {
				add_settings_field( 'ynaa-location', __('Enable locations and activate location metabox in posts', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_extra_sort' ), $this->general_settings_key, 'extra_settings' , array('field'=>'location'));
			 }
			add_settings_field( 'ynaa-eventplugin', __('Select your Event Manager:', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_eventplugin' ), $this->general_settings_key, 'extra_settings' , array('field'=>'eventplugin'));
			add_settings_field( 'ynaa-order_value', __('Order posts on overview page by', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_extra_order' ), $this->general_settings_key, 'extra_settings' , array('field'=>order_value));
			add_settings_field( 'ynaa-sort', __('Group by date', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_extra_sort' ), $this->general_settings_key, 'extra_settings' , array('field'=>'sort'));
			add_settings_field( 'ynaa-extra', __('Allow comments in App', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_general_extra_sort' ), $this->general_settings_key, 'extra_settings' , array('field'=>'comments'));
			
						
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
						$img = wp_get_image_editor( $file['file'] ); // Return an implementation that extends <tt>WP_Image_Editor</tt>
						if ( ! is_wp_error( $img ) ) {							
							$img->resize( $this->logo_image_width, $this->logo_image_height, true );
							$f = $img->save( $file['file']);
						}
						$plugin_options[$keys[$i]] = dirname($file['url']).'/'.basename($f['path']);     
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
		 * Registers the Push settings and appends the
		 * key to the plugin settings tabs array.
		 */
		function nh_ynaa_register_push_settings() {
			$this->plugin_settings_tabs[$this->push_settings_key] = __('Push & iBeacon','nh-ynaa');			
			register_setting( $this->push_settings_key, $this->push_settings_key );
			//Push
			add_settings_section( 'app_push_settings', __('App Push Settings', 'nh-ynaa'), array( &$this, 'nh_ynaa_push_settings_desc' ), $this->push_settings_key );	
			add_settings_field( 'ynaa-appkey', __('App Key', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_push_option' ), $this->push_settings_key, 'app_push_settings' , array('field'=>appkey));
			add_settings_field( 'ynaa-pushsecret', __('PUSHSECRET', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_push_option' ), $this->push_settings_key, 'app_push_settings' , array('field'=>pushsecret));
			add_settings_field( 'ynaa-pushurl', __('PUSHURL', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_push_option' ), $this->push_settings_key, 'app_push_settings' , array('field'=>pushurl));
			add_settings_field( 'ynaa-pushshow', __('Show Push Metabox', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_push_checkbox' ), $this->push_settings_key, 'app_push_settings' , array('field'=>pushshow));
			//iBeacon
			add_settings_section( 'app_ibeacon_settings', __('iBeacon Settings', 'nh-ynaa'), array( &$this, 'nh_ynaa_push_settings_desc' ), $this->push_settings_key );	
			add_settings_field( 'ynaa-ts', null, array( &$this, 'nh_ynaa_field_general_option_hidden' ), $this->general_settings_key, 'app_ibeacon_settings', array('field'=>ts) );
			add_settings_field( 'ynaa-uuid', __('UUID ', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_push_option' ), $this->push_settings_key, 'app_ibeacon_settings' , array('field'=>uuid));
			add_settings_field( 'ynaa-welcome', __('Welcome text ', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_push_option_textarea' ), $this->push_settings_key, 'app_ibeacon_settings' , array('field'=>welcome));
			add_settings_field( 'ynaa-silent', __('Silent intervall (sec) ', 'nh-ynaa'), array( &$this, 'nh_ynaa_field_push_option' ), $this->push_settings_key, 'app_ibeacon_settings' , array('field'=>silent));
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
		 * The following methods provide descriptions
		 * for their respective sections, used as callbacks
		 * with add_settings_section
		 */
	 	/*function nh_ynaa_section_grcode(){
			echo '<div>88888</div>';
		}*/
		function nh_ynaa_section_general_logo() {  }
		function nh_ynaa_section_general_social() { }
		function nh_ynaa_section_general_extra() { }
		function nh_ynaa_section_general_desc() {  }
		function nh_ynaa_push_settings_desc() {   }
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
			if (function_exists('do_accordion_sections')) {
				do_accordion_sections( 'nav-menus', 'side', null );
			}
			$ynaa_menu = '';
			include('include/teaser.php');
		}
		
		function nh_ynaa_categories_settings_desc(){
			$categories = get_categories(array('orderby'=>'name', 'order'=>'ASC', 'hide_empty'=>0));
			if($categories){
				echo '<div id="categorie-div-con" class="categorie-div-con"><ul>';
				foreach($categories as $category){
					 //var_dump($this->categories_settings);
					 if(!$this->categories_settings[$category->term_id]['cat_name']) $this->categories_settings[$category->term_id]['cat_name']= $category->cat_name;
					 
			?>
            		<li>
                    	<div class="image-div" id="<?php echo 'image-div'.$category->term_id;  ?>" style="background-image:url('<?php echo $this->categories_settings[$category->term_id]['img'] ?>')" data-link="<?php echo $category->term_id;  ?>" >
                        	<div class="ttitle"><?php echo ($this->categories_settings[$category->term_id]['cat_name']); ?></div>
                        </div>
                        <div>
                        	<div><a id="upload_image_button<?php echo $category->term_id; ?>" class="upload_image_button" href="#" name="<?php echo $this->categories_settings_key; ?>_items_<?php echo $category->term_id; ?>_img" data-image="<?php echo '#image-div'.$category->term_id;  ?>"   ><?php _e('Set default image for category','nh-ynaa'); ?></a>
           											<input type="hidden" value="<?php echo $this->categories_settings[$category->term_id]['img'] ?>" id="<?php echo $this->categories_settings_key; ?>_items_<?php echo $category->term_id; ?>_img" name="<?php echo $this->categories_settings_key; ?>[<?php echo $category->term_id; ?>][img]" data-id="image-div<?php echo $category->term_id; ?>" data-link="<?php echo $category->term_id;  ?>" /></div>
                                                   
                                                    
                            <?php  echo '<div id="reset-cat-img-link-cont_'.$category->term_id.'">';
								if($this->categories_settings[$category->term_id]['img']) echo '<a href="'.$category->term_id.'" class="reset-cat-img-link">'.(__('Reset image', 'nh-ynaa')).'</a>'; else echo '<br>';
								echo '</div>'; ?>
                            <div>
                            	<div><input type="text" class="cat-name-input" value="<?php echo $this->categories_settings[$category->term_id]['cat_name']; ?>" name="<?php echo $this->categories_settings_key; ?>[<?php echo $category->term_id; ?>][cat_name]"></div>
                            	<div class="hide-cat-div"><?php _e('Hide this category and all posts in this categorie in the app:','nh-ynaa'); ?><br>
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
									<label for="hidecat1"><?php _e('Yes', 'nh-ynaa');  ?></label> <input type="radio" id="hidecat1" name="<?php echo $this->categories_settings_key.'['.$category->term_id.'][hidecat]';?>" <?php echo $yesradio; ?> value="1">
                                    <label for="hidecat0"><?php _e('No', 'nh-ynaa'); ?></label> <input type="radio" id="hidecat0" name="<?php echo $this->categories_settings_key.'['.$category->term_id.'][hidecat]';?>" value="0" <?php echo $noradio; ?>>
								</div>
                                <div class="use-cat-image-div">
                                	<input type="radio" value="0"  name="<?php echo $this->categories_settings_key.'['.$category->term_id.'][usecatimg]';?>" id="use_cat_image1" <?php if(!isset($this->categories_settings[$category->term_id]['usecatimg']) || !$this->categories_settings[$category->term_id]['usecatimg']) echo 'checked'; ?>> <label for="use_cat_image0"><?php _e('Use post image on homescreen', 'nh-ynaa'); ?></label><br>
                                    <input type="radio" value="1"  name="<?php echo $this->categories_settings_key.'['.$category->term_id.'][usecatimg]';?>" id="use_cat_image1" <?php if($this->categories_settings[$category->term_id]['usecatimg'] ) echo 'checked'; ?>> <label for="use_cat_image1"><?php _e('Use category image on homescreen', 'nh-ynaa'); ?></label>
                                    
                                </div>
                            	<div class="show-subcat-div">
							<?php
								if(get_categories(array('hide_empty'=>0, 'child_of'=>$category->term_id))){
									if($this->categories_settings[$category->term_id]['showsub']){
										$yesradio = 'checked';
										$noradio = '';
									}
									else{
										$yesradio = '';
										$noradio = 'checked';
									}
							 _e('Show subcategories overview:', 'nh-ynaa'); 
							 echo '<br><label for="yesradio_'.$category->term_id.'">'; _e('Yes', 'nh-ynaa'); 
							 echo '</label><input type="radio" name="'.$this->categories_settings_key.'['.$category->term_id.'][showsub]" value="1" id="yesradio_'.$category->term_id.'" '.$yesradio.' /> <label for="noradio_'.$category->term_id.'">'; _e('No', 'nh-ynaa'); 
							 echo '</label><input type="radio" name="'.$this->categories_settings_key.'['.$category->term_id.'][showsub]" value="0" id="noradio_'.$category->term_id.'" '.$noradio.' />';
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
				_e('No categories.');
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
			<input type="text" name="<?php echo $this->general_settings_key; ?>[<?php echo $field['field']; ?>]" value="<?php echo esc_attr( $this->general_settings[$field['field']] ); ?>" class="my-color-field" />
			<?php
		}
		
		/*
		 * General Option field callback CSS
		 */
		function nh_ynaa_field_general_option_css($field) {
			
			?>
			<textarea id="css_textarea" name="<?php echo $this->general_settings_key; ?>[<?php echo $field['field']; ?>]"><?php echo esc_attr( $this->general_settings[$field['field']] ); ?></textarea>
						
			<?php
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
		 * General Option field social callback 
		 */
		function nh_ynaa_field_general_social($field) {			
			?>
			<input type="text" name="<?php echo $this->general_settings_key; ?>[<?php echo $field['field']; ?>]" value="<?php echo $this->general_settings[$field['field']]; ?>" />
			<?php
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
			<input type="checkbox" name="<?php echo $this->push_settings_key; ?>[<?php echo $field['field']; ?>]" id="<?php echo 'id_'.$field; ?>" <?php echo $check; ?> value="1" />
			<?php
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
                } //END function nh_the_home_content

			
		/*
		 * Start screen View  
		*/
		function nh_ynaa_field_general_homescreentype($field){
			?>
			<select id="nh_homescreentype" name="<?php echo $this->general_settings_key; ?>[<?php echo $field['field']; ?>]">
                    	<option value="0"><?php _e('Categories', 'nh-ynaa'); ?></option>
                        <option value="1" <?php if($this->general_settings[$field['field']]=='1') echo ' selected'; ?>><?php _e('Articles', 'nh-ynaa'); ?></option>        
                    </select>
           <?php
		}
		
		/*
		 * LAngugae
		*/
		function nh_ynaa_field_general_language($field){
			
			
			?>
			<select  id="nh_language" name="<?php echo $this->general_settings_key; ?>[<?php echo $field['field']; ?>]">
                    	<option value="en"><?php _e('English', 'nh-ynaa'); ?></option>
                        <option value="fr" <?php if($this->general_settings[$field['field']]=='fr') echo ' selected'; ?>><?php _e('French', 'nh-ynaa'); ?></option>
                        <option value="de" <?php if($this->general_settings[$field['field']]=='de') echo ' selected'; ?>><?php _e('German', 'nh-ynaa'); ?></option>
                        <option value="es" <?php if($this->general_settings[$field['field']]=='es') echo ' selected'; ?>><?php _e('Spanish', 'nh-ynaa'); ?></option>
                    </select>
           <?php
		}
		
		/**
		* articles ort by
		*/
		function nh_ynaa_field_general_sorttype($field){
			?>
            <select   <?php if(!$this->general_settings['homescreentype'] || !isset($this->general_settings['homescreentype'])) echo 'disabled'; ?>  id="nh_sorttype" name="<?php echo $this->general_settings_key; ?>[<?php echo $field['field']; ?>]">
                    	<option value="recent"><?php _e('Recent posts', 'nh-ynaa'); ?></option>
                        <!--<option value="popular" <?php if($this->general_settings[$field['field']]=='popular') echo ' selected'; ?>><?php _e('Most popular posts', 'nh-ynaa'); ?></option> -->       
                    </select>
            <?php
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
                	<select id="eventplugin" name="<?php echo $this->general_settings_key; ?>[<?php echo $field['field']; ?>]">
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
			<input value="1" type="checkbox" name="<?php echo $this->general_settings_key; ?>[<?php echo $field['field']; ?>]" id="<?php echo $this->general_settings_key; ?>[<?php echo $field['field']; ?>]" <?php echo $check; ?> />
			<?php
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
			$nh_menu_hook_ynaa = add_options_page( 'yourBlogApp/yourNewsApp', 'yourBlogApp/yourNewsApp', 'manage_options', $this->plugin_options_key, array( &$this, 'nh_ynaa_plugin_options_page' ) );
			add_action("load-{$nh_menu_hook_ynaa}",array(&$this,'nh_create_help_screen'));
		}
		
		
		
		/*
		* Function to create Help 
		*/
		public function nh_create_help_screen() {
			
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
			$tab = isset( $_GET['tab'] ) ? $_GET['tab'] : $this->general_settings_key;
			
			?>
			<div class="wrap">
				<!--<div id="icon-options-general" class="icon32"><br/></div>-->
				<h2><?php _e('Settings for yourBlogApp/yourNewsApp','nh-ynaa'); ?></h2>
				<?php 
					$this->nh_the_home_content();
                    $this->nh_ynaa_plugin_options_tabs();
				if($tab != 'qrcode'){
				 ?>
				<form method="post" action="options.php" enctype="multipart/form-data" id="nh_ynaa_form" class="<?php echo $tab; ?>">					
					<?php wp_nonce_field( 'update-options' ); ?>
					<?php settings_fields( $tab ); ?>					
					<?php do_settings_sections( $tab ); ?>
					<?php submit_button(); ?>
				</form>
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
			$current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : $this->general_settings_key;

			screen_icon();
			echo '<h2 class="nav-tab-wrapper">';
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
							
			wp_enqueue_script( 'ynaa-script-handle', plugins_url('js/ynaa.js', __FILE__ ), array( 'jquery', 'jquery-ui-core', 'jquery-ui-tabs', 'jquery-ui-accordion', 'jquery-ui-sortable', 'wp-color-picker', 'media-upload','thickbox' ), '1.0', true );
			
			
			wp_enqueue_style('thickbox');
			
			
			$data = array('general_settings_key'=>$this->general_settings_key, 'menu_settings_key'=>$this->menu_settings_key, 'teaser_settings_key' => $this->teaser_settings_key, 'homepreset_settings_key'=>$this->homepreset_settings_key, 'delete'=>__('Delete'), 'catText'=>__('Set default image for category','nh-ynaa') , 'allowremoveText' => __('Allow hide on Startscreen','nh-ynaa'), 'color01'=>$this->general_settings['c1'] , 'ajax_url' => admin_url( 'admin-ajax.php' ) );
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
			header('Content-Type: application/json');
			if($ynaa_var=='settings'){
				print_r(json_encode($this->nh_ynaa_settings()));				
			}
			elseif($ynaa_var=='homepresets'){
				print_r(json_encode($this->nh_ynaa_homepresets()));				
			}
			elseif($ynaa_var=='teaser'){
				print_r(json_encode($this->nh_ynaa_teaser()));				
			}
			elseif($ynaa_var=='categories'){
				print_r(json_encode($this->nh_ynaa_categories()));				
			}
			elseif($ynaa_var=='articles'){
				print_r(json_encode($this->nh_ynaa_articles()));				
			}
			elseif($ynaa_var=='article'){
				print_r(json_encode($this->nh_ynaa_article()));				
			}
			elseif($ynaa_var=='events'){
				print_r(json_encode($this->nh_ynaa_events()));				
			}
			elseif($ynaa_var=='event'){
				print_r(json_encode($this->nh_ynaa_event()));				
			}
			elseif($ynaa_var=='social'){
				print_r(json_encode($this->nh_ynaa_social()));				
			}
			elseif($ynaa_var=='comments'){
				print_r(json_encode($this->nh_ynaa_comments()));				
			}
			elseif($ynaa_var=='ibeacon'){
				print_r(json_encode($this->nh_ynaa_ibeacon()));				
			}
			elseif($ynaa_var=='locations'){
				print_r(json_encode($this->nh_ynaa_locations()));				
			}
			elseif($ynaa_var=='yna_settings'){
				print_r(json_encode($this->nh_ynaa_yna_settings()));				
			}
			elseif($ynaa_var){
				print_r(json_encode(array('error'=>$this->nh_ynaa_errorcode(11))));
			}							
			else {
				print_r(json_encode(array('error'=>$this->nh_ynaa_errorcode())));
			}
			exit();
		} // END public function nh_ynaa_template_redirect()
		
		/**
		 * Return Error Array
		 */
		private function nh_ynaa_errorcode($er=10){
			$errorarray = array();
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
				default: $errorarray['error_code']= 10; $errorarray['error_message']='Unknown Error'; break;
			}
			return ($errorarray);			
		} // END private function errorcode()
		
		/**
		 * Return Setting Array
		 */
		private function nh_ynaa_settings(){
			//$returnarray['error']=$this->errorcode(0);	
			if(!get_option($this->general_settings_key))   {
				//echo 'Keine settings';
				$returnarray['error']=$this->nh_ynaa_errorcode(13);
			}
			elseif(!get_option($this->menu_settings_key))   {
				//echo 'Keine Menu';
				$returnarray['error']=$this->nh_ynaa_errorcode(14);
			}			
			else {
				if($_GET['ts']) $ts= $_GET['ts'];
				else $ts = 0;
				
				$returnarray['error']=$this->nh_ynaa_errorcode(0);
				$returnarray['url']=get_bloginfo('url');
				global $nh_ynaa_version;
				$returnarray['plugin_version']=$nh_ynaa_version;
				$returnarray['wpversion']=get_bloginfo('version');
				$returnarray['wpcharset']=get_bloginfo('charset');
				$returnarray['wphtml_type']=get_bloginfo('html_type');
				
				if($ts<$this->general_settings['ts'] || $ts<$this->menu_settings['ts']){
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
					
					if($this->general_settings['ts']>$this->menu_settings['ts'])$ts=$this->general_settings['ts'];
					else $ts=$this->menu_settings['ts'];
					if($this->general_settings['sort'])$returnarray['sort']=1;
					else $returnarray['sort']=0;
					if($this->general_settings['homescreentype']){
						$returnarray['homescreentype']=(int) $this->general_settings['homescreentype'];
						if($this->general_settings['sorttype'])$returnarray['sorttype']=$this->general_settings['sorttype'];
						else $returnarray['sorttype']='recent';
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
					
					if(!$this->general_settings['cm'])$this->general_settings['cm'] =$this->general_settings['c1'];
					$returnarray['changes']=1;
					$returnarray['color-01']=($this->general_settings['c1']);
					$returnarray['color-02']=$this->general_settings['c2'];
					$returnarray['color-navbar']=$this->general_settings['cn'];
					$returnarray['color-menu']=$this->general_settings['cm'];
					$returnarray['color-text']=$this->general_settings['ct'];
					$returnarray['color-headline']=$this->general_settings['ch'];
					$returnarray['color-subheadline']=$this->general_settings['csh'];
					
					if($this->general_settings['logo'])$returnarray['logoUrl']=$this->general_settings['logo'];
					else $returnarray['logoUrl']='';
					$returnarray['hasCategories']=1;
					$returnarray['menuIsSectioned']=0;
					$returnarray['categories']=1;
					$returnarray['allowreorder']=1;
					if($this->general_settings['comments'])	$returnarray['comments']=$this->general_settings['comments'];
					else $returnarray['comments']=0;
					$returnarray['style']='<style type="text/css">body { color:#'.$this->general_settings['ct'].';}'.($this->general_settings['css']).'</style>';					
					
					if($this->menu_settings['menu']){						
						foreach($this->menu_settings['menu'] as $k=>$ar){
							if($ar['status']==0) continue;
							else {
								unset($tempmenu);
								$tempmenu['pos'] =  $ar['pos'];
								$tempmenu['type'] =  $ar['type'];
								$tempmenu['id'] =  $ar['id'];
								$tempmenu['title'] =  $ar['title'];
								$tempmenu['ts']= $this->menu_settings['ts'];
								if(isset($ar['content']))$tempmenu['content'] = $ar['content'];
								if(isset($ar['item_id']))$tempmenu['item_id'] = $ar['item_id'];
								
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
						$returnarray['menu']['error']=$this->nh_ynaa_errorcode(14);
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
				if($_GET['ts']) $ts= $_GET['ts'];
				else $ts = 0;
				
				/*if(($this->general_settings['homescreentype'] && $this->general_settings['sorttype']) || ($_GET['option']=1 && $_GET['sorttype']) ){
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
				if($this->homepreset_settings['items']){
					$returnarray['changes']=0;
					if($ts<$this->homepreset_settings['ts']) {
						$returnarray['changes']=1;
						$ts = $this->homepreset_settings['ts'];
					}
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
						foreach($this->homepreset_settings['items'] as $hp){
							//var_dump($hp);
							if($hp['allowRemove']) $allowRemove = 1; else $allowRemove=0;
							$cat_id = '';
							$img = '';
							$items['articles']['items'][0]['id'] = '';
							$items['articles']['items'][0]['timestamp'] = '';
							$items['articles']['items'][0]['publish_timestamp'] = '';
							
							if($hp['type'] == 'cat'){	
								$cat_id	= $hp['id'];
								$items = ($this->nh_ynaa_articles($hp['id'],1));
								
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
								$cat_id	= $hp['id'];
								$fb = $this->nh_ynaa_get_fbcontent(1);
								if($fb){
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
								$cat_id	= $hp['id'];
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
								$cat_id	= $hp['id'];
								//$location = $this->nh_ynaa_locations(1);
								
								//if($location){
									//	var_dump($location);								
									$items['articles']['items'][0]['id']=($cat_id);
									$items['articles']['items'][0]['url']=$hp['url'];
									$items['articles']['items'][0]['timestamp']=time();
									$items['articles']['items'][0]['publish_timestamp']=time();
									$img = '';
																		
								//}
								 
								//if(!$img &&  $this->categories_settings[-98]['img']) $img = $this->categories_settings[-98]['img'];
								if(!$img && $hp['img']) $img = $hp['img'];
								
							}
							elseif($hp['type'] == 'events'){	
								$cat_id	= $hp['id'];
								$event = $this->nh_ynaa_events(1);
								if($event){
									$items['articles']['items'][0]['id']=$event['events']['items'][0]['id'];
									$items['articles']['items'][0]['timestamp']=$event['events']['items'][0]['timestamp'];
									$items['articles']['items'][0]['publish_timestamp']=$event['events']['items'][0]['publish_timestamp'];
									$img = $event['events']['items'][0]['thumb'];
								}
								if(!$img &&  $this->categories_settings[-1]['img']) $img = $this->categories_settings[-1]['img'];
								if(!$img) $img = $hp['img'];
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
								}
								
								$hp['type'] = 'article';
							}
							$showsub = 0;
							
							if($cat_id && $this->categories_settings[$cat_id]['showsub']) $showsub=1;
							$returnarray['items'][]=array('pos'=>$i, 'type' => $hp['type'], 'allowRemove'=> $allowRemove, 'id'=> $hp['id'], 'cat_id'=>$cat_id,  'title'=>$hp['title'], 'img'=>$img, 'post_id'=>$items['articles']['items'][0]['id'], 'timestamp'=>$items['articles']['items'][0]['timestamp'], 'publish_timestamp' =>$items['articles']['items'][0]['publish_timestamp'], 'showsubcategories'=>$showsub);
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
				
				if($_GET['ts']) $ts= $_GET['ts'];
				else $ts = 0;
				if($this->teaser_settings['teaser']){
					$returnarray['changes']=0;
					if($ts<$this->teaser_settings['ts']) {
						$returnarray['changes']=1;
						$ts = $this->teaser_settings['ts'];
					}
					if(is_array($this->teaser_settings['teaser']) && count($this->teaser_settings['teaser'])>0){
						$i=1;
						foreach($this->teaser_settings['teaser'] as $teaser){
							$p = wp_get_single_post($teaser);
							if($p){
								//var_dump($p);
								if( strtotime($p->post_modified) > $ts){
									$returnarray['changes']=1;
									$ts = strtotime($p->post_modified);
								}
								$category = get_the_category($teaser); 
								if(get_post_type($teaser)=='event') $category[0]->term_id=0;
								$returnarray['items'][]=array('pos'=>$i, 'apectFill'=>1, 'type' => get_post_type($teaser), 'id'=> $teaser, 'title'=> htmlspecialchars_decode($p->post_title), 'thumb'=>$this->nh_getthumblepic($teaser), 'cat_id'=>$category[0]->term_id, 'post_ts'=>strtotime($p->post_modified));
								$i++;
								unset($category);
							}
						}
						
					}
					else {
						
						$returnarray['error']=$this->nh_ynaa_errorcode(18);
					}
					if(!isset($returnarray['items'])){
						$returnarray['error']=$this->nh_ynaa_errorcode(18);
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
			$returnarray['uma']['ts']=time();
			$returnarray['uma']['current_time']=current_time('timestamp');
			
			
			$args=array(
			  'orderby' => 'name',			  
			  'order' => 'ASC',
			  'hide_empty'=>1
			);
			if($_GET['ts']) {
				$ts= $_GET['ts'];				
			}
			else {
				$ts = 0;				
			}
			$categories = get_categories( $args );
			$i=0;
			$parent = array();
			$cat = array();		
			
			if($categories){
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
					
					
					
					$items = ($this->nh_ynaa_articles($category->term_id,1));	
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
						$cat[$category->term_id]=array('pos'=>$i, 'type'=>'cat', 'id'=> $category->term_id, 'parent_id'=>$category->parent, 'title'=>htmlspecialchars_decode($category->name), 'post_img'=>$items['articles']['items'][0]['thumb'], 'img'=>$this->categories_settings[$category->term_id]['img'], 'post_id'=>$items['articles']['items'][0]['id'] ,'post_ts'=>$items['articles']['items'][0]['timestamp'] ,'allowRemove'=> $allowRemove, 'itemdirekt'=>1);
						
						//$ass_cats[$category->term_id] = array('img'=>'');
						if($this->categories_settings[$category->term_id]['showsub']){
							$cat[$category->term_id]['showsubcategories']=1;
							//$ass_cats[$category->term_id]['showsubcategories']=1;
						}
						else $cat[$category->term_id]['showsubcategories']=0;
						if($this->categories_settings[$category->term_id]['usecatimg']){
							$use_cat_img = 1;
						}
						else $use_cat_img = 0;
						$ass_cats[$category->term_id] = array('showsubcategories'=>$cat[$category->term_id]['showsubcategories'],'img'=>$this->categories_settings[$category->term_id]['img'], 'pos'=>$i, 'type'=>'cat', 'id'=> $category->term_id, 'parent_id'=>$category->parent, 'title'=>htmlspecialchars_decode($category->name), 'post_img'=>$items['articles']['items'][0]['thumb'], 'post_id'=>$items['articles']['items'][0]['id'] ,'post_ts'=>$items['articles']['items'][0]['timestamp'] ,'allowRemove'=> $allowRemove, 'itemdirekt'=>1, 'use_cat_img'=> $use_cat_img   ); 
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
			if($this->general_settings['eventplugin']){
				
				$items = $this->nh_ynaa_events(1);
				if($items['events']['items']){							
					if($ts<=$items['events']['items'][0]['timestamp']) {
						$returnarray['changes']=1;
						$ts = $items['events']['items'][0]['timestamp'];
					}
					$event_im = '';
					if(!$items['events']['items'][0]['thumb']) $items['events']['items'][0]['thumb'] = '';
					//if(!$items['events']['items'][0]['thumb'] && $hp[-1]['img']) $items['events']['items'][0]['thumb'] = $hp[-1]['img'];
					//if(!$items['events']['items'][0]['thumb'] && $this->categories_settings[-1]['img']) $items['events']['items'][0]['thumb'] = $this->categories_settings[-1]['img'];
					if($this->categories_settings[-1]['img']) $event_im = $this->categories_settings[-1]['img'];
					$returnarray['items'][]=array('pos'=>$i, 'type'=>'events', 'id'=> -1, 'title'=>__('Events','nh-ynaa'), 'img'=>$items['events']['items'][0]['thumb'], 'post_id'=>$items['events']['items'][0]['id'] ,'post_ts'=>$items['events']['items'][0]['timestamp'] ,'allowRemove'=> $allowRemove);
					$ass_cats[-1]=array('pos'=>$i, 'type'=>'events', 'id'=> -1, 'title'=>__('Events','nh-ynaa'), 'img'=>$event_im, 'post_img'=>$items['events']['items'][0]['thumb'], 'post_id'=>$items['events']['items'][0]['id'] ,'post_ts'=>$items['events']['items'][0]['timestamp'] ,'allowRemove'=> $allowRemove);
					$i++;
					unset($items);
				}
				
			}
			
			//KArte
			
			if($this->general_settings['location']){
				//$hp[-98]['img'] = 'http://yna.nebelhorn.com/wp-content/uploads/2014/03/images.jpg';
				$map_img = '';
				if($this->categories_settings[-98]['img']) $map_img = $this->categories_settings[-98]['img'];
				//if(!$hp[-98]['img'] || $hp[-98]['img']==NULL || $hp[-98]['img']=='null') $hp[-98]['img']='';
				$returnarray['items'][]=array('pos'=>$i, 'type'=>'map', 'id'=> -98, 'title'=>__('Map','nh-ynaa'), 'img'=>$map_img, 'allowRemove'=> 1);
				$ass_cats[-98]=array('pos'=>$i, 'type'=>'map', 'id'=> -98, 'title'=>__('Map','nh-ynaa'),'img'=>$map_img, 'allowRemove'=> 1);
				$i++;
			}
			
			
			//Facebook
			$fb = $this->nh_ynaa_get_fbcontent(1);
			
			if($fb){
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
			
			$returnarray['ass_cats'] = $ass_cats;
			
			return array('categories'=>$returnarray);			
		} // END private function categories()
		
		/**
		 * Return Aricles Array
		 */
		private function nh_ynaa_articles($id=0, $lim=0){
			$allowRemove=1;
			//$returnarray['uma']['info_Articles_start'] = $id.'start nh_ynaa_articles'.$_GET['id']; 
			//$returnarray['uma']['categories_settings'] = $this->categories_settings;
				if(isset($_GET['id']) || $id){
					if(( $id))$tempid= $id;
					else $tempid= $_GET['id'];
					if($this->categories_settings[$tempid]['hidecat']) {
						$returnarray['changes']=1;	
						$returnarray['timestamp']=time();	
						//$returnarray['uma']['info_Articles'] = 'Die Kategorie wurde deaktiviert'; 
						$returnarray['error']=$this->nh_ynaa_errorcode(35);
						return array('articles'=>$returnarray);
					}
				}
				
				if(($_GET['option']=1 && $_GET['sorttype']) ){
					// The Query
					$returnarray['timestamp']=0;
					if(isset($_GET['id'])) $args['cat'] =$_GET['id'];
					elseif($id) $args['cat'] =$id; 
					$args ['post_status'] = 'publish'; 
					$args ['post_type'] = 'post'; 
					$args ['nopaging'] = true; 
					
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
							$returnarray['items'][]=array('pos'=>$i, "type"=>get_post_type(), 'allowRemove'=> 1, 'cat_id'=>$cat_id, 'cat_id_array'=>$cat_id_array,  'title'=>htmlspecialchars_decode($the_query->post->post_title), 'img'=>$img, 'thumb' => 'img', 'post_id'=>$the_query->post->ID, 'timestamp'=>strtotime($the_query->post->post_modified), 'publish_timestamp' =>strtotime($the_query->post->post_date), 'showsubcategories'=>0);
							if(strtotime($the_query->post->post_modified) > $returnarray['timestamp']) $returnarray['timestamp']= strtotime($the_query->post->post_modified);
							$i++;
						}
							
					} else {
						$returnarray['error']=$this->nh_ynaa_errorcode(16);
					}
					// Restore original Post Data 
					wp_reset_postdata();
					return array('articles'=>$returnarray);
				}
				elseif(isset($_GET['id']) || $id) {
				$returnarray['changes']=0;
				//PostID
				//If Post ID Check if is ist the newest Post and if hat changes
				if(isset($_GET['post_id']) && isset($_GET['post_ts'])){
					$break = false;
					$latest_cat_post = new WP_Query( array('posts_per_page' => 1, 'category__in' => array($_GET['id'])));
					//var_dump($latest_cat_post);
					
					if( $latest_cat_post->have_posts() ) {						
						if($latest_cat_post->posts[0]->ID == $_GET['post_id']){
							$break = true;	
							if(strtotime($latest_cat_post->posts[0]->post_modified)>$_GET['post_ts']){
								$ts = strtotime($latest_cat_post->posts[0]->post_modified);
								$returnarray['changes']=1;
								//var_dump($this->categories_settings[$_GET['id']]);
								if ( has_post_thumbnail($latest_cat_post->posts[0]->ID)) {
									$post_thumbnail_image=wp_get_attachment_image_src(get_post_thumbnail_id($latest_cat_post->posts[0]->ID), 'original');
								}
								/*elseif($this->categories_settings[$_GET['id']]['img']){
									$post_thumbnail_image=array($this->categories_settings[$_GET['id']]['img']);
								}*/
								else {
									$post_thumbnail_image=array();
								}
								$returnarray['items'][] = array('pos'=>1, 'id'=>$latest_cat_post->posts[0]->ID,'title'=>htmlspecialchars_decode($latest_cat_post->posts[0]->post_title),'timestamp'=>strtotime($latest_cat_post->posts[0]->post_modified),'type'=>$latest_cat_post->posts[0]->post_type, 'thumb'=> ($post_thumbnail_image[0]), 'publish_timestamp'=> strtotime($latest_cat_post->posts[0]->post_date)); 							
								//$returnarray['items'][]=array('pos'=>1, 'type' => $post->post_type, 'allowRemove'=> $allowRemove, 'id'=> $category->term_id, 'parent_id'=>0, 'title'=>$category->name, 'img'=>$post_thumbnail_image[0], 'post_id'=>$latest_cat_post->post->ID );
							}
							else {
								$ts = $_GET['post_ts'];
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
						$returnarray['timestamp']=$ts;
						return array('articles'=>$returnarray);
					}
				} 
				
				//Kategorie ID
				if($id) {
					$cid = $id;
					if($lim) $limit = $lim;
					else $limit=999;
				}
				else  {$cid = $_GET['id'];
					//LIMIT
					if($_GET['limit']) {
						$limit=$_GET['limit'];
					}
					else {
						$limit = 999;
					}
				}
				
				//Timestamp
				if($_GET['ts']) {
					$ts= $_GET['ts'];
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
					$orderby = 'post_date';
					
					$args = array('posts_per_page'   => -1, 'category' => $cid, 'orderby' => 'post_date',	'order' => 'DESC');
					$posts_array = get_posts( $args );
					
					if($posts_array){
						foreach($posts_array as $po){
							$post_ids[] = $po->ID;
							
						}
					}
				}
				//$post_ids = false;
				if(!$post_ids){
					$post_ids = $wpdb->get_col( $wpdb->prepare( "select p.ID from $table_posts p 
								left join $table_term_relationships tr on tr.object_id=p.ID
								where p.post_status='publish' and tr.term_taxonomy_id=$cid 								
								order by p.post_date desc
								LIMIT 1999",'%d'));
				}
				if($post_ids){
					$returnarray['error']=$this->nh_ynaa_errorcode(0);
					
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
						if ( has_post_thumbnail($post->ID)) {
							$post_thumbnail_image=wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'medium');
						}
						/*elseif($this->categories_settings[$_GET['id']]['img']){
							$post_thumbnail_image=array($this->categories_settings[$_GET['id']]['img']);
						}*/
						else $post_thumbnail_image[0] = '';	
						//echo esc_url($post_thumbnail_image[0]);
						$returnarray['items'][] = array('pos'=>$i, 'id'=>$post->ID,'title'=>htmlspecialchars_decode($post->post_title),'timestamp'=>strtotime($post->post_modified),'type'=>$post->post_type, 'thumb'=> ($post_thumbnail_image[0]), 'publish_timestamp'=> strtotime($post->post_date)); 							
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
			if(isset($_GET['id'])){
				//backup main post
				global $post;
				$stored_post = clone $post;
				$cid = $_GET['id'];
				$returnarray['error']=$this->nh_ynaa_errorcode(0);
				
				$post1 = get_post( $cid);
				
				if($_GET['ts']) $ts= $_GET['ts'];
				else $ts = 0;
				
				if($post1){				
					
					
						
					$post = $post1;
					setup_postdata( $post1 ); 	
								
					$returnarray['id'] = get_the_ID();		
					$returnarray['error']['postid']=$returnarray['id'] ;			
					$returnarray['timestamp'] = strtotime(get_the_date('Y-m-d').' '.get_the_modified_time());
					if($ts<strtotime(get_the_date('Y-m-d').' '.get_the_modified_time())) {
						$ts = strtotime(get_the_date('Y-m-d').' '.get_the_modified_time());
						if ( has_post_thumbnail()) {
							$post_thumbnail_image=(wp_get_attachment_image_src(get_post_thumbnail_id(get_the_ID()), 'medium'));
							$returnarray['uma']['aricle_has_image'] = true;
						}
						else {
							$post_thumbnail_image[0] = '';
									
							/*if(isset($this->homepreset_settings['items'])){
								if(is_array($this->homepreset_settings['items']) && count($this->homepreset_settings['items'])>0){
								           
									foreach($this->homepreset_settings['items'] as $hp){
										if($hp['id']==$returnarray['id'] && $hp['type'] == 'cat' && $hp['img']){
											$post_thumbnail_image[0] =   $hp['img'];
											break;
										}
									}
								}
							}*/
						}
						
						$returnarray['title'] = htmlspecialchars_decode($post->post_title);
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
					
						$queried_post = get_post($returnarray['id']);
						$content = $queried_post->post_content;
						$content = apply_filters('the_content', $content);
						$content = str_replace(']]>', ']]&gt;', $content);
						$content = str_replace("\r\n",'\n',$content);
						//$content = utf8_encode($content);
						
						$content = preg_replace('/[\x00-\x1F\x80-\x9F]/u', '',$content);
						$returnarray['uma']['post_content']= $content;
						$returnarray['uma']['post_content_htmlentities']= htmlentities($content,null,"UTF-8");
						$content = $this->nh_ynaa_get_appcontent($content);
						$returnarray['uma']['post_content_after_nh_ynaa_get_appcontent']= $content;
						//$content = preg_replace('/[\x00-\x1F\x80-\xFF]/', '',$content);
						$this->general_settings['css'] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '',$this->general_settings['css']);
						$content = (str_replace('<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">','<!doctype html>',$content));
						
						if(strpos($content,'<html><head><meta charset="utf-8"></head>'))
							$content = str_replace('<html><head><meta charset="utf-8"></head>','<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width">
       <link href="http://necolas.github.io/normalize.css/3.0.1/normalize.css" rel="stylesheet" type="text/css"><style type="text/css">'.$this->general_settings['css'].' body{color:'.$this->general_settings['ct'].';}</style></head>',$content);
						elseif(strpos($content,'<html>'))
							$content = str_replace('<html>','<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width">
       <link href="http://necolas.github.io/normalize.css/3.0.1/normalize.css" rel="stylesheet" type="text/css"><style type="text/css">'.$this->general_settings['css'].' body{color:'.$this->general_settings['ct'].';}</style></head>',$content);
							else $content = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width">
       <link href="http://necolas.github.io/normalize.css/3.0.1/normalize.css" rel="stylesheet" type="text/css"><style type="text/css">'.$this->general_settings['css'].' body{color:'.$this->general_settings['ct'].';}</style></head><body>'.$content.'</body></html>';
							
						$returnarray['uma']['post_content_0']= $content;
						$returnarray['content']=$content;
						$returnarray['uma']['content']=$content;
						$returnarray['changes']=1;				
						$returnarray['type']=get_post_type();					
						$returnarray['format']='html';
						 	
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
								$returnarray['location']=1;
								if(!$postmeta_location['location_pintype']) $postmeta_location['location_pintype'] = 'red';
								$returnarray['location_info']=array("title"=>$postmeta_location['location_name'],"lat"=>$postmeta_location['location_latitude'],"lng"=>$postmeta_location['location_longitude'], "address"=>$postmeta_location['location_address'],  "id"=>$nh_ynaa_location_id, 'ts'=>$postmeta_location_stamp, 'cat_id'=>$returnarray['catid'], 'pintype'=>$postmeta_location['location_pintype']);
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
					
					$returnarray['id'] = $_GET['id'];
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
		 * Return Social 
		*/
		private function nh_ynaa_social(){
			$returnarray['error']=$this->nh_ynaa_errorcode(24);
			if($_GET['n']=='fb'){
				if($_GET['limit']) $limit= $_GET['limit'];
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
			if($_GET['ts'])$returnarray['ts']=$_GET['ts'];
			else $returnarray['ts']=0;
			if($_GET['id']){
				global $wpdb;
				$table_comments = $wpdb->prefix . "comments";
				//$table_comments_meta = $wpdb->prefix . "comments_meta";
				
				if($_GET['action']=='add' ){
					if(!$_REQUEST['key'] || (!$_REQUEST['comment'] || trim($_REQUEST['comment']) =='') || !$_REQUEST['name'] || !$_REQUEST['email']  ) $returnarray['error']=$this->nh_ynaa_errorcode(30);
					elseif(!is_email($_REQUEST['email'])){
						$returnarray['error']=$this->nh_ynaa_errorcode(31);
					}
					else{
						$commentkey = $wpdb->get_var( "SELECT meta_id FROM $wpdb->commentmeta WHERE meta_key = 'ckey' AND meta_value = '".trim($_REQUEST['key'])."' LIMIT 1" );
						if($commentkey) $returnarray['error']=$this->nh_ynaa_errorcode(32);
						else {
							$ts = time();
							$ts = current_time('timestamp');
							$comment_parent = 0;
							//$wpdb->insert('temp',array('text'=>serialize($_REQUEST)), array('%s'));
							if($_REQUEST['comment_id']) $comment_parent = $_REQUEST['comment_id'];
							$commentdata = array(
								'comment_post_ID' => $_GET['id'],
								 'comment_author' => urldecode(trim($_REQUEST['name'])),
								 'comment_author_email' =>trim($_REQUEST['email']),
								 'comment_author_url' => 'http://',
								 'comment_content' => urldecode(trim($_REQUEST['comment'])),
								 'comment_type' => '',
								'comment_parent' => $comment_parent,
								'user_id' => 0,
								'comment_author_IP' => $_SERVER['REMOTE_ADDR'],
								'comment_agent' => $_SERVER['HTTP_USER_AGENT'],
								'comment_date' => date('Y-m-d H:i:s',$ts),
								'comment_approved' => 0
							);
							if($newcommentid = wp_insert_comment($commentdata)){
								add_comment_meta( $newcommentid, 'ckey', trim($_REQUEST['key']) );
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
					$post_7 = get_post($_GET['id']); 
					if($post_7->comment_status == 'open'){
						$returnarray['comment_status']=$post_7->comment_status;
						$args = array(
							'post_id' => $_GET['id'], // use post_id, not post_ID
							'status' => 'approve',
							'count' => true //return only the count
						);
						$comments_count = get_comments($args);
						$returnarray['comments_count']=$comments_count;
						$comment = array();
						$returnarray['items'] = array();
						if($comments_count>0){
							
							$args = array(
								'post_id' => $_GET['id'], // use post_id, not post_ID
								'status' => 'approve',
								'$order' => 'ASC'
								
							);
								
							$comments = $wpdb->get_results( "SELECT *   FROM $wpdb->comments WHERE comment_approved=1 AND comment_parent=0 AND comment_post_id=".$_GET['id']."  ORDER BY comment_date_gmt DESC ", ARRAY_A  );
							if($comments){
								foreach($comments as $com){
									$parrent_com[$com['comment_ID']][] = $com;
									
									
								}
							}
							
							$comments = $wpdb->get_results( "SELECT *   FROM $wpdb->comments WHERE comment_approved=1 AND comment_parent!=0 AND comment_post_id=".$_GET['id']."   ORDER BY comment_date_gmt ASC ", ARRAY_A  );
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
									$temparray['text']=$ar[0]['comment_content'];
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
									$temparray['author']['img'] = substr($temparray['author']['img'],strpos($temparray['author']['img'],'src=')+5);
									$temparray['author']['img'] = substr($temparray['author']['img'],0,strpos($temparray['author']['img'],'\''));
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
											 $temp['text'] =$ar2['comment_content'];
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
				/*$returnarray['uuid']=$this->push_settings['uuid'];
				if($this->push_settings['welcome']) $returnarray['welcome']=$this->push_settings['welcome'];
				if($this->push_settings['silent']) $returnarray['silent']=$this->push_settings['silent'];*/
				$returnarray['uuid'] ='B9407F30-F5F8-466E-AFF9-25556B57FE6D	' ;
				$returnarray['silent'] =60 ;
				$returnarray['identifier'] ='Beacon1' ;
				$returnarray['welcome'] ='Willkommen bei der Frankfurter Buchmesse.' ;
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
			if($_GET['ts'])	$returnarray['ts']=$_GET['ts'];

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
				elseif($_GET['limit'] ) {
					$limit = " LIMIT ".$_GET['limit']." ";
					$limit2 = $_GET['limit'];
				}
				else $limit = " LIMIT 9999 ";
				
				$returnarray['changes']=0;
				//PostID
				//If Post ID Check if is ist the newest Post and if hat changes
				if(isset($_GET['post_id']) && isset($_GET['post_ts'])){
					$break = false;
					$latest_cat_post = new WP_Query( array('posts_per_page' => 1, 'post_type' => 'event'));
					//var_dump($latest_cat_post);
					if( $latest_cat_post->have_posts() ) : while( $latest_cat_post->have_posts() ) : $latest_cat_post->the_post();  
						if($latest_cat_post->post->ID == $_GET['post_id']){
							$break = true;		
							$i = 1;		
							
										
							if(strtotime($latest_cat_post->post->post_modified)>$_GET['post_ts']){
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
								
									$returnarray['items'][] = array(
										'uma'=>array('start_ts_gmt',get_gmt_from_date($event->event_start_date.' '.$event->event_start_time), 'test'=>1),
										'pos'=>$i,
										'id'=>$latest_cat_post->post->ID, 
										'title'=>htmlspecialchars_decode($latest_cat_post->post->post_title), 
										'timestamp'=>strtotime($latest_cat_post->post->post_modified), 
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
								$ts = $_GET['post_ts'];
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
				if($_GET['ts']) {
					$ts= $_GET['ts'];
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
						}
						else $post_thumbnail_image[0] = '';	
						$start_ts = strtotime($event->event_start_date.' '.$event->event_start_time);
						$end_ts = strtotime($event->event_end_date.' '.$event->event_end_time);
						if(!$event->location_latitude || $event->location_latitude== null || $event->location_latitude=='null' || $event->location_latitude=='0.000000') $event->location_latitude =  0;
						else $event->location_latitude = (float) $event->location_latitude ;
						if(!$event->location_longitude || $event->location_longitude== null || $event->location_longitude=='null' || $event->location_longitude=='0.000000') $event->location_longitude =  0;
						else $event->location_longitude = (float) $event->location_longitude ;	
						$returnarray['items'][] = array(
							'uma'=>array('start_ts_gmt',get_gmt_from_date($event->event_start_date.' '.$event->event_start_time), 'test'=>2),
							'pos'=>$i,
							'id'=>$post->ID, 
							'title'=>htmlspecialchars_decode($post->post_title), 
							'timestamp'=>strtotime($post->post_modified), 
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
							'img' => $post_thumbnail_image[0],
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
			if(isset($_GET['id'])){
				if($_GET['ts']) $ts= $_GET['ts'];
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
					WHERE e.post_id=".$_GET['id']."			
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
							$queried_post = get_post($returnarray['id']);
							$content = $queried_post->post_content;
							$content = apply_filters('the_content', $content);
							$content = str_replace(']]>', ']]&gt;', $content);
							$content = str_replace("\r\n",'\n',$content);
							//$returnarray['uma']['post_content_-1']= $content;
							$content = preg_replace('/[\x00-\x1F\x80-\x9F]/u', '',$content);
							$content = $this->nh_ynaa_get_appcontent($content);
							//$content = preg_replace('/[\x00-\x1F\x80-\xFF]/', '',$content);
							$this->general_settings['css'] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '',$this->general_settings['css']);
							$content = (str_replace('<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">','<!doctype html>',$content));
							
							if(strpos($content,'<html><head><meta charset="utf-8"></head>'))
								$content = str_replace('<html><head><meta charset="utf-8"></head>','<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><link href="http://necolas.github.io/normalize.css/3.0.1/normalize.css" rel="stylesheet" type="text/css"><style type="text/css">'.$this->general_settings['css'].' body{color:'.$this->general_settings['ct'].';}</style></head>',$content);
							elseif(strpos($content,'<html>'))
								$content = str_replace('<html>','<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><link href="http://necolas.github.io/normalize.css/3.0.1/normalize.css" rel="stylesheet" type="text/css"><style type="text/css">'.$this->general_settings['css'].' body{color:'.$this->general_settings['ct'].';}</style></head>',$content);
								else $content = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><link href="http://necolas.github.io/normalize.css/3.0.1/normalize.css" rel="stylesheet" type="text/css"><style type="text/css">'.$this->general_settings['css'].' body{color:'.$this->general_settings['ct'].';}</style></head><body>'.$content.'</body></html>';
							
							if(!$event->location_latitude || $event->location_latitude== null || $event->location_latitude=='null' || $event->location_latitude=='0.000000') $event->location_latitude =  0;
							else $event->location_latitude = (float) $event->location_latitude ;
							if(!$event->location_longitude || $event->location_longitude== null || $event->location_longitude=='null' || $event->location_longitude=='0.000000') $event->location_longitude =  0;
							else $event->location_longitude = (float) $event->location_longitude ;	
														
							$returnarray['title']=htmlspecialchars_decode($post->post_title);
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
							$returnarray['text']=$content;
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
		
		/**
			Function to prepare Content for App
			return Formatet HTML
		*/		
		private function nh_ynaa_get_appcontent($html){
			//echo $html;
			
			
			$libxml_previous_state = libxml_use_internal_errors(true);
			$dom = new DOMDocument();
			$caller = new ErrorTrap(array($dom, 'loadHTML'));
			$caller->call($html);
			if (!$caller->ok()) {
			  $html='<!doctype html><html><head><meta charset="utf-8"></head><body>'.$html.'</body></html>';
			}
			else {
				$dom->validateOnParse = true;
				$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
				$dom->preserveWhiteSpace = false;
	
	
				// dirty fix
				foreach ($dom->childNodes as $item)
					if ($item->nodeType == XML_PI_NODE)
						$dom->removeChild($item); // remove hack
				$dom->encoding = 'UTF-8'; // insert proper
			
				$imgElements  = $dom->getElementsByTagName("img");
				foreach ($imgElements as $imgElement) {
					//echo $imgElement->getAttribute("src").'<hr>';
					if($imgElement->hasAttribute('width'))$imgElement->removeAttribute('width');
					$imgElement->setAttribute('width','100%');
					if($imgElement->hasAttribute('height'))$imgElement->removeAttribute('height');
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
		
				$html = nl2br($html);
				$htmlsup = substr($html,0,strpos($html,'<body>'));
				$htmlsup = str_replace('<br />','',$htmlsup);
				$html = substr($html,strpos($html,'<body>'),-7);		
				$html = $htmlsup.$html;
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
			return ($html);
		}//END private function nh_ynaa_get_appcontent
		
		/**
		 *Function to get Facebook content
		*/		
		private function nh_ynaa_get_fbcontent($limit=50){
			//echo 'fb';
			if(isset($this->general_settings['social_fbid'],$this->general_settings['social_fbsecretid'],$this->general_settings['social_fbappid']) && ($this->general_settings['social_fbid'] != '' && $this->general_settings['social_fbsecretid'] != '' && $this->general_settings['social_fbappid'] != '' )){
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
						$url = 'https://graph.facebook.com/'.$this->general_settings['social_fbid'].'/feed?access_token='.$access_token.'&format=json&limit='.$limit;						 						//echo $url;
						$items = @file_get_contents($url);
						if($items){
							$returnarray=$items;
						}
						else {
							$items =  $this->nh_ynaa_get_data($url);
							if($items){
								$returnarray=$items;
							}
							else {
								$returnarray['error']=$this->nh_ynaa_errorcode(28);
							}
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
		
		/* 
			*gets the data from a URL 
			* @$url String url
			* return strin content
		*/
		function nh_ynaa_get_data($url) {
			$ch = curl_init();
			$timeout = 25;
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
			$data = curl_exec($ch);
			curl_close($ch);
			return $data;
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
							__( 'yourBlogApp/yourNewsApp extras', 'nh_ynaa' ),
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
							__( 'yourBlogApp/yourNewsApp locations', 'nh_ynaa' ),
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
			
			echo '<br /><textarea style="width:100%" id="nh_ynaa_pushtext" name="nh_ynaa_pushtext" maxlength="120"></textarea>';
			
			
		  echo '</label></div> ';
		  
		  echo '<div><label for="nh_ynaa_sendpush">';
			
			
			echo '<input type="button" value="'.__('Send Push').'" id="nh_ynaa_sendpush" />';
			
			
		  echo '</label></div> ';

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
				elseif($generalsettings['location'] ){
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
		function ny_ynaa_teaser_action(){
			$result['type'] = "success";
			if($_POST['tpid']){
				$result['tpid']= $_POST['tpid'];
				$post = get_post($_POST['tpid']);
				if($post) $result['error'] = 0;
				$result['title'] = get_the_title($_POST['tpid']);
				$result['img']= $this->nh_getthumblepic($_POST['tpid']);
				//$result['allowremoveText']= __('Allow hide on Startscreen','nh-ynaa');
				//$result['catText']= __('Set default image for category','nh-ynaa');
			}
			else $result['error'] = __('No ID');
			$result = json_encode($result);
			echo $result;
			die();
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
		function nh_getthumblepic($id, $size='medium'){
			$url ='';
			if($id){
				if ( has_post_thumbnail($id)) {
					$post_thumbnail_image=wp_get_attachment_image_src(get_post_thumbnail_id($id), $size);
				}
				else $post_thumbnail_image[0] = '';	
				$url = $post_thumbnail_image[0];
			}
			return esc_url($url);
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
			
			if(!($this->push_settings['appkey']) || $this->push_settings['appkey'] == '') { _e('No Appkey.'); die(); }
			if(!($this->push_settings['pushsecret']) || $this->push_settings['pushsecret'] == '') {_e('No Push Secret Key.');die(); }
			if(!($this->push_settings['pushurl']) || $this->push_settings['pushurl'] == '') { _e('No Push Url.'); die(); }
			
			define('APPKEY', esc_attr( $this->push_settings['appkey'] )); // App Key
			define('PUSHSECRET', esc_attr( $this->push_settings['pushsecret'] )); // Master Secret
			define('PUSHURL', esc_attr( $this->push_settings['pushurl'] ));
			$device_types = array('ios');
			
			if($cat = $_POST['push_cat']){ 
				
				//$cat= explode(',',$_POST['cat']);
				 
				if(is_array($cat) && count($cat)>0){
					foreach($cat as $k=>$v) $cat[$k]= (string)($v);
					//$device_token['device_token'] = array('6DBBDB5FD28A8A08FDC04DD36032C21C46120C233A7757CDDB666074777AA43E'); // Device 
					$tag['tag'] = $cat;
					$tag2['tag'] = array(get_bloginfo('url'));
					//$tag2['tag'] = 'http://herri.nebelhorn.com';
					if($tag2['tag'][0]=='http://smokeycats.com') $tag2['tag'][0] = 'http://www.smokeycats.com';
					$iosContent = array();
					$iosContent['alert'] = $_POST['push_text'];
					$iosContent['sound'] = "default";
					$iosContent['badge'] = "+1";					
					$iosExtraContent = array();
					$iosExtraContent['articleHierarchyIDs'] = array((int) $cat[0], (int) $_POST['push_post_id']);
					$iosContent['extra'] = $iosExtraContent;

					$alertContent = array();
					$alertContent['ios'] = $iosContent;

					$audience['AND'] = array( $tag, $tag2);
					//$audience['AND'] = array( $device_token, $tag, $tag2); // mit device token
					$push = array("audience" => $audience); //$audience, wenn devicetoke dabei
															//$tag, wenn nur auf tags separiert
					$push['notification'] = $alertContent;
					$push['device_types'] = $device_types;

					$json = json_encode($push);
					//$json= '{"aps":{"alert":"test uma static","badge":1}}';
					var_dump($json);
					
					/*
					POST /api/push HTTP/1.1
					Authorization: Basic <master authorization string>
					Content-Type: application/json
					Accept: application/vnd.urbanairship+json; version=3;
					*/
					//echo PUSHURL;
					//die();
					$session = curl_init(PUSHURL); 
					curl_setopt($session, CURLOPT_USERPWD, APPKEY . ':' . PUSHSECRET);
					curl_setopt($session, CURLOPT_POST, True);
					curl_setopt($session, CURLOPT_POSTFIELDS, $json);
					curl_setopt($session, CURLOPT_HEADER, False);
					curl_setopt($session, CURLOPT_RETURNTRANSFER, True);
					curl_setopt($session, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Accept: application/vnd.urbanairship+json; version=3;'));


					$content = curl_exec($session);
					//var_dump($content);

					// Check if any error occured
					$response = curl_getinfo($session);
					//var_dump($response);
					if($response['http_code']!='202') {
						_e("Got negative response from server, http code: ");
						echo $response['http_code'] . "\n";
					}
					else{
						//Work around for www
						
						
					
						_e("Send successfull.");
						
					}

					curl_close($session);
				}
				else {
					_e('Feed Error!');
				}
			}
			else {
				_e('No Feed Set!');
			}

		//echo 'aaaaa';
			die(); // this is required to return a proper result
		}
		
		/*
		 * Function to get Lan and LAt
		*/
		function getLatLng($address){
			
			
			$address = str_replace(" ", "+", $address);

	 		$url='http://maps.googleapis.com/maps/api/geocode/json?address='.$address.'&sensor=false';
			$source = file_get_contents($url);
			$obj = json_decode($source);
			if($obj != null){
				$LATITUDE = $obj->results[0]->geometry->location->lat;
				$LONGITUDE = $obj->results[0]->geometry->location->lng;
			}else{
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
			
			
    } // END class NH YNAA Plugin
} // END if(!class_exists('NH_YNAA_Plugin))

if(class_exists('NH_YNAA_Plugin'))
{
    // Installation and uninstallation hooks
    register_activation_hook(__FILE__, array('NH_YNAA_Plugin', 'nh_ynaa_activate'));
    register_deactivation_hook(__FILE__, array('NH_YNAA_Plugin', 'nh_ynaa_deactivate'));

    // instantiate the plugin class
    $nh_ynaa = new NH_YNAA_Plugin();
	
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
			add_action('template_redirect', array($nh_ynaa, 'nh_ynaa_template_redirect'));
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
	
?>
<script type="text/javascript" >

jQuery(document).ready(function($) {
	
	//alert('<?php echo $post->ID;  ?>');	

	// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
	$('#nh_ynaa_sendpush').click(function(e) {	
		if(<?php if($post->post_status== 'publish') echo 1; else echo 0;  ?>)	{
			if($('#nh_ynaa_pushtext').val()=='') alert('<?php _e('Insert Pushtext!', 'nh-ynaa'); ?>');
			else {
				//alert('<?php _e('Pleas wait!'); ?>');
								
				jQuery.ajax({
					 type : "post",			 
					 url : ajaxurl,
					 data : {action: "ny_ynaa_push_action", push_post_id:<?php echo $post->ID; ?>, push_cat:[<?php echo $cat; ?>] , push_text:$('#nh_ynaa_pushtext').val()},
					 success: function(data,textStatus,jqXHR ) {
						alert(data);				
					 }
				  })   ;
			}
		}
		else alert('<?php _e('You have to publish the Post first.!', 'nh-ynaa'); ?>');
		//alert('Got this from the server: ' + e);
	});
});
</script>
<?php
	}
}


add_action('wp_ajax_nh_search_action', 'nh_search_action');

//add_action('wp_ajax_nopriv_my_action', 'my_action_callback');



//add_action('wp_ajax_ny_ynaa_push_action', 'ny_ynaa_push_action');
//add_action('wp_ajax_nopriv_my_action', 'my_action_callback');


?>