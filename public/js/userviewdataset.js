$(document).ready( function () {
    // Init datatable.
    $("#dataitemtable").dataTable({
        orderClasses: false,
        bPaginate: true,
        bFilter: true,
        bInfo: false,
        bSortable: true,
        bRetrieve: true,
        aaSorting: [[ 0, "asc" ]],
        aoColumnDefs: [{ "aTargets": [ 13,16,17 ], "bSortable": false, "bSearchable": false }],
        "pageLength": 25
    });

    //LGA autocomplete.
    $( "#addlga, [name='lga']" ).autocomplete({
        source: function(request, response) {
            var results = $.ui.autocomplete.filter(lgas, request.term);
            response(results.slice(0, 20)); //return only 20 results
        }
    });
    $( "#addlga, [name='lga']" ).autocomplete( "option", "appendTo", ".eventInsForm" );

    //feature_term autocomplete.
    $( "#addfeatureterm, [name='feature_term']" ).autocomplete({
        source: function(request, response) {
            var results = $.ui.autocomplete.filter(feature_terms, request.term);
            response(results.slice(0, 20)); //return only 20 results
        }
    });
    $( "#addfeatureterm, [name='feature_term']" ).autocomplete( "option", "appendTo", ".eventInsForm" );

    // Datepickers.
    $('[name="editdatestartdiv"]').datepicker({format: 'yyyy-mm-dd', todayBtn: true, forceParse: false, keyboardNavigation: false});
    $('[name="editdateenddiv"]').datepicker({format: 'yyyy-mm-dd', todayBtn: true, forceParse: false, keyboardNavigation: false});
    $('#editDateStartDiv').datepicker({format: 'yyyy-mm-dd', todayBtn: true, forceParse: false, keyboardNavigation: false});
    $('#editDateEndDiv').datepicker({format: 'yyyy-mm-dd', todayBtn: true, forceParse: false, keyboardNavigation: false});

    // Handle dataitem delete.
    $('.delete-dataitem-button').on('click', function () {
        const dataitemID = $(this).data('itemId');
        const datasetID = $(this).data('setId');
        if (dataitemID && datasetID) {
            $('#deleteConfirmModal #deleteConfirmButton').data('itemId', dataitemID);
            $('#deleteConfirmModal #deleteConfirmButton').data('setId', datasetID);
            $('#deleteConfirmModal').modal('show');
        }
    });

    // When delete confirmed.
    $('#deleteConfirmModal #deleteConfirmButton').on('click', function () {
        const dataitemID = $(this).data('itemId');
        const datasetID = $(this).data('setId');
        if (dataitemID && datasetID) {
            $(this).prop('disabled', 'disabled');
            // Delete the dataitem.
            $.ajax({
                type: 'POST',
                url: ajaxdeletedataitem,
                data: {
                    id: dataitemID,
                    ds_id: datasetID
                },
                success: function (result) {
                    $(this).removeProp('disabled');
                    $('#deleteConfirmModal').modal('hide');
                    // Unset IDs.
                    $(this).data('itemId', "");
                    $(this).data('setId', "");
                    location.reload();
                },
                error: function (xhr, textStatus, errorThrown) {
                    $(this).removeProp('disabled');
                    $('#deleteConfirmModal').modal('hide');
                    alert(xhr.responseText); //error message with error info
                }
            });
        }
    });

    /**
     * Set the values of controls in the dataitem editing form.
     *
     * @param {Array} dataitem
     *   The object data of the dataitem.
     */
    const setEditDataitemFormValues = function (dataitem) {
        if (dataitem.title) {
            $('#editTitle').val(dataitem.title);
        }
        if (dataitem.placename) {
            $('#editPlacename').val(dataitem.placename);
        }
        if (dataitem.latitude) {
            $('#editLatitude').val(dataitem.latitude);
        }
        if (dataitem.longitude) {
            $('#editLongitude').val(dataitem.longitude);
        }
        if (dataitem.recordtype_id && dataitem.recordtype) {
            $('#editRecordtype').val(dataitem.recordtype.type);
        }
        if (dataitem.description) {
            $('#editDescription').val(dataitem.description);
        }
        if (dataitem.feature_term) {
            $('#editFeatureterm').val(dataitem.feature_term);
        }
        if (dataitem.state) {
            $('#editState').val(dataitem.state);
        }
        if (dataitem.datestart) {
            $('#editDateStartDiv').datepicker('setDate', dataitem.datestart);
        }
        if (dataitem.dateend) {
            $('#editDateEndDiv').datepicker('setDate', dataitem.dateend);
        }
        if (dataitem.lga) {
            $('#editLga').val(dataitem.lga);
        }
        if (dataitem.external_url) {
            $('#editExternalurl').val(dataitem.external_url);
        }
        if (dataitem.source) {
            $('#editSource').val(dataitem.source);
        }
    };

    /**
     * Get the data to send to the dataitem edit service.
     *
     * @returns {*}
     *   The request data.
     */
    const getEditDataitemRequestData = function () {
        const dataitemID = $('#editDataitemModal').data('itemId');
        const datasetID = $('#editDataitemModal').data('setId');
        const title = $('#editTitle').val();
        const placename = $('#editPlacename').val();
        const latitude = $('#editLatitude').val();
        const longitude = $('#editLongitude').val();
        const recordType = $('#editRecordtype').val();
        const description = $('#editDescription').val();
        const feature = $('#editFeatureterm').val();
        const state = $('#editState').val();
        const datestart = $('#editDatestart').val();
        const dateend = $('#editDateend').val();
        const lga = $('#editLga').val();
        const externalUrl = $('#editExternalurl').val();
        const source = $('#editSource').val();
        return {
            id: dataitemID,
            ds_id: datasetID,
            title: title ? title : null,
            placename: placename ? placename : null,
            recordtype: recordType ? recordType : null,
            latitude: latitude !== '' ? latitude : null,
            longitude: longitude !== '' ? longitude : null,
            description: description ? description : null,
            datestart: datestart ? datestart : null,
            dateend: dateend ? dateend : null,
            state: state ? state : null,
            featureterm: feature ? feature.toLowerCase() : null,
            lga: lga ? lga.toUpperCase() : null,
            source: source ? source : null,
            url: externalUrl ? externalUrl : null
        };
    };

    /**
     * Clear all values in the dataitem editing form.
     */
    const clearEditDataitemFormValues = function () {
        $('#editTitle').val('');
        $('#editPlacename').val('');
        $('#editLatitude').val('');
        $('#editLongitude').val('');
        $('#editRecordtype').val('');
        $('#editDescription').val('');
        $('#editFeatureterm').val('');
        $('#editState').val('');
        $('#editDateStartDiv').datepicker('setDate', null);
        $('#editDateEndDiv').datepicker('setDate', null);
        $('#editLga').val('');
        $('#editExternalurl').val('');
        $('#editSource').val('');
    };

    // Handle dataitem edit.
    $('.edit-dataitem-button').on('click', function () {
        const dataitemID = $(this).data('itemId');
        const datasetID = $(this).data('setId');
        $.ajax({
            type: 'GET',
            url: ajaxviewdataitem,
            data: {
                id: dataitemID,
                dataset_id: datasetID
            },
            success: function (result) {
                setEditDataitemFormValues(result);
                $('#editDataitemModal').data('itemId', dataitemID);
                $('#editDataitemModal').data('setId', datasetID);
                $('#editDataitemModal').modal('show');
            },
            error: function (xhr, textStatus, errorThrown) {
                alert(xhr.responseText); //error message with error info
            }
        });
    });

    // Unset all control values when the modal is hidden.
    $('#editDataitemModal').on('hidden.bs.modal', function () {
        clearEditDataitemFormValues();
        $('#editDataitemModal').data('itemId', "");
        $('#editDataitemModal').data('setId', "");
    });

    // Handle record edit when the save button is clicked.
    $('#editDataitemSaveButton').on('click', function () {
        $(this).prop('disabled', 'disabled');
        // Save the dataitem.
        $.ajax({
            type: 'POST',
            url: ajaxeditdataitem,
            data: getEditDataitemRequestData(),
            success: function (result) {
                $(this).removeProp('disabled');
                $('#editDataitemModal').modal('hide');
                location.reload();
            },
            error: function (xhr, textStatus, errorThrown) {
                $(this).removeProp('disabled');
                $('#editDataitemModal').modal('hide');
                alert(xhr.responseText); //error message with error info
            }
        });
    });
});
