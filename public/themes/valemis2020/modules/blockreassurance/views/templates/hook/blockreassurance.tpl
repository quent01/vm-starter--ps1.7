{**
 * 2007-2017 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2017 PrestaShop SA
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 *}
{if $elements}
    <div class="container">
        <section class="section section--pt0.5 section--pb0.5 blockreassurance">
            <div class="container-fluid">
                <div class="row">
                    {foreach from=$elements item=element}
                        <div class="blockreassurance__item">
                            <div class="d-flex align-items-center">
                                <div class="blockreassurance__img">
                                    <img src="{$element.image}" alt="{$element.text}">
                                </div>
                                <div class="blockreassurance__txt">
                                    <h2 class="blockreassurance__title">{$element.title}</h2>
                                    <div>
                                        {$element.description}
                                    </div>
                                </div>
                            </div>
                        </div>
                    {/foreach}
                </div>
            </div>    
        </section>
    </div>
{/if}
