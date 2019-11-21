/**
 * NOTICE OF LICENSE
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @author    Mondial Relay
 * @copyright Copyright (c) Mondial Relay
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

// modules ES6
import * as MR_Widget from '../../mondialrelay_widget';

const widget = MR_Widget.widget;

const isRelayCarrierSelected = () => {
  return -1 != MONDIALRELAY_NATIVE_RELAY_CARRIERS_IDS.indexOf(getSelectedCarrierId());
};

const getSelectedCarrierId = () => {
  let id = $('[name^="delivery_option"]:checked').val();
  return id ? id.replace(',', '') : false;
};

const getCarrierDeliveryMode = (id_carrier) => {
  return MONDIALRELAY_CARRIER_METHODS[id_carrier]['delivery_mode'];
};

$(document).on('click', widget.save_button, function(ev) {
  ev.preventDefault();
  ev.stopPropagation();

  widget.displayErrors(null);
  $(widget.summary_container).empty().show();
  widget.addLoader(widget.summary_container);
  
  $(widget).trigger('mondialrelay.saveSelectedRelay.before');
  
  let params = {
    ajax: true,
    action: 'saveSelectedRelay',
    mondialrelay_selectedRelay: MONDIALRELAY_SELECTED_RELAY_IDENTIFIER ? MONDIALRELAY_SELECTED_RELAY_IDENTIFIER : 0,
    id_carrier: getSelectedCarrierId(),
  };
  
  $.ajax(
  {
    type : 'POST',
    url: MONDIALRELAY_AJAX_CHECKOUT_URL,
    data : params,
    dataType: 'json',
    success: function(response) {
      if (!response) {
        this.error(response);
        return;
      }
      
      if (response.status == 'ok') {
        widget.savedRelay = MONDIALRELAY_SELECTED_RELAY_IDENTIFIER;
        $(widget.summary_container)
          .html(response.content.relaySummary);
        widget.hide();
        $(widget.save_container).hide();
        $(widget.widget_container).slideUp();
        $(widget.save_container).slideUp();
      } else {
        widget.savedRelay = null;
        if (response.error.length) {
          widget.displayErrors(response.error);
        }
        widget.removeLoader($(widget.summary_container));
        $(widget).trigger('mondialrelay.saveSelectedRelay.error');
        return;
      }
      
      $(widget).trigger('mondialrelay.saveSelectedRelay.success');
    },
    error: function(response) {
      widget.savedRelay = null;
      alert(MONDIALRELAY_SAVE_RELAY_ERROR);
      $(widget).trigger('mondialrelay.saveSelectedRelay.error');
      widget.removeLoader($(widget.summary_container));
    }
  });
  
  return false;
});

export {
  isRelayCarrierSelected,
  getSelectedCarrierId,
  getCarrierDeliveryMode,
  widget,
};
