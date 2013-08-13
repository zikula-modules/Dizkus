/**
 * Zikula.Dizkus.Admin.ManageSubscriptions.js
 *
 * JQUERY based JS
 */

jQuery(document).ready(function () {
    jQuery('#username').autocomplete({
        serviceUrl: Zikula.Config.baseURL + "ajax.php?module=Dizkus&type=ajax&func=getUsers",
        paramName: 'fragment',
        onSelect: function (suggestion) {
            console.log(suggestion);
            window.location.href = Zikula.Config.baseURL + "index.php?module=Dizkus&type=admin&func=managesubscriptions&uid=" + suggestion.data;
        }
    });
});