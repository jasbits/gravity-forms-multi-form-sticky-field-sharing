<?php
if (! class_exists('GFForms')) {
	die();
}

class SFClear_GF_Field extends GF_Field_Consent
{

	/**
	 *
	 * @var string $type The field type.
	 */
	public $type = 'sf_clear';

	/**
	 * Return the field title, for use in the form editor.
	 *
	 * @return string
	 */
	public function get_form_editor_field_title()
	{
		return esc_attr__('SF_Clear', 'sfaddfieldclearsticky');
	}

	/**
	 * Assign the field button to the Advanced Fields group.
	 *
	 * @return array
	 */
	public function get_form_editor_button()
	{
		return array(
			'group' => 'advanced_fields',
			'text' => $this->get_form_editor_field_title()
		);
	}

	public function get_form_editor_field_settings()
	{
		return array(
			'conditional_logic_field_setting',
			'error_message_setting',
			'label_setting',
			'label_placement_setting',
			'admin_label_setting',
			'checkbox_label_setting',
			'visibility_setting',
			'description_setting',
			'css_class_setting'
		);
	}

	public function allow_html()
	{
		return false;
	}
	
	/**
	 * Enable this field for use with conditional logic.
	 *
	 * @return bool
	 */
	public function is_conditional_logic_supported() {
		return true;
	}
	
	/**
	 * ( Modified from simple-gf-field example ) - Changed to use checkbox as input type 
	 */
	public function get_field_input($form, $value = '', $entry = null)
	{
		$id = absint($this->id);
		$form_id = absint($form['id']);
		$is_entry_detail = $this->is_entry_detail();
		$is_form_editor = $this->is_form_editor();

		// Prepare the value of the input ID attribute.
		$field_id = $is_entry_detail || $is_form_editor || $form_id == 0 ? "input_$id" : 'input_' . $form_id . "_$id";

		$value = esc_attr($value);

		$target_input_id = parent::get_first_input_id( $form );
		$for_attribute = empty( $target_input_id ) ? '' : "for='{$target_input_id}'";
		$invalid_attribute  = $this->failed_validation ? 'aria-invalid="true"' : 'aria-invalid="false"';
		$label_class_attribute = 'class="gfield_sf_clear_label"';
		
		$disabled_text = $is_form_editor ? 'disabled="disabled"' : '';
		$checkbox_label = $this->checkboxLabel;
		$tabindex = $this->get_tabindex();
		// $checked = $is_form_editor ? '' : checked('1', $value, false);
		$checked = $this->SFClearFieldDefault != 'checked' ? '' : 'checked';

		$input = "<input name='input_{$id}' id='{$field_id}' type='checkbox' value='1' {$tabindex} {$invalid_attribute} {$disabled_text} {$checked} /> <label {$label_class_attribute} {$for_attribute} >{$checkbox_label}</label>";

		return sprintf("<div class='ginput_container ginput_container_%s'>%s</div>", $this->type, $input);
	}

}

GF_Fields::register(new SFClear_GF_Field());