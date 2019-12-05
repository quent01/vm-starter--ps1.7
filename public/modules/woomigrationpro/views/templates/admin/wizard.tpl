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
    <script>
        var warningLogPath = '{$warningLogPath|addslashes|escape:'javascript':'UTF-8'}';
        var downloadLogText = '{$downloadLogText|addslashes|escape:'javascript':'UTF-8'}';
        var labelNext = '{$labels.next|addslashes|escape:'javascript':'UTF-8'}';
        var labelPrevious = '{$labels.previous|addslashes|escape:'javascript':'UTF-8'}';
        var labelFinish = '{$labels.finish|addslashes|escape:'javascript':'UTF-8'}';
        var validate_url = '{$validate_url|addslashes|escape:'javascript':'UTF-8'}';
        var nbr_steps = {$wizard_steps.steps|count};
        var enableAllSteps = false;
        {*var multistore_enable = '{$multistore_enable}';*}
    </script>
    <div class="row">
        <div class="col-sm-12">
            <div id="migrationpro_wizard" class="panel swMain">
                <ul class="steps nbr_steps_{$wizard_steps.steps|count}">
                    {foreach from=$wizard_steps.steps key=step_nbr item=step}
                        <li>
                            <a href="#step-{$step_nbr|escape:'html':'UTF-8' + 1}">
                                <span class="stepNumber">{$step_nbr|escape:'html':'UTF-8' + 1}</span>
                                <span class="stepDesc">
							{$step.title|escape:'html':'UTF-8'}<br/>
                                    {if isset($step.desc)}
                                        <small>{$step.desc}</small>{/if}
						</span>
                                <span class="chevron"></span>
                            </a>
                        </li>
                    {/foreach}
                </ul>
                {foreach from=$wizard_contents.contents key=step_nbr item=content}
                    <div id="step-{$step_nbr|escape:'html':'UTF-8' + 1}" class="step_container">
                        {if !empty($processes)}
                            <div class="row">
                                {*<div class="progress active progress-striped" style="display: block; width: 100%">
                                    <div class="progress-bar progress-bar-success" role="progressbar"
                                         style="width: {$percent|escape:'html':'UTF-8'}%"
                                         id="import_progressbar_done">
                                        <span><span id="import_progression_done">{$percent|escape:'html':'UTF-8'}</span>% Imported</span>
                                    </div>
                                </div>*}

                                <div class="col-xs-12 col-sm-12 progress-containerw">
                                    <div class="panel kpi-container">
                                        <div class="panel-heading">
                                            <i class="icon-AdminTools"></i><i class="icon-AdminTools"></i> {l s="Migration Status: Migrated {$percent|escape:'html':'UTF-8'}% of data."  mod='woomigrationpro'}
                                        </div>
                                        <div class="row">
                                            {assign var="processCount" value={$processes|@count|escape:'htmlall':'UTF-8'}}

                                            {foreach from=$processes item=process}
                                                {math equation="x / y" x=12 y=$processCount assign="lgvalue"}
                                                {math equation="x / y" x=24 y=$processCount assign="smvalue"}

                                                {if $process.type eq "taxes"}
                                                    {assign var="icon" value="icon-money"}
                                                    {assign var="color" value="color_migp1"}
                                                {elseif $process.type eq "manufacturers"}
                                                    {assign var="icon" value="icon-certificate"}
                                                    {assign var="color" value="color_migp2"}
                                                {elseif $process.type eq "categories"}
                                                    {assign var="icon" value="icon-AdminCatalog"}
                                                    {assign var="color" value="color_migp3"}
                                                {elseif $process.type eq "products"}
                                                    {assign var="icon" value="icon-archive"}
                                                    {assign var="color" value="color_migp4"}
                                                {elseif $process.type eq "customers"}
                                                    {assign var="icon" value="icon-AdminParentCustomer"}
                                                    {assign var="color" value="color_migp5"}
                                                {elseif $process.type eq "orders"}
                                                    {assign var="icon" value="icon-AdminParentOrders"}
                                                    {assign var="color" value="color_migp6"}
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
                                            <div class="col-lg-12">
                                                <a href="#" class="buttonResume btn btn-success">Resume</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        {/if}
                        <br>
                        {$content}{*PrestaShop Team: main content - if escape DOM get errors*}
                    </div>
                {/foreach}
            </div>
        </div>
    </div>
{/block}
