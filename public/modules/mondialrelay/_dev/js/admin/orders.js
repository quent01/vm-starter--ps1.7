/**
 * NOTICE OF LICENSE
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @author    Mondial Relay
 * @copyright Copyright (c) Mondial Relay
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

$(document).ready(function() {
  $(".shipping_number_show").filter(function(i, elem) {
    return $.trim($(elem).text()) == MONDIALRELAY_ORDER_TRACKING_NUMBER;
  }).each(function() {
    $('<a/>').attr({
      'href': MONDIALRELAY_ORDER_TRACKING_URL,
      'target': 'blank',
    }).append($(this).contents()).appendTo(this);
  });
});