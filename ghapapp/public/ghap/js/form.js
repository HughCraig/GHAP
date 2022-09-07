function changeInput(caller) {
    /*
     *  Change the placeholder text depending on if name or anps_id is selected - dont disable the checkbox as we might still want to use it on bulk search
     */
    document.getElementById('input').placeholder = (caller.options[caller.selectedIndex].value == 'anps_id') ? 'Enter anps ID' : 'Enter place name';
}

// FUNCTIONALITY MOVED TO continueSearchForm function (one time check instead of changing on every input change)
// function exactSearch() {
//     /*
//      *  If we are searching by placename, use name if "exact match" is checked and fuzzyname if not
//      */
//     if ( document.getElementById('exact-match').checked == true )
//         document.getElementById('input').setAttribute("name", "name");
//     else 
//         document.getElementById('input').setAttribute("name", "fuzzyname");
// }

//Enter key calls the submit form function
$(function () {
    $("#input").trigger("focus")
    $("#input").on('keyup', function (event) {
        if (event.key === "Enter") {
            $("#gazformbutton").trigger("click");
        }
    });
});


function submitSearchForm() {
    //SOME AJAX LOGIC TO GET THE FILE FROM BULK FILE UPLOAD AND CHANGE THE PARAMETER TO HAVE ALL THE PLACENAMES https://stackoverflow.com/questions/19617996/file-upload-without-form
    //THROW AN ALERT AND RETURN IF FILE IS TOO LONG OR HAS SOME OTHER ERROR
    var bulkfileinput = document.getElementById("bulkfileinput")
    var CSRF_TOKEN = $('input[name=_token]').val();

    if (bulkfileinput.value.length) {
        var myFormData = new FormData()
        myFormData.append('file', bulkfileinput.files[0]);
        myFormData.append('_token', CSRF_TOKEN);

        $.ajax({
            url: bulkfileparser,
            type: 'POST',
            dataType: 'json',
            contentType: false,
            processData: false,
            data: myFormData,
            success: function (result) {
                continueSearchForm(result.names)
            },
            error: function (xhr, textStatus, errorThrown) {
                alert(xhr.responseText); //error message with error info
            }
        })
    } else {
        continueSearchForm()
    }
}

function continueSearchForm(names = null) {
    //Checking that date inputs match the proper format
    var datefrom = document.getElementById('datefrom');
    var dateto = document.getElementById('dateto');
    datefrom.classList.remove('is-invalid');
    dateto.classList.remove('is-invalid');
    if (datefrom.value && !dateMatchesRegex(datefrom.value)) {
        datefrom.classList.add('is-invalid');
        return alert('"Date From" field is NOT in a valid format!');
    }
    if (dateto.value && !dateMatchesRegex(dateto.value)) {
        dateto.classList.add('is-invalid');
        return alert('"Date To" field is NOT in a valid format!');
    }

    // Validate ANPS ID.
    if ($('#input-select-box').val() === 'anps_id' && !/^\d+$/.test($('#input').val())) {
        $('#input').addClass('is-invalid');
        return alert('ANPS ID should be a number');
    }

    //change the input depending on form settings
    var selectBox = document.getElementById("input-select-box"); //the select box to choose between name/anps_id
    var inputName = selectBox.options[selectBox.selectedIndex].value; //the value selected for search type (containsname fuzzyname name anps_id)
    if (!names) { //if we did NOT bulk file search
        document.getElementById('input').setAttribute("name", inputName); //change input name to the selectbox type
        var trimmed_input = document.getElementById('input').value.trim();
        trimmed_input = trimmed_input.replace(/\s+/, ' '); //replace all instances of single or multiple space with a single space. eg "nobbys     beach" becomes "nobbys beach"
        document.getElementById('input').value = trimmed_input;
    }

    //if we were redirected from the AJAX success with a bulk file of names to search
    else { //if no errors, choose between fuzzynames, containsnames or names but skip for anps_id
        if (names.length > 1500) return alert('File length was too long! Try using a shorter file (<1500 characters)')
        if (inputName == "anps_id") inputName = "containsname";
        //containsname fuzzyname or name, turned into a plural to make the bulk search parameter active
        document.getElementById(inputName + "s").hidden = false;
        document.getElementById(inputName + "s").value = names;
    }


    //put the lat/long limits into the bbox parameter
    var bbox = getBbox()
    if (bbox) document.getElementById('bbox').setAttribute("value", bbox)

    var polygon = getPolygon()
    if (polygon) document.getElementById('polygon').setAttribute("value", polygon)

    var circle = getCircle()
    if (circle) document.getElementById('circle').setAttribute("value", circle)

    //Remove unwanted parameters
    if (document.getElementsByName('leaflet-base-layers_57')[0]) document.getElementsByName('leaflet-base-layers_57')[0].disabled = true //Leaflet map radio button vale (osm / google)
    if (names) {
        document.getElementById('input').disabled = true //if we are bulk searching we can ignore the input form
    }

    // if (document.getElementById('searchForm').method == "get") {
    //   document.getElementsByName("_token")[0].disabled = true //csrf token for file upload
    //   //document.getElementsByName("exact-match")[0].disabled = true
    // }


    //get all the inputs into an array.
    var $inputs = $('#searchForm :input')

    //get an associative array of just the values (remove empty inputs instead of submitting empty strings)
    $inputs.each(function () {
        if ($(this).val() === '') $(this).removeAttr("name")
    });

    //if download=on
    // var download = document.getElementById("download")
    // var paging = document.getElementById("paging")
    // if (download.checked) {
    //   //drop set paging 999999
    //   paging.value = 999999; //no longer needed?
    // }

    $('#searchForm').trigger("submit");
}

function getBbox() {
    var minlong = document.getElementById("minlong").value;
    var minlat = document.getElementById("minlat").value;
    var maxlong = document.getElementById("maxlong").value;
    var maxlat = document.getElementById("maxlat").value;

    if (minlat && maxlat && minlong && maxlong) {
        return "" + minlong + "," + minlat + "," + maxlong + "," + maxlat;
    }
    return null;
}

function getPolygon() {
    var polygon = document.getElementById("polygoninput").value
    return polygon //returns null if not present
}

function getCircle() {
    var circlelong = document.getElementById("circlelong").value;
    var circlelat = document.getElementById("circlelat").value;
    var circlerad = document.getElementById("circlerad").value;
    if (circlelong && circlelat && circlerad) return "" + circlelong + "," + circlelat + "," + circlerad
}

/**
 * Function to compare dates of various accepted formats. This does NOT change the input dates at all.
 *
 * regex: '/^(-?[0-9]*[1-9]+0*)(-(0?[1-9]|1[012])-(0?[1-9]|[12][0-9]|3[01])(T(0?[0-9]|1[0-9]|2[0-3])(:(0?[0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9])(:(0?[0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9])([.][0-9]+)?)?)?)?)?$/'
 * OR
 * '/^(0?[1-9]|[12][0-9]|3[01])\/(0?[1-9]|1[012])\/(-?[0-9]*[1-9]+0*)( ((0?[0-9]|1[0-9]|2[0-3]):(0?[0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9])))$/'
 *
 * Will read 2 digit dates as current century, eg 01/01/21 is Jan 1st 2021
 *  If the user wants to specify an actual 2 digit date use leading zeroes, eg 0021 for the year 21CE
 *
 * IMPORTANT NOTE:
 *  * accepted formats (6 ISO friendly, 2 Excel friendly):
 *      - Year  (accepts negatives, leading zeroes, 1 to n characters, cannot be 0)                             Results in an array of size 2: [0] is full string, [1] is Year
 *      - Year-Month-Day (as above, and day/month cannot be single digits... eg: 1993-02-12 not 1993-2-12)      Results in an array of size 5: [1] is Year, [3] is Month, [4] is Day
 *      - Year-Month-DayThh      AS above but with time in hours                                                Results in an array of size 7: [1] is Year, [3] is Month, [4] is Day, [6] is Hour
 *      - Year-Month-DayThh:mm      AS above but with time in hours and minutes                                 Results in an array of size 9: [1] is Year, [3] is Month, [4] is Day, [6] is Hour, [8] is minute
 *      - Year-Month-DayThh:mm:ss (as above but with time)                                                      Results in an array of size 11: [1] is Year, [3] is Month, [4] is Day, [6] is Hour, [8] is Minute, [10] is Second
 *      - Year-Month-DayThh:mm:ss.sss (as above but with decimal seconds)                                       Results in an array of size 12: [1] is Year, [3] is Month, [4] is Day, [6] is Hour, [8] is Minute, [10] is Second, [11] is decimal seconds
 *      - Day/Month/Year (year month and day rules as above)                                                    Results in an array of size 4: [1] is Day [2] is Month, [3] is Year
 *      - Day/Month/Year hh:mm (as above but with time, no seconds as this is how excel exports csv)            Results in an array of size 8: [1] is Day, [2] is Month, [3] is Year, [6] is Hour, [7] is Minute
 *
 */
function dateMatchesRegex(dateString) {
    if (dateString.match(
            /^(-?[0-9]*[1-9]+0*)(-(0?[1-9]|1[012])-(0?[1-9]|[12][0-9]|3[01])(T(0?[0-9]|1[0-9]|2[0-3])(:(0?[0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9])(:(0?[0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9])([.][0-9]+)?)?)?)?)?$/
        )) return true;
    else if (dateString.match(
            /^(0?[1-9]|[12][0-9]|3[01])\/(0?[1-9]|1[012])\/(-?[0-9]*[1-9]+0*)( ((0?[0-9]|1[0-9]|2[0-3]):(0[0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9])))?$/
        )) return true;
    return false;
}


//Change the form type to POST before file upload, back to GET if we choose not to upload a file
//NO LONGER NEEDED, WE USE GET FOR BULKFILE UPLOAD AND GET THE FILE CONTENTS WITH AJAX

// $( function() {
//   $('#bulkfile').on('click', function() {
//     document.getElementById('searchForm').method = "POST" //Pre-change it to POST or the file upload doesnt work
//     //console.log('POST')
//     document.body.onfocus = checkFileWasSelected
//   }).on('change', function () {
//     document.getElementById("bulkfileCancel").hidden = false   
//     document.getElementById('searchForm').method = "POST" //Change to POST if a file was selected
//     //console.log('POST')
//   })

//   $('#bulkfileCancel').on('click', function () { //After a file is already selected and we hit CANCEL
//     document.getElementById("bulkfile").value = ''
//     document.getElementById('searchForm').method = "GET"  //Change back to GET
//     document.getElementById("bulkfileCancel").hidden = true
//     //console.log('GET')
//   })
// })

// function checkFileWasSelected() {
//  if (!document.getElementById("bulkfile").value.length) { //if we never selected a file (closed or cancelled)
//     document.getElementById('searchForm').method = "GET" //Change it back to GET because we didnt actually pick a file
//     //console.log('GET')
//   }
//   document.body.onfocus = null //remove this event
// }


/* When the user clicks on the filters button,
toggle between hiding and showing the dropdown content */
// function filtersDropDown() {
//     document.getElementById("myDropdown").classList.toggle("show");
//   }

//   function filterFunction() {
//     var input, filter, ul, li, a, i;
//     input = document.getElementById("myInput");
//     filter = input.value.toUpperCase();
//     div = document.getElementById("myDropdown");
//     a = div.getElementsByTagName("a");
//     for (i = 0; i < a.length; i++) {
//       txtValue = a[i].textContent || a[i].innerText;
//       if (txtValue.toUpperCase().indexOf(filter) > -1) {
//         a[i].style.display = "";
//       } else {
//         a[i].style.display = "none";
//       }
//     }
//   }