<?php
/**
 * @author qthenoz@tiz.fr
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

class Tiz_Database extends Module{
    public function __construct()
    {
        $this->name = 'tiz_database';
        $this->tab = '';
        $this->version = '1.0.0';
        $this->author = 'Tiz';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Tiz Database: Permit to do some preconfigured database operations via PHP Cli');
        $this->description = $this->l(
            'Permit to do some preconfigured database operations via PHP Cli (dbresetwoomigration, dbclean, dbrepair, dboptimize, ...)'
        );
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
    }
}