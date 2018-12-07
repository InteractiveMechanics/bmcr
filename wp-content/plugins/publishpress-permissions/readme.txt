=== PublishPress Permissions ===
Contributors: publishpress, andergmartins, stevejburge, pressshack
Author: PublishPress, PressShack
Author URI: https://publishpress.com
Tags: publishpress, permissions
Requires at least: 4.6
Requires PHP: 5.4
Tested up to: 4.9.3
Stable tag: 2.2.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Extend PublishPress implementing permissions control.

== Description ==
Extend PublishPress implementing permissions control.

== Installation ==
There're two ways to install PublishPress plugin:

**Through your WordPress site's admin**

1. Go to your site's admin page;
2. Access the "Plugins" page;
3. Click on the "Add New" button;
4. Search for "PublishPress Permissions";
5. Install PublishPress Permissions plugin;
6. Activate the PublishPress Permissions plugin.

**Manually uploading the plugin to your repository**

1. Download the PublishPress Permissions plugin zip file;
2. Upload the plugin to your site's repository under the *"/wp-content/plugins/"* directory;
3. Go to your site's admin page;
4. Access the "Plugins" page;
5. Activate the PublishPress Permissions plugin.

== Usage ==
- Make sure you have PublishPress plugin installed and active;
- Go to PublishPress Settings page, click on "Permissions" tab and customize its options at will;
- That's it.

== Changelog ==

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

= [2.2.0] - 2018-11-08 =

* Fixed PHP warning when the Permissions module is not loaded;
* Added new permission: Select notification channel in the profile page;
* Added new permission: Access the Dashboard, the admin bar, and the “Your Profile” page;
* Added new permission: Change the role of other users;

= [2.1.0] - 2018-09-19 =

*Fixed:*

* Updated the POT file;

*Added:*

* Added capability field for "upload_files" capability in posts;

= [2.0.4] - 2018-07-02 =

*Fixed:*

* Fixed detection of upgrades to correctly update data;
* Fixed installation to grant all PublishPress permissions to admins by default;
* Fixed typo in the method convertUserGroupPermisssionsToRoleCapabilities, refactoring to convertUserGroupPermissionsToRoleCapabilities;
* Fixed installation and data upgrade methods, fixing the permission to display the submenu;

*Changed:*

* Added redirection to the PublishPress Calendar after upgrading data;

= [2.0.3] - 2018-06-27 =

*Fixed:*

* Fixed hardcoded capability names in the permission module, fixing the submenu;

= [2.0.2] - 2018-06-26 =

*Fixed:*

* Fixed the capability for managing permissions. This should be fixed on 2.0.1 but the code was reverted in a mysterious way;

= [2.0.1] - 2018-06-12 =

*Fixed:*

* Fixed capability for managing permissions. It was set for pp_manage_roles instead of pp_manage_capabilities;
* Fixed list of permissions adding Manage Roles and fixing the capability related to the Manage Permissions field. They were repeated;
* Fixed the menu hook for adding compatibility with PublishPress >= 1.14.0;

= [2.0.0] - 2018-04-05 =

*Fixed:*

* Fixed issue when installed from composer, related to the vendor dir not being found;
* Updated the wordpress-edd-license-integration library to 2.2;
* Fixed broken HTML on messages displayed if PublishPress is not enabled;

*Changed:*

* Changed minimum required PublishPress version to 1.11.4;
* Fix PHP Warning about undefined warnings in fresh install if no license key was set or the settings were not saved yet;
* Removed support for User Groups - they are deprecated in favor of Roles, in PublishPress;

*Added:*

* Added a more detailed control over capabilities related to publishing;
* Added new submenu for Permissions;

= [1.0.4] - 2018-02-21 =

*Fixed:*

* Fixed permission for publishing other's posts;

*Changed:*

* Updated some terms for PublishPress brand;

= [1.0.3] - 2018-02-07 =

*Fixed:*

* Fixed license key validation and automatic update;

= [1.0.2] - 2018-01-26 =

*Fixed:*

* Fixed duplicated columns for the "Publish" status in the permissions matrix;
* Fixed permissions to edit and publish posts;

*Changed:*

* Rebranded for PublishPress;

= [1.0.1] - 2017-08-04 =

* Fixed:
* Fixed PHP Warning in the front-end about undefined index;

* Changed:
* Removed Freemius integration;

= [1.0.0] - 2017-06-21 =

* First release
