/**
 * NOTICE OF LICENSE
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @author    Mondial Relay
 * @copyright Copyright (c) Mondial Relay
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

/**
 * Javascript for PS 1.6, when using OPC
 */

// modules ES6
import * as MR from './checkout';

// Tracks wether a MR carrier was selected; useful to detect changes between
// MR / non-MR carriers
let mondialRelayCarrierSelected = false;

/**
 * Mostly from PrestaShop order-opc.js, updateNewAccountToAddressBlock()
 * Updates the addresses from server
 */
const updateAddresses = () => {
  $.ajax({
    type: 'POST',
    headers: { "cache-control": "no-cache" },
    url: orderOpcUrl + '?rand=' + new Date().getTime(),
    async: true,
    cache: false,
    dataType : "json",
    data: 'ajax=true&method=getAddressBlockAndCarriersAndPayments&token=' + static_token,
    success: function(json)
    {
      if (json.hasError)
      {
        var errors = '';
        for(var error in json.errors)
          //IE6 bug fix
          if(error !== 'indexOf')
            errors += $('<div />').html(json.errors[error]).text() + "\n";
        alert(errors);
      }
      else
      {
        if (typeof json.formatedAddressFieldsValuesList !== 'undefined' && json.formatedAddressFieldsValuesList )
        formatedAddressFieldsValuesList = json.formatedAddressFieldsValuesList;
        
        if (typeof json.order_opc_adress !== 'undefined' && json.order_opc_adress) {
          $('.opc-main-block .addresses').html($($.parseHTML('<div>'+json.order_opc_adress+'</div>')).find('.opc-main-block .addresses').contents());
          updateAddressesDisplay(true);
          if (typeof bindUniform !=='undefined') {
            bindUniform();
          }
        }
      }
    },
    error: function(XMLHttpRequest, textStatus, errorThrown) {
      
    }
  });
};

$(MR.widget).on('mondialrelay.ready', function() {
  if (MR.isRelayCarrierSelected()) {
    mondialRelayCarrierSelected = true;
    if (!MR.widget.savedRelay) {
      // This *should* hide or show the payment methods using a pretty
      // dirty method, but it's still the cleanest.
      $("#HOOK_PAYMENT").empty();
      updatePaymentMethodsDisplay();
      MR.widget.show();
    } else {
      $(MR.widget.summary_container).show();
      MR.widget.hide();
    }
    MR.widget.init({ ColLivMod: MR.getCarrierDeliveryMode(MR.getSelectedCarrierId()) });
  }
});

$(document).on('mondialrelay.contentRefreshed', '#mondialrelay_content', function(ev, data) {
  // We only care about this event if the content was reloaded by AJAX
  if (!data.fromAjax) {
    return;
  }
  
  // The content was refreshed, so the widget is no longer there
  MR.widget.initialized = false;
  // Get latest data
  MR.widget.savedRelay = MONDIALRELAY_SELECTED_RELAY_IDENTIFIER;
  
  if (!MR.isRelayCarrierSelected()) {
    if (mondialRelayCarrierSelected) {
      updateAddresses();
    }
    MR.widget.hide();
    mondialRelayCarrierSelected = false;
  } else {
    if (MR.widget.savedRelay) {
      // If we have a selected relay, then we're reselecting the carrier
      // Resubmit the relay, just in case
      $(MR.widget.save_button).click();
      MR.widget.hide();
    } else {
      MR.widget.show();
    }
    
    MR.widget.initOrUpdate({ColLivMod: MR.getCarrierDeliveryMode(MR.getSelectedCarrierId())});
    mondialRelayCarrierSelected = true;
  }
});

$(MR.widget).on('mondialrelay.saveSelectedRelay.before', function() {
  // This *should* hide or show the payment methods using a pretty
  // dirty method, but it's still the cleanest.
  $("#HOOK_PAYMENT").empty();
});

$(MR.widget).on('mondialrelay.saveSelectedRelay.success', function() {
  // This *should* hide or display the payment methods using a pretty
  // dirty method, but it's still the cleanest. We have to call it
  // after our own call, because we can't specify any additional data
  // to send.
  updatePaymentMethodsDisplay();
  updateAddresses();
});

$(MR.widget).on('mondialrelay.saveSelectedRelay.error', function() {
  // This *should* hide or display the payment methods using a pretty
  // dirty method, but it's still the cleanest. We have to call it
  // after our own call, because we can't specify any additional data
  // to send.
  updatePaymentMethodsDisplay();
  updateAddresses();
});