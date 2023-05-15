$(document).ready( function () {
    // Initiate TinyMCE for all textareas with the class 'wysiwyg-editor'.
    tinymce.init({
        selector: 'textarea.wysiwyg-editor',
        promotion: false,
        branding: false,
        menubar: false,
        plugins: 'lists link',
        toolbar: 'bold italic link | h2 h3 h4 h5 | bullist',
        valid_elements: 'h2,h3,h4,h5,p,strong/b,em/i,a[href|target|title],ul,li,br'
    });
});
