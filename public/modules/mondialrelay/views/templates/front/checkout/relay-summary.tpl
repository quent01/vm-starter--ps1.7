{*
 * NOTICE OF LICENSE
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @author    Mondial Relay
 * @copyright Copyright (c) Mondial Relay
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 *}

<div class="col-md-12 clearfix">

  <div class="pull-left">

    <h4>{l s='Your selected Point Relais :' mod='mondialrelay'}</h4>

    <div class="col-md-12">
      {$selectedRelay->selected_relay_adr1|escape:'htmlall':'UTF-8'} {$selectedRelay->selected_relay_adr2|escape:'htmlall':'UTF-8'}
    </div>

    <div class="col-md-12">
      {$selectedRelay->selected_relay_adr3|escape:'htmlall':'UTF-8'} {$selectedRelay->selected_relay_adr4|escape:'htmlall':'UTF-8'}
    </div>

    <div class="col-md-12">
      {$selectedRelay->selected_relay_postcode|escape:'htmlall':'UTF-8'} {$selectedRelay->selected_relay_city|escape:'htmlall':'UTF-8'}
    </div>

  </div>

  <button id="mondialrelay_change-relay" type="button" class="btn btn-primary">
    <i class='icon-pencil'></i> {l s='Change Point Relais' mod='mondialrelay'}
  </button>

</div>