{*
 * NOTICE OF LICENSE
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @author    Mondial Relay
 * @copyright Copyright (c) Mondial Relay
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 *}

 {* WARNING : this file is mostly copied from the 'layout-ajax.tpl' from the
 admin  theme. Strings are not escaped, as every variable is supposed to be 
 encoded as JSON. Escaping them may result in malformed JSON. *}
 
{if isset($json)}
{strip}
{
{if isset($status) && is_string($status) && trim($status) != ''}{assign 'hasresult' 'ok'}"status" : "{$status}"{/if}
{if !empty($confirmations)}{if $hasresult == 'ok'},{/if}{assign 'hasresult' 'ok'}"confirmations" : {$confirmations}{/if}
{if !empty($informations)}{if $hasresult == 'ok'},{/if}{assign 'hasresult' 'ok'}"informations" : {$informations}{/if}
{if !empty($errors)}{if $hasresult == 'ok'},{/if}{assign 'hasresult' 'ok'}"error" : {$errors}{/if}
{if !empty($warnings)}{if $hasresult == 'ok'},{/if}{assign 'hasresult' 'ok'}"warnings" : {$warnings}{/if}
{if $hasresult == 'ok'},{/if}{assign 'hasresult' 'ok'}"content" : {$page}
}
{/strip}
{else}
  {include 'layout-ajax.tpl'}
{/if}