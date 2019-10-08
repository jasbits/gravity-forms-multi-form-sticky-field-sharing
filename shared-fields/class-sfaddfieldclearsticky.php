<?php
GFForms::include_addon_framework();

/*
 * This added field is only designed for use with the MFSFS plugin.
 * Its purpose is to provide a field type used for clearing out saved field entry data.
 *
 * New type is: "sf_clear" (In UI is "SF_Clear")
 *
 * This class was adapted from: https://github.com/richardW8k/simplefieldaddon
 *
 * Helpfull info: http://inlinedocs.gravityhelp.com/class-GFAddOn.html
 * -JAS
 */
class SFClearStickyFieldAddOn extends GFAddOn
{

	protected $_version = GRAVITY_FORMS_SHARED_FIELDS;

	protected $_min_gravityforms_version = '2.4.6';

	protected $_slug = 'sfaddfieldclearsticky';

	protected $_path = 'shared-fields/sfaddfieldclearsticky.php';

	protected $_full_path = __FILE__;

	protected $_title = 'Gravity Forms Clear Sticky Field Add-On';

	protected $_short_title = 'SF Clear Sticky Field Add-On';

	/**
	 *
	 * @var object $_instance If available, contains an instance of this class.
	 */
	private static $_instance = null;

	/**
	 * Returns an instance of this class, and stores it in the $_instance property.
	 *
	 * @return object $_instance An instance of this class.
	 */
	public static function get_instance()
	{
		if (self::$_instance == null) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Include the field early so it is available when entry exports are being performed.
	 */
	public function pre_init()
	{
		parent::pre_init();

		if ($this->is_gravityforms_supported() && class_exists('GF_Field')) {
			require_once ('includes/class-sf-add-field-clear-sticky.php');
		}
	}

	public function init_admin()
	{
		parent::init_admin();

		add_filter('gform_tooltips', array(
			$this,
			'tooltips'
		));
		add_action('gform_field_appearance_settings', array(
			$this,
			'field_appearance_settings'
		), 10, 2);
	}

	// # FIELD SETTINGS -------------------------------------------------------------------------------------------------

	/**
	 * Add the tooltips for the field.
	 *
	 * @param array $tooltips
	 *        	An associative array of tooltips where the key is the tooltip name and the value is the tooltip.
	 *        	
	 * @return array
	 */
	public function tooltips($tooltips)
	{
		$LOC_tooltips = array(
			'input_class_setting' => sprintf('<h6>%s</h6>%s', esc_html__('Input CSS Classes', 'sfaddfieldclearsticky'), esc_html__('The CSS Class names to be added to the field input.', 'sfaddfieldclearsticky'))
		);

		return array_merge($tooltips, $LOC_tooltips);
	}

	/**
	 * Add the custom setting for the SF Clear Sticky field to the Appearance tab.
	 *
	 * @param int $position
	 *        	The position the settings should be located at.
	 * @param int $form_id
	 *        	The ID of the form currently being edited.
	 */
	public function field_appearance_settings($position, $form_id)
	{
		// Add our custom setting just before the 'Custom CSS Class' setting.
		if ($position == 250) {
			?>
<li class="input_class_setting field_setting"><label
	for="input_class_setting">
					<?php esc_html_e( 'Input CSS Classes', 'sfaddfieldclearsticky' ); ?>
					<?php gform_tooltip( 'input_class_setting' ) ?>
				</label> <input id="input_class_setting" type="text"
	class="fieldwidth-1"
	onkeyup="SetInputClassSetting(jQuery(this).val());"
	onchange="SetInputClassSetting(jQuery(this).val());" /></li>

<?php
		}
	}
}