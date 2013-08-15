/**
 * Zikula.Dizkus.User.ViewForum.js
 * 
 * jQuery based JS
 */

jQuery(document).ready(function () {
    // toogle forum favorite state
    jQuery("#forum-favourite").click(modifyForum);
//    jQuery("#forum-subscription").click(modifyForum);
});

function modifyForum(e) {
    var id = jQuery('#forum_id').val(), action, i = jQuery(this);
    if (i.text() == favouriteForum) {
        action = 'addToFavorites';
    } else if (i.text() == unfavouriteForum) {
        action = 'removeFromFavorites';
    } else if (i.text() == subscribeForum) {
        action = 'subscribe';
    } else if (i.text() == unsubscribeForum) {
        action = 'unsubscribe';
    }


    this.favouritestatus = true;
    var pars = {
        forum: id,
        action: action
    }
    //Ajax.Responders.register(this.dzk_globalhandlers);

    jQuery.ajax({
        type: "POST",
        data: pars,
        url: Zikula.Config.baseURL + "ajax.php?module=Dizkus&type=ajax&func=modifyForum",
        success: function (result) {
            if (result == 'successful') {
                if (action == 'addToFavorites') {
                    i.text(unfavouriteForum);
                } else if (action == 'removeFromFavorites') {
                    i.text(favouriteForum);
                } else if (action == 'subscribe') {
                    i.text(unsubscribeForum);
                } else if (action == 'unsubscribe') {
                    i.text(subscribeForum);
                }
            } else {
                alert('Error! Erroneous result from favourite addition/removal.');
            }
        },
        error: function (result) {
            DizkusShowAjaxError(result);
            return;
        }
    });
    e.preventDefault();
}