<?php 
/*
*      Robo Gallery     
*      Version: 1.0
*      By Robosoft
*
*      Contact: http://robosoft.co
*      Created: 2015
*      Licensed under the GPLv2 license - http://opensource.org/licenses/gpl-2.0.php
*
*      Copyright (c) 2014-2015, Robosoft. All rights reserved.
*      Available only in http://robosoft.co/
*/


$size_group = new_cmb2_box( array(
    'id' 			=> ROBO_GALLERY_PREFIX . 'size_metabox',
    'title' 		=> '<span class="dashicons dashicons-welcome-add-page"></span>'.__('Gallery Size Options' , 'rbs_gallery' ),
    'object_types' 	=> array( ROBO_GALLERY_TYPE_POST ),
    'cmb_styles' 	=> false,
    'show_names'	=> false,
    'context' 		=> 'normal',
    'priority' 		=> 'high',
    'closed'        => rbs_gallery_set_checkbox_default_for_new_post(0),
));

//$size_group->add_field( array('type' => 'toolbox'));

$size_group->add_field( array(
	'name' 			=> __('Thumbs Options', 'rbs_gallery' ),
	'id' 			=> ROBO_GALLERY_PREFIX . 'thumb-size-options',
	'type' 			=> 'size',
	'level'			=> !ROBO_GALLERY_PRO,
	'before_row' 	=> ' <br />
<div class="rbs_block">
	<div class="rbs_thumb_tabs">
		<div role="tabpanel">
			<ul class="nav nav-tabs" role="tablist">
				<li role="presentation" class="active"><a href="#thumb_size_options" aria-controls="thumb_size_options" role="tab" data-toggle="tab">'.__('Thumbs Options', 'rbs_gallery' ).'</a></li>
				<li role="presentation"><a href="#thumb_colums_options" aria-controls="thumb_colums_options" role="tab" data-toggle="tab">'.__('Size Options', 'rbs_gallery' ).'</a></li>
			</ul>
			<div class="tab-content">
				<div role="tabpanel" class="tab-pane active" id="thumb_size_options"><br/>',
));

$size_group->add_field( array(
	'name' 			=> 	__('Custom Ratio', 'rbs_gallery' ),
	'id' 			=> 	ROBO_GALLERY_PREFIX . 'sizeType',
	'type' 			=> 	'switch',
	'depends' 		=> 	'.rbs_size_width, .rbs_size_height',
	'default'		=> 	rbs_gallery_set_checkbox_default_for_new_post(0),
	'bootstrap_style'=> 1,
    'after_row' 	=> '
				</div>',
));

$size_group->add_field( array(
	'name' 			=> __('Colums ', 'rbs_gallery'),
	'id' 			=> ROBO_GALLERY_PREFIX . 'colums',
	'type' 			=> 'colums',
	'default'		=> rbs_gallery_set_checkbox_default_for_new_post(1),
	'bootstrap_style'=> 1,
	'level'			=> !ROBO_GALLERY_PRO,
    'before_row' 	=> '
				<div role="tabpanel" class="tab-pane" id="thumb_colums_options"><br/>',
	'after_row' => '
				</div>
			</div>
		</div>
	</div>
</div>',	
));