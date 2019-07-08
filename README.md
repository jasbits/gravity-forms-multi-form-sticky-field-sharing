# Shared Fields - A WordPress / Gravity Forms Plugin 

This Gravity Forms extending plugin provides a means to capture, save, and "share" field data entry values amongst all forms from any page within the same web site. Additionally all entered field values remain "sticky" in that each field's entered values will remain pre-populated when a form is revisited - including fields sharing the same Admin Label name on other pages.

The Shared Fields plugin provides this capability by saving your form field data, and replacing all custom merge tags with the associated saved values.

All submitted form field data is saved via one of two possible ways: 

1. Directly into the logged-in user’s meta data table on the server.
2. Inserted into the anonymous user's global `$_SESSION` array.   

This capability provides a means to pre-populate any Standard or Advanced field of any type which supports merge tag substitutions (like inserting saved field entry values into HTML blocks used to create a PDF, constructing custom messages with captured entry data, or pre-loading a hidden field's default value to support "Conditional Logic" dynamics).

- Full GitHub repo name: [jasbits/gravity-forms-multi-form-sticky-field-sharing](https://github.com/jasbits/gravity-forms-multi-form-sticky-field-sharing)
- Full Documentation here: [jasbits.github.io/gravity-forms-multi-form-sticky-field-sharing](https://jasbits.github.io/gravity-forms-multi-form-sticky-field-sharing/)

## Contents of This Repository 

Summary of files in this GitHub repo:

* `README.md`. The file that you’re currently reading.
* A `shared-fields` directory that contains the plugin's source code and documentation.
* A `docs` directory for the GitHub hosted doc pages for this plugin.
* `LICENSE`. See contents for additional details (GPLv2 or later).

## Requirements

* Gravity Forms plugin (so far, tested with GF ver 2.4.6 and 2.4.9)

## Description of "Multi-Form Sticky Field Sharing" plugin

NOTE: There are other GF plugins which provide just the "sticky fields" feature (using the captured entries data from enabled form submissions) - such as 13pixlar’s gravity-forms-sticky-form plugin. 

The key unique features this "Shared Fields" plugin provides are:

* Previous page entry values are accessible for populating HTML field blocks within the same multi-page form
* Both "Previous" and "Next" button actions in a multi-page form will save that current page's new entries
* Entry values are accessible by other forms, even on other pages
* Entry values are saved to the user’s database table on the server, or alternatively to the session array
* The capability to create dynamically populated Web Pages and PDFs can be populated with the saved user's entry values by using merge tags

## Installation

1. Upload the extracted `shared-fields` folder to the `/wp-content/plugins/` directory
2. Activate the plugin via the 'Plugins' menu in WordPress as normal
3. Configure any Form's setting for which you wish to capture its entry data (see docs for options)

# Example Use Cases

* Collect form data and use it within a website's pages.
* Create dynamic webpages, emails and custom PDFs using captured form data.
* Use "Conditional Logic" dynamics with form fields based on preceding field or other pages' form entries.
* Construct URLs rendered in HTML blocks or emails which incorporate saved data.
* Share field answers with other fields on other pages, and re-use duplicated forms to capture and preserve multiple sets of data.
* Create "Report Generator" sites with data collection forms, with data accumulating and available within the site for the life of the user's account (or anonymous user's session).

Dev's note: If any user invents or discovers other helpful cases, please share them with us.

# Live Demos

Two "live" demos using this plugin are hosted at: [wp.www-net.com](https://wp.www-net.com)

* Demo 1 - "Unit Test" demo of a multi-page single form which uses all supported field types
* Demo 2 - User app "Dinner Menu Planner" to interactively construct and "publish" a final document page
* Demo 3 - Clothing Shopper With Profile (duo form sharing)

Note: These demos allow you to see multi-page single forms properly capturing and saving form input even if you abandon the form part-way through, they also use multiple forms on the same page.

# Note to Developers

Three levels of debugging support per form is available (see docs).

Hidden feature:  A timestamp generator merge tag called `{time_unix}` is globally functional when the plugin is enabled. It will filter on all merge tag rendering events. Its action is to replace the tag with a string representation of the current Unix time (seconds since epoch). This is a useful means to provide a way of time-stamping rendered output, such as inserting into a hidden field's default value, or used in a link's query string construction for creating a "cache-busting" unique URL. Example: `http://mama.yo?date={time_unix}` 

# Future Ideas

* Add merge tag support for populating field "Choices Labels" and their values (like Checkboxes and Radio Buttons)
* Admin option to manually delete all user meta records named with the back-end prefix: `mfsfs_SYS_`
* Implement a means for the form designer to enable the form user to clear all records as described for Admin option above
* Admin option to NOT store entry data into user's database table, but instead use their session array (mimicking anonymous user handling behavior)
* Capability to support storing entry data into the metadata of a custom post type 
* Add support for Post and Pricing field types
* Add way to pre-populate saved MFSFS variables with defaults

# Credits

This module was conceived, coded, documented, and tested by Jim Squires of Los Angeles, California, May 2019. 

Dev's note: After Jim completed a working POC version of the Shared Fields code, he then discovered 13pixlar's Sticky Form plugin while researching how to package and donate the Shared Fields module to the GitHub community. 13pixlar's admin interfacing code was very helpful for adding that functionality to his Shared Fields plugin.

# Changelog
* 1.0.0-rc.4 - Additions for supporting anonymous users via session array (18MAY2019)
* 1.0.0-rc.1 - Initial rerelease review beta (07MAY2019)
