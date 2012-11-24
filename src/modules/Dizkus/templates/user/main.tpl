{include file='user/header.tpl'}

{if $viewcat > 0}
<h2>{$tree.0.forum_name|safetext}</h2>
{else}
<h2>{gt text="Forums index page"}</h2>
{/if}

<div id="dzk_maincategorylist">
    {foreach item='category' from=$tree}
    <div class="forabg dzk_rounded">
        <div class="inner">
            <ul class="topiclist">
                <li class="dzk_header">
                    <dl>
                        <dt>
                            <span><a id="categorylink_{$category.forum_name}" class="{*if $category.new_posts == true}newpostscategorylink{else}categorylink{/if*}" title="{gt text="Go to category"} '{$category.forum_name|safetext}'" href="{modurl modname='Dizkus' type=user func=main viewcat=$category.forum_id}">{$category.forum_name|safetext}</a></span>
                        </dt>
                        <dd class="topics"><span>{gt text="Topics"}</span></dd>
                        <dd class="posts"><span>{gt text="Posts"}</span></dd>
                        <dd class="lastpost"><span>{gt text="Last post"}</span></dd>
                    </dl>
                </li>
            </ul>

            <ul class="topiclist forums">
                {foreach item='forum' from=$category.children}
                    <li class="row">
                        <dl class="icon">
                            <dt {*if $forum.new_posts == true}class='new-posts'{else}class='no-new-posts'{/if*} >
                                <a title="{gt text="Go to forum"} '{$forum.forum_name|safetext}'" href="{modurl modname='Dizkus' type='user' func='viewforum' forum=$forum.forum_id}">{$forum.forum_name|safetext}</a><br />
                                {if $forum.forum_desc neq ''}{$forum.forum_desc|safehtml}<br />{/if}
                                {include file='user/moderatedBy.tpl' forum=$forum}
                            </dt>

                            <dd class="topics">{$forum.forum_topics|safetext}</dd>
                            <dd class="posts">{$forum.forum_posts|safetext}</dd>
                            <dd class="lastpost">
                                {if isset($forum.last_post)}
                                {include file='user/lastPostBy.tpl' last_post=$forum.last_post replies=-1}
                                {else}
                                <span></span>
                                {/if}
                            </dd>
                        </dl>
                    </li>
                    {foreachelse}
                    <li class="row dzk_empty">
                        {gt text="No forums created."}
                    </li>
                {/foreach}
            </ul>

        </div>
    </div>
    {/foreach}
</div>

{include file='user/footer.tpl'}
