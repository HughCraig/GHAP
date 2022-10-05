/* Get CSRF token for POST and add it to the AJAX header */
var token = $('input[name="csrf-token"]').attr('value');
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

const maxPoints = 10 //maximum number of points in a polygon
const bboxCol = '#FFFFFF'
const polygonCol = '#FFFFFF'
const circleCol = '#FFFFFF'
//const bboxCol = '#0041AA'
//const polygonCol = '#97009c'
//const circleCol = '#0B6623'

var myshape // Define the variable for our shape, will be overwritten as we only want 1 at a time 
var shapetype = 'bbox'

/* Setup vars for leaflet draw */


var osmUrl = 'https://api.mapbox.com/styles/v1/mapbox/streets-v11/tiles/{z}/{x}/{y}?access_token={accessToken}',
    osmAttrib = 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, '
        + '<a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="https://www.mapbox.com/">Mapbox</a>',
    osm = L.tileLayer(osmUrl, {
        id: 'mapbox.streets',
        accessToken: 'pk.eyJ1IjoiYmVub3oxMSIsImEiOiJjazNpMmsyeGIwM3ZnM2JwaW9mdG9sdWl1In0.RrwSfVxBLJhqSK3aTsEaNw',
        maxZoom: 18,
        attribution: osmAttrib
    }),
    map = new L.Map('ausmap', {center: new L.LatLng(-25.753079327995454, 136.08262044870537), zoom: 4}),
    drawnItems = L.featureGroup().addTo(map);

/* Setup additional layers */

const mapOptions = {
    attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery Â© <a href="https://www.mapbox.com/">Mapbox</a>',
    maxZoom: 18,
    tileSize: 512,
    zoomOffset: -1,
    accessToken: 'pk.eyJ1IjoiYmVub3oxMSIsImEiOiJjazNpMmsyeGIwM3ZnM2JwaW9mdG9sdWl1In0.RrwSfVxBLJhqSK3aTsEaNw'
}

/* Tile Layers */
var street = L.tileLayer('https://api.mapbox.com/styles/v1/mapbox/streets-v11/tiles/{z}/{x}/{y}?access_token={accessToken}', mapOptions);

var hybrid = L.tileLayer('https://api.mapbox.com/styles/v1/mapbox/satellite-streets-v11/tiles/{z}/{x}/{y}?access_token={accessToken}', mapOptions);


L.control.layers(
    {
        "Satellite & Roads": hybrid.addTo(map),
        'Open Street Map': osm,
        "Street & Names": street,
        "Google Satellite": L.tileLayer('http://www.google.cn/maps/vt?lyrs=s@189&gl=cn&x={x}&y={y}&z={z}', {
            attribution: 'google'
        })
    },
    {},
    {position: 'topleft', collapsed: false}
).addTo(map);


/* Add the draw controls to the map  */
var drawControl = new L.Control.Draw({
    edit: {
        featureGroup: drawnItems,
        poly: {
            allowIntersection: false
        }
    },
    draw: {
        polygon: {
            allowIntersection: false, // Restricts shapes to simple polygons
            drawError: {
                color: '#ff0000', // Color the shape will turn when intersects
                message: '<strong>You cannot have sides cross over eachother!<strong>' // Message that will show when intersect
            },
            shapeOptions: {
                color: polygonCol //color of the shape itself
            }
        },
        polyline: false,
        circle: {
            allowIntersection: false, // Restricts shapes to simple polygons
            drawError: {
                color: '#ff0000', // Color the shape will turn when intersects
                message: '<strong>You cannot have sides cross over eachother!<strong>' // Message that will show when intersect
            },
            shapeOptions: {
                color: circleCol
            }
        },
        marker: false,
        circlemarker: false,
        rectangle: {
            allowIntersection: false,
            showArea: true,
            shapeOptions: {
                color: bboxCol
            }
        }
    }
})
map.addControl(drawControl);

/* EVENTS */

/* Shape Drawn event */
map.on('draw:created', function (event) {
    count = Object.keys(drawnItems._layers).length //# of shapes on map
    if (count > 0) {
        map.removeLayer(myshape) //remove the old one from the map & container
        drawnItems.removeLayer(myshape);
    }
    myshape = event.layer;
    drawnItems.addLayer(myshape)

    /* Set the input box values for each shape */
    setInputs(myshape)

});

/* Shape Edited event */
map.on('draw:edited', function (event) {
    var layers = event.layers;
    layers.eachLayer(function (layer) {
        myshape = layer; //get the topmost layer, set it as our shape
    });

    /* Set the input box values */
    setInputs(myshape)
});

/* Shape Deleted event */
map.on('draw:deleted', function (event) {
    if (!Object.keys(drawnItems._layers).length) { //only update if we deleted (count of items on map will be 0)
        resetAllInputs()
    }
});


/* Add a pin for UON */
/*
L.marker([-32.8945, 151.6976]).addTo(map)
.bindPopup('University of Newcastle, Australia')
.openPopup();
*/


/* FUNCTIONS */

function setInputs(shape) {
    resetAllInputs()
    if (myshape instanceof L.Rectangle) setRectangleInputs(shape)
    else if (myshape instanceof L.Polygon) setPolygonInputs(shape)
    else if (myshape instanceof L.Circle) setCircleInputs(shape)
}

function setRectangleInputs(shape) {
    changeShapeType('bbox')

    var pointsArr = shape.toGeoJSON().geometry.coordinates[0]; //returns a 2d array where first dimension is a point and second is array(longitude, latitude)
    var minlong = pointsArr[0][0];
    var maxlong = pointsArr[2][0];

    if (maxlong - minlong >= 360) {
        minlong = -180
        maxlong = 180
    }
    else {
        while (maxlong > 180) {
            maxlong -= 360;
        }
        while (maxlong < -180) {
            maxlong += 360;
        }
        while (minlong > 180) {
            minlong -= 360;
        }
        while (minlong < -180) {
            minlong += 360;
        }
    }


    $("#minlong").val(minlong);
    $("#minlat").val(pointsArr[0][1]);
    $("#maxlong").val(maxlong);
    $("#maxlat").val(pointsArr[2][1]);
}

function resetRectangleInputs() {
    $("#minlong").val('');
    $("#minlat").val('');
    $("#maxlong").val('');
    $("#maxlat").val('');
}

function setPolygonInputs(shape) {
    changeShapeType('polygon')

    var pointsArr = shape.toGeoJSON().geometry.coordinates[0] //returns a 2d array, where first dimension is a point and second is array(longitude, latitude)
    var out = ""
    for (var i = 0; i < pointsArr.length; i++) {
        out += pointsArr[i][0] + " " + pointsArr[i][1] + ", "
    }
    $("#polygoninput").val(out.substring(0, out.length - 2));
}

function resetPolygonInputs() {
    $("#polygoninput").val('');
}

function setCircleInputs(shape) {
    changeShapeType('circle')

    var pointsArr = shape.toGeoJSON().geometry.coordinates; //returns an array containing longitude and latitude
    $("#circlelong").val(pointsArr[0]);
    $("#circlelat").val(pointsArr[1]);
    $("#circlerad").val(shape.getRadius());
}

function resetCircleInputs() {
    $("#circlelong").val('');
    $("#circlelat").val('');
    $("#circlerad").val('');
}

function resetAllInputs() {
    resetRectangleInputs()
    resetPolygonInputs()
    resetCircleInputs()
}

function deleteShape() {
    if (myshape) {
        map.removeLayer(myshape);
        drawnItems.removeLayer(myshape);
    }
}

function changeShapeType(type) { //string: polygon bbox or circle
    $('#' + shapetype + 'div').addClass('hidden') //hide the currently showing div
    $('#' + type + 'div').removeClass('hidden') //show the new div
    shapetype = type //set the global var
    $('#mapselector').val(type + 'option') //change what is selected on the select box
}


/* FORM BUTTONS */

/* DRAW BUTTON CLICKED */
$('#mapdraw').click(function () {
    if (shapetype == 'bbox') {
        $.ajax({
            type: 'POST',
            url: '/ajaxbbox',
            data: {
                minlong: $("#minlong").val(),
                minlat: $("#minlat").val(),
                maxlong: $("#maxlong").val(),
                maxlat: $("#maxlat").val()
            },
            success: function (data) {
                var minlong = parseFloat(data.minlong);
                var minlat = parseFloat(data.minlat);
                var maxlong = parseFloat(data.maxlong);
                var maxlat = parseFloat(data.maxlat);

                //update the inputs to use the new numbers?
                $("#minlong").val(minlong);
                $("#minlat").val(minlat);
                $("#maxlong").val(maxlong);
                $("#maxlat").val(maxlat);

                if (minlong > maxlong) {
                    maxlong += 360
                } //go over 180 but only for the visual

                /* Delete old shape */
                deleteShape()

                /* Draw the polygon, if the bbox is filled out*/
                if (minlong != null && maxlong != null && minlat != null && maxlat != null
                    && minlong != "" && maxlong != "" && minlat != "" && maxlat != "") {
                    myshape = L.rectangle([[minlat, minlong], [maxlat, minlong], [maxlat, maxlong], [minlat, maxlong]], {color: bboxCol});

                    drawnItems.addLayer(myshape)
                }
            },
            error: function (xhr, textStatus, errorThrown) {
                alert(xhr.responseText); //error message with error info
            }
        });
    }

    else if (shapetype == 'polygon') { //long lat
        var polystr = $('#polygoninput') //"0 0, 0 100, 100 100, 100 0, 0 0"
        if (!polystr.val()) return alert('polygon input box is empty')

        var pointstrarr = polystr.val().split(',') //["0 0", "0 100", "100 100", "100 0", "0 0"]
        var pointsarr = []

        for (var i = 0; i < pointstrarr.length; i++) {
            var point = pointstrarr[i].trim().split(' ')
            pointsarr.push([point[1], point[0]])
        }

        /* Delete old shape */
        deleteShape()

        myshape = L.polygon(pointsarr, {color: polygonCol})
        drawnItems.addLayer(myshape)
    }


    else if (shapetype == 'circle') {
        var long = $('#circlelong').val()
        var lat = $('#circlelat').val()
        var rad = $('#circlerad').val()

        /* Delete old shape */
        deleteShape()

        myshape = L.circle([lat, long], {radius: rad, color: circleCol})
        drawnItems.addLayer(myshape)
    }
});

/* SELECTOR CHANGED */
$('#mapselector').change(function () {
    var op = $('#mapselector option:selected').val()
    var type = op.substr(0, op.indexOf('option'))
    changeShapeType(type)
})


/* Popup with lat/long where user clicks*/
/* 
var popup = L.popup();

function onMapClick(e) {
    popup
        .setLatLng(e.latlng)
        .setContent("You clicked the map at " + e.latlng.toString())
        .openOn(mymap);
}

mymap.on('click', onMapClick); */