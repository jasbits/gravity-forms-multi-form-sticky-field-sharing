<?php

/**
 * @link https://wp.www-net.com
 * @since 1.0.0
 * @package Gravity_Forms_Multi_Form_Sticky_Field_Sharing
 * 
 * @wordpress-plugin
 * Plugin Name:       Gravity Forms Multi Form Sticky Field Sharing
 * Plugin URI:        https://github.com/jasbits/gravity-forms-multi-form-sticky-field-sharing
 * Description:       This is a <a href="http://www.gravityforms.com/" target="_blank">Gravity Form</a> plugin that enables form fields to be both "shared" and "sticky." By "shared" this means entry field data submitted from another form can be used as default input values or inserted via merge tags on other pages (see documentation and demos).
 * Author:            Jim Squires of Active Web Networks, LLC
 * Version:           1.0.0-rc.5
 * Author URI:        https://www-net.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * 
 * WordPress plugin naming and adaption inspired by Adam Rehal's terrific "sticky-form" plugin
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
	die();
}

/**
 * Current plugin version:
 * ( Ref: Semantic Versioning 2.0.0 - aka "SemVer" see: https://semver.org )
 */
define('GRAVITY_FORMS_SHARED_FIELDS', '1.0.0-rc.5'); // Updated by -JASCoder 08OCT2019 - Add field clear sticky

define('MFSFS_ID', 'mfsfs_SYS_'); // This is a system level prefix used to reference database user meta values
define('MFSFS_SPECIAL_CLEARED_COUNT', 'mfsfs_SPECIAL_clear_count'); // Used for preserving SF_Clear action metric
define('MFSFS_FLAG', 'form_shared_fields_flag');
define('MFSFS_DEBUG', 'form_shared_fields_debug'); // Default is 0, with 1=terse, 2=verbose, 3=noisy
define('MFSFS_PREFIX', 'form_shared_fields_prefix');
define('MFSFS_DEFAULT_PREFIX', 'sf1_'); // Default for prefix defining shared groups

define('MFSFS_SPECIAL_TIME_UNIX', '{time_unix}'); // Supported at all times merge tag filter rendering
define('MFSFS_SPECIAL_CLEAR_DATA', 'special_mfsfs_clear_form'); // deprecated TODO - remove

define('MFSFS_FIELD_TYPE_SF_CLEAR', 'sf_clear');
define('MFSFS_FIELD_TYPE_PHONE', 'phone');
define('MFSFS_FIELD_TYPE_RADIO', 'radio');
define('MFSFS_FIELD_TYPE_CHECKBOX', 'checkbox');
define('MFSFS_FIELD_TYPE_MULTI_SELECT', 'multiselect');
define('MFSFS_FIELD_TYPE_TEXT_LINE', 'text');
define('MFSFS_FIELD_TYPE_TEXT_AREA', 'textarea');
define('MFSFS_FIELD_TYPE_EMAIL_ADDRESS', 'email');
define('MFSFS_FIELD_TYPE_DROP_DOWN', 'select');
define('MFSFS_FIELD_TYPE_NUMBER', 'number');
define('MFSFS_FIELD_TYPE_NAME', 'name');
define('MFSFS_FIELD_TYPE_DATE', 'date');
define('MFSFS_FIELD_TYPE_ADDRESS', 'address');
define('MFSFS_FIELD_TYPE_TIME', 'time');
define('MFSFS_FIELD_TYPE_LIST', 'list');
define('MFSFS_FIELD_TYPE_HTML', 'html');

// Start session for non logged in user support
add_action('init', 'start_session', 1);

// Add the (Advanced) new field "clear-sticky" for purging saved data
add_action('gform_loaded', array(
	'GF_SF_Add_Field_Clear_Sticky_Bootstrap',
	'load'
), 5);

// Admin filters
add_filter('gform_form_settings', 'mfsfs_adm_settings', 80, 2);
add_filter('gform_pre_form_settings_save', 'mfsfs_adm_settings_save');
add_filter('gform_tooltips', 'sf_clr_add_sf_clear_tooltips');

// Application filters
add_filter('gform_validation', 'mfsfs_save_fields');
add_filter('gform_pre_render', 'mfsfs_load_fields', 20, 1);
add_filter('gform_field_value', 'mfsfs_populate_fields', 10, 3); // (used only for the Advanced "List" field)
add_filter('gform_replace_merge_tags', 'mfsfs_adjust_merge_tags', 10, 7); // (includes special case "time_unix")
                                                                          // add_filter('gform_confirmation', 'mfsfs_adjust_confirmation', 10, 4); // (handles merge tags filtering if needed)

// Added settings for new sf_clear field
add_action('gform_field_standard_settings', 'sf_clr_standard_settings', 10, 2);
add_action('gform_editor_js', 'sf_clr_editor_script');

$GLO_field_id_refs = array(); // Used to retain reverse-reference index list of field IDs during "save" process
$GLO_list_value_arrays = array(); // Used to retain list value arrays acquired in pre_render handling
$GLO_hold_user_meta = array(); // Used to save repeating fetches while iterating thru an array
$GLO_form_debug_setting = 0;

/*
 * TODO - relocate this bootstrap to proper place
 *
 */
class GF_SF_Add_Field_Clear_Sticky_Bootstrap
{

	public static function load()
	{
		if (! method_exists('GFForms', 'include_addon_framework')) {
			return;
		}

		require_once ('class-sfaddfieldclearsticky.php');

		GFAddOn::register('SFClearStickyFieldAddOn');
	}
}

/*
 * Swap out all "mfsfs:" flagged merge tags with availible user meta values
 * Note: Also swap special merge tag "time_unix" (typically used in URL query stings to defeat cache)
 *
 * Available args: ($text, $form, $entry, $url_encode, $esc_html, $nl2br, $format)
 */
function mfsfs_adjust_merge_tags($text, $form)
{
	GLOBAL $GLO_hold_user_meta;
	GLOBAL $GLO_form_debug_setting;

	$trg_flag = '{mfsfs:'; // This is what we're watching for... (plus the special_tag case)
	$special_tag = MFSFS_SPECIAL_TIME_UNIX; // (flags action to replace with current unix time numeric string)

	if (FALSE !== strpos($text, $special_tag)) {
		$text = str_replace($special_tag, time(), $text);

		if ($GLO_form_debug_setting > 1) {
			GFCommon::log_debug("mfsfs_adjust_merge_tags(2): SPECIAL_TIME reset to: ($text)");
		}
	}

	if (count($GLO_hold_user_meta) < 1) { // (Does the global array need loading ?)
		$user_got_data = mfsfs_load_user_meta();
	} else {
		$user_got_data = TRUE;
	}

	if (FALSE === $user_got_data) {
		return $text;
	}

	if (FALSE !== strpos($text, $trg_flag)) {
		$out = array();
		$res = preg_match_all("|$trg_flag(.*)}|U", $text, $out, PREG_PATTERN_ORDER);

		if ($res === FALSE || $res < 1 || empty($out[1])) {
			return $text;
		}

		$tag_list = $out[1];

		foreach ($tag_list as $meta_index) {
			if ($meta_index != MFSFS_SPECIAL_CLEARED_COUNT) {
				$fixed_meta_index = MFSFS_ID . mfsfs_convert_str_to_wp_fmt($meta_index);
			} else {
				$fixed_meta_index = MFSFS_SPECIAL_CLEARED_COUNT;

				if (! isset($GLO_hold_user_meta[$fixed_meta_index])) {
					$GLO_hold_user_meta[$fixed_meta_index] = '0'; // forces str replace below
				}
			}

			if (isset($GLO_hold_user_meta[$fixed_meta_index])) {
				$new = $GLO_hold_user_meta[$fixed_meta_index];
				$text = str_replace("$trg_flag$meta_index}", $new, $text);
			} else {
				$new = 'No new value found';
			}

			if ($GLO_form_debug_setting > 1) {
				GFCommon::log_debug("mfsfs_adjust_merge_tags(2): swapped out: ($fixed_meta_index) to now have: ($new) text now is: ($text)");
			}
		}

		// TODO: Find out how to config a form field to set this arg to TRUE : if ($nl2br) {}
		// (for now, we'll just assume this only applies to field type HTML)
		if (! empty($form->type) && $form->type == MFSFS_FIELD_TYPE_HTML) {
			$text = nl2br($text); // ( insert a "<br />" tag before all EOL chars )
		}
	}

	return $text;
}

/**
 * Clear Saved Data
 *
 * @return integer The count of records cleared.
 */
function mfsfs_clear_saved_data($user_id, $form_debug, $purge_action, $trg_group)
{
	$rm_count = 0;

	if ($purge_action == 'all') {
		$trg_pattern = MFSFS_ID;
	} else if ($purge_action == 'group' && ! empty($trg_group)) {
		$trg_pattern = MFSFS_ID . $trg_group;
	} else {
		if ($form_debug > 0) {
			GFCommon::log_debug("mfsfs_clear_saved_data(1) NO-OP - returning (unsuported Clear Data Action or missing target Group Name).");
		}
		return $rm_count;
	}

	if ($form_debug > 0) {
		GFCommon::log_debug("mfsfs_clear_saved_data(1) envoked for user: $user_id - delete action: $purge_action ( target group = $trg_group ) target pattern: $trg_pattern");
	}

	if ($user_id > 0) {
		$all_meta_for_user = array_map(function ($a) {
			return $a[0];
		}, get_user_meta($user_id));

		foreach ($all_meta_for_user as $key => $val) {
			if (strpos($key, $trg_pattern) === 0) {
				if ($form_debug > 1) {
					GFCommon::log_debug("mfsfs_clear_saved_data(2-user) cleared [$key] holding [$val]");
				}

				delete_user_meta($user_id, $key);

				$rm_count ++;
			}
		}
	} else {
		foreach ($_SESSION as $key => $val) {
			if (strpos($key, $trg_pattern) === 0) {
				if ($form_debug > 1) {
					GFCommon::log_debug("mfsfs_clear_saved_data(2-anon) cleared [$key] holding [$val]");
				}

				unset($GLOBALS[_SESSION][$key]);

				$rm_count ++;
			}
		}
	}

	return $rm_count;
}

/*
 * Clear out all meta data
 * TODO deprecate this - ( see mfsfs_clear_saved_data() )
 */
function mfsfs_clear_all_meta($user_id, $form_debug, $form_group, $trg_field)
{
	$cur_type = $trg_field['type'];

	if ($form_debug > 0) {
		GFCommon::log_debug("mfsfs_clear_all_meta() envoked for group = $form_group and type = $cur_type and usr = $user_id");
	}

	if ($user_id > 0) {
		$all_meta_for_user = array_map(function ($a) {
			return $a[0];
		}, get_user_meta($user_id));

		foreach ($all_meta_for_user as $key => $val) {
			if (strpos($key, MFSFS_ID) === 0) {
				GFCommon::log_debug("mfsfs_clear_all_meta(user) cleared [$key] holding [$val]");

				delete_user_meta($user_id, $key);
			}
		}
	} else {
		foreach ($_SESSION as $key => $val) {
			if (strpos($key, MFSFS_ID) === 0) {
				GFCommon::log_debug("mfsfs_clear_all_meta(anon) cleared [$key] holding [$val]");

				unset($GLOBALS[_SESSION][$key]);
			}
		}
	}
}

/*
 * Load into global the user meta data if logged in, else load client's SESSION data
 * Return FALSE (if no data found) else TRUE
 */
function mfsfs_load_user_meta()
{
	GLOBAL $GLO_hold_user_meta;

	$current_user = wp_get_current_user();
	$trg_prefix = MFSFS_ID;

	if (! ($current_user instanceof WP_User) || 0 == $current_user->ID) {

		if (empty($_SESSION)) {
			return FALSE;
		}

		$GLO_hold_user_meta = array_filter($_SESSION, function ($a) use ($trg_prefix) {
			return strpos($a, $trg_prefix) === 0 ? TRUE : FALSE;
		}, ARRAY_FILTER_USE_KEY);

		$GLO_hold_user_meta[MFSFS_SPECIAL_CLEARED_COUNT] = isset($_SESSION[MFSFS_SPECIAL_CLEARED_COUNT]) ? $_SESSION[MFSFS_SPECIAL_CLEARED_COUNT] : '0';
	} else {
		$GLO_hold_user_meta = array_map(function ($a) {
			return $a[0];
		}, get_user_meta($current_user->ID));
	}

	return TRUE;
}

/*
 * Used by populate_fields set by load_fields (only used for tagged List fields)
 */
function mfsfs_populate_fields($value, $field, $form_param_name)
{
	GLOBAL $GLO_list_value_arrays;
	GLOBAL $GLO_form_debug_setting;

	if ($GLO_form_debug_setting > 2) {
		$tmpX = print_r($value, TRUE);
		GFCommon::log_debug("mfsfs_populate_fields(3): arg 'value' is =($tmpX)");

		$tmpX = print_r($field, TRUE);
		GFCommon::log_debug("mfsfs_populate_fields(3): arg 'field' is =($tmpX)");

		GFCommon::log_debug("mfsfs_populate_fields(3): arg 'name' is =($form_param_name)");
	}

	if (! empty($form_param_name) && isset($GLO_list_value_arrays[$form_param_name])) {
		if ($GLO_form_debug_setting > 1) {
			$tmpX = print_r($GLO_list_value_arrays[$form_param_name], TRUE);
			GFCommon::log_debug("mfsfs_populate_fields(2): SET ($form_param_name) to=($tmpX)");
		}

		return $GLO_list_value_arrays[$form_param_name];
	}

	return $value;
}

function mfsfs_load_fields($form)
{
	GLOBAL $GLO_list_value_arrays;
	GLOBAL $GLO_form_debug_setting;

	$form_title = $form['title'];
	$LOC_debug = isset($form[MFSFS_DEBUG]) ? $form[MFSFS_DEBUG] : 0;
	$GLO_form_debug_setting = $LOC_debug;

	if (! isset($form[MFSFS_FLAG]) || ! $form[MFSFS_FLAG]) {
		if ($LOC_debug > 0) {
			GFCommon::log_debug('mfsfs_load_fields(1)' . " NOT MFSFS enabled form, title = ($form_title)");
		}
		return $form;
	}

	$current_user = wp_get_current_user();

	if (! ($current_user instanceof WP_User) || 0 == $current_user->ID) {
		GFCommon::log_debug('mfsfs_load_fields(1): No user is logged in - will use SESSION array.');

		$LOC_UserId = 0; // (anonymous user)
	} else {
		$LOC_UserId = $current_user->ID;
	}

	$current_page = rgpost('gform_source_page_number_' . $form['id']) ? rgpost('gform_source_page_number_' . $form['id']) : 1;

	if ($LOC_debug > 0) {
		GFCommon::log_debug("mfsfs_load_fields(1): ENTER gform_pre_render for form title = $form_title and User ID = $LOC_UserId and page = $current_page");
	}

	$mfsfs_prefix = MFSFS_ID;

	if ($LOC_UserId === 0) {

		if ($LOC_debug > 2) {
			$tmpSess = print_r($_SESSION, TRUE);
			GFCommon::log_debug('mfsfs_load_fields(3)' . " Target PreFix=$mfsfs_prefix and _SESSION array has: [$tmpSess]");
		}

		$all_meta_for_user = array_filter($_SESSION, function ($a) use ($mfsfs_prefix) {
			return strpos($a, $mfsfs_prefix) === 0 ? TRUE : FALSE;
		}, ARRAY_FILTER_USE_KEY);
	} else {
		$all_meta_for_user = array_map(function ($a) {
			return $a[0];
		}, get_user_meta($LOC_UserId));
	}

	if ($LOC_debug > 2 && $LOC_UserId != 0) {
		$tmpX = print_r($all_meta_for_user, TRUE);
		$tmpName = $all_meta_for_user['first_name'];
		GFCommon::log_debug("mfsfs_load_fields(3): user ID=($LOC_UserId) name=($tmpName) / meta=($tmpX)");
	}

	foreach ($form['fields'] as &$field) {
		$fieldType = $field->type;
		$fieldChoices = $field->choices;
		$fieldOther = $field->enableOtherChoice; // Field permits "Other" option with attendant value
		$fieldInputName = $field->inputName; // (used by TYPE_LIST handling)
		$admLabel = $field->adminLabel;
		$tmpLab = MFSFS_ID . mfsfs_convert_str_to_wp_fmt($admLabel);

		/*
		 * TODO - Address this item, it fails because cur-page here is the same page number as was handled by
		 * save-page function (ie rgpost() returns the same page whcih the "next/prev/submit" button was clicked).
		 * So it needs to be properly derived...
		 *
		 * if ($current_page != $field->pageNumber) {
		 * if ($LOC_debug > 1) {
		 * GFCommon::log_debug("mfsfs_load_fields(2): PreSwitch: (at Page=$current_page) form-field $admLabel is not for this page: " . $field->pageNumber);
		 * }
		 *
		 * continue;
		 * }
		 */

		if (isset($all_meta_for_user[$tmpLab]))
			$tmpVal = $all_meta_for_user[$tmpLab];
		else
			continue;

		if ($LOC_debug > 0) {
			GFCommon::log_debug("mfsfs_load_fields(1): PreSwitch: form-fields pre-rend-admLabel-HIT: " . "form-adm-label=$admLabel type=$fieldType user-key=$tmpLab user-meta val=$tmpVal");
		}

		if ($LOC_debug > 2) {
			$tmpX = print_r($field, TRUE);
			GFCommon::log_debug("mfsfs_load_fields(3): entire field object to update pre-render: $tmpX");
		}

		switch ($fieldType) {

			case MFSFS_FIELD_TYPE_LIST:
				if ($LOC_debug > 0) {
					GFCommon::log_debug("mfsfs_load_fields(1): Type = $fieldType Label = $tmpLab - get_fld_indx_from_entry($tmpVal) admLab: $admLabel");
				}

				if (! empty($tmpVal)) {
					$tmpUnSerialized = unserialize($tmpVal);

					if (FALSE !== $tmpUnSerialized && ! empty($tmpUnSerialized)) {
						$GLO_list_value_arrays[$fieldInputName] = $tmpUnSerialized;
					}

					if ($LOC_debug > 1) {
						$tmpInputs = print_r($tmpUnSerialized, TRUE);
						GFCommon::log_debug("mfsfs_load_fields(2): case list all set to: $tmpInputs");
					}
				}
				break;

			case MFSFS_FIELD_TYPE_TEXT_AREA:
			case MFSFS_FIELD_TYPE_TEXT_LINE:
			case MFSFS_FIELD_TYPE_EMAIL_ADDRESS:
			case MFSFS_FIELD_TYPE_DATE:
			case MFSFS_FIELD_TYPE_PHONE:
			case MFSFS_FIELD_TYPE_NUMBER:
				if ($LOC_debug > 0) {
					GFCommon::log_debug("mfsfs_load_fields(1): Type = $fieldType Label = $tmpLab - get_fld_indx_from_entry($tmpVal) admLab: $admLabel");
				}

				if (! empty($tmpVal)) {
					$field->defaultValue = $tmpVal;
					if ($LOC_debug > 1) {
						GFCommon::log_debug("mfsfs_load_fields(2): field set to: $tmpVal");
					}
				}
				break;

			case MFSFS_FIELD_TYPE_RADIO:
				$trgChoiceIndx = mfsfs_get_fld_indx_from_entry($tmpVal, $fieldChoices); // "false" or index of choice to set ON
				if ($LOC_debug > 0) {
					GFCommon::log_debug("mfsfs_load_fields(1): SWITCH-ITEM-RADIO: [$tmpLab] get_fld_indx_from_entry($tmpVal) returns: $trgChoiceIndx");
				}

				if (false === $trgChoiceIndx && $fieldOther == 1) // (user selected Other option)
				{
					$field->defaultValue = $tmpVal;
					if ($LOC_debug > 0) {
						GFCommon::log_debug("mfsfs_load_fields(1): (cannot perform field value save) " . "get_fld_indx_from_entry($tmpVal) returned false - fieldOther = $fieldOther");
					}
				} else if (false !== $trgChoiceIndx) {
					$field->choices[$trgChoiceIndx]['isSelected'] = 'yes';
				} else {
					if ($LOC_debug > 1) {
						GFCommon::log_debug("mfsfs_load_fields(2): unknown field entry: get_fld_indx_from_entry($tmpVal) returned false");
					}
				}
				break;

			case MFSFS_FIELD_TYPE_MULTI_SELECT:
				$trgChoiceIndx = mfsfs_get_fld_indx_from_entry($tmpVal, $fieldChoices); // "false" or index of choice to set ON
				if ($LOC_debug > 0) {
					GFCommon::log_debug("mfsfs_load_fields(1): SWITCH-ITEM-MULTI_SET: [$tmpLab] get_fld_indx_from_entry($tmpVal) returns: $trgChoiceIndx");
				}

				if (false === $trgChoiceIndx) {
					$field->defaultValue = $tmpVal;
					if ($LOC_debug > 0) {
						GFCommon::log_debug("mfsfs_load_fields(1): (cannot perform field value save) " . "get_fld_indx_from_entry($tmpVal) unknown field entry: fieldOther = $fieldOther");
					}
				} else if (false !== $trgChoiceIndx) {
					$field->choices[$trgChoiceIndx]['isSelected'] = 'yes';
				} else {
					if ($LOC_debug > 0) {
						GFCommon::log_debug("mfsfs_load_fields(1): unknown field entry: get_fld_indx_from_entry($tmpVal) returned false");
					}
				}
				break;

			case MFSFS_FIELD_TYPE_DROP_DOWN: // (select)
				foreach ($field->choices as &$choAry) {
					if (! empty($choAry['value']) && $choAry['value'] == $tmpVal) {
						$choAry['isSelected'] = 'yes';

						if ($LOC_debug > 0) {
							GFCommon::log_debug("mfsfs_load_fields(1): case $fieldType - choosen item: " . $choAry['text'] . " for value: $tmpVal");
						}
						break;
					}
				}
				break;

			case MFSFS_FIELD_TYPE_CHECKBOX:
				$tmpUnSerialized = unserialize($tmpVal); // For this case it's an Object
				$tmpChos = print_r($tmpUnSerialized, TRUE);

				foreach ($field->choices as &$choAry) {
					if (! empty($choAry['value']) && isset($tmpUnSerialized[$choAry['value']]) && $tmpUnSerialized[$choAry['value']] == 1) {
						$choAry['isSelected'] = 'yes';
					}

					if ($LOC_debug > 0) {
						GFCommon::log_debug("mfsfs_load_fields(1): case $fieldType - choosen item: " . $choAry['text']);
					}
				}

				if ($LOC_debug > 0) {
					GFCommon::log_debug("mfsfs_load_fields(1): [$tmpLab] choices from user tbl: $tmpChos");
				}
				break;

			case MFSFS_FIELD_TYPE_TIME:
				if (! empty($tmpVal)) {
					$field->inputs[0]['defaultValue'] = $tmpVal[0] . $tmpVal[1]; // HOUR
					$field->inputs[1]['defaultValue'] = $tmpVal[3] . $tmpVal[4]; // MINUTE

					if ($field->timeFormat == '12') {
						$field->inputs[2]['defaultValue'] = $tmpVal[6] . $tmpVal[7]; // AM/PM
					}

					if ($LOC_debug > 1) {
						GFCommon::log_debug("mfsfs_load_fields(2): case $fieldType - set to $tmpVal inputs from user tbl: $tmpInputs time format = " . $field->timeFormat);
					}
				} else {
					if ($LOC_debug > 1) {
						GFCommon::log_debug("mfsfs_load_fields(2): case $fieldType - NO inputs found for field: time ");
					}
				}
				break;

			case MFSFS_FIELD_TYPE_ADDRESS:
				$tmpUnSerialized = unserialize($tmpVal);
				$tmpInputs = print_r($tmpUnSerialized, TRUE);

				$i = 0;

				foreach ($field->inputs as &$inpAry) {
					if (! empty($tmpUnSerialized[$i])) {
						$inpAry['defaultValue'] = $tmpUnSerialized[$i]; // NOTE: This index may not already be isset()

						if ($LOC_debug > 1) {
							GFCommon::log_debug("mfsfs_load_fields(2): case $fieldType - item($i): " . $inpAry['label'] . " set to: " . $tmpUnSerialized[$i]);
						}
					}

					$i ++;
				}

				if ($LOC_debug > 1) {
					GFCommon::log_debug("mfsfs_load_fields(2): [$tmpLab] inputs from user tbl: $tmpInputs");
				}
				break;

			/*
			 * (Special handling for Advanced Name field...
			 * Five supported items: id.2=Prefix, id.3=First, id.4=Mid, id.6=Last, id.8=Suffix
			 * Key / Val is [id]=value - special case for "Prefix" has a choices array using isSelected
			 */
			case MFSFS_FIELD_TYPE_NAME:
				$tmpUnSerialized = unserialize($tmpVal);
				$tmpInputs = print_r($tmpUnSerialized, TRUE);

				foreach ($field->inputs as &$inpAry) { // (iterate thru sub-field array)
					if ($inpAry['label'] == 'Prefix') {
						foreach ($inpAry['choices'] as &$inpCho) { // (iterate thru prefix array)
							if ($inpCho['text'] == $tmpUnSerialized[$inpAry['id']]) {
								if ($LOC_debug > 1) {
									GFCommon::log_debug("mfsfs_load_fields(2): special 'Prefix' case match found: " . $inpCho['text']);
								}
								$inpCho['isSelected'] = 'yes';
								break;
							}
						}
					} // (end 'Prefix' sub-case)
					else if (! empty($tmpUnSerialized[$inpAry['id']])) {
						$inpAry['defaultValue'] = $tmpUnSerialized[$inpAry['id']];
						if ($LOC_debug > 1) {
							GFCommon::log_debug("mfsfs_load_fields(2): case: " . $inpAry['label'] . " set to: " . $inpAry['defaultValue']);
						}
					}

					if ($LOC_debug > 1) {
						GFCommon::log_debug("mfsfs_load_fields(2): case $fieldType - id=" . $inpAry['id'] . " - label=" . $inpAry['label'] . " set to: " . $tmpUnSerialized[$inpAry['id']]);
					}
				}

				if ($LOC_debug > 1) {
					GFCommon::log_debug("mfsfs_load_fields(2): [$tmpLab] inputs from user tbl: $tmpInputs");
				}
				break;

			default:
				if ($LOC_debug > 2) {
					GFCommon::log_debug("mfsfs_load_fields(3): end-of-switch: NOT support type: ($fieldType) admLab: $admLabel");
				}
				break;
		} // End switch
	}

	return $form;
}

// Preserve all Field IDs of form discovered by the parser
function mfsfs_adm_settings($form_settings, $form)
{
	$cur_sfs_flag = rgar($form, MFSFS_FLAG) == 'on' ? 'checked' : '';
	$cur_debug = rgar($form, MFSFS_DEBUG);
	$cur_debug = empty($cur_debug) ? '0' : $cur_debug;
	$cur_prefix = rgar($form, MFSFS_PREFIX);
	$cur_prefix = empty($cur_prefix) ? MFSFS_DEFAULT_PREFIX : $cur_prefix;

	$tr_shared_fields = '
        <tr>
            <td colspan="2"><h4 class="gf_settings_subgroup_title">Multi-Form Sticky Field Sharing Options</h4></td>
        </tr>
        <tr>
            <th>Enable This Feature</th>
            <td>
              <input type="checkbox" name="' . MFSFS_FLAG . '" ' . $cur_sfs_flag . ' />
              <label for="' . MFSFS_FLAG . '">
                Use Shared Fields with Sticky Entry Values supported
              </label>
            </td>
        </tr>
        <tr>
            <th>Set Shared Group Name</th>
            <td>
              <input type="text" value="' . $cur_prefix . '" name="' . MFSFS_PREFIX . '" />
              <label for="' . MFSFS_PREFIX . '">
                Define shared group name for this form<br>(advanced option)
              </label>
            </td>
        </tr>
        <tr>
            <th>Set Debugging Levels</th>
            <td>
              <input type="radio" value="0" name="' . MFSFS_DEBUG . '" ' . ($cur_debug == '0' ? 'checked' : '') . '/>
               <label for="' . MFSFS_DEBUG . '">none &nbsp;
               </label>
              <input type="radio" value="1" name="' . MFSFS_DEBUG . '" ' . ($cur_debug == '1' ? 'checked' : '') . '/> 
               <label for="' . MFSFS_DEBUG . '">terse &nbsp;
               </label>
              <input type="radio" value="2" name="' . MFSFS_DEBUG . '" ' . ($cur_debug == '2' ? 'checked' : '') . '/>
               <label for="' . MFSFS_DEBUG . '">verbose &nbsp;
               </label>
              <input type="radio" value="3" name="' . MFSFS_DEBUG . '" ' . ($cur_debug == '3' ? 'checked' : '') . '/>
               <label for="' . MFSFS_DEBUG . '">noisy &nbsp;
               </label>
              </label>            </td>
        </tr>';

	$form_settings['Form Options']['shared_and_sticky'] = $tr_shared_fields;

	return $form_settings;
}

function mfsfs_adm_settings_save($form)
{
	$cur_value = rgpost(MFSFS_PREFIX);
	$cur_flag = rgpost(MFSFS_FLAG);
	$cur_debug = rgpost(MFSFS_DEBUG);
	$LOC_debug = isset($form[MFSFS_DEBUG]) ? $form[MFSFS_DEBUG] : 0;

	if ($LOC_debug > 0) {
		GFCommon::log_debug('mfsfs_adm_settings_save(1)' . " val/flg/debug= $cur_value / $cur_flag / $cur_debug");
	}

	$form[MFSFS_PREFIX] = empty($cur_value) ? MFSFS_DEFAULT_PREFIX : $cur_value;
	$form[MFSFS_FLAG] = $cur_flag;
	$form[MFSFS_DEBUG] = $cur_debug;

	return $form;
}

function mfsfs_save_fields($validation_result) // (called by WP-GF at validation events)
{
	GLOBAL $GLO_field_id_refs;
	GLOBAL $GLO_form_debug_setting;

	$form = $validation_result['form'];
	$form_title = $form['title'];

	$LOC_clear_count = 0;

	$LOC_debug = isset($form[MFSFS_DEBUG]) ? $form[MFSFS_DEBUG] : 0;
	$GLO_form_debug_setting = $LOC_debug;

	if (! isset($form[MFSFS_FLAG]) || ! isset($form[MFSFS_PREFIX]) || ! $form[MFSFS_FLAG] || 1 > strlen($form[MFSFS_PREFIX])) {
		if ($LOC_debug > 0) {
			$tmp = isset($form[MFSFS_PREFIX]) ? $form[MFSFS_PREFIX] : 'NO GROUP PREFIX'; // (for debug logging)
			GFCommon::log_debug('mfsfs_save_fields(1)' . " NOT MFSFS enabled form, title = ($form_title), group = ($tmp)");
		}
		return $validation_result;
	}

	$current_user = wp_get_current_user();

	if (! ($current_user instanceof WP_User) || 0 == $current_user->ID) {
		GFCommon::log_debug('mfsfs_save_fields(1): No user is logged in - will use SESSION array.');

		$LOC_UserId = 0; // (anonymous user)
	} else {
		$LOC_UserId = $current_user->ID;
	}

	$flds = $form['fields']; // This copy will be used to over-write original array
	$fldLst = mfsfs_get_form_handles($flds); // fldLst's Index is rec's adminLabel, value is index of flds[]

	if (isset($fldLst[MFSFS_SPECIAL_CLEAR_DATA])) { // (Form has a specail field which needs priority processing)
		mfsfs_clear_all_meta($LOC_UserId, $LOC_debug, $form[MFSFS_PREFIX], $flds[$fldLst[MFSFS_SPECIAL_CLEAR_DATA]]); // (pass the field object)
	}

	$entry = GFFormsModel::get_current_lead(); // (ALL THE FILLED OUT FIELDS' ANSWERS)
	$current_page = rgpost('gform_source_page_number_' . $form['id']) ? rgpost('gform_source_page_number_' . $form['id']) : 1;
	$mfsfs_prefix = MFSFS_ID . $form[MFSFS_PREFIX];

	if ($LOC_debug > 0) {
		GFCommon::log_debug('mfsfs_save_fields(1)' . " MFSFS enabled form title = ($form_title) current page = $current_page and prefix = $mfsfs_prefix");
		GFCommon::log_debug('mfsfs_save_fields(1)' . " Begin looping thru flield list for user ID=$LOC_UserId");
	}

	if ($LOC_UserId === 0) {

		if ($LOC_debug > 2) {
			$tmpSess = print_r($_SESSION, TRUE);
			GFCommon::log_debug('mfsfs_save_fields(3)' . " Target PreFix=$mfsfs_prefix and _SESSION array has: [$tmpSess]");
		}

		$all_meta_for_user = array_filter($_SESSION, function ($a) use ($mfsfs_prefix) {
			return strpos($a, $mfsfs_prefix) === 0 ? TRUE : FALSE;
		}, ARRAY_FILTER_USE_KEY);
	} else {
		$all_meta_for_user = array_map(function ($a) {
			return $a[0];
		}, get_user_meta($LOC_UserId));
	}

	if ($LOC_debug > 2) {
		$tmp = print_r($all_meta_for_user, TRUE);
		GFCommon::log_debug("mfsfs_save_fields(3)" . ' User Meta Ary:' . $tmp);
	}

	// ========================================================================================
	// CHECK ALL ENTRY FIELDS OF SUBMITTED FORM AGAINST USER's META DATA (if any)
	// ========================================================================================

	foreach ($fldLst as $admLabel => $fldVal) {
		$trgUserLab = mfsfs_convert_str_to_wp_fmt($admLabel);
		$trgSaveLab = MFSFS_ID . $trgUserLab;

		if ($admLabel == MFSFS_SPECIAL_CLEAR_DATA) { // (handled already above)
			continue;
		}

		if (isset($flds[$fldVal]['type']) && MFSFS_FIELD_TYPE_SF_CLEAR == $flds[$fldVal]['type']) {
			if ($LOC_debug > 1) {
				GFCommon::log_debug("mfsfs_save_fields(2) entry-scan: prefix SPECIAL CASE field type: {$flds[$fldVal]['type']}");
			}
		} else if (strpos($trgUserLab, $form[MFSFS_PREFIX]) !== 0) { // We only save fields with label prefixed with form's admin setting
			if ($LOC_debug > 1) {
				GFCommon::log_debug("mfsfs_save_fields(2) entry-scan: prefix MISS on admLab=$trgUserLab, fldVal=$fldVal");
			}

			continue;
		}

		if ($LOC_debug > 0) {
			GFCommon::log_debug("mfsfs_save_fields(1) entry-scan: prefix HIT on admLab=$trgUserLab, fldVal=$fldVal");
		}

		$userVal = isset($all_meta_for_user[$trgSaveLab]) ? $all_meta_for_user[$trgSaveLab] : '';

		if (isset($GLO_field_id_refs[$fldVal])) { // (NOTE: We DO wish to null-out user vars if submitted field is empty)
			$tmp2 = $GLO_field_id_refs[$fldVal]; // Index to entry ary where submitted val is
			$tmp3 = isset($entry[$tmp2]) ? $entry[$tmp2] : ''; // Submitted val
			$curType = $flds[$fldVal]['type'];
			$formPage = $flds[$fldVal]['pageNumber'];

			if ($LOC_debug > 0) { // TODO add log_debug for UserId == 0 cases too (currently only logs for non-anon users)
				GFCommon::log_debug("mfsfs_save_fields(1) pre-switch for setting user_update for: " . "Label=$trgSaveLab set type: $curType to submitted input: $tmp3 (sub-indx: $tmp2) (user=$userVal) (page=$formPage)");
			}

			if ($formPage != $current_page) { // (this avoids saving not-viewed pages' default entries on multi-page forms)
				continue;
			}

			// ---------------------- FIELD TYPES ------------------------
			//
			switch ($curType) {

				case MFSFS_FIELD_TYPE_SF_CLEAR:
					if ($LOC_debug > 1) {
						$dbMsg = "label:{$flds[$fldVal]['label']} - checked: $tmp3 - option: {$flds[$fldVal]['SFClearFieldOption']} - group: {$flds[$fldVal]['SFClearFieldGroup']}";
						GFCommon::log_debug("mfsfs_save_fields(2) case $curType: SPECIAL CASE action - $dbMsg");
					}

					if ($tmp3 === '1') {
						$LOC_clear_count += mfsfs_clear_saved_data($LOC_UserId, $LOC_debug, $flds[$fldVal]['SFClearFieldOption'], $flds[$fldVal]['SFClearFieldGroup']);
					}
					break;

				case MFSFS_FIELD_TYPE_TIME:
				case MFSFS_FIELD_TYPE_MULTI_SELECT:
					if ($LOC_debug > 1) {
						GFCommon::log_debug("mfsfs_save_fields(2) case $curType: Entries - write to user meta: $tmp3");
					}

					if ($LOC_UserId === 0) {
						$_SESSION[$trgSaveLab] = $tmp3;
						$res = 'SESSION updated';
					} else {
						$res = update_user_meta($LOC_UserId, $trgSaveLab, $tmp3);
					}

					if ($LOC_debug > 1) {
						$res = mfsfs_normalize_result_flag($res);
						GFCommon::log_debug("mfsfs_save_fields(2) Update Result: user_update Returned: $res");
					}
					break;

				case MFSFS_FIELD_TYPE_ADDRESS:
					$eIndx = 1; // (for inputs[] - entry indexes will be tmp2.1-x, eg: 21.1, 21.2...))
					$tmpE = array(); // Populate the list of entry indexes for this set of checkbox vals

					while (isset($entry["$tmp2.$eIndx"])) {
						$tmpE[$eIndx - 1] = $entry["$tmp2.$eIndx"];
						$eIndx ++;
					}

					$tmpE2 = print_r($tmpE, TRUE);

					if ($LOC_debug > 1) {
						GFCommon::log_debug("mfsfs_save_fields(2) case $curType: Entries - write items to user meta: $tmpE2");
					}

					if ($LOC_UserId === 0) {
						$_SESSION[$trgSaveLab] = serialize($tmpE);
						$res = 'SESSION updated';
					} else {
						$res = update_user_meta($LOC_UserId, $trgSaveLab, $tmpE);
					}

					if ($LOC_debug > 1) {
						$res = mfsfs_normalize_result_flag($res);
						GFCommon::log_debug("mfsfs_save_fields(2) case $curType: Update Result: $res");
					}
					break;

				case MFSFS_FIELD_TYPE_NAME: // Five supported: id.2=Prefix, id.3=First, id.4=Mid, id.6=Last, id.8=Suffix
					$tmpE = array(); // Key / Val is [id]=value - special case of id/label is id.2/Prefix, match to value

					$tmpE["$tmp2.2"] = empty($entry["$tmp2.2"]) ? '' : $entry["$tmp2.2"];
					$tmpE["$tmp2.3"] = empty($entry["$tmp2.3"]) ? '' : $entry["$tmp2.3"];
					$tmpE["$tmp2.4"] = empty($entry["$tmp2.4"]) ? '' : $entry["$tmp2.4"];
					$tmpE["$tmp2.6"] = empty($entry["$tmp2.6"]) ? '' : $entry["$tmp2.6"];
					$tmpE["$tmp2.8"] = empty($entry["$tmp2.8"]) ? '' : $entry["$tmp2.8"];

					$tmpE2 = print_r($tmpE, TRUE);

					if ($LOC_debug > 1) {
						GFCommon::log_debug("mfsfs_save_fields(2) case $curType: Entries - write items to user meta: $tmpE2");
					}

					if ($LOC_UserId === 0) {
						$_SESSION[$trgSaveLab] = serialize($tmpE);
						$res = 'SESSION updated';
					} else {
						$res = update_user_meta($LOC_UserId, $trgSaveLab, $tmpE);
					}

					if ($LOC_debug > 1) {
						$res = mfsfs_normalize_result_flag($res);
						GFCommon::log_debug("mfsfs_save_fields(2) case $curType: Update Result: $res");
					}
					break;

				case MFSFS_FIELD_TYPE_CHECKBOX:
					$eIndx = 1; // (checkbox - entry indexes will be tmp2.1-x, eg: 21.1, 21.2...))
					$tmpE = array(); // Acquire the list of entry indexes for this set of checkbox vals

					while (isset($entry["$tmp2.$eIndx"])) {
						$tmpE[$entry["$tmp2.$eIndx"]] = 1;
						$eIndx ++;
					}

					$tmpE2 = print_r($tmpE, TRUE);

					if ($LOC_debug > 1) {
						GFCommon::log_debug("mfsfs_save_fields(2) case $curType: Entries - write items to user meta: $tmpE2");
					}

					if ($LOC_UserId === 0) {
						$_SESSION[$trgSaveLab] = serialize($tmpE);
						$res = 'SESSION updated with new checkbox values';
					} else {
						$res = update_user_meta($LOC_UserId, $trgSaveLab, $tmpE);
					}

					if ($LOC_debug > 1) {
						$res = mfsfs_normalize_result_flag($res);
						GFCommon::log_debug("mfsfs_save_fields(2) case $curType: Update Result: $res");
					}
					break;

				case MFSFS_FIELD_TYPE_DROP_DOWN:
				case MFSFS_FIELD_TYPE_RADIO:
					if (! empty($tmp3) && $userVal != $tmp3) {
						if ($LOC_UserId === 0) {
							$_SESSION[$trgSaveLab] = $tmp3;
							$res = 'SESSION updated';
						} else {
							$res = update_user_meta($LOC_UserId, $trgSaveLab, $tmp3); // (note: returns false if setting same val as set, won't set to null either)

							if ($LOC_debug > 1) {
								$res = mfsfs_normalize_result_flag($res);
								GFCommon::log_debug("mfsfs_save_fields(2) case $curType: Update Result: $res");
							}
						}
					}
					break;

				case MFSFS_FIELD_TYPE_TEXT_LINE:
					if (! empty($tmp3) && $userVal != $tmp3) {
						if ($LOC_UserId === 0) {
							$_SESSION[$trgSaveLab] = $tmp3;
							$res = 'SESSION updated';
						} else {
							$res = update_user_meta($LOC_UserId, $trgSaveLab, $tmp3); // (note: returns false if setting same val as set, won't set to null either)

							if ($LOC_debug > 1) {
								$res = mfsfs_normalize_result_flag($res);
								GFCommon::log_debug("mfsfs_save_fields(2) FT_Text Line: Update Result: user_update text line RES: Returned: $res");
							}
						}
					}
					break;

				case MFSFS_FIELD_TYPE_TEXT_AREA:
					if (! empty($tmp3) && $userVal != $tmp3) {
						if ($LOC_UserId === 0) {
							$_SESSION[$trgSaveLab] = $tmp3;
							$res = 'SESSION updated';
						} else {
							$res = update_user_meta($LOC_UserId, $trgSaveLab, $tmp3); // (note: returns false if setting same val as set, won't set to null either)

							if ($LOC_debug > 1) {
								$res = mfsfs_normalize_result_flag($res);
								GFCommon::log_debug("mfsfs_save_fields(2) FT_Text Area: Update Result: $res");
							}
						}
					}
					break;

				case MFSFS_FIELD_TYPE_EMAIL_ADDRESS:
				case MFSFS_FIELD_TYPE_NUMBER:
				case MFSFS_FIELD_TYPE_DATE:
				case MFSFS_FIELD_TYPE_PHONE:
					if (! empty($tmp3) && $userVal != $tmp3) {
						if ($LOC_UserId === 0) {
							$_SESSION[$trgSaveLab] = $tmp3;
							$res = 'SESSION updated';
						} else {
							$res = update_user_meta($LOC_UserId, $trgSaveLab, $tmp3); // (note: returns false if setting same val as set, won't set to null either)

							if ($LOC_debug > 1) {
								$res = mfsfs_normalize_result_flag($res);
								GFCommon::log_debug("mfsfs_save_fields(2) case $curType: Update Result: $res");
							}
						}
					}
					break;

				case MFSFS_FIELD_TYPE_LIST:
					$entry_list_string = rgar($entry, $tmp2);

					if ($LOC_UserId === 0) {
						$_SESSION[$trgSaveLab] = $entry_list_string;
						$res = 'SESSION updated';
					} else {

						if (! empty($entry_list_string)) {
							$res = update_user_meta($LOC_UserId, $trgSaveLab, $entry_list_string);

							if ($LOC_debug > 1) {
								$res = mfsfs_normalize_result_flag($res);
								GFCommon::log_debug("mfsfs_save_fields(2) case $curType: Update Result: $res");
							}
						}
					}
					break;

				default:
					if ($LOC_debug > 1) {
						GFCommon::log_debug("mfsfs_save_fields(2) (no action): Unsupported: curType=$curType not set to user's table");
					}

					if ($LOC_debug > 1) {
						$tmpFldObj = print_r($flds[$fldVal], TRUE);
						GFCommon::log_debug("mfsfs_save_fields(2) Unknown Fld Obj: $tmpFldObj");
					}
					break;
			} // End Switch
		}
	} // (end of foreach)

	// Update special SF_Clear "clear count" variable with new count, or set to zero
	$lastCCVal = isset($all_meta_for_user[MFSFS_SPECIAL_CLEARED_COUNT]) ? $all_meta_for_user[MFSFS_SPECIAL_CLEARED_COUNT] : '0';

	if ($LOC_clear_count != $lastCCVal) {
		if ($LOC_UserId === 0) {
			$_SESSION[MFSFS_SPECIAL_CLEARED_COUNT] = $LOC_clear_count;
			$res = 'SESSION updated';
		} else {
			$res = update_user_meta($LOC_UserId, MFSFS_SPECIAL_CLEARED_COUNT, $LOC_clear_count);

			if ($LOC_debug > 0) {
				$res = mfsfs_normalize_result_flag($res);
				GFCommon::log_debug("mfsfs_save_fields(1) SF_Clear Count: saved $LOC_clear_count - Save Returned: $res");
			}
		}
	}

	// ========================================================================================
	//

	if ($LOC_debug > 2) {
		$tmp = print_r($fldLst, TRUE);
		GFCommon::log_debug("mfsfs_save_fields(3) fldLst: $tmp");
	}

	// $tmp = print_r($GLO_field_id_refs, TRUE);
	// GFCommon::log_debug('mfsfs_save_fields' . '(): GLO_FldIds:' . $tmp);

	if ($LOC_debug > 2) {
		$tmp = print_r($form, TRUE);
		GFCommon::log_debug("mfsfs_save_fields(3) form ary: $tmp");
	}

	if ($LOC_debug > 2) {
		$tmp = print_r($entry, TRUE);
		GFCommon::log_debug("mfsfs_save_fields(3) entry ary: $tmp");
	}

	return $validation_result;
}

/*
 * Returns array of indexed field names with the value of the form array's index
 */
function mfsfs_get_form_handles(& $flds)
{
	$locFldList = array();

	GLOBAL $GLO_field_id_refs; // Save discovered IDs of each field (key=Fld-Indx, val=ID)

	foreach ($flds as $key => $rec) {
		if (isset($rec['id'])) {
			$GLO_field_id_refs[$key] = $rec['id'];
		}

		if ((isset($rec['type']) && MFSFS_FIELD_TYPE_SF_CLEAR == $rec['type'])) {
			if (empty($rec['adminLabel']) && ! empty($rec['label'])) {
				$locFldList[$rec['label']] = $key;
			} else if (! empty($rec['adminLabel'])) {
				$locFldList[$rec['adminLabel']] = $key;
			}
		} else if (isset($rec['adminLabel']) && null != $rec['adminLabel']) {
			$locFldList[$rec['adminLabel']] = $key; // (this is a corrective action for fields missing option in UI)
		}
	}

	return $locFldList;
}

/*
 * Because user meta data is saved with indexes using underscores for spaces and dashes
 */
function mfsfs_convert_str_to_wp_fmt($origStr)
{
	$newStr = str_replace(' ', '_', $origStr);
	$newStr = str_replace('-', '_', $newStr);

	return $newStr;
}

/*
 * Convert string like "ABC xxx-123" to INT (ignores neg. val)
 * Ref: http://php.net/manual/en/function.intval.php (comment from: espertalhao04)
 */
function mfsfs_convert_str_to_int($s)
{
	return (int) preg_replace('/[^\-\d]*(\-?\d*).*/', '$1', $s);
}

function mfsfs_normalize_result_flag($res)
{
	if ($res === true)
		return 'TRUE';
	else if ($res === false)
		return 'FALSE';
	else
		return $res;
}

function mfsfs_get_fld_indx_from_entry($entryVal, $field_choices)
{
	$keys = array_keys($field_choices);

	foreach ($keys as $ii) {
		if ($field_choices[$ii]['value'] == $entryVal)
			return $ii;
	}

	return false;
}

function start_session()
{
	if (! session_id()) {
		session_start();
	}
}

function sf_clr_standard_settings($position, $form_id)
{
	// create settings on position 25 (right after Description Box)
	if ($position == 25) {
		?>
<li class="sf_clear_default field_setting"><label
	for="field_sf_clear_default" class="section_label">   
<?php esc_html_e( 'Set Default Condition', 'gravityforms' ); ?>
&nbsp;
<?php gform_tooltip( 'form_field_sf_default_value' ) ?>
</label> <input type="checkbox" name="field_sf_clear_default"
	id="field_sf_clear_default_value" value="1"
	onclick="ToggleSFClearDefault();" />Pre-populate default as checked</li>

<li class="sf_clear_setting field_setting"><label
	for="field_sf_clear_option" class="section_label">   
<?php esc_html_e( 'Clear Data Action', 'gravityforms' ); ?>
&nbsp;
<?php gform_tooltip( 'form_field_sf_clear_value' ) ?>
</label> <input type="radio" name="field_sf_clear_option"
	id="field_sf_clear_option_nothing" size="10" value="nothing"
	onclick="SetFieldProperty('SFClearFieldOption', 'nothing');" />Do
	Nothing &nbsp; <input type="radio" name="field_sf_clear_option"
	id="field_sf_clear_option_all" size="10" value="all"
	onclick="SetFieldProperty('SFClearFieldOption', 'all');" />Delete ALL
	Saved Data &nbsp; <input type="radio" name="field_sf_clear_option"
	id="field_sf_clear_option_group" size="10" value="group"
	onclick="SetFieldProperty('SFClearFieldOption', 'group');" />Group ONLY
</li>

<li class="sf_clear_target_group field_setting"><label
	for="field_sf_clear_group" class="section_label">   
<?php esc_html_e( 'Group Name (if applicable)', 'gravityforms' ); ?>
&nbsp;
<?php gform_tooltip( 'form_field_sf_clear_group_name' ) ?>
</label> <input type="text" id="field_sf_clear_target_group"
	onkeyup="SetFieldProperty('SFClearFieldGroup', jQuery(this).val());"
	onchange="SetFieldProperty('SFClearFieldGroup', jQuery(this).val());" />
</li>

<?php
	}
}

function sf_clr_editor_script()
{
	?>
<script type='text/javascript'>
        // adding setting to fields of type "sf_clear"
        fieldSettings.sf_clear += ', .sf_clear_setting';
        fieldSettings.sf_clear += ', .sf_clear_default';
        fieldSettings.sf_clear += ', .sf_clear_target_group';

        function ToggleSFClearDefault() {
        	field.SFClearFieldDefault == 'checked' ? SetFieldProperty('SFClearFieldDefault', '') : SetFieldProperty('SFClearFieldDefault', 'checked');
        }
        
        // binding to the load field settings event to initialize the radio option
        jQuery(document).on('gform_load_field_settings', function(event, field, form){
        	jQuery('#field_sf_clear_target_group').val(field.SFClearFieldGroup);

        	jQuery('#field_sf_clear_default_value').attr('checked', field.SFClearFieldDefault == 'checked');

        	jQuery('#field_sf_clear_option_nothing').attr('checked', field.SFClearFieldOption == 'nothing');
        	jQuery('#field_sf_clear_option_all').attr('checked', field.SFClearFieldOption == 'all');
        jQuery('#field_sf_clear_option_group').attr('checked', field.SFClearFieldOption == 'group');
        });
    </script>
<?php
}

function sf_clr_add_sf_clear_tooltips($tooltips)
{
	$tooltips['form_field_sf_default_value'] = "<h6>Configure Default Set To: 'checked'</h6>This rendered or hidden 'SF_Clear' field will be pre-populated as 'checked'. This is for the use case when your form design requires the user to have a one-click action, triggered by the form submit button. <p>NOTE: If this field is set to 'hidden', so the user will be unable to uncheck it (obviously). Take care with this powerful feature.";

	$tooltips['form_field_sf_clear_value'] = "<h6>Configure Data Clearing Option</h6>The 'Delete ALL Saved Data' option will cause all MFSFS saved data to be deleted; for 'Group ONLY' option, only the data saved of a specific named group will be deleted (see Group Name option below).";

	$tooltips['form_field_sf_clear_group_name'] = "<h6>Used only when above Action is set to Group ONLY</h6>Configure the Group Name targeted for data clearing. Example: <b>sf2_</b>";

	return $tooltips;
}
