<?php
/**
 * Tests for comment Connector class callbacks.
 */

namespace WP_Stream;

class Test_WP_Stream_Connector_Comments extends WP_StreamTestCase {

	/**
	 * Runs before each test
	 */
	public function setUp() {
		parent::setUp();

		// Make partial of Connector_Comments class, with mocked "log" function.
		$this->mock = $this->getMockBuilder( Connector_Comments::class )
			->setMethods( array( 'log' ) )
			->getMock();

		// Register connector.
		$this->mock->register();
	}

	/**
	 * Tests "wp_insert_comment" callback function.
	 */
	public function test_callback_wp_insert_comment() {
		// Create post for later use.
		$post_id = wp_insert_post(
			array(
				'post_title'    => 'Test post',
				'post_content'  => 'Lorem ipsum dolor...',
				'post_status'   => 'publish',
			)
		);

		// Set expected calls for the Mock.
		$this->mock->expects( $this->atLeastOnce() )
			->method( 'log' )
			->withConsecutive(
				array(
					$this->equalTo(
						_x(
							'New %4$s by %1$s on %2$s %3$s',
							'1: Comment author, 2: Post title 3: Comment status, 4: Comment type',
							'stream'
						)
					),
					$this->equalTo(
						array(
							'user_name'      => 'Jim Bean',
							'post_title'     => '"Test post"',
							'comment_status' => 'pending approval',
							'comment_type'   => 'comment',
							'post_id'        => $post_id,
							'is_spam'        => false,
						)
					),
					$this->greaterThan( 0 ),
					$this->equalTo( 'post' ),
					$this->equalTo( 'created' ),
					$this->equalTo( 0 )
				),
				array(
					$this->equalTo(
						_x(
							'Reply to %1$s\'s %5$s by %2$s on %3$s %4$s',
							"1: Parent comment's author, 2: Comment author, 3: Post title, 4: Comment status, 5: Comment type",
							'stream'
						)
					),
					$this->equalTo(
						array(
							'parent_user_name' => 'Jim Bean',
							'user_name'        => 'Jim Bean',
							'post_title'       => '"Test post"',
							'comment_status'   => 'pending approval',
							'comment_type'     => 'comment',
							'post_id'          => "$post_id",
						)
					),
					$this->greaterThan( 0 ),
					$this->equalTo( 'post' ),
					$this->equalTo( 'replied' ),
					$this->equalTo( 0 )
				)
			);

		// Create comments and trigger mocks.
		$comment_id = wp_insert_comment(
			array(
				'comment_content'      => 'Lorem ipsum dolor...',
				'comment_author'       => 'Jim Bean',
				'comment_author_email' => 'jim_bean@example.com',
				'comment_author_IP'    => '::1',
				'comment_post_ID'      => $post_id,
			)
		);
		wp_insert_comment(
			array(
				'comment_content'      => 'Lorem ipsum dolor...',
				'comment_author'       => 'Jim Bean',
				'comment_author_email' => 'jim_bean@example.com',
				'comment_author_IP'    => '::1',
				'comment_post_ID'      => $post_id,
				'comment_parent'       => $comment_id,
			)
		);

		// Confirm callback execution.
		$this->assertFalse( 0 === did_action( 'wp_stream_test_callback_wp_insert_comment' ) );
	}

	/**
	 * Tests "edit_comment" callback function.
	 */
	public function test_callback_edit_comment() {
		// Create post and comment for later use.
		$post_id = wp_insert_post(
			array(
				'post_title'    => 'Test post',
				'post_content'  => 'Lorem ipsum dolor...',
				'post_status'   => 'publish',
			)
		);
		$comment_id = wp_insert_comment(
			array(
				'comment_content'      => 'Lorem ipsum dolor...',
				'comment_author'       => 'Jim Bean',
				'comment_author_email' => 'jim_bean@example.com',
				'comment_author_IP'    => '::1',
				'comment_post_ID'      => $post_id,
			)
		);

		// Set expected calls for the Mock.
		$this->mock->expects( $this->atLeastOnce() )
			->method( 'log' )
			->with(
				$this->equalTo(
					_x(
						'%1$s\'s %3$s on %2$s edited',
						'1: Comment author, 2: Post title, 3: Comment type',
						'stream'
					)
				),
				$this->equalTo(
					array(
						'user_name'    => 'Jim Bean',
						'post_title'   => '"Test post"',
						'comment_type' => 'comment',
						'post_id'      => "$post_id",
						'user_id'      => 0,
					)
				),
				$this->equalTo( $comment_id ),
				$this->equalTo( 'post' ),
				$this->equalTo( 'edited' )
			);

		// Update comment and trigger mock.
		wp_update_comment(
			array(
				'comment_ID'      => $comment_id,
				'comment_content' => 'Lorem ipsum dolor... 2',
			)
		);

		// Confirm callback execution.
		$this->assertFalse( 0 === did_action( 'wp_stream_test_callback_edit_comment' ) );
	}

	/**
	 * Tests "delete_comment" callback function.
	 */
	public function test_callback_delete_comment() {
		// Create post and comment for later use.
		$post_id = wp_insert_post(
			array(
				'post_title'    => 'Test post',
				'post_content'  => 'Lorem ipsum dolor...',
				'post_status'   => 'publish',
			)
		);
		$comment_id = wp_insert_comment(
			array(
				'comment_content'      => 'Lorem ipsum dolor...',
				'comment_author'       => 'Jim Bean',
				'comment_author_email' => 'jim_bean@example.com',
				'comment_author_IP'    => '::1',
				'comment_post_ID'      => $post_id,
			)
		);

		// Set expected calls for the Mock.
		$this->mock->expects( $this->atLeastOnce() )
			->method( 'log' )
			->with(
				$this->equalTo(
					_x(
						'%1$s\'s %3$s on %2$s deleted permanently',
						'1: Comment author, 2: Post title, 3: Comment type',
						'stream'
					)
				),
				$this->equalTo(
					array(
						'user_name'    => 'Jim Bean',
						'post_title'   => '"Test post"',
						'comment_type' => 'comment',
						'post_id'      => "$post_id",
						'user_id'      => 0,
					)
				),
				$this->equalTo( $comment_id ),
				$this->equalTo( 'post' ),
				$this->equalTo( 'deleted' )
			);

		// Delete comment and trigger mock.
		wp_delete_comment( $comment_id, true );

		// Confirm callback execution.
		$this->assertFalse( 0 === did_action( 'wp_stream_test_callback_delete_comment' ) );
	}

	/**
	 * Tests "trash_comment" callback function.
	 */
	public function test_callback_trash_comment() {
		// Create post and comment for later use.
		$post_id = wp_insert_post(
			array(
				'post_title'    => 'Test post',
				'post_content'  => 'Lorem ipsum dolor...',
				'post_status'   => 'publish',
			)
		);
		$comment_id = wp_insert_comment(
			array(
				'comment_content'      => 'Lorem ipsum dolor...',
				'comment_author'       => 'Jim Bean',
				'comment_author_email' => 'jim_bean@example.com',
				'comment_author_IP'    => '::1',
				'comment_post_ID'      => $post_id,
			)
		);

		// Set expected calls for the Mock.
		$this->mock->expects( $this->atLeastOnce() )
			->method( 'log' )
			->with(
				$this->equalTo(
					_x(
						'%1$s\'s %3$s on %2$s trashed',
						'1: Comment author, 2: Post title, 3: Comment type',
						'stream'
					)
				),
				$this->equalTo(
					array(
						'user_name'    => 'Jim Bean',
						'post_title'   => '"Test post"',
						'comment_type' => 'comment',
						'post_id'      => "$post_id",
						'user_id'      => 0,
					)
				),
				$this->equalTo( $comment_id ),
				$this->equalTo( 'post' ),
				$this->equalTo( 'trashed' )
			);

		// Trash comment and trigger mock.
		wp_trash_comment( $comment_id );

		// Confirm callback execution.
		$this->assertFalse( 0 === did_action( 'wp_stream_test_callback_trash_comment' ) );
	}

	/**
	 * Tests "untrash_comment" callback function.
	 */
	public function test_callback_untrash_comment() {
		// Create post and comment for later use.
		$post_id = wp_insert_post(
			array(
				'post_title'    => 'Test post',
				'post_content'  => 'Lorem ipsum dolor...',
				'post_status'   => 'publish',
			)
		);
		$comment_id = wp_insert_comment(
			array(
				'comment_content'      => 'Lorem ipsum dolor...',
				'comment_author'       => 'Jim Bean',
				'comment_author_email' => 'jim_bean@example.com',
				'comment_author_IP'    => '::1',
				'comment_post_ID'      => $post_id,
			)
		);
		wp_trash_comment( $comment_id );

		// Set expected calls for the Mock.
		$this->mock->expects( $this->atLeastOnce() )
			->method( 'log' )
			->with(
				$this->equalTo(
					_x(
						'%1$s\'s %3$s on %2$s restored',
						'1: Comment author, 2: Post title, 3: Comment type',
						'stream'
					)
				),
				$this->equalTo(
					array(
						'user_name'    => 'Jim Bean',
						'post_title'   => '"Test post"',
						'comment_type' => 'comment',
						'post_id'      => "$post_id",
						'user_id'      => 0,
					)
				),
				$this->equalTo( $comment_id ),
				$this->equalTo( 'post' ),
				$this->equalTo( 'untrashed' )
			);

		// Untrash comment and trigger mock.
		wp_untrash_comment( $comment_id );

		// Confirm callback execution.
		$this->assertFalse( 0 === did_action( 'wp_stream_test_callback_untrash_comment' ) );
	}

	/**
	 * Tests "spam_comment" callback function.
	 */
	public function test_callback_spam_comment() {
		// Create post and comment for later use.
		$post_id = wp_insert_post(
			array(
				'post_title'    => 'Test post',
				'post_content'  => 'Lorem ipsum dolor...',
				'post_status'   => 'publish',
			)
		);
		$comment_id = wp_insert_comment(
			array(
				'comment_content'      => 'Lorem ipsum dolor...',
				'comment_author'       => 'Jim Bean',
				'comment_author_email' => 'jim_bean@example.com',
				'comment_author_IP'    => '::1',
				'comment_post_ID'      => $post_id,
			)
		);

		// Set expected calls for the Mock.
		$this->mock->expects( $this->atLeastOnce() )
			->method( 'log' )
			->with(
				$this->equalTo(
					_x(
						'%1$s\'s %3$s on %2$s marked as spam',
						'1: Comment author, 2: Post title, 3: Comment type',
						'stream'
					)
				),
				$this->equalTo(
					array(
						'user_name'    => 'Jim Bean',
						'post_title'   => '"Test post"',
						'comment_type' => 'comment',
						'post_id'      => "$post_id",
						'user_id'      => 0,
					)
				),
				$this->equalTo( $comment_id ),
				$this->equalTo( 'post' ),
				$this->equalTo( 'spammed' )
			);

		// Set comment to spam and trigger mock.
		wp_spam_comment( $comment_id );

		// Confirm callback execution.
		$this->assertFalse( 0 === did_action( 'wp_stream_test_callback_spam_comment' ) );
	}

	/**
	 * Tests "unspam_comment" callback function.
	 */
	public function test_callback_unspam_comment() {
		// Create post and comment for later use.
		$post_id = wp_insert_post(
			array(
				'post_title'    => 'Test post',
				'post_content'  => 'Lorem ipsum dolor...',
				'post_status'   => 'publish',
			)
		);
		$comment_id = wp_insert_comment(
			array(
				'comment_content'      => 'Lorem ipsum dolor...',
				'comment_author'       => 'Jim Bean',
				'comment_author_email' => 'jim_bean@example.com',
				'comment_author_IP'    => '::1',
				'comment_post_ID'      => $post_id,
			)
		);
		wp_spam_comment( $comment_id );

		// Set expected calls for the Mock.
		$this->mock->expects( $this->atLeastOnce() )
			->method( 'log' )
			->with(
				$this->equalTo(
					_x(
						'%1$s\'s %3$s on %2$s unmarked as spam',
						'1: Comment author, 2: Post title, 3: Comment type',
						'stream'
					)
				),
				$this->equalTo(
					array(
						'user_name'    => 'Jim Bean',
						'post_title'   => '"Test post"',
						'comment_type' => 'comment',
						'post_id'      => "$post_id",
						'user_id'      => 0,
					)
				),
				$this->equalTo( $comment_id ),
				$this->equalTo( 'post' ),
				$this->equalTo( 'unspammed' )
			);

		// Unspam comment and trigger mock.
		wp_unspam_comment( $comment_id );

		// Confirm callback execution.
		$this->assertFalse( 0 === did_action( 'wp_stream_test_callback_unspam_comment' ) );
	}

	/**
	 * Tests "transition_comment_status" callback function.
	 */
	public function test_callback_transition_comment_status() {
		// Create post and comment for later use.
		$post_id = wp_insert_post(
			array(
				'post_title'    => 'Test post',
				'post_content'  => 'Lorem ipsum dolor...',
				'post_status'   => 'publish',
			)
		);
		$comment_id = wp_insert_comment(
			array(
				'comment_content'      => 'Lorem ipsum dolor...',
				'comment_author'       => 'Jim Bean',
				'comment_author_email' => 'jim_bean@example.com',
				'comment_author_IP'    => '::1',
				'comment_post_ID'      => $post_id,
			)
		);

		// Set expected calls for the Mock.
		$this->mock->expects( $this->atLeastOnce() )
			->method( 'log' )
			->with(
				$this->equalTo(
					_x(
						'%1$s\'s %3$s %2$s',
						'Comment status transition. 1: Comment author, 2: Post title, 3: Comment type',
						'stream'
					)
				),
				$this->equalTo(
					array(
						'user_name'    => 'Jim Bean',
						'new_status'   => 'unapproved',
						'comment_type' => 'comment',
						'old_status'   => 'pending approval',
						'post_title'   => '"Test post"',
						'post_id'      => "$post_id",
						'user_id'      => 0,
					)
				),
				$this->equalTo( $comment_id ),
				$this->equalTo( 'post' ),
				$this->equalTo( 'unapproved' )
			);

		// Update comment status and trigger mock.
		wp_transition_comment_status( 'hold', 'pending approval', get_comment( $comment_id ) );

		// Confirm callback execution.
		$this->assertFalse( 0 === did_action( 'wp_stream_test_callback_transition_comment_status' ) );
	}

	/**
	 * Tests "comment_duplicate_trigger" callback function.
	 */
	public function test_callback_comment_duplicate_trigger() {
		// Create post and comment for later use.
		$post_id = wp_insert_post(
			array(
				'post_title'    => 'Test post',
				'post_content'  => 'Lorem ipsum dolor...',
				'post_status'   => 'publish',
			)
		);
		$comment_id = wp_new_comment(
			array(
				'comment_content'      => 'Lorem ipsum dolor...',
				'comment_author'       => 'Jim Bean',
				'comment_author_email' => 'jim_bean@example.com',
				'comment_author_url'   => '',
				'comment_author_IP'    => '::1',
				'comment_post_ID'      => $post_id,
				'comment_type'         => 'post',
			)
		);

		// Set expected calls for the Mock.
		$this->mock->expects( $this->atLeastOnce() )
			->method( 'log' )
			->with(
				$this->equalTo(
					_x(
						'Duplicate %3$s by %1$s prevented on %2$s',
						'1: Comment author, 2: Post title, 3: Comment type',
						'stream'
					)
				),
				$this->equalTo(
					array(
						'user_name'    => 'Jim Bean',
						'post_title'   => '"Test post"',
						'comment_type' => 'comment',
						'post_id'      => "$post_id",
						'user_id'      => 0,
					)
				),
				$this->equalTo( $comment_id ),
				$this->equalTo( 'post' ),
				$this->equalTo( 'duplicate' )
			);

		// Create duplicate comment and trigger mock.
		wp_new_comment(
			array(
				'comment_content'      => 'Lorem ipsum dolor...',
				'comment_author'       => 'Jim Bean',
				'comment_author_email' => 'jim_bean@example.com',
				'comment_author_url'   => '',
				'comment_author_IP'    => '::1',
				'comment_post_ID'      => $post_id,
				'comment_type'         => 'post',
			),
			true
		);

		// Confirm callback execution.
		$this->assertFalse( 0 === did_action( 'wp_stream_test_callback_comment_duplicate_trigger' ) );
	}
}
