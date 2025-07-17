$(document).ready(function () {
    let selectedImageSetId = null;
    let selectedImageId = null;
    let glycerineUrl = null;

    $("#addGlycerineImageButton").on("click", function () {
        $("#addModal").modal("hide");
        $("#addGlycerineImageModal").modal("show");
    });

    function resolveRealManifestUrl(url) {
        if (url.startsWith("https://w3id.org/iaw/data/")) {
            return url.replace(
                "https://w3id.org/iaw/data/",
                "https://iaw-server.ardc-hdcl-sia-iaw.cloud.edu.au/api/"
            );
        }
        return url;
    }

    function getThumbnail(thumbnail, width = 600) {
        if (!thumbnail) return null;

        const imageUrl = thumbnail.id;
        const isIIIF =
            (thumbnail.service &&
                thumbnail.service.length > 0 &&
                thumbnail.service[0]["@id"]) ||
            thumbnail.service[0]["id"];
        if (isIIIF) {
            const base =
                thumbnail.service[0]["@id"] || thumbnail.service[0]["id"];
            return `${base}/full/${width},/0/default.jpg`;
        } else {
            return imageUrl;
        }
    }

    function showLoadingWheel() {
        document.getElementById("loadingWheel-contribute").style.display =
            "block";
    }

    function hideLoadingWheel() {
        document.getElementById("loadingWheel-contribute").style.display =
            "none";
    }

    // User load collection manifest
    $("#loadManifest").on("click", function () {
        window.glycerineUrl = null;
        let url = $("#iiifManifestInput").val();
        if (!url) {
            alert("Please enter a IIIF manifest URL");
            return;
        }

        url = resolveRealManifestUrl(url);
        showLoadingWheel();

        $("#imageSetsContainer").empty();
        $("#imagesContainer").empty();

        $.getJSON(url, function (manifest) {
            hideLoadingWheel();
            if (!manifest.items || manifest.items.length === 0) {
                alert("No image sets found in collection.");
                return;
            }

            $.each(manifest.items, function (i, set) {
                const label =
                    set.label && set.label.none
                        ? set.label.none[0]
                        : "Untitled";
                const thumbUrl = getThumbnail(set.thumbnail[0]);
                const setId = set.id;

                const html = `
                    <div class="col-md-4 mb-3 image-set-card" style="cursor:pointer;" data-manifest="${setId}">
                        <div class="card">
                            ${
                                thumbUrl
                                    ? `<img src="${thumbUrl}" class="card-img-top">`
                                    : ""
                            }
                            <div class="card-body">
                                <p class="card-text text-center">${label}</p>
                            </div>
                        </div>
                    </div>
                `;
                $("#imageSetsContainer").append(html);
            });
        }).fail(function () {
            alert("Failed to fetch or parse manifest.");
            hideLoadingWheel();
        });
    });

    // User select image set
    $(document).on("click", ".image-set-card", function () {
        selectedImageSetId = $(this).data("manifest");
        let manifestUrl = $(this).data("manifest");
        manifestUrl = resolveRealManifestUrl(manifestUrl);

        $("#imageSetsContainer").empty();
        $("#imagesContainer").empty();

        showLoadingWheel();
        $.getJSON(manifestUrl, function (manifest) {
            hideLoadingWheel();
            if (!manifest.items || manifest.items.length === 0) {
                alert("No images found in this image set.");
                return;
            }

            $.each(manifest.items, function (i, canvas) {
                const label =
                    canvas.label && canvas.label.none
                        ? canvas.label.none[0]
                        : "Untitled";
                const annotation = canvas.items?.[0]?.items?.[0];
                const imgId = canvas.id;

                const thumbUrl = getThumbnail(annotation?.body);

                const html = `
                                <div class="col-md-3 mb-3 image-thumb-card" style="cursor:pointer;" data-img-id="${imgId}">
                                    <div class="card border">
                                        <img src="${thumbUrl}" class="card-img-top">
                                        <div class="card-body p-2">
                                            <p class="card-text small text-center">${label}</p>
                                        </div>
                                    </div>
                                </div>
                            `;
                $("#imagesContainer").append(html);
            });
        }).fail(function () {
            hideLoadingWheel();
            alert("Failed to fetch image set manifest.");
        });
    });

    // User select image
    $(document).on("click", ".image-thumb-card", function () {
        $(".image-thumb-card").removeClass("border-primary").addClass("border");
        $(this).removeClass("border").addClass("border-primary");

        selectedImageId = $(this).data("img-id");
        let imageSetId = selectedImageSetId.split("/").slice(-2, -1)[0];
        glycerineUrl =
            "https://iaw.ardc-hdcl-sia-iaw.cloud.edu.au/publications/image-sets/" +
            imageSetId +
            "?startImageId=" +
            selectedImageId;
    });

    // Submit: close Glycerine modal and reopen main modal when hidden
    $("#add_glycerine_image_submit").on("click", function (e) {
        e.preventDefault();

        if (!selectedImageSetId || !selectedImageId || !glycerineUrl) {
            alert("Please select an image set and an image.");
            return;
        }

        window.glycerineUrl = glycerineUrl;

        $("#addGlycerineImageModal").modal("hide");
        setTimeout(function () {
            $("#addModal").modal("show");
        }, 400);
    });

    $("#add_glycerine_image_close").on("click", function (e) {
        e.preventDefault();

        $("#addGlycerineImageModal").modal("hide");
        setTimeout(function () {
            $("#addModal").modal("show");
        }, 400);
    });
});
