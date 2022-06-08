/* Get CSRF token for POST and add it to the AJAX header */
var token = $('input[name="csrf-token"]').attr('value');
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

/* Use AJAX to get values from form */
$('#save_search_button').click(function() { 
    $.ajax({
        type: 'POST',
        url: ajaxsavesearch,
        data: {
            name: $("#save_search_name").val(),
            searchquery: $("#save_search_query").val(),
            count: $("#save_search_count").val()
        },
        success: function(data) {
            $("#saveSearchModalButton").hide();
            $("#save_search_name").hide();
            $("#save_search_message").show();
        },
        error: function(xhr, textStatus, errorThrown) {
            alert(xhr.responseText); //error message with error info
        }
    }); 
 });

 function copyLink(id, button, param) {
    if (param === undefined) param = 'anps_id'
    var body = document.getElementsByTagName('body')[0];
    var text = location.protocol + '//' + location.host + '/ghap/search?' + param + "=" + id; //ideally should update with the page???

    var tempInput = document.createElement('INPUT');
    body.appendChild(tempInput);
    tempInput.setAttribute('value', text);
    tempInput.select();
    document.execCommand('copy');
    body.removeChild(tempInput);

    var oldcolor =  $(button).addClass("green-background");
    setTimeout(function(){
        $(button).removeClass("green-background", 1000);
    },800);

    //Show some kind of success message
    // $("#notification_box").addClass("notification-success");
    // $("#notification_message").text('Copied link: ' + text);
    // setTimeout(function(){
    //     $("#notification_box").removeClass("notification-success");
    // },4000);

    document.execCommand("copy");
 }

 function backLink(id, param) {
    if (param === undefined) param = 'anps_id';
    return location.protocol + '//' + location.host + location.pathname + "?" + param + "=" + id;
 }

 function temporalEarthLink(id, param) {
    if (param === undefined) param = 'anps_id';
    link = encodeURIComponent(link + "&format=kml");
    window.open("http\:\/\/tlcmap.org/te/?file="+link);
 }