{gt text="Personal settings" assign=templatetitle}
{pagesetvar name=title value=$templatetitle}
{include file='user/header.tpl'}

<div id="dzk_userprefs">

    <h2>{gt text="Settings"}</h2>

    {modulelinks modname='Dizkus' type='prefs'}<br />

    {form cssClass="z-form"}
    {formvalidationsummary}

    <fieldset>
        <div class="z-formrow">
            {formlabel for="user_post_order" __text="Post order"}
            {formdropdownlist id="user_post_order" items=$orders}
        </div>
        {if $modvars.Dizkus.favorites_enabled eq 'yes'}
        <div class="z-formrow">
            {formlabel for="user_favorites" __text="Post order"}
            {formcheckbox id="user_favorites"}
        </div>
        {/if}
        <div class="z-formrow">
            {formlabel for="user_autosubscribe" __text="Autosubscribe to new topics"}
            {formcheckbox id="user_autosubscribe"}
        </div>

        <div class="z-formbuttons z-buttons">
            {formbutton commandName="save"   __text="Save"   class="z-bt-ok"}
            {formbutton commandName="cancel" __text="Cancel" class="z-bt-cancel"}
        </div>
    </fieldset>
    {/form}<br />
</div>

