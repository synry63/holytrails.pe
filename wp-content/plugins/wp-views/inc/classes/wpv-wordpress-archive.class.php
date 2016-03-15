<?php

final class WPV_WordPress_Archive extends WPV_WordPress_Archive_Embedded {


    /* ************************************************************************* *\
        Constructor
    \* ************************************************************************* */


    /**
     * See parent class constructor description.
     *
     * @param int|WP_Post $wpa WPA post object or ID.
     */
    public function __construct( $wpa ) {
        parent::__construct( $wpa );
    }


}