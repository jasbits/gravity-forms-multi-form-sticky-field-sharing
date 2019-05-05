# Shared Fields - A WordPress / Gravity Forms Plugin 

This plugin saves form field data directly to the logged in user’s meta data table, and pre-populates all fields of any form on any page which has a matching field name and type. 

Additionally, all saved field entries can be used to populate any field of any type which supports merge tag substitutions (like inserting saved field entry values into HTML blocks used to create a PDF).

## Contents

Full repo name: jasbits/gravity-forms-multi-form-sticky-field-sharing

Summary of files in this GitHub repo:

* `.gitignore`. Used to exclude certain files from the repository.
* `README.md`. The file that you’re currently reading.
* A `shared-fields` directory that contains the source code - a fully executable WordPress plugin.

## Requirements

* Gravity Forms plugin (tested with 2.4.6)
* The webpage form user must be logged into a WordPress user account

## Multi-Form Sticky Field Sharing

There are other options which do not require a logged in user, such as 13pixlar’s gravity-forms-sticky-form plugin, the main difference with this Shared Fields plugin is the following:

* Entry values are accessible within the same single multi-page form via merge tags
* Values are also accessible by other forms, even on other pages
* Entry values are saved to the user’s database table on the server
* Pages and PDFs can be populated with saved entry values with merge tags

# Example Use Cases

* x

# Live Demos

Two "live" demos using this plugin are available at: [wp.www-net.com](https://wp.www-net.com)

This WordPress site will auto-generate a short-lived "Demo User" account and log you in - no email or form-filling is required.

* Demo 1 - Unit Test type demo that uses all support field types, and some of the features
* Demo 2 - User app "Dinner Menu Planner" to interactively construct and "publish" a final document 

Note that these two demos allow you to see multi-page single form properly capturing and saving form input even if you abandon the form part way through. You can log out and back in and your progress will be still there where you left off. 

# Credits

This module was conceived, coded, documented, and tested by Jim Squires of Los Angeles, California in 2019. Dev's note: After he completed a working POC version of Shared Fields, Jim discovered 13pixlar's Sticky Form plugin while researching how to donate the Shared Fields module to the GitHub community. 13pixlar's admin interfacing code was very helpful for adding the same to the Shared Fields code.

## Future Ideas

* x

# Changelog
* 1.0.0-rc.1 - Initial rerelease review beta (05MAY2019)
