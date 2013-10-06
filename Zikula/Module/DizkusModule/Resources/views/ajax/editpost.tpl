<div class="dzk_ajaxeditpost" id="postingtext_{$post.post_id}_editor">
    <div class="ajaxeditpoststatusbox">
        <strong>{gt text="Status"}:</strong> <span id="postingtext_{$post.post_id}_status">{gt text="Unchanged"}</span>
    </div>
    <div class='form-group'>
        <label for="{$postingtextareaid}" class='sr-only'>{gt text="Message"}</label>
        <textarea id="{$postingtextareaid}" class="form-control" rows="10" cols="60" name="postingtext_{$post.post_id}_edit">{$post.post_text}</textarea>
    </div>
    <div class='form-group'>
        {notifydisplayhooks eventname='dizkus.ui_hooks.post.ui_edit' id=$post.post_id}
    </div>
    <div class='checkbox'>
        <label for="postingtext_{$post.post_id}_attach_signature">
        <input type="checkbox" name="postingtext_{$post.post_id}attach_signature" id="postingtext_{$post.post_id}_attach_signature" {if $post.AttachSignature eq true}checked="checked"{/if} value="1" />
        &nbsp;{gt text="Attach my signature"}</label>
    </div>
    {if $post.poster_data.moderate eq true && !$isFirstPost}
    <div class='checkbox'>
        <input id="postingtext_{$post.post_id}_delete" type="checkbox"  value="1" /><label for="postingtext_{$post.post_id}_delete">&nbsp;{gt text="Delete post"}</label>
    </div>
    {/if}
    <button id="postingtext_{$post.post_id}_save" class="btn btn-success" type="submit" name="submit">{gt text="Submit"}</button>
    <button id="postingtext_{$post.post_id}_cancel" class="btn btn-danger" type="submit" name="cancel">{gt text="Cancel"}</button>
</div>
