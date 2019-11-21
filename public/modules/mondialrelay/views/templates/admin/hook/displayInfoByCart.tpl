{*
 * NOTICE OF LICENSE
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @author    Mondial Relay
 * @copyright Copyright (c) Mondial Relay
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 *}

<div class="row">
    <div class="pull-left">

        {if isset($needRelay) && $needRelay}
            {if $selectedRelay->selected_relay_num}
                {l s='Relay number :' mod='mondialrelay'} {$selectedRelay->selected_relay_num|escape:'htmlall':'UTF-8'} <br/>

                {$selectedRelay->selected_relay_adr1|escape:'htmlall':'UTF-8'} <br/>

                {if !empty($selectedRelay->selected_relay_adr2)}
                    {$selectedRelay->selected_relay_adr2|escape:'htmlall':'UTF-8'} <br/>
                {/if}

                {$selectedRelay->selected_relay_adr3|escape:'htmlall':'UTF-8'} <br/>

                {if !empty($selectedRelay->selected_relay_adr4)}
                    {$selectedRelay->selected_relay_adr4|escape:'htmlall':'UTF-8'} <br/>
                {/if}

                {$selectedRelay->selected_relay_postcode|escape:'htmlall':'UTF-8'} {$selectedRelay->selected_relay_city|escape:'htmlall':'UTF-8'} <br/>

                {$selectedRelay->selected_relay_country_iso|escape:'htmlall':'UTF-8'}
            {else}
                <div class="alert alert-danger">
                    {l s='This order is using a Mondial Relay carrier, but has no relay selected.' mod='mondialrelay'}
                </div>
            {/if}
        {/if}
        {if !empty($selectedRelay->label_url)}
            <br/>
            <a href="{$selectedRelay->label_url|escape:'htmlall':'UTF-8'}" class="label-tooltip" data-toggle="tooltip" data-original-title="{l s='If you want to generate labels in A5 or 10*15 format, please go to the « Labels History » tab.' mod='mondialrelay'}" data-placement="right" title="">{l s='Download Mondial Relay label' mod='mondialrelay'}</a>
        {/if}
    </div>

    {if isset($updateRelaySelection_url)}
        <a href="{$updateRelaySelection_url|escape:'htmlall':'UTF-8'}" class="btn btn-default"><i class="icon-refresh"></i> {l s='Change' mod='mondialrelay'}</a>
    {/if}
</div>
