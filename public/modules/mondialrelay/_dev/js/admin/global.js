/**
 * NOTICE OF LICENSE
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @author    Mondial Relay
 * @copyright Copyright (c) Mondial Relay
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

/**
 * IMPORTANT NOTE : since the #ajaxBox is animated and its content modified
 * using callbacks on the animations, every function using that box should
 * probably use JQuery's .queue() method for synchronization
 */

const clearAjaxMessages = (box) => {
  $(box || '#ajaxBox').fadeOut(function () {
    $(this).empty();
  });
};

const displayAjaxMessages = (box) => {
  $(box || '#ajaxBox').fadeIn();
};

const addAjaxConfirmation = (content, box) => {
  $(box || '#ajaxBox').queue(function () {
    $(this).append(
      $('#ajax_confirmation')
        .clone()
        .attr('id', null)
        .removeClass('hide')
        .html(content)
    );
    $(this).dequeue();
  });
};

const addAjaxError = (content, box) => {
  $(box || '#ajaxBox').queue(function () {
    $(this).append(
      $('#ajax_confirmation')
        .clone()
        .attr('id', null)
        .removeClass('hide alert-success')
        .addClass('alert-danger')
        .html(content)
    );
    $(this).dequeue();
  });
};

const addAjaxMessages = (data, box) => {
  data.confirmations.forEach(confirmation => {
    addAjaxConfirmation(confirmation, box);
  });

  data.error.forEach(error => {
    addAjaxError(error, box);
  });
};

export {
  clearAjaxMessages,
  displayAjaxMessages,
  addAjaxConfirmation,
  addAjaxError,
  addAjaxMessages
};
