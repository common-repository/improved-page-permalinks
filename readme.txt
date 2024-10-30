=== Improved Page Permalinks ===
Contributors: Seebz
Donate link: 
Tags: plugin, page, permalink, url
Requires at least: 3.0.0
Tested up to: 3.3.0
Stable tag: 0.2.2

Improve page permalinks by adding ".html" for final pages


== Description ==

Improve Page Permalinks.
This plugin just adds *.html* to the permalink pages when needed.
An option can prevent this action for specific pages.

= Example =
* yourblog.com/page**.html**
* yourblog.com/parent**/**
* yourblog.com/parent/child**.html**


== Installation ==

1. Upload the folder "improved-page-permalinks" to "/wp-content/plugins/"
1. Activate the plugin through the "Plugins" menu in WordPress
1. Done!


== Other Notes ==

= Compatibility =
Not tested yet with all previous 3.0 Wordpress version

Tests OK with the following plugins

* [Permalink Redirect](http://wordpress.org/extend/plugins/permalink-redirect/) (v2.0.4)
* [CMS Tree Page View](http://wordpress.org/extend/plugins/cms-tree-page-view/) (v0.6)


== Changelog ==

= 0.2.2 =
* Fix for Wordpress 3.3

= 0.2.1 =
* Consider pages with pagination (using &lt;!--nextpage--&gt;) as a folder
* Feed comments work now (attachments should too)

= 0.2 =
* Better integration with custom permalink structure
* Now work only if custom permalink structure is used (don't adds *.html* on *?p=123* permalinks)

= 0.1.1 =
* Added a better description.

= 0.1 =
* First public version.

