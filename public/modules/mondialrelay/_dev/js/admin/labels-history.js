/**
 * NOTICE OF LICENSE
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @author    Mondial Relay
 * @copyright Copyright (c) Mondial Relay
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

$(document).ready(function() {
  // This is basically black magic.
  // PrestaShop modifies the form when submitting a bulk action, but since
  // we're downloading a file, there's no actual page reload when the request
  // succeeds.
  // So we're left with a dirty form which won't work when submitting
  // successive bulk actions.
  // We're going to replace PS function with our own, which clones the form
  // and calls the native PS function using the clone rather than the original
  // form.
  // We're on our own module page, so it *shouldn't* cause too much trouble.
  sendBulkActionPS = sendBulkAction;
  
  let submitted = false;
  sendBulkAction = function(form, action) {
    if (submitted) {
      return;
    }
    submitted = true;
    
    let virtualForm = $(form).clone(true, true);
    virtualForm.attr('id', $(form).attr('id') + '-clone');
    virtualForm.hide().appendTo('body');
    
    sendBulkActionPS(virtualForm, action);
    
    virtualForm.remove();
    submitted = false;
  };
});