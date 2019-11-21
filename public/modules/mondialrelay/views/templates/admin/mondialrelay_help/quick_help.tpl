{*
 * NOTICE OF LICENSE
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @author    Mondial Relay
 * @copyright Copyright (c) Mondial Relay
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 *}

<div class="panel">
  <div class="panel-heading"> {l s='Quick help' mod='mondialrelay'} </div>
 
  <p> {l s='Check requirements before configuring the module :' mod='mondialrelay'} </p>
  
  <ul>
    <li>
      {l s='Mondial Relay needs SOAP & cURL to be installed on your server' mod='mondialrelay'}
    </li>
    <li>
      {l s=' For labels generation you should have a valid and registered address of your store on your [1] contact page [/1]' tags=['<a href="%s" target="_blank">'|replace:'%s':$store_contact_link] mod='mondialrelay'}
    </li>
    <li>
      {l s='The module needs to install some hooks as well' mod='mondialrelay'}
    </li>
  </ul>
  
  <br/>
  
  <p> {l s='Click on the button below and check if all requirements are completed !' mod='mondialrelay'} </p>
  <p>
    <button
      type="button"
      id="mondialrelay_check-requirements"
      class="btn btn-primary">
      {l s='Check requirements' mod='mondialrelay'}
    </button>
  </p>
  <div id="mondialrelay_requirements-results">

  </div>
  
  <br/>
  
  <p>
    {l s='Any problems with the module after an update ? [1] Try to clear the cache and put the force compilation to on.' tags=['<br/>'] mod='mondialrelay'}
  </p>
  <p>
    <a
      href="{$prestashop_performance_url|escape:'html':'UTF-8'}"
      target="_blank"
      class="btn btn-primary">
      {l s='Clear PrestaShop cache' mod='mondialrelay'}
    </a>
  </p>
  
  <br/>
  
  <p>
    {l s='Any problems with orders status update ? [1] Check Mondial Relay Logs :' tags=['<br/>'] mod='mondialrelay'}
  </p>
  <p>
    <a
      href="{$logs_link|escape:'html':'UTF-8'}"
      target="_blank"
      class="btn btn-primary">
      {l s='Open logs' mod='mondialrelay'}
    </a>
  </p>
  
</div>