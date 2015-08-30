<?php
/**
 * Plugin Name: Peer Categories
 * Version:     2.0.2
 * Plugin URI:  http://coffee2code.com/wp-plugins/peer-categories/
 * Author:      Scott Reilly
 * Author URI:  http://coffee2code.com/
 * License:     GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Description: List the categories that are peer (i.e. share the same category parent) to all lowest-level assigned categories for the specified post.
 *
 * Compatible with WordPress 3.6 through 4.3+.
 *
 * =>> Read the accompanying readme.txt file for instructions and documentation.
 * =>> Also, visit the plugin's homepage for additional information and updates.
 * =>> Or visit: https://wordpress.org/plugins/peer-categories/
 *
 * TODO:
 * * Prefix function wit 'c2c_' and deprecate existing versions
 * * Support filter invocation approach via add_filter( 'peer_categories', 'peer_categories', 10, 2 );
 * * Document previously mentioned filters
 *
 * @package Peer_Categories
 * @author  Scott Reilly
 * @version 2.0.2
 */

/*
	Copyright (c) 2008-2015 by Scott Reilly (aka coffee2code)

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

defined( 'ABSPATH' ) or die();

if ( ! function_exists( 'c2c_peer_categories' ) ) :

/**
 * Outputs the peer categories.
 *
 * For use in the loop
 *
 * @since 2.0
 *
 * @param  string    $separator (optional) String to use as the separator
 * @param  int|false $post_id   (optional) Post ID
 * @return void      (Text is echoed)
*/
function c2c_peer_categories( $separator = '', $post_id = false ) {
	echo c2c_get_peer_categories_list( $separator, $post_id );
}

add_action( 'c2c_peer_categories', 'c2c_peer_categories', 10, 2 );

endif;


if ( ! function_exists( 'c2c_get_peer_categories_list' ) ) :

/**
 * Gets the list of peer categories.
 *
 * @since 2.0
 *
 * @param  string $   separator (optional) String to use as the separator
 * @param  int|false  $post_id (optional) Post ID
 * @return string     The HTML formatted list of peer categories
 */
function c2c_get_peer_categories_list( $separator = '', $post_id = false ) {
	global $wp_rewrite;

	$categories = c2c_get_peer_categories( $post_id );

	if ( empty( $categories ) ) {
		return apply_filters(
			'c2c_peer_categories_list',
			apply_filters( 'peer_categories', __( 'Uncategorized' ), $separator ), // Deprecated as of v2.0
			$separator,
			$post_id
		);
	}

	$rel = ( is_object( $wp_rewrite ) && $wp_rewrite->using_permalinks() ) ? 'rel="category tag"' : 'rel="category"';

	$thelist = '';
	if ( '' == $separator ) {
		$thelist .= '<ul class="post-categories">';
		foreach ( $categories as $category ) {
			$thelist .= "\n\t<li>";
			$thelist .= '<a href="' . get_category_link( $category->term_id ) . '" title="' .
					sprintf( __( 'View all posts in %s' ), $category->name ) . '" ' .
					$rel . '>' . $category->cat_name . '</a></li>';
		}
		$thelist .= '</ul>';
	} else {
		$i = 0;
		foreach ( $categories as $category ) {
			if ( 0 < $i ) {
				$thelist .= $separator;
			}
			$thelist .= '<a href="' . get_category_link( $category->term_id ) . '" title="' .
					sprintf( __( 'View all posts in %s' ), $category->name ) . '" ' .
					$rel . '>' . $category->name.'</a>';
			++$i;
		}
	}

	return apply_filters(
		'c2c_peer_categories_list',
		apply_filters( 'peer_categories', $thelist, $separator ), // Deprecated as of v2.0
		$separator,
		$post_id
	);
}

add_filter( 'c2c_get_peer_categories_list', 'c2c_get_peer_categories_list', 10, 2 );

endif;


if ( ! function_exists( 'c2c_get_peer_categories' ) ) :

/**
 * Returns the list of peer categories for the specified post.
 *
 * If not supplied a post ID, then the top-level categories will be returned.
 *
 * @since 2.0
 *
 * @param  int|false $post_id (optional) Post ID
 * @param  bool      $omit_ancestors (optional) Prevent any ancestors from also being listed, not just immediate parents?
 * @return array     The array of peer categories for the given category. If false, then assumes a top-level category.
 */
function c2c_get_peer_categories( $post_id = false, $omit_ancestors = true ) {
	$categories = get_the_category( $post_id );

	if ( empty( $categories ) ) {
		return get_categories(
			array( 'hide_empty' => false, 'user_desc_for_title' => false, 'title_li' => '', 'parent' => 0, 'exclude' => get_option( 'default_category' ) )
		);
	}

	$peers = $parents = array();

	$omit_ancestors = apply_filters( 'c2c_get_peer_categories_omit_ancestors', $omit_ancestors );

	// Go through all categories and get, then filter out, parents.
	foreach ( $categories as $c ) {
		if ( $c->parent && ! in_array( $c->parent, $parents ) ) {
			if ( $omit_ancestors ) {
				$parents = array_merge( $parents, get_ancestors( $c->term_id, 'category' ) );
			} else {
				$parents[] = $c->parent;
			}
		}
	}
	$parents = array_unique( $parents );

	foreach ( $categories as $c ) {
		if ( ! in_array( $c->term_id, $parents ) ) {
			$peers[] = $c;
		}
	}

	// For each cat at this point, get peer cats.
	$parents = array();
	foreach ( $peers as $c ) {
		$parents[] = ( $c->parent ? $c->parent : 0 );
	}
	$parents = array_unique( $parents );

	$peers = array();
	foreach ( $parents as $p ) {
		$args = array( 'hide_empty' => false, 'user_desc_for_title' => false, 'title_li' => '', 'parent' => $p );
		$cats = get_categories( $args );

		# If this cat has no parent, then only get root categories
		if ( $p == 0 ) {
			$new_peers = array();
			foreach ( $cats as $c ) {
				if ( $c->parent && ! in_array( $c->parent, $parents ) ) {
					$new_peers[] = $c;
				}
			}
		} else {
			$new_peers = $cats;
		}
		$peers = array_merge( $peers, $new_peers );
	}
	usort( $peers, '_usort_terms_by_name' );

	return $peers;
}

add_filter( 'c2c_get_peer_categories', 'c2c_get_peer_categories', 10, 2 );

endif;



/*************
 * DEPRECATED FUNCTIONS
 *************/



if ( ! function_exists( 'peer_categories' ) ) :
/**
 * @since 1.0
 * @deprecated 2.0 Use c2c_peer_categories() instead
 */
function peer_categories( $separator = '', $post_id = false ) {
	_deprecated_function( 'peer_categories', '2.0', 'c2c_peer_categories' );
	c2c_peer_categories( $separator, $post_id );
}
endif;

if ( ! function_exists( 'get_peer_categories_list' ) ) :
/**
 * @since 1.0
 * @deprecated 2.0 Use c2c_get_peer_categories_list() instead
 */
function get_peer_categories_list( $separator = '', $post_id = false ) {
	_deprecated_function( 'get_peer_categories_list', '2.0', 'c2c_get_peer_categories_list' );
	return c2c_get_peer_categories_list( $separator, $post_id );
}
endif;

if ( ! function_exists( 'get_peer_categories' ) ) :
/**
 * @since 1.0
 * @deprecated 2.0 Use c2c_get_peer_categories() instead
 */
function get_peer_categories( $id = false ) {
	_deprecated_function( 'get_peer_categories', '2.0', 'c2c_get_peer_categories' );
	return c2c_get_peer_categories( $id );
}
endif;
