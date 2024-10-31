=== Restrict Content Pro - Limited Quantity Available ===
Author URI: https://ithemes.com
Author: iThemes
Contributors: jthillithemes, ithemes
Tags: Restrict Content Pro, limited quantity, premium content, memberships, subscriptions, restrictions
Requires at least: 4.5
Tested up to: 5.8.0
Stable tag: 1.0.3
License: GPLv2 or later

Limit the number of times a membership level can be purchased on your Restrict Content Pro powered membership site.


== Description ==

**On October 14th, 2021, all Restrict Content Pro add-ons will be removed from the WordPress plugin repository.**

**This plugin and all other Restrict Content Pro add-ons will remain available to download in your <a href="https://members.ithemes.com/panel/downloads.php">iThemes Member's Panel</a>.**

This add-on enables you to limit the number of times a membership level can be purchased on your membership site powered by Restrict Content Pro. After the limit has been reached, the membership level is deactivated, hiding it from the registration form. Existing subscribers will continue to be billed, if they are set up for automatic renewals.

This plugin is an add-on for [Restrict Content Pro](https://restrictcontentpro.com/).

Once activated, this plugin will provide a new option on the membership level add/edit screen that lets you define the maximum number of times the level can be purchased.

Please note: this plugin requires PHP 5.3 or later. If you don't know what that means, ask your web host. If you're already using Restrict Content Pro, you're good to go because it requires PHP 5.3 or later as well.

== Installation ==

1. Go to Plugins > Add New in your WordPress dashboard.
2. Search for "Restrict Content Pro - Limited Quantity Available"
3. Click "Install Now" on the plugin listed in the search results.
4. Click "Activate Plugin" after the plugin is installed.
5. Define the level quantity in Restrict Content Pro under Restrict > Membership Levels.

== Frequently Asked Questions ==
= How does it work? =
When a membership level is configured for limited availability, each time that level is purchased the total number of sales for that level is incremented. When the total number of sales reaches the maximum quantity available, the level is deactivated so that it is hidden from the registration form. Existing memberships will continue to be billed, if they are set up for automatic renewals, and they will continue to have access to the restricted content their membership provides until their membership expires or is cancelled.

== Screenshots ==

1. Membership level edit screen

== Changelog ==

= 1.0.2 - 19 November 2019 =
* Tested with WordPress 5.3.
* Tweak: Increase width of Membership Level "Total Quantity Available" setting input field.
* Tweak: Update plugin description.

= 1.0.1 =
* Fix: Quantity limitations not working with `[register_form_stripe]` shortcode.
* Fix: Using incorrect number of parameters in `remove_filter()` function.
* Tweak: Updated and improved PHPDocs.

= 1.0 =
* Initial Release
