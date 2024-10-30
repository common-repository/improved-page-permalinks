<?php
/*
Plugin Name: Improved Page Permalinks
Plugin URI: http://wordpress.org/extend/plugins/improved-page-permalinks/
Description: Improve Page Permalinks. This plugin just adds <strong>.html</strong> to the permalink pages when needed. An option can prevent this action for specific pages.
Author: Seebz
Version: 0.2.2
Author URI: http://seebz.net/
*/


define('IPP_VERSION', '0.2.2');



function ipp_check_version() {
	$stored_version = get_option('ipp_version');
	if (!$stored_version || version_compare($stored_version, IPP_VERSION, '<')) {
		ipp_upgrade();
	}
}
function ipp_upgrade() {
	global $wp_rewrite;
	
	update_option('ipp_version', IPP_VERSION);
	$wp_rewrite->flush_rules();
}



register_activation_hook(__FILE__, 'ipp_active');
register_deactivation_hook(__FILE__, 'ipp_deactive');
function ipp_active(){
	global $wp_rewrite;
	
	$wp_rewrite->flush_rules();
}	
function ipp_deactive(){
	global $wp_rewrite, $wpdb;
	
	remove_filter('page_rewrite_rules', 'ipp_filter_page_rewrite_rules');
	$wp_rewrite->flush_rules();
	$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key = '_ipp_likefolder'");
}



add_filter('init', 'ipp_filter_init');
function ipp_filter_init(){
	global $wp_rewrite;

	if( $wp_rewrite->using_permalinks() ) {
		add_filter('user_trailingslashit', 'ipp_filter_user_trailingslashit', 100, 2);
		add_filter('page_link', 'ipp_filter_page_link', 100, 3);
		
		add_action('admin_menu', 'ipp_action_admin_menu');
		add_action('wp_insert_post', 'ipp_action_wp_insert_post');

		ipp_check_version();
	}
}



add_filter('page_rewrite_rules', 'ipp_filter_page_rewrite_rules');
function ipp_filter_page_rewrite_rules($page_rewrite){
	global $wp_rewrite;
	
	$new_rules = ipp_array_map_keys('ipp_array_map_keys_callback', $page_rewrite);
	return $new_rules;
}


function ipp_filter_user_trailingslashit($string, $type){
	if( in_array($type, array('home', 'page')) && !preg_match('`(/|\.html|\.php)$`', $string) )
		return $string . '/';
	elseif( in_array($type, array('home', 'page')) && preg_match('`(\.html/)$`', $string) )
		return rtrim($string, '/');
	else
		return $string;
}


function ipp_filter_page_link( $link, $id ){
	$post = &get_post($id);
	
	// Homepage
	if($post && !$post->post_parent && rtrim($link, '/') == home_url()) {
		return $link;
	}
	// Folder
	$folder = ipp_is_page_for_posts($post->ID) || preg_match('`<!--nextpage-->`', $post->post_content) || get_post_meta($post->ID, '_ipp_likefolder', true) || ipp_has_children($post->ID);
	if($folder){
		return preg_replace('`(/|\.html)$`', '', $link) . '/';
	}
	// Page
	if( !strpos($link, '.html') ){
		return rtrim($link, '/') . '.html';
	}
	// Other ?
	return $link;
}


function ipp_action_admin_menu(){
	add_meta_box('ppp','Improved Page Permalinks','ipp_meta_box','page','side');
}
function ipp_meta_box(){
	load_plugin_textdomain('improved-page-permalinks', false, dirname(plugin_basename(__FILE__)).'/languages');
	
	global $post;
	$likefolder = false;
	if (get_post_meta($post->ID, '_ipp_likefolder', true)) {
		$likefolder = true;
	}
	$folder = ipp_is_page_for_posts($post->ID) || preg_match('`<!--nextpage-->`', $post->post_content) || ipp_has_children($post->ID);
	if( $folder ) {
	?>
	<input type="hidden" name="ipp_likefolder" value="0" />
	<input type="checkbox" checked="checked" disabled="disabled"/> 
	<label for="ipp_likefolder"><?php _e("Consider page as folder",'improved-page-permalinks') ?></label><br />
	<?php
	} else {
	?>
	<input type="hidden" name="ipp_likefolder" value="0" />
	<input type="checkbox" id="ipp_likefolder" name="ipp_likefolder" value="1" <?php checked($likefolder); ?>/> 
	<label for="ipp_likefolder"><?php _e("Consider page as folder",'improved-page-permalinks') ?></label><br />
	<?php
	}
}


function ipp_action_wp_insert_post($pID){
	if (isset($_POST['ipp_likefolder'])){
		if($_POST['ipp_likefolder']) {
			if (!get_post_meta($pID, '_ipp_likefolder', true))
				add_post_meta($pID, '_ipp_likefolder', true, true);
		} else {
			if (get_post_meta($pID, '_ipp_likefolder', true))
				delete_post_meta($pID, '_ipp_likefolder');
		}
	}
}





/*
 * Return true if 'page_id' is 'page_for_posts'
 */
function ipp_is_page_for_posts($page_id){
	return ( get_option('show_on_front')!='posts' && get_option('page_for_posts')==$page_id );
}


/*
 * Return true if 'parent_id' page has children
 */
$ipp_has_children_cache = array();
function ipp_has_children($parent_id=0){
	global $wpdb, $ipp_has_children_cache;
	
	if ( !isset($ipp_has_children_cache[$parent_id]) )
	{
		$where  = "post_type = 'page'";
		
		// list of statuses: http://wordpress.org/support/topic/314325
		$where .= " AND post_status NOT IN ('trash', 'auto-draft')";
		
		if ($parent_id >= 0) {
			$where .= $wpdb->prepare(' AND post_parent = %d ', $parent_id);
		}
		
		$query = "SELECT COUNT(ID) FROM $wpdb->posts WHERE $where";
		$ipp_has_children_cache[$parent_id] = (boolean) $wpdb->get_var($query);
	}
	
	return $ipp_has_children_cache[$parent_id];
}


/*
 * array_map() for keys
 */
function ipp_array_map_keys($callback, $arr) {
	if( !is_callable($callback) ) return FALSE;
	
	$keys = array_keys($arr);
	$keys = array_map($callback, $keys);
	
	$out = array();
	foreach($arr as $value) {
		$key = array_shift($keys);
		$out[ $key ] = $value;
	}
	
	return $out;
}


/*
 * callback function for ipp_array_map_keys()
 */
function ipp_array_map_keys_callback($key) {
	return preg_replace(
		'`^([^/]+)(\(?/)`U',
		'$1(?:\.html)?$2',
		$key
	);
}



/*
 * Dev
 */
function ipp_debug($str='') {
return;
	file_put_contents(dirname(__FILE__).'/ipp.log', $str."\n", FILE_APPEND);
}


function ipp_reset_log() {
	file_put_contents(dirname(__FILE__).'/ipp.log', '');
}




?>