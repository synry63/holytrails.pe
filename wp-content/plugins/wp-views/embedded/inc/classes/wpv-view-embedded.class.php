<?php

/**
 * Represents a single View.
 *
 * The embedded version of the wrapper. Concentrates on getters and get_ methods only.
 *
 * @since 1.8
 */
class WPV_View_Embedded extends WPV_View_Base {


    /**
     * See parent class constructor description.
     *
     * @param int|WP_Post $view View post object or ID.
     */
    public function __construct( $view ) {
        parent::__construct( $view );
    }


    /**
     * @var array Default postmeta for the View.
     *
     * Note that this should contain all postmeta keys a View can have (if they're not generic).
     *
     * @todo Add missing default values.
     * @todo Add description to default values.
     */
    protected static $postmeta_defaults = array(
        WPV_View_Base::POSTMETA_DESCRIPTION => ''
    );


    /**
     * @return array Default postmeta for the View.
     */
    protected function get_postmeta_defaults() {
        return WPV_View_Embedded::$postmeta_defaults;
    }



    /* ************************************************************************* *\
            Custom getters
    \* ************************************************************************* */


    /**
     * @var null|string Cache for the content_summary property.
     */
    private $content_summary_cache = null;


    /**
     * @return string "Content summary" of a View, stating what and how it will show.
     */
    protected function _get_content_summary() {
        if( empty( $this->content_summary_cache ) ) {
            $this->content_summary_cache = sanitize_text_field(
                apply_filters( 'wpv-view-get-content-summary', '', $this->object_id, $this->settings )
            );
        }
        return $this->content_summary_cache;
    }


}