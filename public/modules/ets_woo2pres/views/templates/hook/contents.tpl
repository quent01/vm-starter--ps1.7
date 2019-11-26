{*
* 2007-2019 ETS-Soft
*
* NOTICE OF LICENSE
*
* This file is not open source! Each license that you purchased is only available for 1 wesite only.
* If you want to use this file on more websites (or projects), you need to purchase additional licenses. 
* You are not allowed to redistribute, resell, lease, license, sub-license or offer our resources to any third party.
* 
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs, please contact us for extra customization service at an affordable price
*
*  @author ETS-Soft <etssoft.jsc@gmail.com>
*  @copyright  2007-2019 ETS-Soft
*  @license    Valid for 1 website (or project) for each purchase of license
*  International Registered Trademark & Property of ETS-Soft
*}
{if $contents}
    <ul>
        {foreach from=$contents item='content'}
            {if $content.count>0 || (isset($content.count_xml) && $content.count_xml>0)}
                <li>
                    <strong>{$content.title|escape:'html':'UTF-8'}</strong>
                        {if isset($content.count_xml)}
                            {if $content.count|intval<$content.count_xml|intval}
                                {$content.count|intval}
                            {else}
                                {$content.count_xml|intval} 
                            {/if}
                        {else}
                            {$content.count|intval}
                        {/if}
                    {if isset($content.count_xml)}/{$content.count_xml|intval}{/if}
                    <span>
                        {if $content.count==0}
                            ({l s='Waiting' mod='ets_woo2pres'})
                        {else}
                            {if isset($content.count_xml) &&  $content.count|intval<$content.count_xml|intval}
                                ({l s='Processing' mod='ets_woo2pres'})
                            {else}
                                ({l s='Completed' mod='ets_woo2pres'})
                            {/if}
                        {/if}
                    </span>
                </li>
            {/if}
        {/foreach}
    </ul>
{/if}
