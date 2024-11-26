<?php
/**
 * Kit Custom Fields Resource class.
 *
 * @package ConvertKit_MM
 * @author ConvertKit
 */

/**
 * Reads Kit Custom Fields from the options table, and refreshes
 * Kit Custom Fields data stored locally from the API.
 *
 * @since   1.2.8
 */
class ConvertKit_MM_Resource_Custom_Fields extends ConvertKit_MM_Resource {

	/**
	 * Holds the Settings Key that stores site wide ConvertKit settings
	 * 
	 * @since 	1.2.8
	 *
	 * @var     string
	 */
	public $settings_name = 'convertkit-mm-custom-fields';

	/**
	 * The type of resource
	 * 
	 * @since 	1.2.8
	 *
	 * @var     string
	 */
	public $type = 'custom_fields';

	/**
	 * The key to use when alphabetically sorting resources.
	 *
	 * @since   1.2.8
	 *
	 * @var     string
	 */
	public $order_by = 'label';

}
