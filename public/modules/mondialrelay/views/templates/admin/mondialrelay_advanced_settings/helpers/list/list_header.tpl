{*
 * NOTICE OF LICENSE
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @author    Mondial Relay
 * @copyright Copyright (c) Mondial Relay
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 *}
{extends file=$original_header}

{block preTable}
  {if $list_id == 'mondialrelay_cron-tasks'}
    <div class="alert alert-info">
      {l s='You should create a Cron task on your server for updating  order status when the order is shipped.' mod='mondialrelay'} <br/>
      {l s='The CRON task is used for setting the frequency of calls to our tracing service in order to see the parcels with "delivered" status. We recommend to set a 6h call frequency.' mod='mondialrelay'}
    </div>
  {/if}
{/block}