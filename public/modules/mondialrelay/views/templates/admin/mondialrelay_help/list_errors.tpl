{*
 * NOTICE OF LICENSE
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @author    Mondial Relay
 * @copyright Copyright (c) Mondial Relay
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 *}

{if isset($title)} {$title|escape:'html':'UTF-8'} {/if}

<ul>
  {foreach $errors item=error}
    <li> {$error|escape:'html':'UTF-8'} </li>
  {/foreach}
</ul>