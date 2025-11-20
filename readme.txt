=== Kit (formerly ConvertKit) for MemberMouse ===
Contributors: nathanbarry, growdev, travisnorthcutt
Donate link: http://kit.com/
Tags: convertkit, email, marketing, membermouse
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.1
Stable tag: 1.3.5
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

This plugin integrates Kit with Member Mouse.

== Description ==

[Kit](https://kit.com) makes it easy to capture more leads and sell more products. This plugin makes it a little bit easier for those of us using Member Mouse to subscribe and tag customers that signup for memberships, products or bundles.

**New to Kit? [Creating an account](https://app.kit.com/users/signup?plan=newsletter-free&utm_source=wordpress&utm_term=en_US&utm_content=readme) is 100% free for your first 10,000 subscribers, making Kit an email marketing solution for everyone - whether you're new to email marketing or a seasoned professional email marketer.**

== Installation ==

1. Upload the `convertkit-membermouse` folder to the `/wp-content/plugins/` directory
2. Active the Kit for MemberMouse plugin through the 'Plugins' menu in WordPress

== Configuration ==

1. Configure the plugin by navigating to `Settings > Kit Membermouse` in the WordPress Administration Menu, and clicking the `Connect` button
2. Select a tag to add to customers who signup for each Membership Level
3. Save your settings

== Screenshots ==

1. Kit MemberMouse settings page

== Frequently asked questions ==

= Does this plugin require a paid service? =

No. You must first have an account on [kit.com](https://kit.com?utm_source=wordpress&utm_term=en_US&utm_content=readme), but you do not have to use a paid plan!

== Changelog ==

### 1.3.5 2025-11-20
* Updated: Use WordPress Libraries 2.1.1

### 1.3.4 2025-09-29
* Updated: WordPress Coding Standards for JS and CSS
* Updated: Use WordPress Libraries 2.1.0

### 1.3.3 2025-07-10
* Fix: Automatically refresh Access Token when expired

### 1.3.2 2025-05-07
* Fix: Require all Plugin files to ensure compatibility with Solid Central (previously iThemes Sync)
* Updated: Updated GPL license from v2 to v3
* Updated: Require PHP 7.1 or greater
* Updated: Use WordPress Libraries 2.0.9

### 1.3.1 2025-04-15
* Updated: Use WordPress Libraries 2.0.8

### 1.3.0 2025-03-25
* Fix: Remove `load_plugin_textdomain` call, as it's not needed since WordPress 4.6
* Updated: Use WordPress Libraries 2.0.7

### 1.2.9 2025-01-23
* Updated: ConvertKit WordPress Libraries to 2.0.6

### 1.2.8 2024-11-27
* Added: Settings: Tagging: Option to remove tag on Membership Level or Bundle removal
* Added: Settings: Custom Fields: Option to store Last Name in a Custom Field

### 1.2.7 2024-11-13
* Added: OAuth: Issue site-specific Access and Refresh Token when using the same Kit account on multiple WordPress sites
* Updated: ConvertKit WordPress Libraries to 2.0.5

### 1.2.6 2024-10-11
* Fix: Kit branding tweaks and secondary button colors

### 1.2.5.1 2024-10-01
* Updated: Changed branding to Kit
* Updated: Kit WordPress Libraries to 2.0.4

### 1.2.4 2024-09-13
* Updated: ConvertKit WordPress Libraries to 2.0.2
* Fix: Don't automatically refresh tokens on non-production sites

### 1.2.3 2024-08-24
* Fix: Include WordPress Libraries 2.0.1 with release

### 1.2.2 2024-08-23
* Added: Use ConvertKit v4 API and OAuth. You'll need to authorize one time at `Settings > ConvertKit MemberMouse > Connect`
* Fix: Update subscriber's email address in ConvertKit when their email address is changed in MemberMouse
* Updated: ConvertKit WordPress Libraries to 2.0.1

### 1.2.1 2024-07-16
* Fix: Settings: Improved UI

### 1.2.0 2024-07-09
* Added: Tag on Product purchase
* Added: Tag on Bundle purchase / assignment
* Fix: Settings: Add 'None' option when tagging by Membership Level, Product or Bundle
* Fix: Ensure code meets WordPress Coding Standards

### 1.1.3 2024-06-04
* Updated: Support for WordPress 6.5.3

### 1.1.2 2020-04-08
* Switch to only use first names to match ConvertKit
* Apply tag on membership level change, not only initial joining of the site

### 1.0.2 2018-01-25
* Added tag to be applied when a membership cancels or a member is deleted.
* Added debug log setting

### 1.0.1
* Fixed PHP short tag causing a T_STRING error.

### 1.0
* Initial release

== Upgrade notice ==

