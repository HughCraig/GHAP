// w3css sidebar
$(document).ready(function () {
    //$("div#mainnav a[href*='" + location.pathname + "']").removeClass("w3-orange");
    $("div#mainnav a[href='" + location.pathname + "']").addClass("navselectedpage");
});

function w3_open() {
    document.getElementById("mySidebar").style.display = "block";
}

function w3_close() {
    document.getElementById("mySidebar").style.display = "none";
}

function dropClick() {
    var x = document.getElementById("navMapDD");
    if (x.className.indexOf("w3-show") == -1) {
        x.className += " w3-show";
    } else {
        x.className = x.className.replace(" w3-show", "");
    }
}

// this one is for the nav dropdowns... i think. maybe it's redundant
function concertina(id) {
    var x = document.getElementById(id);
    if (x.className.indexOf("w3-show") == -1) {
        x.className += " w3-show";
    } else {
        x.className = x.className.replace(" w3-show", "");
    }
}

// this one is for drop downs on page
function concertinify(id) {
    var x = document.getElementById(id);
    if (x.className.indexOf("w3-show") == -1) {
        x.className += " w3-show";
    } else {
        x.className = x.className.replace(" w3-show", "");
    }
}