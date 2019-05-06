# Shared Fields - A WordPress / Gravity Forms Plugin 

This plugin saves form field data directly into the logged in user’s meta data table on the server, and it will also pre-populate all fields of any form on any page which has a matching field name and type. 

Additionally, all saved field entries can be used to populate any field of any type which supports merge tag substitutions (like inserting saved field entry values into HTML blocks used to create a PDF).

## Contents

Full GitHub repo name: jasbits/gravity-forms-multi-form-sticky-field-sharing

Summary of files in this GitHub repo:

* `.gitignore`. Used to exclude certain files from the repository.
* `README.md`. The file that you’re currently reading.
* A `shared-fields` directory that contains the plugin's source code.

## Requirements

* Gravity Forms plugin (so far, tested with GF ver 2.4.6)
* To function, this plugin requires that the website user must be logged into a WordPress user account

## Multi-Form Sticky Field Sharing

NOTE: There are other options which do not require that the user be logged in, such as 13pixlar’s gravity-forms-sticky-form plugin. The main difference between the two plugins are:

* Entry values are accessible within the same single multi-page form via merge tags
* Entered values are accessible by other forms, even on other pages
* Entry values are saved to the user’s database table on the server
* Pages and PDFs can be populated with the saved user's entry values using merge tags

# Example Use Cases

* x

# Live Demos

Two "live" demos using this plugin are available at: [wp.www-net.com](https://wp.www-net.com)

NOTE: That WordPress site will auto-generate a short-lived "Demo User" account and log you in - no email or form-filling is required.

* Demo 1 - "Unit Test" type demo of a multi-page single form which uses all supported field types
* Demo 2 - User app "Dinner Menu Planner" to interactively construct and "publish" a final document page 

Note: These two demos allow you to see multi-page single form properly capturing and saving form input even if you abandon the form part way through, as well as multiple forms on same page. Also note you can log out and back in of your demo account, and see how your progress on other forms will be still populated with your entries. 

# Credits

This module was conceived, coded, documented, and tested by Jim Squires of Los Angeles, California, May of 2019. Dev's note: After Jim completed a working POC version of the Shared Fields code, he then discovered 13pixlar's Sticky Form plugin while researching how to package and donate the Shared Fields module to the GitHub community. 13pixlar's admin interfacing code was very helpful for adding that functionality to his Shared Fields plugin.

# Developers

Hidden feature available:  A UNIX "time in seconds" merge tag called `{time_unix}` which is active for all merge tag rendering events. It will replace the tag with a string of the current unix-time (seconds since epoc). This is typically used in a link's query string to help produce a unique URL, but might have other uses as well. Example: `http://mama.yo?date={time_unix}` 

# Future Ideas

* Admin option to manually delete all user meta records with prefix `mfsfs_SYS_` and optionally with the shared group name as well.
* Implement a means for the form designer to enable the form user to clear all records as described for Admin option above.

# Changelog
* 1.0.0-rc.1 - Initial rerelease review beta (06MAY2019)
