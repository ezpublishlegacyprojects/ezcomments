{ezcss_require( 'comment.css' )}
<div class="ezcom-add-result">
  {if is_set($error_message)}
    <div class="message-error">
        <p>
            {$error_message}
        </p>
        <input type="button" onclick="history.back();" class="button" value="{'Back'|i18n( 'view/action' )}" />
    </div>
  {/if}
  {if and( is_set( $success ), $success|eq( 1 ) )}
    <div class="message-error">
        <p>
            {'Posting succeeds!'|i18n( 'extension/ezcomments/add' )}
        </p>
        <form action="{'comment/add'|ezurl}" method="get" name="CommentAdded">
            <input type="hidden" name="RedirectURI" value={$redirect_uri} />
            <input type="submit" class="button" name="BackButton" value="{'Back'|i18n( 'extension/ezcomments/action') }" />
        </form>
    </div>
  {/if}
</div>