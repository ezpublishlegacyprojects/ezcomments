<?php
/**
 * File containing ezcomComment class
 *
 * @copyright Copyright (C) 1999-2010 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPLv2
 *
 */

/**
 * ezcomComment persistent object class definition
 *
 */
class ezcomComment extends eZPersistentObject
{
    /**
     * Construct, use {@link ezcomComment::create()} to create new objects.
     *
     * @param array $row
     */
    public function __construct( $row )
    {
        parent::__construct( $row );
    }

    /**
     * Fields definition.
     *
     * @static
     * @return array
     */
    public static function definition()
    {
        static $def = array( 'fields' => array( 'id' => array( 'name' => 'ID',
                                                               'datatype' => 'integer',
                                                               'default' => 0,
                                                               'required' => true ),
                                                'language_id' => array( 'name' => 'LanguageID',
                                                                        'datatype' => 'integer',
                                                                        'default' => 0,
                                                                        'required' => true ),
                                                'created' => array( 'name' => 'Created',
                                                                    'datatype' => 'integer',
                                                                    'default' => 0,
                                                                    'required' => true ),
                                                'modified' => array( 'name' => 'Modified',
                                                                     'datatype' => 'integer',
                                                                     'default' => 0,
                                                                     'required' => true ),
                                                'user_id' => array( 'name' => 'UserID',
                                                                    'datatype' => 'integer',
                                                                    'default' => 0,
                                                                    'required' => true ),
                                                'session_key' => array( 'name' => 'SessionKey',
                                                                        'datatype' => 'string',
                                                                        'default' => '',
                                                                        'required' => true ),
                                                'ip' => array( 'name' => 'IPAddress',
                                                               'datatype' => 'string',
                                                               'default' => '',
                                                               'required' => true ),
                                                'contentobject_id' => array( 'name' => 'ContentObjectID',
                                                                             'datatype' => 'integer',
                                                                             'default' => 0,
                                                                             'required' => true ),
                                                'parent_comment_id' => array( 'name' => 'ParentCommentID',
                                                                              'datatype' => 'integer',
                                                                              'default' => 0,
                                                                              'required' => true ),
                                                'name' => array( 'name' => 'Name',
                                                                 'datatype' => 'string',
                                                                 'default' => '',
                                                                 'required' => true ),
                                                'email' => array( 'name' => 'EMail',
                                                                  'datatype' => 'string',
                                                                  'default' => '',
                                                                  'required' => true ),
                                                'url' => array( 'name' => 'URL',
                                                                'datatype' => 'string',
                                                                'default' => '',
                                                                'required' => true ),
                                                'text' => array( 'name' => 'Text',
                                                                 'datatype' => 'string',
                                                                 'default' => '',
                                                                 'required' => true ),
                                                'status' => array( 'name' => 'Status',
                                                                   'datatype' => 'integer',
                                                                   'default' => 0,
                                                                   'required' => true ),
                                                'title' => array( 'name' => 'Title',
                                                                  'datatype' => 'string',
                                                                  'default' => '',
                                                                  'required' => true ) ),
                             'keys' => array( 'id' ),
                             'function_attributes' => array(
                                                            'contentobject' => 'contentObject' ),
                             'increment_key' => 'id',
                             'class_name' => 'ezcomComment',
                             'name' => 'ezcomment' );
        return $def;
    }

    /**
     * get the contentobject of one comment
     * @return eZContentObject
     */
    public function contentObject()
    {
        if ( isset( $this->ContentObjectID ) and $this->ContentObjectID )
        {
            return eZContentObject::fetch( $this->ContentObjectID );
        }
        return null;
    }

    /**
     * Creates new ezcomComments object
     *
     * @param array $row
     * @return ezcomComment
     */
    public static function create( $row = array() )
    {
        $object = new self( $row );
        return $object;
    }

    /**
     * Fetch comment by given id.
     *
     * @param int $id
     * @return ezcomComment
     */
    static function fetch( $id )
    {
        $cond = array( 'id' => $id );
        $return = eZPersistentObject::fetchObject( self::definition(), null, $cond );
        return $return;
    }

    /**
     * fetch comment by email
     * @param string $email email address
     * @param array $sorts sort array
     * @param integer $offset offset
     * @param integer $length length
     * @param integer $status status of comment
     * @return ezcomComment|null 
     */
    static function fetchByEmail( $email, $sorts = null, $offset = null, $length = null, $status = false )
    {
        $cond = array();
        $cond['email'] = $email;
        if ( $status !== false )
        {
            $cond['status'] = $status;
        }
        $limit = null;
        if ( !is_null( $offset ) )
        {
           $limit = array();
           $limit = array( 'offset' => $offset, 'length' => $length );
        }
        $return = eZPersistentObject::fetchObjectList( self::definition(), null, $cond, $sorts, $limit );
        return $return;
    }

    /**
     * fetch comment list by contentobject id
     * @param string $contentObjectID
     * @param string $languageID
     * @param integer $status
     * @param array $sorts
     * @param integer $offset
     * @param integer $length
     * @return array comment list
     */
    static function fetchByContentObjectID( $contentObjectID, $languageID, $status = null, $sorts = null, $offset = null, $length = null )
    {
        $cond = array();
        $cond['contentobject_id'] = $contentObjectID;
        $cond['language_id'] = $languageID;
        if( !is_null( $status ) )
        {
            $cond['status'] = $status;
        }
        if ( is_null( $offset ) || is_null( $length ) )
        {
            return null;
        }
        else
        {
            $limit = array( 'offset' => $offset, 'length' => $length);
            $return = eZPersistentObject::fetchObjectList( self::definition(), null, $cond, $sorts, $limit );
            return $return;
        }
    }

    /**
     * Count the comments by content object id
     * @param integer $contentObjectID
     * @param integer $languageID
     * @param integer $status
     * @return count of comments
     */
    static function countByContent( $contentObjectID, $languageID = false, $status = null )
    {
        $cond = array();
        $cond['contentobject_id'] = $contentObjectID;
        if ( $languageID !== false )
        {
            $cond['language_id'] = $languageID;
        }
        if ( !is_null( $status ) )
        {
            $cond['status'] = $status;
        }
        return eZPersistentObject::count( self::definition(), $cond );
    }

    /**
     * Fetch the count of contentobject the user commented on
     * @param $email user's email
     * @param $status status of comment
     * @return count of contentobject with id.
     */
    static function countContentObjectByEmail( $email, $status = false )
    {
        $statusString = "";
        if ( $status !== false )
        {
            $statusString = " AND status = $status";
        }
        $sql = "SELECT COUNT(*) as row_count FROM " .
               "( SELECT DISTINCT contentobject_id, language_id ".
               " FROM ezcomment " .
               " WHERE email='$email'" .
               "$statusString" .
               ") as contentobject";
        $db = eZDB::instance();
        $result = $db->arrayQuery( $sql );
        return $result[0]['row_count'];
    }

}

?>