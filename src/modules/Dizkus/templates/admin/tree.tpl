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
                {gt text='Create a new category'}
            </a>
        </li>
        <li>
            <a href="{modurl modname='Dizkus' type='admin' func='modifyforum'}" title="Create a new forum" class="z-iconlink z-icon-es-new">
                {gt text='Create a new forum'}
            </a>
        </li>
        <li>
            <a href="{modurl modname='Dizkus' type='admin' func='syncforums'}" title="Recalculate cached post and topics totals" class="z-iconlink z-icon-es-gears">
                {gt text='Recalculate cached post and topics totals'}
            </a>
        </li>
    </ul><br />

    <table class="z-admintable">
        <thead>
            <tr>
                <th width="100%">{gt text="Name"}</th>
                <th nowrap="">{gt text="Actions"}</th>
            </tr>
        </thead>
        <tbody>
        {include file='admin/subtree.tpl'}
        </tbody>

    </table>

</div>

{adminfooter}