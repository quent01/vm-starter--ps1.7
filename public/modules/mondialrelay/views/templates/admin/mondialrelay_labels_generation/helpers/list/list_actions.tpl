{*
 * NOTICE OF LICENSE
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @author    Mondial Relay
 * @copyright Copyright (c) Mondial Relay
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 *}
<a href="{$href_generate|escape:'html':'UTF-8'}" title="{$action|escape:'html':'UTF-8'}" class="btn btn-default">
  <i class="mondialrelay_icon-generate"><img src="{$module_path|escape:'html':'UTF-8'}views/img/icon-pdf.png"/></i>
</a>
<a href="{$href_edit|escape:'html':'UTF-8'}"
   data-toggle="tooltip"
   data-placement="bottom"
   title="{l s='You can change or add a Point Relais, weight and insurance.' mod='mondialrelay'}"
   class="btn btn-default"><i class="icon-pencil"></i></a>