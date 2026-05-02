(function ($) {
  $(function () {
    $(".WP_CustomUtility_MetaBox_textarea").each(function () {
      autosize(this);
      $(this).on("resize", function () {
        autosize(this);
      });
    });

    $.extend({
      set_MetaBox_multiplier: function (args) {
        args = $.extend(
          {
            metabox_class_name: "WP_CustomUtility_MetaBox",
            field_type: "text",
            target_id: "",
            field_id: "",
            minus_attr: "",
            dialog_args: "",
            picker: "",
            val: "",
            multiply_groups: [],
          },
          args
        );
        let metabox_class_name = args["metabox_class_name"],
          target_id = args["target_id"],
          field_id = args["field_id"],
          minus_attr = args["minus_attr"],
          dialog_args = args["dialog_args"],
          picker = args["picker"],
          val = args["val"],
          multiply_groups = args.multiply_groups,
          multiply_in_process = false;

        let get_multiply_group = function (b) {
          if (undefined === b || undefined === (b = $(b)).get(0)) return false;
          let field_name = b
            .attr("id")
            .replace(
              new RegExp("^" + field_id + "_(.+?)(?:_(d+))?_(?:plus|minus)$"),
              "$1"
            );
          let multiply_group = false;
          for (let i in multiply_groups) {
            if (in_array(field_name, multiply_groups[i])) {
              multiply_group = multiply_groups[i];
              break;
            }
          }
          return multiply_group;
        };

        $("." + field_id + "_form_field_multiply_plus").on(
          "click",
          function () {
            let b = $(this),
              multiply_group = get_multiply_group(b);

            if (multiply_group === false) return false;

            if (multiply_in_process === false) {
              multiply_in_process = new Array();
            }
            if (in_array(b.attr("id"), multiply_in_process)) return false;
            else multiply_in_process.push(b.attr("id"));

            for (let i in multiply_group) {
              let id = field_id + "_" + multiply_group[i] + "_plus";
              if (in_array(id, multiply_in_process)) {
                continue;
              } else {
                !in_array(id, multiply_in_process) &&
                  multiply_in_process.push(id);
                $("#" + id).trigger("click");
              }
            }
            if (multiply_in_process.length == multiply_group.length) {
              multiply_in_process = false;
              multiply_group = false;
              return false;
            }
          }
        );

        $("#" + target_id + "_delete_field_dialog")
          .hide()
          .dialog({
            autoOpen: false,
            closeOnEscape: true,
            modal: true,
            title: dialog_args.title,
            buttons: {
              [dialog_args.confirm_button]: function () {
                let target = $[metabox_class_name + "_delete_field"],
                  m = target.match(new RegExp(field_id + "_(.+?)_(\\d+)$")),
                  field_name = m[1],
                  field_count = m[2],
                  multiply_group = get_multiply_group(
                    $("#" + target + "_minus")
                  );
                if (false === multiply_group) {
                  multiply_group = [field_name];
                }
                for (let i in multiply_group) {
                  $(
                    "#" +
                      field_id +
                      "_" +
                      multiply_group[i] +
                      "_" +
                      field_count +
                      "_form_field_wrap"
                  ).fadeOut({
                    complete: function () {
                      $(this).remove();
                    },
                  }); ////////// DELETE
                }
                $(this).dialog("close");
                multiply_group = false;
              },
              [dialog_args.cancel_button]: function () {
                $(this).dialog("close");
              },
            },
          });

        let fn_confirm_delete_field_dialog = function () {
          let b = $(this),
            multiply_group = get_multiply_group(b),
            b_id = b.attr("id"),
            m = b_id.match(
              new RegExp("^" + field_id + "_(.+?)(?:_(\\d+))?_minus$")
            ),
            field_name = m[1],
            field_count = m[2];
          $.extend({
            [metabox_class_name + "_delete_field"]: b.data("multiply_target"),
          });
          let target = $[metabox_class_name + "_delete_field"];

          if (false === multiply_group || false === multiply_in_process) {
            $("#" + target_id + "_delete_field_dialog").dialog("open");
            $(".ui-front").css("z-index", 300011);
            $(".ui-front.ui-widget-overlay").css("z-index", 300010);
          }
        };

        $(
          "." + metabox_class_name + "_form_field_multiply_minus",
          $("#" + target_id + "_box")
        ).each(function () {
          $(this).on("click", fn_confirm_delete_field_dialog);
        });

        $("#" + target_id + "_plus").on("click", function () {
          let fieldname = target_id,
            field_count = parseInt(fieldname.replace(/^.*?_([\d]+)$/, "$1")),
            wrapper = fieldname + "_form_field_wrap",
            w = $("." + wrapper + ":last"),
            ww = w.clone(true),
            fid_re = new RegExp("(" + fieldname + "_)([0-9]+)", "g"),
            fid_replace = function () {
              return arguments[1] + (parseInt(arguments[2]) + 1);
            },
            minus = $(
              "span." + metabox_class_name + "_form_field_multiply_minus",
              ww
            ),
            image_view = $("." + target_id + "_image_view", ww),
            newid,
            delete_button = $("." + target_id + "_delete_button", ww),
            pickup_button = $("." + target_id + "_pickup_button", ww),
            img_fields = [image_view, delete_button, pickup_button],
            form_elements = $("input, textarea", ww);

          ww.attr("id", ww.attr("id").replace(fid_re, fid_replace));
          if (form_elements[0]) {
            form_elements.each(function () {
              let fe = $(this),
                is_checkbox = fe.attr("type") == "checkbox",
                is_button = fe.attr("type") == "button";
              newid = fe.attr("id").replace(fid_re, fid_replace);
              fe.attr("id", newid);
              if (!is_checkbox && !is_button) fe.val("");
              let label = is_checkbox
                ? $(fe.parent("label").get(0))
                : $("#" + fieldname + "_box label");
              if (is_checkbox) {
                let newname = fe
                  .attr("name")
                  .replace(
                    new RegExp(/(\x5b)(\d+)(\x5d)(\x5b\x5d)?$/),
                    function () {
                      let a = arguments;
                      a[2] = parseNumber(a[2]) + 1;
                      return a[1] + a[2] + a[3] + a[4];
                    }
                  );
                fe.attr("name", newname).attr("checked", false);
              }
              label.attr("for", newid);
            });
          }

          for (i in img_fields) {
            if (img_fields[i][0]) {
              img_fields[i]
                .attr(
                  "class",
                  img_fields[i].attr("class").replace(fid_re, fid_replace)
                )
                .attr(
                  "id",
                  img_fields[i].attr("id").replace(fid_re, fid_replace)
                );
            }
          }
          if (image_view[0]) image_view.html("");
          if (delete_button[0]) delete_button.hide();

          if (!minus.length) {
            minus = $("<span " + minus_attr + ">-</span>").appendTo(ww);
          } else {
            minus = minus
              .attr("id", minus.attr("id").replace(fid_re, fid_replace))
              .attr("class", minus.attr("class").replace(fid_re, fid_replace))
              .data(
                "multiply_target",
                ww.attr("id").replace(/_form_field_wrap$/, "")
              );
          }
          minus.on("click", fn_confirm_delete_field_dialog);
          ww.hide()
            .insertAfter(w)
            .fadeIn(200, function () {
              $("input", this).focus();
            });
          if (args["field_type"] == "text") {
            $("input", ww).removeClass("hasDatepicker");
          }
          let pickers = {
            datepicker: function (option) {
              $("input", ww).datepicker(option).focus();
            },
            datetimepicker: function (option) {
              $("input", ww).datetimepicker(option).focus();
            },
            timepicker: function (option) {
              $("input", ww).timepicker(option).focus();
            },
            slider: function (option) {
              _slider_options[_slider_options.length] = $.extend(
                slider_options,
                {
                  value: val,
                }
              );
              $(".ui-slider", ww)
                .empty()
                .attr("id", newid + "_slider")
                .slider(_slider_options[_slider_options.length - 1]);
            },
          };
          console.log(picker);
          if (args["field_type"] == "text" && picker["picker"]) {
            pickers[picker["picker"]](picker["options"]);
          }
        });
      },

      set_MetaBox__Image: function (args) {
        args = $.extend(
          {
            target_id: "",
            dialog_messages: {},
            image_size_id_suffix: "",
            label: "",
            buttons: {},
            ajax_url: "",
            error_ajax_message: "",
          },
          args
        );
        let target_id = args["target_id"],
          dialog_messages = args["dialog_messages"],
          image_size_id_suffix = args["image_size_id_suffix"],
          label = args["label"],
          buttons = args["buttons"];
        $("#dialog_confirm_delete_" + target_id).dialog({
          autoOpen: false,
          closeOnEscape: true,
          modal: true,
          title: dialog_messages["title"],
          buttons: {
            [dialog_messages["confirm_button"]]: function () {
              var wrap = $("#" + $["image_field_wrapper"]);
              $("img", wrap).fadeOut({
                complete: function () {
                  $("." + target_id + "_image_view", wrap).html("");
                },
              });
              $("." + target_id, wrap).val("");
              $("." + target_id + image_size_id_suffix, wrap).val("");
              $("." + target_id + "_delete_button", wrap).hide();
              $(this).dialog("close");
            },
            [dialog_messages["cancel_button"]]: function () {
              $(this).dialog("close");
            },
          },
        });
        var delete_button = $(
          "." + target_id + "_delete_button",
          $("." + target_id + "_form_field_wrap")
        ).on("click", function () {
          var wrap = $(
              $(this)
                .parents("." + target_id + "_form_field_wrap")
                .get(0)
            ),
            e = $("." + target_id, wrap);
          if ($["image_field_wrapper"] === undefined)
            $.extend({ image_field_wrapper: wrap.attr("id") });
          else $["image_field_wrapper"] = wrap.attr("id");
          if (e.val()) {
            $("#dialog_confirm_delete_" + target_id).dialog("open");
          } else return false;
          if (!e.val()) {
            $(this).hide();
          }
        });
        delete_button.each(function () {
          var wrap = $(
              $(this)
                .parents("." + target_id + "_form_field_wrap")
                .get(0)
            ),
            e = $("." + target_id, wrap);
          if (!e.val()) $(this).hide();
        });

        $(
          "." + target_id + "_pickup_button",
          $("." + target_id + "_form_field_wrap")
        ).on("click", function () {
          var mediabox;
          var wrap = $(
            $(this)
              .parents("." + target_id + "_form_field_wrap")
              .get(0)
          );
          if ($["image_field_wrapper"] === undefined)
            $.extend({ image_field_wrapper: wrap.attr("id") });
          else $["image_field_wrapper"] = wrap.attr("id");
          if (mediabox) {
            mediabox.open();
            return;
          }
          mediabox = wp.media({ state: target_id + "_Media_Picker" });
          mediabox.states.add([
            new wp.media.controller.Library({
              id: target_id + "_Media_Picker",
              title: label + " : " + buttons["button_pickup_name"],
              filterable: "uploaded",
              // library	 : wp.media.query( mediabox.options.library ),
              multiple: mediabox.options.multiple ? "reset" : false,
              editable: true,
              displayUserSettings: false,
              contentUserSetting: false,
              displaySettings: true,
              allowLocalEdits: true,
            }),
          ]);
          mediabox.on("select", function () {
            var m = mediabox.el,
              sidebar = $(".media-sidebar", m),
              wrap = $("#" + $["image_field_wrapper"]),
              id = $(".edit-attachment", sidebar)
                .attr("href")
                .match(/\x3f.*?post=(\d+)&?.*?$/)[1],
              image_view = $("." + target_id + "_image_view", wrap),
              image_size_field = $(
                "." + target_id + image_size_id_suffix,
                wrap
              ),
              image_size = $(
                ".attachment-display-settings select[name=size]",
                sidebar
              ).val();
            $.ajax({
              type: "POST",
              url: args["ajax_url"],
              data: { action: "get_thumbnail", attachment_id: id },
              success: function (data) {
                $("." + target_id + "", wrap).val(id);
                image_view.html(data);
                $("." + target_id + "_delete_button").show();
                $("a", image_view).fancybox();
                image_size_field.val(image_size);
              },
              error: function () {
                image_view.html(args["error_ajax_message"]);
              },
            });
          });
          mediabox.open();
        });
      },
    });
  });
})(jQuery);
