=== Narrato for Writers by Iftiar ===
Contributors:      iftiarhossain
Tags:              blog, writing, stories, medium, publishing
Requires at least: 6.4
Tested up to:      7.0
Requires PHP:      8.1
Stable tag:        1.0.0
License:           GPLv2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Transform your WordPress site into a clean, Medium-style writing and reading platform.

== Description ==

Narrato for Writers by Iftiar turns any WordPress installation into a focused writing and reading platform inspired by Medium.com.

Install the plugin, activate it, and you immediately get:

* A dedicated **Story** post type with subtitle and reading time support
* A **Topics** taxonomy to organise stories by subject
* Clean, distraction-free front-end templates for single stories, archives, and topic pages
* A reading progress bar on every story
* Auto-calculated reading time based on word count
* A settings page to control layout and display options
* Works with both classic and block themes


= Built for Writers =

* Serif typography optimised for long-form reading
* Mobile-first responsive layout
* Related stories shown at the end of each story
* Author bio box on single story pages
* Topic archive pages with story count


== Installation ==

1. Upload the `narrato-for-writers` folder to the `/wp-content/plugins/` directory, or install directly from the WordPress plugin directory.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **Settings → Narrato Settings** to configure display options.
4. Go to **Stories → Add New** to write your first story.
5. Visit **Settings → Permalinks** and click Save to flush rewrite rules.

== Frequently Asked Questions ==

= Does this plugin work with my current theme? =

Yes. Narrato for Writers injects its own templates for story pages, archives and topic pages. These work alongside both classic and block themes without modifying your theme files.

= Will it conflict with my existing posts? =

No. Narrato for Writers uses a dedicated `story` custom post type. Your existing posts, pages and other content are not affected.

= How is reading time calculated? =

Reading time is automatically calculated when you save or publish a story, based on an average reading speed of 200 words per minute.

= Can I use the blocks on regular pages? =

Yes. The Story Card and Story Feed blocks can be used on any page or post. The Story Header and Reading Time blocks are designed for use inside story content.

= Where do I report bugs or request features? =

Please use the support forum on wordpress.org for bug reports and feature requests.


== Changelog ==

= 1.0.0 =
* Initial release
* Story custom post type with subtitle and reading time meta
* Topics taxonomy
* Front-end templates for single story, archive and topic pages
* Reading progress bar
* Admin settings page

= 1.1.0 =
* New: Claps — logged-in users can clap up to 50 times per story
* New: Bookmarks — save stories to your personal reading list
* New: Floating engagement sidebar on single story pages
* New: My Bookmarks page at /my-bookmarks/
* New: Clap count displayed on story cards and archive
* New: REST API endpoints for claps and bookmarks (narrato/v1)
* Improved: Version upgrade routine with DB migration support

== Upgrade Notice ==

= 1.1.0 =
Stable tag: 1.1.0