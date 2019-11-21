/**
 * NOTICE OF LICENSE
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @author    Mondial Relay
 * @copyright Copyright (c) Mondial Relay
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

// modules ES6
import * as MR from './global';

const settings = MONDIALRELAY_HELP;

$(document).on('click', '#mondialrelay_check-requirements', function () {
  const button = this;
  const messagesBox = '#mondialrelay_requirements-results';  

  let params = {
    ajax: true,
    action: 'checkRequirements'
  };

  MR.clearAjaxMessages(messagesBox);
  button.disabled = true;

  $.ajax({
    url: settings.helpUrl,
    method: 'POST',
    data: params,
    dataType: 'json',

    success: response => {
      MR.addAjaxMessages(response, messagesBox);
      MR.displayAjaxMessages(messagesBox);
    },

    error: response => {
      MR.addAjaxError(MONDIALRELAY_MESSAGES.unknown_error, messagesBox);
      MR.displayAjaxMessages(messagesBox);
    },

    complete: () => {
      button.disabled = false;
    }
  });
});

$(document).on('click', '#mondialrelay_register-hooks', function () {
  const button = this;
  const messagesBox = '#mondialrelay_requirements-results';  

  let params = {
    ajax: true,
    action: 'registerHooks'
  };

  MR.clearAjaxMessages(messagesBox);
  button.disabled = true;

  $.ajax({
    url: settings.helpUrl,
    method: 'POST',
    data: params,
    dataType: 'json',

    success: response => {
      MR.addAjaxMessages(response, messagesBox);
      MR.displayAjaxMessages(messagesBox);
    },

    error: response => {
      MR.addAjaxError(MONDIALRELAY_MESSAGES.unknown_error, messagesBox);
      MR.displayAjaxMessages(messagesBox);
    },

    complete: () => {
      button.disabled = false;
    }
  });
});
