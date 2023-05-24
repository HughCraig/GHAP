/**
 * A class used for message display.
 */
class MessageBanner {

    /**
     * Constructor.
     *
     * @param {jQuery} container
     *   The jQuery element of the container.
     */
    constructor(container) {
        this.container = container;
    }

    /**
     * Add a success message.
     *
     * @param {string} content
     *   The content of the message.
     */
    success(content) {
        this.insertMessage(content, 'success');
    }

    /**
     * Add an info message.
     *
     * @param {string} content
     *   The content of the message.
     */
    info(content) {
        this.insertMessage(content, 'info');
    }

    /**
     * Add a warning message.
     *
     * @param {string} content
     *   The content of the message.
     */
    warning(content) {
        this.insertMessage(content, 'warning');
    }

    /**
     * Add an error message.
     *
     * @param {string} content
     *   The content of the message.
     */
    error(content) {
        this.insertMessage(content, 'danger');
    }

    /**
     * Insert a message to the banner.
     *
     * @param {string} content
     *   The content of the message.
     * @param {string} type
     *   The type of the message.
     */
    insertMessage(content, type) {
        const alert = $(`<div class="alert alert-${type}" role="alert">${content}</div>`);
        this.container.append(alert);
    }

    /**
     * Clear all messages from the banner.
     */
    clear() {
        this.container.html('');
    }

    /**
     * Make the banner visible.
     */
    show() {
        this.container.show();
    }

    /**
     * Hide the banner.
     */
    hide() {
        this.container.hide();
    }
}
