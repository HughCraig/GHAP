<?php
include '../head.php';
?>

    <div class="simplepage">

        <h2>3D Visualisation</h2>

        <p>These are two tools for showing a place given a URL with information about it
        <h3>3d Place</h3>

        <p>A tool for showing a place given a URL with information about it. This can be handy for constructing a URL
            you could send to someone, or put on your web page, or for developers to integrate into apps. You can simply
            provide 'latlng' coordinates in the address, like this:
        </p>
        <p>
            <a href="http://tlcmap.org/3d/place.html?latlng=-25.344416,%20131.036974">http://tlcmap.org/3d/place.html?latlng=-25.344416,%20131.036974</a>
        </p>
        <p>
            You can optionally also provide name, id, description and a 'linkback' (a URL to reference the source of
            this information, to an entry in the gazetteer, or your research on this place, etc) like this:
        </p>
        <p>
            <a href="http://tlcmap.org/3d/place.html?latlng=-25.344416,%20131.036974&name=Uluru&id=286532&description=A large sacred sandstone monolith at the heart of Australia.&linkback=http://tlcmap.org/ghap/search?anps_id=286530">
                http://tlcmap.org/3d/place.html?latlng=-25.344416,%20131.036974&name=Uluru&id=286532&description=A large
                sacred sandstone monolith at the heart of
                Australia.&linkback=http://tlcmap.org/ghap/search?anps_id=286530</a>
        </p>

        <h3>3d Places</h3>
        <p>Visualise set of places on a 3D map by providing the URL of a GeoJSON file. This is a prototype and does not
            yet work outside of TLCMap systems due to COR security issues. The URL of the GeoJSON file must be percent
            encoded.
        </p>
        <p>Example, using the GeoJSON output of the Gazza fuzzy search for 'Newcastle':
        </p>
        <p>
            <a href="http://tlcmap.org/3d/places.html?load=http://tlcmap.org/ghap/search?fuzzyname=Newcastle%26searchausgaz=on%26searchpublicdatasets=on%26format=json">http://tlcmap.org/3d/places.html?load=http://tlcmap.org/ghap/search?fuzzyname=Newcastle%26searchausgaz=on%26searchpublicdatasets=on%26format=json</a>
        </p>


        <p>
            Enhancements are planned for both of these tools to make them easier to use.
        </p>

    </div>

<?php
include '../foot.php';
?>