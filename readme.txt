=== Billplz for GiveWP ===
Contributors: billplz, yiedpozi, wanzulnet
Tags: give, donation, billplz, payment
Requires at least: 4.6
Tested up to: 6.7
Stable tag: 4.0.0
Requires PHP: 7.0
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Billplz payment integration for GiveWP.

== Description ==

Billplz payment integration for GiveWP.

== Installation ==

= Minimum Requirements =

* WordPress 4.7
* GiveWP 3.0

= Automatic installation =

Automatic installation is the easiest option -- WordPress will handle the file transfer, and you won’t need to leave your web browser. To do an automatic install of Billplz for GiveWP, log in to your WordPress dashboard, navigate to the Plugins menu, and click “Add New.”

In the search field type “Billplz for GiveWP,” then click “Search Plugins.” Once you’ve found us,  you can view details about it such as the point release, rating, and description. Most importantly of course, you can install it by! Click “Install Now,” and WordPress will take it from there.

= Manual installation =

Manual installation method requires downloading the Billplz for GiveWP plugin and uploading it to your web server via your favorite FTP application. The WordPress codex contains [instructions on how to do this here](https://wordpress.org/support/article/managing-plugins/#manual-plugin-installation).

== Changelog ==

= 4.0.0 - 2025-03-11 =
- Added: Support for Visual Donation Form Builder
- Added: Link the bill ID in the transaction details section
- Fixed: Disable gateway if currency is not supported
- Modified: Removed `give_billplz_label` filter hook; use GiveWP filter hook to modify the gateway checkout label: `give_gateway_checkout_label`

= 3.4.0 =
- Added: Support for Enable Extra Payment Completion Information

= 3.3.0 = 
* Added: New filter for modifying required parameter during bill creation.
* Fixed: Issue on `redirect_url`

= 3.2.1 =
- Added: Filter hook for Reference 1 and 2

= 3.2.0 =
- Fixed: Resolve issue when having multiple donation attempt at the same time
* Modified: Billplz now respect to Give Test configuration

= 3.1.0 =
- Added: Support for latest Give plugin version 2.5.x
* Fixed: Support for PHP 5.6

= 3.0.2 =
- Added: Added ability to set different Billplz account for each forms

= 1.0.0 =
- Initial plugin release
