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
{extends file="helpers/form/form.tpl"}
{block name="input"}
    {if $input.type == 'checkbox'}
            {if isset($input.values.query) && $input.values.query}
                {foreach $input.values.query as $value}
    				{assign var=id_checkbox value=$input.name|cat:'_'|cat:$value[$input.values.id]|escape:'html':'UTF-8'}
    				<div class="checkbox{if isset($input.expand) && strtolower($input.expand.default) == 'show'} hidden{/if}">
    					{strip}
    						<label for="{$id_checkbox|escape:'html':'UTF-8'}">                                
    							<input {if $value[$input.values.id]=='message'}disabled="disabled"{/if} type="checkbox" name="{$input.name|escape:'html':'UTF-8'}[]" id="{$id_checkbox|escape:'html':'UTF-8'}" {if isset($value[$input.values.id])} value="{$value[$input.values.id]|escape:'html':'UTF-8'}"{/if}{if isset($fields_value[$input.name]) && is_array($fields_value[$input.name]) && $fields_value[$input.name] && in_array($value[$input.values.id],$fields_value[$input.name]) || $value[$input.values.id]=='message'} checked="checked"{/if} />
    							{$value[$input.values.name]|escape:'html':'UTF-8'}
    						</label>
    					{/strip}
    				</div>
    			{/foreach} 
            {/if} 
    {else}
        {$smarty.block.parent}                    
    {/if}            
{/block}
{block name="legend"}
	<div class="panel-heading">
		{if isset($field.image) && isset($field.title)}<img src="{$field.image|escape:'html':'UTF-8'}" alt="{$field.title|escape:'html':'UTF-8'}" />{/if}
		{if isset($field.icon)}<i class="{$field.icon|escape:'html':'UTF-8'}"></i>{/if}
		{$field.title|escape:'html':'UTF-8'}
        {if isset($addNewUrl)}
            <span class="panel-heading-action">                        
                <a class="list-toolbar-btn ybc-blog-add-new" href="{$addNewUrl|escape:'html':'UTF-8'}">
                    <span data-placement="top" data-html="true" data-original-title="{l s='Add new item ' mod='ets_woo2pres'}" class="label-tooltip" data-toggle="tooltip" title="">
        				<i class="process-icon-new"></i>
                    </span>
                </a>            
            </span>
        {/if}
         {if isset($post_key) && $post_key}<input type="hidden" name="post_key" value="{$post_key|escape:'html':'UTF-8'}" />{/if}
	</div>
    {if isset($configTabs) && $configTabs}
        <ul>
            {foreach from=$configTabs item='tab' key='tabId'}
                <li class="confi_tab config_tab_{$tabId|escape:'html':'UTF-8'}" data-tab-id="{$tabId|escape:'html':'UTF-8'}">{$tab|escape:'html':'UTF-8'}</li>
            {/foreach}
        </ul>
    {/if}
{/block}
{block name="input_row"}
    {if isset($isConfigForm) && $isConfigForm}
    <div class="ybc-form-group{if isset($input.tab) && $input.tab} ybc-blog-tab-{$input.tab|escape:'html':'UTF-8'}{/if}">            
        {$smarty.block.parent}
        {if isset($input.info) && $input.info}
            <div class="ybc_tc_info alert alert-warning">{$input.info|escape:'html':'UTF-8'}</div>
        {/if}
    </div>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}