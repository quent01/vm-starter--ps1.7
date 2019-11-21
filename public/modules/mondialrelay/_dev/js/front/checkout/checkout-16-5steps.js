/**
 * NOTICE OF LICENSE
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @author    Mondial Relay
 * @copyright Copyright (c) Mondial Relay
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

/**
 * Javascript for PS 1.6, when not using OPC
 */

// modules ES6
import * as MR from './checkout';

$(document).on('submit', '#form', function(ev) {
  if (MR.isRelayCarrierSelected() && !MR.widget.savedRelay) {
    ev.preventDefault();
    ev.stopPropagation();
    alert(MONDIALRELAY_NO_SELECTION_ERROR);
    return false;
  }
  
  return true;
});

$(document).on('change', '[name^="delivery_option"]', function(ev) {
  if (!MR.isRelayCarrierSelected()) {
    MR.widget.hide();
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
  
  // Hide payment methods as long as we don't have a relay selected
  if (!MR.isRelayCarrierSelected()) {
    MR.widget.hide();
  } else {
    if (MR.widget.savedRelay) {
      // If we have a selected relay, then we're reselecting the carrier
      // Resubmit the relay, just in case
      $(MR.widget.save_button).click();
    }
    MR.widget.show();
    MR.widget.initOrUpdate({ColLivMod: MR.getCarrierDeliveryMode(MR.getSelectedCarrierId())});
  }
});

$(MR.widget).on('mondialrelay.ready', function() {
  if (MR.isRelayCarrierSelected()) {
    if (!MR.widget.savedRelay) {
      MR.widget.show();
    } else {
      $(MR.widget.summary_container).show();
      MR.widget.hide();
    }
    MR.widget.init({ ColLivMod: MR.getCarrierDeliveryMode(MR.getSelectedCarrierId()) });
  }
});