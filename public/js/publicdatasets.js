$(document).ready(function () {
  $("#datasettable").DataTable({
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
      { responsivePriority: 1, targets: 0 }, // keep first column longest
      { responsivePriority: 2, targets: 6 }  // keep this next
    ]
  });
});
