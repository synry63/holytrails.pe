<?php

// This is used to create layouts from PHP.

class WPDD_layout {

    private $rows;
    private $width;
    private $name;
    private $parent_layout_name;
    private $post_id;
    private $post_slug;
    private $cssframework;

    function __construct($width, $cssframework = 'bootstrap'){
        global $wpddlayout;

        $this->rows = array();
        $this->width = $width;
        $this->name = '';
        $this->parent_layout_name = '';
        $this->post_id = 0;
        $this->post_slug = '';
        $this->cssframework = $wpddlayout->get_css_framework();
    }

    function add_row($row) {
        if ($row->get_layout_type() == 'fixed' && ($row->get_width() != $this->width)) {
            global $wpddlayout;
            $wpddlayout->record_render_error(__('The row width is different from the layout width. This happens when the child layout does not contain the same number of columns as the child placeholder in the parent layout.', 'ddl-layouts'));
        }
        $this->rows[] = $row;
    }

    function get_width() {
        return $this->width;
    }

    function get_css_framework()
    {
        return $this->cssframework;
    }

    function get_json() {
        return wp_json_encode($this->get_as_array());
    }

    function get_as_array() {
        $rows_array = array();
        foreach($this->rows as $row) {
            $rows_array[] = $row->get_as_array();
        }

        return array('Rows' => $rows_array);
    }

    function frontend_render($target) {
        if ($this->post_id) {
            $context = $this->get_string_context();
    
            $target->set_context($context);
        }

        $target->push_current_layout($this);
        foreach($this->rows as $row) {
            $row->frontend_render($target);
        }
        $target->pop_current_layout($this);
    }

    function set_name($name) {
        $this->name = $name;
    }
    function get_name() {
        return $this->name;
    }

    function set_parent_name($parent) {
        $this->parent_layout_name = $parent;
    }
    function get_parent_name() {
        return $this->parent_layout_name;
    }
    function get_parent_layout() {
        global $wpddlayout;

        return $wpddlayout->get_layout($this->parent_layout_name);
    }

    function set_post_id ($id) {
        $this->post_id = $id;
    }

    function get_post_id () {
        return $this->post_id;
    }

    function set_post_slug ($slug) {
        $this->post_slug = $slug;

        foreach($this->rows as $row) {
            $row->set_post_slug($slug);
        }

    }

    function get_post_slug () {
        return $this->post_slug;
    }

    function get_width_of_child_layout_cell() {

        foreach($this->rows as $row) {
            $child_width = $row->get_width_of_child_layout_cell();
            if ($child_width > 0) {
                return $child_width;
            }
        }

        return 0;

    }

    function get_row_count() {
        return sizeof($this->rows);
    }

    function get_children() {
        global $wpddlayout;

        $children = array();

        $layout_list = $wpddlayout->get_layout_list();

        foreach($layout_list as $layout_id) {
            $layout = $wpddlayout->get_layout_settings($layout_id, true);
            if ($layout) {
                if ( property_exists ( $layout , 'parent' ) && $layout->parent == $this->get_post_slug()) {
                    $children[] = $layout_id;
                }
            }

        }

        return $children;

    }

    function get_string_context () {
        return array('kind' => 'Layout',
            'name' => $this->post_id,
            'title' => $this->name,
            'edit_link' =>  admin_url( 'admin.php?page=dd_layouts_edit&amp;layout_id=' . $this->post_id)
            );
        
    }
    function register_strings_for_translation ($context = null) {
        if (!$context) {
            $context = $this->get_string_context();
        }
        
        foreach($this->rows as $row) {
            $row->register_strings_for_translation($context);
        }

    }

    function get_row_with_child()
    {
        $ret = null;

        foreach( $this->rows as $row )
        {
            if( $row->is_row_with_child() )
            {
                $ret = $row;
                break;
            }
        }

        return $ret;
    }

    function get_cells_of_type( $cell_type ){
        $ret = array();

        foreach( $this->rows as $row ){
            $cell = $row->find_cell_of_type( $cell_type );

            if( $cell  ){
                $ret[] = $cell;
            }
        }

        return $ret;
    }

    function get_cell_by_id( $cell_id ){
        $ret = null;

        foreach( $this->rows as $row ){
            $cell = $row->get_cell_by_id( $cell_id );

            if( $cell  ){
                $ret = $cell;
                break;
            }
        }

        return $ret;
    }

    function get_all_cells_of_type( $cell_type ){
        $ret = array();

        foreach( $this->rows as $row ){
            $cells = $row->find_cells_of_type( $cell_type );

            if( is_array($cells) && count($cells) > 0  ){
                foreach( $cells as $cell ){
                    $ret[] = $cell;
                }
            }
        }

        return $ret;
    }


    function has_cell_of_type( $cell_type, $check_parent = false ){
        if( $check_parent === false ){
            $ret = $this->get_cells_of_type( $cell_type );
            return count($ret) > 0 ? $ret[0] : false;
        } else{
            $parent = $this->get_parent_layout();
            $ret = $this->get_cells_of_type( $cell_type );
            return count($ret) > 0 ? $ret[0] : false || ( $parent && $parent->has_cell_of_type( $cell_type, true ) );
        }
    }

    function change_full_width_child_layout_row($child)
    {
        // get the row with a child
        $parent_row = $this->get_row_with_child();

        // if the parent row is null don't do nothing
        if ($parent_row === null) return false;


        $children_rows = $child->get_rows();
        $children_rows = array_values($children_rows); // Re-index the array
        $children_rows_length = count($children_rows);
        

        // if there are no rows in child don't do nothing
        if ($children_rows_length === 0) return false;

        // Set the context for each row so string translation
        // works in the context of the child layout.
        $context = $child->get_string_context();
        for ( $i=0; $i < $children_rows_length; $i++ ) {
            $children_rows[$i]->set_context($context);
        }
        
        // keep track of the parent's row position
        $index = 0;
        $ret = false;
        $count = count($this->rows);
        $preserve = array();

        for ($i = 0; $i < $count; $i++) {
            //remove the parent row we don't need
            if ( $parent_row === $this->rows[$i] ) {
                $ret = true;
                $index = $i;
                unset($this->rows[$i]);
            }

            // remove rows after the parent and store them
            if( $i > $index && $ret === true )
            {
                $preserve[] = $this->rows[$i];
                unset($this->rows[$i]);
            }
        }

        // inject the children rows in the parent's rows array
        if( $ret === true )
        {
            for ( $i=0; $i < $children_rows_length; $i++ ) {
                $this->rows[$index + $i] = $children_rows[$i];
            }

            // inject originals rows after the child
            foreach( $preserve as $row )
            {
                $this->rows[] = $row;
            }
            // resort the array with new keys
            ksort( $this->rows );
        }

        // tell the caller we did the job
       // var_dump( $this->rows );
        return $ret;
    }

    function get_rows()
    {
        return $this->rows;
    }

    function get_layout_type(){
        return $this->layout_type;
    }

    //FIXME: this method is deprecated remove it.
    function get_cells_with_images( ){
        $ret = array();

        foreach( $this->get_rows() as $row ){
            $cell = $row->find_cells_with_images( );

            if( $cell  ){
                $ret[] = $cell;
            }
        }

        return $ret;
    }

    function get_cells_with_content_field( $field_name ){

        $ret = array();

        foreach( $this->get_rows() as $row ){
            $cell = $row->find_cells_with_content_field( $field_name );

            if( $cell  ){
                $ret[] = $cell;
            }
        }
        return $ret;
    }
    
    function convert_sidebar_grid_for_preset() {
        foreach( $this->get_rows() as $row ){
            $row->convert_sidebar_grid_for_preset();
        }
    }

}

// Base class for all elements

class WPDD_layout_element {
    private $name;
    private $css_class_name;
    private $editor_visual_template_id;

    function __construct($name, $css_class_name = '', $editor_visual_template_id = '', $css_id = '', $tag = 'div' ) {
        $this->name = $name;
        $this->css_class_name = $css_class_name;
        $this->editor_visual_template_id = $editor_visual_template_id;
        $this->css_id = $css_id;
        if (!$tag) {
            $tag = 'div';
        }
        $this->tag = $tag;
    }

    function get_as_array() {
        return array(
            'name' => $this->name,
            'cssClass' => $this->css_class_name,
            'cssId' => $this->css_id,
            'editorVisualTemplateID' => $this->editor_visual_template_id,
            'kind' => null
        );
    }
    function get_name() {
        return $this->name;
    }

    function get_css_class_name() {
        return $this->css_class_name;
    }
    function get_css_id()
    {
        return $this->css_id;
    }
    function get_tag() {
        return $this->tag;
    }

    function getKind()
    {
        $obj = (object) $this->get_as_array();
        return $obj->kind;
    }

    function register_strings_for_translation ($context) {

        // do nothing by default
    }

    function set_post_slug ($slug) {
    }

}

class WPDD_layout_row extends WPDD_layout_element {

    private $cells;
    function __construct($name, $css_class_name = '', $editor_visual_template_id = '', $layout_type = 'fixed', $css_id = '', $additionalCssClasses = '', $tag = 'div', $mode = 'normal') {
        parent::__construct($name, $css_class_name, $editor_visual_template_id, $css_id, $tag);
        $this->cells = array();
        $this->additionalCssClasses = $additionalCssClasses;
        $this->set_layout_type( $layout_type );
        $this->mode = $mode;
        
        $this->context = null;

    }

    function add_cell($cell) {
        $this->cells[] = $cell;
    }
    
    function set_context($context) {
        $this->context = $context;
    }

    function get_additional_css_classes()
    {
        return $this->additionalCssClasses;
    }

    function get_as_array() {
        $data = parent::get_as_array();

        $cells_array = array();
        foreach($this->cells as $cell) {
            $cells_array[] = $cell->get_as_array();
        }

        $data['kind'] = 'Row';
        $data['Cells'] = $cells_array;
        $data['layout_type'] = $this->get_layout_type();
        $data['additionalCssClasses'] = $this->get_additional_css_classes();
        $data['mode'] = $this->get_mode();
        return $data;
    }

    function get_mode () {
        return $this->mode;
    }

    function get_width() {
        $width = 0;
        foreach($this->cells as $cell) {
            $width += $cell->get_width();
        }

        return $width;
    }

    function frontend_render($target) {
        $target->row_start_callback( $this->get_css_class_name(), $this->get_layout_type(), $this->get_css_id(), $this->get_additional_css_classes(), $this->get_tag(), $this->get_mode());

        // see if we should use a context for the row.
        $old_context = null;
        if ($this->context) {
            $old_context = $target->get_context();
            $target->set_context($this->context);
        }
        
        foreach($this->cells as $cell) {
            $cell->frontend_render($target);
        }

        if ($old_context) {
            $target->set_context($old_context);
        }
        
        $target->row_end_callback($this->get_tag() );
        
    }

    function set_layout_type( $layout_type )
    {
        $this->layout_type = $layout_type;
    }

    function get_layout_type( )
    {
        return $this->layout_type;
    }

    function get_width_of_child_layout_cell() {
        foreach ($this->cells as $cell) {
            $width = $cell->get_width_of_child_layout_cell();
            if ($width > 0) {
                return $width;
            }
        }
        return 0;
    }

    public function get_cells()
    {
        return $this->cells;
    }

    function register_strings_for_translation ($context) {
        foreach ($this->cells as $cell) {
            $cell->register_strings_for_translation($context);
        }
    }

    function set_post_slug ($slug) {
        foreach ($this->cells as $cell) {
            $cell->set_post_slug($slug);
        }
    }

    function is_row_with_child()
    {
        $bool = false;
        foreach ($this->cells as $cell)
        {
            if( $cell->get_cell_type() === 'child-layout' ){
                $bool = true;
                break;
            }
        }

        return $bool;
    }

    function find_cell_of_type( $cell_type )
    {
        $cells = $this->get_cells();

        if( count($cells) === 0 ) return false;

        foreach( $cells as $cell ){
            if( $cell->get_cell_type() == $cell_type ){
                return $cell;
            }

            if( $cell instanceof WPDD_layout_container ){
                foreach( $cell->get_rows() as $row ){
                    $ret = $row->find_cell_of_type( $cell_type );
                    if( $ret ){
                        return $ret;
                    }
                }
            }
        }

        return false;
    }

    function get_cell_by_id( $cell_id ){
        $cells = $this->get_cells();

        if( count($cells) === 0 ) return false;

        foreach( $cells as $cell ){
            if( $cell->get_unique_id () == $cell_id  ){
                return $cell;
            }

            if( $cell instanceof WPDD_layout_container ){
                foreach( $cell->get_rows() as $row ){
                    $ret = $row->find_cell_of_type( $cell_id  );
                    if( $ret ){
                        return $ret;
                    }
                }
            }
        }

        return false;
    }

    function find_cells_of_type( $cell_type )
    {
        $cells = $this->get_cells();

        if( count($cells) === 0 ) return false;

        $ret = array();

        foreach( $cells as $cell ){
            if( $cell->get_cell_type() == $cell_type ){
                $ret[] = $cell;
            }

            if( $cell instanceof WPDD_layout_container ){

                foreach( $cell->get_rows() as $row ){
                    $ret = array_merge( $ret, $row->find_cells_of_type( $cell_type ) );
                }
            }
        }

        return $ret;
    }

    //FIXME: this method is deprecated remove it.
    function find_cells_with_images(  )
    {
        $cells = $this->get_cells();

        if( count($cells) === 0 ) return false;

        foreach( $cells as $cell ){
            if( $cell->has_image() ){
                return $cell;
            }

            if( $cell instanceof WPDD_layout_container ){
                foreach( $cell->get_rows() as $row ){
                    $ret = $row->find_cells_with_images( );
                    if( $ret ){
                        return $ret;
                    }
                }
            }
        }

        return false;
    }


    function find_cells_with_content_field( $field_name )
    {
        $cells = $this->get_cells();

        if( count($cells) === 0 ) return false;

        foreach( $this->get_cells() as $cell ){
            if( $cell->get_content_field_value( $field_name ) !== null ){
                return $cell;
            }

            if( $cell instanceof WPDD_layout_container ){
                foreach( $cell->get_rows() as $row ){
                    $ret = $row->find_cells_with_content_field( $field_name );
                    if( $ret ){
                        return $ret;
                    }
                }
            }
        }

        return false;
    }
    
    function convert_sidebar_grid_for_preset() {
        
        for( $i = 0; $i < count($this->cells); $i++ ){
            $cell = $this->cells[$i];
            if( $cell instanceof WPDD_layout_container ){
                if ($cell->get_name() == 'Sidebar') {
                    $new_cell = new WPDD_layout_spacer(
                                        $cell->get_name(),
                                        $cell->get_width(),
                                        $cell->get_css_class_name(),
                                        '',
                                        true
                                       );
                    
                    $this->cells[$i] = $new_cell;
                } else {
                    foreach( $cell->get_rows() as $row ){
                        $row->convert_sidebar_grid_for_preset();
                    }
                }
            }
        }
    }



}

class WPDD_layout_cell extends WPDD_layout_element {

    private $width;
    private $content;
    private $cell_type;
    private $unique_id;

    function __construct($name,
                            $width,
                            $css_class_name = '',
                            $editor_visual_template_id = '',
                            $content = null,
                            $css_id = '',
                            $tag = 'div',
                            $unique_id = '') {

        parent::__construct($name, $css_class_name, $editor_visual_template_id, $css_id, $tag);

        $this->width = $width;
        $this->content = $content;
        $this->cell_type = null;
        $this->unique_id = $unique_id;
    }

    function set_content($content) {
        $this->content = $content;
    }

    function get_content() {
        return $this->content;
    }

    function get_width() {
        return $this->width;
    }

    function set_cell_type($cell_type) {
        $this->cell_type = $cell_type;
    }

    function get_cell_type()
    {
        return $this->cell_type;
    }

    function get_as_array() {
        $data = parent::get_as_array();

        $data['kind'] = 'Cell';
        $data['width'] = $this->width;
        $data['content'] =  $this->content;
        $data['cell_type'] = $this->cell_type;

        return $data;
    }

    function get($param) {
        if (isset($this->content[$param])) {
            return $this->content[$param];
        } else {
            return null;
        }
    }

    function frontend_render($target) {

        $target->cell_start_callback($this->get_css_class_name(), $this->width, $this->get_css_id(), $this->get_tag() );

        do_action( 'ddl_before_frontend_render_cell', $this, $target );

        $this->frontend_render_cell_content($target);

        do_action( 'ddl_after_frontend_render_cell', $this, $target );

        $target->cell_end_callback($this->get_tag());
    }

    function frontend_render_cell_content($target) {
    }

    function get_width_of_child_layout_cell() {
        return 0;
    }
    
    function get_unique_id () {
        return $this->unique_id;
    }

    //FIXME: this method is deprecated remove it.
    function has_image( ) {
        $regex = '/<img[^>]*?/siU';
        if(preg_match_all($regex, $this->get_content(), $matches, PREG_SET_ORDER)) {
            return true;
        } else {
            return false;
        }
        return false;
    }

    function get_content_field_value( $field_name ){
        $content = $this->get_content();

        if( !is_array( $content ) ) return null;

        if( !array_key_exists( $field_name, $content ) ) return null;

        if( isset( $content[$field_name] ) ) return $content[$field_name];

        return null;
    }

    function set_content_field_value($field_name, $field_value){
        $content = $this->get_content();
        $content[$field_name] = $field_value;
        $this->set_content($content);
    }
}

class WPDD_layout_container extends WPDD_layout_cell {

    private $layout;

    function __construct($name, $width, $css_class_name = '', $editor_visual_template_id = '', $css_id = '', $tag = 'div', $cssframework = 'bootstrap' ) {
        parent::__construct($name, $width, $css_class_name, $editor_visual_template_id, null, $css_id, $tag);
        $this->layout = new WPDD_layout( $width, $cssframework);
    }

    function add_row($row) {
        if ($row->get_layout_type() == 'fixed' && ($row->get_width() != $this->layout->get_width())) {
            global $wpddlayout;
            $wpddlayout->record_render_error(__('The row width is different from the layout width. This happens when the child layout does not contain the same number of columns as the child placeholder in the parent layout.', 'ddl-layouts'));
        }
        $this->layout->add_row($row);
    }

    function set_post_slug ($slug) {
        $this->layout->set_post_slug($slug);
    }

    function get_width() {
        return $this->layout->get_width();
    }


    function get_as_array() {
        $data = parent::get_as_array();

        $data['kind'] = 'Container';
        $data = array_merge($data, $this->layout->get_as_array());

        return $data;
    }

    function frontend_render_cell_content($target) {
        $this->layout->frontend_render($target);
    }

    function get_width_of_child_layout_cell() {
        return $this->layout->get_width_of_child_layout_cell();
    }

    function register_strings_for_translation ($context) {
        $this->layout->register_strings_for_translation($context);
    }

    function get_rows(){
        return $this->layout->get_rows();
    }

}

class WPDD_layout_spacer extends WPDD_layout_element {

    private $width;
    private $_preset_mode;
    private $cell_type = 'spacer';

    function __construct($name, $width, $css_class_name = '', $css_id = '', $preset_mode = false) {
        parent::__construct($name, $css_class_name, $css_id);
        $this->width = $width;
        $this->_preset_mode = $preset_mode;
    }

    function get_width() {
        return $this->width;
    }

    function get_as_array() {
        $data = parent::get_as_array();

        $data['kind'] = 'Cell';
        $data['width'] = $this->width;
        $data['cell_type'] = 'spacer';

        return $data;
    }

    function frontend_render($target) {
        if ($this->_preset_mode) {
            // render as a div for display in the preset selection on new layouts dialog.
            $target->cell_start_callback($this->get_css_class_name(), $this->width, $this->get_css_id(), 'div' );

            $target->cell_content_callback($this->get_name(), $this);

            $target->cell_end_callback('div');
        } else {
            $target->spacer_start_callback( $this->width );
        }
    }

    function get_cell_type()
    {
        return $this->cell_type;
    }

    function get_width_of_child_layout_cell() {
        return 0;
    }
}

// Cell factory class to be extended
class WPDD_layout_cell_factory {

    public function get_editor_cell_template() {
        // return an empty cell template if this function is not
        // overriden

        return '';
    }

    public function element_name($param) {
        // returns the name of the input element used in the dialog
        return 'ddl-layout-' . $param;
    }

}

// include real cell types
require_once WPDDL_CLASSES_ABSPATH . '/cell_types/wpddl.cell_cell-grid-cell.class.php';
require_once WPDDL_CLASSES_ABSPATH . '/cell_types/wpddl.cell_text.class.php';
require_once WPDDL_CLASSES_ABSPATH . '/cell_types/wpddl.cell_slider.class.php';
require_once WPDDL_CLASSES_ABSPATH . '/cell_types/wpddl.cell_video.class.php';
require_once WPDDL_CLASSES_ABSPATH . '/cell_types/wpddl.cell_comments.class.php';
//require_once WPDDL_CLASSES_ABSPATH . '/cell_types/wpddl.cell_cred_base.class.php';
require_once WPDDL_CLASSES_ABSPATH . '/cell_types/wpddl.cell_cred.class.php';
//require_once WPDDL_CLASSES_ABSPATH . '/cell_types/wpddl.cell_cred_user.class.php';
require_once WPDDL_CLASSES_ABSPATH . '/cell_types/wpddl.cell_imagebox.php';
require_once WPDDL_CLASSES_ABSPATH . '/cell_types/wpddl.missing_cell_type.class.php';

add_filter('dd_layouts_register_cell_factory', 'dd_layouts_register_container_factory');
function dd_layouts_register_container_factory($factories) {
    $factories['ddl-container'] = new WPDD_layout_container_factory;
    return $factories;
}

//require_once WPDDL_CLASSES_ABSPATH . '/cell_types/wpddl.cell_post_content.class.php';
require_once WPDDL_CLASSES_ABSPATH . '/cell_types/wpddl.cell_views_content_template.class.php';
//require_once WPDDL_CLASSES_ABSPATH . '/cell_types/wpddl.cell_post_loop.class.php';
require_once WPDDL_CLASSES_ABSPATH . '/cell_types/wpddl.cell_views_loop.class.php';

require_once WPDDL_CLASSES_ABSPATH . '/cell_types/wpddl.cell_menu.class.php';
require_once WPDDL_CLASSES_ABSPATH . '/cell_types/wpddl.cell_widget.class.php';
//require_once WPDDL_CLASSES_ABSPATH . '/cell_types/wpddl.cell_widget_area.class.php';

require_once WPDDL_CLASSES_ABSPATH . '/cell_types/wpddl.cell_views-grid-cell.class.php';
require_once WPDDL_CLASSES_ABSPATH . '/cell_types/wpddl.child_layout.class.php';

//require_once WPDDL_ABSPATH . '/reference-cell/reference-cell.php';


// Some test code

function test_layout_code() {
    $layout_test = new WPDD_layout(12);
    $row = new WPDD_layout_row('row 1');
    for ($i = 0; $i < 12; $i++) {
        $cell = new WPDD_layout_cell('cell:' . (string)($i + 1), 1);
        $row->add_cell($cell);
    }
    $layout_test->add_row($row);
    $json = $layout_test->get_json();
}

//test_layout_code();
