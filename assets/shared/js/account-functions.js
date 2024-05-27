(function($) {
  $(function() {
    $(document).on("click", ".confirm_member_status", function(e) {
      $.ajax({
        cache: false,
        type: "POST",
        url: maclean_ajax_url,
        data: {
          action: "confirm_member_status"
        },
        success: function(data) {
          alert("Member Status Confirmed!");
          $(e.target).remove();
        }
      });
    });
    $(document).on("click", ".confirm_account_status", function(e) {
      $.ajax({
        cache: false,
        type: "POST",
        url: maclean_ajax_url,
        data: {
          action: "confirm_account_status"
        },
        success: function(data) {
          alert("Account Status Confirmed!");
          $(e.target).remove();
        }
      });
    });
    $(document).on("click", ".confirm_account_information", function(e) {
      $.ajax({
        cache: false,
        type: "POST",
        url: maclean_ajax_url,
        data: {
          action: "confirm_account_information"
        },
        success: function(data) {
          alert("Account Information Confirmed!");
          $(e.target).remove();
        }
      });
    });
    $(document).on("click", ".reset_password", function(e) {
      location.href = $(e.target).data("url");
    });
    $("#address_full").on("change", function(e) {
      parseAccountRegistrationAddress(e.target);
    });
    $(document).on("click", ".delete_sub_representative", function(e) {
      e.preventDefault();
      $.ajax({
        cache: false,
        type: "POST",
        url: maclean_ajax_url,
        data: {
          action: "delete_sub_representative",
          sub_representative_user_id: $(e.target).data("userid")
        },
        success: function(data) {
          $(e.target)
            .parent()
            .parent()
            .remove();
          if (
            $(".divTablePrimaryHeader")
              .parent()
              .find(".divTableRow").length === 2
          ) {
            $.ajax({
              cache: false,
              type: "POST",
              url: maclean_ajax_url,
              data: {
                action: "no_representatives"
              },
              success: function(data) {
                $(".divTablePrimaryHeader").after(data);
              }
            });
            alert(data);
          }
        }
      });
    });
    $("#does_business_with").on("change", function(e) {
      if ($(e.target).val() == "yes") {
        $("#does_business_with_other").val("n/a");
        $("#how_did_you_hear_about_us").text("n/a");
      }
    });
    $(".add_another_representative").on("click", function(e) {
      $(e.target).hide();
      $("#representative_request_form").show();
      $(".add_representative_header").show();
      $(".representative_request_error_messages").html("");
    });
    $(document).on("submit", "#representative_edit_accounts_form", function(e) {
      e.preventDefault();
      var form_data = objectifyForm(
        $("#representative_edit_accounts_form").serializeArray()
      );
      form_data.action = "update_accounts";
      $(".representative_request_error_messages").html("");
      submitRepresentativesRequestForm(form_data);
    });
    $(document).on("click", ".edit_sub_representative_accounts", function(e) {
      $.ajax({
        cache: false,
        type: "POST",
        url: maclean_ajax_url,
        data: {
          action: "edit_accounts_ajax",
          user_id: $(e.target).data("userid")
        },
        success: function(data) {
          $("#representative_request_form").hide();
          $(".edit_accounts_header").show();
          $(".add_representative_header").hide();
          $("#edit_accounts_content").html(data);
        }
      });
    });

    // REPRESENTATIVE REQUEST FORM =================================================================================

    var RepresentativeRequestForm = (function() {
      var $form = $("#representative_request_form");

      var $submit = $form.find("#representative_edit_accounts_form_submit");

      var $title = $(".submit-heading");

      var $inputs = {
        firstName: $form.find("#first_name"),
        lastName: $form.find("#last_name"),
        email: $form.find("#email"),
        password1: $form.find("#password_primary"),
        password2: $form.find("#password_secondary"),
        //websiteText: $form.find("#website"),
        phoneText: $form.find("#phone"),
        cityText: $form.find("#city"),
        stateText: $form.find("#state"),
        zipText: $form.find("#zip"),
        countryText: $form.find("#country"),
        salesRepAgencyText: $form.find("#sales_rep_agency"),
        salesRepContactNameText: $form.find("#sales_rep_contact_name")
        //businessWithMacleanSelect: $form.find("#business_with_maclean"),
        //businessWithOtherText: $form.find("#business_with_other")
      };

      var $errors = {
        firstName: $form.find(".first-name .error"),
        lastName: $form.find(".last-name .error"),
        email: $form.find(".email .error"),
        password1: $form.find(".password.primary .error"),
        password2: $form.find(".password.secondary .error"),
        //website: $form.find(".website .error"),
        phone: $form.find(".phone .error"),
        city: $form.find(".city .error"),
        state: $form.find(".state .error"),
        zip: $form.find(".zip .error"),
        country: $form.find(".country .error")
        //businessWithOther: $form.find(".business-with-other .error")
      };

      // error messages
      var $messagesDiv = $("#rep_request_messages");
      var $successMessageDiv = $messagesDiv.find("div.success");
      var $errorMessageDiv = $messagesDiv.find("div.error");
      var $errorP = $messagesDiv.find("#error_message");

      var submitClickedFirstTime = false;

      $form.on("change", "input", function() {
        if (submitClickedFirstTime) validate();
      });

      //   $inputs.businessWithMacleanSelect.on("change", function() {
      //     if ($(this).val() === "no") {
      //       $inputs.businessWithOtherText.prop("required", true);
      //       $inputs.businessWithOtherText.prop("disabled", false);
      //       $inputs.businessWithOtherText.attr("placeholder", "");
      //     } else {
      //       $inputs.businessWithOtherText.prop("required", false);
      //       $inputs.businessWithOtherText.prop("disabled", true);
      //       $inputs.businessWithOtherText.attr("placeholder", "n/a");
      //       $inputs.businessWithOtherText.val("");
      //     }
      //   });

      function validate() {
        var valid = true;

        // first name
        if (!validateNotEmpty($inputs.firstName.val())) {
          $errors.firstName.show();
          valid = false;
        } else {
          $errors.firstName.hide();
        }

        // last name
        if (!validateNotEmpty($inputs.lastName.val())) {
          $errors.lastName.show();
          valid = false;
        } else {
          $errors.lastName.hide();
        }

        // email
        if (!validateEmail($inputs.email.val())) {
          $errors.email.show();
          valid = false;
        } else $errors.email.hide();

        // password
        if ($inputs.password1.val() !== $inputs.password2.val()) {
          $errors.password1.show();
          $errors.password2.show();
          valid = false;
        } else {
          $errors.password1.hide();
          $errors.password2.hide();
        }

        // // website
        // if ( ! validateUrl( $inputs.websiteText.val() ) ) {
        //     $errors.website.show();
        //     valid = false;
        // } else
        //     $errors.website.hide();

        // phone
        if (!validatePhone($inputs.phoneText.val())) {
          $errors.phone.show();
          valid = false;
        } else {
          $errors.phone.hide();
        }

        // city
        if (!validateNotEmpty($inputs.cityText.val())) {
          $errors.city.show();
          valid = false;
        } else {
          $errors.city.hide();
        }

        // state
        if (!validateNotEmpty($inputs.stateText.val())) {
          $errors.state.show();
          valid = false;
        } else {
          $errors.state.hide();
        }

        // zip
        if (!validateNotEmpty($inputs.zipText.val())) {
          $errors.zip.show();
          valid = false;
        } else {
          $errors.zip.hide();
        }

        // country
        if (!validateNotEmpty($inputs.countryText.val())) {
          $errors.country.show();
          valid = false;
        } else {
          $errors.country.hide();
        }

        // // business with other
        // if ( $inputs.businessWithMacleanSelect.val() === 'no'
        //     && ! validateNotEmpty( $inputs.businessWithOtherText.val() )
        // ) {
        //     $errors.businessWithOther.show();
        //     valid = false;
        // } else {
        //     $errors.businessWithOther.hide();
        // }
        return valid;
      }

      $submit.on("click", function() {
        submitClickedFirstTime = true;
        validate();
      });

      $form.on("submit", function(event) {
        event.preventDefault();

        if (validate()) {
          $form.LoadingOverlay("show");

          var formData = objectifyForm($form.serializeArray());
          formData.action = "add_representative_request_submission";

          jQuery.ajax({
            cache: false,
            type: "POST",
            url: maclean_ajax_url,
            data: formData,
            success: function(data) {
              data = JSON.parse(data);

              $form.hide();
              $messagesDiv.show();
              $title.hide();

              var success = data.success || false;
              var message = data.message || false;

              if (success) {
                $successMessageDiv.show();
              } else {
                jQuery("h1.submit-heading").hide();
                $errorP.html(
                  message ||
                    "An error occurred, please reload the page and try again."
                );
                $errorMessageDiv.show();
              }

              $form.LoadingOverlay("hide");
            },
            error: function() {
              $errorP.text(
                "An error occurred, please reload the page and try again."
              );
              $errorMessageDiv.show();

              $form.LoadingOverlay("hide");
            }
          });
        }
      });
    })();
  });
})(jQuery);

function parseAccountRegistrationAddress(element) {
  //In this case it gets the address from an element on the page, but obviously you  could just pass it to the method
  // instead
  var address = jQuery(element).val();

  geocoder.geocode({ address: address }, function(results, status) {
    if (status == google.maps.GeocoderStatus.OK) {
      var components = get_address_components(results[0].address_components);
      if (
        results.length === 0 ||
        typeof components.street_number === "undefined" ||
        components.street_number.length === 0 ||
        typeof components.administrative_area_level_1 === "undefined" ||
        components.administrative_area_level_1.length < 2 ||
        typeof components.postal_code === "undefined" ||
        components.postal_code.length === 0
      ) {
        jQuery("#lat_long").val("");
        jQuery("#address_1").val("");
        jQuery("#city").val("");
        jQuery("#state").val("");
        jQuery("#zipcode").val("");
      } else {
        jQuery("#lat_long").val(
          "{" +
            results[0].geometry.location.lat() +
            ", " +
            results[0].geometry.location.lng() +
            "}"
        );
        jQuery("#address_1").val(
          components.street_number[0] +
            " " +
            components.route[0] +
            (typeof components.subpremise !== "undefined"
              ? " " + components.subpremise[0]
              : "")
        );
        jQuery("#city").val(components.locality[0]);
        jQuery("#state").val(components.administrative_area_level_1[1]);
        jQuery("#zipcode").val(components.postal_code[0]);
      }
    }
  });
}

var onSubmit = function(token) {
  jQuery("#representative_request_form").submit();
};

var onloadCallback = function() {
  if (jQuery("#representative_request_form").length > 0) {
    grecaptcha.render("representative_request_form", {
      sitekey: "6LfE38wUAAAAAAQxZPhVUH4Sc6HHSyklwrq7rxu7",
      callback: onSubmit
    });
  }
};
