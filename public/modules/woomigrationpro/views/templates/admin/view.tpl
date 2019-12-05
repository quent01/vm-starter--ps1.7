{*
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from MigrationPro
 * Use, copy, modification or distribution of this source file without written
 * license agreement from the MigrationPro is strictly forbidden.
 * In order to obtain a license, please contact us: contact@migration-pro.com
 *
 * INFORMATION SUR LA LICENCE D'UTILISATION
 *
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe MigrationPro
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la MigrationPro est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter la MigrationPro a l'adresse: contact@migration-pro.com
 *
 * @author    MigrationPro
 * @copyright Copyright (c) 2012-2019 MigrationPro
 * @license   Commercial license
 * @package   MigrationPro: WooCommerce To PrestaShop
*}

{extends file="helpers/view/view.tpl"}

{block name="override_tpl"}
    <div class="row">
        <div id="step-3-clear" class="alert-success wizard_error" style="display:none">
            <ul>
                <li>{l s="Clear cache and re-build search index done."  mod='woomigrationpro'}</li>
            </ul>
        </div>

        <div id="step-3-done" class="alert-success wizard_error" style="display:none">
            <ul>
                <li>{l s="Migration successfully done."  mod='woomigrationpro'}</li>
            </ul>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12 col-sm-12 progress-container">
            <div class="panel kpi-container">
                <div class="panel-heading">
                    <i class="icon-AdminTools"></i> {l s="Migration Status"  mod='woomigrationpro'}
                </div>
                <div class="row">
                    {assign var="processCount" value={$processes|@count|escape:'htmlall':'UTF-8'}}

                    {if !$processes}
                        <h2>{l s="There is no new data on your source. =("  mod='woomigrationpro'}</h2>
                    {/if}
                    {foreach from=$processes item=process}
                        {if $processCount lt 7}
                            {math equation="x / y" x=12 y=$processCount assign="lgvalue"}
                            {math equation="x / y" x=24 y=$processCount assign="smvalue"}
                        {elseif $processCount gte 7}
                            {math equation="x / y" x=22 y=$processCount assign="lgvalue"}
                            {math equation="x / y" x=44 y=$processCount assign="smvalue"}
                        {/if}

                        {if $process.type eq "taxes"}
                            {assign var="icon" value="icon-money"}
                            {assign var="color" value="color_migp1"}
                        {elseif $process.type eq "manufacturers"}
                            {assign var="icon" value="icon-certificate"}
                            {assign var="color" value="color_migp2"}
                        {elseif $process.type eq "categories"}
                            {assign var="icon" value="icon-AdminCatalog"}
                            {assign var="color" value="color_migp3"}
                        {elseif $process.type eq "carriers"}
                            {assign var="icon" value="icon-truck"}
                            {assign var="color" value="color_migp10"}
                        {elseif $process.type eq "products"}
                            {assign var="icon" value="icon-archive"}
                            {assign var="color" value="color_migp4"}
                        {elseif $process.type eq "warehouse"}
                            {assign var="icon" value="icon-inbox"}
                            {assign var="color" value="color_migp14"}
                        {elseif $process.type eq "catalog_price_rules"}
                            {assign var="icon" value="icon-dollar"}
                            {assign var="color" value="color_migp11"}
                        {elseif $process.type eq "employees"}
                            {assign var="icon" value="icon-user"}
                            {assign var="color" value="color_migp8"}
                        {elseif $process.type eq "customers"}
                            {assign var="icon" value="icon-AdminParentCustomer"}
                            {assign var="color" value="color_migp5"}
                        {elseif $process.type eq "orders"}
                            {assign var="icon" value="icon-AdminParentOrders"}
                            {assign var="color" value="color_migp6"}
                        {elseif $process.type eq "cms"}
                            {assign var="icon" value="icon-desktop"}
                            {assign var="color" value="color_migp9"}
                        {elseif $process.type eq "seo"}
                            {assign var="icon" value="icon-search"}
                            {assign var="color" value="color_migp4"}
                        {/if}
                        <div class="col-sm-{$smvalue|string_format:"%d"|escape:'htmlall':'UTF-8'} col-lg-{$lgvalue|string_format:"%d"|escape:'htmlall':'UTF-8'}">
                            <div id="process-{$process.type|escape:'htmlall':'UTF-8'}" class="box-stats {$color|escape:'htmlall':'UTF-8'}">
                                <div class="kpi-content">
                                    <i class="{$icon|escape:'htmlall':'UTF-8'}"></i>
                                    <span class="title">{$process.type|ucfirst|escape:'htmlall':'UTF-8'}</span>
                                    <span class="subtitle">imported {$process.total|escape:'htmlall':'UTF-8'}/{$process.imported|escape:'htmlall':'UTF-8'}</span>
                                    {math equation="y / x * 100" x=$process.total y=$process.imported assign="percents"}
                                    <div class="progress-info">
                                        <div class="progress">
                                            <span style="width: {if $percents == false}0{else}{$percents|escape:'htmlall':'UTF-8'}{/if}%;"
                                                  class="progress-bar"></span>
                                        </div>
                                    </div>
                                    <span class="value">{if $percents == false}0{else}{$percents|string_format:"%d"|escape:'htmlall':'UTF-8'}{/if}%</span>
                                </div>
                            </div>
                        </div>
                    {/foreach}
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <a href="#" class="buttonTry btn btn-success" style="display: none; float: right">{l s="Resume"  mod='woomigrationpro'}</a>
        <a href="#" class="buttonClear btn btn-success" style="display: none; float: right">{l s="Clear cache and Re-Buil Index"  mod='woomigrationpro'}</a>
    </div>
{/block}
