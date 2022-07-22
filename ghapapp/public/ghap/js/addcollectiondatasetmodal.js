$(document).ready(function() {

    /**
     * Sanitize for html output.
     *
     * @param {string} value
     *   Raw value.
     */
    const sanitize = function(value) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#x27;',
            "/": '&#x2F;',
        };
        const reg = /[&<>"'/]/ig;
        return value.replace(reg, (match) => (map[match]));
    };

    /**
     * Refresh the layer select list.
     *
     * @param {string} scope
     *   Can either be 'public' or 'user' to set the scope of the options.
     */
    const refreshSelectList = function (scope) {
        // Clear all existing options.
        $('#datasetSelect').val(null).trigger('change');
        $('#datasetSelect').html('<option value=""></option>');

        $('#datasetSelect').prop("disabled", true);
        $.ajax({
            url: `${uiServiceRoot}/${scope}`,
            success: function(results) {
                if (Array.isArray(results) && results.length > 0) {
                    // Add options.
                    for (let i = 0; i < results.length; i++) {
                        const option = new Option(results[i].name, results[i].id, false, false);
                        $('#datasetSelect').append(option).trigger('change');
                    }
                }
                $('#datasetSelect').prop("disabled", false);
            },
            error: function(xhr, textStatus, errorThrown) {
                alert(xhr.responseText);
            }
        });
    };

    /**
     * Initialize the modal.
     */
    const initModal = function () {
        $('#layerInfo').html('');
        $('#scopePublicLayersRadio').prop('checked', true);
        refreshSelectList('public');
    };

    /**
     * Refresh the info content with a dataset.
     *
     * @param {Object} data
     *   The information data of the dataset.
     */
    const refreshDatasetInfoContent = function (data) {
        let html = `<div class="table-responsive">`;
        html += `<table class="table table-bordered">`;
        html += `<tr><th class="w-25">Name</th><td>${data.name ? sanitize(data.name) : ''}</td></tr>`;
        html += `<tr><th>Description</th><td>${data.description ? sanitize(data.description) : ''}</td></tr>`;
        html += `<tr><th>Type</th><td>${data.type ? sanitize(data.type) : ''}</td></tr>`;
        html += `<tr><th>Content Warning</th><td>${data.warning ? sanitize(data.warning) : ''}</td></tr>`;
        html += `<tr><th>Contributor</th><td>${data.ownerName ? sanitize(data.ownerName) : ''}</td></tr>`;
        html += `<tr><th>Entries</th><td>${data.entries}</td></tr>`;
        html += `<tr><th>Visibility</th><td>${data.public ? 'Public' : 'Private'}</td></tr>`;
        html += `<tr><th>Allow ANPS Collection?</th><td>${data.allowanps ? 'Yes' : 'No'}</td></tr>`;
        html += `<tr><th>Added to System</th><td>${data.created_at ? data.created_at : ''}</td></tr>`;
        html += `<tr><th>Updated in System</th><td>${data.updated_at ? data.updated_at : ''}</td></tr>`;
        html += `</table>`;
        html += `</div>`;
        html += `<div><a href="${data.url}" target="_blank">View layer details</a></div>`;
        $('#layerInfo').html(html);
    };

    // Initialize the select2 widget.
    $('#datasetSelect').select2({
        theme: "bootstrap",
        placeholder: 'Select a layer...'
    });

    // Event handling when an option is selected.
    $('#datasetSelect').on('select2:select', function (e) {
        if (typeof e.params.data.id !== 'undefined') {
            $('#datasetSelect').prop("disabled", true);
            $.ajax({
                url: `${uiServiceRoot}/${e.params.data.id}/info`,
                success: function(data) {
                    if (data) {
                        refreshDatasetInfoContent(data);
                    }
                    $('#datasetSelect').prop("disabled", false);
                },
                error: function(xhr, textStatus, errorThrown) {
                    alert(xhr.responseText);
                }
            });
        }
    });

    // Event handling when the scope is changed.
    $('input[name="scopeRadioOptions"]').on('change', function () {
        $('#layerInfo').html('');
        refreshSelectList($(this).val());
    });

    // Event handling when the add button is clicked.
    $('#submitAddDataset').on('click', function () {
        $('#addDatasetModal').modal('hide');
        const selections = $('#datasetSelect').select2('data');
        if (selections.length > 0 && selections[0].id) {
            const datasetID = selections[0].id;
            const collectionID = $('#addDatasetModal').data('collection-id');
            $.ajax({
                type: 'POST',
                url: addDatasetToCollectionService,
                data: {
                    id: collectionID,
                    datasetID: datasetID
                },
                success: function(result) {
                    // Reload the page once the dataset is added. Ideally only the table could be refreshed as the
                    // response contains the required data to add the new row.
                    location.reload();
                },
                error: function(xhr, textStatus, errorThrown) {
                    alert(xhr.responseText); //error message with error info
                }
            });
        }
    });

    // Event handling when the modal is shown.
    $('#addDatasetModal').on('show.bs.modal', function () {
        initModal();
    });

});
