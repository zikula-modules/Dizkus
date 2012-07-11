{ajaxheader modname='Dizkus'}
{pageaddvar name='javascript' value='modules/Dizkus/javascript/dizkus_tools.js'}
{pageaddvar name='javascript' value='modules/Dizkus/javascript/dizkus_admin.js'}
{adminheader}
<div class="z-admin-content-pagetitle">
    {icon type="options" size="small"}
    <h3>{gt text="Forum tree"}</h3>
</div>

<div id="dizkus_admin">


    <ul class="z-menulinks">
        <li>
            <a href="{modurl modname='Dizkus' type='admin' func='modifycategory'}" title="Create a new category" class="z-iconlink z-icon-es-new">
                Create a new category
            </a>
        </li>
        <li>
            <a href="{modurl modname='Dizkus' type='admin' func='modifyforum'}" title="Create a new forum" class="z-iconlink z-icon-es-new">
                Create a new forum
            </a>
        </li>
        <li>
            <a href="{modurl modname='Dizkus' type='admin' func='syncforums'}" title="Synchronize forum and topic indexes to fix any discrepancies that might exist" class="z-iconlink z-icon-es-gears">
                Synchronize forum/topic index
            </a>
        </li>
    </ul><br />

    <table class="z-admintable">
        <thead>
            <tr>
                <th width="100%">{gt text="Name"}</th>
                <th>{gt text="Actions"}</th>
            </tr>
        </thead>
        <tbody>
            {foreach item='category' from=$tree name='foo'}
                <tr class="{cycle values='z-odd,z-even'}">
                    <td>
                        <a href="{modurl modname='Dizkus' type='user' func='viewforum'}">{$category.name|safetext}</a>
                    </td>
                    <td nowrap>
                        <a href="{modurl modname='Dizkus' type='admin' func='modifycategory' id=$category.id}">
                            {img modname=core set=icons/extrasmall src=xedit.png __alt="Edit"}
                        </a>
                        <a href="{modurl modname='Dizkus' type='user' func='main' viewcat=$category.id}">
                            {img modname=core set=icons/extrasmall src=demo.png __alt="Show"}
                        </a>
                        {if $smarty.foreach.foo.first}
                            <a href="{modurl modname='Dizkus' type='admin' func='changeCatagoryOrder' id=$category.id action='decrease'}" style="margin-left:20px">
                                {img modname=core set=icons/extrasmall src=down.png __alt="Down"}
                            </a>
                        {else}
                            <a href="{modurl modname='Dizkus' type='admin' func='changeCatagoryOrder' id=$category.id action='increase'}">
                                {img modname=core set=icons/extrasmall src=up.png __alt="Up"}
                            </a>
                            {if !$smarty.foreach.foo.last}
                            <a href="{modurl modname='Dizkus' type='admin' func='changeCatagoryOrder' id=$category.id action='decrease'}">
                                {img modname=core set=icons/extrasmall src=down.png __alt="Down"}
                            </a>
                            {/if}
                        {/if}
                    </td>
                </tr>
                {if count($category.subforums) > 0}
                    {assign var='margin2' value=20}
                    {include file="admin/subtree.tpl" forums=$category.subforums margin=$margin2}
                {/if}
            {foreachelse}
                <tr class="z-admintableempty">
                    <td colspan="4">
                        {gt text="No sub forums available"}
                    </td>
                </tr>
            {/foreach}
        </tbody>

    </table>

</div>

{adminfooter}