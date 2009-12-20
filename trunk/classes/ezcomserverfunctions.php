<?php
//
// Definition of ezsrServerFunctions class
//
// Created on: <06-Dec-2009 00:00:00 xc>
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ Comments
// SOFTWARE RELEASE: 1.0-0
// COPYRIGHT NOTICE: Copyright (C) 2009 eZ Systems AS
// SOFTWARE LICENSE: GNU General Public License v2.0
// NOTICE: >
//   This program is free software; you can redistribute it and/or
//   modify it under the terms of version 2.0  of the GNU General
//   Public License as published by the Free Software Foundation.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of version 2.0 of the GNU General
//   Public License along with this program; if not, write to the Free
//   Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
//   MA 02110-1301, USA.
//
//
// ## END COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
//

/*
 * ezjscServerFunctions for ezcomments
 */

class ezcomServerFunctions extends ezjscServerFunctions
{
    /**
     * create an error object that will be used for client use
     * @param string $message : message
     * @param string $errorCode : error code defined, for instance com_01
     * @return array : error object 
     */
    protected static function createErrorObject( $message, $errorCode = null )
    {
        $error = array();
        $error['type'] = 'ezcomments_error';
        $error['message'] = $message;
        if( !is_null( $errorCode ) )
        {
            $error['code'] = $errorCode;
        }
        return json_encode( $error );
    }
    
    /**
     * Get the comment list in view 'notification'.
     * Return format:
     * ===========================================
     * comments:
     * -------------------------------------------
     * id, contentobject_id, notification, comment text, object name
     * 5,   12,               1, This is a comment, ezpublish 4.3 released!
     * -------------------------------------------
     * total_count: 125
     * ===========================================
     * @return JSON object
     * 
     */
    public static function get_notification_comment_list( $args )
    {
        $http = eZHTTPTool::instance();
        $offset = null;
        $length = null;
        $userID = null;
        $argObject = array();
        
        $ezcommentsINI = eZINI::instance( 'ezcomments.ini' );
        //1. check the permission
        
        //2. check user
        
        if( $http->hasPostVariable( 'args' ) )
        {
            $args = $http->postVariable( 'args' );
            $argObject = json_decode($args);
        }
            
        //3. check offset
        $defaultNumPerPage = $ezcommentsINI->variable( 'notificationSettings', 'NumberPerPage' );
        if( $defaultNumPerPage != '-1' )
        {
            if ( isset( $argObject->offset ) )
            {
                $offset = $argObject->offset;
            }
            else
            {
                $offset = 0;
            }
            //4. check countPerPage
            if ( isset( $argObject->length ) )
            {
                $length = $argObject->length;
            }
            else
            {
                $length = $defaultNumPerPage;
            }
        }
        
        //5. fetch comment
        $comments = null;
        $countArray = null;
        $defaultSortField = $ezcommentsINI->variable( 'notificationSettings', 'DefaultSortField' );
        $defaultSortOrder = $ezcommentsINI->variable( 'notificationSettings', 'DefaultSortOrder' );
        $sorts = array( $defaultSortField=>$defaultSortOrder );
        if ( isset( $argObject->hashString ) && ( $argObject->hashString !== "" ) )
        {
            $subscriber = ezcomSubscriber::fetchByHashString( $argObject->hashString );
            $email = $subscriber->attribute( 'email' );
            
            $comments = ezcomComment::fetchByEmail( $email, $sorts, $offset, $length );
            $db = eZDB::instance();
            $countArray = $db->arrayQuery( 'SELECT count(*) AS count FROM ezcomment '.
                                           ' WHERE email =\''.$email . '\'');
        }
        else
        {
            $userID = eZUser::currentUserID();
            if ( $userID != 0 )
            {
                $comments = ezcomComment::fetchForUser( $userID, $sorts, $offset, $length );
                $db = eZDB::instance();
                $countArray = $db->arrayQuery( 'SELECT count(*) AS count FROM ezcomment '.
                                               ' WHERE  user_id =' . $userID );
            }
            else
            {
                return null;
            }
        }
        
        $totalCount = $countArray[0]['count'];
        
        //6. build JSON object and return
        $result = array();
        
        if( !is_null( $comments ) && is_array( $comments ) )
        {
            $resultComments = array();
            foreach( $comments as $comment )
            {
                $row = array();
                $contentobject_id = $comment->attribute( 'contentobject_id' );
                $contentObject = eZContentObject::fetch( $contentobject_id );
                $objectName =  $contentObject -> attribute( 'name' );
                $row['id'] = $comment->attribute( 'id' );
                $row['contentobject_id'] = $contentobject_id;
                $row['content_url'] = $contentObject->mainNode()->attribute( 'url_alias' );
                $row['notification'] = $comment->attribute('notification');
                $row['text'] = nl2br( htmlspecialchars( $comment->attribute('text') ) );
                $row['object_name'] = htmlspecialchars( $objectName );
                $local = eZLocale::instance();
                $time = $comment->attribute('created');
                $row['time'] = $local->formatShortDateTime($time);
                $resultComments[] = $row;
            }
            
            $result['comments'] = $resultComments;
            $result['total_count'] = $totalCount;
            $result = json_encode( $result );

        }
        else
        {
            $result = null;
        }
        return $result;
    }
    
    /**
     * 
     * @param $args: get args
     * @return string: update result message
     */
    public static function update_notification_comment( $args )
    {
        $http = eZHTTPTool::instance();
        
        //1. check user
        $user = eZUser::currentUser();
        $email = $user->attribute('email');
        //2. get parameters
        $argObject = null;
        if( $http->hasPostVariable( 'args' ) )
        {
            $argsString = $http->postVariable( 'args' );
            $argObject = json_decode( $argsString, true ); 
        }
        $message = null;
        
        //3. buid update parameters and execute update
        $fields = array();
        $conditions = array();
        $updateResult = true;
        $message = "";
        
        foreach( $argObject as $row )
        {
            $fields['notification'] = $row['notification'];
            $conditions['id'] = $row['id'];
            ezcomComment::updateFields( $fields, $conditions );
            ezcomSubscription::cleanupSubscription( $email, null, $row['id']);
        }
        
        //4. return result
        if ( $updateResult )
        {
            $message .= "Update success";
        }
        else
        {
            $message = "Update error";
        }
        return $message;
    }
    
    /**
     * update the notification setting.
     * 
     * Format of $recNotifications
     * =============================
     * id, notification
     * 5, 0
     * 6, 1
     * 8, 0
     * =============================
     * 
     * @param JSON object $recNotifications
     * @return boolean succeed/failed
     */
    public static function set_notification_setting( $recNotifications )
    {
        
    }
    
    /**
     * Get the default settings in ini file.
     * @return unknown_type
     */
    public static function get_default_settings()
    {
        
    }
    
    public static function get_view_comment_list()
    {
        $http = eZHTTPTool::instance();
        $offset = null;
        $length = null;
        $contentobject_id = null;
        $argObject = array();
        
        $ezcommentsINI = eZINI::instance( 'ezcomments.ini' );
        //1. check the permission
        
        //2. check user
        
        if( $http->hasPostVariable( 'args' ) )
        {
            $args = $http->postVariable( 'args' );
            $argObject = json_decode($args);
        }
//        if ( isset( $argObject->user_id ) )
//        {
//            $userID = $argObject->user_id;
//        }
//        else
//        {
//            $userID = eZUser::currentUserID();
//        }
            
        //3. check offset
        $defaultNumPerPage = $ezcommentsINI->variable( 'commentSettings', 'NumberPerPage' );
        if( $defaultNumPerPage != '-1' )
        {
            if ( isset( $argObject->offset ) )
            {
                $offset = $argObject->offset;
            }
            else
            {
                $offset = 0;
            }
            //4. check countPerPage
            if ( isset( $argObject->length ) )
            {
                $length = $argObject->length;
            }
            else
            {
                $length = $defaultNumPerPage;
            }
        }
        if( !isset($argObject->oid) )
        {
            return null;
        }
        else if(!is_int($argObject->oid))
        {
            return null;
        }
        else
        {
            $contentobjectID = $argObject->oid;
            $defaultSortField = $ezcommentsINI->variable( 'commentSettings', 'DefaultSortField' );
            $defaultSortOrder = $ezcommentsINI->variable( 'commentSettings', 'DefaultSortOrder' );
            $sorts = array( $defaultSortField=>$defaultSortOrder );
            $comments = ezcomComment::fetchByContentObjectID( $contentobjectID, $sorts, $offset, $length);
            $db = eZDB::instance();
            $countArray = $db->arrayQuery( 'SELECT count(*) AS count FROM ezcomment WHERE contentobject_id ='.$contentobjectID );
            $totalCount = $countArray[0]['count'];
            
            $result = array();
            if( $comments == null )
            {
                return null;
            }
            else
            {
                $resultComments = array();
                foreach ( $comments as $comment )
                {
                    $row = array();
                    $row['id'] = $comment->attribute( 'id' );
                    $row['oid'] = $comment->attribute( 'contentobject_id' );
                    $local = eZLocale::instance();
                    $modified = $comment->attribute( 'modified' );
                    $row['modified'] = $local->formatShortDateTime( $modified );
                    $created = $comment->attribute( 'created' );
                    $row['created'] = $local->formatShortDateTime( $created );
                    $row['title'] = htmlspecialchars( $comment->attribute( 'title' ) );
                    $row['text'] = nl2br( htmlspecialchars( $comment->attribute( 'text' ) ) );
                    $row['author'] = $comment->attribute( 'name' );
                    $row['userid'] = $comment->attribute( 'user_id' );
                    $row['website'] = htmlspecialchars( $comment->attribute( 'url' ) );
                    $resultComments[] = $row;
                }
                $result['comments'] = $resultComments;
                $result['total_count'] = $totalCount;
                return json_encode($result);
            }
        }
    }
    
    
    /**
     * Add comment into database
     * @param array $args
     * @return string result
     */
    public static function add_comment($args)
    {
        //1.get user
        $user = eZUser::currentUser();
        //2. vertify data
        $argObject = null;
        $http = eZHTTPTool::instance();
        if( $http->hasPostVariable( 'args' ) )
        {
           $args = $http->postVariable( 'args' );
           $argObject = json_decode($args);
        }
        else
        {
            return self::createErrorObject( 'Parameter doesn\'t exist!', 'ezcom_add_001' );
        }
        if( is_null( $argObject ) )
        {
            return self::createErrorObject( 'Parameter is empty!', 'ezcom_add_002');
        }
        if( !isset( $argObject->name ) ||  $argObject->name == '' )
        {
            return self::createErrorObject( 'Name is empty!', 'ezcom_add_002');
        }
        if( isset( $argObject->email ) )
        {
            if( eZMail::validate( $argObject->email ) == false )
            {
                return self::createErrorObject( 'Not a valid email address', 'ezcom_add_002' );
            }
        }
        if( !isset( $argObject->content) || $argObject->content == '' )
        {
            return self::createErrorObject( 'Content can not be empty', 'ezcom_add_002' );
        }
        if ( !isset( $argObject->language ) || $argObject->language == '' || !is_int( $argObject->language ) )
        {
            return self::createErrorObject( 'Language can not be empty or string', 'ezcom_add_002' );
        }
        if ( !isset( $argObject->oid ) || $argObject->oid == '' || !is_int( $argObject->oid ) )
        {
            return self::createErrorObject( 'Object ID can not be empty or string', 'ezcom_add_002' );
        }
        $contentObjectID =  $argObject->oid;
        
        //3. insert data
        //3.1 convert data and insert into comment table
        $comment = ezcomComment::create();
        if( isset( $argObject->title ) )
        {
            $comment->setAttribute( 'title', $argObject->title );
        }
        $comment->setAttribute( 'name', $argObject->name );
        if( isset( $argObject->website ) )
        {
            $comment->setAttribute( 'url', $argObject->website );
        }
        $comment->setAttribute( 'language_id', $argObject->language);
        $comment->setAttribute( 'email', $argObject->email );
        $comment->setAttribute( 'text', $argObject->content );
        $comment->setAttribute( 'user_id', $user->attribute( 'contentobject_id' ) );
        $comment->setAttribute( 'contentobject_id', $contentObjectID);
        $currentTime = time();
        $comment->setAttribute( 'created', $currentTime);
        $comment->setAttribute( 'modified', $currentTime);
        if( $argObject->notified === true )
        {
            $comment->setAttribute( 'notification', 1 );
        }
        else
        {
            $comment->setAttribute( 'notification', 0 );
        }
        $comment->store();
        $commentAdded = ezcomComment::fetchByTime( 'created', $currentTime);
        
        $languageID = $argObject->language;
        $contentID = $contentObjectID . '_' . $languageID;
        $subscriptionType = 'ezcomcomment';
        
        $hasSubscription = false;
        $subscriptionMessage = "";
        if( $argObject->notified === true )
        {
            //3.2 insert into subscriber
            $ezcommentsINI = eZINI::instance( 'ezcomments.ini' );
            $subscriber = ezcomSubscriber::fetchByEmail( $argObject->email );
            //if there is no data in subscriber for same email, save it
            if( is_null( $subscriber ) )
            {
                $subscriber = ezcomSubscriber::create();
                $subscriber->setAttribute( 'user_id', $userID);
                $subscriber->setAttribute( 'email', $argObject->email );
                if( $user->isAnonymous() )
                {
                    $subscriber->setAttribute( 'hash_string', hash('md5',uniqid()) );
                }
                $subscriber->store();
                $subscriber = ezcomSubscriber::fetchByEmail( $argObject->email );
            }
            else
            {
                if( $subscriber->attribute( 'enabled' ) === 0 )
                {
                    $subscriptionMessage = 'The email is disabled,
                                 you won\'t receive notification
                                  until it is activated!';
                }
            }
            //3.3 insert into subscription table
            // if there is no data in ezcomment_subscription with given contentobject_id and subscriber_id
            $hasSubscription = ezcomSubscription::exists( $contentID,
                                            $argObject->email,
                                            $subscriptionType);
            if( $hasSubscription === false )
            {
                $subscription = ezcomSubscription::create();
                $subscription->setAttribute( 'user_id', $userID );
                $subscription->setAttribute( 'subscriber_id', $subscriber->attribute( 'id' ) );
                $subscription->setAttribute( 'subscription_type', "ezcomcomment" );
                $subscription->setAttribute( 'content_id', $contentID );
                $subscription->setAttribute( 'subscription_time', $currentTime );
                $subscription->store();
            }
        }
        
        // 3.4 insert data into notification queue
        // and there is subscription,not adding to notification queue
        if( ezcomSubscription::exists( $contentID, null,$subscriptionType ) )
        {
            $notification = ezcomNotification::create();
            $notification->setAttribute( 'contentobject_id', $contentObjectID );
            $notification->setAttribute( 'language_id', $languageID );
            $notification->setAttribute( 'comment_id', $commentAdded->attribute('id') );
            $notification->store();
        }
         
        return 'Comment added!' . $subscriptionMessage;
    }

}