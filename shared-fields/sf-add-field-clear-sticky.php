<?php
/*
Plugin Name: Gravity Forms Simple Field Add-On
Plugin URI: http://www.gravityforms.com
Description: A simple add-on to demonstrate how to use the Add-On Framework to include a new field type.
Version: 1.0
Author: Rocketgenius
Author URI: http://www.rocketgenius.com
Text Domain: simplefieldaddon
Domain Path: /languages

------------------------------------------------------------------------
Copyright 2012-2016 Rocketgenius Inc.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/

define( 'GF_SIMPLE_FIELD_ADDON_VERSION', '1.0' );

add_action( 'gform_loaded', array( 'GF_Simple_Field_AddOn_Bootstrap', 'load' ), 5 );

class GF_Simple_Field_AddOn_Bootstrap {

    public static function load() {

        if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
            return;
        }

        require_once( 'class-gfsimplefieldaddon.php' );

        GFAddOn::register( 'GFSimpleFieldAddOn' );
    }

}