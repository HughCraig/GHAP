$(document).ready(function () {
    /**
     * Refresh the saved search select list.
     */
    const refreshSavedSearchSelectList = function () {
        $("#savedSearchSelect").html('<option value=""></option>');
        $("#savedSearchSelect").prop("disabled", true);
        const collectionID = $("#addSavedSearchModal").data("collection-id");

        $.ajax({
            url: ajaxGetUserSavedSearchesURL,
            data:{
                collectionID: collectionID,
            },
            success: function (results) {
                if (Array.isArray(results) && results.length > 0) {
                    for (let i = 0; i < results.length; i++) {
                        const option = new Option(
                            results[i].name,
                            results[i].id,
                            false,
                            false
                        );
                        $("#savedSearchSelect")
                            .append(option)
                            .trigger("change");
                    }
                }
                $("#savedSearchSelect").prop("disabled", false);
            },
            error: function (xhr, textStatus, errorThrown) {
                alert(xhr.responseText);
            },
        });
    };

    //
    /**
     * Initialize the saved search modal.
     */
    const initSavedSearchModal = function () {
        refreshSavedSearchSelectList();
    };

    // Initialize the select2 widget for saved search select box.
    $("#savedSearchSelect").select2({
        theme: "bootstrap",
        placeholder: "Select a saved search...",
        dropdownParent: $("#addSavedSearchModal"),
        width: "100%",
    });

    const msgBanner = new MessageBanner(
        $("#addSavedSearchModal .message-banner")
    );
    msgBanner.hide();

    // Event handling when the add saved search button is clicked.
    $("#submitAddSavedSearch").on("click", function () {
        msgBanner.clear();
        const selections = $("#savedSearchSelect").select2("data");
        if (selections.length > 0 && selections[0].id) {
            const savedSearchID = selections[0].id;
            const collectionID = $("#addSavedSearchModal").data(
                "collection-id"
            );

            $.ajax({
                type: "POST",
                url: ajaxAddSavedSearchesURL,
                data: {
                    collectionID: collectionID,
                    savedSearchID: savedSearchID,
                },
                success: function (result) {
                    //Reload page once saved search is added
                    location.reload();
                },
                error: function (xhr, textStatus, errorThrown) {
                    msgBanner.error(xhr.responseText);
                    msgBanner.show();
                    $("#saveSearchModal").scrollTop(0);
                },
            });
        }
    });

    // Event handling when the saved search modal is shown.
    $("#addSavedSearchModal").on("show.bs.modal", function () {
        initSavedSearchModal();
    });
});
