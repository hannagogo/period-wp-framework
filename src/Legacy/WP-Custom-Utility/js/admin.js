(function ($) {
  $(function () {
    window.WP_CUSTOMUTILITY = new Object();
    window.WP_CUSTOMUTILITY.ENV = new Object();
    window.WP_CUSTOMUTILITY.ENV.CustomPostType = $("#post_type")
      ? $("#post_type").val()
      : undefined;
    window.WP_CUSTOMUTILITY.ENV.PostID = $("#post_ID")
      ? $("#post_ID").val()
      : undefined;
    window.WP_CUSTOMUTILITY.ENV.UserID = $("#user-id")
      ? $("#user-id").val()
      : undefined;
    window.WP_CUSTOMUTILITY.ENV.Shortlink = $("#shortlink")
      ? $("#shortlink").val()
      : undefined;

    $.addCustomFunction("liveSearch");
    $(
      "#wp-admin-bar-view a, #site-heading a, #updated a, #wp-admin-bar-site-name a, #edit-slug-box a"
    ).attr("target", "_blank");

    $(".tablenav.top .actions:last").after(
      $("<input id=posts_search_text type=text size=48 />")
        .liveSearch(".wp-list-table tbody", "tr")
        .on("keyup", function () {
          var b = $(".close_button", $(this).parent());
          if ($(this).val() == "") b.hide();
          else b.show();
        })
        .keydown(function (evt) {
          if (evt.keyCode == 27) $(this).val("");
        })
    );
    $("#posts_search_text").wrap(
      $("<label id=posts_search_label for=posts_search_text></label>")
    );

    $("#posts_search_label")
      .prepend("<span class='material-symbols-rounded '>search</span>")
      .wrap($("<div class=alignleft></div>").addClass("options"));
    // $("#posts_search_text").after(
    //   $(close_button)
    //     .on("click", function () {
    //       $("#posts_search_text").val("").trigger("keyup").focus();
    //     })
    //     .hide()
    // );
  });
})(jQuery);
