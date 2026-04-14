$(document).ready( function () {
    // Initiate TinyMCE for all textareas with the class 'wysiwyg-editor'.
    tinymce.init({
        selector: 'textarea.wysiwyg-editor',
        promotion: false,
        branding: false,
        menubar: false,
        plugins: 'lists link',
        toolbar: 'bold italic link | h2 h3 h4 h5 | bullist',
        valid_elements: 'h2,h3,h4,h5,p,strong/b,em/i,a[href|target|title],ul,li,br',
        height: 200,
        // Ensure TinyMCE dialogs (e.g. link editor) render above Bootstrap 5 modals (z-index 1055).
        z_index_base: 1100,
        setup: function (editor) {
            // Hide all Bootstrap tooltips when TinyMCE opens a dialog (e.g. Insert Link),
            // so they don't appear on top of / interfere with the dialog.
            editor.on('OpenWindow', function () {
                $('[data-bs-toggle="tooltip"]').tooltip('hide');
            });
        }
    });
    console.log('TinyMCE initialized for .wysiwyg-editor textareas.');

    // Fix: TinyMCE dialog fields (URL, Title etc.) not editable inside Bootstrap modal.
    // Bootstrap 5 uses native addEventListener for its focus trap, so jQuery's
    // stopImmediatePropagation does not prevent it. We must use a native capture-phase
    // listener to intercept focusin before Bootstrap 5's handler steals focus.
    // Ref: https://github.com/tinymce/tinymce/issues/782
    document.addEventListener('focusin', function(e) {
        if (e.target.closest('.tox-dialog-wrap, .tox-tinymce-aux')) {
            e.stopImmediatePropagation();
        }
    }, true);
});
