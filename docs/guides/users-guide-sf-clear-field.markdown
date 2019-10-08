---
layout: page
title:  "Site Builder's Guide - SF_Clear Field"
---

## MFSFS - Custom Field (Advanced)

---

<p> &nbsp;<br /></p>

[BACK to Main Page](users-guide.html)

### Description of the Custom SF_Clear field
The MFSFS plugin supports saving any field's user supplied entry values. There are use cases which need a means to DELETE (or "clear") any or all groups of the saved MFSFS data. This plugin supports a custom field to provide several ways to do this.

A form's data deletion will occur when users click a form's SUBMIT button, at which time all SF_Clear fields of that form will be processed. 

The SF_Clear fields allow the user to select options, or they may be pre-set and hidden from view. The option of "hiding" an SF_Form field will require you to have also enabled the "Default Condition" as checked, else it will be effectively ignored. 

The total number of records cleared will be saved to a special merge tag which you can use in places like the form's Confirmation Message output ( [see example below](#eg-conf-msg) ).

All of the custom field's options are demonstrated in the Live Demo 4, which targets the saved data captured in Demos 1-3. Demo 4 displays three forms with several SF_Clear fields all on one Page (see link below). 

[MFSFS Live Demos](https://wp.www-net.com)<br />

<p> &nbsp;<br /></p>

---
### Supported Features
* **The "SF_Clear" advanced custom field provides these capabilities:**
  1. Clear saved data of all or just selected groups
  1. Choice of One-Click or Confirm and Click operation
  1. Multiple "SF_Clear" fields in one form with selective confirmation
  1. The number of deleted records is available in special merge tag
<br />

<p> &nbsp;<br /></p>
[TOP](#)

---
### Form's Admin Settings
Any form you wish to include one or more SF_Clear fields MUST be enabled for MFSFS and MUST have a group name (see [Main Page](users-guide.html) for details on enabling MFSFS in Form Settings).
<a name="eg-conf-msg"></a>
<p> &nbsp;<br /></p>

#### Special Merge Tag (total number of records cleared)
A special merge tag value is available for use in the form's **Confirmation message** or for other purposes. You have the option to display or use it via this special merge tag: `mfsfs_SPECIAL_clear_count`. 

This example HTML is used for the confirmation settings messages by all three forms of Live Demo 4 page: 

    <font color="green">SF_Clear returned delete record count of: {mfsfs:mfsfs_SPECIAL_clear_count} at time of: {time_unix}</font> 

For additional details on using the "time_unix" merge tag, see "Note to Developers" in the About page.

<p> &nbsp;<br /></p>
[TOP](#)

---
### Example Configuring A Form's Submit Button
For this example, the form's Submit Button is set to be hidden if ALL three SF_Clear fields are unchecked. This is not required of course, but it's a nice option.

&nbsp;&nbsp;![Screen cap of "Form Button" settings demonstrating use of Conditional Logic](../assets/imgs/doc-img-mfsfs-form4a-Form-Button-setting-50.png "Form Button settings")  

<p> &nbsp;<br /></p>
[TOP](#)

---
### Example Field Configuration (General tab)
&nbsp;&nbsp;![Screen cap of Demo 4a Field 1](../assets/imgs/doc-img-mfsfs-form4a-field1-gen-50.png "MFSFS Field 1 of Demo 4")

In this example we are configuring the SF_Clear field to ONLY target deleting saved data of fields grouped with Admin Label prefixed with "demo1_".

Note that the option for "hidden" is found under the Advanced tab (not shown here).

The top form in Live Demo 4 contains this and two more SF_Clear fields, which target groups demo2_ and demo3_ of the other demos.

<p> &nbsp;<br /></p>

[TOP](#)

[BACK to Main Page](users-guide.html)

<p> &nbsp;<br /></p>
