<?php
/**
 * @package Peer_Categories
 * @author Scott Reilly
 * @version 1.1.2
 */
/*
Plugin Name: Peer Categories
Version: 1.1.2
Plugin URI: http://coffee2code.com/wp-plugins/peer-categories/
Author: Scott Reilly
Author URI: http://coffee2code.com
Description: List the categories that are peer (i.e. share the same category parent) to all lowest-level assigned categories for the specified post.

Compatible with WordPress 2.5+, 2.6+, 2.7+, 2.8+, 2.9+, 3.0+, 3.1+, 3.2+.

=>> Read the accompanying readme.txt file for instructions and documentation.
=>> Also, visit the plugin's homepage for additional information and updates.
=>> Or visit: http://wordpress.org/extend/plugins/peer-categories/

TODO:
	* Prefix function wit 'c2c_' and deprecate existing versions
	* Support filter invocation approach via add_filter( 'peer_categories', 'peer_categories', 10, 2 );
	* Document previously mentioned filters

*/

/*
Copyright (c) 2008-2011 by Scott Reilly (aka coffee2code)

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

if ( ! function_exists( 'peer_categories' ) ) :
/**
 * Outputs the peer categories.
 *
 * For use in the loop
 *
 * @param string $separator (optional) String to use as the separator
 * @param int|false $post_id (optional) Post ID
 * @return void (Text is echoed)
*/
function peer_categories( $separator = '', $post_id = false ) {
	echo get_peer_categories_list( $separator, $post_id );
}
endif;


if ( ! function_exists( 'get_peer_categories_list' ) ) :
/**
 * Gets the list of peer categories.
 *
 * @param string $separator (optional) String to use as the separator
 * @param int|false $post_id (optional) Post ID
 * @return string The HTML formatted list of peer categories
 */
function get_peer_categories_list( $separator = '', $post_id = false ) {
	global $wp_rewrite;
	$categories = get_peer_categories( $post_id );
	if ( empty( $categories ) )
		return apply_filters( 'peer_categories', __( 'Uncategorized' ), $separator );

	$rel = ( is_object( $wp_rewrite ) && $wp_rewrite->using_permalinks() ) ? 'rel="category tag"' : 'rel="category"';

	$thelist = '';
	if ( '' == $separator ) {
		$thelist .= '<ul class="post-categories">';
		foreach ( $categories as $category ) {
			$thelist .= "\n\t<li>";
			$thelist .= '<a href="' . get_category_link( $category->term_id ) . '" title="' . sprintf( __( 'View all posts in %s' ), $category->name ) . '" ' .
						$rel . '>' . $category->cat_name.'</a></li>';
		}
		$thelist .= '</ul>';
	} else {
		$i = 0;
		foreach ( $categories as $category ) {
			if ( 0 < $i )
				$thelist .= $separator . ' ';
			$thelist .= '<a href="' . get_category_link( $category->term_id ) . '" title="' . sprintf( __( 'View all posts in %s' ), $category->name ) . '" ' . $rel . '>' . $category->name.'</a>';
			++$i;
		}
	}
	return apply_filters( 'peer_categories', $thelist, $separator );
}
endif;


if ( ! function_exists( 'get_peer_categories' ) ) :
/**
 * Returns the list of peer categories for the specified post. IF not supplied a
 * post ID, then the top-level categories will be returned.
 *
 * @param int|false $id (optional) Post ID
 * @return array The array of peer categories for the given category. If false, then assumes a top-level category.
 */
function get_peer_categories( $id = false ) {
	$categories = get_the_category( $id );
	if ( empty( $categories ) )
		return get_categories( array( 'hide_empty' => false, 'user_desc_for_title' => false, 'title_li' => '', 'child_of' => 0, 'depth' => 1 ) );

	$peers = array();
	$parents = array();

	// Go through all categories and get, then filter out, parents.
	foreach ( $categories as $c )
		$parents[] = $c->parent;
	foreach ( $categories as $c ) {
		if ( !in_array( $c->term_id, $parents ) )
			$peers[] = $c;
	}
	
	// For each cat at this point, get peer cats.
	$parents = array();
	foreach ( $peers as $c )
		$parents[] = ( $c->parent ? $c->parent : 0 );
	$parents = array_unique( $parents );
	$peers = array();
	foreach ( $parents as $p ) {
		$args = array( 'hide_empty' => false, 'user_desc_for_title' => false, 'title_li' => '', 'child_of' => $p, 'depth' => 0 );
		$cats = get_categories( $args );
		# If this cat has no parent, then only get root categories
		if ( $p == 0 ) {
			$new_peers = array();
			foreach ( $cats as $c ) {
				//TODO? Might also want to add extra conditional clause of !in_array($c->parent, $parents)
				if ( $c->parent == 0 )
					$new_peers[] = $c;
			}
		} else {
			$new_peers = $cats;
		}
		$peers = array_merge( $peers, $new_peers );
	}
	usort( $peers, '_usort_terms_by_name' );
	return $peers;
}
endif;

?>