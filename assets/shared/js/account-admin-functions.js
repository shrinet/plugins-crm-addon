jQuery(document).ready(function() {
  jQuery("#account_number_filter").on("change", function(e) {
    var val = jQuery(e.target).val();
    jQuery("#account_group_filter").val("");
    jQuery(".exporter_content").html(
      "<h2>Please Wait.... Updating List....</h2>"
    );
    jQuery.ajax({
      cache: false,
      type: "POST",
      url: maclean_ajax_url,
      data: {
        action: "get_export_content",
        account_id: val,
        should_combine_groups: jQuery("#should_combine_accounts_filter").val()
      },
      success: function(data) {
        jQuery(".exporter_content").html(data);
      }
    });
  });
  jQuery("#should_combine_accounts_filter").on("change", function(e) {
    var val = jQuery(e.target).val();
    jQuery(".exporter_content").html(
      "<h2>Please Wait.... Updating List....</h2>"
    );
    var data_obj = {
      action: "get_export_content",
      should_combine_groups: val
    };
    if (jQuery("#account_number_filter").val() !== "") {
      data_obj.account_id = jQuery("#account_number_filter").val();
    }
    if (jQuery("#account_group_filter").val() !== "") {
      data_obj.account_group = jQuery("#account_group_filter").val();
    }
    if (
      typeof data_obj.account_id === "undefined" &&
      typeof data_obj.account_group === "undefined"
    ) {
      data_obj.account_id = "";
    }
    jQuery.ajax({
      cache: false,
      type: "POST",
      url: maclean_ajax_url,
      data: data_obj,
      success: function(data) {
        jQuery(".exporter_content").html(data);
      }
    });
  });
  jQuery("#account_group_filter").on("change", function(e) {
    var val = jQuery(e.target).val();
    jQuery("#account_number_filter").val("");
    jQuery(".exporter_content").html(
      "<h2>Please Wait.... Updating List....</h2>"
    );
    jQuery.ajax({
      cache: false,
      type: "POST",
      url: maclean_ajax_url,
      data: {
        action: "get_export_content",
        account_group: val,
        should_combine_groups: jQuery("#should_combine_accounts_filter").val()
      },
      success: function(data) {
        jQuery(".exporter_content").html(data);
      }
    });
  });
  jQuery(document).on("click", ".download_representatives_btn", function(e) {
    var memo = jQuery("#account_number_filter").val();
    if (memo !== "") {
      memo = "For Account Number: " + memo;
    }
    var memo = jQuery("#account_group_filter").val();
    if (memo !== "") {
      memo = "For Account Group: " + memo;
    }
    var memo_addition = jQuery("#should_combine_accounts_filter").val();
    if (memo_addition !== "false") {
      memo += " (Accounts From Groups Included In Account Numbers Column) ";
    } else {
      memo += " (Accounts From Groups Not Included In Account Numbers Column) ";
    }
    JSONToCSVConvertor(maclean_user_data, "Members Export" + memo, true);
  });
});

function JSONToCSVConvertor(JSONData, ReportTitle, ShowLabel) {
  var arrData = typeof JSONData != "object" ? JSON.parse(JSONData) : JSONData;
  var CSV = "";
  CSV += ReportTitle + "\r\n\n";
  if (ShowLabel) {
    var row = "";
    for (var index in arrData[0]) {
      row += index + ",";
    }
    row = row.slice(0, -1);
    CSV += row + "\r\n";
  }

  for (var i = 0; i < arrData.length; i++) {
    var row = "";
    for (var index in arrData[i]) {
      row += '"' + arrData[i][index] + '",';
    }
    row.slice(0, row.length - 1);
    CSV += row + "\r\n";
  }
  var formattedDate = new Date(
    Date.now() - new Date().getTimezoneOffset() * 60000
  )
    .toISOString()
    .slice(0, 19)
    .replace(/[^0-9]/g, "");
  var fileName = "Maclean_";

  fileName += ReportTitle.replace(/ /g, "_");

  if (navigator.msSaveBlob) {
    // IE 10+
    var blob = new Blob([CSV], {
      type: "text/csv;charset=utf8;"
    });
    navigator.msSaveBlob(blob, fileName + "_" + formattedDate + ".csv");
  } else {
    var uri = "data:text/csv;charset=utf-8," + escape(CSV);
    var link = document.createElement("a");
    link.href = uri;
    link.style = "visibility:hidden";
    link.download = fileName + "_" + formattedDate + ".csv";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }
}
