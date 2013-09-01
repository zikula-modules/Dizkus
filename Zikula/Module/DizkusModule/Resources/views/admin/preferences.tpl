{adminheader}
<div class="z-admin-content-pagetitle">
    {icon type="config" size="small"}
    <h3>{gt text="Settings"}</h3>
</div>

<div id="dizkus_admin">

    {form cssClass="z-form"}
    {formvalidationsummary}

    <fieldset>
        <legend>{gt text="General settings"}</legend>
        <div class="z-formrow">
            {formlabel for="forum_enabled" __text='Forums are accessible to visitors'}
            {formcheckbox id="forum_enabled"}
        </div>
        <p class="z-formnote z-informationmsg">
            {gt text="If the 'Forums are accessible to visitors' setting is deactivated then only administrators will have access to the forums. You can temporarily deactivate this setting to take the forums off-line when you need to perform maintenance."}
        </p>
        <div class="z-formrow">
            {formlabel for="forum_disabled_info" __text="Message displayed if forums are disabled"}
            {formtextinput id="forum_disabled_info" textMode="multiline" rows="3" cols="40" text=$modvars.Dizkus.forum_disabled_info}
        </div>
        <div class="z-formrow">
            {formlabel for="indexTo" __text="Redirect forum index to forum id"}
            {formtextinput id="indexTo" text=$modvars.Dizkus.indexTo size="5" maxLength="10"}
        </div>
        <p class="z-formnote z-informationmsg">
            {gt text="Leave blank to use standard forum index."}
        </p>
        <div class="z-formrow">
            {formlabel for="email_from" __text="Sender address for e-mail messages from forums"}
            {formemailinput id="email_from" text=$modvars.Dizkus.email_from size="30" maxLength="100"}
        </div>
        <div class="z-formrow">
            {formlabel for="hot_threshold" __text="'Hot topic' threshold (default: 20)"}
            {formintinput id="hot_threshold" text=$modvars.Dizkus.hot_threshold size="3" maxLength="3" minValue=2 maxValue=100}
        </div>
        <div class="z-formrow">
            {formlabel for="posts_per_page" __text="Posts per page in topic index (default: 15)"}
            {formintinput id="posts_per_page" text=$modvars.Dizkus.posts_per_page size="3" maxLength="3" minValue=1 maxValue=100}
        </div>
        <div class="z-formrow">
            {formlabel for="topics_per_page" __text="Topics per page in forum index (default: 15)"}
            {formintinput id="topics_per_page" text=$modvars.Dizkus.topics_per_page size="3" maxLength="3" minValue=5 maxValue=100}
        </div>
        <div class="z-formrow">
            {formlabel for="url_ranks_images" __text="Path to rank images"}
            {formtextinput id="url_ranks_images" text=$modvars.Dizkus.url_ranks_images size="30" maxLength="100"}
        </div>
        <div class="z-formrow">
            {formlabel for="ajax" __text="Enable user-side ajax"}
            {formcheckbox id="ajax"}
        </div>
        <div class="z-formrow">
            {formlabel for="solved_enabled" __text="Enable solved option in topics"}
            {formcheckbox id="solved_enabled"}
        </div>
    </fieldset>

    <fieldset>
        <legend>{gt text="Forum Search settings"}</legend>
        {* fulltext disabled until technology available
        <div class="z-formrow">
        {formlabel for="fulltextindex" __text="Enable full-text index field searching"}
        {formcheckbox id="fulltextindex"}
        <p class="z-formnote z-informationmsg">{gt text="Notice: For searches with full-text index fields, you need MySQL 4 or later; the feature does not work with InnoDB databases. This flag will normally be set during installation, when the index fields have been created. Search results may be empty if the query string is present in too many postings. This is a feature of MySQL. For more information, see <a href=\"http://dev.mysql.com/doc/mysql/en/fulltext-search.html\" title=\"Full-text search in MySQL\">'Full-text search in MySQL'</a> in the MySQL documentation."}</p>
        </div>
        <div class="z-formrow">
        {formlabel for="extendedsearch" __text="Enable extended full-text search in internal search"}
        {formcheckbox id="extendedsearch"}
        <p class="z-formnote z-informationmsg">{gt text="Notice: Extended full-text searching enables queries like '+Dizkus -Skype' to find posts that contain 'Dizkus' but not 'Skype'. Requires MySQL 4.01 or later. For more information, see <a href=\"http://dev.mysql.com/doc/mysql/en/fulltext-boolean.html\" title=\"Extended full-text search in MySQL\">'Full-text search in MySQL'</a> in the MySQL documentation."}</p>
        </div>
        *}
        <div class="z-formrow">
            {formlabel for="showtextinsearchresults" __text="Show text in search results"}
            {formcheckbox id="showtextinsearchresults"}
            <p class="z-formnote z-informationmsg">{gt text="Notice: Deactivate the 'Show text in search results' setting for high-volume sites if you need to improve search performance, or if you need to be attentive to constant cleaning of the search results table."}</p>
        </div>
        <div class="z-formrow">
            {formlabel for="minsearchlength" __text="Minimum number of characters in search query string (1 minimum)"}
            {formintinput id="minsearchlength" text=$modvars.Dizkus.minsearchlength size="2" maxLength="2" minValue=1 maxValue=50}
        </div>

        <div class="z-formrow">
            {formlabel for="maxsearchlength" __text="Maximum number of characters in search query string (50 maximum)"}
            {formintinput id="maxsearchlength" text=$modvars.Dizkus.maxsearchlength size="2" maxLength="2" minValue=1 maxValue=50}
        </div>
    </fieldset>

    <fieldset>
        <legend>{gt text="User-related settings"}</legend>
        <div class="z-formrow">
            {formlabel for="post_sort_order" __text="Default sort order for posts"}
            {formdropdownlist id="post_sort_order" items=$post_sort_order_options selectedValue=$modvars.Dizkus.post_sort_order}
        </div>
        <div class="z-formrow">
            {formlabel for="signature_start" __text="Beginning of signature"}
            {formtextinput id="signature_start" textMode="multiline" rows="3" cols="40" text=$modvars.Dizkus.signature_start|default:''}
        </div>
        <div class="z-formrow">
            {formlabel for="signature_end" __text="End of signature"}
            {formtextinput id="signature_end" textMode="multiline" rows="3" cols="40" text=$modvars.Dizkus.signature_end|default:''}
        </div>
        <div class="z-formrow">
            {formlabel for="signaturemanagement" __text="Enable signature management via forum user settings"}
            {formcheckbox id="signaturemanagement"}
        </div>
        <div class="z-formrow">
            {formlabel for="removesignature" __text="Strip user signatures from posts"}
            {formcheckbox id="removesignature"}
        </div>
    </fieldset>

    <fieldset>
        <legend>{gt text="Security settings"}</legend>
        <div class="z-formrow">
            {formlabel for="log_ip" __text="Log IP addresses"}
            {formcheckbox id="log_ip"}
        </div>
        <div class="z-formrow">
            {formlabel for="striptags" __text="Strip HTML tags from new posts"}
            {formcheckbox id="striptags"}
            <p class="z-formnote z-informationmsg">{gt text="Notice: Setting 'Strip HTML tags from new posts' to enabled does not affect the content of '[code][/code]' BBCode tags."}</p>
        </div>

        <div class="z-formrow">
            {formlabel for="timespanforchanges" __text="Number of hours during which non-moderators are allowed to edit their post (leave blank for unlimited)"}
            <span>
                {formintinput id="timespanforchanges" text=$modvars.Dizkus.timespanforchanges size="3" maxLength="3"}
                {gt text="hours"}
            </span>
        </div>
        <div class="z-formrow">
            {formlabel for="striptagsfromemail" __text="Strip HTML tags from outgoing email post content"}
            {formcheckbox id="striptagsfromemail"}
            <p class="z-formnote z-informationmsg">{gt text="Strip action occurs post filter hook action."}</p>
        </div>
        <div class="z-formrow">
            {formlabel for="notifyAdminAsMod" __text="Admin to notify with Moderator notifications"}
            {formdropdownlist id="notifyAdminAsMod" items=$admins selectedValue=$modvars.Dizkus.notifyAdminAsMod}
        </div>
    </fieldset>

    <fieldset>
        <legend>{gt text="Other settings"}</legend>
        <div class="z-formrow">
            {formlabel for="m2f_enabled" __text="Enable Mail2Forum"}
            {formcheckbox id="m2f_enabled" disabled=true}
        </div>
        <div class="z-formrow">
            {formlabel for="rss2f_enabled" __text="Enable RSS2Forum"}
            {formcheckbox id="rss2f_enabled" disabled=true}
        </div>
        <div class="z-formrow">
            {formlabel for="favorites_enabled" __text="Enable favourites"}
            {formcheckbox id="favorites_enabled"}
        </div>
        <div class="z-formrow">
            {formlabel for="deletehookaction" __text="Action to be performed when 'delete' hook is called"}
            {formdropdownlist id="deletehookaction" items=$deletehook_options selectedValue=$modvars.Dizkus.deletehookaction}
        </div>
    </fieldset>

    <div class="z-formbuttons z-buttons">
        {formbutton id="submit" commandName="submit" __text="Save" class="z-bt-ok"}
        {formbutton id="restore" commandName="restore" __text="Restore defaults" class="z-bt-delete"}
    </div>

    {/form}

</div>

{adminfooter}