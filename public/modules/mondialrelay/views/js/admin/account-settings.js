/*!
 * NOTICE OF LICENSE
 * 
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @author    Mondial Relay
 * @copyright Copyright (c) Mondial Relay
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */!function(n){var e={};function t(o){if(e[o])return e[o].exports;var r=e[o]={i:o,l:!1,exports:{}};return n[o].call(r.exports,r,r.exports,t),r.l=!0,r.exports}t.m=n,t.c=e,t.d=function(n,e,o){t.o(n,e)||Object.defineProperty(n,e,{enumerable:!0,get:o})},t.r=function(n){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(n,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(n,"__esModule",{value:!0})},t.t=function(n,e){if(1&e&&(n=t(n)),8&e)return n;if(4&e&&"object"==typeof n&&n&&n.__esModule)return n;var o=Object.create(null);if(t.r(o),Object.defineProperty(o,"default",{enumerable:!0,value:n}),2&e&&"string"!=typeof n)for(var r in n)t.d(o,r,function(e){return n[e]}.bind(null,r));return o},t.n=function(n){var e=n&&n.__esModule?function(){return n.default}:function(){return n};return t.d(e,"a",e),e},t.o=function(n,e){return Object.prototype.hasOwnProperty.call(n,e)},t.p="",t(t.s=5)}({2:function(n,e,t){"use strict";t.d(e,"c",function(){return o}),t.d(e,"d",function(){return r}),t.d(e,"a",function(){return u}),t.d(e,"b",function(){return c});
/**
 * NOTICE OF LICENSE
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @author    Mondial Relay
 * @copyright Copyright (c) Mondial Relay
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
var o=function(n){$(n||"#ajaxBox").fadeOut(function(){$(this).empty()})},r=function(n){$(n||"#ajaxBox").fadeIn()},u=function(n,e){$(e||"#ajaxBox").queue(function(){$(this).append($("#ajax_confirmation").clone().attr("id",null).removeClass("hide alert-success").addClass("alert-danger").html(n)),$(this).dequeue()})},c=function(n,e){n.confirmations.forEach(function(n){!function(n,e){$(e||"#ajaxBox").queue(function(){$(this).append($("#ajax_confirmation").clone().attr("id",null).removeClass("hide").html(n)),$(this).dequeue()})}(n,e)}),n.error.forEach(function(n){u(n,e)})}},5:function(n,e,t){"use strict";t.r(e);var o=t(2),r=MONDIALRELAY_ACCOUNTSETTINGS;
/**
 * NOTICE OF LICENSE
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @author    Mondial Relay
 * @copyright Copyright (c) Mondial Relay
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */$(document).on("click","#mondialrelay_check-connection",function(){var n=this,e=n.form,t={ajax:!0,action:"checkConnection"};r.checkConnectionFields.forEach(function(n){t[n]=$(e).find('[name="'.concat(n,'"]')).val()}),o.c(),n.disabled=!0,$.ajax({url:r.accountSettingsUrl,method:"POST",data:t,dataType:"json",success:function(n){o.b(n),o.d()},error:function(n){o.a(MONDIALRELAY_MESSAGES.unknown_error),o.d()},complete:function(){n.disabled=!1}})})}});