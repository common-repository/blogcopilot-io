
jQuery(document).ready(function($) {
    $(".generate-more-images-button").click(function() {
        var button = $(this);
        var postId = button.data("post-id");
        var postTitle = button.data("post-title");
        var postTaskId = button.data("post-task");

        var spinnerContainer = document.getElementById("blogcopilot-spinner-container");
        var myDiv = document.getElementById("blogcopilot-display-div");
        var buttons = $("button");
        spinnerContainer.style.display = "flex"; // Show the spinner
        myDiv.style.opacity = "0.5";  // 50% opacity
        buttons.prop("disabled", true);

        $.ajax({
            url: blogcopilotParams.ajax_url,
            type: "POST",
            data: {
                action: "blogcopilot_io_generate_more_images_2",
                post_id: postId,
                title: postTitle,
                taskId: postTaskId,
                _ajax_nonce: blogcopilotParams.nonce,                
            },
            success: function(response) {
                if (response.success) {                    
                    var imageCounter = $(`#ai_image_grid_${postId} .ai_image_container`).length;
                    response.data.forEach(function(imageUrl) {
                        var confirmationId = "image_confirmation_" + postId + "_" + imageCounter;
                        imageUrl = blogcopilotParams.apiUrl + imageUrl;
                
                        var newImageHtml = "<div class=\'ai_image_container\' style=\'text-align: center;\'>" +
                                            "<img src=\'" + imageUrl + "\' style=\'width: 100%; height: auto; border-radius: 10px; transition: transform 0.3s ease;\'>" +
                                            "<button class=\'btn btn-primary btn-sm ai_select_image\' style=\'margin-top: 10px;\' data-image-url=\'" + imageUrl + "\' data-post-id=\'" + postId + "\' data-confirmation-target=\'" + confirmationId + "\'>As Featured</button> " +
                                            "<button class=\'btn btn-secondary btn-sm ai_use_in_content\' style=\'margin-top: 10px; margin-left: 5px;\' data-image-url=\'" + imageUrl + "\' data-post-id=\'" + postId + "\' data-confirmation-target=\'" + confirmationId + "\'>In Content</button>" +
                                            "<div id=\'" + confirmationId + "\' style=\'color: green; display:none; margin-top: 5px;\'>Image added successfully!</div>" +
                                            "</div>";
                        $(`#ai_image_grid_${postId}`).append(newImageHtml);
                        imageCounter++;
                    });
                } else {
                    var errorMessageHtml = "<div class=\'alert alert-secondary alert-dismissible fade show my-2\' role=\'alert\'>" +
                    "<span class=\'alert-icon\'><i class=\'bi bi-exclamation-diamond\'></i></span>";

                    // Checking if error exists in response.data
                    if (response.data && response.data.error) {
                        errorMessageHtml += "<span class=\'alert-text\'>" + response.data.error + "</span>";
                    } else {
                       errorMessageHtml += "<span class=\'alert-text\'>Unknown error occurred</span>";
                    }

                    // Include quota information if available
                    if (response.data.monthlyGeneratedImages) {
                        errorMessageHtml += "<p>Monthly Generated Images: " + response.data.monthlyGeneratedImages + "</p>";
                    }
                    if (response.data.monthlyImageQuota) {
                        errorMessageHtml += "<p>Monthly Image Quota: " + response.data.monthlyImageQuota + "</p>";
                    }
                    if (response.data.remainingImageQuota) {
                        errorMessageHtml += "<p>Remaining Image Quota: " + response.data.remainingImageQuota + "</p>" +
                                            "<p><br/><strong>Upgrade to higher version to increase limits!</strong></p>";
                    }
                    
                    errorMessageHtml += "</div>";
                    var boxToReplace = $(`#blogcopilot_io_generate_more_images_container_${postId}`);

                    boxToReplace.html(errorMessageHtml);                        
                }
            },
            error: function(response) {
                alert("Error generating images for post ID " + postId + ".");
            },
            complete: function() {
                // Re-enable buttons and inputs, hide the spinner
                spinnerContainer.style.display = "none";
                myDiv.style.opacity = "1";
                buttons.prop("disabled", false);
            }                   
        });
    });

    $('.publish-button').click(function() {
        var button = $(this);
        var postId = button.data('article-id'); // Use the draft post ID

        $.ajax({
            type: "POST",
            url: blogcopilotParams.ajax_url,
            data: {
                action: "blogcopilot_io_publish_post",
                post_id: postId,
                _ajax_nonce: blogcopilotParams.nonce,
            },
            success: function(response) {
                var newPostId = response;
                $('#edit-draft-btn-' + postId).text('View');
                $('#message-' + postId).html('<p>Post published successfully. Please also add photos to the content.</p>');

                // Hide the button
                button.hide();
            }
        });
    });

    // Event delegation for handling clicks on dynamically added buttons
    $(document).on("click", ".ai_select_image, .ai_use_in_content", function() {
        var imageUrl = $(this).data("image-url");
        var postId = $(this).data("post-id");
        var confirmationTargetId = $(this).data("confirmation-target");
        var confirmationTarget = $("#" + confirmationTargetId); // Correctly target the confirmation message div

        $.ajax({
            url: blogcopilotParams.ajax_url,
            type: "POST",
            data: {
                action: $(this).hasClass("ai_select_image") ? "blogcopilot_io_ai_set_featured_image" : "blogcopilot_io_ai_use_image_in_content",
                post_id: postId,
                image_url: imageUrl,
                _ajax_nonce: blogcopilotParams.nonce,
            },
            success: function(response) {
                confirmationTarget.html("<p>Image added successfully!</p>").show();
            },
            error: function(response) {
                confirmationTarget.text("Error processing image.").show();
            }
        });
    });
});

