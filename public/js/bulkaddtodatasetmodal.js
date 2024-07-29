document
    .getElementById("uploadMobDatasetWithRoute")
    .addEventListener("change", function () {
        const mobilityOptions = document.getElementById("mobilityRouteOptions");
        mobilityOptions.style.display = this.checked ? "block" : "none";

        if (this.checked) {
            const addNewPlacesRadio = document.querySelector(
                'input[name="datasetPurpose"][value="addNewPlaces"]'
            );
            if (addNewPlacesRadio) {
                addNewPlacesRadio.checked = true;
            }
        } else {
            document.getElementById("isODPairs").checked = false;
            const radioBtns = mobilityOptions.querySelectorAll(
                'input[name="datasetPurpose"]'
            );
            radioBtns.forEach((btn) => (btn.checked = false));
        }
    });
