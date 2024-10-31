(function ($) {
  'use strict'

  $(document).ready(function () {
    init_vue()

    init_gdex_settings_page()

    init_consignments_page()

    init_order_page()

    init_shipment_page()
  })

  function init_vue () {
    Vue.use(vuelidate.default)
  }

  function init_gdex_settings_page () {
    var adminpage = window.adminpage

    if (adminpage !== 'woocommerce_page_wc-settings') {
      return
    }

    var $form = $('#mainform')
    var $input = $('.gdex-sender-input')
    var $editableInputs = $input.filter('.gdex-sender-input-editable')

    var $postalCodeInput = $input.filter('#woocommerce_gdex_gdex_sender_postal_code')
    var $locationInput = $input.filter('#woocommerce_gdex_gdex_sender_location')
    var $locationIdInput = $input.filter('#woocommerce_gdex_gdex_sender_location_id')
    var $cityInput = $input.filter('#woocommerce_gdex_gdex_sender_city')
    var $stateIdInput = $input.filter('#woocommerce_gdex_gdex_sender_state')

    var $fromApi = $('.gdex-sender-load-from-api-input')
    $fromApi.on('change', function (event) {
      var useDefault = $fromApi.is(':checked')

      $editableInputs.prop('readOnly', useDefault)

      $locationInput.closest('tr').toggle(useDefault)
      $locationIdInput.closest('tr').toggle(!useDefault)
    }).change()

    $postalCodeInput
      .on('keypress', function (event) {
        if (event.keyCode === 13) {
          event.preventDefault()

          $locationIdInput.focus()
        }
      })
      .on('blur', function (event) {
        var postalCodeInput = event.target

        if (postalCodeInput.value !== postalCodeInput.defaultValue) {
          postalCodeInput.defaultValue = postalCodeInput.value

          $form.block({
            message: null,
            overlayCSS: {
              background: '#fff',
              opacity: 0.6
            }
          })

          $.post(ajaxurl, {
            action: 'gdex-get-place',
            postal_code: postalCodeInput.value,
            'gdex-nonce': gdex_setting.get_place.nonce,
          }, function (response) {
            $locationIdInput.html('')
            $locationInput.val('')
            $cityInput.val('')
            $stateIdInput.val('')

            if (response.success) {
              var optionHtml = ''

              lodash.each(response.data.locations, function (location) {
                optionHtml += '<option value="' + location.id + '" data-city="' + location.city + '" data-state="' + location.state + '">' + location.name + '</option>'
              })

              $locationIdInput.html(optionHtml).change()
            } else {
              alert(response.data.message)
            }

            $form.unblock()
          })
        }
      })

    $locationIdInput.on('change', function (event) {
      var $selectedOption = $(event.target).find(':selected')

      $locationInput.val($selectedOption.text())
      $cityInput.val($selectedOption.data('city'))
      $stateIdInput.val($selectedOption.data('state'))
    })
  }

  function init_consignments_page () {
    var adminpage = window.adminpage
    var typenow = window.typenow

    if (typenow !== 'gdex-consignment') {
      return
    }

    if (adminpage !== 'edit-php') {
      return
    }

    var $form = $('#posts-filter')
    $form.on('submit', function (event) {

      var $action = $('#bulk-action-selector-top')
      if ($action.val() !== 'gdex_print_consignments') {
        return
      }

      var $print_links = $('#the-list :checked[name="post[]"]').closest('.hentry').find('.print-button')
      if (!$print_links.length) {
        return
      }

      event.preventDefault()

      var consignment_ids = $print_links.map(function (index, link) {
        var $link = $(link)

        var $entry = $link.closest('.hentry')

        return $entry.attr('id').split('-')[1]
      }).get()

      gdex_consignments.print_notes.consignment_ids = consignment_ids

      var print_notes_url = ajaxurl + '?' + $.param(gdex_consignments.print_notes)

      window.open(print_notes_url)
    })
  }

  function init_order_page () {
    var adminpage = window.adminpage
    var typenow = window.typenow

    if (typenow !== 'shop_order') {
      return
    }

    if (adminpage !== 'post-new-php' && adminpage !== 'post-php') {
      return
    }

    init_shipping_estimate_meta_box()

    function init_shipping_estimate_meta_box () {
      var $box = $('#gdex-shipping-estimate-meta-box')
      if (!$box.length) {
        return
      }

      new Vue({
        el: $box[0],

        data: {
          postcode: gdex_shipping_estimate_meta_box.postcode,
          country: gdex_shipping_estimate_meta_box.country,
          weight: gdex_shipping_estimate_meta_box.weight,
          estimate: gdex_shipping_estimate_meta_box.estimate,
          quoted_at: gdex_shipping_estimate_meta_box.quoted_at,
        },

        computed: {
          $box () {
            return $(this.$el)
          },

          has_estimate: function () {
            return !!this.estimate
          },
        },

        methods: {
          submit () {
            block_meta_box(this.$box)

            var vm = this

            $.post(woocommerce_admin_meta_boxes.ajax_url, {
              action: 'gdex-quote-shipping-estimate',
              order_id: woocommerce_admin_meta_boxes.post_id,
              nonce: $('#gdex-shipping-estimate-meta-box-nonce').val(),
            }, function (response) {
              if (response.success) {
                vm.estimate = response.data.estimate
                vm.quoted_at = response.data.quoted_at
              }

              unblock_meta_box(vm.$box)
            })
          },

          create_shipment_order (event) {
            var $button = $(event.target)

            window.location = $button.data('target')
          }
        }
      })
    }
  }

  function init_shipment_page () {
    var adminpage = window.adminpage
    var typenow = window.typenow

    if (typenow !== 'gdex-shipment-order') {
      return
    }

    if (adminpage !== 'post-new-php' && adminpage !== 'post-php') {
      return
    }

    init_submenu()
    init_shipment_order_data_meta_box()

    function init_submenu () {
      var $parent_menu = $('#toplevel_page_gdex')
      $parent_menu.removeClass('wp-not-current-submenu')
      $parent_menu.addClass('wp-has-current-submenu')
      $parent_menu.addClass('wp-menu-open')

      $parent_menu.children('a').removeClass('wp-not-current-submenu')
      $parent_menu.children('a').addClass('wp-has-current-submenu')
      $parent_menu.children('a').addClass('wp-menu-open')

      var $submenu = $('[href="edit.php?post_type=gdex-shipment-order"]')
      $submenu.addClass('current')
      $submenu.parent('li').addClass('current')
    }

    function init_shipment_order_data_meta_box () {
      var $post = $('.post-new-php.post-type-gdex-shipment-order #post')
      if (!$post.length) {
        return
      }

      window.a = new Vue({
        el: $post[0],

        data: {
          sender_name: gdex_shipment_order_data_meta_box.sender_name,
          sender_email: gdex_shipment_order_data_meta_box.sender_email,
          sender_mobile_number: gdex_shipment_order_data_meta_box.sender_mobile_number,
          sender_address1: gdex_shipment_order_data_meta_box.sender_address1,
          sender_address2: gdex_shipment_order_data_meta_box.sender_address2,
          sender_postal_code: gdex_shipment_order_data_meta_box.sender_postal_code,
          sender_location_id: gdex_shipment_order_data_meta_box.sender_location_id,
          sender_location: gdex_shipment_order_data_meta_box.sender_location,
          sender_city: gdex_shipment_order_data_meta_box.sender_city,
          sender_state: gdex_shipment_order_data_meta_box.sender_state,
          service_type: gdex_shipment_order_data_meta_box.service_type,
          pick_up_date: '',
          pick_up_time: '09:00:00',
          pick_up_transportation: gdex_shipment_order_data_meta_box.pick_up_transportation,
          pick_up_trolley_required: 'no',
          pick_up_remark: '',
          consignments: gdex_shipment_order_consignments_meta_box.consignments,
          options: {
            locations: gdex_shipment_order_data_meta_box.locations,
            pick_up_dates: [],
          },
          states: {
            shipping_rate: 0.00,
            wallet_balance: gdex_shipment_order_actions_meta_box.wallet_balance,
            is_sender_postal_code_valid: true,
            is_updating_locations: false,
            is_updating_pick_up_dates: false,
            is_submitting: false,
          },
        },

        computed: {
          total () {
            return lodash.sumBy(this.consignments, 'rate')
          },

          isPickUp () {
            return this.service_type === 'pick up'
          },

          is_pick_up_saturday () {
            if (!this.pick_up_date) {
              return false
            }

            var pick_up_date = new Date(this.pick_up_date)

            return pick_up_date.getDay() === 6
          },

          is_submitting () {
            return this.states.is_submitting
          },

          is_updating () {
            if (this.states.is_updating_locations || this.states.is_updating_pick_up_dates) {
              return true
            }

            if (lodash.some(this.consignments, 'is_updating_rate')) {
              return true
            }

            return false
          },

          is_insufficient_balance () {
            return this.total > this.states.wallet_balance
          },

          can_submit () {
            if (this.is_submitting) {
              return false
            }

            if (this.is_updating) {
              return false
            }

            if (this.$v.$invalid) {
              return false
            }

            if (this.is_insufficient_balance) {
              return false
            }

            return true
          }
        },

        watch: {
          is_pick_up_saturday (is_pick_up_saturday) {
            if (is_pick_up_saturday) {
              this.pick_up_time = '09:00:00'
            }
          }
        },

        validations: {
          sender_name: {
            required: validators.required
          },
          sender_email: {
            required: validators.required,
            email: validators.email
          },
          sender_mobile_number: {
            required: validators.required
          },
          sender_address1: {
            required: validators.required
          },
          sender_address2: {},
          sender_postal_code: {
            required: validators.required,
            maxLength: validators.maxLength(6),
            exists (sender_postal_code) {
              return this.states.is_sender_postal_code_valid
            }
          },
          sender_location_id: {
            required: validators.required
          },
          sender_location: {
            required: validators.required
          },
          sender_city: {
            required: validators.required
          },
          sender_state: {
            required: validators.required
          },
          service_type: {
            required: validators.required
          },
          pick_up_date: {
            required: validators.requiredIf(function (pick_up_date) {
              return this.isPickUp && !this.states.is_sender_postal_code_valid && pick_up_date
            })
          },
          pick_up_time: {
            required: validators.requiredIf('isPickup')
          },
          pick_up_transportation: {
            required: validators.requiredIf(function (pick_up_transportation) {
              return this.isPickUp && pick_up_transportation
            })
          },
          pick_up_trolley_required: {
            required: validators.requiredIf(function (pick_up_trolley_required) {
              return this.isPickUp && pick_up_trolley_required
            })
          },
          pick_up_remark: {},
          consignments: {
            required: validators.required,
            $each: {
              parcel_type: {
                required: validators.required
              },
              pieces: {
                required: validators.required,
                min: validators.minValue(1)
              },
              weight: {
                required: validators.required,
                min: validators.minValue(0.1)
              }
            }
          }
        },

        methods: {
          update_locations () {
            this.states.is_sender_postal_code_valid = true
            this.states.is_updating_locations = true

            var vm = this

            $.post(ajaxurl, {
              action: 'gdex-get-place',
              postal_code: vm.sender_postal_code,
              'gdex-nonce': $('#gdex_get_place_nonce').val(),
            }, function (response) {
              vm.states.is_sender_postal_code_valid = response.success

              if (vm.states.is_sender_postal_code_valid) {
                vm.options.locations = response.data.locations
                vm.sender_location_id = vm.options.locations.length ? vm.options.locations[0].id : ''
                vm.update_place()
              } else {
                vm.options.locations = []
                vm.sender_location_id = ''
                vm.sender_location = ''
                vm.sender_city = ''
                vm.sender_state = ''
              }

              vm.states.is_updating_locations = false
            })
          },

          update_place () {
            var vm = this

            var location = this.options.locations.find(function (location) {
              return location.id === vm.sender_location_id
            })

            vm.sender_location = location.name
            vm.sender_city = location.city
            vm.sender_state = location.state

          },

          update_pick_up_dates () {
            this.states.is_sender_postal_code_valid = true
            this.states.is_updating_pick_up_dates = true

            var vm = this

            $.post(ajaxurl, {
              action: 'gdex-get-shipment-order-pick-up-dates',
              postcode: this.sender_postal_code,
              nonce: $('#gdex_get_shipment_order_pick_up_dates_nonce').val(),
            }, function (response) {
              vm.states.is_sender_postal_code_valid = response.success

              if (vm.states.is_sender_postal_code_valid) {
                vm.options.pick_up_dates = response.data.dates
                vm.pick_up_date = vm.options.pick_up_dates.length ? vm.options.pick_up_dates[0].value : ''
              } else {
                vm.options.pick_up_dates = []
                vm.pick_up_date = ''
              }

              vm.states.is_updating_pick_up_dates = false
            })
          },

          handle_sender_postal_code_on_blur (event) {
            var sender_postal_code_input = event.target

            if (sender_postal_code_input.value !== sender_postal_code_input.defaultValue) {
              sender_postal_code_input.defaultValue = sender_postal_code_input.value

              this.update_locations()
              this.update_pick_up_dates()
              this.update_all_consignment_shipping_rates()
            }
          },

          update_consignment_shipping_rate (consignment) {
            if (consignment.is_updating_rate) {
              consignment.is_updating_rate.abort()
            }

            var vm = this

            consignment.is_updating_rate = $.post(ajaxurl, {
              action: 'gdex-consignment-quote-shipping-rate',
              sender_postal_code: this.sender_postal_code,
              order_id: consignment.order_id,
              weight: consignment.weight,
              type: consignment.parcel_type,
              nonce: $('#gdex_consignment_quote_shipping_rate_nonce').val(),
            }, function (response) {
              if (response.success) {
                consignment.is_updating_rate = false
                consignment.rate = response.data.rate
              } else {
                // vm.update_consignment_shipping_rate(consignment)
              }
            })
          },

          update_all_consignment_shipping_rates () {
            lodash.forEach(this.consignments, this.update_consignment_shipping_rate)
          },

          handle_consignment_parcel_type_on_change (consignment, event) {
            this.update_consignment_shipping_rate(consignment)
          },

          handle_consignment_weight_on_blur (consignment, event) {
            var consignment_weigt_input = event.target

            if (consignment_weigt_input.value !== consignment_weigt_input.defaultValue) {
              consignment_weigt_input.defaultValue = consignment_weigt_input.value

              this.update_consignment_shipping_rate(consignment)
            }
          },

          submit () {
            this.$v.$touch()

            if (!this.can_submit) {
              return
            }

            this.states.is_submitting = true

            this.$el.submit()
          }
        },

        mounted () {
          this.update_pick_up_dates()
          this.update_all_consignment_shipping_rates()
        }
      })
    }
  }

  function block_meta_box ($box) {
    $box.block({
      message: null,
      overlayCSS: {
        background: '#fff',
        opacity: 0.6
      }
    })
  }

  function unblock_meta_box ($box) {
    $box.unblock()
  }

})(jQuery)
