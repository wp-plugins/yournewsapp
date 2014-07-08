<?php
/**
* Widget Class
*/
class NH_Blappsta_Widget extends WP_Widget
{
 // constructor
	function NH_Blappsta_Widget() {
		parent::WP_Widget(false, $name = __('Blappsta Widget', 'nh-ynaa') );

	}

	// widget form creation
	function form($instance) {	
	// Check values
	if( $instance) {
		 $title = esc_attr($instance['title']);
		 $icon_url = esc_attr($instance['icon_url']);
		 $app_name = esc_attr($instance['app_name']);
		 $apple_link = esc_textarea($instance['apple_link']);
		 $google_link = esc_textarea($instance['google_link']);
	} else {
		 $title = 'Download our APP';
		 $icon_url = '';
		 $app_name = '';
		 $apple_link = '';
		 $google_link = '';
		 
	}
	?>
	
	<p>
	<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Widget Title:', 'nh-ynaa'); ?></label>
	<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
	</p>
	
    <p>
	<label for="<?php echo $this->get_field_id('icon_url'); ?>"><?php _e('Icon link:', 'nh-ynaa'); ?></label>
	<input class="widefat" id="<?php echo $this->get_field_id('icon_url'); ?>" name="<?php echo $this->get_field_name('icon_url'); ?>" type="text" value="<?php echo $icon_url; ?>" />
	</p>
	<p>
	<label for="<?php echo $this->get_field_id('app_name'); ?>"><?php _e('App name:', 'nh-ynaa'); ?></label>
	<input class="widefat" id="<?php echo $this->get_field_id('app_name'); ?>" name="<?php echo $this->get_field_name('app_name'); ?>" type="text" value="<?php echo $app_name; ?>" />
	</p>
	
    <p>
	<label for="<?php echo $this->get_field_id('apple_link'); ?>"><?php _e('Apple App Store link:', 'nh-ynaa'); ?></label>
	<input class="widefat" id="<?php echo $this->get_field_id('apple_link'); ?>" name="<?php echo $this->get_field_name('apple_link'); ?>" type="text" value="<?php echo $apple_link; ?>" />
	</p>
    <p>
	<label for="<?php echo $this->get_field_id('google_link'); ?>"><?php _e('Google Play Store link:', 'nh-ynaa'); ?></label>
	<input class="widefat" id="<?php echo $this->get_field_id('google_link'); ?>" name="<?php echo $this->get_field_name('google_link'); ?>" type="text" value="<?php echo $google_link; ?>" />
	</p>
	
	<?php
	}

	// widget update
	function update($new_instance, $old_instance) {
      $instance = $old_instance;
      // Fields
      $instance['title'] = strip_tags($new_instance['title']);
      $instance['icon_url'] = strip_tags($new_instance['icon_url']);
	  $instance['app_name'] = strip_tags($new_instance['app_name']);
      $instance['apple_link'] = strip_tags($new_instance['apple_link']);
	  $instance['google_link'] = strip_tags($new_instance['google_link']);
     return $instance;
	}

	// widget display
	function widget($args, $instance) {
	   extract( $args );
	   // these are the widget options
	   $title = apply_filters('widget_title', $instance['title']);
	   $icon_url = $instance['icon_url'];
	   $app_name = $instance['app_name'];
	   $apple_link = $instance['apple_link'];
	   $google_link = $instance['google_link'];
	   $plugin_url =plugins_url().'/yournewsapp';
	   echo $before_widget;
	   ?>
       <div class="widget-text wp_widget_plugin_box">
       <?php
	   if ( $title ) {
		  echo $before_title . $title . $after_title;
	   }
	   ?>
       <div id="blappsta-icon-cont">
       	<?php if($icon_url) { ?>
       		<div class="blappsta-app-img" >
                <div class="blappsta-app-img-div"></div>	
            	
            	<div class="blappsta-app-img-div blappsta-app-img-div2" style="background-image: url('<?php echo  $icon_url; ?>')"></div>	
            	<img src="<?php echo  $plugin_url; ?>/img/widgets/icon-window.png" alt="" />
            </div>
         <?php } ?>   
         <?php
		 if($app_name){
		?>
            <div class="blappsta-app-name"><?php echo $app_name; ?></div>
         <?php } ?> 
            <div>
            <?php if($apple_link) { 
				if(substr(get_bloginfo('language'),0,2)=='de') $storeimg = 'apple_store_de.png'; 
				else $storeimg = 'apple_store_en.png'; 
			?>
                <div class="blappsta-app-apple-store"><a href="<?php echo $apple_link; ?>" target="_blank"><img src="<?php echo  $plugin_url; ?>/img/widgets/<?php echo $storeimg; ?>" alt="Apple App Store" title=""></a></div>
            <?php }
				if($google_link) { 
				if(substr(get_bloginfo('language'),0,2)=='de') $storeimg = 'google_play_store_de.png'; 
				else $storeimg = 'google_play_store_en.png';
			?>
                <div><a href="<?php echo $google_link; ?>" target="_blank"><img src="<?php echo  $plugin_url; ?>/img/widgets/<?php echo $storeimg; ?>" alt="Google Play Store" title=""></a></div>
            <?php } ?>
            </div>
            
            <div class="blappsta-powerd" >
                <a href="http://www.blappsta.com" target="_blank"><img src="<?php echo  $plugin_url; ?>/img/widgets/powerd-by-blappsta.png" alt="Powerd by Blappsta" title=""></a>
            </div>
        </div>
        </div>
       <?php
	  
	   echo $after_widget;
	}
}


?>