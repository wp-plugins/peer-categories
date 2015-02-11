<?php

class Peer_Categories_Test extends WP_UnitTestCase {

	private $cats;

	function setUp() {
		parent::setUp();

		$this->create_categories();
	}

	function tearDown() {
		parent::tearDown();

		remove_filter( 'c2c_get_peer_categories_omit_ancestors', '__return_false' );
	}



	/**
	 *
	 * HELPER FUNCTIONS
	 *
	 */




	private function create_categories() {
		$cats = array();

		$cats['cat'] = $cat = $this->factory->category->create( array( 'name' => 'vegetables' ) );

		$cat_1   = $this->factory->category->create( array( 'parent' => $cat,   'name' => 'leafy' ) );
		$cats['cat_1']   = $cat_1;
		$cats['cat_1_1'] = $this->factory->category->create( array( 'parent' => $cat_1, 'name' => 'broccoli' ) );
		$cats['cat_1_2'] = $this->factory->category->create( array( 'parent' => $cat_1, 'name' => 'bok choy' ) );
		$cats['cat_1_3'] = $this->factory->category->create( array( 'parent' => $cat_1, 'name' => 'celery' ) );

		$cat_2   = $this->factory->category->create( array( 'parent' => $cat,   'name' => 'fruiting' ) );
		$cats['cat_2']   = $cat_2;
		$cats['cat_2_1'] = $this->factory->category->create( array( 'parent' => $cat_2, 'name' => 'bell pepper' ) );
		$cats['cat_2_2'] = $this->factory->category->create( array( 'parent' => $cat_2, 'name' => 'cucumber' ) );
		$cats['cat_2_3'] = $this->factory->category->create( array( 'parent' => $cat_2, 'name' => 'pumpkin' ) );

		$cat_3   = $this->factory->category->create( array( 'parent' => $cat,   'name' => 'podded' ) );
		$cats['cat_3']   = $cat_3;
		$cats['cat_3_1'] = $this->factory->category->create( array( 'parent' => $cat_3, 'name' => 'chickpea' ) );
		$cats['cat_3_2'] = $this->factory->category->create( array( 'parent' => $cat_3, 'name' => 'lentil' ) );
		$cats['cat_3_3'] = $this->factory->category->create( array( 'parent' => $cat_3, 'name' => 'soybean' ) );

		return $this->cats = $cats;
	}

	private function expected( $cats, $separator = '' ) {
		$links = array();

		foreach ( $cats as $cat ) {
			$c = get_category( $cat );
			$links[] = sprintf(
				'<a href="%s" title="View all posts in %s" rel="category">%s</a>',
				get_category_link( $cat ),
				$c->name,
				$c->name
			);
		}

		if ( ! empty( $separator ) ) {
			$ret = implode( $separator, $links );
		} else {
			$ret = '<ul class="post-categories">' . "\n\t" . '<li>' . implode( "</li>\n\t<li>", $links ) . '</li></ul>';
		}

		return $ret;
	}

	function get_obj_name( $obj ) {
		return $obj->name;
	}

	function assertObjectsEquals( $expected, $actual, $exceptions = array() ) {
		if ( ! is_object( $expected ) )
			$this->fail( "Failed to assert that expected object is an object." );

		if ( ! is_object( $actual ) )
			$this->fail( "Failed to assert that actual is an object." );

		foreach ( get_object_vars( $expected ) as $prop => $val ) {
			if ( in_array( $prop, $exceptions ) )
				continue;

			if ( ! property_exists( $actual, $prop ) )
				$this->fail( "Failed to assert that object has expected property '$prop'.");

			if ( $val != $actual->$prop )
				$this->fail( "Failed to assert that '{$actual->$prop}' matches '{$val}' for property '$prop'.");
		}
	}

	function assertObjectArraysEquals( $expected, $actual, $exceptions = array( 'filter', 'object_id' ) ) {
		if ( ! is_array( $expected ) )
			$this->fail( "Failed to assert that expected object is an array." );

		if ( ! is_array( $actual ) )
			$this->fail( "Failed to assert that actual is an arrat." );

		if ( ( $a = count( $expected ) ) != ( $b = count( $actual ) ) )
			$this->fail( "Failed to assert that the array of size $b equals the expected array size of $a.");

		foreach ( $expected as $i => $v ) {
			$this->assertObjectsEquals( $v, $actual[ $i ], $exceptions );
		}
	}

	/**
	 *
	 * TESTS
	 *
	 */



	/* c2c_get_peer_categories() */

	function test_post_with_all_categories_in_branch_assigned_for_c2c_get_peer_categories() {
		$post_id = $this->factory->post->create();
		wp_set_post_categories( $post_id, array( $this->cats['cat'], $this->cats['cat_1'], $this->cats['cat_1_1'] ) );

		$expected =  array(
			get_category( $this->cats['cat_1_2'] ),
			get_category( $this->cats['cat_1_1'] ),
			get_category( $this->cats['cat_1_3'] ),
		);

		$this->assertObjectArraysEquals( $expected, c2c_get_peer_categories( $post_id ) );
	}

	function test_post_with_mid_level_category_for_c2c_get_peer_categories() {
		$post_id = $this->factory->post->create();
		wp_set_post_categories( $post_id, array( $this->cats['cat_2'] ) );

		$expected =  array(
			get_category( $this->cats['cat_2'] ),
			get_category( $this->cats['cat_1'] ),
			get_category( $this->cats['cat_3'] ),
		);

		$this->assertObjectArraysEquals( $expected, c2c_get_peer_categories( $post_id ) );
	}

	// This verifies coded behavior. But it may seem weird and counter-intuitive.
	// If cat_2 and cat_2_1 are both directly assigned, cat_2 would get excluded. But if
	// cat_3 and cat_2_1 are directly assigned (as this basically tests), cat_2 will get included
	// as a peer to cat_3.
	function test_post_with_leaf_category_and_non_ancestor_mid_leaf_category_for_c2c_get_peer_categories() {
		$post_id = $this->factory->post->create();
		wp_set_post_categories( $post_id, array( $this->cats['cat_3'], $this->cats['cat_2_2'] ) );

		$expected =  array(
			get_category( $this->cats['cat_2_1'] ),
			get_category( $this->cats['cat_2_2'] ),
			get_category( $this->cats['cat_2'] ),
			get_category( $this->cats['cat_1'] ),
			get_category( $this->cats['cat_3'] ),
			get_category( $this->cats['cat_2_3'] ),
		);

		$this->assertObjectArraysEquals( $expected, c2c_get_peer_categories( $post_id ) );
	}

	function test_post_with_category_and_its_grandchild_assigned_for_c2c_get_peer_categories() {
		$post_id = $this->factory->post->create();
		wp_set_post_categories( $post_id, array( $this->cats['cat'], $this->cats['cat_1_1'] ) );

		$expected = array(
			get_category( $this->cats['cat_1_2'] ),
			get_category( $this->cats['cat_1_1'] ),
			get_category( $this->cats['cat_1_3'] ),
		);

		$this->assertObjectArraysEquals( $expected, c2c_get_peer_categories( $post_id ) );
	}

	function test_post_with_category_and_its_grandchild_assigned_and_ancestors_allowed_for_c2c_get_peer_categories() {
		$post_id = $this->factory->post->create();
		wp_set_post_categories( $post_id, array( $this->cats['cat'], $this->cats['cat_1_1'] ) );

		$expected = array(
			get_category( $this->cats['cat_1_2'] ),
			get_category( $this->cats['cat_1_1'] ),
			get_category( $this->cats['cat_1_3'] ),
		);

		$this->assertObjectArraysEquals( $expected, c2c_get_peer_categories( $post_id, false ) );
	}

	function test_filter_c2c_get_peer_categories_omit_ancestors() {
		add_filter( 'c2c_get_peer_categories_omit_ancestors', '__return_false' );

		$post_id = $this->factory->post->create();
		wp_set_post_categories( $post_id, array( $this->cats['cat'], $this->cats['cat_1_1'] ) );

		$expected = array(
			get_category( $this->cats['cat_1_2'] ),
			get_category( $this->cats['cat_1_1'] ),
			get_category( $this->cats['cat_1_3'] ),
		);

		$this->assertObjectArraysEquals( $expected, c2c_get_peer_categories( $post_id ) );
	}

	function test_post_with_all_sibling_categories_for_c2c_get_peer_categories() {
		$post_id = $this->factory->post->create();
		wp_set_post_categories( $post_id, array( $this->cats['cat_3_1'], $this->cats['cat_3_2'], $this->cats['cat_3_3'] ) );

		$expected = array(
			get_category( $this->cats['cat_3_1'] ),
			get_category( $this->cats['cat_3_2'] ),
			get_category( $this->cats['cat_3_3'] ),
		);

		$this->assertObjectArraysEquals( $expected, c2c_get_peer_categories( $post_id ) );
	}

	function test_post_with_cousin_categories_for_c2c_get_peer_categories() {
		$post_id = $this->factory->post->create();
		wp_set_post_categories( $post_id, array( $this->cats['cat_1_1'], $this->cats['cat_2_1'], $this->cats['cat_3_1'] ) );

		$expected = array(
			get_category( $this->cats['cat_2_1'] ),
			get_category( $this->cats['cat_1_2'] ),
			get_category( $this->cats['cat_1_1'] ),
			get_category( $this->cats['cat_1_3'] ),
			get_category( $this->cats['cat_3_1'] ),
			get_category( $this->cats['cat_2_2'] ),
			get_category( $this->cats['cat_3_2'] ),
			get_category( $this->cats['cat_2_3'] ),
			get_category( $this->cats['cat_3_3'] ),
		);

		$this->assertObjectArraysEquals( $expected, c2c_get_peer_categories( $post_id ) );
	}

	function test_implicit_post_id_for_c2c_get_peer_categories() {
		$post_id = $this->factory->post->create();
		wp_set_post_categories( $post_id, array( $this->cats['cat'], $this->cats['cat_1'], $this->cats['cat_1_1'] ) );
		query_posts( array( 'p' => $post_id ) );
		the_post();

		$expected = array(
			get_category( $this->cats['cat_1_2'] ),
			get_category( $this->cats['cat_1_1'] ),
			get_category( $this->cats['cat_1_3'] ),
		);

		$this->assertObjectArraysEquals( $expected, apply_filters( 'c2c_get_peer_categories', $post_id ) );
	}

	function test_post_with_no_categories_for_c2c_get_peer_categories() {
		$post_id = $this->factory->post->create();

		$this->assertEmpty( c2c_get_peer_categories( $post_id ) );
	}

	function test_filter_invocation_for_c2c_get_peer_categories() {
		$post_id = $this->factory->post->create();
		wp_set_post_categories( $post_id, array( $this->cats['cat'], $this->cats['cat_1'], $this->cats['cat_1_1'] ) );

		$expected = array(
			get_category( $this->cats['cat_1_2'] ),
			get_category( $this->cats['cat_1_1'] ),
			get_category( $this->cats['cat_1_3'] ),
		);

		$this->assertObjectArraysEquals( $expected, apply_filters( 'c2c_get_peer_categories', $post_id ) );
	}

	/* c2c_get_peer_categories_list() */

	function test_c2c_get_peer_categories_list() {
		$post_id = $this->factory->post->create();
		wp_set_post_categories( $post_id, array( $this->cats['cat'], $this->cats['cat_1'], $this->cats['cat_1_1'] ) );

		$expected = $this->expected( array( $this->cats['cat_1_2'], $this->cats['cat_1_1'], $this->cats['cat_1_3'] ) );

		$this->assertEquals( $expected, c2c_get_peer_categories_list( '', $post_id ) );
	}

	function test_custom_separator_for_c2c_get_peer_categories_list() {
		$post_id = $this->factory->post->create();
		wp_set_post_categories( $post_id, array( $this->cats['cat_2_1'], $this->cats['cat_3_1'] ) );

		$expected = $this->expected(
			array( $this->cats['cat_2_1'], $this->cats['cat_3_1'], $this->cats['cat_2_2'], $this->cats['cat_3_2'], $this->cats['cat_2_3'], $this->cats['cat_3_3'] ),
			', '
		);

		$this->assertEquals( $expected, c2c_get_peer_categories_list( ', ', $post_id ) );
	}

	function test_implicit_post_id_for_c2c_get_peer_categorie_list() {
		$post_id = $this->factory->post->create();
		wp_set_post_categories( $post_id, array( $this->cats['cat'], $this->cats['cat_1'], $this->cats['cat_1_1'] ) );
		query_posts( array( 'p' => $post_id ) );
		the_post();

		$expected = $this->expected( array( $this->cats['cat_1_2'], $this->cats['cat_1_1'], $this->cats['cat_1_3'] ) );

		$this->assertEquals( $expected, c2c_get_peer_categories_list() );
	}

	function test_explicit_post_id_for_c2c_get_peer_categories_list() {
		$post_id1 = $this->factory->post->create();
		wp_set_post_categories( $post_id1, array( $this->cats['cat_1_3'], $this->cats['cat_3_3'] ) );
		$post_id2 = $this->factory->post->create();
		wp_set_post_categories( $post_id2, array( $this->cats['cat_2_1'], $this->cats['cat_3_1'] ) );

		$expected1 = $this->expected(
			array( $this->cats['cat_1_2'], $this->cats['cat_1_1'], $this->cats['cat_1_3'], $this->cats['cat_3_1'], $this->cats['cat_3_2'], $this->cats['cat_3_3'] ),
			', '
		);
		$expected2 = $this->expected(
			array( $this->cats['cat_2_1'], $this->cats['cat_3_1'], $this->cats['cat_2_2'], $this->cats['cat_3_2'], $this->cats['cat_2_3'], $this->cats['cat_3_3'] ),
			', '
		);

		$this->assertEquals( $expected1, c2c_get_peer_categories_list( ', ', $post_id1 ) );
		$this->assertEquals( $expected2, c2c_get_peer_categories_list( ', ', $post_id2 ) );
	}

}
