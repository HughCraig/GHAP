class MobilityFields {
    /**
     * Clears and disables input fields and select elements within the route group element in EditDataitem modal
     * @param {string} routeGroupSelector - Selector for the route group container
     */
    static clearRouteFields(routeGroupSelector) {
        var $inputs = $(routeGroupSelector)
            .find("input")
            .not(".route-options-container input");
        var $selects = $(routeGroupSelector).find("select");
        $inputs.val("").prop({
            required: false,
            readonly: true,
        });
        $selects.val("").prop({
            required: false,
            disabled: true,
        });
        $selects.empty();
    }

    /**
     * Clears all mobility-related fields and hides relevant sections in EditDataitem modal
     * @param {string} qtySelector - Selector for quantity input
     * @param {string} qtyGroupSelector - Selector for quantity group container
     * @param {string} routeGroupSelector - Selector for route group container
     * @param {string} routeOptionsContainerSelector - Selector for route options container
     */
    static clearMobilityFields(
        qtySelector,
        qtyGroupSelector,
        routeGroupSelector,
        routeOptionsContainerSelector = null
    ) {
        // hide mobility relavant fields
        $(qtyGroupSelector).hide();
        $(routeGroupSelector).hide();
        // clear all mobility values
        $(qtySelector).val("");
        MobilityFields.clearRouteFields(routeGroupSelector);
        if (routeOptionsContainerSelector !== null) {
            $(routeOptionsContainerSelector).empty();
        }
    }

    /**
     * Generates route options based on the dataitem's related route information
     * @param {Object} dataitem - The data item containing route information
     * @return {Array} An array of option objects with value and label properties
     */
    static generateRouteOptions(dataitem) {
        const options = [
            { value: "keep", label: "Do not change" },
            { value: "update-new", label: "Update to a new route" },
        ];
        if (dataitem.route_id !== null) {
            options.push(
                { value: "drop", label: "Drop from current route" },
                {
                    value: "update-current",
                    label: "Update in current route",
                }
            );
        }
        if (dataitem.has_other_routes) {
            options.push({
                value: "update-existing",
                label: "Update to an existing route",
            });
        }

        return options;
    }

    /**
     * Generates HTML for route options
     * @param {Array} options - An array of route option objects with value and label properties
     * @return {string} HTML string for route options
     */
    static generateRouteOptionsHTML(options) {
        let html = "";
        for (let option of options) {
            html += `
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="routeOption" id="routeOption${option.value}" value="${option.value}">
              <label class="form-check-label" for="routeOption${option.value}">${option.label}</label>
            </div>
          `;
        }
        return html;
    }

    /**
     * Sets up the default route option and triggers a change event
     * @param {string} routeOptionsContainer - Selector for the route options container
     * @param {string} routeOptionSelector - Selector for the route option elements
     * @param {string} defaultOption - The default option value (default is "keep")
     */
    static setupDefaultRouteOption(
        routeOptionsContainer,
        routeOptionSelector,
        defaultOption = "keep"
    ) {
        $(
            `${routeOptionsContainer} ${routeOptionSelector}[value="${defaultOption}"]`
        )
            .prop("checked", true)
            .trigger("change");
    }

    /**
     * Handles changes in the record type, showing or hiding mobility-related fields
     * @param {string} selectedRecordType - The selected record type
     * @param {string} qtySelector - Selector for the quantity input
     * @param {string} qtyGroupSelector - Selector for the quantity group container
     * @param {string} routeGroupSelector - Selector for the route group container
     * @param {string} routeOptionSelector - Selector for the route option elements
     * @param {string} routeOptionsContainerSelector - Selector for the route options container
     */
    static handleRecordTypeChange(
        selectedRecordType,
        qtySelector,
        qtyGroupSelector,
        routeGroupSelector,
        routeOptionSelector,
        routeOptionsContainerSelector
    ) {
        if (selectedRecordType === "Mobility") {
            $(qtyGroupSelector).show();
            $(routeGroupSelector).show();
        } else {
            MobilityFields.clearMobilityFields(
                qtySelector,
                qtyGroupSelector,
                routeGroupSelector,
                routeOptionsContainerSelector
            );
            $(routeOptionSelector).off("change");
        }
    }

    /**
     * Initializes mobility fields with data item information and sets up event handlers
     * @param {Object} dataitem - The data item containing mobility information
     * @param {string} csrfToken - CSRF token for secure requests
     * @param {string} qtySelector - Selector for the quantity input
     * @param {string} routeOptionsContainerSelector - Selector for the route options container
     * @param {string} routeOptionSelector - Selector for the route option elements
     * @param {string} stopIdxSelector - Selector for the stop index element
     * @param {string} routeIdSelector - Selector for the route ID element
     * @param {string} routeTitleSelector - Selector for the route title element
     * @param {string} routeDescriptionSelector - Selector for the route description element
     * @param {string} routeGroupSelector - Selector for the route group container
     * @param {string} routeIdStopIdxGroupSelector - Selector for the route ID and stop index group
     * @param {string} routeTitleGroupSelector - Selector for the route title group
     * @param {string} routeDescriptionGroupSelector - Selector for the route description group
     */
    static initializeMobilityFields(
        dataitem,
        csrfToken,
        qtySelector,
        routeOptionsContainerSelector,
        routeOptionSelector,
        stopIdxSelector,
        routeIdSelector,
        routeTitleSelector,
        routeDescriptionSelector,
        routeGroupSelector,
        routeIdStopIdxGroupSelector,
        routeTitleGroupSelector,
        routeDescriptionGroupSelector
    ) {
        // Set the quantity field value
        let qtyValue = dataitem.quantity;
        $(qtySelector).val(qtyValue);

        // Generate route options
        const routeOptions = MobilityFields.generateRouteOptions(dataitem);
        const optionsHTML =
            MobilityFields.generateRouteOptionsHTML(routeOptions);
        $(routeOptionsContainerSelector).html(optionsHTML);

        // Set the default route option
        MobilityFields.setupDefaultRouteOption(
            routeOptionsContainerSelector,
            routeOptionSelector
        );

        // Define route option handlers
        const routeOptionHandlers = {
            keep: () =>
                MobilityFields.editDataitemTrcjManipulateCurrent(
                    dataitem,
                    stopIdxSelector,
                    routeIdSelector,
                    routeTitleSelector,
                    routeDescriptionSelector,
                    routeGroupSelector,
                    routeIdStopIdxGroupSelector,
                    routeTitleGroupSelector,
                    routeDescriptionGroupSelector
                ),
            drop: () =>
                MobilityFields.editDataitemTrcjManipulateCurrent(
                    dataitem,
                    stopIdxSelector,
                    routeIdSelector,
                    routeTitleSelector,
                    routeDescriptionSelector,
                    routeGroupSelector,
                    routeIdStopIdxGroupSelector,
                    routeTitleGroupSelector,
                    routeDescriptionGroupSelector
                ),
            "update-current": () =>
                MobilityFields.editDataitemTrcjManipulateCurrent(
                    dataitem,
                    stopIdxSelector,
                    routeIdSelector,
                    routeTitleSelector,
                    routeDescriptionSelector,
                    routeGroupSelector,
                    routeIdStopIdxGroupSelector,
                    routeTitleGroupSelector,
                    routeDescriptionGroupSelector,
                    true
                ),
            "update-new": () =>
                MobilityFields.editDataitemTrcjUpdateNew(
                    routeIdStopIdxGroupSelector,
                    routeTitleSelector,
                    routeDescriptionSelector,
                    routeGroupSelector,
                    routeTitleGroupSelector,
                    routeDescriptionGroupSelector
                ),
            "update-existing": () =>
                MobilityFields.editDataitemTrcjUpdateExisting(
                    dataitem.id,
                    csrfToken,
                    routeGroupSelector,
                    routeIdSelector,
                    stopIdxSelector,
                    routeTitleSelector,
                    routeDescriptionSelector,
                    routeIdStopIdxGroupSelector,
                    routeTitleGroupSelector,
                    routeDescriptionGroupSelector
                ),
        };

        // Bind route option change event
        $(routeOptionSelector)
            .off("change")
            .on("change", function () {
                const selectedOption = $(this).val();
                const handler = routeOptionHandlers[selectedOption];
                if (handler) {
                    handler();
                } else {
                    console.error(
                        `Unsupported route option: ${selectedOption}`
                    );
                }
            });

        // Initialize route details
        MobilityFields.editDataitemTrcjManipulateCurrent(
            dataitem,
            stopIdxSelector,
            routeIdSelector,
            routeTitleSelector,
            routeDescriptionSelector,
            routeGroupSelector,
            routeIdStopIdxGroupSelector,
            routeTitleGroupSelector,
            routeDescriptionGroupSelector
        );
    }

    /**
     * Initializes route details in the UI
     * @param {number} currStopIdx - Current stop index
     * @param {string} currTjcId - Current route ID
     * @param {Object} currTjcDetails - Current route details
     * @param {string} StopIdxSelector - Selector for the stop index value element
     * @param {string} routeIdSelector - Selector for the route ID value element
     * @param {string} routeTitleSelector - Selector for the route title value element
     * @param {string} routeDescriptionSelector - Selector for the route description value element
     */
    static initializeRouteDetails(
        currStopIdx,
        currTjcId,
        currTjcDetails,
        StopIdxSelector,
        routeIdSelector,
        routeTitleSelector,
        routeDescriptionSelector
    ) {
        $(routeIdSelector).append(
            $("<option>", {
                value: currTjcId,
                text: currTjcId,
            })
        );
        $(routeIdSelector).val(currTjcId);

        const currAllStopIndices = Object.values(
            currTjcDetails.allStopIndices
        ).sort(function (a, b) {
            return a - b;
        });
        $.each(currAllStopIndices, function (index, stopIdx) {
            let isCurrentStop = stopIdx === currStopIdx;
            let stopText = isCurrentStop ? `${stopIdx} (Current)` : stopIdx;
            $(StopIdxSelector).append(
                $("<option>", { value: stopIdx, text: stopText })
            );
        });
        $(StopIdxSelector).val(currStopIdx);

        $(routeTitleSelector).val(currTjcDetails.title);
        $(routeDescriptionSelector).val(currTjcDetails.description);
    }

    /**
     * Manipulates the current route fields based on the provided dataitem in EditDataitem modal
     * @param {Object} data - The data object containing route information
     * @param {string} stopIdxSelector - Selector for the route stop index element
     * @param {string} routeIdSelector - Selector for the route ID element
     * @param {string} routeTitleSelector - Selector for the route title element
     * @param {string} routeDescriptionSelector - Selector for the route description element
     * @param {string} routeGroupSelector - Selector for the route group container
     * @param {string} routeIdStopIdxGroupSelector - Selector for the route ID and stop index group
     * @param {string} routeTitleGroupSelector - Selector for the route title group
     * @param {string} routeDescriptionGroupSelector - Selector for the route description group
     * @param {boolean} isUpdateMetadata - Flag to determine if metadata of route (title, description and stop_idx) should be updated
     */
    static editDataitemTrcjManipulateCurrent(
        data,
        stopIdxSelector,
        routeIdSelector,
        routeTitleSelector,
        routeDescriptionSelector,
        routeGroupSelector,
        routeIdStopIdxGroupSelector,
        routeTitleGroupSelector,
        routeDescriptionGroupSelector,
        isUpdateMetadata = false
    ) {
        MobilityFields.clearRouteFields(routeGroupSelector);
        if (data.route_id && data.currentRouteDetails) {
            MobilityFields.initializeRouteDetails(
                data.stop_idx,
                data.route_id,
                data.currentRouteDetails,
                stopIdxSelector,
                routeIdSelector,
                routeTitleSelector,
                routeDescriptionSelector
            );
        }

        // $(routeGroupSelector).show();
        $(routeIdStopIdxGroupSelector).show();
        $(routeTitleGroupSelector).show();
        $(routeDescriptionGroupSelector).show();

        if (isUpdateMetadata) {
            $(stopIdxSelector).prop({ disabled: false, required: true });
            $(routeTitleSelector).prop({
                readonly: false,
                required: true,
            });
            $(routeDescriptionSelector).prop("readonly", false);
        }
    }

    /**
     * Updates the UI for creating a new route in EditDataitem modal
     * @param {string} routeIdStopIdxGroupSelector - Selector for the route ID and stop index group
     * @param {string} routeTitleSelector - Selector for the route title element
     * @param {string} routeDescriptionSelector - Selector for the route description element
     * @param {string} routeGroupSelector - Selector for the route group container
     * @param {string} routeTitleGroupSelector - Selector for the route title group
     * @param {string} routeDescriptionGroupSelector - Selector for the route description group
     */
    static editDataitemTrcjUpdateNew(
        routeIdStopIdxGroupSelector,
        routeTitleSelector,
        routeDescriptionSelector,
        routeGroupSelector,
        routeTitleGroupSelector,
        routeDescriptionGroupSelector
    ) {
        // initialize route information input and selection
        MobilityFields.clearRouteFields(routeGroupSelector);
        $(routeIdStopIdxGroupSelector).hide();
        $(routeTitleSelector).prop({
            readonly: false,
            required: true,
        });
        $(routeDescriptionSelector).prop("readonly", false);
        // $(routeGroupSelector).show();
        $(routeTitleGroupSelector).show();
        $(routeDescriptionGroupSelector).show();
    }

    /**
     * Updates the UI for selecting an existing route in EditDataitem modal
     * Details of other routes are fetched within `MobilityFields.populateRoutes`
     * @param {string} dataitemID - The ID of the dataitem
     * @param {string} csrfToken - CSRF token for secure requests
     * @param {string} routeGroupSelector - Selector for the route group container
     * @param {string} routeIdSelector - Selector for the route ID element
     * @param {string} stopIdxSelector - Selector for the stop index element
     * @param {string} routeTitleSelector - Selector for the route title element
     * @param {string} routeDescriptionSelector - Selector for the route description element
     * @param {string} routeIdStopIdxGroupSelector - Selector for the route ID and stop index group
     * @param {string} routeTitleGroupSelector - Selector for the route title group
     * @param {string} routeDescriptionGroupSelector - Selector for the route description group
     */
    static async editDataitemTrcjUpdateExisting(
        dataitemID,
        csrfToken,
        routeGroupSelector,
        routeIdSelector,
        stopIdxSelector,
        routeTitleSelector,
        routeDescriptionSelector,
        routeIdStopIdxGroupSelector,
        routeTitleGroupSelector,
        routeDescriptionGroupSelector
    ) {
        // initialize route information input and selection
        MobilityFields.clearRouteFields(routeGroupSelector);
        const fetchOtherRoutesDetailsUrl = updateOtherRoutesDetailsUrl.replace(
            "{dataitemId}",
            dataitemID
        );
        await MobilityFields.populateRoutes(
            fetchOtherRoutesDetailsUrl,
            csrfToken,
            routeIdSelector,
            routeTitleSelector,
            routeDescriptionSelector,
            stopIdxSelector
        );
        $(routeIdSelector).prop({
            disabled: false,
            required: true,
        });
        $(stopIdxSelector).prop({
            disabled: false,
            required: true,
        });
        $(routeTitleSelector).prop({
            readonly: false,
            required: true,
        });
        $(routeDescriptionSelector).prop("readonly", false);
        $(routeIdStopIdxGroupSelector).show();
        $(routeTitleGroupSelector).show();
        $(routeDescriptionGroupSelector).show();
    }

    /**
     * Fetches and populates details of other routes that current dataitem isn't associated with, using session storage for caching
     * @param {string} fetchUrl - The URL to fetch details of other routes that current dataitem isn't associated with
     * @param {string} csrfToken - CSRF token for secure requests
     * @param {string} routeIdSelector - Selector for the route ID element
     * @param {string} routeTitleSelector - Selector for the route title element
     * @param {string} routeDescriptionSelector - Selector for the route description element
     * @param {string} stopIdxSelector - Selector for the stop index element
     */
    static async populateRoutes(
        fetchUrl,
        csrfToken,
        routeIdSelector,
        routeTitleSelector,
        routeDescriptionSelector,
        stopIdxSelector
    ) {
        try {
            let otherRoutesDetails;
            const cacheKey = `route_${fetchUrl}`;

            const cachedData = sessionStorage.getItem(cacheKey);
            if (cachedData) {
                otherRoutesDetails = JSON.parse(cachedData);
            } else {
                const response = await fetch(fetchUrl, {
                    method: "GET",
                    headers: {
                        "X-CSRF-TOKEN": csrfToken,
                    },
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                otherRoutesDetails = await response.json();
                sessionStorage.setItem(
                    cacheKey,
                    JSON.stringify(otherRoutesDetails)
                );
            }

            otherRoutesDetails.forEach((route) => {
                $(routeIdSelector).append(
                    $("<option>", {
                        value: route.id,
                        text: `${route.id} -- ${route.title}`,
                    })
                );
            });

            $(routeIdSelector)
                .off("change")
                .on("change", function () {
                    const selectedId = $(this).val();
                    const selectedRoute = otherRoutesDetails.find(
                        (t) => t.id == selectedId
                    );
                    if (selectedRoute) {
                        $(routeTitleSelector).val(selectedRoute.title);
                        $(routeDescriptionSelector).val(
                            selectedRoute.description
                        );
                        MobilityFields.updateStopIdxOptions(
                            stopIdxSelector,
                            selectedRoute.allStopIndices
                        );
                    }
                });

            if (otherRoutesDetails.length > 0) {
                $(routeIdSelector)
                    .val(otherRoutesDetails[0].id)
                    .trigger("change");
            }
        } catch (error) {
            console.error("Error in populateRoutes:", error);
            throw error;
        }
    }

    /**
     * Updates the stop index options in the select element
     * @param {string} stopIdxSelector - Selector for the stop index element
     * @param {Array} stopIndices - Array of stop indices to populate the select element
     */
    static updateStopIdxOptions(stopIdxSelector, stopIndices) {
        $(stopIdxSelector).empty();
        $(stopIdxSelector).append(
            $("<option>", {
                value: "append",
                text: "Append",
            })
        );
        stopIndices = Object.values(stopIndices).sort(function (a, b) {
            return a - b;
        });
        $.each(stopIndices, function (index, stopIdx) {
            $(stopIdxSelector).append(
                $("<option>", { value: stopIdx, text: stopIdx })
            );
        });
        $(stopIdxSelector).val("append");
    }
}
