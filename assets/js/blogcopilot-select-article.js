document.getElementById("post_id").addEventListener("input", function() {
    let selectedOption = document.querySelector("#datalistArticles option[value=\'" + this.value + "\']");
    if (selectedOption) {
        document.getElementById("linked_post_id").value = selectedOption.dataset.postId;
    } else {
        document.getElementById("linked_post_id").value = "";
    }
});