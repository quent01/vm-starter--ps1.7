{*
 * NOTICE OF LICENSE
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @author    Mondial Relay
 * @copyright Copyright (c) Mondial Relay
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 *}

 {* Note : This file was copied to views/templates/admin/mondialrelay_advanced_settings/content.tpl *}
{block name="mondialrelay_content-prepend"}
  {if $with_mondialrelay_header}
    <div id="mondialrelay_header" class="panel clearfix">
      <div>
        <img src="{$module_path|escape:'htmlall':'UTF-8'}views/img/logo_hd.png" class="pull-left mondialrelay_logo"/>
      </div>
      <div>
        <p>
        {l s='For more than 20 years, Mondial Relay has been the specialist in parcel delivery thanks to the broadest European e-commerce delivery network, one of the leading retailers of e-commerce parcels in France, Europe and more 40,000 e-merchants, with parcel delivery solutions, in France and Europe, Points Relais (pickup point), Drive and Home.' mod='mondialrelay'} <br/>
        </p>

        <div class="hidden-xs">
          <p>
            {l s='Give your customers a cheap, easy, safe and convenient delivery offer :' mod='mondialrelay'} <br/>
            {l s='[1] Cheap [/1] : Low prices from 3.79Eur (excl. Tax) !' mod='mondialrelay' tags=['<strong>']} <br/>
            {l s='[1] Easy [/1] : Sending or receiving a parcel has never been easier with all the solutions offered by Mondial Relay. Point Relais (pickup point) is getting more and more popular !' mod='mondialrelay' tags=['<strong>']} <br/>
            {l s='[1] Safe and practical [/1] : Mondial Relay takes care of your parcels. With a reliable tracing system customers can track their package and pick them up at one of our Points Relais or at home (excl. France).' mod='mondialrelay' tags=['<strong>']}
          </p>

          <p>
            {l s='Mondial Relay is evaluated every day by hundreds of customers through NetReviews and has a rating of 4.4/5.' mod='mondialrelay'} <br/>
            <a href="https://www.mondialrelay.fr/solutionspro/decouvrez-votre-offre/" target="_blank">{l s=' Discover Mondial Relay offers' mod='mondialrelay'}</a>
          </p>
        </div>

        <div class="visible-xs">
          <p>
            {l s='Give your customers a cheap, easy, safe and convenient delivery offer.' mod='mondialrelay'}
          </p>

          <p>
            <a href="https://www.mondialrelay.fr/solutionspro/decouvrez-votre-offre/" target="_blank">{l s=' Discover Mondial Relay offers' mod='mondialrelay'}</a>
          </p>
        </div>
      </div>
      
    </div>
  {/if}
{/block}

{if $with_mondialrelay_header}
  {block name="help_guide"}
    {if $help_link != 'AdminMondialrelayHelp'}
    <div class="alert alert-info">
      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
      {include '../mondialrelay_help/steps.tpl'}
    </div>
    {/if}
  {/block}
{/if}

{block name="mondialrelay_content-body"}
  {include 'content.tpl'}
{/block}

{block name="mondialrelay_content-append"}
{/block}