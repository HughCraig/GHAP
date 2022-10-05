$(document).ready(function () {
    // Check whether the help video is loaded.
    if ($('#helpVideoModal').length > 0) {
        // Pause the help video when the modal is closed.
        $('#helpVideoModal').on('hidden.bs.modal', function () {
            $('#helpVideoModal').find('iframe')[0].contentWindow.postMessage('{"event":"command","func":"pauseVideo","args":""}', '*')
        });
    }
});
