(function($) {
  function resetTables() {
    jQuery("table").each(function(index, element) {
      var t = jQuery(element).DataTable();
      t.columns.adjust().draw();
    });
  }

  function sort_sub_cats_id(a, b) {
    return $(b).data("catid") < $(a).data("catid") ? 1 : -1;
  }

  function sort_sub_cats_name_desc(a, b) {
    return $(b).data("name") < $(a).data("name") ? 1 : -1;
  }

  function sort_sub_cats_name_asc(a, b) {
    return $(b).data("name") > $(a).data("name") ? 1 : -1;
  }

  var productDataFilter = (function() {
    var $productDataFilter = null;
    var $productCategories = null;
    var $productCategoriesSort = null;

    function init() {
      $productDataFilter = $(".product-data-filter");
      $productCategories = $(".product-cat");
      $productCategoriesSort = $(".product-cats-sort");
    }

    function getSelectedCategoryIds() {
      var catIds = [];
      $productDataFilter.each(function(index, element) {
        if ($(element).is(":checked")) {
          $(element)
            .data("catids")
            .toString()
            .split(",")
            .forEach(function(element) {
              catIds.push(element);
            });
        }
      });
      return catIds;
    }

    function handleClick(e) {
      if ($(e.target).is(":checked")) {
        var catIds = $(e.target)
          .data("catids")
          .toString()
          .split(",");
        catIds.push(getSelectedCategoryIds());
        catIds = catIds.flat();
        catIds = catIds.filter(onlyUnique);
        $productCategories.each(function(index, element) {
          if (catIds.length > 0) {
            $(element).hide();
            catIds.forEach(function(ele) {
              if ($(element).hasClass("product-cat-" + ele)) {
                $(element).show();
              }
            });
            $(".prod-count > .count ").html(catIds.length);
          } else {
            $(element).show();
            $(".prod-count > .count ").html($(".product-cat").length);
          }
        });
      } else {
        var catIds = [];
        catIds.push(getSelectedCategoryIds());
        catIds = catIds.flat();
        catIds = catIds.filter(onlyUnique);
        $productCategories.each(function(index, element) {
          if (catIds.length > 0) {
            $(element).hide();
            catIds.forEach(function(ele) {
              if ($(element).hasClass("product-cat-" + ele)) {
                $(element).show();
              }
            });
            $(".prod-count > .count ").html(catIds.length);
          } else {
            $(element).show();
            $(".prod-count > .count ").html($(".product-cat").length);
          }
        });
      }
      $productCategoriesSort.trigger("change");
    }

    return {
      init: init,
      handleClick: handleClick
    };
  })();

  $(document).ready(function() {
    productDataFilter.init();
    $(".product-data-filter").on("click", productDataFilter.handleClick);

    $("#image-gallery").lightSlider({
      gallery: true,
      item: 1,
      thumbItem: 9,
      slideMargin: 0,
      speed: 1500,
      auto: true,
      loop: true,
      pauseOnHover: true,
      pause: 3500,
      onSliderLoad: function() {
        $("#image-gallery").removeClass("cS-hidden");
      }
    });

    $(document).on("click", ".filter-group .filter > span", function(e) {
      if (
        $(e.target)
          .parent()
          .find("ul")
          .first()
          .is(":hidden")
      ) {
        $(e.target).addClass("active");
      } else {
        $(e.target).removeClass("active");
      }
      $(e.target)
        .parent()
        .find("ul")
        .first()
        .toggle();
    });

    $(".product-cats-sort").on("change", function(e) {
      if ($(e.target).val() === "id") {
        $(".product-cats > .product-cat")
          .sort(sort_sub_cats_id)
          .appendTo(".product-cats");
      } else if ($(e.target).val() === "name-asc") {
        $(".product-cats > .product-cat")
          .sort(sort_sub_cats_name_asc)
          .appendTo(".product-cats");
      } else if ($(e.target).val() === "name-desc") {
        $(".product-cats > .product-cat")
          .sort(sort_sub_cats_name_desc)
          .appendTo(".product-cats");
      }
    });
    $.initialize(".reponse_to_quote", function() {
      var button = $(this)
        .parent()
        .find(".single_adq_button");
      if (typeof button !== "undefined" && !$(button).hasClass("added")) {
        $(this).remove();
      } else {
        $(this).addClass("button");
        $(this).style(
          "padding-left: 15px; padding-right: 15px; min-width: 150px; margin-left: 10px;"
        );
      }
    });
    $(".radio-toggle-product-tables").on("click", function(event) {
      if (jQuery(event.target).is(":checked")) {
        var typeShow = jQuery(event.target).data("typeShow");
        var typeHide = jQuery(event.target).data("typeHide");
        jQuery(
          "#" + jQuery(event.target).data("tableid") + "-" + typeShow
        ).show();
        jQuery(
          "#" + jQuery(event.target).data("tableid") + "-" + typeHide
        ).hide();
      }
    });

    $(".datatable-products").each(function(index, element) {
      $(element).DataTable({
        paging: false,
        responsive: true
      });
    });
  });
})(jQuery);
