$(document).ready(function () {
    // Init datatable.
    $("#savedsearchestable").dataTable({
        orderClasses: false,
        bPaginate: true,
        bFilter: true,
        bInfo: false,
        bSortable: true,
        bRetrieve: true,
        aaSorting: [[0, "asc"]],
        aoColumnDefs: [
            { aTargets: [4], bSortable: false, bSearchable: false },
            { aTargets: [1, 3, 4], bSearchable: false },
        ],
        pageLength: 25,
    });

    $(".openMetaDataModal").on("click", function () {
        var searchId = $(this).data("id");
        const search = searches.find((search) => search.id === searchId);

        if (search) {
            if (search.name) {
                $("#name").text(search.name);
            }

            if (search.description) {
                $("#description").html(search.description);
            }

            if (search.recordtype_id) {
                $("#type").text(recordTypeMap[search.recordtype_id]);
            }

            if (search.warning) {
                $("#warning").html(search.warning);
            }

            if (subjectKeywordMap[search.id]) {
                var keywords = subjectKeywordMap[search.id]
                    .map(function (item) {
                        return item.keyword;
                    })
                    .join(", ");
                $("#subject").html(keywords);
            }

            if (search.temporal_from) {
                $("#temporal_from").text(search.temporal_from);
            }

            if (search.temporal_to) {
                $("#temporal_to").text(search.temporal_to);
            }

            if (search.latitude_from) {
                $("#latitude_from").text(search.latitude_from);
            }
            if (search.latitude_to) {
                $("#latitude_to").text(search.latitude_to);
            }
            if (search.longitude_from) {
                $("#longitude_from").text(search.longitude_from);
            }
            if (search.longitude_to) {
                $("#longitude_to").text(search.longitude_to);
            }

            if (search.query) {
                $("#search_query").empty();
                const params = new URLSearchParams(search.query.slice(1));
                for (const [key, value] of params) {
                    $("#search_query").append(
                        `<tr><td>${key}</td><td>${value}</td></tr>`
                    );
                }
            }

            $("#savesearchmetadatamodal").modal("show");
        } else {
            alert("Error: Search not found");
        }
    });
});
