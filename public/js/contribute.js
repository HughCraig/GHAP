$(document).ready(function () {
    // Change the advance search button icon on expand/collapse.
    $("#layerotherinfo").on("show.bs.collapse", function () {
        $("#advancedSearchButton")
            .find("i.fa")
            .removeClass("fa-chevron-down")
            .addClass("fa-chevron-up");
    });
    $("#layerotherinfo").on("hide.bs.collapse", function () {
        $("#advancedSearchButton")
            .find("i.fa")
            .removeClass("fa-chevron-up")
            .addClass("fa-chevron-down");
    });

    if (typeof msgBanner === "undefined") {
        var msgBanner = new MessageBanner($("#newLayerModal .message-banner"));
        msgBanner.hide();
    }

    $("#source").on("click", function () {
        $("#contributesource").modal("show");
    });

    $("#contributesavebtn").on("click", function () {
        let isValid = validateAddLayerRequestData(msgBanner);

        if (isValid) {
            let layerFormData = getAddLayerRequestData();
            var new_layer_id;

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
                                dataitems: window.contributesourcedata,
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

                                window.location.href = "/myprofile/mydatasets/" + new_layer_id;
                            },
                        });
                    } else {
                        window.location.href =
                            "/myprofile/mydatasets/" + new_layer_id;
                    }
                },
            });
        }
    });
});
