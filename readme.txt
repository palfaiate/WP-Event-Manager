=== WP Event Manager ===
Contributors: mikejolley, automattic, adamkheckler, alexsanford1, annezazu, cena, chaselivingston, csonnek, davor.altman, donnapep, donncha, drawmyface, erania-pinnera, jacobshere, jakeom, jeherve, jenhooks, jgs, jonryan, kraftbj, lamdayap, lschuyler, macmanx, nancythanki, orangesareorange, rachelsquirrel, ryancowles, richardmtl, scarstocea
Tags: event manager, event listing, event board, event management, event lists, event list, event, events, company, hiring, employment, employer, employees, candidate, freelance, internship, event listings, positions, board, application, hiring, listing, manager, recruiting, recruitment, talent
Requires at least: 4.9
Tested up to: 5.2
Requires PHP: 5.6
Stable tag: 1.33.5
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Manage event listings from the WordPress admin panel, and allow users to post event listings directly to your site.

== Description ==

WP Event Manager is a **lightweight** event listing plugin for adding event-board like functionality to your WordPress site. Being shortcode based, it can work with any theme (given a bit of CSS styling) and is really simple to setup.

= Features =

* Add, manage, and categorize event listings using the familiar WordPress UI.
* Searchable & filterable ajax powered event listings added to your pages via shortcodes.
* Frontend forms for guests and registered users to submit & manage event listings.
* Allow event listers to preview their listing before it goes live. The preview matches the appearance of a live event listing.
* Each listing can be tied to an email or website address so that event seekers can apply to the events.
* Searches also display RSS links to allow event seekers to be alerted to new events matching their search.
* Allow logged in employers to view, edit, mark filled, or delete their active event listings.
* Developer friendly code — Custom Post Types, endpoints & template files.

The plugin comes with several shortcodes to output events in various formats, and since its built with Custom Post Types you are free to extend it further through themes.

[Read more about WP Event Manager](https://wpeventmanager.com/).

= Documentation =

Documentation for the core plugin and add-ons can be found [on the docs site here](https://wpeventmanager.com/documentation/). Please take a look before requesting support because it covers all frequently asked questions!

= Add-ons =

The core WP Event Manager plugin is free and always will be. It covers all functionality we consider 'core' to running a simple event board site.

Additional, advanced functionality is available through add-ons. Not only do these extend the usefulness of the core plugin, they also help fund the development and support of core.

You can browse available add-ons after installing the plugin by going to `event Listings > Add-ons`. Our popular add-ons include:

**[Applications](https://wpeventmanager.com/add-ons/applications/)**

Allow candidates to apply to events using a form & employers to view and manage the applications from their event dashboard.

**[WooCommerce Paid Listings](https://wpeventmanager.com/add-ons/wc-paid-listings/)**

Paid listing functionality powered by WooCommerce. Create custom event packages which can be purchased or redeemed during event submission. Requires the WooCommerce plugin.

**[Resume Manager](https://wpeventmanager.com/add-ons/resume-manager/)**

Resume Manager is a plugin built on top of WP Event Manager which adds a resume submission form to your site and resume listings, all manageable from WordPress admin.

**[event Alerts](https://wpeventmanager.com/add-ons/event-alerts/)**

Allow registered users to save their event searches and create alerts which send new events via email daily, weekly or fortnightly.

**[Core add-on bundle](https://wpeventmanager.com/add-ons/bundle/)**

You can get the above add-ons and several others at discount with our [Core Add-on Bundle](https://wpeventmanager.com/add-ons/bundle/). Take a look!

= Contributing and reporting bugs =

You can contribute code to this plugin via GitHub: [https://github.com/Automattic/wp-event-manager](https://github.com/Automattic/wp-event-manager) and localizations via [https://translate.wordpress.org/projects/wp-plugins/wp-event-manager](https://translate.wordpress.org/projects/wp-plugins/wp-event-manager)

Thanks to all of our contributors.

= Support =

Use the WordPress.org forums for community support where we try to help all users. If you spot a bug, you can log it (or fix it) on [Github](https://github.com/Automattic/wp-event-manager) where we can act upon them more efficiently.

If you need help with one of our add-ons, [please raise a ticket in our help desk](https://wpeventmanager.com/support/).

If you want help with a customization, please consider hiring a developer! [http://events.wordpress.net/](http://events.wordpress.net/) is a good place to start.

== Installation ==

= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don't even need to leave your web browser. To do an automatic install, log in to your WordPress admin panel, navigate to the Plugins menu and click Add New.

In the search field type "WP Event Manager" and click Search Plugins. Once you've found the plugin you can view details about it such as the point release, rating and description. Most importantly of course, you can install it by clicking _Install Now_.

= Manual installation =

The manual installation method involves downloading the plugin and uploading it to your web server via your favorite FTP application.

* Download the plugin file to your computer and unzip it
* Using an FTP program, or your hosting control panel, upload the unzipped plugin folder to your WordPress installation's `wp-content/plugins/` directory.
* Activate the plugin from the Plugins menu within the WordPress admin.

= Getting started =

Once installed:

1. Create a page called "events" and inside place the `[events]` shortcode. This will list your events.
2. Create a page called "submit event" and inside place the `[submit_event_form]` shortcode if you want front-end submissions.
3. Create a page called "event dashboard" and inside place the `[event_dashboard]` shortcode for logged in users to manage their listings.

**Note when using shortcodes**, if the content looks blown up/spaced out/poorly styled, edit your page and above the visual editor click on the 'text' tab. Then remove any 'pre' or 'code' tags wrapping your shortcode.

For more information, [read the documentation](https://wpeventmanager.com/documentation/).

== Frequently Asked Questions ==

= How do I setup WP Event Manager? =
View the getting [installation](https://wpeventmanager.com/document/installation/) and [setup](https://wpeventmanager.com/document/setting-up-wp-event-manager/) guide for advice getting started with the plugin. In most cases it's just a case of adding some shortcodes to your pages!

= Can I use WP Event Manager without frontend event submission? =
Yes! If you don't setup the [submit_event_form] shortcode, you can just post from the admin backend.

= How can I customize the event application process? =
There are several ways to customize the event application process in WP Event Manager, including using some extra plugins (some are free on Wordpress.org).

See: [Customizing the event Application Process](https://wpeventmanager.com/document/customising-event-application-process/)

= How can I customize the event submission form? =
There are three ways to customize the fields in WP Event Manager;

1. For simple text changes, using a localisation file or a plugin such as https://wordpress.org/plugins/say-what/
2. For field changes, or adding new fields, using functions/filters inside your theme's functions.php file: [https://wpeventmanager.com/document/editing-event-submission-fields/](https://wpeventmanager.com/document/editing-event-submission-fields/)
3. Use a 3rd party plugin such as [https://plugins.smyl.es/wp-event-manager-field-editor/](https://plugins.smyl.es/wp-event-manager-field-editor/?in=1) which has a UI for field editing.

If you'd like to learn about WordPress filters, here is a great place to start: [https://pippinsplugins.com/a-quick-introduction-to-using-filters/](https://pippinsplugins.com/a-quick-introduction-to-using-filters/)

= How can I be notified of new events via email? =
If you wish to be notified of new postings on your site you can use a plugin such as [Post Status Notifier](http://wordpress.org/plugins/post-status-notifier-lite/).

= What language files are available? =
You can view (and contribute) translations via the [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/wp-event-manager).

== Unit Testing ==

The plugin contains all the files needed for running tests.
Developers who would like to run the existing tests or add their tests to the test suite and execute them will have to follow these steps:
1. `cd` into the plugin directory.
2. Run the install script(you will need to have `wget` installed) - `bash tests/bin/install-wp-tests.sh <db-name> <db-user> <db-pass> <db-host> <wp-version>`.
3. Run the plugin tests - `phpunit`

The install script installs a copy of WordPress in the `/tmp` directory along with the WordPress unit testing tools. 
It then creates a database based on the parameters passed to it.

== Screenshots ==

1. The submit event form.
2. Submit event preview.
3. A single event listing.
4. event dashboard.
5. event listings and filters.
6. event listings in admin.

== Changelog ==
= 1.34.0 =
* Templates Updated: `content-event_listing.php`, `event-submitted.php`.
* Enhancement: Add support for pre-selecting categories in `[events]` using category slugs in query string (e.g. `/events?search_category=developer,pm,senior`).
* Change: event listing now supports `author` functionality, which will expose the author field in the REST API.
* Change: Menu position is fixed in WP admin. Plugins such as Resumes and Applications will need to be updated to show in WP admin below WPJM. (@technerdlove)
* Change: Filter form on `[events]` resets on page refresh and uses query string as expected.
* Change: No longer required to generate usernames from email for password field. (@manzoorwanijk)
* Change: Use minified version of remote jQuery UI CSS. (@ovidiul)
* Change: Google Maps link uses https.
* Fix: Clear the `filled` flag when relisting a event listing.
* Fix: Page titles are properly set during initial set up. (@JuanchoPestana)
* Fix: Correctly format list of file extensions when an unsupported file type is uploaded.
* Fix: Latitude and longitude are correctly used in `content-event_listing.php` template. (@MarieComet)
* Fix: Delete widget options on plugin uninstall. (@JuanchoPestana)
* Fix: Remove unused parameter in `event-submitted.php` template. (@JuanchoPestana)
* Third Party: Fix issue with saving attachments when using Download Attachments plugin.
* Third Party: Fix issue with Polylang where translations get overwritten on save of another language.
* Dev: Adds the ability to completely disable the state saving functionality of `[events]` results.
* Dev: Allows custom calls to `get_event_listings()` to just get `ids` and `id=>parent`. (@manzoorwanijk)
* Dev: Switched to short-array syntax across plugin. 
* Dev: Updated `jquery-fileupload` library to 10.2.0.
* Dev: Updated `select2` library to 4.0.7.

= 1.33.5 =
* Fix: Issue where a JS error could occur when submitting a event.

= 1.33.4 =
* Note: WP Event Manager now requires a minimum PHP version of 5.6.20.
* Fix: Javascript error in event-submission.js on custom event description fields.
* Fix: Checking typeof undefined should be in quotes in event_submission.js.
* Fix: Plugin activation issue that didn't set up roles correctly.
* Fix: Escaped HTML issue in expiring events email notice.
* Change: Added additional unslashing and sanitization of input variables from forms. 
* Change: Limited direct database access within the plugin and migrated to WordPress core functions when possible.
* Removed: Transient garbage collection. WordPress 4.9 and up handle this automatically.

= 1.33.3 =
* Fix: Upgrade jquery-fileupload to v9.32.0.
* Fix: Set frame origin on pages where shortcodes are embedded.

= 1.33.2 =
* Fix: Issue with `[events]` filter form on some themes and plugins.

= 1.33.1 =
* Fix: reCAPTCHA is checked when saving draft event listings.
* Fix: Fix for fatal error encountered when importing events with WP All Import.
* Fix: Maximum file upload limit is now not set for multiple file upload fields.
* Fix: Theme compatibility fix when event listings are shown outside of the `[events]` shortcode.
* Fix: Custom rich text fields no longer have their HTML tags removed.

= 1.33.0 =
* Enhancement: Allow registered users to save drafts of event listings to be continued later from event dashboard.
* Enhancement: Allow access to event listing fields in REST API.
* Enhancement: Required event categories and event description fields are now checked before submit on frontend event submission form.
* Enhancement: Optimized database query in WP admin event listings page.
* Enhancement: Added submit button on event filter template for `[events]` shortcode to improve accessibility.
* Enhancement: Added option to show company logo on Featured events widget.
* Enhancement: `[events]` filter form values are kept during a session and results cached when clicking on event listing.
* Enhancement: Reintroduce change from 1.32.0 where event types can be preselected in `[events]` shortcode with `?search_event_type=term-slug`. (@felipeelia)
* Fix: Embedded videos are no longer removed from event descriptions.
* Fix: Company logo showing outside of box on event listing page.
* Dev: Limit the number of files per multi-file upload field by passing `file_limit` to the field in the `submit_event_form_fields` filter.
* Dev: Added field type class to fieldset on event submission fields. (@tripflex)
* Deprecation: Removed unreleased REST API implementation hidden under `WPJM_REST_API_ENABLED` constant.
* Deprecation: Added warning for upcoming minimum PHP version requirement of 5.6.20.
* Usage Tracking: Track source of event submission (frontend vs WP admin) to better understand how events are entered.
* Usage Tracking: Track official extension license events and activation to better compare types of usage between users and catch activation errors.

= 1.33.5 =
* Fix: Issue where a JS error could occur when submitting a event.

= 1.32.3 =
* Fix: Escape tooltip text in WordPress admin. (Props hd7exploit)
* Fix: Escape user display names on author selector while editing event listings. (Props hd7exploit)

= 1.32.2 =
* Fix: Issue saving event types for event listings in WordPress admin after WordPress 5.1 update.
* Fix: Add nonce checks on edit/submit forms for logged in users. Will require updates to `templates/event-preview.php` if overridden in theme. (Props to foobar7)
* Fix: Escape JSON encoded strings.
* Fix: Add additional sanitization for file attachment fields.

= 1.32.1 =
* Fix: Adds compatibility with PHP 7.3
* Fix: Restores original site search functionality.

= 1.32.0 =
* Enhancement: Switched from Chosen to Select2 for enhanced dropdown handling and better mobile support. May require theme update.
* Enhancement: Draft and unsubmitted event listings now appear in `[event_dashboard]`, allowing users to complete their submission.
* Enhancement: [REVERTED IN 1.32.1] Filled and expired positions are now hidden from WordPress search. (@felipeelia) 
* Enhancement: Adds additional support for the new block editor. Restricted to classic block for compatibility with frontend editor.
* Enhancement: event types can be preselected in `[events]` shortcode with `?search_event_type=term-slug`. (@felipeelia)
* Enhancement: Author selection in WP admin now uses a searchable dropdown.
* Enhancement: Setup wizard is accessed with a flash message instead of an automatic redirect upon activation.
* Enhancement: When using supported themes, event listing archive slug can be changed in Permalink settings.
* Fix: Company tagline alignment issue with company name. (@0xDELS)
* Fix: "Load Previous Listings" link unnecessarily shows up on `[events]` shortcode. (@tonytettinger)
* Fix: Category selector fixed in the event listings page in WP Admin. (@AmandaJBell)
* Fix: Issue with quote encoding on Apply for event email link.
* Fix: Link `target` attributes have been removed in templates.
* Dev: Allow for event submission flow to be interrupted using `before` argument on form steps.
* Dev: HTML allowed in custom company field labels. (@tripflex)
* Dev: event feed slug name can be customized with the `event_manager_event_feed_name` filter.
* Deprecated: Unreleased REST API implementation using `WPJM_REST_API_ENABLED` was replaced with standard WP REST API.

= 1.31.3 =
* Fix: Escape the attachment URL. (Props to karimeo)
* Fix: Custom event field priority fix when using decimals. (@tripflex)
* Fix: Fix issue with empty mutli-select in WP admin events page. (@felipeelia)
* Fix: Issue with data export when email doesn't have any event listings. 
* Third Party: Improved WPML support. (@vukvukovich)

= 1.31.2 =
* Fix: Adds missing quote from WP admin taxonomy fields. (@redpik)

= 1.31.1 =
* Enhancement: Add option to show company logo in Recent events widget. (@RajeebTheGreat)
* Enhancement: Suggest additional cookie information on Privacy Policy page.
* Enhancement: Add WPJM related meta data to user data extract.
* Fix: Tightened the security of the plugin with additional string escaping.
* Fix: Issue with map link in admin backend. (@RajeebTheGreat)
* Fix: No longer auto-expire event listings in Draft status.
* Fix: Issue with undefined index error in WP admin. (@albionselimaj)
* Fix: Issue with duplicate usernames preventing submission of event listings. (@timothyjensen)
* Dev: Widespread code formatting cleanup throughout the plugin. 

= 1.31.0 =
* Change: Minimum WordPress version is now 4.7.0.
* Enhancement: Add email notifications with initial support for new events, updated events, and expiring listings.
* Enhancement: For GDPR, scrub WPJM data from database on uninstall if option is enabled.
* Enhancement: Filter by Filled and Featured status in WP admin.
* Enhancement: Simplify the display of application URLs.
* Enhancement: When using WPML, prevent changes to page options when on a non-default language. (@vukvukovich) 
* Enhancement: Include company logo in structured data. (@RajeebTheGreat)
* Enhancement: Use more efficient jQuery selectors in scripts. (@RajeebTheGreat)
* Enhancement: Use proper `<h2>` tag in `content-summary-event_listing.php` template for the event title. (@abdullah1908)
* Enhancement: Hide empty categories on `[event]` filter.
* Fix: Update calls to `get_terms()` to use the new format.
* Fix: Maintain the current tab when saving settings in WP Admin.
* Fix: Enqueue the date picker CSS when used on the front-end.
* Fix: Remove errors when widget instance was created without setting defaults.
* REST API Pre-release: Add support for event category taxonomy endpoints.
* Dev: Add `$event_id` parameter to `event_manager_event_dashboard_do_action_{$action}` action hook. (@jonasvogel)
* Dev: Add support for hidden WPJM settings in WP Admin.

= 1.30.2 =
* Enhancement: Show notice when user is using an older version of WordPress.
* Enhancement: Hide unnecessary view mode in WP Admin's event Listings page. (@RajeebTheGreat) 
* Enhancement: Add support for the `paged` parameter in the RSS feed. (@RajeebTheGreat)
* Fix: Minor PHP 7.2 compatibility fixes.
* Dev: Allow `parent` attribute to be passed to `event_manager_dropdown_categories()`. (@RajeebTheGreat)

= 1.30.1 =
* Fix: Minor issue with a strict standard error being displayed on some instances.

= 1.30.0 =
* Enhancement: Adds ability to have a reCAPTCHA field to check if event listing author is human.
* Enhancement: Allows for option to make edits to event listings force listing back into pending approval status.
* Enhancement: Adds spinner and disables form when user submits event listing.
* Enhancement: Update the add-ons page of the plugin.
* Enhancement: Added the ability to sort events randomly on the Featured events Widget.
* Enhancement: Improved handling of alternative date formats when editing event expiration field in WP admin.
* Enhancement: Added star indicator next to featured listings on `[event_dashboard]`.
* Enhancement: Opt-in to usage tracking so we can better improve the plugin.
* Enhancement: Introduced new asset enqueuing strategy that will be turned on in 1.32.0. Requires plugin and theme updates. (Dev notes: https://github.com/Automattic/wp-event-manager/pull/1354)
* Fix: Use WordPress core checks for image formats to not confuse `docx` as an image. (@tripflex)
* Fix: Issue with `[events]` shortcode when `categories` argument is provided.
* Fix: Issue with double encoding HTML entities in custom text area fields.
* Fix: Updates `event-dashboard.php` template with `colspan` fix on no active listings message.
* Fix: Clear event listings cache when deleting a user and their event listings.
* Dev: Adds `is_wpjm()` and related functions to test if we're on a WPJM related page.
* Dev: Adds `event_manager_user_edit_event_listing` action that fires after a user edits a event listing.
* Dev: Adds `event_manager_enable_event_archive_page` filter to enable event archive page.
* Dev: Adds `date` field for custom event listing form fields.

= 1.29.3 =
* Fix: When retrieving event listing results, cache only the post results and not all of `WP_Query` (props slavco)

= 1.29.2 =
* Fix: PHP Notice when sanitizing multiple inputs (bug in 1.29.1 release). (@albionselimaj)

= 1.29.1 =
* Enhancement: When retrieving listings in `[events]` shortcode, setting `orderby` to `rand_featured` will still place featured listings at the top.
* Enhancement: Scroll to show application details when clicking on "Apply for event" button.
* Change: Updates `account-signin.php` template to warn users email will be confirmed only if that is enabled.
* Fix: Sanitize URLs and emails differently on the application method event listing field.
* Fix: Remove PHP notice in Featured events widget. (@himanshuahuja96)
* Fix: String fix for consistent spelling of "license" when appearing in strings. (@garrett-eclipse)
* Fix: Issue with paid add-on licenses not showing up when some third-party plugins were installed.
* Dev: Runs new actions (`event_manager_recent_events_widget_before` and `event_manager_recent_events_widget_after`) inside Recent events widget.
* Dev: Change `wpjm_get_the_event_types()` to return an empty array when event types are disabled.
* See all: https://github.com/Automattic/wp-event-manager/milestone/15?closed=1

= 1.29.0 =
* Enhancement: Moves license and update management for official add-ons to the core plugin.
* Enhancement: Update language for setup wizard with more clear descriptions.
* Fix: Prevent duplicate attachments to event listing posts for non-image media. (@tripflex)
* Fix: PHP error on registration form due to missing placeholder text.
* Fix: Apply `the_event_application_method` filter even when no default is available. (@turtlepod)
* Fix: Properly reset category selector on `[events]` shortcode.

= 1.28.0 =
* Enhancement: Improves support for Google event Search by adding `eventPosting` structured data.
* Enhancement: Adds ability for event types to be mapped to an employment type as defined for Google event Search.
* Enhancement: Requests search engines no longer index expired and filled event listings.
* Enhancement: Improves support with third-party sitemap generation in Jetpack, Yoast SEO, and All in One SEO.
* Enhancement: Updated descriptions and help text on settings page.
* Enhancement: Lower cache expiration times across plugin and limit use of autoloaded cache transients.
* Fix: Localization issue with WPML in the [events] shortcode.
* Fix: Show event listings' published date in localized format.
* Fix: event submission form allows users to select multiple event types when they go back a step.
* Fix: Some themes that overloaded functions would break in previous release.
* Dev: Adds versions to template files so it is easier to tell when they are updated.
* Dev: Adds a new `wpjm_notify_new_user` action that allows you to override default behavior.
* Dev: Early version of REST API is bundled but disabled by default. Requires PHP 5.3+ and `WPJM_REST_API_ENABLED` constant must be set to true. Do not use in production; endpoints may change. (@pkg)

= 1.27.0 =
* Enhancement: Admins can now allow users to specify an account password when posting their first event listing.
* Enhancement: Pending event listing counts are now cached for improved WP Admin performance. (@tripflex)
* Enhancement: Allows users to override permalink slugs in WP Admin's Permalink Settings screen.
* Enhancement: Allows admins to perform bulk updating of events as filled/not filled.
* Enhancement: Adds event listing status CSS classes on single event listings.
* Enhancement: Adds `wpjm_the_event_title` filter for inserting non-escaped HTML alongside event titles in templates.
* Enhancement: Allows admins to filter by `post_status` in `[events]` shortcode.
* Enhancement: Allows accessing settings tab from hash in URL. (@tripflex)
* Fix: Make sure cron events for checking/cleaning expired listings are always in place.
* Fix: Better handling of multiple event types. (@spencerfinnell)
* Fix: Issue with deleting company logos from event listings submission form.
* Fix: Warning thrown on event submission form when user not logged in. (@piersb)  
* Fix: Issue with WPML not syncing some meta fields.
* Fix: Better handling of AJAX upload errors. (@tripflex)
* Fix: Remove event posting cookies on logout.
* Fix: Expiration date can be cleared if default event duration option is empty. (@spencerfinnell)
* Fix: Issue with Safari and expiration datepicker.

= 1.26.2 =
* Fix: Prevents use of Ajax file upload endpoint for visitors who aren't logged in. Themes should check with `event_manager_user_can_upload_file_via_ajax()` if using endpoint in templates.  
* Fix: Escape post title in WP Admin's event Listings page and template segments. (Props to @EhsanCod3r)

= 1.26.1 =
* Enhancement: Add language using WordPress's current locale to geocode requests.
* Fix: Allow attempts to use Google Maps Geocode API without an API key. (@spencerfinnell)
* Fix: Issue affecting event expiry date when editing a event listing. (@spencerfinnell)
* Fix: Show correct total count of results on `[events]` shortcode.

= 1.26.0 =
* Enhancement: Warn the user if they're editing an existing event.
* Enhancement: WP Admin event Listing page's table is now responsive. (@turtlepod)
* Enhancement: New setting for hiding expired listings from `[events]` filter. (@turtlepod)
* Enhancement: Use WP Query's built in search function to improve searching in `[events]`.
* Fix: event Listing filter only searches meta fields with relevant content. Add custom fields with `event_listing_searchable_meta_keys` filter. (@turtlepod)
* Fix: Improved support for WPML and Polylang.
* Fix: Expired field no longer forces admins to choose a date in the future. (@turtlepod)
* Fix: Listings with expiration date in past will immediately expire; moving to Active status will extend if necessary. (@turtlepod)
* Fix: Google Maps API key setting added to fix geolocation retrieval on new sites.
* Fix: Issue when duplicating a event listing with a field for multiple file uploads. (@turtlepod)
* Fix: Hide page results when adding links in the `[submit_event_form]` shortcode.
* Fix: event feed now loads when a site has no posts.
* Fix: No error is thrown when deleting a user. (@tripflex)
* Dev: Plugins and themes can now retrieve JSON of event Listings results without HTML. (@spencerfinnell)
* Dev: Updated inline documentation.

See additional changelog items in changelog.txt
