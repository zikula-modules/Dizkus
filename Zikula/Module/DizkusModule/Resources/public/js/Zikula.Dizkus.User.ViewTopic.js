/**
 * Zikula.Dizkus.User.ViewTopic.js
 *
 * jQuery based JS
 */

jQuery(document).ready(function() {
    jQuery("#toggletopiclock").click(changeTopicStatus);
    jQuery("#toggletopicsticky").click(changeTopicStatus);
    jQuery("#toggletopicsubscription").click(changeTopicStatus);
    jQuery("#toggletopicsolve").click(changeTopicStatus);
    // POST EDIT
    hookEditLinks();

    // QUICK REPLY
    hookQuickReplySubmit();
    hookQuickReplyPreview();
    hookQuickReplyCancel();

    // Show cancel button.
    jQuery('#btnCancelQuickReply').show();

    jQuery('a.disabled').click(function(e) {
        e.preventDefault();
        //do other stuff when a click happens
    }).hover(function(){
        jQuery(this).css('cursor','not-allowed');
    } , function(){
        jQuery(this).css('cursor','default');
    });
    // toggle visibility of edit icon for topic title
    jQuery('#edittopicsubjectbutton').hover(
        function() {
            if (typeof jQuery('#userAllowedToEdit').val() !== "undefined") {
                jQuery('#edittopicicon').show();
            }
        },
        function() {jQuery('#edittopicicon').hide();}
    );
    if (typeof jQuery('#userAllowedToEdit').val() !== "undefined") {
        jQuery('#edittopicsubjectbutton').addClass('editabletopicheader tooltips').attr('title', clickToEdit).tooltip();
        jQuery('#edittopicsubjectbutton').click(function() { jQuery('#topicsubjectedit_editor').show(); });
        jQuery('#topicsubjectedit_cancel').click(function() { jQuery('#topicsubjectedit_editor').hide(); });
        jQuery("#topicsubjectedit_save").click(changeTopicTitle);
    }
});

function changeTopicStatus(e) {
    var action;
    var i = jQuery(this);
    switch(i.attr('id')) {
        case "toggletopiclock":
            action = i.data('status') == 0 ? 'lock' : 'unlock';
            break;
        case "toggletopicsticky":
            action = i.data('status') == 0 ? 'sticky' : 'unsticky';
            break;
        case "toggletopicsubscription":
            action = i.data('status') == 0 ? 'subscribe' : 'unsubscribe';
            break;
        case "toggletopicsolve":
            action = i.data('status') == 0 ? 'solve' : 'unsolve';
            break;
        default:
            console.log('Wrong action');
            return;
    }

    jQuery.ajax({
        type: "POST",
        data: {
            topic: jQuery('#topic_id').val(),
            action: action
        },
        url: Zikula.Config.baseURL + "index.php?module=ZikulaDizkusModule&type=ajax&func=changeTopicStatus",
        success: function(result) {
            if (result == 'successful') {
                if (action == 'lock') {
                    i.attr('title', unlockTopic).removeClass('icon-lock').addClass('icon-unlock');
                    jQuery('#dzk_quickreply').hide("slow"); // hide quickly reply
                } else if (action == 'unlock') {
                    i.attr('title', lockTopic).removeClass('icon-unlock').addClass('icon-lock');
                    jQuery('#dzk_quickreply').show("slow"); // show quickly reply
                } else if (action == 'sticky') {
                    i.attr('title', unstickyTopic).empty().html(unstickyTopicIcon);
                } else if (action == 'unsticky') {
                    i.attr('title', stickyTopic).empty().html(stickyTopicIcon);
                } else if (action == 'subscribe') {
                    i.attr('title', unsubscribeTopic).empty().html(unsubscribeTopicIcon);
                } else if (action == 'unsubscribe') {
                    i.attr('title', subscribeTopic).empty().html(subscribeTopicIcon);
                } else if (action == 'solve') {
                    i.attr('title', unsolveTopic).empty().html(unsolveTopicIcon);
                    jQuery('#topic_solved').show();
                } else if (action == 'unsolve') {
                    i.attr('title', solveTopic).empty().html(solveTopicIcon);
                    jQuery('#topic_solved').hide();
                }
                // invert data-status value
                i.data('status', i.data('status') == 0 ? 1 : 0);
                // destroy and recreate tooltip
                i.tooltip('destroy').tooltip();
            } else {
                console.log(result);
                alert('Error! Erroneous result from locking/unlocking action.');
            }
        },
        error: function(result) {
            DizkusShowAjaxError(result);
            return;
        }
    });
    e.preventDefault();
}


function changeTopicTitle(e) {

    jQuery.ajax({
        type: "POST",
        data: {
            topic: jQuery('#topic_id').val(),
            title: jQuery('#topicsubjectedit_subject').val(),
            userAllowedToEdit: jQuery('#userAllowedToEdit').val(),
            action: 'setTitle'
        },
        url: Zikula.Config.baseURL + "index.php?module=ZikulaDizkusModule&type=ajax&func=changeTopicStatus",
        success: function(result) {
            if (result == 'successful') {
                jQuery('#topicsubjectedit_editor').addClass('z-hide');
                jQuery('#topic_title').text(jQuery('#topicsubjectedit_subject').val());
            } else {
                console.log(result);
                alert('Error! Erroneous result when attempting to change topic title.');
            }
        },
        error: function(result) {
            DizkusShowAjaxError(result);
            return;
        }
    });
    e.preventDefault();
}

/**
 * Quote a text.
 *
 * @param text
 */
function quote(text) {
    text = text.replace(/_____LINEFEED_DIZKUS_____/g, "\n");

    jQuery('#message').val(jQuery('#message').val() + text);

    scrollTo("#dzk_quickreply");
}

// "Hook" into links / buttons

/**
 * Hook into the post edit links and use ajax instead.
 */
function hookEditLinks() {
    function editPostHandler(event) {
        event.preventDefault();
        var postId = jQuery(event.currentTarget).data('post');
        quickEdit(postId);
    }

    jQuery('.editpostlink').each(
            function() {
                jQuery(this).click(editPostHandler);
            }
    );

}


/**
 * Hook into submit quick reply button and use ajax instead.
 */
function hookQuickReplySubmit() {
    function submitQuickReplyHandler(event) {
        event.preventDefault();
        if (typeof Scribite !== 'undefined') {
            Scribite.renderAllElements();
        }
        createQuickReply();
    }

    jQuery('#btnSubmitQuickReply').each(
            function() {
                jQuery(this).click(submitQuickReplyHandler);
            }
    );

}

/**
 * Hook into preview quick reply button and use ajax instead.
 */
function hookQuickReplyPreview() {
    function previewQuickReplyHandler(event) {
        event.preventDefault();
        previewQuickReply();
    }

    jQuery('#btnPreviewQuickReply').each(
        function() {
            jQuery(this).click(previewQuickReplyHandler);
        }
    );
}

/**
 * Hook into cancel quick reply button.
 */
function hookQuickReplyCancel() {
    function cancelQuickReplyHandler(event) {
        event.preventDefault();
        cancelQuickReply();
    }

    jQuery('#btnCancelQuickReply').each(
        function() {
            jQuery(this).click(cancelQuickReplyHandler);
        }
    );
}


// Quick edit features

/**
 * True if a post is currently edited.
 * @type {boolean}
 */
var postEditing = false;

/**
 * False as long as the user has not changed the post.
 * @type {boolean}
 */
var postEditingChanged = false;

/**
 * The post id of the post currently edited.
 *
 * This is false if no post is edited at the moment.
 */
var postId = false;

/**
 * Shows an ajax indicator for a post or a quick reply.
 * spinner icon is located in the template so text is appended then .show() parent div
 * @param text The text to show next to the icon.
 * @param postId If set, the ajax indicator will be shown for a post, else for a quick reply.
 */
function showAjaxIndicator(text, postId) {
    if (postId) {
        text = '<span id="ajaxindicatortext_' + postId + '">&nbsp;' + text + '</span>';
        jQuery('#dizkusinformation_' + postId).append(text).show();
    } else {
        text = '<span id="ajaxindicatortext_-1">&nbsp;' + text + '</span>';
        jQuery('#dizkusinformation_-1').append(text).show();
    }
}

/**
 * Hides an ajax indicator for a post or a quick reply.
 * removes appended text then .hide() the parent div
 * @param postId If set, the ajax indicator will be hidden for a post, else for a quick reply.
 */
function hideAjaxIndicator(postId) {
    if (postId) {
        jQuery('#ajaxindicatortext_' + postId).remove();
        jQuery('#dizkusinformation_' + postId).hide();
    } else {
        jQuery('#ajaxindicatortext_-1').remove();
        jQuery('#dizkusinformation_-1').hide();
    }
}

/**
 * Edit a post.
 *
 * @param id The post id.
 */
function quickEdit(id) {
    var successHandler = function(result, message, request) {
        // Hide post footer
        jQuery('#postingoptions_' + postId).hide();
        // Overwrite posting text.
        jQuery('#postingtext_' + postId).hide().after(result.data);

        // Hide quickreply
        jQuery('#dzk_quickreply').fadeOut();

        // Observe buttons
        jQuery('#postingtext_' + postId + '_edit').keyup(quickEditChanged);
        jQuery('#postingtext_' + postId + '_save').click(quickEditSave);
        jQuery('#postingtext_' + postId + '_cancel').click(quickEditCancel);

    }, errorHandler = function(request, message, detail) {
        postEditing = false;
        postId = false;
        DizkusShowAjaxError(request.responseText);
    };

    if (!postEditing) {
        postEditing = true;
        postEditingChanged = false;
        postId = id;

        jQuery.ajax({
            data: {post: postId},
            url: Zikula.Config.baseURL + 'index.php?module=ZikulaDizkusModule&type=ajax&func=editpost'
        }).done(successHandler).fail(errorHandler).always(function() {
            hideAjaxIndicator(postId);
        });
        showAjaxIndicator(zLoadingPost+'...', postId);
    }
}

/**
 * Tell the user that he has changed the text.
 */
function quickEditChanged() {
    if (!postEditingChanged) {
        postEditingChanged = true;
        jQuery('#postingtext_' + postId + '_status').html('<span style="color: red;">' + zChanged + '</span>');
    }
}

/**
 * Save edited post.
 */
function quickEditSave() {
    var newPostMsg = jQuery('#postingtext_' + postId + '_edit').val(),
            pars = {
        postId: postId,
        message: newPostMsg,
        attach_signature: (jQuery('#postingtext_' + postId + '_attach_signature').prop('checked')) ? 1 : 0,
        delete_post: 0 /* Do not use 'delete' here, this is a reserved word. */
    };

    if (!newPostMsg) {
        // no text
        return;
    }

    if (jQuery('#postingtext_' + postId + '_delete').prop('checked')) {
        jQuery('#postingtext_' + postId + '_status').html('<span style="color: red;">' + zDeletingPost + '...</span>');
        pars['delete_post'] = 1;
    } else {
        jQuery('#postingtext_' + postId + '_status').html('<span style="color: red;">' + zUpdatingPost + '...</span>');
    }

    var successHandler = function(result, message, request) {
        var action = result.data.action,
                redirect = result.data.redirect,
                newText = result.data.newText;

        postEditing = false;
        postEditingChanged = false;

        // Remove editor.
        jQuery('#postingtext_' + postId + '_editor').remove();

        if (action === 'deleted') {
            // Remove post
            jQuery('#posting_' + postId).fadeOut();
        } else if (action === 'topic_deleted') {
            // Remove post
            jQuery('#posting_' + postId).fadeOut();
            // Redirect to overview url.
            window.setTimeout("window.location.href='" + redirect + "';", 500);
            return;
        } else {
            // Insert new text.
            jQuery('#postingtext_' + postId).html(newText).show();
        }

        // Show quickreply
        jQuery('#dzk_quickreply').fadeIn();

        // Show post footer
        jQuery('#postingoptions_' + postId).show();
    }, errorHandler = function(request, message, detail) {
        DizkusShowAjaxError(request.responseText);
    };
    jQuery.ajax({
        data: pars,
        url: Zikula.Config.baseURL + 'index.php?module=ZikulaDizkusModule&type=ajax&func=updatepost'
    }).done(successHandler).fail(errorHandler);
}

/**
 * Cancel editing a post.
 */
function quickEditCancel() {
    // Show post footer
    jQuery('#postingoptions_' + postId).show();

    // Show post text
    jQuery('#postingtext_' + postId).show();

    // Show quickreply
    jQuery('#dzk_quickreply').fadeIn();

    // Remove post editor
    jQuery('#postingtext_' + postId + '_editor').remove();

    postEditing = false;
    postEditingChanged = false;
}


// Quick reply features.

/**
 * True if the user is in the quick reply process.
 * @type {boolean}
 */
var quickReplying = false;

/**
 * Saves and shows the new post.
 * @returns {boolean} Used to not to submit the normal, non-ajax form.
 */
function createQuickReply() {
    if (!quickReplying) {
        var message = jQuery('#message').val();
        if (!message) {
            return false;
        }

        quickReplying = true;
        var pars = {
            topic: jQuery('#topic').val(),
            message: message,
            attach_signature: jQuery('#attach_signature').prop('checked') ? 1 : 0,
            subscribe_topic: jQuery('#subscribe_topic').prop('checked') ? 1 : 0,
            preview: 0
        };

        var successHandler = function(result, message, request) {
            var post = result.data.data;

            // clear textarea and reset preview
            cancelQuickReply();

            // show new posting
            jQuery('#quickreplyposting').html(post).removeClass('hidden');

            // Scroll to new posting.
            scrollTo('#quickreplyposting');

            // prepare everything for another quick reply
            jQuery('#quickreplyposting').after('<li id="new_quickreplyposting"></li>');
            // clear old id
            jQuery('#quickreplyposting').prop('id', '');
            // rename new id
            jQuery('#new_quickreplyposting').prop('id', 'quickreplyposting');
            // enable js options in quickreply
            jQuery('ul.javascriptpostingoptions').each(function() {
                jQuery(this).removeClass('hidden');
            });

            quickReplying = false;

            // Hook into edit link to work via ajax.
            hookEditLinks();

        }, errorHandler = function(request, message, detail) {
            DizkusShowAjaxError(request.responseText);
            quickReplying = false;
        };
        jQuery.ajax({
            data: pars,
            url: Zikula.Config.baseURL + 'index.php?module=ZikulaDizkusModule&type=ajax&func=reply'
        }).done(successHandler).fail(errorHandler).always(function() {
            hideAjaxIndicator();
        });
        showAjaxIndicator(zStoringReply+'...');

    }
    return false;
}

/**
 * Shows a preview of the quick reply.
 * @returns {boolean}
 */
function previewQuickReply() {
    if (!quickReplying) {
        var message = jQuery('#message').val();
        if (!message) {
            return false;
        }

        quickReplying = true;

        var pars = {
            topic: jQuery('#topic').val(),
            message: message,
            attach_signature: jQuery('#attach_signature').prop('checked') ? 1 : 0,
            preview: 1
        };

        var successHandler = function(result, message, request) {
            // Show preview.
            jQuery('#quickreplypreview').html(result.data.data).removeClass('hidden');

            // Scroll to preview.
            scrollTo('#quickreplypreview');

            quickReplying = false;
        }, errorHandler = function(request, message, detail) {
            DizkusShowAjaxError(request.responseText);
            quickReplying = false;
        };
        jQuery.ajax({
            data: pars,
            url: Zikula.Config.baseURL + 'index.php?module=ZikulaDizkusModule&type=ajax&func=reply'
        }).done(successHandler).fail(errorHandler).always(function() {
            hideAjaxIndicator();
        });
        showAjaxIndicator(zPreparingPreview + '...');
    }
}

/**
 * Aborts quick replying by emptying the message field and hiding previews.
 */
function cancelQuickReply() {
    jQuery('#message').val("");
    jQuery('#quickreplypreview').addClass('hidden');
    quickReplying = false;
}