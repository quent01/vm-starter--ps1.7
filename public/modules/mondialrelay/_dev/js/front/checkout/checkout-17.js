/**
 * NOTICE OF LICENSE
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @author    Mondial Relay
 * @copyright Copyright (c) Mondial Relay
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

/**
 * Javascript for PS 1.7
 */

// modules ES6
import * as MR from './checkout';

// The container of the whole "mondialrelay_content", as the original one is
// badly placed
let selectedCarrierExtraContent = null;

// This will track if the page should be refreshed when changing carrier, as
// other carriers may depend on it
let flagPaymentStepNeedsRefresh = null;

// This will track if the payment step should be reachable using the link or not
let isPaymentStepReachable = null;

const setSelectedCarrierExtraContent = () => {
  selectedCarrierExtraContent = $('[name^="delivery_option"]:checked')
    .closest('.delivery-option')
    .next('.carrier-extra-content');
  return selectedCarrierExtraContent;
};

const setProcessLocked = (lock) => {
  if (lock) {
    // Delivery step is not complete
    $("#checkout-delivery-step").removeClass('-complete');
    // Disable submit delivery button
    $("button[name='confirmDeliveryOption']").attr('disabled', true);
  } else {
    // Delivery step is not complete
    $("#checkout-delivery-step").addClass('-complete');
    // Enable submit delivery button
    $("button[name='confirmDeliveryOption']").attr('disabled', false);
  }
};

$(MR.widget).on('mondialrelay.ready', function() {
  if (MR.isRelayCarrierSelected()) {
    setSelectedCarrierExtraContent();
  
    // "Payment" step is not reachable using the link; save the previous state to
    // restore it if carrier changes. The user MUST click the form button,
    // otherwise the validation hook won't be triggered.
    if (isPaymentStepReachable === null) {
      isPaymentStepReachable = $("#checkout-payment-step").hasClass('-reachable');
    }
    $("#checkout-payment-step").removeClass('-reachable').addClass('-unreachable');
    
    $("#mondialrelay_content").appendTo(selectedCarrierExtraContent);
    if (!MR.widget.savedRelay) {
      setProcessLocked(true);
      MR.widget.show();
    } else {
      $(MR.widget.summary_container).show();
      MR.widget.hide();
    }
    MR.widget.init({ ColLivMod: MR.getCarrierDeliveryMode(MR.getSelectedCarrierId()) });
  }
  
  // Click events sometimes mess with PrestaShop's checkout process, so catch
  // them and silence them
  $(MR.widget.widget_container).on('click', function(ev) {
    ev.preventDefault();
    ev.stopPropagation();
  });
});

// We can't submit the form if no relay was selected
$(document).on('submit', '#js-delivery', function(ev) {
  if (MR.isRelayCarrierSelected() && !MR.widget.savedRelay) {
    ev.preventDefault();
    ev.stopPropagation();

    setSelectedCarrierExtraContent();
    $("#mondialrelay_content").appendTo(selectedCarrierExtraContent);
    MR.widget.initOrUpdate({ ColLivMod: MR.getCarrierDeliveryMode(MR.getSelectedCarrierId()) });

    alert(MONDIALRELAY_NO_SELECTION_ERROR);
    return false;
  }

  return true;
});

prestashop.on('updatedDeliveryForm', function() {
  // The content was refreshed, so the widget is no longer there
  MR.widget.initialized = false;
  // Get latest data
  MR.widget.savedRelay = MONDIALRELAY_SELECTED_RELAY_IDENTIFIER;
});

// We must check this event if Mondial Relay is selected, otherwise the
// form is reloaded immediately, and we can't check if a relay was selected; our
// PS 1.7.2+ hook is never triggered as it requires the form to be submitted
// with the submit button, not just when a carrier changes or a relay is
// selected.
$('#js-delivery').on('change', '[name^="delivery_option"]', function(ev) {
  // If another carrier than Mondial Relay is selected
  if (!MR.isRelayCarrierSelected()) {
    // Hide our extraContent, and set it to null
    if (selectedCarrierExtraContent) {
      selectedCarrierExtraContent.hide();
      selectedCarrierExtraContent = null;
    }
    setProcessLocked(false);
    
    MR.widget.resetSelectedRelay();
    MR.widget.resetSavedRelay(true);
    
    if (flagPaymentStepNeedsRefresh) {
      // Some carriers may depend on the page refresh
      $("#checkout-payment-step .content").append(flagPaymentStepNeedsRefresh);
      flagPaymentStepNeedsRefresh = null;
    }
    if (isPaymentStepReachable !== null && isPaymentStepReachable) {
      // "Payment" step is reachable
      $("#checkout-payment-step").addClass('-reachable').removeClass('-unreachable');
    }
    return;
  }

  // We let this event propagate. If no relay is selected, we'll see an alert or
  // a php error and we'll stay on delivery step.
  /*ev.preventDefault();
  ev.stopPropagation();*/

  // We never want a page refresh when using a carrier requiring a relay; the
  // user MUST click the form button, otherwise the validation hook won't be
  // triggered
  flagPaymentStepNeedsRefresh = $('.js-cart-payment-step-refresh').clone();
  $('.js-cart-payment-step-refresh').remove();
  
  // "Payment" step is not reachable using the link; save the previous state to
  // restore it if carrier changes
  if (isPaymentStepReachable === null) {
    isPaymentStepReachable = $("#checkout-payment-step").hasClass('-reachable');
  }
  $("#checkout-payment-step").removeClass('-reachable').addClass('-unreachable');

  // We can't go further if we haven't selected a relay
  if (!MR.widget.savedRelay) {
    setProcessLocked(true);
    MR.widget.show();
  } else if (!$(ev.target).is(MR.widget.selected_relay_input)) {
      // If we DO have a selected relay, then we're reselecting a carrier
      // The delivery_mode may be different though
      MR.widget.hide();
  }

  // If we switched from a Mondial Relay carrier to another, hide the previous
  // extraContent
  if (selectedCarrierExtraContent) {
    selectedCarrierExtraContent.hide();
  }
  
  // If we changed delivery mode, then our current relay selection is invalid
  if (MR.widget.widget_current_params != null) {
    let oldDeliveryMode = MR.widget.widget_current_params.ColLivMod;
    let newDeliveryMode = MR.getCarrierDeliveryMode(MR.getSelectedCarrierId());
    if (oldDeliveryMode != newDeliveryMode) {
        MR.widget.resetSelectedRelay();
        MR.widget.resetSavedRelay();
    }
  }
  
  // Show the new extraContent, and update the widget
  setSelectedCarrierExtraContent();
  $("#mondialrelay_content").appendTo(selectedCarrierExtraContent);
  MR.widget.initOrUpdate({ColLivMod: MR.getCarrierDeliveryMode(MR.getSelectedCarrierId())});
});

// When selecting a PR in the list from the widget
$('#js-delivery').on('change', '#mondialrelay_content *', function(ev) {
  // If we selected a Mondial Relay carrier, we MUST NOT let that event
  // propagate, or a form reload may be triggered even though we haven't
  // selected a relay
  ev.preventDefault();
  ev.stopPropagation();
});

$(MR.widget).on('mondialrelay.saveSelectedRelay.before', function() {
  setProcessLocked(true);
  // Disable delivery options change, to prevent concurrent AJAX requests
  $("[name^='delivery_option']")
    .attr('readonly', true)
    .on('click.mondialrelay.lock', function(ev) {
      ev.preventDefault();
      ev.stopPropagation();
    });
});

$(MR.widget).on('mondialrelay.saveSelectedRelay.success', function() {
  setProcessLocked(false);
  // Enable delivery options change
  $("[name^='delivery_option']")
    .attr('readonly', false)
    .off('click.mondialrelay.lock');
});

$(MR.widget).on('mondialrelay.saveSelectedRelay.error', function() {
  setProcessLocked(true);
  // Enable delivery options change
  $("[name^='delivery_option']")
    .attr('readonly', false)
    .off('click.mondialrelay.lock');
});