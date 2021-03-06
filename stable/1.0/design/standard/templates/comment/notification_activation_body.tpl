{* DO NOT EDIT THIS FILE! Use an override template instead. *}
{'Dear %email,'|i18n( 'ezcomments/comment/activationnotification', , hash( '%email', $subscriber.email ) )} 
<br />
<br />
<p>
{'You have subscribed to comment update on content. Before you start the service, you need to confirm the subscription.'|i18n( 'ezcomments/comment/activationnotification' )}
 <br />
 <h4>"{$contentobject.name}"</h4>
 <a href={$contentobject.main_node.url_alias|ezurl( , 'full' )}>
    {$contentobject.main_node.url_alias|ezurl( 'no', 'full' )}
 </a>
</p>
<p>
{'Please click the link to activate.'|i18n( 'ezcomments/comment/activationnotification' )}
<a href={concat( '/comment/activate/', $subscription.hash_string )|ezurl( , 'full' )}>
    {concat( '/comment/activate/', $subscription.hash_string )|ezurl( 'no' ,'full' )}
</a>
</p>
    {def $expiry_days=ezini( 'NotificationSettings', 'DaysToCleanupSubscription', 'ezcomments.ini' )}
        {if $expiry_days|eq( '-1' )|not}
            <p>
                {if $expiry_days|ge( 1 )}
                    {'The activation will expire after %expiry_days days.'|i18n( 'ezcomments/comment/activationnotification', , hash( '%expiry_days', $expiry_days ) )}
                {else}
                    {'The activation will expire after %expiry_hours hours.'|i18n( 'ezcomments/comment/activationnotification', , hash( '%expiry_hours', $expiry_days|mul( 24 ) ) )}
                {/if}
            </p>
        {/if}
    {undef $expiry_days}
<p>
    {'If you do not want to receive the subscription email, ignore this email.'|i18n( 'ezcomments/comment/activationnotification' )}
</p>
<p>
    {'You can go to your setting page to manage your subscription.'|i18n( 'ezcomments/comment/activationnotification' )}
</p>