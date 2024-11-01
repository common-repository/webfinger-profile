<?php
/*
Plugin Name: WebFinger Profile Widget
Plugin URI: http://blog.duthied.com/2011/08/30/webfinger-profile-plugin/
Description: This plugin allows you to display display a widget on your blog with the profile information from any website that supports the WebFinger protocol.
Version: 1.8
Author: Devlon Duthie
Author URI: http://blog.duthied.com
*/

/*  Copyright 2011 Devlon Duthie (email: duthied@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


define("webfinger_profile_path", WP_PLUGIN_URL . "/" . str_replace(basename( __FILE__), "", plugin_basename(__FILE__)));
define("webfinger_profile_version", "1.8");
$plugin_dir = basename(dirname(__FILE__));
$plugin = plugin_basename(__FILE__); 

define("webfinger_profile_acct_name", "webfinger_profile_admin_options");
add_shortcode("webfinger_profile_fullname", "webfinger_profile_fullname");
add_shortcode("webfinger_profile_link", "webfinger_profile_link");
add_shortcode("webfinger_profile_avatar", "webfinger_profile_avatar");
add_shortcode("webfinger_profile_username", "webfinger_profile_username");

// get host meta from host, parsed from webfinger_profile_acct_name: foo@bar.com
// eg: https://diasp.org/.well-known/host-meta
function webfinger_profile_getXRDUrl($hostname, $username) {
	if (isset($username) && isset($hostname)) {
		
		$acct = urlencode("acct:" . $username . "@" . $hostname);
		
		// request hostmeta file
		$host_meta_url = "http://" . $hostname . "/.well-known/host-meta";
		
		$host_meta_dom = new DOMDocument();
		
		$host_meta_file = file_get_contents($host_meta_url);
		if (!$host_meta_file) {
			die("Unable to load host-meta file from: " . $host_meta_url);
		}
		$host_meta_dom->loadXML($host_meta_file);
		
		update_option(webfinger_profile_hostname, $hostname);
		
		$xrd_meta_items = $host_meta_dom->getElementsByTagName("XRD");
		foreach ($xrd_meta_items as $xrd_meta_item) {
			$host_meta_links = $xrd_meta_item->getElementsByTagName("Link");
			foreach ($host_meta_links as $host_meta_link) {
				if ($host_meta_link->hasAttribute("rel")) {
					if ($host_meta_link->getAttribute("rel") == "lrdd") {
						$xrd_url_template = $host_meta_link->getAttribute("template");
					}
				}
			}
		}
		$xrd_url = str_replace("{uri}", $acct, $xrd_url_template);
	}
	
	return $xrd_url;
}

function webfinger_profile_getHCardUrl($xrd_url) {
	if (isset($xrd_url)) {
		$xrd_dom = new DOMDocument();
		
		$xrd_file = file_get_contents($xrd_url);
		if (!$xrd_file) {
			die("Unable to load XRD file from: " . $xrd_url);
		}
		$xrd_dom->loadXML($xrd_file);
		
		$xrd_children = $xrd_dom->getElementsByTagName("XRD");
		foreach ($xrd_children as $xrd_child) {
			// subject
			
			$xrd_node_list = $xrd_child->getElementsByTagName("Subject");
			foreach ($xrd_node_list as $xrd_node_list_item) {
				$xrd_subject = $xrd_node_list_item;
			}
			
			// ensure the subject child of the XRD element matches the requested resource (eg: acct:foo@bar.com)
			// TODO...
			$xrd_profile_url = "";
			$xrd_link_list = $xrd_child->getElementsByTagName("Link");
			foreach ($xrd_link_list as $xrd_link) {
				if ($xrd_link->hasAttribute("rel")) {
					if ($xrd_link->getAttribute("rel") == "http://microformats.org/profile/hcard") {
						$hcard_url = $xrd_link->getAttribute("href");
					} elseif ($xrd_link->getAttribute("rel") == "http://webfinger.net/rel/profile-page") {
						$xrd_profile_url = $xrd_link->getAttribute("href");
					}
				}
			}
		}
		
		// profile url
		update_option(webfinger_profile_profileurl, $xrd_profile_url);
	}
	
	return $hcard_url;
}

function webfinger_profile_setProfileData($hcard_url, $username, $hostname) {
	if (isset($hcard_url)) {
		require_once dirname(__FILE__) . "/webfinger_profile_simple_html_dom.php";
		
		// TODO: error handling
		$hcard_file = file_get_contents($hcard_url);
		if (!$hcard_file) {
			die("Unable to load XRD file from: " . $hcard_url);
		}
		$hcard_dom = webfinger_profile_str_get_html($hcard_file);
		
		$hcard_avatar = "";
		$hcard_full_name = "";
		
		// hunt-and-peck
		
		// avatar
		$hcard_avatar_tmp = $hcard_dom->find("img[class=avatar]");
		if (count($hcard_avatar_tmp) > 0) {
			$hcard_avatar = $hcard_avatar_tmp[0]->src;
		}
		
		// full name
		$hcard_full_name_tmp = $hcard_dom->find("[class=fn]");
		if (count($hcard_full_name_tmp) > 0) {
			$hcard_full_name = $hcard_full_name_tmp[0]->innertext;
		}
		
		// set the options (persist data)
		update_option(webfinger_profile_fullname, $hcard_full_name);
		update_option(webfinger_profile_username, $username);
		update_option(webfinger_profile_avatar, $hcard_avatar);
		
	}
}

function webfinger_profile_displayAdminContent() {
	
	$options = webfinger_profile_get_admin_options();

	echo <<<EOF
	<div class='wrap'>
		<h2>WebFinger Profile</h2>
		<p>This plugin provides a Widget called <strong>'Join Me - Profile Widget'</strong> that you can add to the sidebar via the Widgets section.</p>
		<p>You can also use some of the ShortCodes below to place portions of your profile wherever ShortCodes are supported.</p>
		<p>If you opt to use the Widget, you can edit the style.css file in the plugin to customize the display of the badge to suit your theme.</p>
		<p></p>
	</div>
	
	<div class="wrap">
		<h2>Configuration</h2>
		<form method="post" action="{$_SERVER["REQUEST_URI"]}">
			Enter the email address of the WebFinger-Compatible site that you want to display your profile from:<br /><br />
			<input type="text" name="webfinger_profile_acct_name" value="{$options["webfinger_profile_acct_name"]}"/> &nbsp;
			<input type="submit" value="Save" />
		</form>
		<p></p>
	</div>
EOF;
	
	// display shortcodes and thier values
	echo "<div class='wrap'><h2>Current Values and ShortCodes:</h2>(to update enter your profile email address above and click submit)";
	$full_name = get_option(webfinger_profile_fullname);
	if ($full_name != "") {
		echo "<p>Full Name (ShortCode: [webfinger_profile_fullname]): {$full_name}</p>";
	}
	$profile_username = get_option(webfinger_profile_username);
	if ($profile_username != "") {
		echo "<p>Avatar (ShortCode: [webfinger_profile_username]): {$profile_username}</p>";
	}
	$profile_url = get_option(webfinger_profile_profileurl);
	if ($profile_url != "") {
		echo "<p>Profile Link (ShortCode: [webfinger_profile_link]): <a href='{$profile_url}'>link</a></p>";
	}
	$avatar = get_option(webfinger_profile_avatar);
	if ($avatar != "") {
		echo "<p>Avatar (ShortCode: [webfinger_profile_avatar]):<br /> <img height='96' width='96' src='{$avatar}'></p>";
	}

	echo "</div>";
}

function webfinger_profile_display_admin_page() {
	if (isset($_REQUEST["webfinger_profile_acct_name"])) {
		$options = array();
		$options["webfinger_profile_acct_name"] = $_REQUEST["webfinger_profile_acct_name"];
		update_option(webfinger_profile_acct_name, $options);
		
		$tmp_account_array = explode("@", $options["webfinger_profile_acct_name"]);
		if (isset($tmp_account_array[1])) {
			$username = $tmp_account_array[0];
			$hostname = $tmp_account_array[1];
		} else {
			unset($tmp_account_array);
		}
		
		// get XRD url from host-meta
		$xrd_url = webfinger_profile_getXRDUrl($hostname, $username);
		// get hcard url from XRD
		$hcard_url = webfinger_profile_getHCardUrl($xrd_url);
		// parse hcard and store in options
		webfinger_profile_setProfileData($hcard_url, $username, $hostname);
	}
	
	webfinger_profile_displayAdminContent();
}

function webfinger_profile_admin() {
	add_options_page("WebFinger Profile", "WebFinger Profile", "manage_options", "wf-profile", "webfinger_profile_display_admin_page");
}

function webfinger_profile_fullname() {
	return get_option(webfinger_profile_fullname);
}

function webfinger_profile_link() {
	return get_option(webfinger_profile_profileurl);
}

function webfinger_profile_avatar() {
	return get_option(webfinger_profile_avatar);
}

function webfinger_profile_username() {
	return get_option(webfinger_profile_username);
}

function webfinger_profile_settings_link($links) { 
	$settings_link = '<a href="options-general.php?page=wf-profile">Settings</a>'; 
  	array_unshift($links, $settings_link); 
  	return $links; 
}

function widget_webfinger_profile_badge($args) {
    extract($args);

	$profile_link_url = webfinger_profile_link();
	$profile_link_text = "Join me on " . webfinger_profile_hostname();
	$profile_avatar = webfinger_profile_avatar();
	
	$content = "<div class='wf_badge_widget1_wrapper'>";
	$content .= "<a class='wf_badge_widget1_anchor' href='{$profile_link_url}' target='_blank'>";
	$content .= "<img height='96' width='96' class='wf_badge_widget1_avatar' src='{$profile_avatar}'></br /><span class='wf_badge_widget1_anchor_text'>{$profile_link_text}</span></a>";
	$content .= "</div>";
	
	echo $before_widget;
	echo $before_title . '' . $after_title;
    echo $content;
    echo $after_widget;
}

function add_webfinger_profile_widget_stylesheet() {
	$cssUrl = plugins_url( 'style.css' , __FILE__ );
	wp_register_style('webFingerProfileStyleSheets', $cssUrl);
	wp_enqueue_style('webFingerProfileStyleSheets', $cssUrl);
}

function webfinger_profile_deactivate() {
	delete_option(webfinger_profile_hostname);
	delete_option(webfinger_profile_acct_name);
	delete_option(webfinger_profile_profileurl);
	delete_option(webfinger_profile_fullname);
	delete_option(webfinger_profile_username);
	delete_option(webfinger_profile_avatar);
}

function webfinger_profile_get_admin_options() {
	return get_option(webfinger_profile_acct_name);
}

function webfinger_profile_hostname() {
	return get_option(webfinger_profile_hostname);
}

add_filter("plugin_action_links_$plugin", "webfinger_profile_settings_link");
register_deactivation_hook( __FILE__, "webfinger_profile_deactivate");
register_sidebar_widget("Join Me - Profile Widget", "widget_webfinger_profile_badge");
add_action("admin_menu", "webfinger_profile_admin");
add_action("wp_print_styles", "add_webfinger_profile_widget_stylesheet");
