class ExtendedDataEditor {

    /**
     * Constructor.
     *
     * @param {string} selector
     *   The CSS selector of the editor container.
     */
    constructor(selector) {
        this.container = $(selector);
        this.body = this.container.find('table tbody');
    }

    /**
     * Initialise the editor.
     *
     * @param {Object|null} data
     *   The initial data to set up the editor, or null if there's no initial data.
     */
    init(data = null) {
        const editor = this;
        this.setData();

        // Bind add row button click event.
        this.container.find('.editor-add-button').on('click', function () {
            editor._addRow();
        });
    }

    /**
     * Get the extended data.
     *
     * @returns {Object|null}
     *   The object of the extended data, or null if there's no data.
     */
    getData() {
        const data = {};
        this.body.find('tr').each(function () {
            const name = $(this).find('.editor-name').val();
            const value = $(this).find('.editor-value').val();
            if (name !== '' && value !== '') {
                data[name] = value;
            }
        });
        if (Object.keys(data).length > 0) {
            return data;
        }
        return null;
    }

    /**
     * Set the extended data to the editor.
     *
     * This will overwrite the current data and apply the according display.
     *
     * @param {Object|null} data
     *   The extended data object.
     */
    setData(data = null) {
        this.body.html('');
        if (data && typeof data === 'object') {
            for (let name in data) {
                this._addRow(name, data[name]);
            }
        }
    }

    /**
     * Add a row in the display.
     *
     * @param {string|null} name
     *   The name of the extended data.
     * @param {string|null} value
     *   The value of the extended data.
     * @private
     */
    _addRow(name = null, value = null) {
        const row = $(`<tr><td></td><td></td><td></td></tr>`);
        const nameInput = $(`<input type="text" class="form-control editor-name">`);
        const valueInput = $(`<input type="text" class="form-control editor-value">`);
        const deleteButton = $(`<button type="button" class="btn btn-sm btn-default">Delete</button>`);
        row.find('td').eq(0).append(nameInput);
        row.find('td').eq(1).append(valueInput);
        row.find('td').eq(2).append(deleteButton);

        if (name !== null) {
            nameInput.val(name);
        }
        if (value !== null) {
            valueInput.val(value);
        }

        this.body.append(row);

        // Bind click event to the delete button.
        deleteButton.on('click', function () {
            row.remove();
        });
    }
}
