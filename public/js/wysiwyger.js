tinymce.init({
    selector: '.wysiwyger',  // change this value according to your HTML
    plugins: 'link image media table lists code',
    menubar: 'edit format insert',
    menu: {
        edit: {title: 'Edit', items: 'undo redo selectall'},
        format: {title: 'Format', items: 'bold italic | blockformats | removeformat | lists'},
        insert: {title: 'Insert', items: 'link image media | table code'}
    },
    toolbar: [
        {
            name: 'history', items: ['undo', 'redo']
        },
        {
            name: 'styles', items: ['styleselect']
        },
        {
            name: 'formatting', items: ['bold', 'italic', 'numlist', 'bullist']
        },
        {
            name: 'insert', items: ['link', 'image', 'media', 'table', 'code']
        }
    ],
    style_formats: [
        {
            title: 'Headings and Paragraph', items: [
                {title: 'Paragraph', format: 'p'},
                {title: 'Header 1', format: 'h1'},
                {title: 'Header 2', format: 'h2'},
                {title: 'Header 3', format: 'h3'},
                {title: 'Header 4', format: 'h4'},
                {title: 'Header 5', format: 'h5'},
                {title: 'Header 6', format: 'h6'}
            ]
        }
    ]
});
