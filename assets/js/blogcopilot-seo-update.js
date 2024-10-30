function updatePostSEO(post_id, title, content) {
    jQuery.ajax({
        url: blogcopilotParams.ajax_url,
        type: 'POST',
        data: { 
            action: 'blogcopilot_io_update_post',
            post_id: post_id,
            title: title,
            content: content,
            _ajax_nonce: blogcopilotParams.nonce,
        },
        success: function(response) {
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error("Error updating SEO parameters: ", textStatus, errorThrown, jqXHR);
        }
    });
}

updatePostSEO(blogcopilotParams.postId,blogcopilotParams.title,blogcopilotParams.article);
