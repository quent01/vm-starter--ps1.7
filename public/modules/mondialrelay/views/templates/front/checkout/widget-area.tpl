{*
 * NOTICE OF LICENSE
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @author    Mondial Relay
 * @copyright Copyright (c) Mondial Relay
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 *}
 
<div id="mondialrelay_content">
  {* Add JS def, the PS way. We're not using the global scope, so we'll only
   * include what we manually specified.
   *}
  {include file=$js_inclusion_template}
  
  <div id="mondialrelay_widget">
    {if !$isModuleConfigured}
      <div class="alert alert-danger">{l s='This carrier has not been configured yet; please contact the merchant.' mod='mondialrelay'}</div>
    {/if}
  </div>
  
  <div id="mondialrelay_save-container" style="display: none">
    
    <input id="mondialrelay_selected-relay"
      name="mondialrelay_selectedRelay"
      type="hidden"
      />
    
    <button id="mondialrelay_save-relay" type="button" class="btn btn-primary">
      {l s='Use this Point Relais' mod='mondialrelay'}
    </button>
  
  </div>
  
  <div id="mondialrelay_result">
    <div id="mondialrelay_errors" style="display: none"></div>
    <div id="mondialrelay_summary" style="display: none">
      {if $selectedRelay}
        {include file='./relay-summary.tpl'}
      {/if}
    </div>
  </div>
    
  {* The loader appended by the JS wherever we want it. *}
  <div
    id="mondialrelay_loader-template"
    class="mondialrelay_loader"
    style="display: none"
  >
    <img src="{$module_url|escape:'htmlall':'UTF-8'}/views/img/loader.gif"/>
  </div>

  {literal}
  <script type="text/javascript">
    if (typeof $ != 'undefined') {
      $(document).ready(function() {
        $("#mondialrelay_content").trigger("mondialrelay.contentRefreshed", {'fromAjax': {/literal}{if $fromAjax}true{else}false{/if}{literal}});
      });
    }
  {/literal}
  </script>
</div>