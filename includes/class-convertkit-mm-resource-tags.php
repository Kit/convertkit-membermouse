<?php
/**
 * ConvertKit Tags Resource class.
 *
 * @package ConvertKit_MM
 * @author ConvertKit
 */

/**
 * Reads ConvertKit Tags from the options table, and refreshes
 * ConvertKit Tags data stored locally from the API.
 *
 * @since   1.3.0
 */
class ConvertKit_MM_Resource_Tags extends ConvertKit_MM_Resource {

	/**
	 * Holds the Settings Key that stores site wide ConvertKit settings
	 *
	 * @since   1.3.0
	 *
	 * @var     string
	 */
	public $settings_name = 'convertkit-mm-tags';

	/**
	 * The type of resource
	 *
	 * @since   1.3.0
	 *
	 * @var     string
	 */
	public $type = 'tags';

}
