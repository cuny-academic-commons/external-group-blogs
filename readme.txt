=== BuddyPress External Group Blogs ===
Contributors: apeatling, modemlooper, boonebgorges, r-a-y, cuny-academic-commons
Tags: buddypress, groups, blogs, external-blogs, feeds, aggregation
Requires at least: WordPress 3.5 / BuddyPress 1.7
Tested up to: WordPress 4.6 / BuddyPress 2.6
Stable Tag: 1.6.1

== Description ==

Give group creators and administrators on your BuddyPress install the ability to attach external blog RSS feeds to groups.

Blog posts will appear within the activity stream for the group.

New posts will automatically be pulled every hour, or every 30 minutes if someone specifically visits a group page.

== Installation ==

 1. Copy bp-groups-externalblogs.php to /wp-content/plugins/
 2. In the Wordpress Admin panel, visit the plugins page and Activate the plugin.
 3. When creating new groups you will see a new creation step "External Blogs".
 4. Blogs can be added to an existing group by visiting that group's admin menu.

== Changelog ==

= 1.6.1 =
* Fix PHP notice

= 1.6.0 =
* Better RSS validation before parsing a feed
* Update a group's RSS feeds when a user visits the group homepage after a certain, configurable interval
* Record the RSS guid as activity meta for better unique detection to prevent duplicate activity items
* Record the RSS URL as activity meta to deal with deleting a specific RSS feed from a group
* Use the RSS postlink when recording the activity's primary_link
* Fix strict standards notices

= 1.5.2 =
* Fixes potential fatal error due to dynamic function redeclaration

= 1.5.1 =
* Whitespace cleanup

= 1.5 =
* fixes for latest version BuddyPress.

= 1.2.1 =

* Adding missing filter option and fixed duplication bug

= 1.2 =

* Fixes for BuddyPress 1.2 final, as well as better cron support for auto-updates.

= 1.1 =
* Updated the plugin to support BuddyPress 1.2

= 1.0 =
* Initial release.
