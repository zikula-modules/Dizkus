{assign var='templatetitle' value=$topic.topic_title}
{include file='user/header.tpl' parent=$topic.forum.forum_id}

<input id="topic_id" type="hidden" value={$topic.topic_id}>
{pageaddvar name='javascript' value='modules/Dizkus/javascript/dizkus_user_viewtopic.js'}

<script type="text/javascript">
    function quote(text) {
        $('message').setValue($('message').getValue()+text);
        $('message').scrollTo();
    }
</script>

<h2>
<span class="editabletopicheader" id="edittopicsubjectbutton" title="">
    <span id="topic_solved" {if !$topic.solved or !$modvars.Dizkus.solved_enabled}class="z-hide"{/if}>
        {gt text="[Solved]"}
    </span>
    <span id="topic_title">{$topic.topic_title|safetext}</span>
</span>
<a class="dzk_notextdecoration" title="{gt text="Bottom"}" href="#bottom">&nbsp;{img modname='Dizkus' src="icon_bottom.gif" __alt="Bottom"}</a>
</h2>

{* add inline edit *}
{usergetvar name='uid' assign='currentUser'}
{if $permissions.moderate eq 1 || $topic.topic_poster eq $currentUser}
{include file='ajax/edittopicsubject.tpl'}
<script type="text/javascript">
    jQuery('#edittopicsubjectbutton').addClass('tooltips').attr('title', '{{gt text="Click to edit"}}');
    jQuery('#edittopicsubjectbutton').click(function() {jQuery('#topicsubjectedit_editor').removeClass('z-hide')});
    jQuery('#topicsubjectedit_cancel').click(function() {jQuery('#topicsubjectedit_editor').addClass('z-hide')});
    jQuery("#topicsubjectedit_save").click(changeTopicTitle);

</script>
{/if}


{userloggedin assign='userloggedin'}

<div class="dzk_topicoptions roundedbar dzk_rounded" id="topic_{$topic.topic_id}">
    <div class="inner">
        <div id="dzk_javascriptareatopic">
            <ul class="dzk_topicoptions linklist z-clearfix">
                {*if $topic.prev_topic_id and $topic.topic_id neq $topic.prev_topic_id }
                <li><a class="dzk_arrow previoustopiclink tooltips" title="{gt text="Previous topic"}" href="{modurl modname='Dizkus' type=user func=viewtopic topic=$topic.prev_topic_id}">&nbsp;</a></li>
                {/if*}

                {if $permissions.comment}
                <li><a class="dzk_arrow newtopiclink tooltips" title="{gt text="Create a new topic"}" href="{modurl modname='Dizkus' type=user func=newtopic forum=$topic.forum.forum_id}">{gt text="New topic"}</a></li>
                {/if}

                {if $userloggedin}
                <li><a class="dzk_arrow mailtolink tooltips" title="{gt text="Send the posts within this topic as an e-mail message to someone"}" href="{modurl modname='Dizkus' type=user func=emailtopic topic=$topic.topic_id}">{gt text="Send as e-mail"}</a></li>
                {/if}

                {*<li>{printtopic_button topic_id=$topic.topic_id cat_id=$topic.cat_id forum_id=$topic.forum.forum_id}*}</li>

                {if $userloggedin}
                <li>
                    {if $isSubscribed}
                        {modurl modname='Dizkus' type='user' func='changeTopicStatus' action='unsubscribe' topic=$topic.topic_id assign='url'}
                        {gt text="Unsubscribe from topic" assign='msg'}
                    {else}
                        {modurl modname='Dizkus' type='user' func='changeTopicStatus' action='subscribe' topic=$topic.topic_id assign='url'}
                        {gt text="Subscribe to topic" assign='msg'}
                    {/if}
                    <a id="toggletopicsubscription" class="dzk_arrow tooltips" href="{$url}" title="{$msg}">{$msg}</a>
                </li>
                {if $modvars.Dizkus.solved_enabled|default:0}
                <li>
                    {if $topic.solved}
                        {modurl modname='Dizkus' type='user' func='changeTopicStatus' action='unsolve' topic=$topic.topic_id assign='url'}
                        {gt text="Mark as unsolved" assign='msg'}
                        {else}
                        {modurl modname='Dizkus' type='user' func='changeTopicStatus' action='solve' topic=$topic.topic_id assign='url'}
                        {gt text="Mark as solved" assign='msg'}
                    {/if}
                    <a id="toggletopicsolve" class="dzk_arrow tooltips" href="{$url}" title="{$msg}">{$msg}</a>
                </li>
                {/if}
                {/if}

                {*if $topic.next_topic_id and $topic.topic_id neq $topic.next_topic_id}
                <li>
                    <a class="dzk_arrow nexttopiclink tooltips" title="{gt text="Next topic"}" href="{modurl modname='Dizkus' type=user func=viewtopic topic=$topic.next_topic_id}">
                        &nbsp;
                    </a>
                </li>
                {/if*}
            </ul>

            {if $permissions.moderate eq 1}
            <ul class="dzk_topicoptions linklist z-clearfix">
                <li>
                    {if $topic.topic_status eq 0}
                        {modurl modname='Dizkus' type='user' func='changeTopicStatus' action='lock' topic=$topic.topic_id assign='url'}
                        {gt text="Lock topic" assign='msg'}
                    {else}
                        {modurl modname='Dizkus' type='user' func='changeTopicStatus' action='unlock' topic=$topic.topic_id assign='url'}
                        {gt text="Unlock topic" assign='msg'}
                    {/if}
                    <a id="toggletopiclock" class="dzk_arrow tooltips" title="{$msg}" href="{$url}">{$msg}</a>
                </li>

                <li>
                    {if $topic.sticky eq 0}
                        {modurl modname='Dizkus' type='user' func='changeTopicStatus' action='sticky' topic=$topic.topic_id assign='url'}
                        {gt text="Give this topic 'sticky' status" assign='msg'}
                        {else}
                        {modurl modname='Dizkus' type='user' func='changeTopicStatus' action='unsticky' topic=$topic.topic_id assign='url'}
                        {gt text="Remove 'sticky' status" assign='msg'}
                    {/if}
                    <a id="toggletopicsticky" class="dzk_arrow tooltips" title="{$msg}" href="{$url}">{$msg}</a>
                </li>

                <li><a class="dzk_arrow movetopiclink tooltips" title="{gt text="Move topic"}" href="{modurl modname='Dizkus' type=user func=movetopic topic=$topic.topic_id}">{gt text="Move topic"}</a></li>
                <li><a class="dzk_arrow deletetopiclink tooltips" title="{gt text="Delete topic"}" href="{modurl modname='Dizkus' type=user func=deletetopic topic=$topic.topic_id}">{gt text="Delete topic"}</a></li>
            </ul>
            {/if}
        </div>

    </div>
</div>

{pager show='post' rowcount=$pager.numitems limit=$pager.itemsperpage posvar='start'}

<div id="dzk_postinglist">
    <ul>
        {counter start=0 print=false assign='post_counter'}
        {foreach key='num' item='post' from=$posts}
        {counter}
        <li class="post_{$post.post_id}">
            {include file='user/post/single.tpl'}
        </li>
        {/foreach}
        <li id="quickreplyposting" class="hidden">&nbsp;</li>
        <li id="quickreplypreview" class="hidden">&nbsp;</li>
    </ul>
</div>

{pager show='post' rowcount=$pager.numitems limit=$pager.itemsperpage posvar='start'}

{if ($topic.topic_status neq 1) and ($permissions.comment eq true)}
<a id="reply">
<div id="dzk_quickreply" class="forum_post {cycle values='post_bg1,post_bg2'} dzk_rounded">
    <div class="inner">
        <div class="dzk_subcols z-clearfix">
            <form id="quickreplyform" class="dzk_form" action="{modurl modname='Dizkus' type='user' func='reply'}" method="post" enctype="multipart/form-data">
                <div>
                    <input type="hidden" id="forum" name="forum" value="{$topic.forum.forum_id}" />
                    <input type="hidden" id="topic" name="topic" value="{$topic.topic_id}" />
                    <input type="hidden" id="quote" name="quote" value="" />
                    <input type="hidden" id="authid" name="authid" value="" />
                    <div class="post_header">
                        <label for="message" class="quickreply_title" style="display:block;">{gt text="Quick reply"}</label>
                    </div>
                    <div class="post_text_wrap">
                        <div class="post_text">
                            <div id="dizkusinformation"></div>
                            {notifydisplayhooks eventname='dizkus.ui_hooks.editor.display_view' id='message'}
                            <textarea id="message" name="message" cols="10" rows="60"></textarea>

                            {if isset($hooks.MediaAttach)}{$hooks.MediaAttach}{/if}
                            {if $modvars.Dizkus.striptags == 'yes'}
                            <p>{gt text="No HTML tags allowed (except inside [code][/code] tags)"}</p>
                            {/if}

                            <div class="dzk_subcols z-clearfix">
                                <div id="quickreplyoptions" class="dzk_col_left">
                                    <ul>
                                        <li><strong>{gt text="Options"}</strong></li>
                                        {if $userloggedin}
                                        <li>
                                            <input type="checkbox" id="attach_signature" name="attach_signature" checked="checked" value="1" />
                                            <label for="attach_signature">{gt text="Attach my signature"}</label>
                                        </li>
                                        <li>
                                            <input type="checkbox" id="subscribe_topic" name="subscribe_topic" checked="checked" value="1" />
                                            <label for="subscribe_topic">{gt text="Notify me when a reply is posted"}</label>
                                        </li>
                                        {/if}
                                        <li id="quickreplybuttons" class="z-buttons">
                                            <input class="z-bt-ok z-bt-small" type="submit" name="submit" value="{gt text="Submit"}" />
                                            <input class="z-bt-preview z-bt-small" type="submit" name="preview" value="{gt text="Preview"}" />
                                            {button type="button" id="btnCancelQuickReply" class="dzk_detachable z-bt-small hidden" src=button_cancel.png set=icons/extrasmall __alt="Cancel" __title="Cancel" __text="Cancel"}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="post_footer"></div>
                </div>
            </form>
        </div>

    </div>
</div>

{mediaattach_fileuploads objectid=$topic.topic_id}
<div id="dzk_displayhooks">
    {if isset($hooks.Ratings)}{$hooks.Ratings}{/if}
</div>

{/if}


{include file='user/moderatedBy.tpl' forum=$topic.forum}

<script type="text/javascript">
    // <![CDATA[
    var storingReply = "{{gt text='Storing reply...'}}";
    var preparingPreview = "{{gt text='Preparing preview...'}}";
    var storingPost = "{{gt text='Storing post...'}}";
    var deletingPost = "{{gt text='Deleting post...'}}";
    var updatingPost = "{{gt text='Updating post...'}}";
    var statusNotChanged = "{{gt text='Unchanged'}}";
    var statusChanged = "{{gt text='Changed'}}";
    var subscribeTopic = "{{gt text='Subscribe to topic'}}";
    var unsubscribeTopic = "{{gt text='Unsubscribe from topic'}}";
    var lockTopic = "{{gt text='Lock topic'}}";
    var unlockTopic = "{{gt text='Unlock topic'}}";
    var stickyTopic = "{{gt text="Give this topic 'sticky' status"}}";
    var unstickyTopic = "{{gt text="Remove 'sticky' status"}}";
    var solveTopic = "{{gt text="Mark as solved"}}";
    var unsolveTopic = "{{gt text="Mark as unsolved"}}";
    // ]]>


    /*
    ajax overwriting
    $$('.editpostlink').each(
        function (e) {
            e.observe('click', myHandler);
        }
    );

    function myHandler(event) {
        Event.stop(event);
        alert('test');
    };*/

</script>

{include file='user/footer.tpl'}