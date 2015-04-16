<?php
/* Group blog extension using the BuddyPress group extension API */
if ( class_exists('BP_Group_Extension' ) ) {

	class Group_External_Blogs extends BP_Group_Extension {

		function __construct() {
			global $bp;

			$this->name = __( 'External Blogs', 'bp-groups-externalblogs' );
			$this->slug = 'external-blog-feeds';
			$this->create_step_position = 21;
			$this->enable_nav_item = false;
		}


		function create_screen( $group_id = null ) {
			global $bp;
			if ( !bp_is_group_creation_step( $this->slug ) )
				return false;


			$times = array( '10', '15', '20', '30', '60' );

			echo '<label for="fetch-time">' .  _e( 'Refresh time:', 'bp-groups-externalblogs' ) . '</label>';

			echo "<select id='fetch-time' name='fetch-time'>";

			$default = __( 'Default', 'buddysuite' );

			echo "<option value='30'>$default</option>";

			foreach( $times as $time ) {

				$selected = ( $fetch == $time ) ? 'selected="selected"' : '';

				echo "<option value='$time' $selected>$time</option>";
			}
			echo "</select>  ";


			?>
			<p><?php _e(
				"Add RSS feeds of blogs you'd like to attach to this group in the box below.
				 Any future posts on these blogs will show up on the group page and be recorded
				 in activity streams.", 'bp-groups-externalblogs' ) ?>
			</p>

			<p>

				<span class="desc"><?php _e( "Seperate URL's with commas.", 'bp-groups-externalblogs' ) ?></span>
				<label for="blogfeeds"><?php _e( "Feed URL's:", 'bp-groups-externalblogs' ) ?></label>
				<textarea name="blogfeeds" id="blogfeeds"><?php echo implode( ', ', array_map( 'esc_url', (array) groups_get_groupmeta( bp_get_current_group_id(), 'blogfeeds' ) ) ); ?></textarea>
			</p>
			<?php
			wp_nonce_field( 'groups_create_save_' . $this->slug );
		}


		public function create_screen_save( $group_id = null ) {
			global $bp;

			check_admin_referer( 'groups_create_save_' . $this->slug );
			$unfiltered_feeds = explode( ',', $_POST['blogfeeds'] );

			foreach( (array) $unfiltered_feeds as $blog_feed ) {

				if ( ! empty( $blog_feed ) ) {
					$blog_feeds[] = esc_url_raw( trim( $blog_feed ) );
				}

			}
			groups_update_groupmeta( bp_get_current_group_id(), 'fetchtime', $_POST['fetch-time'] );
			groups_update_groupmeta( bp_get_current_group_id(), 'blogfeeds', $blog_feeds );
			groups_update_groupmeta( bp_get_current_group_id(), 'bp_groupblogs_lastupdate', gmdate( "Y-m-d H:i:s" ) );
			/* Fetch */
			bp_groupblogs_fetch_group_feeds( bp_get_current_group_id() );
		}


		function edit_screen( $group_id = null ) {
			global $bp;

			if ( !bp_is_group_admin_screen( $this->slug ) )
				return false;

			$meta = groups_get_groupmeta( bp_get_current_group_id(), 'fetchtime' );

			$fetch = !empty( $meta ) ? $meta : '30' ;

			$times = array( '10', '15', '20', '30', '60' );

			echo '<p><label for="fetch-time">';  _e( "Refresh time:", "bp-groups-externalblogs" ); echo '</label>';

			echo "<select id='fetch-time' name='fetch-time'>";

			$default = __( 'Default', 'buddysuite' );

			echo "<option value='30'>$default</option>";

			foreach( $times as $time ) {

				$selected = ( $fetch == $time ) ? 'selected="selected"' : '';

				echo "<option value='$time' $selected>$time</option>";
			}
			echo "</select></p>";


			?>


			<span class="desc"><?php _e( "Enter RSS feed URL's for blogs you would like to attach to this group. Any future posts on these blogs will show on the group activity stream. Seperate URL's with commas.", 'bp-groups-externalblogs' ) ?></span>
			<p>
				<label for="blogfeeds"><?php _e( "Feed URL's:", 'bp-groups-externalblogs' ) ?></label>
				<textarea name="blogfeeds" id="blogfeeds"><?php echo implode( ', ', array_map( 'esc_url', (array) groups_get_groupmeta( bp_get_current_group_id(), 'blogfeeds' ) ) ); ?></textarea>
			</p>
			<input type="submit" name="save" value="<?php _e( "Update Feed URL's", 'bp-groups-externalblogs' ) ?>" />
			<?php
			wp_nonce_field( 'groups_edit_save_' . $this->slug );
		}


		function edit_screen_save( $group_id = null ) {
			global $bp;

			if ( !isset( $_POST['save'] ) )
				return false;
			check_admin_referer( 'groups_edit_save_' . $this->slug );
			$existing_feeds = (array) groups_get_groupmeta( bp_get_current_group_id(), 'blogfeeds' );
			$unfiltered_feeds = explode( ',', $_POST['blogfeeds'] );
			foreach( (array) $unfiltered_feeds as $blog_feed ) {
				if ( ! empty( $blog_feed ) ) {
					$blog_feeds[] = esc_url_raw( trim( $blog_feed ) );
				}
			}
			/* Loop and find any feeds that have been removed, so we can delete activity stream items */
			if ( ! empty( $existing_feeds ) ) {
				foreach( (array) $existing_feeds as $feed ) {
					if ( ! in_array( $feed, (array) $blog_feeds ) ) {
						$removed[] = $feed;
					}
				}
			}
			if ( $removed  ) {
				foreach( (array) $removed as $feed ) {
					$existing = bp_activity_get( array(
						'user_id' => false,
						'component' => $bp->groups->id,
						'type' => 'exb',
						'item_id' => bp_get_current_group_id(),
						'update_meta_cache' => false,
						'display_comments' => false,
						'meta_query' => array( array (
							'key'   => 'exb_feedurl',
							'value' => trim( $feed ),
						) )
					 ) );

					// only delete items matching the feed
					if ( ! empty( $existing['activities'] ) ) {
						$aids = wp_list_pluck( $existing['activities'], 'id' );
						foreach ( $aids as $aid ) {
							bp_activity_delete( array(
								'id' => $aid
							) );
						}

					// old way - delete all feed items matching the group
					} else {
						bp_activity_delete( array(
							'item_id' => bp_get_current_group_id(),
							'component' => $bp->groups->id,
							'type' => 'exb'
						) );
					}
				}
			}

			groups_update_groupmeta( bp_get_current_group_id(), 'fetchtime', $_POST['fetch-time'] );
			groups_update_groupmeta( bp_get_current_group_id(), 'blogfeeds', $blog_feeds );
			groups_update_groupmeta( bp_get_current_group_id(), 'bp_groupblogs_lastupdate', gmdate( "Y-m-d H:i:s" ) );
			/* Re-fetch */
			bp_groupblogs_fetch_group_feeds( bp_get_current_group_id() );
			bp_core_add_message( __( 'External blog feeds updated successfully!', 'bp-groups-externalblogs' ) );
			bp_core_redirect( bp_get_group_permalink( groups_get_current_group() ) . '/admin/' . $this->slug );
		}
		/* We don't need display functions since the group activity stream handles it all. */
		function display( $group_id = null ) {}
		function widget_display() {}
	}

	bp_register_group_extension( 'Group_External_Blogs' );

	function bp_groupblogs_fetch_group_feeds( $group_id = false ) {
		global $bp;

		if ( empty( $group_id ) ) {
			$group_id = bp_get_current_group_id();
		}

		if ( $group_id == bp_get_current_group_id() ) {
			$group = groups_get_current_group();
		} else {
			$group = new BP_Groups_Group( $group_id );
		}

		if ( !$group ) {
			return false;
		}

		$group_blogs = groups_get_groupmeta( $group_id, 'blogfeeds' );

		/* Set the visibility */
		$hide_sitewide = ( 'public' != $group->status ) ? true : false;

		foreach ( (array) $group_blogs as $feed_url ) {
			$feed_url = trim( $feed_url );
			if ( empty( $feed_url ) ) {
				continue;
			}

			// Make sure the feed is accessible
			$test = wp_remote_get( $feed_url );

			if ( is_wp_error( $test ) ) {
				continue;
			}

			try { $rss = new SimpleXmlElement( $test['body'] ); }
			catch( Exception $e ){
				continue;
			}

			$rss = fetch_feed( trim( $feed_url ) );

			if (!is_wp_error($rss) ) {
				$maxitems = $rss->get_item_quantity( 10 );
				$rss_items = $rss->get_items( 0, $maxitems );

				foreach ( $rss_items as $item ) {;
					$key = $item->get_date( 'U' );
					$items[$key]['title'] = $item->get_title();
					$items[$key]['subtitle'] = $item->get_title();
					//$items[$key]['author'] = $item->get_author()->get_name();
					$items[$key]['blogname'] = $item->get_feed()->get_title();
					$items[$key]['link'] = $item->get_permalink();
					$items[$key]['blogurl'] = $item->get_feed()->get_link();
					$items[$key]['description'] = $item->get_description();
					$items[$key]['source'] = $item->get_source();
					$items[$key]['copyright'] = $item->get_copyright();
					$items[$key]['primary_link'] = $item->get_link();
					$items[$key]['guid'] = $item->get_id();
					$items[$key]['feedurl'] = $feed_url;
				}
			}
		}

		if ( $items ) {
			ksort( $items );
			$items = array_reverse( $items, true );
		} else {
			return false;
		}


		/* Record found blog posts in activity streams */
		foreach ( (array) $items as $post_date => $post ) {

			$activity_action = sprintf( __( 'Blog: %s from %s in the group %s', 'bp-groups-externalblogs' ), '<a class="feed-link" href="' . esc_attr( $post['link'] ) . '">' . esc_attr( $post['title'] ) . '</a>', '<a class="feed-author" href="' . esc_attr( $post['blogurl'] ) . '">' . esc_attr( $post['blogname'] ) . '</a>', '<a href="' . bp_get_group_permalink( $group ) . '">' . esc_attr( $group->name ) . '</a>' );

			$activity_content = '<div>' . strip_tags( bp_create_excerpt( $post['description'], 175 ) ) . '</div>';
			$activity_content = apply_filters( 'bp_groupblogs_activity_content', $activity_content, $post, $group );

			/* Fetch an existing activity_id if one exists. */
			// backpat
			$id = bp_activity_get_activity_id( array( 'user_id' => false, 'action' => $activity_action, 'component' => $bp->groups->id, 'type' => 'exb', 'item_id' => $group_id, 'secondary_item_id' => wp_hash( $post['blogurl'] ) ) );

			// new method
			if ( empty( $id ) ) {
				$existing = bp_activity_get( array(
					'user_id' => false,
					'component' => $bp->groups->id,
					'type' => 'exb',
					'item_id' => $group_id,
					'update_meta_cache' => false,
					'display_comments' => false,
					'meta_query' => array( array (
						'key'   => 'exb_guid',
						'value' => $post['guid'],
					) )
				 ) );

				// we've found an existing entry
				if ( ! empty( $existing['activities'] ) ) {
					$id = (int) $existing['activities'][0]->id;
				}
			}

			/* Record or update in activity streams. */
			// Skip if it already exists
			if ( empty( $id ) ) {
				$aid = groups_record_activity( array(
					'id' => $id,
					'user_id' => false,
					'action' => $activity_action,
					'content' => $activity_content,
					'primary_link' => $post['primary_link'],
					'type' => 'exb',
					'item_id' => $group_id,
					'recorded_time' => gmdate( "Y-m-d H:i:s", $post_date ),
					'hide_sitewide' => $hide_sitewide
				) );

				// save rss guid as activity meta
				bp_activity_update_meta( $aid, 'exb_guid', $post['guid'] );
				bp_activity_update_meta( $aid, 'exb_feedurl', $post['feedurl'] );
			}
		}

		return $items;
	}

	/* Add a filter option to the filter select box on group activity pages */
	function bp_groupblogs_add_filter() { ?>
		<option value="exb"><?php _e( 'External Blogs', 'bp-groups-externalblogs' ) ?></option><?php
	}
	add_action( 'bp_group_activity_filter_options', 'bp_groupblogs_add_filter' );
	add_action( 'bp_activity_filter_options', 'bp_groupblogs_add_filter' );


	/* Fetch group  posts after 30 mins expires and someone hits the group page */
	function bp_groupblogs_refetch() {
		global $bp;

		$last_refetch = groups_get_groupmeta( bp_get_current_group_id(), 'bp_groupblogs_lastupdate' );
		$meta = groups_get_groupmeta( bp_get_current_group_id(), 'fetchtime' );

		$fetch_time = !empty( $meta ) ? $meta : '30' ;

		if ( strtotime( gmdate( "Y-m-d H:i:s" ) ) >= strtotime( '+' .$fetch_time. ' minutes', strtotime( $last_refetch ) ) )
			add_action( 'wp_footer', '_bp_groupblogs_refetch' );

	}
	add_action( 'groups_screen_group_home', 'bp_groupblogs_refetch' );


	/* Refetch the latest group posts via AJAX so we don't stall a page load. */
	function _bp_groupblogs_refetch() {
		global $bp; ?>

		<script type="text/javascript">
			jQuery(document).ready( function() {

				jQuery.post( ajaxurl, {
					action: 'refetch_groupblogs',
					'group_id': <?php echo bp_get_current_group_id() ?>
				},
				function(response){

				});
			});
		</script><?php
		groups_update_groupmeta( bp_get_current_group_id(), 'bp_groupblogs_lastupdate', gmdate( "Y-m-d H:i:s" ) );
	}


	/* Refresh via an AJAX post for the group */
	function bp_groupblogs_ajax_refresh() {

		bp_groupblogs_fetch_group_feeds( $_POST['group_id'] );

	}
	add_action( 'wp_ajax_refetch_groupblogs', 'bp_groupblogs_ajax_refresh' );


	function bp_groupblogs_cron_refresh() {
		global $bp, $wpdb;

		$group_ids = $wpdb->get_col( $wpdb->prepare( "SELECT group_id FROM " . $bp->groups->table_name_groupmeta . " WHERE meta_key = 'blogfeeds'" ) );

		foreach( $group_ids as $group_id )
			bp_groupblogs_fetch_group_feeds( $group_id );
	}
	add_action( 'bp_groupblogs_cron', 'bp_groupblogs_cron_refresh' );
}


// Add a filter option groups avatar
function bp_groupblogs_avatar_type($var) {
	global $activities_template;

	if ( $activities_template->activity->type == "exb" ) {
		return 'group';
	} else {
		return $var;
	}
}
add_action( 'bp_get_activity_avatar_object_groups', 'bp_groupblogs_avatar_type');
add_action( 'bp_get_activity_avatar_object_activity', 'bp_groupblogs_avatar_type');


function bp_groupblogs_avatar_id($var) {
	global $activities_template;

	if ( $activities_template->activity->type == "exb" ) {
		return $activities_template->activity->item_id;
	}

	return $var;

}
add_action( 'bp_get_activity_avatar_item_id', 'bp_groupblogs_avatar_id');

/**
 * Use the RSS feed's primary link instead of native BP activity permalink.
 *
 * @since 1.6.0
 *
 * @param  string $retval   Activity permalink
 * @param  object $activity Activity data for the current activity item.
 * @return string
 */
function bp_groupblogs_filter_get_permalink( $retval, $activity ) {
	if ( 'groups' !== $activity->component ) {
		return $retval;
	}

	if ( 'exb' !== $activity->type ) {
		return $retval;
	}

	if ( ! empty( $activity->primary_link ) ) {
		$retval = esc_url( $activity->primary_link );
	}

	return $retval;
}
add_filter( 'bp_activity_get_permalink', 'bp_groupblogs_filter_get_permalink', 10, 2 );