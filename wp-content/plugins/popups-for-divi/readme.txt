=== Popups for Divi ===
Contributors: timstrifler, devjenndivilife, strackerphil-1
Tags: popup, marketing, divi
Requires at least: 4.0.0
Tested up to: 6.9
Stable tag: 3.2.7
Requires PHP: 7.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A quick and easy way to create Popup layers inside the Divi Visual Builder!

== Description ==

After the plugin is activated, the Visual Builder displays a new tab called "Popup" in the Section Settings modal. In the "Popup" tab, you can turn a regular Section into a Popup!

It's super simple, as you can see on the plugins Demo Page: [divimode.com/divi-popup/demo](https://divimode.com/divi-popup/demo/?utm_source=wporg&utm_medium=link&utm_campaign=popups-for-divi)

# ℹ️ How it works

First, activate the plugin (no configuration needed!)

1. Open up your Visual Builder and edit a Section - you'll see a new "Popup" tab in the Section Settings.
2. Toggle the option "This is a Popup" and set the "Popup ID" to something (e.g., "`newsletter-optin`"). Close the Section Settings.
3. Add a Button Module somewhere else on the page and set the "Link URL" to your Popup ID, with a leading "#" hash (e.g. "`#newsletter-optin`")
4. **That's all**. Save the page and exit the Visual Builder! Click on the button, and you'll see your Popup. Congratulations!

# ⭐️ Additional details

Check out the [Plugin website](https://divimode.com/divi-popup/?utm_source=wporg&utm_medium=link&utm_campaign=popups-for-divi) for more details. You'll find:

* Examples
* CSS class options
* JS API documentation
* WP filter documentation

Tested in all major browsers on Windows and Mac: Chrome, Firefox, Safari, IE 11, Edge!

# Popups for Divi Course

During the past years, we've added a ton of features and have created a stable and powerful marketing plugin.

To celebrate the anniversary, we have created a six-day course that teaches you everything about the plugin. It walks you through the basics of creating your first Popup, shows possible ways to customize your Popup layouts and goes into advanced techniques and usages of the plugin.

> "The instruction emails really helped me to understand how to use the plugin correctly!"

The course is available in your **wp-admin Dashboard** right after you install and activate the plugin. Check out the screenshots to see the form. Also, have a look in the FAQ section, if you want to disable this feature.

# 🥳 Want more?

If you want to get the most out of Divi, you need to have a look at **[Divi Areas Pro](https://divimode.com/divi-areas-pro/?utm_source=wporg&utm_medium=link&utm_campaign=popups-for-divi)** to get additional features:

> * An **admin UI** to create and configure your popups
> * Choose between **4 Area Types**: Popup, Inline, Fly-in, Hover
> * A **beautiful UI** that blends in perfectly with Divi
> * Add **advanced triggers** to your Areas:
>  * On click
>  * On hover
>  * On scroll
>  * After delay
>  * On Exit
> * Customize the Area **Display**
>  * Show on certain pages
>  * Show on certain devices
>  * Show for certain user roles or guests
> * Customize Area **Behavior**
>  * Show/Hide the Close button
>  * Display the Area once per hour, day, week, ...
> * Flexible **position for Inline Areas**
>  * Replace/extend the page header
>  * or Footer
>  * or Comment section
>  * or actually *any* Divi section inside the page content
> * It comes with an extended version of the **JS API**
> * **Great documentation** built into the plugin and an online knowledge base
> * and much more...
>
> 👉 [Learn more about **Divi Areas Pro**](https://divimode.com/divi-areas-pro/?utm_source=wporg&utm_medium=link&utm_campaign=popups-for-divi) (with screenshots!)

== Installation ==

Install the plugin from the WordPress admin page "Plugins > Install"

or

1. Upload `popups-for-divi` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= How do I use Popups for Divi to create a popup? =

https://player.vimeo.com/video/1070058157?app_id=58479

= How much performance does Popups for Divi need? =

Actually none! We designed it with maximum page performance in mind. Our tests did show literally no change in page loading speed.

The plugin will add a single line of CSS to the page output and load two files that currently are only about 31 kb in size (9kb gzipped). Both files are cached by the browser and used on all other pages of your website.

Those two files are the JS API and the popup CSS rules that center the popup, dim the background, etc.

= Is Popups for Divi compatible with Caching plugins? =

Yes, absolutely! There is no dynamic logic in the PHP code like other popup plugins have. Popups for Divi is a little bit of PHP but the main logic sits inside the small javascript library it provides. It works on the client-side and therefore is not restricted by caching plugins.

= Is this plugin compatible with every Divi version? =

This plugin is kept compatible with the latest Divi release.

We did test it with all releases since the initial Divi 3.0 release. Possibly it will also work with older versions

= Does this plugin also work with other themes/site builders? =

Yes, actually it will! But out of the box it is optimized to work with Divi 3.0 and later. If you use any other theme or site builder then you need to change the default options of the plugin via the `evr_divi_popup-js_data` filter.

For more details visit [divimode.com/divi-popup](https://divimode.com/divi-popup/?utm_source=wporg&utm_medium=link&utm_campaign=popups-for-divi)

= Does this plugin display any ads? =

No. This plugin is free and does not display any ads. In fact, the plugin does not have a UI at all.

Popups for Divi is just that - a plain popup plugin, not our marketing strategy!

Since version 1.6.0 we now offer a six-day email course that shows you how the plugin works. You will see a notification in your wp-admin dashboard right after activating the plugin.

= Do you collect any details from my website? =

No, we do not collect or transmit any data from your website.

Since version 1.6.0 there is one exception: We now offer an email course that shows you how the plugin works. You will see a notification in your wp-admin dashboard right after activating the plugin. When you opt-in to receive the onboarding emails we will transmit the details you entered (your name and email address) to the plugin website to add you to our onboarding email-list.

= Is there a way to hide the onboarding notice? =

Yes, there is!

Since 1.6.0 the plugin offers an onboarding course that consists of 6 emails. We offer this course right after plugin activation in your wp-admin "Dashboard" page (nowhere else).

This onboarding notice is displayed to administrator users only. Once the user clicks on the "Dismiss" button, the message is never displayed again for them.

You can also globally disable the onboarding notice by defining the constant [`DISABLE_NAG_NOTICES`](https://codex.wordpress.org/Plugin_API/Action_Reference/admin_notices#Disable_Nag_Notices) in your wp-config.php or themes function.php

= I need to revert to an older version =

When you experience any problems with the plugin, I would love you to first head over to the [support forum](https://wordpress.org/support/plugin/popups-for-divi/) and briefly share your issue with me.

Here's how you can revert to an older version of the plugin:

1. Go to the [Advanced View](https://wordpress.org/plugins/popups-for-divi/advanced/) Page and scroll down to the bottom.
2. Pick your version and click "Download" (you can choose any version since 1.5.1)
3. Now go to your wp-admin Panel and open the Plugins list
4. Deactivate and Delete the Popups for Divi plugin! *Note: You will not lose any data, but while the plugin is deactivated/missing your Popups might be visible like normal page content.*
5. On the Plugins page click the "Add New" button in the top and then click on "Upload Plugin"
6. Select the .zip file which you downloaded in Step 2 and upload it. Activate and you're done!

Alternatively, you can replace the `popups-for-divi` folder via FTP: Extract the .zip file which you downloaded in Step 2 and upload it to your `/wp-content/plugins` folder.

= I have more questions or need help =

Please first visit the [**plugin website**](https://divimode.com/divi-popup/?utm_source=wporg&utm_medium=link&utm_campaign=popups-for-divi), as it includes examples and documentation that could answer your questions.

If that does not help, then head over to the [**support forum**](https://wordpress.org/support/plugin/popups-for-divi/) to see if someone else had the same problem and found a solution to it. Also, feel free to ask for help there.

You can also send us a private support request via the [**support form on divimode.com**](https://divimode.com/get-support//?utm_source=wporg&utm_medium=link&utm_campaign=popups-for-divi). Please note, that might need a while to answer your private support requests.

When you need additional features, then please have a look at our the Premium plugin [**Divi Areas Pro**](https://divimode.com/divi-areas-pro/?utm_source=wporg&utm_medium=link&utm_campaign=popups-for-divi) which comes with a lot of cool options!

== Screenshots ==

1. Step 1: Prepare your Popup inside a normal Divi Section, right on your page.
2. Step 2: Open the Section Settings, enable the "This is a Popup" flag and define a unique Popup ID.
3. Step 3: That's how the final Popup is displayed to a visitor.
4. Check out the extensive API documentation and popup samples on divimode.com
5. Our free email course walks you through every aspect of the plugin - from the basics to advanced use-cases and techniques.

== Changelog ==

= Version 3.2.7 =
- Fix: Functionality to detect Popup triggers in Divi 5

= Version 3.2.6 =
- Fix: Visual Builder notice error related to Divi 5 conversion outline

= Version 3.2.5 =
- Improve support for Divi 5
- Improve functionality to trigger Popups

= Version 3.2.4 =
- Improve functionality to detect Popup triggers
- Add Popup Settings panel in Divi 5 Section module
- Fix: Front-end JavaScript files are now included regardless of post status

= Version 3.2.3 =
- Plugin tested with WordPress 6.9

= Version 3.2.2 =
- Improve Divi 5 popup HTML render

= Version 3.2.1 =
- Improve compatibility with WP Rocket.
- Fix Popup showing before the page is loaded

= Version 3.2.0 =
- Full Support Divi 5
- Compatibility with WP Rocket.

= Version 3.1.0 =
- Add limited support for Divi 5

= Version 3.0.9 =
- Support WordPress 6.8 & Divi 4.27.4 
- Fix URL link on plugin description

= Version 3.0.8 =
- Update plugin description

= Version 3.0.7 =
- Support WordPress 6.7.2

= Version 3.0.6 =
- Support WordPress 6.6.2 & Divi 4.26.1

= Version 3.0.5 =
* Fix: Hide the onboarding notice for non-admin users.
* Change: Remove unused third-party integration with Forminator that could cause problems with Divi.

Plugin tested with WordPress 5.9.0 and Divi 4.14.7

= Version 3.0.3 =
* Improve: Compatibility with WP Rocket 3.9.
* Fix: PHP 8 compatibility.
* Fix: Address error in plugins uninstall.php script.
* Fix: Pinch-Zoom works while Popup is open on mobile devices.
* Fix: Popup contents that have "overflow: scroll" can be scrolled via the mouse wheel.

= Version 3.0.2 =
* Fix: The plugin does not trigger "Constant already defined / headers already sent" warnings anymore.

= Version 3.0.1 =
* Change: Change plugin file structure for easier maintenance.
* Change: Major improvements in the JS API - bugfixes, clean-up, improvements.
* Change: Integrate divimode admin notifications.
* Fix: Removed debugging output in JavaScript console.

= Version 2.3.4 =
* Change: Rename JS and CSS assets to comply with the naming conventions of Divi.
* Fix: Certain websites experienced a delay between triggering a Popup and the Popup becoming visible.

= Version 2.3.0 =
* Improve: Popups do not cover the Admin Toolbar anymore for logged in users.
* Change: New JS action to customize Area positioning - `position_boundary`
* Change: When a Popup is visible, a new CSS class is added to the body tag.
* Change: Rename some files and folders to comply with the naming conventions of Divi.
* Fix: Sections in Visual Builder are not randomly renamed to "Popup - #undefined" anymore.
* Fix: Exit-Intent Popups do not need a Popup ID (but it's still recommended to add one).
* Fix: More robust plugin initialization. Popups will work, even when Divi does not initialize correctly.
* Fix: Divis Theme Builder could sometimes miss Area layout settings, that's a thing of the past.
* Fix: Open the Divi Lightbox in front of Popups.
* Fix: The `background-repeat` CSS rule is now correctly applied to the background image of a Popup section.
* Fix: Fix issue with calculation of size and position, caused by certain Row settings.
* Fix: Fix a rare issue on iOS that would reload the website when a Popup was triggered.
* Fix: Rare ReCaptcha bug that happened when no site_key was present for some reason.
* Fix: Forminator ReCaptcha is supported inside Popups.
* Fix: CF7 ReCaptcha is supported inside Popups.
* Fix: Layout compatibility with wpDataTables.

= Version 2.2.5 =
* Improve: We have made the Visual Builder integration (the "Popup-Tab") faster and more stable.
* Improve: Do not include the JS API for certain 404 results, such as missing images.
* Change: New JS API function `DiviAreaItem.isPrepared()` to check if an Area is fully initialized.
* Change: New JS filter to add custom initialization code - `pre_init_area`
* Change: New JS filter to dynamically change an Areas z-index - `apply_z_index`
* Change: New JS filter to adjust Area initialization - `area_preparation_time`
* Change: The JS API function `DiviAreaItem.getData()` does not require a parameter anymore.
* Fix: The z-index is correctly applied again.
* Fix: Improve the full-height calculation of Popups

= 2.2.4 =
* Improve: Area sizes are more accurate when using Divis responsive sizes.
* Improve: Images inside Popups are instantly loaded in Chrome (fixed a lazy-load bug).
* Improve: Area size is re-calculated when the Area contents change, e.g. when an accordion is opened or closed.
* Improve: When a Popup is opened, scrolling is disabled in all browsers, without shifting the content!
* Change: New JS action that fires when an Area was resized `resize_area`.
* Change: New JS action to customize screen-position of an Area `position_area` (not available for Inline Areas).
* Fix: Full-Height Popups can be scrolled again in Safari/iPhones.

= 2.2.3 =
* Fix: Position of the close button is correct in full-height Popups.

= 2.2.2 =
* Improve: Images inside Areas are instantly loaded in Chrome (fixed a lazy-load bug).
* Improve: New logic to calculate Area size and position that supports orientation-change of mobile devices.
* Fix: WooCommerce pages now display all available Popups, not only the first one.
* Fix: Added support for IE 11.
* Fix: Popup text is no longer blurry in Windows browsers
* Change: New JS API function to identify an Area: `DiviAreaItem.hasId()`
* Change: Deprecated the "full-width" class, because it's not used anymore

= 2.2.1 =
* Fix: The close button does not trigger any scrollbars when hovered
* Fix: Popups are now always hidden when the page loads - in some cases, Popups inside Headers/Footers were visible right when the page loaded
* Fix: Click inside an open Popup does not try to re-open that Popup - i.e., fixed the "flickering issue."
* Fix: Accordions and other interactive elements inside Areas are working again
* Fix: Plugin is compatible with Gravity Forms 2.4.18+
* Fix: Bullet lists now display bullets inside Popups
* Improve: Plugin now plays nice with SG Optimizer and WP Rocket
* Improve: The close button is now outside the Popup container and can be positioned anywhere, via CSS
* Improve: Minor performance optimizations in the JS code

= 2.2.0 =
* Change: Fully refactored JS API that is documented on https://divimode.com/knowledge-base/
* Change: Some CSS class names have changed, e.g. "evr_fb_popup_modal" is now "da-overlay"
* Change: The JS configuration object changed its name to `DiviAreaConfig` (formerly `DiviPopupData`)
* Change: Split the JS API into two files - a minimal loader that is enqueued in the header, and the rest of the API which is enqueued in the footer
* Change: The DiviAreaConfig object is output in the page header so that values can be overwritten via JS in the page body
* Change: Default z-index of Popups now is "1000000" (that's one "0" more than before) to display Popups above a Full-Page menu
* New: Click triggers can be added to a Row or Section. The plugin now supports virtually any Divi "Link" field!
* New: JS class `DiviAreaItem` that represents a single Popup
* New: JS API function: `DiviArea.getArea()`
* New: JS API hooks: `area_wrap`, `area_close_button`, `refresh_area`, `init_overlay`, `reorder_areas`
* Improve: Popup content always expands downwards when a scrollbar is visible - for example when using Accordions inside a Popup
* Improve: When a popup is triggered inside a Full-Page menu, the menu is closed while the Popup is opened
* Improve: Now we support Popup triggers on any page (like shop pages or blog archive) and any element (like menus or footers)
* Improve: When a Popup is opened, the scroll bars of the page are not removed anymore
* Improve: Support for the Divi Builder Plugin is even better, cases of missing CSS styles are fixed
* Improve: Lots of comments added and typos fixed throughout the plugin
* Fix: We found and fixed a problem with the Visual Builder when inserting Popups into blog posts
* Fix: Some script debugging options were incompatible with WordPress' new block editor

Here is a full list of all API changes in this update: https://divimode.com/api-1-2-0-changes/

= 2.1.1 =
* Change JS API does not include deprecated function `observe()`! Use `addAction()` or `addFilter()` instead
* Improve the JS API further and expose additional functions
* Improve triggers on Modules: Links inside modules are treated as normal links, not as popup triggers
* Fix bugs on some WooCommerce and Divi Builder archive pages
* Fix some JS API issues for Divi Areas Pro integration

= 2.1.0 =
* Add correct Area layout on WooCommerce pages
* Add compatibility with the Divi Builder plugin
* Improve Exit-Intent logic so that multiple exit-intent popups are displayed one by one instead of all at once
* Improve JS API: The `DiviArea` object exposes additional functions
* Improve the minification of CSS files to generate ~16% smaller files
* Improve the background overlay in modern browsers (sorry, does not work in Firefox yet)
* Fix console error that said `could not load style.css.map`
* Fix the "Close other Popups" behavior, so now it will really close other popups
* Fix some minor bugs in the popup behavior

= 2.0.5 =
* Add new JS API: `DiviArea.addActionOnce()`
* Fix a bug that happened for logged in users when WordPress runs on a Windows Server

= 2.0.4 =
* Add the new option "Show Loader" to the Popup Tab to better support iframes inside the Popup
* Improve input of Popup ID in Visual Builder to prevent invalid characters
* Improve code structure for better unit testing and quality assurance
* Improve JavaScript error reporting in the dev console
* Fix some more JS errors that happened with specific versions of PHP/WordPress
* Fix display of Popups when using the Avada theme
* Fix a JS error that was caused by wrong load-order of JS libraries

= 2.0.3 =
* Improve code style: Apply the WordPress Coding Standards 2.0

= 2.0.2 =
* Improve code style: Apply the WordPress Coding Standards 2.0
* Improved security check in the onboarding form
* Improve the copy and some JS logic of the onboarding form
* Fix some typos

= 2.0.1 =
* Fix an urgent problem where the plugin would remove the contents of the page while saving the page in Divi! 😳

= 2.0.0 =
* Add a brand new Tab to the Visual Builder that allows you to configure all popup details using Divi! No more class names 🥳
* Fix JS API integration for IE 11

(Older entries are in the file changelog.txt in the plugin directory)
