<?php
/**
 * File containing the ezcomUtility class
 *
 * @copyright Copyright (C) 1999-2010 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPLv2
 *
 */

/**
 * Utility library for comment system
 *
 */
class ezcomUtility
{

    public function generateSusbcriberHashString( $subscriber )
    {
        return strtoupper( hash( 'md5', uniqid( '', true ). time() ) );
    }

    /**
     * generate the hashstring of a subscription
     * @param $input
     * @return string the hashed string
     */
    public function generateSubscriptionHashString( $subscription )
    {
        return strtoupper( hash( 'md5', uniqid( '', true ). time() ) );
    }

    /**
     * create new instance of the object
     * TODO: load the class dynamically
     * @return ezcomUtility
     */
    public static function instance()
    {
        return new ezcomUtility();
    }
}

?>