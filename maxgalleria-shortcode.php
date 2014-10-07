<?php
class MaxGalleriaShortcode {
	public function __construct() {
		add_shortcode('maxgallery', array($this, 'maxgallery_shortcode'));
	}
	
	public function maxgallery_shortcode($atts) {	
		extract(shortcode_atts(array(
			'id' => '',
			'name' => ''
		), $atts));
		
		$gallery_id = sanitize_text_field("{$id}");
		$gallery_name = sanitize_text_field("{$name}");
		
		$output = '';
		$gallery = null;
		
		if ($gallery_id != '' && $gallery_name != '') {
			// If both given, the id wins
			$gallery = get_post($gallery_id);
		}

		if ($gallery_id != '' && $gallery_name == '') {
			// Get the gallery by id
			$gallery = get_post($gallery_id);
		}
		
		if ($gallery_id == '' && $gallery_name != '') {
			// Get the gallery by name
			$query = new WP_Query(array('name' => $gallery_name, 'post_type' => MAXGALLERIA_POST_TYPE));
			$gallery = $query->get_queried_object();
		}
    		
		if (isset($gallery) && $gallery->post_status == 'publish') {
        
      global $wpdb;
      
      $options = new MaxGalleryOptions($gallery->ID);
      
      if($options->is_video_gallery())
        $items_per_page = get_post_meta( $gallery->ID, 'maxgallery_videos_per_page', true );
      else
        $items_per_page = get_post_meta( $gallery->ID, 'maxgallery_images_per_page', true );
      
      if(($items_per_page == '') || ($items_per_page == 0)) {
        // if blank, get all attachments by setting items_per_page to -1
        $items_per_page = -1;
      }   
      else { 
        // calculate total number of pages; for this we need the total number of attachments
        $sql = "select SQL_CALC_FOUND_ROWS ID from " . $wpdb->prefix . "posts where post_parent = $gallery->ID and post_type = 'attachment'";
        $rows = $wpdb->get_results($sql);
        $count = $wpdb->get_row("select FOUND_ROWS()", ARRAY_A);
        $total_posts = $count['FOUND_ROWS()'];
      }
                  
      $paged = (get_query_var('page')) ? get_query_var('page') : 1;
			$args = array(
				'post_parent' => $gallery->ID,
				'post_type' => 'attachment',
				'orderby' => 'menu_order',
				'order' => 'asc',
				'numberposts' => -1, // All of them
                'posts_per_page' => $items_per_page,
                'paged' => $paged
			);

			$attachments = get_posts($args);
   
      $total_number_pages = ceil($total_posts / $items_per_page);
     
      foreach ($attachments as $attachment) {
        setup_postdata($attachment);
			
			if (count($attachments) > 0) {
				//$options = new MaxGalleryOptions($gallery->ID);

				global $maxgalleria;
				$templates = $maxgalleria->get_template_addons();
				
				foreach ($templates as $template) {
					if ($template['key'] == $options->get_template()) {
						$output = call_user_func($template['output'], $gallery, $attachments);
					}
				}
			}
		}
      }
    
    
   
    // display page links if we have pages
    if($items_per_page != -1) {
        $link = get_the_permalink();
        $prev = $paged-1;
        $next = $paged+1;
        $page_links  = "<div style='clear:both'></div><div class='mg-pagination'>";
        if($prev != 0) {
            $page_links .= '<a class="mg-page-previous" href="'.$link.($paged-1).'">&laquo; Previous</a>'; 
        }
        if($next <= $total_number_pages) {
            $page_links .= '<a class="mg-page-next" href="'.$link.($paged+1).'">Next &raquo;</a>'; 
        }
        $page_links .= "</div>";    
    }
    else
        $page_links = "";
    
		return $output . $page_links;
	}
}
?>