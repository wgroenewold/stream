<?php

class WP_Stream_Connector_Blogs extends WP_Stream_Connector {

	/**
	 * Connector slug
	 *
	 * @access public
	 * @static
	 *
	 * @var string
	 */
	public static $name = 'blogs';

	/**
	 * Actions registered for this connector
	 *
	 * @access public
	 * @static
	 *
	 * @var array
	 */
	public static $actions = array(
		'wpmu_new_blog',
		'wpmu_activate_blog',
		'wpmu_new_user',
		'add_user_to_blog',
		'remove_user_from_blog',
		'make_spam_blog',
		'make_ham_blog',
		'mature_blog',
		'unmature_blog',
		'archive_blog',
		'unarchive_blog',
		'make_delete_blog',
		'make_undelete_blog',
		'update_blog_public',
	);

	/**
	 * Return translated connector label
	 *
	 * @access public
	 * @static
	 *
	 * @return string
	 */
	public static function get_label() {
		return __( 'Sites', 'stream' );
	}

	/**
	 * Return translated action labels
	 *
	 * @access public
	 * @static
	 *
	 * @return array
	 */
	public static function get_action_labels() {
		return array(
			'updated'      => __( 'Updated', 'stream' ),
			'created'      => __( 'Created', 'stream' ),
			'archive_blog' => __( 'Archived', 'stream' ),
			'deleted'      => __( 'Deleted', 'stream' ),
		);
	}

	/**
	 * Return translated context labels
	 *
	 * @access public
	 * @static
	 *
	 * @return array
	 */
	public static function get_context_labels() {
		$labels = array();

		if ( is_multisite() && ! wp_is_large_network() ) {
			$blogs = wp_get_sites();

			foreach ( $blogs as $blog ) {
				$blog_details   = get_blog_details( $blog['blog_id'] );
				$key            = sanitize_key( $blog_details->blogname );
				$labels[ $key ] = $blog_details->blogname;
			}
		}

		return $labels;
	}

	/**
	 * Add action links to Stream drop row in admin list screen
	 *
	 * @filter wp_stream_action_links_{connector}
	 *
	 * @access public
	 * @static
	 *
	 * @param array $links
	 * @param int   $record
	 *
	 * @return array
	 */
	public static function action_links( $links, $record ) {
		$links [ __( 'Site Admin', 'stream' ) ] = get_admin_url( $record->object_id );

		if ( $record->object_id ) {
			$site_admin_link = get_admin_url( $record->object_id );

			if ( $site_admin_link ) {
				$links [ __( 'Site Admin', 'stream' ) ] = $site_admin_link;
			}

			$site_settings_link = add_query_arg(
				array(
					'id' => $record->object_id,
				),
				network_admin_url( 'site-info.php' )
			);

			if ( $site_settings_link ) {
				$links [ __( 'Site Settings', 'stream' ) ] = $site_settings_link;
			}
		}

		return $links;
	}

	/**
	 * @action wpmu_new_blog
	 *
	 * @access public
	 * @static
	 *
	 * @param int $blog_id
	 */
	public static function callback_wpmu_new_blog( $blog_id ) {
		$blog    = get_blog_details( $blog_id );
		$context = sanitize_key( $blog->blogname );

		self::log(
			_x(
				'A new site called "%1$s" has been created.',
				'1. Site name',
				'stream'
			),
			array(
				'site_name' => $blog->blogname,
			),
			$blog_id,
			array( $context => 'created' )
		);
	}

	/**
	 * @action wpmu_activate_blog
	 *
	 * @access public
	 * @static
	 *
	 * @param int $blog_id
	 * @param int $user_id
	 */
	public static function callback_wpmu_activate_blog( $blog_id, $user_id ) {
		$blog    = get_blog_details( $blog_id );
		$context = sanitize_key( $blog->blogname );

		self::log(
			_x(
				'A new site called "%1$s" has been registered.',
				'1. Site name',
				'stream'
			),
			array(
				'site_name' => $blog->blogname,
			),
			$blog_id,
			array( $context => 'created' ),
			$user_id
		);
	}

	/**
	 * @action add_user_to_blog
	 *
	 * @access public
	 * @static
	 *
	 * @param int    $user_id
	 * @param string $role
	 * @param int    $blog_id
	 *
	 * @return void
	 */
	public static function callback_add_user_to_blog( $user_id, $role, $blog_id ) {
		$blog    = get_blog_details( $blog_id );
		$user    = get_user_by( 'id', $user_id );
		$context = sanitize_key( $blog->blogname );

		if ( ! is_a( $user, 'WP_User' ) ) {
			return;
		}

		self::log(
			_x(
				'%1$s has been added to the site "%2$s" with %3$s capabilities.',
				'1. User\'s name, 2. Site name, 3. Role',
				'stream'
			),
			array(
				'user_name' => $user->display_name,
				'site_name' => $blog->blogname,
				'role_name' => $role,
			),
			$blog_id,
			array( $context => 'updated' )
		);
	}

	/**
	 * @action remove_user_from_blog
	 *
	 * @access public
	 * @static
	 *
	 * @param int $user_id
	 * @param int $blog_id
	 *
	 * @return void
	 */
	public static function callback_remove_user_from_blog( $user_id, $blog_id ) {
		$blog    = get_blog_details( $blog_id );
		$user    = get_user_by( 'id', $user_id );
		$context = sanitize_key( $blog->blogname );

		if ( ! is_a( $user, 'WP_User' ) ) {
			return;
		}

		self::log(
			_x(
				'%1$s has been removed from the site "%2$s".',
				'1. User\'s name, 2. Site name',
				'stream'
			),
			array(
				'user_name' => $user->display_name,
				'site_name' => $blog->blogname,
			),
			$blog_id,
			array( $context => 'updated' )
		);
	}

	/**
	 * @action make_spam_blog
	 *
	 * @access public
	 * @static
	 *
	 * @param int $blog_id
	 *
	 * @return void
	 */
	public static function callback_make_spam_blog( $blog_id ) {
		self::callback_update_blog_status( $blog_id, __( 'marked as spam', 'stream' ), 'updated' );
	}

	/**
	 * @action make_ham_blog
	 *
	 * @access public
	 * @static
	 *
	 * @param int $blog_id
	 *
	 * @return void
	 */
	public static function callback_make_ham_blog( $blog_id ) {
		self::callback_update_blog_status( $blog_id, __( 'marked as not spam', 'stream' ), 'updated' );
	}

	/**
	 * @action mature_blog
	 *
	 * @access public
	 * @static
	 *
	 * @param int $blog_id
	 *
	 * @return void
	 */
	public static function callback_mature_blog( $blog_id ) {
		self::callback_update_blog_status( $blog_id, __( 'marked as mature', 'stream' ), 'updated' );
	}

	/**
	 * @action unmature_blog
	 *
	 * @access public
	 * @static
	 *
	 * @param int $blog_id
	 *
	 * @return void
	 */
	public static function callback_unmature_blog( $blog ) {
		self::callback_update_blog_status( $blog_id, __( 'marked as not mature', 'stream' ), 'updated' );
	}

	/**
	 * @action archive_blog
	 *
	 * @access public
	 * @static
	 *
	 * @param int $blog_id
	 *
	 * @return void
	 */
	public static function callback_archive_blog( $blog ) {
		self::callback_update_blog_status( $blog_id, __( 'archived', 'stream' ), 'archive_blog' );
	}

	/**
	 * @action unarchive_blog
	 *
	 * @access public
	 * @static
	 *
	 * @param int $blog_id
	 *
	 * @return void
	 */
	public static function callback_unarchive_blog( $blog_id ) {
		self::callback_update_blog_status( $blog_id, __( 'restored from archive', 'stream' ), 'updated' );
	}

	/**
	 * @action make_delete_blog
	 *
	 * @access public
	 * @static
	 *
	 * @param int $blog_id
	 *
	 * @return void
	 */
	public static function callback_make_delete_blog( $blog_id ) {
		self::callback_update_blog_status( $blog_id, __( 'deleted', 'stream' ), 'deleted' );
	}

	/**
	 * @action make_undelete_blog
	 *
	 * @access public
	 * @static
	 *
	 * @param int $blog_id
	 *
	 * @return void
	 */
	public static function callback_make_undelete_blog( $blog_id ) {
		self::callback_update_blog_status( $blog_id, __( 'restored', 'stream' ), 'updated' );
	}

	/**
	 * @action update_blog_public
	 *
	 * @access public
	 * @static
	 *
	 * @param int $blog_id
	 *
	 * @return void
	 */
	public static function callback_update_blog_public( $blog_id, $value ) {
		$status = ( $value ) ? __( 'marked as public', 'stream' ) : __( 'marked as private', 'stream' );

		self::callback_update_blog_status( $blog_id, $status, 'updated' );
	}

	/**
	 * @action update_blog_public
	 *
	 * @access public
	 * @static
	 *
	 * @param int $blog_id
	 *
	 * @return void
	 */
	public static function callback_update_blog_status( $blog_id, $status, $action ) {
		$blog    = get_blog_details( $blog_id );
		$context = sanitize_key( $blog->blogname );

		self::log(
			_x(
				'"%1$s" has been %2$s.',
				'1. Site name, 2. Status',
				'stream'
			),
			array(
				'site_name' => $blog->blogname,
				'status'    => $status,
			),
			$blog_id,
			array( $context => $action )
		);

	}

}
