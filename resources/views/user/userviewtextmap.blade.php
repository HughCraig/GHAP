
<script>
    const logo_url = "{{ asset('img/tlcmaplogofull_sm50.png') }}";
    const home_url = "{{ url('/') }}";

    var ajaxdeletedataitem = "{{url('ajaxdeletedataitem')}}";
    var ajaxadddataitem = "{{url('ajaxadddataitem')}}";
    var ajaxaddtextcontent = "{{url('ajaxaddtextcontent')}}";
    var ajaxedittextplacecoordinates = "{{url('ajaxedittextplacecoordinates')}}";
</script>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="initial-scale=1,maximum-scale=1,user-scalable=no" />
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>TLCMap Textmap Editor Viewer</title>

        <link rel="stylesheet" href="{{ asset('css/textmap/view.css') }}" />
        <link rel="stylesheet" href="{{ asset('css/textmap/textmap.css') }}" />
        <link rel="stylesheet" href="https://js.arcgis.com/4.22/esri/themes/light/main.css" />
    </head>

    <body>
   
        <div style="display: flex; width: 100%; height: 100%">
            <div id="text">
                <div id="editPopup">
                    <button id="closePopupButton">&times;</button>

                    <div>
                        <h4 style="margin: 0">Latitude</h4>
                        <input type="number" id="latitudeInput" step="0.0001" min="-90" max="90" />

                        <h4 style="margin: 0">Longitude</h4>
                        <input type="number" id="longitudeInput" step="0.0001" min="-90" max="90" />

                        <button id="refreshMapButton" class="btn btn-default btn-sm">Refresh Map</button>
                        <button id="unsetButton" class="btn btn-default btn-sm">Unset</button>
                    </div>

                    <p style="font-size: 14px; margin-bottom: 0">
                        You can also click on the map to change the location.
                    </p>

                    <div style="display: flex" id="changeAllPlace">
                        <input type="checkbox" id="applyAllCheckbox" />
                        <label for="applyAllCheckbox" id="applyAllCheckboxText" style="font-size: 14px"></label>
                    </div>

                    <div style="margin-top: 20px">
                        <button id="saveButton" class="btn btn-primary">Save</button>
                        <button id="deleteButton" class="btn btn-highlight">Delete Place</button>
                        <button id="cancelButton" class="btn btn-secondary">Cancel</button>
                    </div>
                </div>

                <div id="textcontent"></div>

                <div id="control-buttons">
                    <button id="switchviewmode" class="btn btn-secondary">Switch to edit mode</button>
                    <button id="backtolayer" class="btn btn-secondary" style="margin-left: 3%;">View Layer</button>
                </div>
            </div>

            <div id="map">
                <div id="viewDiv"></div>
                <div id="infoDiv">
                    <img class="mdicon" />
                </div>
            </div>
        </div>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/dompurify/2.3.3/purify.min.js"></script>
        <script src="https://js.arcgis.com/4.22/"></script>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
        <script src="{{ asset('js/textmap/config-loader.js') }}"></script>
        <script src="{{ asset('js/textmap/map-configurator.js') }}"></script>
        <script src="{{ asset('js/textmap/textmap.js') }}"></script>
    </body>
</html>