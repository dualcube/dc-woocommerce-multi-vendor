<?php

use Elementor\Core\DynamicTags\Data_Tag;

abstract class WCMp_Elementor_DataTagBase extends Data_Tag {

    public function get_group() {
        return 'wcmp';
    }

    public function get_categories() {
        return [ \Elementor\Modules\DynamicTags\Module::BASE_GROUP ];
    }
}
