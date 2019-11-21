{*
 * NOTICE OF LICENSE
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @author    Mondial Relay
 * @copyright Copyright (c) Mondial Relay
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 *}
{extends file=$original_template}

{block name="footer" prepend}
  {* PrestaShop calls htmlentities() on the 'back' parameter, so '&' characters
  are encoded as HTML in the url... We need to decode them so they can be considered
  as parameters. *}
  {capture name='setBackUrl'}{$back_url|html_entity_decode}{/capture}
  {assign var='back_url' value=$smarty.capture.setBackUrl scope='parent'}
{/block}

{block name="input" prepend}
  {if $input.type == 'mondialrelay_relay-input'}
       
    <div id="mondialrelay_content">

      <div id="mondialrelay_errors">
        {if !$selectedRelay}
          <div class="alert alert-danger">
            {l s='This order is using a Mondial Relay carrier, but has no Point Relais selected.' mod='mondialrelay'}
          </div>
        {/if}
      </div>

      <div id="mondialrelay_widget" class="form-group col-xs-12">
      </div>

      <input id="mondialrelay_selected-relay"
        type="hidden"
        {if isset($input['name'])}
          name="{$input['name']|escape:'htmlall':'UTF-8'}"
          {if $selectedRelay}
            value="{$selectedRelay->getFullRelayIdentifier()|escape:'htmlall':'UTF-8'}"
          {/if}
        {/if}
      />

      <div id="mondialrelay_result">
        {if $selectedRelay}
          <div id="mondialrelay_summary">
              <input id="mondialrelay_displayed-relay-number" type="text" class="pull-left fixed-width-xl" value="{$selectedRelay->selected_relay_num|escape:'htmlall':'UTF-8'}" disabled/>
            <div class="col-lg-9 fixed-width-xl">
              <button id="mondialrelay_change-relay" type="button" class="btn btn-primary">
                <i class='icon-pencil'></i> {l s='Change Point Relais' mod='mondialrelay'}
              </button>
            </div>
          </div>
        {/if}
      </div>

      {* The loader appended by the JS wherever we want it. *}
      <div
        id="mondialrelay_loader-template"
        class="mondialrelay_loader"
        style="display: none"
      >
        <img src="{$module_url|escape:'htmlall':'UTF-8'}/views/img/loader.gif"/>
      </div>
    </div>
  {/if}
{/block}