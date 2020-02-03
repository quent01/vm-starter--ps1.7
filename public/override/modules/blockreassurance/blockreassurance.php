<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;


class blockreassuranceOverride extends blockreassurance implements WidgetInterface{

    public function getWidgetVariables($hookName = null, array $configuration = []){
        $blocks = ReassuranceActivity::getAllBlockByStatus(
            $this->context->language->id,
            $this->context->shop->id
        );

        $elements = [];
        foreach ($blocks as $key => $value) {
            if (!empty($value['icon'])) {
                $elements[$key]['image'] = $value['icon'];
            } elseif (!empty($value['custom_icon'])) {
                $elements[$key]['image'] = $value['custom_icon'];
            } else {
                $elements[$key]['image'] = '';
            }

            $elements[$key]['text'] = $value['title'] . ' ' . $value['description'];

            $elements[$key]['title'] = $value['title'];
            $elements[$key]['description'] = $value['description'];
        }

        return [
            'elements' => $elements,
        ];
    }
}
