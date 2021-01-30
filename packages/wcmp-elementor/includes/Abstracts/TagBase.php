<?php

use Elementor\Core\DynamicTags\Tag;

abstract class WCMp_Elementor_TagBase extends Tag {
    
	public function get_group() {
			return 'wcmp';
	}

	public function get_categories() {
			return [ \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY ];
	}
}
