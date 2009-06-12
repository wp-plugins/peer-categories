<?php
/*
Plugin Name: Peer Categories
Version: 1.0
Plugin URI: http://coffee2code.com/wp-plugins/peer-categories
Author: Scott Reilly
Author URI: http://coffee2code.com
Description: Display only the categories that are peer (i.e. share the same category parent) to all lowest-level assigned categories for the specified post.

	This plugin provides a template tag which acts a modified version of WordPress's built-in template tag, `the_category()`.  
	`the_category()` lists all categories directly assigned to the specified post.  `peer_categories()` lists those categories
	*PLUS* any categories that are peer to those categories and *MINUS* categories that are parents to other assigned
	categories.  Peer categories are categories that all share the same category parent.

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

	If you directly assigned the categories "Fruiting" and "Pumpkin" to a post, `peer_categories()` would return a list that consists
	of: "Bell Pepper", "Cucumber", and "Pumpkin".  Notice that since "Fruiting" was a parent to a directly assigned category, its peers
	are not included in the list.  If only "Fruiting" were selected as a category, then "Leafy", "Fruiting", and "Podded" would have been
	listed.

	By default, categories are listed as an HTML list.  The first argment to the template tag allows you to define a custom separator,
	e.g. to have a simple comma-separated list of peer categories: <?php peer_categories(','); ?>.

	As with categories listed via the_category(), categories that are listed are presented as links to the respective category's
	archive page.

	Example usage (based on preceeding example):

	<?php peer_categories(); ?>
	Displays something like:
	    <ul><li><a href="http://yourblog.com/category/fruiting/bell-pepper">Bell Pepper</a></li>
	    <li><a href="http://yourblog.com/category/fruiting/cucumber">Cucumber</a></li>
	    <li><a href="http://yourblog.com/category/fruiting/pumpkin">Pumpkin</a></li></ul>

	<?php peer_categories(','); ?></ul>
	Displays something like:
    	<a href="http://yourblog.com/category/fruiting/bell-pepper">Bell Pepper</a>, <a href="http://yourblog.com/category/fruiting/cucumber">Cucumber</a>, <a href="http://yourblog.com/category/fruiting/pumpkin">Pumpkin</a>

Compatible with WordPress 2.5+, 2.6+, 2.7+, 2.8+.

=>> Read the accompanying readme.txt file for more information.  Also, visit the plugin's homepage
=>> for more information and the latest updates

Installation:

1. Download the file http://coffee2code.com/wp-plugins/peer-categories.zip and unzip it into your 
wp-content/plugins/ directory.
2. Activate the plugin through the 'Plugins' admin menu in WordPress
3. (optional) Add filters for 'peer_categories' to filter peer category listing
4. Use the template tag <?php peer_categories() ?> somewhere inside "the loop"

*/

/*
Copyright (c) 2008-2009 by Scott Reilly (aka coffee2code)

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation 
files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, 
modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the 
Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR
IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

/*
	In the loop 
*/
function peer_categories( $separator = '', $post_id = false ) {
	echo get_peer_categories_list($separator, $post_id);
}

function get_peer_categories_list( $separator = '', $post_id = false ) {
	global $wp_rewrite;
	$categories = get_peer_categories($post_id);
	if ( empty($categories) )
		return apply_filters('peer_categories', __('Uncategorized'), $separator);

	$rel = ( is_object($wp_rewrite) && $wp_rewrite->using_permalinks() ) ? 'rel="category tag"' : 'rel="category"';

	$thelist = '';
	if ( '' == $separator ) {
		$thelist .= '<ul class="post-categories">';
		foreach ( $categories as $category ) {
			$thelist .= "\n\t<li>";
			$thelist .= '<a href="' . get_category_link($category->term_id) . '" title="' . sprintf(__("View all posts in %s"), $category->name) . '" ' .
						$rel . '>' . $category->cat_name.'</a></li>';
		}
		$thelist .= '</ul>';
	} else {
		$i = 0;
		foreach ( $categories as $category ) {
			if ( 0 < $i )
				$thelist .= $separator . ' ';
			$thelist .= '<a href="' . get_category_link($category->term_id) . '" title="' . sprintf(__("View all posts in %s"), $category->name) . '" ' . $rel . '>' . $category->name.'</a>';
			++$i;
		}
	}
	return apply_filters('peer_categories', $thelist, $separator);
}

function get_peer_categories( $id = false ) {
	$categories = get_the_category($id);
	if ( empty($categories) ) {
		return get_categories(array('hide_empty' => false, 'user_desc_for_title' => false, 'title_li' => '', 'child_of' => 0, 'depth' => 1));
	}

	$peers = array();
	$parents = array();

	// Go through all categories and get, then filter out, parents.
	foreach ( $categories as $c ) { $parents[] = $c->parent; }
	foreach ( $categories as $c ) {
		if ( !in_array($c->term_id, $parents) ) { $peers[] = $c; }
	}
	
	// For each cat at this point, get peer cats.
	$parents = array();
	foreach ( $peers as $c ) {
		if ( $c->parent ) {
			$parents[] = $c->parent;
		} else {
			$parents[] = 0;
		}
	}
	$parents = array_unique($parents);
	$peers = array();
	foreach ( $parents as $p ) {
		$args = array('hide_empty' => false, 'user_desc_for_title' => false, 'title_li' => '', 'child_of' => $p, 'depth' => 0);
		$cats = get_categories($args);
		# If this cat has no parent, then only get root categories
		if ( $p == 0 ) {
			$new_peers = array();
			foreach ($cats as $c) {
				//TODO? Might also want to add extra conditional clause of !in_array($c->parent, $parents)
				if ($c->parent == 0) $new_peers[] = $c;
			}
		} else {
			$new_peers = $cats;
		}
		$peers = array_merge($peers, $new_peers);
	}
	usort($peers, '_usort_terms_by_name');
	return $peers;
}

?>