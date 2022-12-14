<?php
// Create shotcode for menu
function main_menu_code()
{
    $options = array(
        'echo' => false,
        'container' => 'nav',
        'container_class' => 'main-menu',
        'theme_location' => 'main',
        'menu_class'           => 'main-menu__list',
		'fallback_cb'     => 'Main_Navwalker::fallback',
		'walker'          => new Main_Navwalker(),
    );

    $menu = wp_nav_menu($options);
    return $menu;
}

//Create theme location 
function register_my_menus()
{
    $locations = array(
        'top' => __('Top Menu', 'bricks-child'),
        'main' => __('Main Menu', 'bricks-child'),
    );
    register_nav_menus($locations);
}
add_action('init', 'register_my_menus');

// Including file 
require_once __DIR__ . '/includes/menu/main-menu-walker.php';


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

//enable custom field wordpress when acf installed//
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

//Display Most Used Tag in Last 7 Days//
function most_use_tags()
{
  global $wpdb;
$term_ids = $wpdb->get_col("
    SELECT term_id FROM $wpdb->term_taxonomy
    INNER JOIN $wpdb->term_relationships ON $wpdb->term_taxonomy.term_taxonomy_id=$wpdb->term_relationships.term_taxonomy_id
    INNER JOIN $wpdb->posts ON $wpdb->posts.ID = $wpdb->term_relationships.object_id
    WHERE DATE_SUB(CURDATE(), INTERVAL 30 DAY) <= $wpdb->posts.post_date");

if(count($term_ids) > 0){

  $tags = get_tags(array(
    'orderby' => 'count',
    'order'   => 'DESC',
    'number'  => 7,
    'include' => $term_ids,
  ));
  $output .= '<ul>';
foreach ( (array) $tags as $tag ) {
  $output .= '<li>#<a href="' . get_tag_link ($tag->term_id) . '" rel="tag">' . $tag->name . '</a></li>';
};
$output .= '</ul>';

}

  return $output;
}


// Retrieve YT THumbnail and Set it as Featured Imaeg//

function generate_video_thumbnail($post_id, $post) {
	$current_post_thumbnail = get_post_thumbnail_id( $post_id );
    if ( 0 !== $current_post_thumbnail ) {
        return;
    }
	
	$url = get_field('url_youtube', $post_id, false);
	$oembed = _wp_oembed_get_object();
	$provider = $oembed->get_provider($url);
	$oembed_data = $oembed->fetch( $provider, $url );
	$thumbnail = $oembed_data->thumbnail_url;
	$title = get_the_title($post_id);
	
	if($url) {
		$image = media_sideload_image( $thumbnail, $post_id, $title, 'id' );
		set_post_thumbnail( $post_id, $image );
	}
}
add_action('save_post', 'generate_video_thumbnail', 12, 3);

//Get ID Oembed ACF//

function parse_video_uri( $url ) {
		
  // Parse the url 
  $parse = parse_url( $url );
  
  // Set blank variables
  $video_type = '';
  $video_id = '';
  
  // Url is http://youtu.be/xxxx
  if ( $parse['host'] == 'youtu.be' ) {
  
    $video_type = 'youtube';
    
    $video_id = ltrim( $parse['path'],'/' );	
    
  }
  
  // Url is http://www.youtube.com/watch?v=xxxx 
  // or http://www.youtube.com/watch?feature=player_embedded&v=xxx
  // or http://www.youtube.com/embed/xxxx
  if ( ( $parse['host'] == 'youtube.com' ) || ( $parse['host'] == 'www.youtube.com' ) ) {
  
    $video_type = 'youtube';
    
    parse_str( $parse['query'] );
    
    $video_id = $v;	
    
    if ( !empty( $feature ) )
      $video_id = end( explode( 'v=', $parse['query'] ) );
      
    if ( strpos( $parse['path'], 'embed' ) == 1 )
      $video_id = end( explode( '/', $parse['path'] ) );
    
  }
  
  // Url is http://www.vimeo.com
  if ( ( $parse['host'] == 'vimeo.com' ) || ( $parse['host'] == 'www.vimeo.com' ) ) {
  
    $video_type = 'vimeo';
    
    $video_id = ltrim( $parse['path'],'/' );	
          
  }
  $host_names = explode(".", $parse['host'] );
  $rebuild = ( ! empty( $host_names[1] ) ? $host_names[1] : '') . '.' . ( ! empty($host_names[2] ) ? $host_names[2] : '');
  // Url is an oembed url wistia.com
  if ( ( $rebuild == 'wistia.com' ) || ( $rebuild == 'wi.st.com' ) ) {
  
    $video_type = 'wistia';
      
    if ( strpos( $parse['path'], 'medias' ) == 1 )
        $video_id = end( explode( '/', $parse['path'] ) );
  
  }
  
  // If recognised type return video array
  if ( !empty( $video_type ) ) {
  
    $video_array = array(
      'type' => $video_type,
      'id' => $video_id
    );
  
    return $video_array;
    
  } else {
  
    return false;
    
  }
  
}

// Json Retrieve Duration by YT API key//

function getDuration(){
  $video = get_field( 'url_youtube', false, false );
  $v = parse_video_uri( $video ); 
  $vid = $v['id'];
  $apikey = "yourapikey"; // Like this AIcvSyBsLA8znZn-i-aPLWFrsPOlWMkEyVaXAcv
  $dur = file_get_contents("https://www.googleapis.com/youtube/v3/videos?part=contentDetails&id=$vid&key=$apikey");
  $VidDuration =json_decode($dur, true);
  foreach ($VidDuration['items'] as $vidTime)
  {
      $VidDuration= $vidTime['contentDetails']['duration'];
  }
  preg_match_all('/(\d+)/',$VidDuration,$parts);
  $hours = floor($parts[0][0]/60);
$minutes = $parts[0][0]%60;
$seconds = $parts[0][1];
if($hours != 0)
                return $hours.':'.$minutes.':'.$seconds;
            else
                return $minutes.':'.$seconds;

}



//Get Total ACF Image Gallery ACF //
function gettotalphotos() {
$video = count(get_field( 'gallery' ));
return $video;
}

//Remove Google FOnt From Google Map (Need Google Map api)
var head = document.getElementsByTagName('head')[0];
var insertBefore = head.insertBefore;
head.insertBefore = function(newElement, referenceElement) {
  if (newElement.href && newElement.href.indexOf('https://fonts.googleapis.com/css?family') === 0) {
    console.info('Prevented Google Font from loading!');
    return;
  }
  insertBefore.call(head, newElement, referenceElement);
};
 function initMap() {
  var map = new google.maps.Map(document.getElementById("map"), {
     center: { lat: -34.397, lng: 150.644 },
     zoom: 8,
   });
 }
 window.initMap = initMap;

/////
 
add_filter( 'bricks/dynamic_data/post_terms_links', function( $has_links, $post, $taxonomy) {
  return $taxonomy !== 'desa'; 
}, 10, 3);
