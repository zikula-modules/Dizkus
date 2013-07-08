{assign var='msgmodule' value=$modvars.ZConfig.messagemodule}
{assign var="onlinestyle" value="style=\"background-image: url(`$baseurl`modules/Dizkus/images/`$coredata.language`/icon_user_online.gif); background-position: top right; background-repeat: no-repeat;\""}


{modapifunc modname='Dizkus' type='UserData' func='getUserOnlineStatus' uid=$post.poster.user_id assign='isPosterOnline'}


{if isset($post_counter) AND isset($post_count) AND $post_counter == $post_count}<a id="bottom"></a>{/if}
<a id="pid{$post.post_id}" ></a>

{if $post3.contactlist_ignored|default:0 == 1}
<div id="hidelink_posting_{$post.post_id}" class="roundedbar dzk_rounded">
    {gt text="Show hidden postings of ignored user"} <em>{$post.poster.user_id}</em>
    <a href="javascript:void(0);" title="{gt text="Click here"}">({gt text="Click here"})</a>
</div>
{/if}

<div id="posting_{$post.post_id}" class="forum_post dzk_rounded {cycle values='post_bg1,post_bg2'}" {if $isPosterOnline}{$onlinestyle}{/if}>
    <div class="inner">

        <div class="dzk_subcols z-clearfix">
            <div id="posting_{$post.post_id}_userinfo" class="post_author dzk_colpost_left">
                <div class="dzk_avatar">
                    <strong>{$post.poster.user_id|profilelinkbyuid}</strong>
                    <br />
                    <p>{$post.poster.user.uname}</p>{* TODO: this is temp to show the data is here w/o another DB call *}
                    <p>{$post.poster.user.email}</p>{* TODO: this is temp to show the data is here w/o another DB call *}
                    {useravatar uid=$post.poster.user.uid}

                    {if !empty($post.poster.rank.rank_image)}
                        <br />
                        {if $post.poster.rank.rank_link neq ''}
                        <a href="{$post.poster.rank.rank_link}" title="{$post.poster.rank.rank_link}">
                        {/if}
                        <img class="userinforankimage" src="{$baseurl}{$post.poster.rank.rank_image}" alt="{$post.poster.rank.rank_title}" title="{$post.poster.rank.rank_desc}" />
                        {if $post.poster.rank.rank_link neq ''}</a>{/if}
                    {else}
                        {getRankByPostCount posts=$post.poster.user_posts ranks=$ranks assign='posterRank'}
                        {if $posterRank.rank_link neq ''}
                        <a href="{$posterRank.rank_link}" title="{$posterRank.rank_link}">
                        {/if}
                        {if $posterRank.rank_image neq ''}
                        <img class="userinforankimage" src="{$baseurl}{$posterRank.rank_image}" alt="{$posterRank.rank_title}" title="{$posterRank.rank_desc}" />
                        {/if}
                        {if $posterRank.rank_link neq ''}</a>{/if}
                    {/if}
                </div>

                <ul>
                    {if !empty($post.poster.rank.rank_title)}
                    <li><strong>{gt text="Rank"}: </strong>{$post.poster.rank.rank_title|safetext}</li>
                    {else}
                    <li><strong>{gt text="Rank"}: </strong>{$posterRank.rank_title|safetext}</li>
                    {/if}
                    {usergetvar name='user_regdate' assign="user_regdate"}
                    <li><strong>{gt text="Registered"}: </strong>{$user_regdate|dateformat:'datebrief'}</li>
                    {if !$isPosterOnline}
                    <li><strong>{gt text="Last visit"}: </strong>{$post.poster.user_lastvisit|dateformat:'datebrief'}</li>
                    {/if}


                    <li><strong>{gt text="Posts"}: </strong>{$post.poster.user_posts}</li>
                    {if $coredata.logged_in eq true}
                    <li>
                        {* image link to profile deactivated because of a bug in the core *}
                        {* $post.user_data.uname|profilelinkbyuname:'':"`$baseurl`modules/Dizkus/images/icon_post_profile.gif" *}
                        {if $msgmodule}
                        <a href="{modurl modname=$msgmodule func="user" func="newpm" uid=$post.poster.user_ui}">{img modname='Dizkus' src='icon_post_pn.gif' __alt='Send a private message'}</a>
                        {/if}
                        {if isset($topic) AND $post.poster_data.moderate eq true AND $post.poster_data.seeip eq true}
                        <a title="{gt text="View IP address"}" href="{modurl modname='Dizkus' type='user' func='viewIpData' post=$post.post_id}">{img modname='Dizkus' src='icon_post_ip.gif' __alt='View IP address'}</a>
                        {/if}
                        &nbsp;
                    </li>
                    {/if}
                </ul>
            </div>

            <div class="postbody dzk_colpost_right">
                <div class="postinfo">
                    {if isset($topic)}<a class="linktopostlink tooltips" href="{modurl modname='Dizkus' type='user' func='viewtopic' topic=$post.topic_id start=$start}#pid{$post.post_id}" title="{gt text="Link to this post"}">{img modname='Dizkus' src='target.gif' __alt='Link to this post'}</a>{/if}
                    <strong>{gt text="Posted"}: </strong>{$post.post_time|dateformat:'datetimebrief'}
                </div>
                <div class="content" id="postingtext_{$post.post_id}">
                    {$post.post_text|safehtml|notifyfilters:'dizkus.filter_hooks.post.filter'}

                    {if $post.post_attach_signature}
                    {usergetvar name='signature' assign="signature"}
                    {if !empty($signature)}
                        <em>
                            <br /><br />--<br />
                            {$signature|safehtml|notifyfilters:'dizkus.filter_hooks.post.filter'}
                        </em>
                    {/if}
                    {/if}

                </div>
                {notifydisplayhooks eventname='dizkus.ui_hooks.post.ui_view' id=$post.post_id}
            </div>

            <div class="postlink">
                {if !isset($preview) OR $preview neq true}
                <div class="dzk_colpost_right">
                    <ul id="postingoptions_{$post.post_id}" class="javascriptpostingoptions linklist z-clearfix" style="float:right;">
                        {if $permissions.moderate eq true}
                        {if (isset($num) AND $num neq 0) OR (isset($topic) AND $start neq 0)}
                        <li><a class="movepostlink tooltips" title="{gt text="Move post"}" href="{modurl modname='Dizkus' type='user' func='movepost' post=$post.post_id}">{img modname='Dizkus' src='icon_post_move.gif' __alt='Move post' }</a></li>
                        <li><a class="splittopiclink tooltips" title="{gt text="Split topic"}" href="{modurl modname='Dizkus' type='user' func='splittopic' post=$post.post_id}">{img modname='Dizkus' src='icon_post_split.gif' __alt='Split topic' }</a></li>
                        {/if}
                        {/if}

                        {if isset($topic) AND $topic.topic_status neq 1}
                        {if $permissions.comment eq true}
                        <li>
                            <a class="quotepostlink tooltips" id="quotebutton_{$post.post_id}" title="{gt text="Quote post or selection"}" onclick="quote('{dzkquote text=$post.post_text|htmlentities uid=$post.poster.user_id}');">{img modname='Dizkus' src='icon_post_quote.gif' __alt='Quote'}</a>
                        </li>
                        {/if}
                        {if $permissions.edit eq 1}
                        <li><a class="editpostlink tooltips" id="editbutton_{$post.post_id}" title="{gt text="Edit post"}" href="{modurl modname='Dizkus' type='user' func='editpost' post=$post.post_id}">{img modname='Dizkus' src='icon_post_edit.gif' __alt='Edit'}</a></li>
                        {/if}
                        {elseif isset($topic)}
                        <li>{img modname='Dizkus' src="icon_post_close.gif" class="tooltips" __alt="Topic locked" }</li>
                        {/if}
                        <li><a class="notifymoderatorlink tooltips" href="{modurl modname='Dizkus' type='user' func='report' post=$post.post_id}" title="{gt text="Notify moderator about this posting"}">{img modname='Dizkus' src='icon_topic_mod.gif' __alt='Notify moderator' }</a></li>
                        <li><a class="dzk_notextdecoration tooltips" title="{gt text="Top"}" href="#top">&nbsp;{img modname='Dizkus' src="icon_up.gif" __alt="Top" }</a></li>
                    </ul>
                </div>
                {/if}
            </div>
        </div>

    </div>
</div>

{*if $post.contactlist_ignored|default:0 == 1}
<script type="text/javascript">
    // <![CDATA[
    $('posting_{{$post.post_id}}').toggle();
    // ]]>
</script>
{/if*}
