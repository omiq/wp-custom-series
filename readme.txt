=== Custom Series Plugin ===
Contributors: chrisgarrett
Tags: series, posts, content organization, blog
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.7
Requires PHP: 7.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Organize your blog posts into connected series to help readers navigate related content.

== Description ==

Custom Series Plugin allows you to organize your WordPress blog posts into connected series. This helps readers discover and navigate related content more easily.

= Features =

* Add a "Series" field to your posts
* Quick edit support for series assignment
* Bulk edit support for assigning multiple posts to a series at once
* Series management page in admin
* Shortcode to display posts in a series
* Gutenberg block with extensive formatting options
* Custom titles and descriptions for each series

= How to Use =

1. Install and activate the plugin
2. Edit any post to assign it to a series
3. Use the [series] shortcode or Gutenberg block to display posts in a series
4. Manage series titles and descriptions from the Series menu
5. Use bulk edit to assign multiple posts to a series at once

= Shortcode Usage =

Basic usage:
[series]

With specific series name:
[series name="Your Series Name"]

= Gutenberg Block =

The plugin includes a Gutenberg block that provides the same functionality as the shortcode but with additional formatting options:

* Alignment (left, center, right)
* Show/hide series title and description
* Custom colors for background, text, and title
* Adjustable padding and margins
* Border customization (width, color, radius)

= Bulk Edit =

The plugin adds a Series field to the bulk edit panel, allowing you to:

* Assign multiple posts to an existing series at once
* Create a new series and assign multiple posts to it
* Maintain the current series when editing a single post

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/custom-series` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Settings->Series screen to configure the plugin

== Frequently Asked Questions ==

= Can I use this with custom post types? =

Currently, the plugin only works with the default 'post' type.

= How do I style the series list? =

The plugin adds CSS classes that you can customize in your theme:
* .custom-series
* .custom-series-title
* .custom-series-description
* .custom-series-list
* .custom-series-list-item
* .custom-series-list-item.current

= How do I use the bulk edit feature? =

1. Go to Posts > All Posts
2. Select multiple posts using the checkboxes
3. Choose "Edit" from the Bulk Actions dropdown and click "Apply"
4. In the bulk edit panel, you'll see a "Series" dropdown
5. Choose an existing series or select "— New Series —"
6. If creating a new series, enter the name in the text field that appears
7. Click "Update" to apply the changes to all selected posts

== Changelog ==

= 1.7 =
* Added Gutenberg block with extensive formatting options
* Added bulk edit functionality for series assignment
* Improved shortcode output

= 1.6 =
* Added series management page
* Added support for series titles and descriptions
* Improved shortcode output

= 1.0 =
* Initial release

== Upgrade Notice ==

= 1.7 =
Major update adding Gutenberg block support and bulk edit functionality for easier series management. 