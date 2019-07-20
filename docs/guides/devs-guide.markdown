---
layout: page
title:  "PHP Dev's Guide"
---

<p> &nbsp;<br /></p>

## MFSFS ("Multi-Form Sticky Field Sharing")

---

### Key Concepts for the Dev's perspective
* **The MFSFS Plugin Operates On Four Filters:**
  1. `gform_validation` Submitted entry values will be captured and saved 
  1. `gform_pre_render` All "sticky" fields will be populated with their saved values
  1. `gform_field_value` Special case for populating sticky List fields only
  1. `gform_replace_merge_tags` Replaces merge tag targets with available saved values  
<br />

* **Captured Entry Data is Saved Two Possible Ways:**
  1. Into the logged-in user's meta data table **OR**
  1. Into the anonymous user's current SESSION array

  Note: Saved entry data will associated to an array key name composed of `mfsfs_SYS_` + the Admin Label. Additionally, the key used will convert all spaces and dashes into underscores (see also: User's Guide / "Minor Note On Admin Label Naming").  
<br />

* **Typical Use Cases for the PHP Dev:**
  1. Accessing saved entry data
  1. Modifying user's saved data before or after MFSFS plugin's actions  
<br />

* **Useful PHP functions in MFSFS plugin include:**
  - `mfsfs_clear_all_meta()` - (Unfinished function for deleting saved data)
    + As of 1.0.0-rc.4 this wipes out ALL user meta data (or SESSION) values whose key is prefixed with `mfsfs_SYS_`.
  - `mfsfs_load_user_meta()` - (Acquire saved data)
    + This populates the GLOBAL $GLO_hold_user_meta array (DB's meta or SESSION array)
    Note: If the global array is already populated, it gets unset first.


  