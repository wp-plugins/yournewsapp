<?php $menu_id=0;
var_dump($this->general_settings);
 ?>
<div id="nav-menus-frame">
	<div id="menu-settings-column" class="metabox-holder">
		<div class="clear"></div>
		<div class="accordion-container" id="side-sortables">
		
		
		<ul class="outer-border">
			<?php
				$args = array('name'=>"!=attachment");
				$post_types = get_post_types();
				$menu_id=0;				
				foreach( $post_types as $post_type ){
					if( !in_array( $post_type, array( 'attachment', 'revision', 'nav_menu_item' ) ) ){
					?>
						<li id="add-<?php echo $post_type; ?>" class="control-section accordion-section add-<?php echo $post_type; ?>">
							<h3 title="<?php echo ucfirst($post_type); ?>" tabindex="0" class="accordion-section-title hndle"><?php echo ucfirst($post_type); ?></h3>				
							<div class="accordion-section-content ">
								<div class="inside">
								<?php
									$args = array(
										'post_type'=> $post_type,							
										'order'    => 'post_modified',
										'fields'	=>array('id','post_title')
										);
									$the_query = new WP_Query( $args );
									if($the_query->have_posts() ) : 
										?>
										<div class="posttypediv tabclass" id="posttype-<?php echo $post_type; ?>">
											<ul class="add-menu-item-tabs">
												<li><a href="#tabs-panel-posttype-<?php echo $post_type; ?>-most-recent" data-type="tabs-panel-posttype-<?php echo $post_type; ?>-most-recent" class="nav-tab-link">Zuletzt erstellt</a></li>
												<li><a class="nav-tab-link" data-type="<?php echo $post_type; ?>-all" href="#<?php echo $post_type; ?>-all">Zeige alle</a></li>
												<!--<li>
													<a href="#tabs-panel-posttype-<?php echo $post_type; ?>-search" data-type="tabs-panel-posttype-<?php echo $post_type; ?>-search" class="nav-tab-link">
														Suchen				</a>
												</li>-->											
											</ul><!-- .posttype-tabs -->
											<div class="tabs-panel tabs-panel-active" id="tabs-panel-posttype-<?php echo $post_type; ?>-most-recent">
												<ul class="categorychecklist form-no-clear" id="<?php echo $post_type; ?>checklist-most-recent">
												<?php
												$i=0;
												$li = "";
												while ( $the_query->have_posts() ) : $the_query->the_post();
													if($i>7) break;
													$temp = "";
													$temp .= '<li>';
													$temp .=  '<input type="hidden" value="'.$post_type.'" name="type-menu-item-'.$post_type.$menu_id.'" id="type-menu-item-'.$post_type.$menu_id.'">';
													$temp .=  '<input type="hidden" value="html" name="link-typ-menu-item-'.$post_type.$menu_id.'" id="link-type-menu-item-'.$post_type.$menu_id.'">';
													$temp .=  '<input type="hidden" value="'.$this->shortenText($the_query->post->post_title).'" name="title-menu-item-'.$post_type.$menu_id.'" id="title-menu-item-'.$post_type.$menu_id.'">';
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
											
											<div class="tabs-panel" id="<?php echo $post_type; ?>-all">
												<ul class="categorychecklist form-no-clear" data-wp-lists="list:<?php echo $post_type; ?>" id="<?php echo $post_type; ?>checklist">
												<?php
													echo $li;
													while ( $the_query->have_posts() ) : $the_query->the_post();											
														echo '<li>';
														echo '<input type="hidden" value="'.$post_type.'" name="type-menu-item-'.$post_type.$menu_id.'" id="type-menu-item-'.$post_type.$menu_id.'">';
														echo '<input type="hidden" value="html" name="link-typ-menu-item-'.$post_type.$menu_id.'" id="link-type-menu-item-'.$post_type.$menu_id.'">';
														echo '<input type="hidden" value="'.$this->shortenText($the_query->post->post_title).'" name="title-menu-item-'.$post_type.$menu_id.'" id="title-menu-item-'.$post_type.$menu_id.'">';
														echo '<label class="menu-item-title">';
														echo '<input type="checkbox" value="'.$the_query->post->ID.'" name="menu-item-'.$post_type.$menu_id.'" class="menu-item-checkbox" /> ';										
														echo $this->shortenText($the_query->post->post_title).'</label>';													
														echo '</li>';												
														$menu_id++;
													endwhile; 
												?>											
												</ul>
											</div><!-- /.tabs-panel -->
											
											<p class="button-controls">
												<span class="list-controls"><a class="select-all" href="<?php echo $_SERVER['PHP_SELF']; ?>?page=ynaa_plugin_options&page-tab=all&amp;selectall=1#posttype-<?php echo $post_type; ?>">Alle ausw&auml;hlen</a></span>
												<span class="add-to-menu">
													<input type="submit" id="submit-posttype-<?php echo $post_type; ?>" name="add-post-type-menu-item" value="Zum Men&auml; hinzuf&auml;gen" class="button-secondary submit-add-to-menu right">
													<span class="spinner"></span>
												</span>
											</p>
										</div><!-- /.posttypediv -->										
										
										<?php
									else: 
										echo 'Keine Elemente';
									endif; //End Post Query
								?>
								</div><!-- .inside -->
							</div><!-- .accordion-section-content -->
						</li><!-- .accordion-section -->
					<?php
						
						
					}
				}
				//var_dump($post_types);
			?>
			<li id="add-page" class="control-section accordion-section  open add-page">
				<h3 title="Seiten" tabindex="0" class="accordion-section-title hndle">Seiten</h3>				
				<div class="accordion-section-content ">
					<div class="inside">						
					<?php
						
						$args = array(
							'sort_order' => 'DESC',
							'sort_column' => 'post_modified',
						);
						$pages = get_pages($args);
						if($pages) {
					?>
							<div class="posttypediv tabclass" id="posttype-page">
								<ul class="add-menu-item-tabs">
									<li><a href="#tabs-panel-posttype-page-most-recent" data-type="tabs-panel-posttype-page-most-recent" class="nav-tab-link">Zuletzt erstellt</a></li>
									<li><a class="nav-tab-link" data-type="page-all" href="#page-all">Zeige alle</a></li>
									<!--<li>
										<a href="#tabs-panel-posttype-page-search" data-type="tabs-panel-posttype-page-search" class="nav-tab-link">
											Suchen				</a>
									</li>-->
									
								</ul><!-- .posttype-tabs -->
								
								<div class="tabs-panel tabs-panel-active" id="tabs-panel-posttype-page-most-recent">
									<ul class="categorychecklist form-no-clear" id="pagechecklist-most-recent">
									<?php
										$i=0;
										foreach($pages as $page){
											if($i>7) break;
											echo '<li>';
											echo '<input type="hidden" value="page" name="type-menu-item-page'.$menu_id.'" id="type-menu-item-page'.$menu_id.'">';
											echo '<input type="hidden" value="html" name="link-typ-menu-item-page'.$menu_id.'" id="link-type-menu-item-page'.$menu_id.'">';
											echo '<input type="hidden" value="'.$page->post_title.'" name="title-menu-item-page'.$menu_id.'" id="title-menu-item-page'.$menu_id.'">';
											echo '<label class="menu-item-title">';
											echo '<input type="checkbox" value="'.$page->ID.'" name="menu-item-page'.$menu_id.'" class="menu-item-checkbox" /> ';										
											echo $page->post_title.'</label>';
											
											echo '</li>';
											$i++;
											$menu_id++;
										}
									?>
					
									</ul>
								</div><!-- /.tabs-panel -->
								<!--
								<div id="tabs-panel-posttype-page-search" class="tabs-panel tabs-panel-inactive">
									<p class="quick-search-wrap">
									<input type="search" name="quick-search-posttype-page" value="" title="Suchen" class="quick-search input-with-default-title">
									<span class="spinner"></span>
									<input type="submit" value="Suchen" class="button button-small quick-search-submit hide-if-js" id="submit-quick-search-posttype-page" name="submit">			</p>
									<ul class="categorychecklist form-no-clear" data-wp-lists="list:page" id="page-search-checklist">
									</ul>
								</div><!-- /.tabs-panel -->

								<div class="tabs-panel" id="page-all">
									<ul class="categorychecklist form-no-clear" data-wp-lists="list:page" id="pagechecklist">
									<?php
									
										foreach($pages as $page){											
											echo '<li>';
											echo '<label class="menu-item-title">';
											echo '<input type="checkbox" value="'.$page->ID.'" name="menu-item['.$menu_id.']" class="menu-item-checkbox" /> ';
											echo $page->post_title.'</label>';											
											echo '</li>';
											
											$menu_id++;
										}
									?>
									<!--	<li><label class="menu-item-title"><input type="checkbox" value="-3" name="menu-item[-3][menu-item-object-id]" class="menu-item-checkbox add-to-top"> Startseite: Startseite</label><input type="hidden" value="0" name="menu-item[-3][menu-item-db-id]" class="menu-item-db-id"><input type="hidden" value="" name="menu-item[-3][menu-item-object]" class="menu-item-object"><input type="hidden" value="" name="menu-item[-3][menu-item-parent-id]" class="menu-item-parent-id"><input type="hidden" value="custom" name="menu-item[-3][menu-item-type]" class="menu-item-type"><input type="hidden" value="Startseite" name="menu-item[-3][menu-item-title]" class="menu-item-title"><input type="hidden" value="http://localhost/wordpress-test/" name="menu-item[-3][menu-item-url]" class="menu-item-url"><input type="hidden" value="" name="menu-item[-3][menu-item-target]" class="menu-item-target"><input type="hidden" value="" name="menu-item[-3][menu-item-attr_title]" class="menu-item-attr_title"><input type="hidden" value="" name="menu-item[-3][menu-item-classes]" class="menu-item-classes"><input type="hidden" value="" name="menu-item[-3][menu-item-xfn]" class="menu-item-xfn"></li>
										<li><label class="menu-item-title"><input type="checkbox" value="2" name="menu-item[-5][menu-item-object-id]" class="menu-item-checkbox"> Beispiel-Seite</label><input type="hidden" value="0" name="menu-item[-5][menu-item-db-id]" class="menu-item-db-id"><input type="hidden" value="page" name="menu-item[-5][menu-item-object]" class="menu-item-object"><input type="hidden" value="0" name="menu-item[-5][menu-item-parent-id]" class="menu-item-parent-id"><input type="hidden" value="post_type" name="menu-item[-5][menu-item-type]" class="menu-item-type"><input type="hidden" value="Beispiel-Seite" name="menu-item[-5][menu-item-title]" class="menu-item-title"><input type="hidden" value="http://localhost/wordpress-test/beispiel-seite/" name="menu-item[-5][menu-item-url]" class="menu-item-url"><input type="hidden" value="" name="menu-item[-5][menu-item-target]" class="menu-item-target"><input type="hidden" value="" name="menu-item[-5][menu-item-attr_title]" class="menu-item-attr_title"><input type="hidden" value="" name="menu-item[-5][menu-item-classes]" class="menu-item-classes"><input type="hidden" value="" name="menu-item[-5][menu-item-xfn]" class="menu-item-xfn"></li>
										<li><label class="menu-item-title"><input type="checkbox" value="10" name="menu-item[-6][menu-item-object-id]" class="menu-item-checkbox"> Gebietsplaner</label><input type="hidden" value="0" name="menu-item[-6][menu-item-db-id]" class="menu-item-db-id"><input type="hidden" value="page" name="menu-item[-6][menu-item-object]" class="menu-item-object"><input type="hidden" value="0" name="menu-item[-6][menu-item-parent-id]" class="menu-item-parent-id"><input type="hidden" value="post_type" name="menu-item[-6][menu-item-type]" class="menu-item-type"><input type="hidden" value="Gebietsplaner" name="menu-item[-6][menu-item-title]" class="menu-item-title"><input type="hidden" value="http://localhost/wordpress-test/gebietsplaner/" name="menu-item[-6][menu-item-url]" class="menu-item-url"><input type="hidden" value="" name="menu-item[-6][menu-item-target]" class="menu-item-target"><input type="hidden" value="" name="menu-item[-6][menu-item-attr_title]" class="menu-item-attr_title"><input type="hidden" value="" name="menu-item[-6][menu-item-classes]" class="menu-item-classes"><input type="hidden" value="" name="menu-item[-6][menu-item-xfn]" class="menu-item-xfn"></li>
									-->
									</ul>
								</div><!-- /.tabs-panel -->

								<p class="button-controls">
									<span class="list-controls"><a class="select-all" href="<?php echo $_SERVER['PHP_SELF']; ?>?page=ynaa_plugin_options&page-tab=all&amp;selectall=1#posttype-page">Alle ausw&auml;hlen</a></span>
									<span class="add-to-menu">
										<input type="submit" id="submit-posttype-page" name="add-post-type-menu-item" value="Zum Men&auml; hinzuf&auml;gen" class="button-secondary submit-add-to-menu right">
										<span class="spinner"></span>
									</span>
								</p>
							</div><!-- /.posttypediv -->
						<?php
						}
						else {
							echo 'Keine Elemente';
						}
						?>
						
					</div><!-- .inside -->
				</div><!-- .accordion-section-content -->
			</li><!-- .accordion-section -->
			<li id="add-post" class="control-section accordion-section  add-post">
				<h3 title="Beitrag" tabindex="0" class="accordion-section-title hndle">Beitrag</h3>
				<div class="accordion-section-content ">
					<div class="inside">
					<?php
						$args = array(
							'sort_order' => 'DESC',
							'sort_column' => 'post_modified',
							'posts_per_page'=>1000
						);
						$posts = get_posts($args);
						//var_dump($posts);
						if($posts) {
					?>
							<div class="posttypediv tabclass" id="posttype-post">
								<ul class="posttype-tabs add-menu-item-tabs" id="posttype-post-tabs">
									<li>
										<a href="#tabs-panel-posttype-post-most-recent" data-type="tabs-panel-posttype-post-most-recent" class="nav-tab-link">Zuletzt erstellt				</a>
									</li>
									<li>
										<a href="#post-all" data-type="post-all" class="nav-tab-link">Zeige alle</a>
									</li>
									<li>
										<a href="#tabs-panel-posttype-post-search" data-type="tabs-panel-posttype-post-search" class="nav-tab-link">Suchen</a>
									</li>
								</ul><!-- .posttype-tabs -->

								<div class="tabs-panel tabs-panel-active" id="tabs-panel-posttype-post-most-recent">
									<ul class="categorychecklist form-no-clear" id="postchecklist-most-recent">
									<?php
										$i=0;
										foreach($posts as $post){
											if($i>7) break;
											echo '<li>';
											echo '<input type="hidden" value="post" name="type-menu-item-page'.$menu_id.'" id="type-menu-item-page'.$menu_id.'">';
											echo '<input type="hidden" value="html" name="link-typ-menu-item-page'.$menu_id.'" id="link-type-menu-item-page'.$menu_id.'">';
											echo '<input type="hidden" value="'.$this->shortenText($post->post_title,25).'" name="title-menu-item-page'.$menu_id.'" id="title-menu-item-page'.$menu_id.'">';
											echo '<label class="menu-item-title">';
											echo '<input type="checkbox" value="'.$post->ID.'" name="menu-item-page'.$menu_id.'" class="menu-item-checkbox" /> ';										
											echo $this->shortenText($post->post_title).'</label>';
											
											echo '</li>';
											$i++;
											$menu_id++;
										}
									?>										
									</ul>
								</div><!-- /.tabs-panel -->

								<div id="tabs-panel-posttype-post-search" class="tabs-panel">
									<p class="quick-search-wrap">
										<input type="search" name="quick-search-posttype-post" value="" title="Suchen" class="quick-search input-with-default-title">
										<span class="spinner"></span>
										<input type="submit" value="Suchen" class="button button-small quick-search-submit hide-if-js" id="submit-quick-search-posttype-post" name="submit">			
									</p>
									<ul class="categorychecklist form-no-clear" data-wp-lists="list:post" id="post-search-checklist"></ul>
								</div><!-- /.tabs-panel -->

								<div class="tabs-panel tabs-panel-view-all" id="post-all">
									<ul class="categorychecklist form-no-clear" data-wp-lists="list:post" id="postchecklist">
									<?php
									
										foreach($posts as $post){
											
											echo '<li>';
											echo '<input type="hidden" value="post" name="type-menu-item-page'.$menu_id.'" id="type-menu-item-page'.$menu_id.'">';
											echo '<input type="hidden" value="html" name="link-typ-menu-item-page'.$menu_id.'" id="link-type-menu-item-page'.$menu_id.'">';
											echo '<input type="hidden" value="'.$this->shortenText($post->post_title,25).'" name="title-menu-item-page'.$menu_id.'" id="title-menu-item-page'.$menu_id.'">';
											echo '<label class="menu-item-title">';
											echo '<input type="checkbox" value="'.$post->ID.'" name="menu-item-page'.$menu_id.'" class="menu-item-checkbox" /> ';										
											echo $this->shortenText($post->post_title).'</label>';
											
											echo '</li>';
											
											$menu_id++;
										}
									?>			
									</ul>
								</div><!-- /.tabs-panel -->

								<p class="button-controls">
									<span class="list-controls">
										<a class="select-all" href="/wordpress-test/wp-admin/nav-menus.php?post-tab=all&amp;selectall=1#posttype-post">Alle auswählen</a>
									</span>
									
									<span class="add-to-menu">
										<input type="submit" id="submit-posttype-post" name="add-post-type-menu-item" value="Zum Men&auml; hinzuf&auml;gen" class="button-secondary submit-add-to-menu right">
										<span class="spinner"></span>
									</span>
								</p>
							</div><!-- /.posttypediv -->
						<?php
						}
						else {
							echo 'Keine Elemente';
						}
						?>	
					</div><!-- .inside -->
				</div><!-- .accordion-section-content -->
			</li><!-- .accordion-section -->
			<li id="add-custom-links" class="control-section accordion-section   add-custom-links">
						<h3 title="Links" tabindex="0" class="accordion-section-title hndle">Links</h3>
						<div class="accordion-section-content ">
							<div class="inside">
									<div id="customlinkdiv" class="customlinkdiv">
		<input type="hidden" name="menu-item[-9][menu-item-type]" value="custom">
		<p id="menu-item-url-wrap">
			<label for="custom-menu-item-url" class="howto">
				<span>URL</span>
				<input type="text" value="http://" class="code menu-item-textbox" name="menu-item[-9][menu-item-url]" id="custom-menu-item-url">
			</label>
		</p>

		<p id="menu-item-name-wrap">
			<label for="custom-menu-item-name" class="howto">
				<span>Link Text</span>
				<input type="text" title="Men&auml;element" class="regular-text menu-item-textbox input-with-default-title" name="menu-item[-9][menu-item-title]" id="custom-menu-item-name">
			</label>
		</p>

		<p class="button-controls">
			<span class="add-to-menu">
				<input type="submit" id="submit-customlinkdiv" name="add-custom-menu-item" value="Zum Men&auml; hinzuf&auml;gen" class="button-secondary submit-add-to-menu right">
				<span class="spinner"></span>
			</span>
		</p>

	</div><!-- /.customlinkdiv -->
								</div><!-- .inside -->
						</div><!-- .accordion-section-content -->
					</li><!-- .accordion-section -->
										<li id="add-category" class="control-section accordion-section   add-category">
						<h3 title="Kategorien" tabindex="0" class="accordion-section-title hndle">Kategorien</h3>
						<div class="accordion-section-content ">
							<div class="inside">
									<div class="taxonomydiv" id="taxonomy-category">
		<ul class="taxonomy-tabs add-menu-item-tabs" id="taxonomy-category-tabs">
			<li class="tabs">
				<a href="#tabs-panel-category-pop" data-type="tabs-panel-category-pop" class="nav-tab-link">
					Häufig verwendet				</a>
			</li>
			<li>
				<a href="#tabs-panel-category-all" data-type="tabs-panel-category-all" class="nav-tab-link">
					Zeige alle				</a>
			</li>
			<li>
				<a href="#tabs-panel-search-taxonomy-category" data-type="tabs-panel-search-taxonomy-category" class="nav-tab-link">
					Suchen				</a>
			</li>
		</ul><!-- .taxonomy-tabs -->

		<div class="tabs-panel tabs-panel-active" id="tabs-panel-category-pop">
			<ul class="categorychecklist form-no-clear" id="categorychecklist-pop">
				<li><label class="menu-item-title"><input type="checkbox" value="1" name="menu-item[-10][menu-item-object-id]" class="menu-item-checkbox"> Allgemein</label><input type="hidden" value="0" name="menu-item[-10][menu-item-db-id]" class="menu-item-db-id"><input type="hidden" value="category" name="menu-item[-10][menu-item-object]" class="menu-item-object"><input type="hidden" value="0" name="menu-item[-10][menu-item-parent-id]" class="menu-item-parent-id"><input type="hidden" value="taxonomy" name="menu-item[-10][menu-item-type]" class="menu-item-type"><input type="hidden" value="Allgemein" name="menu-item[-10][menu-item-title]" class="menu-item-title"><input type="hidden" value="http://localhost/wordpress-test/category/allgemein/" name="menu-item[-10][menu-item-url]" class="menu-item-url"><input type="hidden" value="" name="menu-item[-10][menu-item-target]" class="menu-item-target"><input type="hidden" value="" name="menu-item[-10][menu-item-attr_title]" class="menu-item-attr_title"><input type="hidden" value="" name="menu-item[-10][menu-item-classes]" class="menu-item-classes"><input type="hidden" value="" name="menu-item[-10][menu-item-xfn]" class="menu-item-xfn"></li>
			</ul>
		</div><!-- /.tabs-panel -->

		<div class="tabs-panel tabs-panel-view-all tabs-panel-inactive" id="tabs-panel-category-all">
						<ul class="categorychecklist form-no-clear" data-wp-lists="list:category" id="categorychecklist">
				<li><label class="menu-item-title"><input type="checkbox" value="1" name="menu-item[-11][menu-item-object-id]" class="menu-item-checkbox"> Allgemein</label><input type="hidden" value="0" name="menu-item[-11][menu-item-db-id]" class="menu-item-db-id"><input type="hidden" value="category" name="menu-item[-11][menu-item-object]" class="menu-item-object"><input type="hidden" value="0" name="menu-item[-11][menu-item-parent-id]" class="menu-item-parent-id"><input type="hidden" value="taxonomy" name="menu-item[-11][menu-item-type]" class="menu-item-type"><input type="hidden" value="Allgemein" name="menu-item[-11][menu-item-title]" class="menu-item-title"><input type="hidden" value="http://localhost/wordpress-test/category/allgemein/" name="menu-item[-11][menu-item-url]" class="menu-item-url"><input type="hidden" value="" name="menu-item[-11][menu-item-target]" class="menu-item-target"><input type="hidden" value="" name="menu-item[-11][menu-item-attr_title]" class="menu-item-attr_title"><input type="hidden" value="" name="menu-item[-11][menu-item-classes]" class="menu-item-classes"><input type="hidden" value="" name="menu-item[-11][menu-item-xfn]" class="menu-item-xfn"></li>
<li><label class="menu-item-title"><input type="checkbox" value="4" name="menu-item[-12][menu-item-object-id]" class="menu-item-checkbox"> temp</label><input type="hidden" value="0" name="menu-item[-12][menu-item-db-id]" class="menu-item-db-id"><input type="hidden" value="category" name="menu-item[-12][menu-item-object]" class="menu-item-object"><input type="hidden" value="0" name="menu-item[-12][menu-item-parent-id]" class="menu-item-parent-id"><input type="hidden" value="taxonomy" name="menu-item[-12][menu-item-type]" class="menu-item-type"><input type="hidden" value="temp" name="menu-item[-12][menu-item-title]" class="menu-item-title"><input type="hidden" value="http://localhost/wordpress-test/category/temp/" name="menu-item[-12][menu-item-url]" class="menu-item-url"><input type="hidden" value="" name="menu-item[-12][menu-item-target]" class="menu-item-target"><input type="hidden" value="" name="menu-item[-12][menu-item-attr_title]" class="menu-item-attr_title"><input type="hidden" value="" name="menu-item[-12][menu-item-classes]" class="menu-item-classes"><input type="hidden" value="" name="menu-item[-12][menu-item-xfn]" class="menu-item-xfn"></li>
<li><label class="menu-item-title"><input type="checkbox" value="2" name="menu-item[-13][menu-item-object-id]" class="menu-item-checkbox"> test</label><input type="hidden" value="0" name="menu-item[-13][menu-item-db-id]" class="menu-item-db-id"><input type="hidden" value="category" name="menu-item[-13][menu-item-object]" class="menu-item-object"><input type="hidden" value="0" name="menu-item[-13][menu-item-parent-id]" class="menu-item-parent-id"><input type="hidden" value="taxonomy" name="menu-item[-13][menu-item-type]" class="menu-item-type"><input type="hidden" value="test" name="menu-item[-13][menu-item-title]" class="menu-item-title"><input type="hidden" value="http://localhost/wordpress-test/category/test/" name="menu-item[-13][menu-item-url]" class="menu-item-url"><input type="hidden" value="" name="menu-item[-13][menu-item-target]" class="menu-item-target"><input type="hidden" value="" name="menu-item[-13][menu-item-attr_title]" class="menu-item-attr_title"><input type="hidden" value="" name="menu-item[-13][menu-item-classes]" class="menu-item-classes"><input type="hidden" value="" name="menu-item[-13][menu-item-xfn]" class="menu-item-xfn">
<ul class="children">
	<li><label class="menu-item-title"><input type="checkbox" value="3" name="menu-item[-14][menu-item-object-id]" class="menu-item-checkbox"> test2</label><input type="hidden" value="0" name="menu-item[-14][menu-item-db-id]" class="menu-item-db-id"><input type="hidden" value="category" name="menu-item[-14][menu-item-object]" class="menu-item-object"><input type="hidden" value="0" name="menu-item[-14][menu-item-parent-id]" class="menu-item-parent-id"><input type="hidden" value="taxonomy" name="menu-item[-14][menu-item-type]" class="menu-item-type"><input type="hidden" value="test2" name="menu-item[-14][menu-item-title]" class="menu-item-title"><input type="hidden" value="http://localhost/wordpress-test/category/test/test2/" name="menu-item[-14][menu-item-url]" class="menu-item-url"><input type="hidden" value="" name="menu-item[-14][menu-item-target]" class="menu-item-target"><input type="hidden" value="" name="menu-item[-14][menu-item-attr_title]" class="menu-item-attr_title"><input type="hidden" value="" name="menu-item[-14][menu-item-classes]" class="menu-item-classes"><input type="hidden" value="" name="menu-item[-14][menu-item-xfn]" class="menu-item-xfn"></li>

</ul></li>
			</ul>
					</div><!-- /.tabs-panel -->

		<div id="tabs-panel-search-taxonomy-category" class="tabs-panel tabs-panel-inactive">
						<p class="quick-search-wrap">
				<input type="search" name="quick-search-taxonomy-category" value="" title="Suchen" class="quick-search input-with-default-title">
				<span class="spinner"></span>
				<input type="submit" value="Suchen" class="button button-small quick-search-submit hide-if-js" id="submit-quick-search-taxonomy-category" name="submit">			</p>

			<ul class="categorychecklist form-no-clear" data-wp-lists="list:category" id="category-search-checklist">
						</ul>
		</div><!-- /.tabs-panel -->

		<p class="button-controls">
			<span class="list-controls">
				<a class="select-all" href="/wordpress-test/wp-admin/nav-menus.php?category-tab=all&amp;selectall=1#taxonomy-category">Alle auswählen</a>
			</span>

			<span class="add-to-menu">
				<input type="submit" id="submit-taxonomy-category" name="add-taxonomy-menu-item" value="Zum Men&auml; hinzuf&auml;gen" class="button-secondary submit-add-to-menu right">
				<span class="spinner"></span>
			</span>
		</p>

	</div><!-- /.taxonomydiv -->
								</div><!-- .inside -->
						</div><!-- .accordion-section-content -->
					</li><!-- .accordion-section -->
										<li id="add-post_tag" class="control-section accordion-section hide-if-js  add-post_tag">
						<h3 title="Schlagworte" tabindex="0" class="accordion-section-title hndle">Schlagworte</h3>
						<div class="accordion-section-content ">
							<div class="inside">
								<p>Keine Elemente.</p>							</div><!-- .inside -->
						</div><!-- .accordion-section-content -->
					</li><!-- .accordion-section -->
										<li id="add-post_format" class="control-section accordion-section   add-post_format">
						<h3 title="Formatvorlage" tabindex="0" class="accordion-section-title hndle">Formatvorlage</h3>
						<div class="accordion-section-content ">
							<div class="inside">
								<p>Keine Elemente.</p>							</div><!-- .inside -->
						</div><!-- .accordion-section-content -->
					</li><!-- .accordion-section -->
										<li id="add-attachment_category" class="control-section accordion-section   add-attachment_category">
						<h3 title="Att. Categories" tabindex="0" class="accordion-section-title hndle">Att. Categories</h3>
						<div class="accordion-section-content ">
							<div class="inside">
								<p>Keine Elemente.</p>							</div><!-- .inside -->
						</div><!-- .accordion-section-content -->
					</li><!-- .accordion-section -->
										<li id="add-attachment_tag" class="control-section accordion-section   add-attachment_tag">
						<h3 title="Att. Tags" tabindex="0" class="accordion-section-title hndle">Att. Tags</h3>
						<div class="accordion-section-content ">
							<div class="inside">
								<p>Keine Elemente.</p>							</div><!-- .inside -->
						</div><!-- .accordion-section-content -->
					</li><!-- .accordion-section -->
							</ul><!-- .outer-border -->
	</div>
	</div>
	<!-- /#menu-settings-column -->

	<div id="menu-management-liquid" >
		<div id="menu-management">
			<div class="menu-edit ">
				<div id="nav-menu-header">
					<div class="major-publishing-actions">						
						<div class="publishing-action">App Men&uuml;</div>
					</div><!-- END .major-publishing-actions -->					
				</div><!-- END .nav-menu-header -->
					<div id="post-body">
						
						<div id="post-body-content" class="">
							<h3>Men&uuml; Struktur</h3>
							<div class="drag-instructions post-body-plain">
								<p>Hier k&ouml;nnen Sie die App Men&uuml; generieren</p>
							</div>							
							<div id="menu-accordion">
								<ul id="menu-to-edit" class="menu nav-menus-php">
									<li class="menu-item menu-item-depth-0 menu-item-custom menu-item-edit-inactive pending" id="menu-item-68" style="display: list-item;">
										<dl class="menu-item-bar">
										<dt class="menu-item-handle">
											<span class="item-title"><span class="menu-item-title">Startseite</span> <span style="display: none;" class="is-submenu">sub item</span></span>
											<span class="item-controls">
												<!--<span class="item-type">Benutzerdefiniert</span>-->												
												<a href="http://localhost/wordpress-test/wp-admin/nav-menus.php?edit-menu-item=68#menu-item-settings-68" title="Startseite. Menu item 1 of 3." id="edit-68" class="item-edit">Startseite. Menu item 1 of 3.</a>
											</span>
										</dt>
									</dl>

										<div id="menu-item-settings-68" class="menu-item-settings">
															<p class="field-url description description-wide">
												<label for="edit-menu-item-url-68">
													URL<br>
													<input type="text" value="http://localhost/wordpress-test/" name="menu-item-url[68]" class="widefat code edit-menu-item-url" id="edit-menu-item-url-68">
												</label>
											</p>
														<p class="description description-thin">
											<label for="edit-menu-item-title-68">
												Angezeigter Name<br>
												<input type="text" value="Startseite" name="menu-item-title[68]" class="widefat edit-menu-item-title" id="edit-menu-item-title-68">
											</label>
										</p>
										<p class="description description-thin">
											<label for="edit-menu-item-attr-title-68">
												HTML-Attribut title (optional)<br>
												<input type="text" value="" name="menu-item-attr-title[68]" class="widefat edit-menu-item-attr-title" id="edit-menu-item-attr-title-68">
											</label>
										</p>
										<p class="field-link-target description hidden-field">
											<label for="edit-menu-item-target-68">
												<input type="checkbox" name="menu-item-target[68]" value="_blank" id="edit-menu-item-target-68">
												Link in neuem Fenster oder Tab öffnen					</label>
										</p>
										<p class="field-css-classes description description-thin hidden-field">
											<label for="edit-menu-item-classes-68">
												CSS-Klassen (optional)<br>
												<input type="text" value="" name="menu-item-classes[68]" class="widefat code edit-menu-item-classes" id="edit-menu-item-classes-68">
											</label>
										</p>
										<p class="field-xfn description description-thin hidden-field">
											<label for="edit-menu-item-xfn-68">
												Link-Beziehungen (XFN)<br>
												<input type="text" value="" name="menu-item-xfn[68]" class="widefat code edit-menu-item-xfn" id="edit-menu-item-xfn-68">
											</label>
										</p>
										<p class="field-description description description-wide hidden-field">
											<label for="edit-menu-item-description-68">
												Beschreibung<br>
												<textarea name="menu-item-description[68]" cols="20" rows="3" class="widefat edit-menu-item-description" id="edit-menu-item-description-68"></textarea>
												<span class="description">Die Beschreibung wird im Menü angezeigt, sofern das benutzte Theme das unterstützt.</span>
											</label>
										</p>

										<p class="field-move hide-if-no-js description description-wide">
											<label>
												<span>Move</span>
												<a class="menus-move-up" href="#" style="display: none;">Up one</a>
												<a class="menus-move-down" href="#" style="display: inline;" title="Move down one">Down one</a>
												<a class="menus-move-left" href="#" style="display: none;"></a>
												<a class="menus-move-right" href="#" style="display: none;"></a>
												<a class="menus-move-top" href="#" style="display: none;">To the top</a>
											</label>
										</p>

										<div class="menu-item-actions description-wide submitbox">
																<a href="http://localhost/wordpress-test/wp-admin/nav-menus.php?action=delete-menu-item&amp;menu-item=68&amp;_wpnonce=51e33347c7" id="delete-68" class="item-delete submitdelete deletion">Entfernen</a> <span class="meta-sep hide-if-no-js"> | </span> <a href="http://localhost/wordpress-test/wp-admin/nav-menus.php?edit-menu-item=68&amp;cancel=1377601264#menu-item-settings-68" id="cancel-68" class="item-cancel submitcancel hide-if-no-js">Abbrechen</a>
										</div>

										<input type="hidden" value="68" name="menu-item-db-id[68]" class="menu-item-data-db-id">
										<input type="hidden" value="68" name="menu-item-object-id[68]" class="menu-item-data-object-id">
										<input type="hidden" value="custom" name="menu-item-object[68]" class="menu-item-data-object">
										<input type="hidden" value="0" name="menu-item-parent-id[68]" class="menu-item-data-parent-id">
										<input type="hidden" value="1" name="menu-item-position[68]" class="menu-item-data-position">
										<input type="hidden" value="custom" name="menu-item-type[68]" class="menu-item-data-type">
									</div><!-- .menu-item-settings-->
										<ul class="menu-item-transport"></ul>
									</li>
							<li class="menu-item menu-item-depth-0 menu-item-page menu-item-edit-inactive pending" id="menu-item-69" style="display: list-item;">
								<dl class="menu-item-bar">
									<dt class="menu-item-handle">
										<span class="item-title"><span class="menu-item-title">Beispiel-Seite</span> <span style="display: none;" class="is-submenu">sub item</span></span>
										<span class="item-controls">
											<span class="item-type">Seite</span>
											<span class="item-order hide-if-js">
												<a class="item-move-up" href="http://localhost/wordpress-test/wp-admin/nav-menus.php?action=move-up-menu-item&amp;menu-item=69&amp;_wpnonce=7380bbbf13"><abbr title="Nach oben verschieben">?</abbr></a>
												|
												<a class="item-move-down" href="http://localhost/wordpress-test/wp-admin/nav-menus.php?action=move-down-menu-item&amp;menu-item=69&amp;_wpnonce=7380bbbf13"><abbr title="Nach unten verschieben">?</abbr></a>
											</span>
											<a href="http://localhost/wordpress-test/wp-admin/nav-menus.php?edit-menu-item=69#menu-item-settings-69" title="Beispiel-Seite. Menu item 2 of 3." id="edit-69" class="item-edit">Beispiel-Seite. Menu item 2 of 3.</a>
										</span>
									</dt>
								</dl>

								<div id="menu-item-settings-69" class="menu-item-settings">
													<p class="description description-thin">
										<label for="edit-menu-item-title-69">
											Angezeigter Name<br>
											<input type="text" value="Beispiel-Seite" name="menu-item-title[69]" class="widefat edit-menu-item-title" id="edit-menu-item-title-69">
										</label>
									</p>
									<p class="description description-thin">
										<label for="edit-menu-item-attr-title-69">
											HTML-Attribut title (optional)<br>
											<input type="text" value="" name="menu-item-attr-title[69]" class="widefat edit-menu-item-attr-title" id="edit-menu-item-attr-title-69">
										</label>
									</p>
									<p class="field-link-target description hidden-field">
										<label for="edit-menu-item-target-69">
											<input type="checkbox" name="menu-item-target[69]" value="_blank" id="edit-menu-item-target-69">
											Link in neuem Fenster oder Tab öffnen					</label>
									</p>
									<p class="field-css-classes description description-thin hidden-field">
										<label for="edit-menu-item-classes-69">
											CSS-Klassen (optional)<br>
											<input type="text" value="" name="menu-item-classes[69]" class="widefat code edit-menu-item-classes" id="edit-menu-item-classes-69">
										</label>
									</p>
									<p class="field-xfn description description-thin hidden-field">
										<label for="edit-menu-item-xfn-69">
											Link-Beziehungen (XFN)<br>
											<input type="text" value="" name="menu-item-xfn[69]" class="widefat code edit-menu-item-xfn" id="edit-menu-item-xfn-69">
										</label>
									</p>
									<p class="field-description description description-wide hidden-field">
										<label for="edit-menu-item-description-69">
											Beschreibung<br>
											<textarea name="menu-item-description[69]" cols="20" rows="3" class="widefat edit-menu-item-description" id="edit-menu-item-description-69"></textarea>
											<span class="description">Die Beschreibung wird im Menü angezeigt, sofern das benutzte Theme das unterstützt.</span>
										</label>
									</p>

									<p class="field-move hide-if-no-js description description-wide">
										<label>
											<span>Move</span>
											<a class="menus-move-up" href="#" style="display: inline;" title="Move up one">Up one</a>
											<a class="menus-move-down" href="#" style="display: inline;" title="Move down one">Down one</a>
											<a class="menus-move-left" href="#" style="display: none;"></a>
											<a class="menus-move-right" href="#" style="display: inline;" title="Move under Startseite">Under Startseite</a>
											<a class="menus-move-top" href="#" style="display: inline;" title="Move to the top">To the top</a>
										</label>
									</p>

									<div class="menu-item-actions description-wide submitbox">
																<p class="link-to-original">
												Ursprünglicher Name: <a href="http://localhost/wordpress-test/beispiel-seite/">Beispiel-Seite</a>						</p>
															<a href="http://localhost/wordpress-test/wp-admin/nav-menus.php?action=delete-menu-item&amp;menu-item=69&amp;_wpnonce=b3c399a42d" id="delete-69" class="item-delete submitdelete deletion">Entfernen</a> <span class="meta-sep hide-if-no-js"> | </span> <a href="http://localhost/wordpress-test/wp-admin/nav-menus.php?edit-menu-item=69&amp;cancel=1377601264#menu-item-settings-69" id="cancel-69" class="item-cancel submitcancel hide-if-no-js">Abbrechen</a>
									</div>

									<input type="hidden" value="69" name="menu-item-db-id[69]" class="menu-item-data-db-id">
									<input type="hidden" value="2" name="menu-item-object-id[69]" class="menu-item-data-object-id">
									<input type="hidden" value="page" name="menu-item-object[69]" class="menu-item-data-object">
									<input type="hidden" value="0" name="menu-item-parent-id[69]" class="menu-item-data-parent-id">
									<input type="hidden" value="1" name="menu-item-position[69]" class="menu-item-data-position">
									<input type="hidden" value="post_type" name="menu-item-type[69]" class="menu-item-data-type">
								</div><!-- .menu-item-settings-->
								<ul class="menu-item-transport"></ul>
							</li>
							<li class="menu-item menu-item-depth-0 menu-item-page menu-item-edit-inactive pending" id="menu-item-70" style="display: list-item;">
								<dl class="menu-item-bar">
									<dt class="menu-item-handle">
										<span class="item-title"><span class="menu-item-title">Gebietsplaner</span></span>
										<span class="item-controls">
											<span class="item-type">Seite</span>
											<span class="item-order hide-if-js"></span>
											<a href="#" title="Gebietsplaner. Menu item 3 of 3." id="edit-70" class="item-edit">Gebietsplaner. Menu item 3 of 3.</a>
										</span>
									</dt>
								</dl>

								<div id="menu-item-settings-70" class="menu-item-settings">
									<p class="description description-thin">
										<label for="edit-menu-item-title-70">Angezeigter Name<br>
											<input type="text" value="Gebietsplaner" name="<?php echo $this->general_settings_key; ?>[menu][0]['name']" class="widefat edit-menu-item-title" id="edit-menu-item-title-70">
											<input type="text" value="0" name="<?php echo $this->general_settings_key; ?>[menu][0]['pos']" id="menu-pos" />
											<input type="text" value="html" name="<?php echo $this->general_settings_key; ?>[menu][0]['type']" id="menu-type" />
											<input type="text" value="1" name="<?php echo $this->general_settings_key; ?>[menu][0]['status']" id="menu-type" />
										</label>
									</p>									
									<div class="menu-item-actions description-wide submitbox">
										<a href="70" id="delete-70" class="item-delete submitdelete deletion">Entfernen</a>
									</div>
								</div><!-- .menu-item-settings-->
								<ul class="menu-item-transport"></ul>
							</li></ul>		
							</div><!-- /#menu-accordion -->
						</div><!-- /#post-body-content -->
					</div><!-- /#post-body -->
					<div id="nav-menu-footer">
						<div class="major-publishing-actions">
							<div class="publishing-action">&nbsp;<?php submit_button(); ?>
								<!--<input type="submit" name="save_menu" id="save_menu_header" class="button button-primary menu-save" value="Menü erstellen">-->
							</div><!-- END .publishing-action -->
						</div><!-- END .major-publishing-actions -->
					</div><!-- /#nav-menu-footer -->
				</div><!-- /.menu-edit -->
			</form><!-- /#update-nav-menu -->
		</div><!-- /#menu-management -->
	</div><!-- /#menu-management-liquid -->	
</div><!-- /#nav-menus-frame -->