{if (count($forum.moderatorUsers) > 0) OR (count($forum.moderatorGroups) > 0)}
<div id='dzk_moderatedby' class="text-muted{if isset($well) && $well} well well-sm{/if}">
    <em>{gt text="Moderated by"}:</em>
{/if}

{if count($forum.moderatorUsers) > 0}
<span>
    {foreach name='moderators' item='mod' key='modid' from=$forum.moderatorUsers}
        {$mod.forumUser.user.uname|profilelinkbyuname}{if !$smarty.foreach.moderators.last}, {/if}
    {/foreach}
    {if count($forum.moderatorGroups) > 0}, {/if}
</span>
{/if}
{if count($forum.moderatorGroups) > 0}
<span>
    {foreach name='modgroups' item='group' key='id' from=$forum.moderatorGroups}
        {$group.group.name} ({gt text='Group'}){if !$smarty.foreach.modgroups.last}, {/if}
    {/foreach}
</span>
{/if}

{if (count($forum.moderatorUsers) > 0) OR (count($forum.moderatorGroups) > 0)}
</div>
{/if}