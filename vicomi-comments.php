<?php
/*
Plugin Name: Vicomi
Plugin URI: http://vicomi.com/
Description: Vicomi comments is a free comment platform with a new cool, stylish graphic interface that replaces your default WordPress comment system. 
Author: Vicomi <support@vicomi.com>
Version: 1.0
Author URI: http://vicomi.com/
*/

require_once(dirname(__FILE__) . '/lib/vc-api.php');
define('VICOMI_COMMENTS_V', '1.0');

// set unique id
if(!get_option('vicomi_comments_uuid')) {
    update_option('vicomi_comments_uuid', vccGetGUID());
}

function vicomi_comments_plugin_basename($file) {
    $file = dirname($file);

    // From WP2.5 wp-includes/plugin.php:plugin_basename()
    $file = str_replace('\\','/',$file); // sanitize for Win32 installs
    $file = preg_replace('|/+|','/', $file); // remove any duplicate slash
    $file = preg_replace('|^.*/' . PLUGINDIR . '/|','',$file); // get relative path from plugins dir

    if ( strstr($file, '/') === false ) {
        return $file;
    }

    $pieces = explode('/', $file);
    return !empty($pieces[count($pieces)-1]) ? $pieces[count($pieces)-1] : $pieces[count($pieces)-2];
}

if ( !defined('WP_CONTENT_URL') ) {
    define('WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
}
if ( !defined('PLUGINDIR') ) {
    define('PLUGINDIR', 'wp-content/plugins'); // back compat.
}

define('VICOMI_COMMENTS_PLUGIN_URL', WP_CONTENT_URL . '/plugins/' . vicomi_comments_plugin_basename(__FILE__));

// api ref
$vicomi_comments_api = new VicomiAPI();


function vicomi_comments_is_installed() {
    return get_option('vicomi_comments_api_key');
}

// register plugin events
function vicomi_comments_activate() {
    $vicomi_comments_api = new VicomiAPI();
    $vicomi_comments_api->plugin_activate(get_option('vicomi_comments_api_key'), 'comments', get_option('vicomi_comments_uuid'));
}

function vicomi_comments_deactivate() {
    $vicomi_comments_api = new VicomiAPI();
    $vicomi_comments_api->plugin_deactivate(get_option('vicomi_comments_api_key'), 'comments', get_option('vicomi_comments_uuid'));
}

function vicomi_comments_uninstall() {
    $vicomi_comments_api = new VicomiAPI();
    $vicomi_comments_api->plugin_uninstall(get_option('vicomi_comments_api_key'), 'comments', get_option('vicomi_comments_uuid'));
}

register_activation_hook( __FILE__, 'vicomi_comments_activate' );
register_deactivation_hook( __FILE__, 'vicomi_comments_deactivate' );
register_uninstall_hook( __FILE__, 'vicomi_comments_uninstall' );

function vicomi_comments_can_replace() {
    global $id, $post;

    if (get_option('vicomi_comments_active') === '0'){ return false; }

    $replace = get_option('vicomi_comments_replace');

    if ( is_feed() )                       { return false; }
    if ( 'draft' == $post->post_status )   { return false; }
	if ( !get_option('vicomi_comments_api_key') ) { return false; }
    else if ( 'all' == $replace )          { return true; }
}

function vicomi_comments_manage_dialog($message, $error = false) {
    global $wp_version;

    echo '<div '
        . 'class="error fade'
        . ( (version_compare($wp_version, '2.5', '<') && $error) ? '-ff0000' : '' )
        . '"><p><strong>'
        . $message
        . '</strong></p></div>';
}

// Disable WP Comments
$EMBED = false;
function vicomi_comments_template($value) {
    global $EMBED;
    global $post;
    global $comments;

    if ( !( is_singular() && ( have_comments() || 'open' == $post->comment_status ) ) ) {
        return;
    }

    if ( !vicomi_comments_is_installed() || !vicomi_comments_can_replace() ) {
        return $value;
    }

    $EMBED = true;
    return dirname(__FILE__) . '/comments.php';
}

function vicomi_comments_plugin_action_links($links, $file) {
    $plugin_file = basename(__FILE__);
    if (basename($file) == $plugin_file) {
        if (!vicomi_comments_is_installed()) {
            $settings_link = '<a href="edit-comments.php?page=vicomi-comments">Configure</a>';
        } else {
            $settings_link = '<a href="edit-comments.php?page=vicomi-comments#adv">Settings</a>';    
        }
        array_unshift($links, $settings_link);
    }
    return $links;
}
add_filter('plugin_action_links', 'vicomi_comments_plugin_action_links', 10, 2);

function vicomi_comments_open($open, $post_id=null) {
    global $EMBED;
    if ($EMBED) return false;
    return $open;
}
add_filter('comments_open', 'vicomi_comments_open');

// Add Vicomi to Comments menu
function vicomi_comments_add_pages() {
     add_submenu_page(
         'edit-comments.php',
         'Vicomi',
         'Vicomi',
         'moderate_comments',
         'vicomi-comments',
         'vicomi_comments_moderate'
     );
}
add_action('admin_menu', 'vicomi_comments_add_pages', 10);

function vicomi_comments_moderate() {
	include_once(dirname(__FILE__) . '/moderate.php');
}

// Fix sub menu
function vicomi_comments_admin_head() {
?>
<script type="text/javascript">
jQuery(function($) {
    // fix menu
    var mc = $('#menu-comments');
    mc.find('a.wp-has-submenu').attr('href', 'edit-comments.php?page=vicomi-comments').end().find('.wp-submenu  li:has(a[href="edit-comments.php?page=vicomi-comments"])').prependTo(mc.find('.wp-submenu ul'));
    // fix admin bar
    $('#wp-admin-bar-comments').find('a.ab-item').attr('href', 'edit-comments.php?page=vicomi-comments');
});
</script>
<?php
	 if (isset($_GET['page']) && $_GET['page'] == 'vicomi-comments') {
?>
<style>

.vicomi-comments-menu {
	height: 20px;
	list-style: none;
	margin: 0;
	padding: 0;
	border: 0;
}

.vicomi-comments-menu span {
	float: left;
	color: #333;
	padding: 0.25em;
	height: 16px;
	cursor: pointer;
	margin-right: 8px;
	text-align: right;
}

.vicomi-comments-menu span.selected {
	font-weight: bold;
}

.vicomi-comments-header {
	height:20px;
	border-bottom: 1px solid #ccc;
	padding: 10px 0;
}

.vicomi-comments-content {
}

.vicomi-comments-btn {
	display: inline-block;
	padding: 6px 12px;
	margin-bottom: 0;
	font-size: 14px;
	font-weight: normal;
	line-height: 1.428571429;
	text-align: center;
	white-space: nowrap;
	vertical-align: middle;
	cursor: pointer;
	border: 1px solid transparent;
	border-radius: 4px;
	-webkit-user-select: none;
	-moz-user-select: none;
	-ms-user-select: none;
	-o-user-select: none;
	user-select: none;
	
	color: #333333;
	background-color: #ffffff;
	border-color: #cccccc;
}

.vicomi-comments-btn:hover{
	color: #333333;
	background-color: #ebebeb;
	border-color: #adadad;
}

.form-section{
	padding-top:10px;
}

.form-section input{
	width: 200px;
	height: 30px;
}

</style>

<script type="text/javascript">
jQuery(function($) {
    $('.vicomi-comments-menu span').click(function() {
        $('.vicomi-comments-menu span.selected').removeClass('selected');
        $('.vicomi-comments-page, .vicomi-comments-settings').hide();
        $('.' + $(this).attr('rel')).show();
		$(this).addClass('selected');
    });
});
</script>
<?php

    }
}
add_action('admin_head', 'vicomi_comments_admin_head');

add_filter('comments_template', 'vicomi_comments_template');

function vicomi_comments_pre_comment_on_post($comment_post_ID) {
    if (vicomi_comments_can_replace()) {
        wp_die('Oops! Vicomi disabled the built-in commenting system.' );
    }
    return $comment_post_ID;
}
add_action('pre_comment_on_post', 'vicomi_comments_pre_comment_on_post');


/**
 * JSON ENCODE for PHP < 5.2.0
 * Checks if json_encode is not available and defines json_encode
 * to use php_json_encode in its stead
 * Works on iteratable objects as well - stdClass is iteratable, so all WP objects are gonna be iteratable
 */
if(!function_exists('cf_json_encode')) {
    function cf_json_encode($data) {
// json_encode is sending an application/x-javascript header on Joyent servers
// for some unknown reason.
//         if(function_exists('json_encode')) { return json_encode($data); }
//         else { return cfjson_encode($data); }
        return cfjson_encode($data);
    }

    function cfjson_encode_string($str) {
        if(is_bool($str)) {
            return $str ? 'true' : 'false';
        }

        return str_replace(
            array(
                '"'
                , '/'
                , "\n"
                , "\r"
            )
            , array(
                '\"'
                , '\/'
                , '\n'
                , '\r'
            )
            , $str
        );
    }

    function cfjson_encode($arr) {
        $json_str = '';
        if (is_array($arr)) {
            $pure_array = true;
            $array_length = count($arr);
            for ( $i = 0; $i < $array_length ; $i++) {
                if (!isset($arr[$i])) {
                    $pure_array = false;
                    break;
                }
            }
            if ($pure_array) {
                $json_str = '[';
                $temp = array();
                for ($i=0; $i < $array_length; $i++) {
                    $temp[] = sprintf("%s", cfjson_encode($arr[$i]));
                }
                $json_str .= implode(',', $temp);
                $json_str .="]";
            }
            else {
                $json_str = '{';
                $temp = array();
                foreach ($arr as $key => $value) {
                    $temp[] = sprintf("\"%s\":%s", $key, cfjson_encode($value));
                }
                $json_str .= implode(',', $temp);
                $json_str .= '}';
            }
        }
        else if (is_object($arr)) {
            $json_str = '{';
            $temp = array();
            foreach ($arr as $k => $v) {
                $temp[] = '"'.$k.'":'.cfjson_encode($v);
            }
            $json_str .= implode(',', $temp);
            $json_str .= '}';
        }
        else if (is_string($arr)) {
            $json_str = '"'. cfjson_encode_string($arr) . '"';
        }
        else if (is_numeric($arr)) {
            $json_str = $arr;
        }
        else if (is_bool($arr)) {
            $json_str = $arr ? 'true' : 'false';
        }
        else {
            $json_str = '"'. cfjson_encode_string($arr) . '"';
        }
        return $json_str;
    }
}

// generate unique id
function vccGetGUID(){
    if (function_exists('com_create_guid')){
        return com_create_guid();
    }else{
        mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid = chr(123)// "{"
            .substr($charid, 0, 8).$hyphen
            .substr($charid, 8, 4).$hyphen
            .substr($charid,12, 4).$hyphen
            .substr($charid,16, 4).$hyphen
            .substr($charid,20,12)
            .chr(125);// "}"
        return $uuid;
    }
}

?>
