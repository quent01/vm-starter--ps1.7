/*!
 * NOTICE OF LICENSE
 * 
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @author    Mondial Relay
 * @copyright Copyright (c) Mondial Relay
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */!function(e){var n={};function t(r){if(n[r])return n[r].exports;var o=n[r]={i:r,l:!1,exports:{}};return e[r].call(o.exports,o,o.exports,t),o.l=!0,o.exports}t.m=e,t.c=n,t.d=function(e,n,r){t.o(e,n)||Object.defineProperty(e,n,{enumerable:!0,get:r})},t.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},t.t=function(e,n){if(1&n&&(e=t(e)),8&n)return e;if(4&n&&"object"==typeof e&&e&&e.__esModule)return e;var r=Object.create(null);if(t.r(r),Object.defineProperty(r,"default",{enumerable:!0,value:e}),2&n&&"string"!=typeof e)for(var o in e)t.d(r,o,function(n){return e[n]}.bind(null,o));return r},t.n=function(e){var n=e&&e.__esModule?function(){return e.default}:function(){return e};return t.d(n,"a",n),n},t.o=function(e,n){return Object.prototype.hasOwnProperty.call(e,n)},t.p="",t(t.s=6)}({2:function(e,n,t){"use strict";t.d(n,"c",function(){return r}),t.d(n,"d",function(){return o}),t.d(n,"a",function(){return u}),t.d(n,"b",function(){return a});
/**
 * NOTICE OF LICENSE
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @author    Mondial Relay
 * @copyright Copyright (c) Mondial Relay
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
var r=function(e){$(e||"#ajaxBox").fadeOut(function(){$(this).empty()})},o=function(e){$(e||"#ajaxBox").fadeIn()},u=function(e,n){$(n||"#ajaxBox").queue(function(){$(this).append($("#ajax_confirmation").clone().attr("id",null).removeClass("hide alert-success").addClass("alert-danger").html(e)),$(this).dequeue()})},a=function(e,n){e.confirmations.forEach(function(e){!function(e,n){$(n||"#ajaxBox").queue(function(){$(this).append($("#ajax_confirmation").clone().attr("id",null).removeClass("hide").html(e)),$(this).dequeue()})}(e,n)}),e.error.forEach(function(e){u(e,n)})}},6:function(e,n,t){"use strict";t.r(n);var r=t(2),o=MONDIALRELAY_HELP;
/**
 * NOTICE OF LICENSE
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @author    Mondial Relay
 * @copyright Copyright (c) Mondial Relay
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */$(document).on("click","#mondialrelay_check-requirements",function(){var e=this,n="#mondialrelay_requirements-results";r.c(n),e.disabled=!0,$.ajax({url:o.helpUrl,method:"POST",data:{ajax:!0,action:"checkRequirements"},dataType:"json",success:function(e){r.b(e,n),r.d(n)},error:function(e){r.a(MONDIALRELAY_MESSAGES.unknown_error,n),r.d(n)},complete:function(){e.disabled=!1}})}),$(document).on("click","#mondialrelay_register-hooks",function(){var e=this,n="#mondialrelay_requirements-results";r.c(n),e.disabled=!0,$.ajax({url:o.helpUrl,method:"POST",data:{ajax:!0,action:"registerHooks"},dataType:"json",success:function(e){r.b(e,n),r.d(n)},error:function(e){r.a(MONDIALRELAY_MESSAGES.unknown_error,n),r.d(n)},complete:function(){e.disabled=!1}})})}});