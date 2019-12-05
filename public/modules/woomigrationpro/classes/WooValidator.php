<?php
/**
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
 */

class WooValidator
{
    private $fieldsForSkipping = array('nleft', 'nright', 'level_depth', 'id_shop_default');
    private $fields = array();
    private $className = '';
    private $primary = '';
    private $allowSettingDefaultValue = true;
    private $object = null;
    private $messages = array();

    public function __construct()
    {
    }

    public function setObject($object)
    {
        $className = get_class($object);
        if ($definitions = ObjectModel::getDefinition($className)) {
            $this->fields = $definitions['fields'];
            $this->primary = $definitions['primary'];
            $this->className = $className;
        }

        $this->object = $object;
    }

    public function checkFields()
    {
        $this->messages = array();
        foreach ($this->fields as $fieldName => $fieldData) {
            $this->validateField($fieldName, $fieldData, $this->object);
        }
    }

    public function allowSettingDefaultValue($allow)
    {
        $this->allowSettingDefaultValue = $allow;
    }

    public function getValidationMessages()
    {
        return $this->messages;
    }

    private function validateField($field, $fieldData, $object)
    {
        // get default language of the shop
        $defaultLangId = Configuration::get('PS_LANG_DEFAULT');

        $editedByTheModule = false;
        //is the field multilanguage
        $fieldWithLanguage = isset($fieldData['lang']);
        //is the field required
        $fieldIsRequired = isset($fieldData['required']) && $fieldData['required'] == 1;

        // check if the field is required
        if ($fieldIsRequired) {
            // if this is a multilanguage field
            if ($fieldWithLanguage) {
                //  check if default language' value is empty
                if (Tools::isEmpty($object->{$field}[$defaultLangId])) {
                    $object->{$field}[$defaultLangId] = "Default empty " . $field;
                    $editedByTheModule = true;
                    $this->messages[] = Tools::ucfirst(str_replace('_', ' ', $field)) . " of " . $this->className . " with ID " . $object->id . " is empty for shop's default language and it is not allowed in PrestaShop. " . $this->generateMessageEnd($field);
                }
            } else {
                if (Tools::isEmpty($object->{$field})) {
                    if (!empty($fieldData['default'])) {
                        $object->{$field} = $fieldData['default'];
                    } else {
                        if (Tools::strtolower($fieldData['validate']) == 'ispasswd') {
                            $object->{$field} = md5(time() . _PS_ADMIN_DIR_);
                        } else {
                            $object->{$field} = "Default empty " . $field;
                        }
                        $editedByTheModule = true;
                        $this->messages[] = Tools::ucfirst(str_replace('_', ' ', $field)) . " of " . $this->className . " with ID " . $object->id . " is empty and it is not allowed in PrestaShop. " . $this->generateMessageEnd($field);
                    }
                }
            }
        }

        // if field has size limit then check is
        if (isset($fieldData['size'])) {
            $size = array('min' => 0, 'max' => $fieldData['size']);
            //When parameter is array Tools::strlen($sting)  return false
            if (is_array($object->{$field})) {
                foreach ($object->{$field} as $key => $value) {
                    $length = Tools::strlen($value);
                    if ($length < $size['min'] || $length > $size['max']) {
                        $object->{$field}[$key] = Tools::substr($value, $size['min'], $size['max']);

                        $editedByTheModule = true;
                        $this->messages[] = Tools::ucfirst(str_replace('_', ' ', $field)) . " of " . $this->className . " with ID " . $object->id . " is longer than allowed in PrestaShop. " . $this->generateMessageEnd($field);
                    }
                }
            } else {
                $length = Tools::strlen($object->{$field});
                if ($length < $size['min'] || $length > $size['max']) {
                    $object->{$field} = Tools::substr($object->{$field}, $size['min'], $size['max']);

                    $editedByTheModule = true;
                    $this->messages[] = Tools::ucfirst(str_replace('_', ' ', $field)) . " of " . $this->className . " with ID " . $object->id . " is longer than allowed in PrestaShop. " . $this->generateMessageEnd($field);
                }
            }
        }

        // if this field must not be skipped and must be validated then check it
        if (!in_array($field, $this->fieldsForSkipping) && isset($fieldData['validate'])) {
            // if Validate class has not such validate method then throw an standart PS exception
            if (!method_exists('Validate', $fieldData['validate'])) {
                throw new PrestaShopException('Validation function not found: ' . $fieldData['validate']);
            }

//            if (!empty($object->{$field})) {
            $res = true;
            $isCleanHtmlValidationRule = false;
            $invalidFieldLanguageIds = array();
            // if this field must be a html then check with this method and set $isCleanHtmlValidationRule to true for not to assign all html text to messages
            if (Tools::strtolower($fieldData['validate']) == 'iscleanhtml') {
                $isCleanHtmlValidationRule = true;
                if (!empty($object->{$field}) && !is_array($object->{$field}) && !call_user_func(array('Validate', $fieldData['validate']), $object->{$field}, (int)Configuration::get('PS_ALLOW_HTML_IFRAME'))) {
                    $res = false;
                }
            } else {
                // if this is multilanguage field
                if ($fieldWithLanguage) {
                    if (empty($object->{$field}) || !is_array($object->{$field})) {
                        return;
                    }
                    // loop field's all languages and check its value
                    foreach ($object->{$field} as $langId => $value) {
                        if (!empty($value) && !call_user_func(array('Validate', $fieldData['validate']), $value)) {
                            // if value is not valid in some language then add this language's ID to invalid languages array
                            $res = false;
                            $invalidFieldLanguageIds[] = $langId;
                        }
                    }
                } else {
                    if (!empty($object->{$field}) && !call_user_func(array('Validate', $fieldData['validate']), $object->{$field})) {
                        $res = false;
                    }
                }
            }

            $currentInvalidValue = '';

            // if there is a validation error then handle it and add to messages
            if (!$res) {
                // if invalid value is not a html text or value is not longer than 60 (length can be changed) then add value to message
                if (!$isCleanHtmlValidationRule || (Tools::strlen($object->{$field}) < 60)) {
                    if ($fieldWithLanguage) {
                        $currentInvalidValue = "(language ID(s) - " . implode(',', $invalidFieldLanguageIds) . ")";
                    } else {
                        $currentInvalidValue = "('" . $object->{$field} . "')";
                    }
                }

                // check and set default value to the field
                if (Tools::strtolower($fieldData['validate']) == 'isemail') {
                    $object->{$field} = "email" . time() . rand(1, 999) . "@default.com";
                } elseif (Tools::strtolower($fieldData['validate']) == 'ispasswd') {
                    $object->{$field} = md5(time() . _PS_ADMIN_DIR_);
                } elseif (Tools::strtolower($fieldData['validate']) == 'isdate') {
                    $object->{$field} = date('Y-m-d H:i:s');
                } else {
                    // if the field is multilanguage then set default value languages to each
                    if ($fieldWithLanguage) {
                        foreach ($invalidFieldLanguageIds as $langId) {
                            $object->{$field}[$langId] = "Default value";
                            if (!call_user_func(array('Validate', $fieldData['validate']), $object->{$field}[$langId])) {
                                $object->{$field} = 0;
                            }
                        }
                    } else {
                        $object->{$field} = "Default value";
                        if (!call_user_func(array('Validate', $fieldData['validate']), $object->{$field})) {
                            $object->{$field} = 0;
                        }
                    }
                }
                if (!$editedByTheModule) {
                    $this->messages[] = Tools::ucfirst(str_replace('_', ' ', $field)) . " of " . $this->className . " with ID " . $object->id . " is not a valid value in PrestaShop " . $currentInvalidValue . ". " . $this->generateMessageEnd($field);
                }
            }
        }
    }

    private function generateMessageEnd($field)
    {
        if ($this->allowSettingDefaultValue) {
            return "For that reason, the module set default value to '" . $field . "'.";
        }

        return "For that reason, " . $this->className . " could not be saved.";
    }
}
