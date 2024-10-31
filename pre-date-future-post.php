<?php
/*
Plugin Name: Pre Date Future Post
Plugin URI: http://wordpress.org/extend/plugins/pre-date-future-post/
Description: Allows you to set posts to appear automatically in the future, but have a post date in the past.
Author: Brodie Thiesfield
Version: 1.3
*/

/** 
 * Debug logging
 */
function predatefuturepost_log($line) {
    global $current_blog;

    $fp = fopen(__FILE__ . ".log", "ab");
    if ($fp) {
        $gmtoffset = (int) (3600 * ((double) get_option('gmt_offset')));
        
        $currts = time() + $gmtoffset;
        fwrite($fp, gmdate('Y-m-d H:i:s ', $currts).$current_blog->blog_id." $line\n");
        fclose($fp);
    }
}

/** 
 * Function that does the actual posting - called by wp_cron
 */
function predatefuturepost_update_post() {
	global $wpdb;
    $sql =  "SELECT post_id " .
            "FROM wp_posts JOIN wp_postmeta ON ID = post_id " .
            "WHERE post_type = 'post' " .
            "AND post_status = 'pending' " .
            "AND meta_key = 'make-public-date' " .
            "AND meta_value < " . time();
	$result = $wpdb->get_results($sql);

    foreach ($result as $p) {
        predatefuturepost_log("publishing post_id $p->post_id");
        wp_update_post(array('ID' => $p->post_id, 'post_status' => 'publish'));
        delete_post_meta($p->post_id, 'make-public-date');
	}
}
add_action ('predatefuturepost_update_post_'.$current_blog->blog_id, 'predatefuturepost_update_post');

/** 
 * Called at plugin activation
 */
function predatefuturepost_activate () {
    predatefuturepost_log("activating pre-date-future-post");

	global $current_blog;
    
    $gmtoffset = (int) (3600 * ((double) get_option('gmt_offset')));
    $nexthour = time() + $gmtoffset;                // local timezone
    $nexthour = ((int) ($nexthour / 3600)) * 3600;  // round down to the nearest hour
    $nexthour += 3630;                              // 30 seconds past the next hour 
    $nexthour -= $gmtoffset;                        // convert back to GMT
    
	wp_schedule_event($nexthour, 'hourly', 'predatefuturepost_update_post_'.$current_blog->blog_id);

    $nextevent = gmdate('Y-m-d H:i:s', $nexthour + $gmtoffset);
    predatefuturepost_log("Events scheduled to occur hourly from $nextevent");
}
register_activation_hook (__FILE__, 'predatefuturepost_activate');

/**
 * Called at plugin deactivation
 */
function predatefuturepost_deactivate () {
    predatefuturepost_log("deactivating pre-date-future-post");

	global $current_blog;
	wp_clear_scheduled_hook('predatefuturepost_update_post_'.$current_blog->blog_id);
}
register_deactivation_hook (__FILE__, 'predatefuturepost_deactivate');

/**
 * adds an 'Make Public' column to the post display table.
 */
function predatefuturepost_add_column ($columns) {
  	$columns['predatefuturepost'] = 'Make Public';
  	return $columns;
}
add_filter ('manage_posts_columns', 'predatefuturepost_add_column');
add_filter ('manage_pages_columns', 'predatefuturepost_add_column');

/**
 * fills the 'Make Public' column of the post display table.
 */
function predatefuturepost_show_value ($column_name) {
	if ($column_name === 'predatefuturepost') {
        $gmtoffset = (int) (3600 * ((double) get_option('gmt_offset')));

    	global $wpdb, $post;
        $ts = $wpdb->get_var("SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = 'make-public-date' AND post_id = $post->ID");
        echo ($ts ? gmdate('d M y @ H:00', $ts + $gmtoffset) : '');
  	}
}
add_action ('manage_posts_custom_column', 'predatefuturepost_show_value');
add_action ('manage_pages_custom_column', 'predatefuturepost_show_value');

/**
 * Add's hooks to get the meta box added to post
 */
function predatefuturepost_meta_post() {
    global $wpdb, $post;
    $status = $wpdb->get_var("SELECT post_status FROM $wpdb->posts WHERE ID = $post->ID");
    if ($status === 'pending') {
        add_meta_box('predatefuturepostdiv', __('Make Post Public Date'), 'predatefuturepost_meta_box', 'post', 'advanced', 'high');
    }
}
add_action ('dbx_post_advanced','predatefuturepost_meta_post');

/**
 * Add's hooks to get the meta box added to page
 */
function predatefuturepost_meta_page() {
    global $wpdb, $post;
    $status = $wpdb->get_var("SELECT post_status FROM $wpdb->posts WHERE ID = $post->ID");
    if ($status === 'pending') {
    	add_meta_box('predatefuturepostdiv', __('Make Post Public Date'), 'predatefuturepost_meta_box', 'page', 'advanced', 'high');
    }
}
add_action ('edit_page_form','predatefuturepost_meta_page');

/**
 * Actually adds the meta box
 */
function predatefuturepost_meta_box($post) { 
    $gmtoffset = (int) (3600 * ((double) get_option('gmt_offset')));
    $currts = time();

    $checked = "checked='checked'";
	$ts = get_post_meta($post->ID, 'make-public-date', true);
	if (empty($ts)) {
        $ts = $currts;
        $checked = "";
	} 
    
    $currts += $gmtoffset;
    $ts += $gmtoffset;

    $defaulthour = gmdate('G', $ts);
    $defaultday = gmdate('d', $ts);
    $defaultmonth = gmdate('F', $ts);
    $defaultyear = gmdate('Y', $ts);

	$html .= "<p><input type='checkbox' name='predatefuturepost_enable' id='predatefuturepost_enable' value='on' $checked />";
	$html .= '<label for="predatefuturepost_enable">Automatically publish this post on the following date</label></p>';
	$html .= '<table><tr>';
	   $html .= '<th style="text-align: left;">Time</th>';
	   $html .= '<th style="text-align: left;">Day</th>';
	   $html .= '<th style="text-align: left;">Month</th>';
	   $html .= '<th style="text-align: left;">Year</th>';
	$html .= '</tr><tr>';
	$html .= '<td>';
	$html .= '<select name="predatefuturepost_hour" id="predatefuturepost_hour">';
    	for($i = 0; $i <= 23; $i++) {
            $selected = ($defaulthour == $i) ? ' selected="selected"' : '';
    		$html .= sprintf('<option value="%1$d"%2$s>%1$02d:00</option>', $i, $selected);
    	}
	$html .= '</select>';
	$html .= ' on ';
	$html .= '</td><td>'; 
	$html .= "<input type='text' id='predatefuturepost_day' name='predatefuturepost_day' value='$defaultday' size='2' />"; 
	$html .= '</td><td>'; 
	$html .= '<select name="predatefuturepost_month" id="predatefuturepost_month">';
        $curryear = gmdate('Y', $currts);
    	for($i = 1; $i <= 12; $i++) {
            $thismonthts = gmmktime(0, 0, 0, $i, 1, $curryear);
            $selected = '';
    		if ($defaultmonth == gmdate('F', $thismonthts)) {
    			$selected = ' selected="selected"';
            }
    		$html .= '<option value="'.gmdate('m', $thismonthts).'"'.$selected.'>'.gmdate('F', $thismonthts).'</option>';
    	}
	$html .= '</select>';
	$html .= '</td><td>';
	$html .= '<select name="predatefuturepost_year" id="predatefuturepost_year">';
    	if ($defaultyear < $curryear)
    		$curryear = $defaultyear;
    	for($i = $curryear; $i < $curryear + 8; $i++) {
            $selected = '';
    		if ($i == $defaultyear) {
    			$selected = ' selected="selected"';
            }
    		$html .= "<option$selected>$i</option>";
    	}
	$html .= '</select>';
	$html .= '</td></tr></table>';
    
    $offset = get_option('gmt_offset');
    $offset = ($offset > 0.0) ? ('UTC +'.$offset) : (($offset == 0.0) ? 'UTC' : 'UTC '.$offset);
    $html .= "<p><b>Note:</b> This time uses the timezone setting ($offset) from this blog's settings.</p>";

	echo $html;
}

/**
 * Called when post is saved - stores make-public-date meta value
 */
function predatefuturepost_update_post_meta($id) {
    if (array_key_exists('predatefuturepost_enable', $_POST) && $_POST['predatefuturepost_enable'] == 'on') {
        $gmtoffset = (int) (3600 * ((double) get_option('gmt_offset')));

        $hour  = $_POST['predatefuturepost_hour'];
        $day   = $_POST['predatefuturepost_day'];
        $month = $_POST['predatefuturepost_month'];
        $year  = $_POST['predatefuturepost_year'];

        $ts = gmmktime($hour, 0, 0, $month, $day, $year);
        $ts -= $gmtoffset;
        
        delete_post_meta($id, 'make-public-date');
        update_post_meta($id, 'make-public-date', $ts, true);
    }
    else {
        delete_post_meta($id, 'make-public-date');
    }
}
add_action('save_post','predatefuturepost_update_post_meta');

?>
