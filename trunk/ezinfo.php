<?php
/**
 * File containing ezcomCommentsInfo class
 *
 * @copyright Copyright (C) 1999-2010 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPLv2
 *
 */

class eZCommentsInfo
{
    static function info()
    {
        return array(
            'Name' => "eZ Comments",
            'Version' => "1.0.0Beta2",
            'Copyright' => 'Copyright (C) 1999-' . date('Y') . ' eZ Systems AS',
            'License' => 'GNU General Public License v2.0',
        );
    }
}
?>
