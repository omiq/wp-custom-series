# wp-custom-series
WordPress plugin to group articles into custom series

## Using the Plugin:
### Add "Series" Field:

When editing a post, you will see the "Series" field where you can enter a series name.
### Quick Edit:

The "Series" field will also be available in the Quick Edit screen of the posts list.
### Shortcode:

Use the shortcode ```[series name="SeriesName"]``` to display a list of posts in the same series, ommitting the name= will show the series set for the current post.


## Features in v1

* Defines the plugin and adds a meta box for the "Series" field in the post editor.
* Saves the "Series" custom field data when a post is saved.
* Adds the "Series" field to the Quick Edit screen and saves the data when a quick edit is performed.
* Creates a ```[series]``` shortcode that lists posts in the same series, excluding the current post from linking to itself.
* Allows you to optionally set a title and/or description for the series and output it in the table of contents.
