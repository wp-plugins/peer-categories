=== Peer Categories ===
Contributors: Scott Reilly
Donate link: http://coffee2code.com/donate
Tags: categories, category, peer, sibling, related posts, similar posts, list, the_category, coffee2code
Requires at least: 2.5
Tested up to: 2.8
Stable tag: 1.0
Version: 1.0

Display only the categories that are peer (i.e. share the same category parent) to all lowest-level assigned categories for the specified post.

== Description ==

Display only the categories that are peer (i.e. share the same category parent) to all lowest-level assigned categories for the specified post.

This plugin provides a template tag which acts a modified version of WordPress's built-in template tag, `the_category()`.  `the_category()` lists all categories directly assigned to the specified post.  `peer_categories()` lists those categories *PLUS* any categories that are peer to those categories and *MINUS* categories that are parents to other assigned categories.  Peer categories are categories that all share the same category parent.

For example, assume your category structure is hierarchical and looks like this:

Vegetables
|-- Leafy
|   |-- Broccoli
|   |-- Bok Choy
|   |-- Celery
|-- Fruiting
|   |-- Bell Pepper
|   |-- Cucumber
|   |-- Pumpkin
|-- Podded
|   |-- Chickpea
|   |-- Lentil
|   |-- Soybean

If you directly assigned the categories "Fruiting" and "Pumpkin" to a post, `peer_categories()` would return a list that consists of: "Bell Pepper", "Cucumber", and "Pumpkin".  Notice that since "Fruiting" was a parent to a directly assigned category, it and its peers are not included in the list.  If only "Fruiting" were selected as a category, then "Leafy", "Fruiting", and "Podded" would have been listed.

By default, categories are listed as an HTML list.  The first argment to the template tag allows you to define a custom separator, e.g. to have a simple comma-separated list of peer categories: `<?php peer_categories(','); ?>`.

As with categories listed via `the_category()`, categories that are listed are presented as links to the respective category's archive page.

Example usage (based on preceeding example):

`<?php peer_categories(); ?>`
Displays something like:
    `<ul><li><a href="http://yourblog.com/category/fruiting/bell-pepper">Bell Pepper</a></li>
    <li><a href="http://yourblog.com/category/fruiting/cucumber">Cucumber</a></li>
    <li><a href="http://yourblog.com/category/fruiting/pumpkin">Pumpkin</a></li></ul>`

`<?php peer_categories(','); ?></ul>`
Displays something like:
    `<a href="http://yourblog.com/category/fruiting/bell-pepper">Bell Pepper</a>, <a href="http://yourblog.com/category/fruiting/cucumber">Cucumber</a>, <a href="http://yourblog.com/category/fruiting/pumpkin">Pumpkin</a>`


== Installation ==

1. Unzip `peer-categories-v1.0.zip` inside the `/wp-content/plugins/` directory for your site
1. Activate the plugin through the 'Plugins' admin menu in WordPress
1. (optional) Add filters for 'peer_categories' to filter peer category listing
1. Use the template tag `<?php peer_categories(); ?>` somewhere inside "the loop"

== Frequently Asked Questions ==

= Why isn't an assigned category for the post showing up in the 'peer_categories()' listing? =

If an assigned category is the parent for one or more other assigned categories for the post, then the category parent is not included in the listing.  Only peers to the lowest-level assigned categories are considered.


