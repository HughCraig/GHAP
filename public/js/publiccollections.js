$(document).ready(function () {
  $("#collectionsTable").DataTable({
    orderClasses: false,
    paging: true,
    searching: true,
    info: false,
    retrieve: true,
    responsive: true,
    order: [[0, "asc"]],
    pageLength: 25,
    language: {
      search: "Filter list:"
    },
    columnDefs: [
      { responsivePriority: 1, targets: 0 }, // most important column
      { responsivePriority: 2, targets: 5 }  // second priority column
    ]
  });
});