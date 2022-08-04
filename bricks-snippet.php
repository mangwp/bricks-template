<?php
// Create and value to customfield each page visited by user (Most View)//
function wpb_set_post_views($postID) {
  $count_key = 'wpb_post_views_count';
  $count = get_post_meta($postID, $count_key, true);
  if($count==''){
      $count = 0;
      delete_post_meta($postID, $count_key);
      add_post_meta($postID, $count_key, '0');
  }else{
      $count++;
      update_post_meta($postID, $count_key, $count);
  }
}
//To keep the count accurate, lets get rid of prefetching
remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);
function wpb_track_post_views ($post_id) {
  if ( !is_single() ) return;
  if ( empty ( $post_id) ) {
      global $post;
      $post_id = $post->ID;    
  }
  wpb_set_post_views($post_id);
}
add_action( 'wp_head', 'wpb_track_post_views');
add_filter('acf/settings/remove_wp_meta_box', '__return_false');

//Disable Gutenberg//
add_filter('use_block_editor_for_post', '__return_false', 10);


// Show post tags with link and a custom separator// use {echo:wpdocs_show_tags}
function wpdocs_show_tags()
{
  $post_tags = get_the_tags();
  $separator = '';
  $output = '';

  if (!empty($post_tags)) {
    foreach ($post_tags as $tag) {
      $output .= '<span class="tags-list"><a href="' . esc_attr(get_tag_link($tag->term_id)) . '">' . __($tag->name) . '</a></span>' . $separator;
    }
  }

  return trim($output, $separator);
}

// Show post cats with link and a custom separator// use {echo:wpdocs_show_tags}

function wpdocs_show_cats()
{
  $post_cats = get_the_cats();
  $separator = '';
  $output = '';

  if (!empty($post_cats)) {
    foreach ($post_cats as $cat) {
      $output .= '<span class="tags-list"><a href="' . esc_attr(get_cat_link($cat->term_id)) . '">' . __($cat->name) . '</a></span>' . $separator;
    }
  }

  return trim($output, $separator);
}
// Show related post by term using term_id // use {echo:post_tags_ids}
function post_tags_ids()
{
  return implode(',', wp_get_post_tags(get_the_ID(), array('fields' => 'ids')));
}

// Make ACF FIled REadonly

function acf_read_only_field($field)
{
    if (is_admin()) {
      $field['readonly'] = true;
    }
  return $field;
}

add_filter('acf/load_field/name=view_count', 'acf_read_only_field');

