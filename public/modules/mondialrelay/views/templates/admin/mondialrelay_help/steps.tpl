{*
 * NOTICE OF LICENSE
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @author    Mondial Relay
 * @copyright Copyright (c) Mondial Relay
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 *}

<ul>
    <li>
        {l s='Enter and register your Mondial Relay webservice parameters on the page [1] Account Settings [/1]' tags=['<a href="%s" target="_blank">'|replace:'%s':$account_settings_link] mod='mondialrelay'}
    </li>
    <li>
        {l s='If needed you can modify the way to display the Points Relais via the page [1] Advanced parameters [/1]' tags=['<a href="%s" target="_blank">'|replace:'%s':$advanced_settings_link] mod='mondialrelay'}
    </li>
    <li>
        {l s='You can find the elements to set up the CRON task from your hosting provider to update the status "delivered" of your orders via the page [1] advanced parameters [/1]' tags=['<a href="%s" target="_blank">'|replace:'%s':$advanced_settings_link] mod='mondialrelay'}
    </li>
    <li>
        {l s='Create a new carrier via the page [1] Carrier Settings page [/1]' tags=['<a href="%s" target="_blank">'|replace:'%s':$mondialrelay_carrier_settings_link] mod='mondialrelay'}
    </li>
    <li>
        {l s='Define the additional parameters from your carrier like shipping fees, weight categories, shipping countries, max. weight, … via the page [1] Shipping > Carriers [/1]' tags=['<a href="%s" target="_blank">'|replace:'%s':$prestashop_carrier_settings_link] mod='mondialrelay'}
    </li>
    <li>
        {l s='Enter the name, address, phone number of your shop via the page [1] Contact / Shops [/1]' tags=['<a href="%s" target="_blank">'|replace:'%s':$store_contact_link] mod='mondialrelay'}
    </li>
    <li>
        {l s='Create and print your labels to ship your orders' mod='mondialrelay'}
    </li>
</ul>