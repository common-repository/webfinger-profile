=== WebFinger Profile ===
Contributors: duthied
Donate link: TBD
Tags: XML, Discovery, host-meta, Webfinger, simple web discovery, swd
Requires at least: 3.2
Tested up to: 3.2
Stable tag: 1.8

WebFinger Profile for WordPress

== Description ==
This plugin allows you to display a widget on your blog with the profile information from any website that supports the 
WebFinger protocol. (Specifically tested with Diaspora.org, Status.net, Friendika.com)

Webfinger: http://code.google.com/p/webfinger/
simple-web-discovery: http://tools.ietf.org/html/draft-jones-simple-web-discovery

== Changelog ==
= 1.8 = 
* ensured no function name collision

= 1.7 =
* modified description to mention widget name
* added settings link to plugin description in plugins listing page

= 1.6 =
* removed temp. hack for Diaspora - they now support the rel="http://webfinger.net/rel/profile-page" in the XRD (Thanks Maxwell)
* added deactivation hook to purge profile data
* fixed up some function names to ensure non-collision

= 1.5 =
* get profile url from XRD ('cept Diaspora until they add this data)

= 1.4 =
* generic method for discovering hcard elements
* improved method of getting external files
* dialed in the XRD discovery code

= 1.3 =
* tightened up the description

= 1.2 =
* added stylesheet
* added class names to badge widget

= 1.1 =
* modularized core code
* added badge widget

= 1.0 =
* Initial release

== Installation ==

1. Install the plugin from your Wordpress admin panel.

OR

1. Upload the plugin folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

== Frequently Asked Questions ==

Q. Why doesn't the link in the widget go directly to my profile on [insert social network here]?
A. Some of the networks don't have a full profile link in the hCard or XRD at the moment (or I can't find it).  This might change in the future.
