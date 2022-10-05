//https://www.w3schools.com/howto/tryit.asp?filename=tryhow_js_tabs
function openTab(evt, tabName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tabcontent");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }
    tablinks = document.getElementsByClassName("tablinks");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }
    document.getElementById(tabName).style.display = "block";
    evt.currentTarget.className += " active";
}

$(document).ready(function () {
    // Active tab based on the error existence.
    if ($('#General').find('.invalid-feedback').length > 0) {
        document.getElementById("general_tab").click();
    } else if ($('#Password').find('.invalid-feedback').length > 0) {
        document.getElementById("password_tab").click();
    } else if ($('#Email').find('.invalid-feedback').length > 0) {
        document.getElementById("email_tab").click();
    } else {
        document.getElementById("general_tab").click();
    }
});
