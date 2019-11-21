/**
 * NOTICE OF LICENSE
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @author    Mondial Relay
 * @copyright Copyright (c) Mondial Relay
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

// modules ES6
import * as MR_Widget from '../mondialrelay_widget';

$(MR_Widget.widget).on('mondialrelay.ready', function() {
  // Don't show the map if it's not immediately needed
  if (MR_Widget.widget.savedRelay) {
    MR_Widget.widget.hide();
  }
  MR_Widget.widget.init({ ColLivMod: MONDIALRELAY_DELIVERY_MODE });
});

 $(MR_Widget.widget).on('mondialrelay.selectedRelay', function(ev, data) {
  MR_Widget.widget.displayErrors(null);
  $("#mondialrelay_displayed-relay-number").val(data.relayData.ID);
});

$(document).on('submit', '#mondialrelay_selected_relay_form', function(ev) {
  if (!$(MR_Widget.widget.selected_relay_input).val()) {
    MR_Widget.widget.displayErrors([MONDIALRELAY_NO_SELECTION_ERROR]);
    ev.stopPropagation();
    ev.preventDefault();
    return false;
  }
});