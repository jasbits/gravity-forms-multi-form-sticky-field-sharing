# Shared Fields - A WordPress / Gravity Forms Plugin 

This plugin saves form field data directly into the logged in user’s meta data table on the server, and it will also pre-populate all fields of any form on any page which has a matching field name and type. 

Additionally, all saved field entries can be used to populate any field of any type which supports merge tag substitutions (like inserting saved field entry values into HTML blocks used to create a PDF, constructing custom messages with captured entry data, or pre-loading a hidden field's default value to support "Conditional Logic" dynamics).

## Contents

Full GitHub repo name: jasbits/gravity-forms-multi-form-sticky-field-sharing

Summary of files in this GitHub repo:

* `.gitignore`. Used to exclude certain files from the repository.
* `README.md`. The file that you’re currently reading.
* A `shared-fields` directory that contains the plugin's source code and documentation.
* `LICENSE`. See contents for additional details (GPLv2 or later).

## Requirements

* Gravity Forms plugin (so far, tested with GF ver 2.4.6)
* To function, this plugin currently requires that the website user must be logged into a WordPress user account

## Description of "Multi-Form Sticky Field Sharing" plugin

NOTE: There are other plugins which do not require that the user be logged in, such as 13pixlar’s gravity-forms-sticky-form plugin. 

The key unique features this plugin provides are:

* Entry values are accessible within the same single multi-page form via merge tags
* Entry values are accessible by other forms, even on other pages
* Entry values are saved to the user’s database table on the server
* Pages and PDFs can be populated with the saved user's entry values by using merge tags

## Installation

1. Upload the extracted `shared-fields` folder to the `/wp-content/plugins/` directory
2. Activate the plugin via the 'Plugins' menu in WordPress as normal
3. Configure any Form's setting for which you wish to capture its entry data (see docs for options)

# Example Use Cases

* Collect form data and use it within a website's pages.
* Create dynamic webpages, emails and custom PDFs using captured form data.
* Use "Conditional Logic" dynamics with form fields based on preceding field entries.
* Construct URLs rendered in HTML blocks or emails which incorporate saved data.
* Share field answers with other fields on other pages, and re-use duplicated forms to capture and preserve multiple sets of data.
* Create "Report Generator" sites with data collection forms, with data accumulating and available within the site for the life of the user's account.

If any user invents or discovers other helpful cases, please share them with us.

# Live Demos

Two "live" demos using this plugin are available at: [wp.www-net.com](https://wp.www-net.com)

NOTE: That WordPress site will auto-generate a short-lived "Demo User" account and log you in - no email or form-filling is required.

* Demo 1 - "Unit Test" demo of a multi-page single form which uses all supported field types
* Demo 2 - User app "Dinner Menu Planner" to interactively construct and "publish" a final document page 

Note: These two demos allow you to see multi-page single forms properly capturing and saving form input even if you abandon the form part-way through, they also use multiple forms on the same page. Also note you can log in and out of your demo account, and see how your progress on all the visited forms will still be populated with your last entries. 

# Note to Developers

Three levels of debugging support per form is available (see docs).

Forms can be "grouped" with other forms or made unique via the "shared group name" form option.

Hidden feature:  A timestamp generator merge tag called `{time_unix}` is globally functional when the plugin is enabled. It will filter on all merge tag rendering events. Its action is to replace the tag with a string representation of the current Unix time (seconds since epoch). This is a useful means to provide a way of time-stamping rendered output, such as inserting into a hidden field's default value, or used in a link's query string construction for creating a "cache-busting" unique URL. Example: `http://mama.yo?date={time_unix}` 

# Future Ideas

* Admin option to manually delete all user meta records naned with the prefix `mfsfs_SYS_` (optionally limit scope to just a shared group name).
* Implement a means for the form designer to enable the form user to clear all records as described for Admin option above.
* Admin option on form settings to not require logged-in state to support saving entry data. Instead store entry data into the client browser's session cookies, rather than the user table.

# Credits

This module was conceived, coded, documented, and tested by Jim Squires of Los Angeles, California, May 2019. 

Dev's note: After Jim completed a working POC version of the Shared Fields code, he then discovered 13pixlar's Sticky Form plugin while researching how to package and donate the Shared Fields module to the GitHub community. 13pixlar's admin interfacing code was very helpful for adding that functionality to his Shared Fields plugin.

# Changelog
* 1.0.0-rc.1 - Initial rerelease review beta (07MAY2019)
