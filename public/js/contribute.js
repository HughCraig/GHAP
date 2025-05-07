$(document).ready(function () {
    if (typeof msgBanner === "undefined") {
        var msgBanner = new MessageBanner($("#newLayerModal .message-banner"));
        msgBanner.hide();
    }

    const layerNameInput = document.getElementById("layername");
    const nextButton = document.getElementById("basicInfoNextButton");

    let lastDescriptionContent = "";

    function validateInputs() {
        const nameFilled = layerNameInput.value.trim().length > 0;
        const editor = tinymce.get("description");
        const descText = editor
            ? editor.getContent({ format: "text" }).trim()
            : "";
        const descFilled = descText.length > 0;
        nextButton.disabled = !(nameFilled && descFilled);
    }

    function showLoadingWheel() {
        document.getElementById("loadingWheel-contribute").style.display =
            "block";
    }

    function hideLoadingWheel() {
        document.getElementById("loadingWheel-contribute").style.display =
            "none";
    }

    layerNameInput.addEventListener("input", validateInputs);

    // Wait until TinyMCE is fully loaded
    const waitForTinyMCE = setInterval(function () {
        const editor = tinymce.get("description");
        if (editor && editor.initialized) {
            clearInterval(waitForTinyMCE);

            // Start polling for changes every 300ms
            setInterval(function () {
                const currentContent = editor
                    .getContent({ format: "text" })
                    .trim();
                if (currentContent !== lastDescriptionContent) {
                    lastDescriptionContent = currentContent;
                    validateInputs();
                }
            }, 300);
        }
    }, 200);

    $("#source").on("click", function () {
        $("#contributesource").modal("show");
    });

    $("#contributesavebtn").on("click", function () {
        let isValid = validateAddLayerRequestData(msgBanner);

        if (isValid) {
            let layerFormData = getAddLayerRequestData();
            var new_layer_id;

            showLoadingWheel();

            if (window.fromTextID !== null) {
                layerFormData.append("from_text_id", window.fromTextID);
                layerFormData.delete("recordtype");
                layerFormData.append("recordtype", "Text");
            }
            $.ajax({
                type: "POST",
                url: "/myprofile/mydatasets/newdataset/create", //'User\UserController@createNewDataset'
                data: layerFormData,
                contentType: false,
                processData: false,
                headers: {
                    Accept: "application/json",
                },
                success: function (result) {
                    new_layer_id = result.dataset_id;

                    if (
                        window.contributesourcedata !== null &&
                        window.contributesourcedata !== undefined &&
                        Array.isArray(window.contributesourcedata) &&
                        window.contributesourcedata.length > 0
                    ) {
                        $.ajax({
                            type: "POST",
                            url: ajaxcreatedataitemsfordataset,
                            data: {
                                ds_id: new_layer_id,
                                dataitems: JSON.stringify(
                                    window.contributesourcedata
                                ),
                            },
                            headers: {
                                "Content-Type":
                                    "application/x-www-form-urlencoded; charset=UTF-8",
                                Accept: "application/json",
                            },
                            success: function (response) {
                                // Add text context for text layer
                                if (
                                    window.fromTextID !== null &&
                                    response.dataitems.length > 0
                                ) {
                                    response.dataitems.forEach(function (
                                        dataitem
                                    ) {
                                        var new_dataitem_uid = dataitem.uid;
                                        const textConextFormData =
                                            new FormData();

                                        textConextFormData.append(
                                            "dataitem_uid",
                                            new_dataitem_uid
                                        );

                                        textConextFormData.append(
                                            "text_id",
                                            window.fromTextID
                                        );

                                        textConextFormData.append(
                                            "start_index",
                                            parseInt(
                                                dataitem.extended_data.offset
                                            )
                                        );
                                        textConextFormData.append(
                                            "end_index",
                                            parseInt(
                                                dataitem.extended_data.offset,
                                                10
                                            ) + dataitem.title.length
                                        );
                                        textConextFormData.append(
                                            "sentence_start_index",
                                            dataitem.extended_data
                                                .sentence_start_index
                                        );
                                        textConextFormData.append(
                                            "sentence_end_index",
                                            dataitem.extended_data
                                                .sentence_end_index
                                        );
                                        textConextFormData.append(
                                            "line_index",
                                            dataitem.extended_data.line
                                        );
                                        textConextFormData.append(
                                            "line_word_start_index",
                                            dataitem.extended_data.word
                                        );
                                        textConextFormData.append(
                                            "line_word_end_index",
                                            -1
                                        );

                                        $.ajax({
                                            type: "POST",
                                            url: ajaxaddtextcontent,
                                            data: textConextFormData,
                                            contentType: false,
                                            processData: false,
                                        });
                                    });
                                } else {
                                    window.contributesourcedata = null;
                                    window.fromTextID = null;
                                }

                                window.location.href =
                                    "/myprofile/mydatasets/" + new_layer_id;
                            },
                            error: function (xhr) {
                                console.error(xhr.status);
                                console.error(xhr.responseText);
                                hideLoadingWheel();
                            },
                        });
                    } else {
                        window.location.href =
                            "/myprofile/mydatasets/" + new_layer_id;
                    }
                },
                error: function (xhr) {
                    hideLoadingWheel();
                },
            });
        }
    });
});
