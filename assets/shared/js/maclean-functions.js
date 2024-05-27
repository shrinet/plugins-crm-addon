function onlyUnique(value, index, self) {
  return self.indexOf(value) === index;
}

function objectifyForm(formArray) {
  //serialize data function
  var returnArray = {};
  for (var i = 0; i < formArray.length; i++) {
    if (typeof returnArray[formArray[i]["name"]] !== "undefined") {
      if (!Array.isArray(returnArray[formArray[i]["name"]])) {
        var old_val = returnArray[formArray[i]["name"]];
        returnArray[formArray[i]["name"]] = [];
        returnArray[formArray[i]["name"]].push(old_val);
      }
      returnArray[formArray[i]["name"]].push(formArray[i]["value"]);
    } else {
      returnArray[formArray[i]["name"]] = formArray[i]["value"];
    }
  }
  return returnArray;
}

function get_address_components(address_components) {
  var components = {};
  jQuery.each(address_components, function(k, v1) {
    jQuery.each(v1.types, function(k2, v2) {
      components[v2] = [v1.long_name, v1.short_name];
    });
  });
  return components;
}

function validatePhone(phone) {
  var filter = /^((\+[1-9]{1,4}[ \-]*)|(\([0-9]{2,3}\)[ \-]*)|([0-9]{2,4})[ \-]*)*?[0-9]{3,4}?[ \-]*[0-9]{3,4}?$/;
  return filter.test(phone);
}
function validateEmail(email) {
  var filter = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
  return filter.test(email);
}

function validateUrl(url) {
  var filter = /^(http|https):\/\/[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,5}(:[0-9]{1,5})?(\/.*)?$/i;
  return filter.test(url);
}

function validateNotEmpty(val) {
  return val !== "" && val !== null && typeof val !== "undefinded";
}

function submitRepresentativesRequestForm(form_data) {
  if (typeof form_data.accounts !== "undefined") {
    if (!Array.isArray(form_data.accounts)) {
      form_data.accounts = [form_data.accounts];
    }
  }
  jQuery.ajax({
    cache: false,
    type: "POST",
    url: maclean_ajax_url,
    data: form_data,
    success: function(data) {
      data = JSON.parse(data);
      if (typeof data.message !== "undefined") {
        if (data.message.indexOf("Success") !== -1) {
          jQuery("#representative_request_form").hide();
          jQuery(".add_representative_header").hide();
          jQuery(".add_another_representative").show();
        }
        jQuery(".representative_request_error_messages").html(data.message);
        jQuery(".divTablePrimaryHeader").after(data.table);
        jQuery(".divTablePrimaryHeader")
          .parent()
          .find(".divTableRow.noRepresentatives")
          .remove();
        if (typeof data.table_col !== "undefined") {
          jQuery("#representative_request_form").show();
          jQuery(".add_another_representative").hide();
          jQuery(".edit_accounts_header").hide();
          jQuery(".add_representative_header").show();
          jQuery("#edit_accounts_content").html("");
          jQuery(".divTableRow." + data.row_class)
            .find(".divTableCell:nth-child(" + data.col_index + ")")
            .first()
            .html(data.table_col);
        }
      } else {
        if (data.indexOf("Success") !== -1) {
          jQuery("#representative_request_form").hide();
          jQuery(".add_another_representative").show();
        }
        jQuery(".representative_request_error_messages").html(data);
      }
    }
  });
}
