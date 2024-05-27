jQuery(document).ready(function() {
  jQuery(".fullscreen-table").on("click", function(e) {
    var target = e.target;
    var table_class = "." + jQuery(target).data("tablename") + "-table:visible";
    var table_title = jQuery(target).data("tabletitle");
    var table = jQuery(table_class).first()[0];
    var bounds = table.getBoundingClientRect();
    var height = bounds.height;
    var width = bounds.width * 1.25;
    var table = jQuery(table).clone();
    tableInst = jQuery(table).dataTable({ retrieve: true });
    tableInst.fnDestroy();
    jQuery(table).addClass("dialog-table")[0];
    if (jQuery(table).hasClass("removeLastColAE")) {
      jQuery(table)
        .find("td:last-child")
        .replaceWith("");
      jQuery(table)
        .find("th:last-child")
        .replaceWith("");
      jQuery(table)
        .find("thead tr td")
        .replaceWith("");
    }
    jQuery(table)
      .find("thead tr th")
      .removeClass("sorting")
      .removeClass("sorting_asc")
      .removeClass("sorting_desc");
    jQuery(table).addClass("reinit-table");
    jQuery(table).dialog({
      width: width,
      height: height,
      title: table_title,
      dialogClass: "table-popup",
      close: function(event, ui) {
        jQuery("html").removeClass("fullscreen-table-open");
        jQuery(".reinit-table").remove();
      },
      open: function(event, ui) {
        jQuery("html").addClass("fullscreen-table-open");
        jQuery(".reinit-table").dataTable({
          responsive: true,
          paging: false
        });
      }
    });
  });
});
