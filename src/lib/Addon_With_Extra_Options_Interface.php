<?php

namespace cybot\cookiebot\lib;

interface Addon_With_Extra_Options_Interface {
	/**
	 * @return string
	 */
	public function get_extra_addon_options_html();
}