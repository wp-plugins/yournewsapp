<?php $menu_id=0;
//var_dump($this->general_settings);
 ?>
 <input type="hidden" name="<?php echo $this->homepreset_settings_key; ?>[ts]" value="<?php echo time(); ?>" />
<div id="nav-menus-frame">
	<div id="menu-settings-column" class="metabox-holder">
		<div class="clear"></div>
		<div class="accordion-container" id="side-sortables">		
			<ul class="outer-border">
				<li id="add-custom-categories" class="control-section accordion-section   add-custom-categories">
				<h3 title="<?php _e('Categories', 'nh-ynaa'); ?>" tabindex="0" class="accordion-section-title hndle"><?php _e('Categories', 'nh-ynaa'); ?></h3>
				<div class="accordion-section-content ">
					<div class="inside">
						<div id="customcategoriediv" class="customlinkdiv">		
							<ul class="categorychecklist form-no-clear" id="cat-checklist-all">
								<?php
								$args=array(
								  'orderby' => 'name',			  
								  'order' => 'ASC'
								);
								$categories = get_categories( $args );
								$post_type = 'cat';
								foreach ( $categories as $category ) {
									echo '<li>';
									echo '<input type="hidden" value="'.$post_type.'" name="type-menu-item-'.$post_type.$menu_id.'" id="type-menu-item-'.$post_type.$menu_id.'">';
									echo '<input type="hidden" value="'.$post_type.'" name="link-typ-menu-item-'.$post_type.$menu_id.'" id="link-type-menu-item-'.$post_type.$menu_id.'">';
									echo '<input type="hidden" value="'.$category->name.'" name="title-menu-item-'.$post_type.$menu_id.'" id="title-menu-item-'.$post_type.$menu_id.'">';
									echo '<label class="menu-item-title">';
									echo '<input type="checkbox" value="'.$category->term_id.'" name="menu-item-'.$post_type.$menu_id.'" class="menu-item-checkbox" /> ';										
									echo $category->name.'</label>';													
									echo '</li>';
									$menu_id++;
								}
									//wp_category_checklist();
									
								?>
							</ul>
							<p class="button-controls">
								<!--<span class="list-controls"><a class="select-all" href="<?php echo $_SERVER['PHP_SELF']; ?>?page=ynaa_plugin_options&page-tab=all&amp;selectall=1#category ?>"><?php //_e('Select All'); ?></a></span>-->
								<span class="add-to-menu">
									<input type="submit" id="submit-customcategorydiv" name="add-custom-menu-item" value="<?php _e('Add to Menu'); ?>" class="button-secondary submit-add-to-homepreset right">
									<span class="spinner"></span>
								</span>
							</p>

						</div><!-- /.customlinkdiv -->
					</div><!-- .inside -->
				</div><!-- .accordion-section-content -->
			</li>
				<?php
					
					$post_types = get_post_types();
								
					foreach( $post_types as $post_type ){
						if( !in_array( $post_type, array( 'attachment', 'revision', 'nav_menu_item' ) ) ){
						?>
							<li id="add-<?php echo $post_type; ?>" class="control-section accordion-section add-<?php echo $post_type; ?>">
								<h3 title="<?php echo ucfirst(_n($post_type,$post_type.'s',2,'nh-ynaa')); ?>" tabindex="0" class="accordion-section-title hndle"><?php echo ucfirst(_n($post_type,$post_type.'s',2));?></h3>				
								<div class="accordion-section-content ">
									<div class="inside">
									<?php
										$args = array(
											'post_type'=> $post_type,							
											'order'    => 'post_modified'//,
											//'nopaging' => true											
											);
										$the_query = new WP_Query( $args );
										if($the_query->have_posts() ) : 
											?>
											<div class="posttypediv tabclass" id="posttype-<?php echo $post_type; ?>">
												<ul class="add-menu-item-tabs">
													<li><a href="#tabs-panel-posttype-<?php echo $post_type; ?>-most-recent" data-type="tabs-panel-posttype-<?php echo $post_type; ?>-most-recent" class="nav-tab-link"><?php _e('Most Recent','nh-ynaa'); ?></a></li>
													<!--<li><a class="nav-tab-link" data-type="<?php echo $post_type; ?>-all" href="#<?php echo $post_type; ?>-all"><?php _e('View All','nh-ynaa'); ?></a></li>-->
													<li>
														<a href="#tabs-panel-posttype-<?php echo $post_type; ?>-search" data-type="tabs-panel-posttype-<?php echo $post_type; ?>-search" class="nav-tab-link">
															Suchen				</a>
													</li>											
												</ul><!-- .posttype-tabs -->
												<div class="tabs-panel tabs-panel-active" id="tabs-panel-posttype-<?php echo $post_type; ?>-most-recent">
													<ul class="categorychecklist form-no-clear" id="<?php echo $post_type; ?>checklist-most-recent">
													<?php
													$i=0;
													$li = "";
													while ( $the_query->have_posts() ) : $the_query->the_post();
														
														$temp = "";
														$temp .= '<li>';
														$temp .=  '<input type="hidden" value="'.$post_type.'" name="type-menu-item-'.$post_type.$menu_id.'" id="type-menu-item-'.$post_type.$menu_id.'" >';
														$temp .=  '<input type="hidden" value="html" name="link-typ-menu-item-'.$post_type.$menu_id.'" id="link-type-menu-item-'.$post_type.$menu_id.'">';
														//$temp .=  '<input type="hidden" value="'.$this->shortenText($the_query->post->post_title).'" name="title-menu-item-'.$post_type.$menu_id.'" id="title-menu-item-'.$post_type.$menu_id.'">';
														$temp .=  '<label class="menu-item-title">';
														$temp .=  '<input type="checkbox" value="'.$the_query->post->ID.'" name="menu-item-'.$post_type.$menu_id.'" class="menu-item-checkbox" /> ';										
														$temp .=  $this->shortenText($the_query->post->post_title).'</label>';													
														$temp .=  '</li>';
														echo $temp;
														$li .= $temp;
														$i++;
														$menu_id++;
													endwhile; 
													?>
													</ul>
												</div><!-- /.tabs-panel -->
												
												<!--<div class="tabs-panel" id="<?php echo $post_type; ?>-all">
													<ul class="categorychecklist form-no-clear" data-wp-lists="list:<?php echo $post_type; ?>" id="<?php echo $post_type; ?>checklist">
													<?php
														/*echo $li;
														while ( $the_query->have_posts() ) : $the_query->the_post();											
															echo '<li>';
															echo '<input type="hidden" value="'.$post_type.'" name="type-menu-item-'.$post_type.$menu_id.'" id="type-menu-item-'.$post_type.$menu_id.'">';
															echo '<input type="hidden" value="html" name="link-typ-menu-item-'.$post_type.$menu_id.'" id="link-type-menu-item-'.$post_type.$menu_id.'">';
															//echo '<input type="hidden" value="'.$this->shortenText($the_query->post->post_title).'" name="title-menu-item-'.$post_type.$menu_id.'" id="title-menu-item-'.$post_type.$menu_id.'">';
															echo '<label class="menu-item-title">';
															echo '<input type="checkbox" value="'.$the_query->post->ID.'" name="menu-item-'.$post_type.$menu_id.'" class="menu-item-checkbox" /> ';										
															echo $this->shortenText($the_query->post->post_title).'</label>';													
															echo '</li>';												
															$menu_id++;
														endwhile; */
													?>											
													</ul>
												</div>--><!-- /.tabs-panel -->
                                                <div id="tabs-panel-posttype-<?php echo $post_type; ?>-search" class="tabs-panel">
                                                        <p class="quick-search-wrap">
                                                			<input type="search" name="quick-search-posttype-<?php echo $post_type; ?>" value="" title="Suchen" class="quick-search" autocomplete="off">
                                                            <input type="hidden" value="<?php echo $post_type; ?>" class="search-post-type" />
                                                             
                                                            <span class="spinner" style="display: none;"></span>
                                                			<input type="submit" value="Suchen" class="button button-small quick-search-submit hide-if-js" id="submit-quick-search-posttype-page" name="submit">		</p>
                                
                                                    <ul class="categorychecklist form-no-clear" data-wp-lists="list:page" id="page-search-checklist"></ul>
                                                </div>
												
												<p class="button-controls">
													<span class="list-controls"><a class="select-all" href="<?php echo $_SERVER['PHP_SELF']; ?>?page=ynaa_plugin_options&page-tab=all&amp;selectall=1#posttype-<?php echo $post_type; ?>"><?php _e('Select All'); ?></a></span>
													<span class="add-to-menu">
														<input type="submit" id="submit-posttype-<?php echo $post_type; ?>" name="add-post-type-menu-item" value="<?php _e('Add to Homepreset','nh-ynaa'); ?>" class="button-secondary submit-add-to-homepreset right">
														<span class="spinner"></span>
													</span>
												</p>
											</div><!-- /.posttypediv -->										
											
											<?php
										else: 
											_e('No items.');
										endif; //End Post Query
									?>
									</div><!-- .inside -->
								</div><!-- .accordion-section-content -->
							</li><!-- .accordion-section -->
						<?php
							
							
						}
					}					
				?>	
                <?php if(true) { ?>
        		<li id="add-app-menus" class="control-section accordion-section   add-app-menus">
				<h3 title="<?php _e('App Extras').'sasa'; ?>" tabindex="0" class="accordion-section-title hndle"><?php _e('App Extras'); ?></h3>
				<div class="accordion-section-content ">
					<div class="inside">
						<div id="app-menusdiv" class="customlinkdiv">		
							<ul class="categorychecklist form-no-clear" id="app-checklist-all">
								<?php
								/*$args=array(
								  'orderby' => 'name',			  
								  'order' => 'ASC'
								);
								$categories = get_categories( $args );
								*/
						
								foreach ( $this->appmenus_pre as $appmenu_pre ) {
									//if($appmenu_pre['id']!=-1 &&  $appmenu_pre['id'] !=-2 &&  $appmenu_pre['id'] !=-98 && $appmenu_pre['id'] !=-3 ) continue;
									//if($appmenu_pre['id']==-1 && !$this->general_settings['eventplugin']) continue;	
									
									echo '<li>';
									echo '<input type="hidden" value="'.$appmenu_pre['type'].'" name="type-menu-item-'.$appmenu_pre['type'].$menu_id.'" id="type-menu-item-'.$appmenu_pre['type'].$menu_id.'">';
									echo '<input type="hidden" value="cat" name="link-typ-menu-item-'.$appmenu_pre['type'].$menu_id.'" id="link-type-menu-item-'.$appmenu_pre['type'].$menu_id.'">';
									echo '<input type="hidden" value="'.$appmenu_pre['title'].'" name="title-menu-item-'.$appmenu_pre['type'].$menu_id.'" id="title-menu-item-'.$appmenu_pre['type'].$menu_id.'">';
									echo '<label class="menu-item-title">';
									echo '<input type="checkbox" value="'.$appmenu_pre['id'].'" name="menu-item-'.$appmenu_pre['type'].$menu_id.'" class="menu-item-checkbox" /> ';										
									echo $appmenu_pre['title'].'</label>';													
									echo '</li>';
									$menu_id++;
								}
								
									//wp_category_checklist();
									
								?>
							</ul>
							<p class="button-controls">
								<!--<span class="list-controls"><a class="select-all" href="<?php echo $_SERVER['PHP_SELF']; ?>?page=ynaa_plugin_options&page-tab=all&amp;selectall=1#category ?>"><?php //_e('Select All'); ?></a></span>-->
								<span class="add-to-menu">
									<input type="submit" id="submit-customcategorydiv" name="add-custom-menu-item" value="<?php _e('Add to Homepreset','nh-ynaa'); ?>" class="button-secondary submit-add-to-homepreset right">
									<span class="spinner"></span>
								</span>
							</p>

						</div><!-- /.customlinkdiv -->
					</div><!-- .inside -->
				</div><!-- .accordion-section-content -->
			</li> 
            <?php } ?>       					
		</ul><!-- .outer-border -->
        <input type="hidden" value="<?php echo $menu_id; ?>" id="menu_id_counter">
		</div>
	</div><!-- /#menu-settings-column -->

	<div id="menu-management-liquid" >
		<div id="menu-management" class="homepresetpreview">
			<div class="menu-edit ">
				<div id="nav-menu-header">
					<div class="major-publishing-actions">						
						<div class="publishing-action"><?php _e('Homepresets','nh-ynaa'); ?></div>
					</div><!-- END .major-publishing-actions -->					
				</div><!-- END .nav-menu-header -->
					<div id="post-body">
						
						<div id="post-body-content" class="">
							<h3><?php _e('Homepresets','nh-ynaa'); ?></h3>
							<div class="drag-instructions post-body-plain">
								<p><?php _e('Here you change the settings for home screen.','nh-ynaa'); ?></p>
							</div>							
							<div id="menu-accordion">
                            	
								<ul id="menu-to-edit" class="menu nav-menus-php nh-homepreset-ul">                              
                                	
									<?php
									//var_dump($this->homepreset_settings);
									if($this->homepreset_settings['items'] && !empty($this->homepreset_settings['items'])){
										$menuitems= $this->homepreset_settings['items'];
										$i = 0;							
										foreach($menuitems as $v=>$ar){									
										
										?>
											<li id="homepresetli<?php echo $v; ?>" class="floatli">
                                                 
                                                 <?php 
												 	if($ar['type']=='cat' || $ar['type']=='fb' || $ar['type']=='events' ||$ar['type']=='map' ||$ar['type']=='webview') { 
														echo '<div class="hpdiv" id="hpdiv'.$v.'" ';
														if($this->categories_settings[$ar['id']]['img']) echo  'style="background-image:url(\''.($this->categories_settings[$ar['id']]['img']).'\');"';
														elseif(($ar['img'])) echo  'style="background-image:url(\''.($ar['img']).'\');"';
														else echo 'style="background-color:'.$this->general_settings['c1'].';"';
														echo ' >';
												 	}
												 	else {
	                                                	echo '<div class="hpdiv" id="hpdiv'.$v.'" style="background-image:url(\''.($this->nh_getthumblepic($ar['id'])).'\');">';
    	                                            }
												   ?>
                                                    <div class="ttitle" id="hptitle<?php echo $v; ?>div"><?php echo ($ar['title']); ?></div>
                                                    <?php if($ar['type']=='cat' || $ar['type']=='fb' || $ar['type']=='events' ||$ar['type']=='webview') { ?>
                                                    
                                                    <div class="setdefaultcatpic" <?php if($ar['type']!='webview') echo ' style="display:none;"'; ?>><a id="upload_image_button<?php echo $v; ?>" class="upload_image_button" href="#" name="<?php echo $this->homepreset_settings_key; ?>_items_<?php echo $v; ?>_img"><?php _e('Set default image','nh-ynaa'); ?></a></div>
           											<input type="hidden" value="<?php echo $ar['img']; ?>" id="<?php echo $this->homepreset_settings_key; ?>_items_<?php echo $v; ?>_img" name="<?php echo $this->homepreset_settings_key; ?>[items][<?php echo $v; ?>][img]" data-id="hpdiv<?php echo $v; ?>" />                                         
                                                    <?php } ?>
                                               </div>
                                                <div><input type="text" value="<?php echo $ar['title']; ?>" id="hptitle<?php echo $v; ?>" name="<?php echo $this->homepreset_settings_key; ?>[items][<?php echo $v; ?>][title]" class="hptitle" /></div>
                                                
                                                <div class="h30">
                                                <?php if($ar['type']=='webview') { ?>
                                                <input type="text" value="<?php echo $ar['url']; ?>"  name="<?php echo $this->homepreset_settings_key; ?>[items][<?php echo $v; ?>][url]"  />
                                                <?php } ?>
                                                </div>
                                                <div><input type="checkbox" name="<?php echo $this->homepreset_settings_key; ?>[items][<?php echo $v; ?>][allowRemove]" id="allowRemove<?php echo $v; ?>" value="1" <?php if($ar['allowRemove']) echo ' checked="checked" '; ?> /><label for="allowRemove<?php echo $v; ?>"> <?php _e('Allow hide on Startscreen','nh-ynaa'); ?></label></div>
                                                <div>
                                                    <a href="<?php echo $v; ?>" class="delhp"><?php _e('Delete'); ?></a>
                                                    <input type="hidden" value="<?php echo $ar['id']; ?>"  name="<?php echo $this->homepreset_settings_key; ?>[items][<?php echo $v; ?>][id]"   />
                                                    <input type="hidden" value="<?php echo $ar['type']; ?>"  name="<?php echo $this->homepreset_settings_key; ?>[items][<?php echo $v; ?>][type]" />                                                     
                                                    <input type="hidden" value="<?php echo $v; ?>" name="<?php echo $this->homepreset_settings_key; ?>[items][<?php echo $v; ?>][id2]" id="menu-id<?php $v; ?>" class="homepreset-id-ynaa" />
                                                </div>
                                            </li><!--End Hompreset-item -->
										<?php
											$i++;
											//if($i % 2 == 0 ) echo '<li class="empty_li_clear"></li>';
										}
										?>
										
											
										<?php
									}
									?>	
									
									
                                    
								</ul>		
                                <div style="clear:both;"></div>
							</div><!-- /#menu-accordion -->
						</div><!-- /#post-body-content -->
					</div><!-- /#post-body -->
					<div id="nav-menu-footer">
						<div class="major-publishing-actions">
							<div class="publishing-action">&nbsp;<?php //submit_button(); ?>
								<!--<input type="submit" name="save_menu" id="save_menu_header" class="button button-primary menu-save" value="Men� erstellen">-->
							</div><!-- END .publishing-action -->
						</div><!-- END .major-publishing-actions -->
					</div><!-- /#nav-menu-footer -->
				</div><!-- /.menu-edit -->
			<!--</form>--><!-- /#update-nav-menu -->
		</div><!-- /#menu-management -->
	</div><!-- /#menu-management-liquid -->	
</div><!-- /#nav-menus-frame -->
<div style="clear:both;"></div>