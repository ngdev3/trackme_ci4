
					
/* Dropdownjs */

(function (factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jquery'], factory);
    } else if (typeof exports === 'object') {
        // Node/CommonJS
        module.exports = factory(require('jquery'));
    } else {
        // Browser globals
        factory(jQuery);
    }
}(function($) {

  var methods = {
    options : {
      "optionClass": "",
      "dropdownClass": "",
      "autoinit": false,
      "callback": false,
      "onSelected": false,
      "destroy": function(element) {
        this.destroy(element);
      },
      "dynamicOptLabel": "Add a new option..."
    },
    init: function(options) {

      // Apply user options if user has defined some
      if (options) {
        options = $.extend(methods.options, options);
      } else {
        options = methods.options;
      }

      function initElement($select) {
        // Don't do anything if this is not a select or if this select was already initialized
        if ($select.data("dropdownjs") || !$select.is("select")) {
          return;
        }

        // Is it a multi select?
        var multi = $select.attr("multiple");

        // Does it allow to create new options dynamically?
        var dynamicOptions = $select.attr("data-dynamic-opts"),
            $dynamicInput = $();

        // Create the dropdown wrapper
        var $dropdown = $("<div></div>");
        $dropdown.addClass("dropdownjs").addClass(options.dropdownClass);
        $dropdown.data("select", $select);

        // Create the fake input used as "select" element and cache it as $input
        var $input = $("<input type=text readonly class=fakeinput>");
        if ($.material) { $input.data("mdproc", true); }
        // Append it to the dropdown wrapper
        $dropdown.append($input);

        // Create the UL that will be used as dropdown and cache it AS $ul
        var $ul = $("<ul></ul>");
        $ul.data("select", $select);

        // Append it to the dropdown
        $dropdown.append($ul);

        // Transfer the placeholder attribute
        $input.attr("placeholder", $select.attr("placeholder"));

        // Loop trough options and transfer them to the dropdown menu
        $select.find("option").each(function() {
          // Cache $(this)
          var $this = $(this);
          methods._addOption($ul, $this);

        });

        // If this select allows dynamic options add the widget
        if (dynamicOptions) {
          $dynamicInput = $("<li class=dropdownjs-add></li>");
          $dynamicInput.append("<input>");
          $dynamicInput.find("input").attr("placeholder", options.dynamicOptLabel);
          $ul.append($dynamicInput);
        }



        // Cache the dropdown options
        var selectOptions = $dropdown.find("li");

        // If is a single select, selected the first one or the last with selected attribute
        if (!multi) {
            var $selected;
            if ($select.find(":selected").length) {
                $selected = $select.find(":selected").last();
            }
            else {
                $selected = $select.find("option, li").first();
               // $selected = $select.find("option").first();
            }
            methods._select($dropdown, $selected);
        } else {
            var selectors = [], val = $select.val()
            for (var i in val) {
              selectors.push(val[i]);
            }
            if (selectors.length > 0) {
              var $target = $dropdown.find(function() { return $.inArray($(this).data("value"), selectors) !== -1; });
              $target.removeClass("selected");
              methods._select($dropdown, $target);
            }
        }

        // Transfer the classes of the select to the input dropdown
        $input.addClass($select[0].className);

        // Hide the old and ugly select
        $select.hide().attr("data-dropdownjs", true);

        // Bring to life our awesome dropdownjs
        $select.after($dropdown);

        // Call the callback
        if (options.callback) {
          options.callback($dropdown);
        }

        //---------------------------------------//
        // DROPDOWN EVENTS                       //
        //---------------------------------------//

        // On click, set the clicked one as selected
        $ul.on("click", "li:not(.dropdownjs-add)", function(e) {
          methods._select($dropdown, $(this));
          // trigger change event, if declared on the original selector
          $select.change();
        });
        $ul.on("keydown", "li:not(.dropdownjs-add)", function(e) {
          if (e.which === 27) {
            $(".dropdownjs > ul > li").attr("tabindex", -1);
            return $input.removeClass("focus").blur();
          }
          if (e.which === 32 && !$(e.target).is("input")) {
            methods._select($dropdown, $(this));
            return false;
          }
        });

        $ul.on("focus", "li:not(.dropdownjs-add)", function() {
          if ($select.is(":disabled")) {
            return;
          }
          $input.addClass("focus");
        });

        // Add new options when the widget is used
        if (dynamicOptions && dynamicOptions.length) {
          $dynamicInput.on("keydown", function(e) {
            if(e.which !== 13) return;
            var $option = $("<option>"),
                val = $dynamicInput.find("input").val();
            $dynamicInput.find("input").val("");

            $option.attr("value", val);
            $option.text(val);
            $select.append($option);

          });
        }

        // Listen for new added options and update dropdown if needed
        $select.on("DOMNodeInserted", function(e) {
          var $this = $(e.target);
          if (!$this.val().length) return;

          methods._addOption($ul, $this);
          $ul.find("li").not(".dropdownjs-add").attr("tabindex", 0);

        });

        $select.on("DOMNodeRemoved", function(e) {
          var deletedValue = $(e.target).attr('value');
          $ul.find("li").filter(function() { return $(this).data("value") === deletedValue; }).remove();
          var $selected;

          setTimeout(function () {
            if ($select.find(":selected").length) {
              $selected = $select.find(":selected").last();
            }
            else {
              $selected = $select.find("option, li").first();
            }
            methods._select($dropdown, $selected);
          }, 100);

        });

        // Update dropdown when using val, need to use .val("value").trigger("change");
        $select.on("change", function(e) {
          var $this = $(e.target);

          if (!multi) {
            var $selected;
            if ($select.find(":selected").length) {
              $selected = $select.find(":selected").last();
            }
            else {
              $selected = $select.find("option, li").first();
            }
            methods._select($dropdown, $selected);
          } else {
            var target = $select.find(":selected"),
                values = $(this).val();
            // Unselect all options
            selectOptions.removeClass("selected");
            // Select options
            target.each(function () {
                var selected = selectOptions.filter(function() { return $.inArray($(this).data("value"), values) !== -1; });
                selected.addClass("selected");
            });
          }
        });

        // Used to make the dropdown menu more dropdown-ish
        $input.on("click focus", function(e) {
          e.stopPropagation();
          if ($select.is(":disabled")) {
            return;
          }
          $(".dropdownjs > ul > li").attr("tabindex", -1);
          $(".dropdownjs > input").not($(this)).removeClass("focus").blur();

          $(".dropdownjs > ul > li").not(".dropdownjs-add").attr("tabindex", 0);

          // Set height of the dropdown
          var coords = {
            top: $(this).offset().top - $(document).scrollTop(),
            left: $(this).offset().left - $(document).scrollLeft(),
            bottom: $(window).height() - ($(this).offset().top - $(document).scrollTop()),
            right: $(window).width() - ($(this).offset().left - $(document).scrollLeft())
          };

          var height = coords.bottom;

          // Decide if place the dropdown below or above the input
          if (height < 200 && coords.top > coords.bottom) {
            height = coords.top;
            $ul.attr("placement", $("body").hasClass("rtl") ? "top-right" : "top-left");
          } else {
            $ul.attr("placement", $("body").hasClass("rtl") ? "bottom-right" : "bottom-left");
          }

          $(this).next("ul").css("max-height", height - 20);
          $(this).addClass("focus");
        });
        // Close every dropdown on click outside
        $(document).on("click", function(e) {

          // Don't close the multi dropdown if user is clicking inside it
          if (multi && $(e.target).parents(".dropdownjs").length) return;

          // Don't close the dropdown if user is clicking inside the dynamic-opts widget
          if ($(e.target).parents(".dropdownjs-add").length || $(e.target).is(".dropdownjs-add")) return;

          // Close opened dropdowns
          $(".dropdownjs > ul > li").attr("tabindex", -1);
            if ($(e.target).hasClass('disabled')) {
              return;
        }
          $input.removeClass("focus");
        });
      }

      if (options.autoinit) {
        $(document).on("DOMNodeInserted", function(e) {
          var $this = $(e.target);
          if (!$this.is("select")) {
            $this = $this.find('select');
          }
            $this.each(function() {
                if ($(this).is(options.autoinit)) {
                    initElement($(this));
                }
            });
        });
      }

      // Loop trough elements
      $(this).each(function() {
        initElement($(this));
      });
    },
    select: function(target) {
      var $target = $(this).find(function() { return $(this).data("value") === target; });
      methods._select($(this), $target);
    },
    _select: function($dropdown, $target) {

      if ($target.is(".dropdownjs-add")) return;

      // Get dropdown's elements
      var $select = $dropdown.data("select"),
          $input  = $dropdown.find("input.fakeinput");
      // Is it a multi select?
      var multi = $select.attr("multiple");

      // Cache the dropdown options
      var selectOptions = $dropdown.find("li");

      // Behavior for multiple select
      if (multi) {
        // Toggle option state
        $target.toggleClass("selected");
        // Toggle selection of the clicked option in native select
        $target.each(function(){
          var value = $(this).prop("tagName") === "OPTION" ? $(this).val() : $(this).data("value"),
              $selected = $select.find("[value=\"" + value + "\"]");
          $selected.prop("selected", $(this).hasClass("selected"));
        });
        // Add or remove the value from the input
        var text = [];
        selectOptions.each(function() {
          if ($(this).hasClass("selected")) {
            text.push($(this).text());
          }
        });
        $input.val(text.join(", "));
      }

      // Behavior for single select
      if (!multi) {
        if ($target.hasClass("disabled")) {
          return;
        }
        // Unselect options except the one that will be selected
        if ($target.is("li")) {
            selectOptions.not($target).removeClass("selected");
        }
        // Select the selected option
        $target.addClass("selected");
        // Set the value to the input
        $input.val($target.text().trim());
        var value = $target.prop("tagName") === "OPTION" ? $target.val() : $target.data("value");
        // When val is set below on $select, it will fire change event,
        // which ends up back here, make sure to not end up in an infinite loop.
        // This is done last so text input is initialized on first load when condition is true.
        if (value === $select.val()) {
          return;
        }
        // Set the value to the native select
        $select.val(value);
      }

      // This is used only if Material Design for Bootstrap is selected
      if ($.material) {
        if ($input.val().trim()) {
          $select.removeClass("empty");
        } else {
          $select.addClass("empty");
        }
      }

      // Call the callback
      if (this.options.onSelected) {
        this.options.onSelected($target.data("value"));
      }

    },
    _addOption: function($ul, $this) {
      // Create the option
      var $option = $("<li></li>");

      // Style the option
      $option.addClass(this.options.optionClass);

      // If the option has some text then transfer it
      if ($this.text()) {
        $option.text($this.text());
      }
      // Otherwise set the empty label and set it as an empty option
      else {
        $option.html("&nbsp;");
      }
      // Set the value of the option
      $option.data("value", $this.val());

      // Will user be able to remove this option?
      if ($ul.data("select").attr("data-dynamic-opts")) {
        $option.append("<span class=close></span>");
        $option.find(".close").on("click", function() {
          $option.remove();
          $this.remove();
        });
      }

      // Ss it selected?
      if ($this.prop("selected")) {
        $option.attr("selected", true);
        $option.addClass("selected");
      }

      if ($this.prop("disabled")) {
        $option.addClass("disabled");
      }

      // Append option to our dropdown
      if ($ul.find(".dropdownjs-add").length) {
        $ul.find(".dropdownjs-add").before($option);
      } else {
        $ul.append($option);
      }
    },
    destroy: function($e) {
      $($e).show().removeAttr('data-dropdownjs').next('.dropdownjs').remove();
    }
  };

  $.fn.dropdown = function(params) {
    if( typeof methods[params] == 'function' ) methods[params](this);
    if (methods[params]) {
      return methods[params].apply(this, Array.prototype.slice.call(arguments,1));
    } else if (typeof params === "object" | !params) {
      return methods.init.apply(this, arguments);
    } else {
      $.error("Method " + params + " does not exists on jQuery.dropdown");
    }
  };

}));




// ===== Material Form ===== //
(function ($) {

    // Selector to select only not already processed elements
    $.expr[":"].notmdproc = function (obj) {
        if ($(obj).data("mdproc")) {
            return false;
        } else {
            return true;
        }
    };

    function _isChar(evt) {
        if (typeof evt.which == "undefined") {
            return true;
        } else if (typeof evt.which == "number" && evt.which > 0) {
            return (
              !evt.ctrlKey
              && !evt.metaKey
              && !evt.altKey
              && evt.which !== 8  // backspace
              && evt.which !== 9  // tab
              && evt.which !== 13 // enter
              && evt.which !== 16 // shift
              && evt.which !== 17 // ctrl
              && evt.which !== 20 // caps lock
              && evt.which !== 27 // escape
            );
        }
        return false;
    }

    function addFormGroupFocus(element) {
        var $element = $(element);
        if (!$element.prop('disabled')) {  // this is showing as undefined on chrome but works fine on firefox??
            $element.closest(".form-group").addClass("is-focused");
        }
    }

    function toggleDisabledState($element, state) {
        var $target;
        if ($element.hasClass('checkbox-inline') || $element.hasClass('radio-inline')) {
            $target = $element;
        } else {
            $target = $element.closest('.checkbox').length ? $element.closest('.checkbox') : $element.closest('.radio');
        }
        return $target.toggleClass('disabled', state);
    }

    function toggleTypeFocus($input) {
        var disabledToggleType = false;
        if ($input.is($.material.options.checkboxElements) || $input.is($.material.options.radioElements)) {
            disabledToggleType = true;
        }
        $input.closest('label').hover(function () {
            var $i = $(this).find('input');
            var isDisabled = $i.prop('disabled'); // hack because the addFormGroupFocus() wasn't identifying the property on chrome
            if (disabledToggleType) {
                toggleDisabledState($(this), isDisabled);
            }
            if (!isDisabled) {
                addFormGroupFocus($i);     // need to find the input so we can check disablement
            }
        },
          function () {
              removeFormGroupFocus($(this).find('input'));
          });
    }

    function removeFormGroupFocus(element) {
        $(element).closest(".form-group").removeClass("is-focused"); // remove class from form-group
    }

    $.material = {
        "options": {
            // These options set what will be started by $.material.init()
            "validate": true,
            "input": true,
            "ripples": false,
            //"checkbox": false,
           // "togglebutton": false,
           // "radio": false,
            "arrive": true,
            "autofill": false,

            "withRipples": [
              ".btn:not(.btn-link)",
              ".card-image",
              ".navbar a:not(.withoutripple)",
              ".dropdown-menu a",
              ".nav-tabs a:not(.withoutripple)",
              ".withripple",
              ".pagination li:not(.active):not(.disabled) a:not(.withoutripple)"
            ].join(","),
            "inputElements": "input.form-control, textarea.form-control, select.form-control",
           // "checkboxElements": ".checkbox > label > input[type=checkbox], label.checkbox-inline > input[type=checkbox]",
           // "togglebuttonElements": ".togglebutton > label > input[type=checkbox]",
           // "radioElements": ".radio > label > input[type=radio], label.radio-inline > input[type=radio]"
        },
        //"checkbox": function (selector) {
        //    // Add fake-checkbox to material checkboxes
        //    var $input = $((selector) ? selector : this.options.checkboxElements)
        //      .filter(":notmdproc")
        //      .data("mdproc", true)
        //      .after("<span class='checkbox-material'><span class='check'></span></span>");

        //    toggleTypeFocus($input);
        //},
        //"togglebutton": function (selector) {
        //    // Add fake-checkbox to material checkboxes
        //    var $input = $((selector) ? selector : this.options.togglebuttonElements)
        //      .filter(":notmdproc")
        //      .data("mdproc", true)
        //      .after("<span class='toggle'></span>");

        //    toggleTypeFocus($input);
        //},
        //"radio": function (selector) {
        //    // Add fake-radio to material radios
        //    var $input = $((selector) ? selector : this.options.radioElements)
        //      .filter(":notmdproc")
        //      .data("mdproc", true)
        //      .after("<span class='circle'></span><span class='check'></span>");

        //    toggleTypeFocus($input);
        //},
        "input": function (selector) {
            $((selector) ? selector : this.options.inputElements)
              .filter(":notmdproc")
              .data("mdproc", true)
              .each(function () {
                  var $input = $(this);

                  // Requires form-group standard markup (will add it if necessary)
                  var $formGroup = $input.closest(".form-group"); // note that form-group may be grandparent in the case of an input-group
                  if ($formGroup.length === 0 && $input.attr('type') !== "hidden" && !$input.attr('hidden')) {
                      $input.wrap("<div class='form-group'></div>");
                      $formGroup = $input.closest(".form-group"); // find node after attached (otherwise additional attachments don't work)
                  }

                  // Legacy - Add hint label if using the old shorthand data-hint attribute on the input
                  if ($input.attr("data-hint")) {
                      $input.after("<p class='form-text'>" + $input.attr("data-hint") + "</p>");
                      $input.removeAttr("data-hint");
                  }

                  // Legacy - Change input-sm/lg to form-group-sm/lg instead (preferred standard and simpler css/less variants)
                  var legacySizes = {
                      "input-lg": "form-group-lg",
                      "input-sm": "form-group-sm"
                  };
                  $.each(legacySizes, function (legacySize, standardSize) {
                      if ($input.hasClass(legacySize)) {
                          $input.removeClass(legacySize);
                          $formGroup.addClass(standardSize);
                      }
                  });

                  // Legacy - Add label-floating if using old shorthand <input class="floating-label" placeholder="foo">
                  if ($input.hasClass("floating-label")) {
                      var placeholder = $input.attr("placeholder");
                      $input.attr("placeholder", null).removeClass("floating-label");
                      var id = $input.attr("id");
                      var forAttribute = "";
                      if (id) {
                          forAttribute = "for='" + id + "'";
                      }
                      $formGroup.addClass("label-floating");
                      $input.after("<label " + forAttribute + "class='col-form-label'>" + placeholder + "</label>");
                  }

                  // Set as empty if is empty 
                  if ($input.val() === null || $input.val() === "undefined" || $input.val() === "") {
                      $formGroup.addClass("is-empty");
                  }

                  // Support for file input
                  if ($formGroup.find("input[type=file]").length > 0) {
                      $formGroup.addClass("is-fileinput");
                  }
              });
        },
        "attachInputEventHandlers": function () {
            var validate = this.options.validate;

            $(document)
              .on("keydown paste", ".form-control", function (e) {
                  if (_isChar(e)) {
                      $(this).closest(".form-group").removeClass("is-empty");
                  }
              })
              .on("keyup change", ".form-control", function () {
                  var $input = $(this);
                  var $formGroup = $input.closest(".form-group");
                  var isValid = (typeof $input[0].checkValidity === "undefined" || $input[0].checkValidity());

                  if ($input.val() === "") {
                      $formGroup.addClass("is-empty");
                  }
                  else {
                      $formGroup.removeClass("is-empty");
                  }

                  // Validation events do not bubble, so they must be attached directly to the input: http://jsfiddle.net/PEpRM/1/
                  //  Further, even the bind method is being caught, but since we are already calling #checkValidity here, just alter
                  //  the form-group on change.
                  //
                  // NOTE: I'm not sure we should be intervening regarding validation, this seems better as a README and snippet of code.
                  //        BUT, I've left it here for backwards compatibility.
                  if (validate) {
                      if (isValid) {
                          $formGroup.removeClass("has-error");
                      }
                      else {
                          $formGroup.addClass("has-error");
                      }
                  }
              })
              .on("focus", ".form-control, .form-group.is-fileinput", function () {
                  addFormGroupFocus(this);
              })
              .on("blur", ".form-control, .form-group.is-fileinput", function () {
                  removeFormGroupFocus(this);
              })
              // make sure empty is added back when there is a programmatic value change.
              //  NOTE: programmatic changing of value using $.val() must trigger the change event i.e. $.val('x').trigger('change')
              .on("change", ".form-group input", function () {
                  var $input = $(this);
                  if ($input.attr("type") === "file") {
                      return;
                  }

                  var $formGroup = $input.closest(".form-group");
                  var value = $input.val();
                  if (value) {
                      $formGroup.removeClass("is-empty");
                  } else {
                      $formGroup.addClass("is-empty");
                  }
              })
              // set the fileinput readonly field with the name of the file
              .on("change", ".form-group.is-fileinput input[type='file']", function () {
                  var $input = $(this);
                  var $formGroup = $input.closest(".form-group");
                  var value = "";
                  $.each(this.files, function (i, file) {
                      value += file.name + ", ";
                  });
                  value = value.substring(0, value.length - 2);
                  if (value) {
                      $formGroup.removeClass("is-empty");
                  } else {
                      $formGroup.addClass("is-empty");
                  }
                  $formGroup.find("input.form-control[readonly]").val(value);
              });
        },
        "ripples": function (selector) {
            $((selector) ? selector : this.options.withRipples).ripples();
        },
        "autofill": function () {
            // This part of code will detect autofill when the page is loading (username and password inputs for example)
            var loading = setInterval(function () {
                $("input[type!=checkbox]").each(function () {
                    var $this = $(this);
                    if ($this.val() && $this.val() !== $this.attr("value")) {
                        $this.trigger("change");
                    }
                });
            }, 100);

            // After 10 seconds we are quite sure all the needed inputs are autofilled then we can stop checking them
            setTimeout(function () {
                clearInterval(loading);
            }, 10000);
        },
        "attachAutofillEventHandlers": function () {
            // Listen on inputs of the focused form (because user can select from the autofill dropdown only when the input has focus)
            var focused;
            $(document)
              .on("focus", "input", function () {
                  var $inputs = $(this).parents("form").find("input").not("[type=file]");
                  focused = setInterval(function () {
                      $inputs.each(function () {
                          var $this = $(this);
                          if ($this.val() !== $this.attr("value")) {
                              $this.trigger("change");
                          }
                      });
                  }, 100);
              })
              .on("blur", ".form-group input", function () {
                  clearInterval(focused);
              });
        },
        "init": function (options) {
            this.options = $.extend({}, this.options, options);
            var $document = $(document);

            if ($.fn.ripples && this.options.ripples) {
                this.ripples();
            }
            if (this.options.input) {
                this.input();
                this.attachInputEventHandlers();
            }
            //if (this.options.checkbox) {
            //    this.checkbox();
            //}
            //if (this.options.togglebutton) {
            //    this.togglebutton();
            //}
            //if (this.options.radio) {
            //    this.radio();
            //}
            if (this.options.autofill) {
                this.autofill();
                this.attachAutofillEventHandlers();
            }

            if (document.arrive && this.options.arrive) {
                if ($.fn.ripples && this.options.ripples) {
                    $document.arrive(this.options.withRipples, function () {
                        $.material.ripples($(this));
                    });
                }
                if (this.options.input) {
                    $document.arrive(this.options.inputElements, function () {
                        $.material.input($(this));
                    });
                }               

            }
        }
    };

})(jQuery);


(function ($) {

  $.fn.characterCounter = function(){
    return this.each(function(){
      var $input = $(this);
      var $counterElement = $input.parent().find('span[class="character-counter"]');

      // character counter has already been added appended to the parent container
      if ($counterElement.length) {
        return;
      }

      var itHasLengthAttribute = $input.attr('data-length') !== undefined;

      if(itHasLengthAttribute){
        $input.on('input', updateCounter);
        $input.on('focus', updateCounter);
        $input.on('blur', removeCounterElement);

        addCounterElement($input);
      }

    });
  };

  function updateCounter(){
    var maxLength     = +$(this).attr('data-length'),
    actualLength      = +$(this).val().length,
    isValidLength     = actualLength <= maxLength;

    $(this).parent().find('span[class="character-counter"]')
                    .html( actualLength + '/' + maxLength);

    addInputStyle(isValidLength, $(this));
  }

  function addCounterElement($input) {
    var $counterElement = $input.parent().find('span[class="character-counter"]');

    if ($counterElement.length) {
      return;
    }

    $counterElement = $('<span/>')
                        .addClass('character-counter')
                        .css('float','right')
                        .css('font-size','12px')
                        .css('height', 1);

    $input.parent().append($counterElement);
  }

  function removeCounterElement(){
    $(this).parent().find('span[class="character-counter"]').html('');
  }

  function addInputStyle(isValidLength, $input){
    var inputHasInvalidClass = $input.hasClass('invalid');
    if (isValidLength && inputHasInvalidClass) {
      $input.removeClass('invalid');
    }
    else if(!isValidLength && !inputHasInvalidClass){
      $input.removeClass('valid');
      $input.addClass('invalid');
    }
  }

  $(document).ready(function(){
    $('input, textarea').characterCounter();
  });

}( jQuery ));


$.material.init();
 $("select").dropdown();
//$('.input-counter').characterCounter();

var $inputCounter = $('.input-counter');
var length = $inputCounter.attr('data-length');
var $input = $inputCounter.find('input, textarea');
$input.attr('data-length', length);
$input.characterCounter();