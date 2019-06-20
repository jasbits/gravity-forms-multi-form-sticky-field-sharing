---
layout: page
title:  "Site Builder's Guide"
---

<p> &nbsp;<br /></p>

## MFSFS ("Multi-Form Sticky Field Sharing")

---

### Key Concepts
* **You can make any individual field "sticky" and "sharable" by doing these two steps:**
  1. Enable MFSFS in the form's Admin Settings ( [see image below](#eg-adm-form) )
  1. Set the "Admin Label" of a field to begin with the form's "Group Name"   
<br />
* **"Sticky" fields will auto-save entry input, and auto-populate when next displayed**
  - A form's Admin Settings must enabled to allow any of its fields to be saved and shared
  - In the form's Admin Settings you can define its "Group Name" (eg. "demo1_")
  - To make a **field** sticky, define its Admin Label to start with that form's "Group Name"  
  (eg. A Radio Field called `demo1_gender` and a Name Field called `demo1_first_name`)
  - Another form's field using the **same** Admin Name will act just like the original
  - All saved sticky field input can be used in merge tags and by custom PHP code  
<br />
* **Captured and saved field entry values can be "shared" four possible ways:**<br />
  1. pre-loading any field's default by using a custom merge tag
  1. rendering within an HTML field ( via merge tag - ( [see image below](#eg-para-field) )
  1. easily acquired within custom PHP code
  1. pre-loading original field entry was captured from (sticky)<br />
  ( note: this also includes other fields set with same Admin Label )  
<br />
* **Saved entry data is recorded two possible ways:**<br />
  1. If user is logged in, data is saved into the server's WP database
  1. Otherwise, their data is saved into their Session array<br />
( note: the plugin called "WP Session Manager" may support load-balancing requirements )  
<br />
* **An enabled field's data is captured only when the form is submitted, or when either of the Prev/Next buttons of a multi-page form are clicked**  
<br />
* **By pre-populating hidden fields with saved data using merge tags, creative display dynamics are possible, such as using a field's "Conditional Logic" rules**

<p> &nbsp;<br /></p>

<a name="eg-adm-form"></a>

---
### Form Admin Settings
* Enable a form to capture and save selected fields (Admin Setting)
* Enable debug log option to capture the plug-in's back-end processing (Optional)
* Define a form's "Group Name" prefix for selected field Admin Lables (Optional)
<br />( note: if a field's Admin Label does NOT have the defined prefix, it will not be saved )

#### Example Admin Form Settings 
&nbsp;&nbsp;![Screen cap of Admin Form Settings for MFSFS Plug-in](../assets/imgs/doc-img-mfsfs-form_admin_settings-50d.png "MFSFS Form Admin Settings")

In this example we will capture and save (and make "sticky") any field with Admin Label prefixed with "demo2_" (eg. `demo2_first_name`). So for this case, defining a merge tag for use in a field would be like: `{mfsfs:demo2_first_name}`

Note that Debugging Levels are only active when Gravity Forms plug-in settings have "Logging" enabled. 


<p> &nbsp;<br /></p>

<a name="eg-para-field"></a>

---
### Example Configuring A "Paragraph" Field for MFSFS
For this example, the field's MFSFS form settings has its "Shared Group Name" set to `demo1_`  

&nbsp;&nbsp;![Screen cap of form field - enable for MFSFS support](../assets/imgs/doc-img-mfsfs-form_eg-para-50.png "Paragraph Form Field Advance Tab")  

Then, you could display a user's text input in one or more HTML fields (for example), like this:  

&nbsp;&nbsp;![Screen cap of HTML Form Settings](../assets/imgs/doc-img-mfsfs-form_eg-html-50.png "MFSFS HTML Settings")


<p> &nbsp;<br /></p>

<a name="eg-para-field"></a>

---
### Minor Note On Admin Label Naming 
Internally, MFSFS saves and retrieves enabled field values by referencing the field's Admin Label. It requires using underscores ( `_` ) for seperators. Should you include spaces and dashes in your field's Admin Labels, MFSFS will internally convert them all to underscores. This is gennerally only interesting to someone using PHP to acquire or change a field's saved data. However, you **could** cause a collision issue if you tried to have two fields with same Group Name like `demo5_My Book` and `demo5_My-Book` - this would have MFSFS treating them both as if they were the same record.








<p> &nbsp;<br /></p>

End of doc.


<p> &nbsp;<br /></p>
