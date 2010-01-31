<?php
//
// Created on: <11-Jan-2010 15:56:00 xc>
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ Comments extension for eZ Publish
// SOFTWARE RELEASE: 1.0-1
// COPYRIGHT NOTICE: Copyright (C) 2010 eZ Systems AS
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
require_once( 'kernel/common/template.php' );
$tpl = templateInit();
$Module = $Params['Module'];
if( $Module->isCurrentAction( 'DeleteComment' ) )
{
    $http = eZHTTPTool::instance();
    $commentID = null;
    if( $http->hasPostVariable( 'CommentID' ) )
    {
        $commentID = $http->postVariable( 'CommentID' );
    }
    if( !is_numeric( $commentID ) )
    {
        eZDebug::writeError( 'The parameter CommentID is not a number!', 'ezcomments' );
        return;
    }
    $tpl = templateInit();
    $comment = ezcomComment::fetch( $commentID );
    $permissionResult = checkPermission( $comment );
    if( $permissionResult !== true )
    {
        $tpl->setVariable( 'error_message', $permissionResult );
        return showDeleteForm( $tpl, $commentID );
    }
    else
    {
        //execute deleting
        $commentManager = ezcomCommentManager::instance();
        $deleteResult = $commentManager->deleteComment( $comment );
        if( $deleteResult === true )
        {
            //clean up cache
            eZContentCacheManager::clearContentCache( $comment->attribute( 'contentobject_id' ) );

            $redirectURI = null;
            if ( $http->hasPostVariable( "RedirectURI" ) )
            {
                $redirectURI = $http->postVariable( 'RedirectURI' );
            }
            //todo: deal with the case that there is no last Access URI
            return $Module->redirectTo( $redirectURI );
        }
        else
        {
            $tpl->setVariable( 'error_message', ezi18n( 'extension/ezcomments/delete', 'Deleting failed!' ) );
            return showDeleteForm( $tpl, $commentID );
        }
    }
}
else if( $Module->isCurrentAction( 'Cancel' ) )
{
     $http = eZHTTPTool::instance();
     $redirectURI = null;
     if ( $http->hasPostVariable( "RedirectURI" ) )
     {
         $redirectURI = $http->postVariable( 'RedirectURI' );
     }
     //todo: deal with the case that there is no last Access URI
     return $Module->redirectTo( $redirectURI );
     return;
}
else
{
    $commentID = $Params['CommentID'];
    $comment = ezcomComment::fetch( $commentID );
    $permissionResult = checkPermission( $comment );
    if( $permissionResult !== true )
    {
        $tpl->setVariable( 'error_message', $permissionResult );
    }
    return showDeleteForm( $tpl, $commentID );
}

function checkPermission( $comment )
{
    // check permission
    $contentObject = $comment->contentObject();
    $contentNode = $contentObject->mainNode();
    $languageID = $comment->attribute( 'language_id' );
    $languageCode = eZContentLanguage::fetch( $languageID )->attribute( 'locale' );
    $canDeleteResult = ezcomPermission::hasAccessToFunction( 'delete', $contentObject, $languageCode, $comment, null, $contentNode );

    $objectAttributes = $contentObject->fetchDataMap( false, $languageCode );
    $objectAttribute = null;
    foreach( $objectAttributes as $attribute )
    {
        if( $attribute->attribute( 'data_type_string' ) === 'ezcomcomments' )
        {
            $objectAttribute = $attribute;
            break;
        }
    }
    $commentContent = $objectAttribute->content();
    if( !$canDeleteResult['result'] || !$commentContent['show_comments'] )
    {
        return ezi18n( 'extension/comment/delete', 'You don\'t have '.
                                                    ' the permission to delete comment ' .
                                                    ' or the showing comment function is disabled!' );
    }
    else
    {
        return true;
    }
}

function showDeleteForm( $tpl, $commentID )
{
    if( is_null( $commentID ) || $commentID == '' )
    {
        eZDebug::writeError( 'No comment id', 'Delete comment' );
        return;
    }
    if( !is_numeric( $commentID ) )
    {
        eZDebug::writeError( 'Comment id is not a number!', 'Delete comment' );
        return;
    }
    $tpl->setVariable( 'comment_id', $commentID );
    $redirectURI = null;
    $http = eZHTTPTool::instance();
    if ( $http->hasSessionVariable( "LastAccessesURI" ) )
    {
        $redirectURI = $http->sessionVariable( 'LastAccessesURI' );
    }
    if( is_null( $redirectURI ) )
    {
        //todo: handle the redirectURI
    }
    $tpl->setVariable( 'redirect_uri', $redirectURI );
    $Result = array();
    $Result['path'] = array( array( 'url' => false,
                            'text' => ezi18n( 'extension/ezcomments/delete', 'Delete comment' ) ) );
    $Result['content'] = $tpl->fetch( 'design:comment/delete.tpl' );
    return $Result;
}
?>