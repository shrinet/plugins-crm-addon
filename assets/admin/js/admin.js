var numProcesses = 1;
var lastCount = -1;
var instCount = 0;
var itterCount = 0;
var killed = false;
var resetCount = 0;
var initThreads = false;
var overCount = 0;

function import_data(e) {
  initThreads = jQuery("#init_threads").val() === "YES" ? true : false;
  numProcesses = jQuery("#processesToSpinUp").val();
  lastCount = -1;
  instCount = 0;
  itterCount = 0;
  killed = false;
  resetCount = 0;
  jQuery("#msg-loading").show();
  var count = 0;
  var passReset = jQuery("#reset").val() === "YES";
  if (passReset) {
    overCount = 0;
  }
  while (count < numProcesses) {
    setTimeout(function() {
      callAjax(e, passReset);
    }, count * 2500);
    count++;
  }
}

function callAjax(e, passReset = true) {
  instCount = Math.abs(instCount);
  instCount++;
  console.log(instCount);
  if (initThreads) {
    itterCount++;
    if (
      resetCount > 10 ||
      (itterCount > jQuery("#processesToSpinUp").val() + 25 && !killed)
    ) {
      console.log("requeueing");
      import_data(e);
      resetCount = 0;
      itterCount = 0;
    } else if (killed) {
      itterCount = 0;
      resetCount++;
      killed = false;
    }
  }
  jQuery.ajax({
    cache: false,
    type: "POST",
    url: maclean_ajax_url,
    data: {
      action: "import_data",
      import_sequence:
        jQuery("#sequence").val() !== ""
          ? jQuery("#sequence").val()
          : jQuery("#importSequence").val(),
      meta_key: jQuery("#metaKey").val(),
      sequence_version: jQuery("#sequenceVersion").val(),
      file_sequence: jQuery("#file_sequence").val() === "YES" ? "1" : "0",
      reset: passReset && jQuery("#reset").val() === "YES" ? "1" : "0",
      init_threads: jQuery("#init_threads").val() === "YES" ? "1" : "0"
    },
    success: function(data) {
      if (passReset) {
        passReset = !passReset;
      }
      instCount--;
      if (data.indexOf(":") !== -1) {
        var d = data.split(":");
        console.log("FILE NAME: " + d[2]);
        jQuery("#msg-loading").show();
        console.log("FILES LEFT TO PROCESS: " + d[1]);
        if (typeof stopProcess !== "undefined" && stopProcess === 1) {
          instCount = 0;
          console.log("USER STOPPED PROCESS");
        } else if (parseInt(d[1]) !== 0) {
          lastCount = parseInt(d[1]);
          if (instCount <= jQuery("#processesToSpinUp").val()) {
            var multiplier = parseInt(d[0]);
            if (jQuery("#processesToSpinUp").val() === 1) {
              multiplier = 1;
            } else {
              multiplier = 7.5;
            }
            setTimeout(function() {
              callAjax(e, false);
            }, multiplier * 1000);
          } else {
            killed = true;
            console.log("TO MANY PROCESSES; KILLED 1");
          }
        } else {
          killed = true;
          console.log("NOTHING LEFT TO PROCESS");
        }
      } else {
        console.log(data);
        jQuery("#msg-loading").hide();
      }
    },
    error: function(var1, var2, var3) {
      instCount--;
    },
    fail: function(data) {
      instCount--;
      console.log("Download Failed, please try again.");
    }
  });
}


jQuery(document).ready(function() {
  jQuery('#publish').click(function() {
    if(jQuery(this).data("valid")) {
      return true;
  }
    var form_data = jQuery('#post').serializeArray();
    form_data = jQuery.param(form_data);
    var data = {
      action: 'maclean_pre_submit_validation',
      form_data: form_data
    };
    jQuery.post(ajaxurl, data, function(response) {
      console.log(response);
      if (response =='success') {
        jQuery('#publish').data("valid", true).trigger('click');
      }else{
        alert("please correct the following errors: " + response);
        jQuery("#post").data("valid", false);

      }
              //hide loading icon, return Publish button to normal
      jQuery('#ajax-loading').hide();
      jQuery('#publish').removeClass('button-primary-disabled');
      jQuery('#save-post').removeClass('button-disabled');
    });
    return false;
  });
});