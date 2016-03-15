<?php
/**
 * Represents a single WordPress Archive
 *
 * @since 1.8
 */
class WPV_WordPress_Archive_Embedded extends WPV_View_Base {

    /**
     * See parent class constructor description.
     *
     * @param int|WP_Post $wpa WPA post object or ID.
     */
    public function __construct( $wpa ) {
        parent::__construct( $wpa );
    }


    /**
     * @var array Default postmeta for the WPA.
     *
     * Note that this should contain all postmeta keys a WPA can have (if they're not generic).
     *
     * @todo Add missing default values.
     * @todo Add description to default values.
     */
    protected static $postmeta_defaults = array();


    /**
     * @return array Default postmeta for the WPA.
     */
    protected function get_postmeta_defaults() {
        return WPV_WordPress_Archive_Embedded::$postmeta_defaults;
    }


    /* ************************************************************************* *\
            Custom methods
    \* ************************************************************************* */


    /**
     * Get information about loops that have this WPA assigned as archive template.
     *
     * Take a look at $WPV_view_archive_loop->get_archive_loops() for better understanding of what is being returned.
     *
     * @param string $loop_type Relevant loop type. Possible values are the same as for $WPV_view_archive_loop->get_archive_loops().
     *
     * @return array Array of loop information.
     */
    public function get_assigned_loops( $loop_type = 'all' ) {

        global $WPV_view_archive_loop;
        $loops = $WPV_view_archive_loop->get_archive_loops( $loop_type, true );

        $selected_loops = array();
        foreach( $loops as $loop ) {
            if( $loop['wpa'] == $this->id ) {
                $selected_loops[] = $loop;
            }
        }

        return $selected_loops;
    }
}