jQuery(document).ready(function() {
  jQuery(".x-ref-submit").on("click", function(e) {
    jQuery(e.target)
      .parent()
      .trigger("submit");
  });

  jQuery(".datatable-cross-reference").each(function(index, element) {
    jQuery(element).DataTable({
      paging: false,
      responsive: true,
      buttons: [{ extend: "excel", className: "buttonsToHide" }],
      columns: [
        {
          className: "part-number"
        },
        {
          className: "manufacturer"
        },
        {
          className: "mps-catalog-number"
        },
        {
          className: "mps-description"
        },
        {
          className: "compatability-of-cross"
        },
        {
          className: "notes",
          orderable: false
        },
        {
          className: "go-to-product-page",
          orderable: false
        }
      ]
    });
  });
});
