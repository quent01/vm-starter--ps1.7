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


require_once('../../../wp-blog-header.php');

define('MP_TOKEN', "[[[[[[sample_token]]]]]]]");

error_reporting(1);

if (!isset($_SERVER)) {
    $_GET = &$HTTP_GET_VARS;
    $_POST = &$HTTP_POST_VARS;
    $_ENV = &$HTTP_ENV_VARS;
    $_SERVER = &$HTTP_SERVER_VARS;
    $_COOKIE = &$HTTP_COOKIE_VARS;
    $_REQUEST = array_merge($_GET, $_POST, $_COOKIE);
}


define('MPROOT_BASE_NAME', basename(getcwd()));
define('MPCONNECTOR_BASE_DIR', dirname(__FILE__));
define('MPSTORE_BASE_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR);

class MPServer
{

    var $action = null;
    var $adapter = null;
    var $response = null;

    function __construct()
    {
        $this->action = $this->_getAction();
        $this->adapter = $this->_getAdapter();
        $this->response = $this->_getResponse();
    }

    function run()
    {
        if (empty($_GET)) {
            echo 'Connector File ready to work';
            return;
        }
        if (!$this->_checkToken()) {
            $this->response->error('The connector module generated for [[[[[[old_domain]]]]]]]! Please, re-download connector module and install to the WooCommerce and try connection again.', null);
            return;
        }

        $this->action->setConnector($this);
        $this->action->run();
    }

    function _getAdapter()
    {
        $adapter = new MPServerAdapter();
        return $adapter;
    }

    function _getResponse()
    {
        $response = new MPServerResponse();
        return $response;
    }

    function _getAction()
    {
        $action = new MPServerAction();
        return $action;
    }

    function _checkToken()
    {
        if (isset($_GET['token']) && $_GET['token'] == MP_TOKEN) {
            return true;
        } else {
            return false;
        }
    }

}

class MPServerAction
{

    var $type = null;
    var $connector = null;

    function __construct()
    {

    }

    function setConnector($connector)
    {
        $this->connector = $connector;
    }

    function _getActionType($action_type)
    {
        $action = null;
        $action_type = strtolower($action_type);
        $class_name = __CLASS__ . ucfirst($action_type);
        if (class_exists($class_name)) {
            $action = new $class_name();
        }
        return $action;
    }

    function run()
    {
        if (isset($_GET['action']) && $action = $this->_getActionType($_GET['action'])) {
            $action->setConnector($this->connector);
            $action->run();
        } else {
            $response = $this->connector->response;
            $response->createResponse('error', 'Action not found !', null);
            return;
        }
    }

    function _getResponse()
    {
        return $this->connector->response;
    }

    function _getAdapter()
    {
        return $this->connector->adapter;
    }

    function _getCart()
    {
        $adapter = $this->_getAdapter();
        $cart = $adapter->getCart();
        return $cart;
    }

}

class MPServerActionCheck extends MPServerAction
{

    function __construct()
    {
        parent::__construct();
    }

    function run()
    {
        $response = $this->_getResponse();
        $adapter = $this->_getAdapter();
        $cart = $this->_getCart();
        $obj['cms'] = $adapter->detectCartType();
        if ($cart) {
            $obj['woo_version'] = WC()->version;
            $obj['image_category'] = $cart->imageDirCategory;
            $obj['image_product'] = $cart->imageDirProduct;
            $obj['image_manufacturer'] = $cart->imageDirManufacturer;
            $obj['image_supplier'] = $cart->imageDirSupplier;
            $obj['table_prefix'] = $cart->tablePrefix;
            $obj['version'] = $cart->version;
            $obj['charset'] = $cart->char_set;
            $obj['blowfish_key'] = $cart->blowfish_key;
            $obj['cookie_key'] = $cart->cookie_key;
            $obj['wpml_is_active'] = function_exists('icl_object_id');
            $connect = $cart->connect();
            if ($connect && $char_set = $this->_checkDatabaseExist($connect)) {
                if ($obj['charset'] == '') {
                    $obj['charset'] = $char_set;
                }
                $obj['connect'] = array(
                    'result' => 'success',
                    'msg' => 'Successful connect to database !'
                );
            } else {
                $obj['connect'] = array(
                    'result' => 'error',
                    'msg' => 'Not connect to database !'
                );
            }
        }
        $response->success('Successful check CMS !', $obj);
        return;
    }

    function _checkDatabaseExist($connect)
    {
        $query = "SHOW VARIABLES LIKE \"ch%\"";
        $rows = array();
        $char = null;
        if (version_compare(PHP_VERSION, '7.0.0', '>=')) {
            $result = @mysqli_query($connect, $query);
            while ($row = @mysqli_fetch_array($result)) {
                $rows[] = $row;
            }
        } else {
            $result = @mysql_query($query, $connect);
            while ($row = @mysql_fetch_array($result)) {
                $rows[] = $row;
            }
        }

        foreach ($rows as $row) {
            if ($row['Variable_name'] == 'character_set_database') {
                $char_set = $row['Value'];
            }
            if (strpos($row['Value'], 'utf8') !== false) {
                $char = 'utf8';
                break;
            }
        }
        if (!$char) {
            $char = $char_set;
        }
        return $char;
    }

}


class MPServerActionFile extends MPServerAction
{

    function __construct()
    {
        parent::__construct();
    }

    function run()
    {
        $obj = array();
        $response = $this->_getResponse();

        $attachmentFileName = base64_decode($_REQUEST['query']);
        if (is_string($attachmentFileName)) {
            $path = $attachmentFileName;
            if (file_exists($path)) {
                $content = @file_get_contents($path);
                $obj[] = $content;
            }
        }
        $response->success(null, $obj);
        return;
    }

}

class MPServerActionQuery extends MPServerAction
{

    private $images = array();
    private $imageIds = '';

    function __construct()
    {
        parent::__construct();
    }

    /**
     *
     */
    function run()
    {
        $obj = array();

        $response = $this->_getResponse();
        $cart = $this->_getCart();
        if ($cart) {
            $connect = $cart->connect();
            if ($connect && isset($_REQUEST['query'])) {
                if (isset($_REQUEST['char_set'])) {
                    if (version_compare(PHP_VERSION, '7.0.0', '>=')) {
                        @mysqli_query($connect, "SET NAMES  utf8");
                        @mysqli_query($connect, "SET CHARACTER SET utf8");
                        @mysqli_query($connect, "SET CHARACTER_SET_CONNECTION=utf8");
                    } else {
                        @mysql_query("SET NAMES  utf8", $connect);
                        @mysql_query("SET CHARACTER SET utf8", $connect);
                        @mysql_query("SET CHARACTER_SET_CONNECTION=utf8", $connect);
                    }

                }

                $query = base64_decode($_REQUEST['query']);

                if (isset($_REQUEST['serialize']) && $_REQUEST['serialize']) {
                    $query = unserialize($query);
                    //  print_r( $query);
                    foreach ($query as $key => $string) {


                        //custom changes
                        if ($key == 'languages' && !function_exists('icl_object_id')) {
                            $obj[$key] = array(array('source_id' => 1, 'source_name' => get_locale()));
                        } else if ($key == 'order_states') {
                            $result = array();
                            $count = 1;
                            foreach (wc_get_order_statuses() as $statusineng => $status) {
                                //$key as defult in english with  but $status can be in any lang
                                $result[] = array('source_id' => $count, 'source_name' => $statusineng);
                                $count++;
                            }
                            $obj[$key] = $result;

                        } else {

                            if (isset($query['groupedqueriesconfiguration']) && $key === 'groupedqueriesconfiguration') {
                                continue;
                            }

                            if (isset($query['groupedqueriesconfiguration'][$key])) {
                                $obj[$key] = $this->_getData($string, $connect,
                                    isset($query['groupedqueriesconfiguration'][$key]),
                                    isset($query['groupedqueriesconfiguration'][$key]) ? $query['groupedqueriesconfiguration'][$key] : null, $key);
                            } else {
                                $obj[$key] = $this->_getData($string, $connect, false, null, null);
                            }
                        }
                    }
                } else {
                    $obj = $this->_getData($string, $connect, false, null, null);
                }

                $response->success(null, $obj);

                return;
            } else {
                $response->error('Can\'t connect to database or not run query !', null);
                return;
            }
        } else {
            $response->error('CMS Cart not found !', null);
            return;
        }
    }


    function _getData($query, $connect, $IsGrouped = false, $GroupKey = null, $key = null)
    {
        $rows = array();
        $oldIdProduct = -1;
        $productGroupedItems = array();
        $IsHaveResult = false;
        $id_product_attribute = 0;
        $product_attribute = array();
        $n = 0;
        $count = 0;
        if ($key === 'product_imgs') {
            $query = $query . ' and p.ID IN (' . $this->imageIds . ')';
            //   echo   $query; die;
        }
        if (version_compare(PHP_VERSION, '7.0.0', '>=')) {
            $res = @mysqli_query($connect, $query);
            while ($row = @mysqli_fetch_assoc($res)) {
                $count++;
            }
            $res = @mysqli_query($connect, $query);
            while ($row = @mysqli_fetch_assoc($res)) {

                if ($IsGrouped) {
                    if ($key === "addresses") {
                        $rows[] = $row;
                    } else {

                        if ($key === "product_variation") {
                            if ($id_product_attribute > 0 && $id_product_attribute != $row['id_product_attribute']) {
                                $productGroupedItems[] = $product_attribute;
                                $product_attribute = array();
                            }
                        }

                        if ($oldIdProduct > 0 && $oldIdProduct != $row[$GroupKey]) {
                            $rows[$oldIdProduct] = $productGroupedItems;
                            $productGroupedItems = array();
                        }

                        if ($key === 'product_imgs') {
                            $postId = $row['post_id'];
                            $row['post_id'] = $this->image[$postId];
                            $rows['product_imgs_all'][] = $row;
                            $productGroupedItems[] = $row;
                            $oldIdProduct = $this->image[$postId];
                        } else if ($key === 'product_imgage_ids') {
                            $images = explode(',', $row['meta_value']);
                            if (count($images) > 0) {
                                foreach ($images as $imgage) {
                                    $this->image[$imgage] = $row['post_id'];
                                    if ($this->imageIds === '') {
                                        $this->imageIds = $imgage;
                                    } else {
                                        $this->imageIds = $this->imageIds . ',' . $imgage;
                                    }
                                }
                            }
                        } else if ($key === "product_meta") {
                            $productGroupedItems[$row['meta_key']] = $row['meta_value'];
                            $oldIdProduct = $row[$GroupKey];
                        } else if ($key === "product_variation") {
                            $n++;
                            $id_product_attribute = $product_attribute['id_product_attribute'] = $row['id_product_attribute'];
                            $product_attribute[$row['meta_key']] = $row['meta_value'];
                            $oldIdProduct = $row[$GroupKey];
                            if ($n == $count) {
                                $productGroupedItems[] = $product_attribute;
                                $rows[$oldIdProduct] = $productGroupedItems;
                                $productGroupedItems = array();
                            }

                        } else if ($key === "product_lang") {
                            $productGroupedItems[] = $row;
                            $oldIdProduct = $row[$GroupKey];

                        } else if ($key === 'product_langs_meta') {
                            $productGroupedItems[$row['id_lang']][] = $row;
                            $oldIdProduct = $row[$GroupKey];
                        } else {
                            $productGroupedItems[] = $row;
                            $oldIdProduct = $row[$GroupKey];
                        }
                    }
                } else {
                    $rows[] = $row;
                }
                if (!$IsHaveResult) {
                    $IsHaveResult = true;
                }

            }

            if ($key !== "addresses") {

                if ($IsGrouped) {

                    if ($IsHaveResult && !isset($rows[$oldIdProduct])) {

                        $rows[$oldIdProduct] = $productGroupedItems;
                        $productGroupedItems = array();

                    }
                }
            }
        } else {
            $res = @mysql_query($query, $connect);
            while ($row = @mysql_fetch_array($res, MYSQL_ASSOC)) {
                $count++;
            }
            $res = @mysql_query($query, $connect);
            while ($row = @mysql_fetch_array($res, MYSQL_ASSOC)) {

                if ($row['meta_key'] == 'thumbnail_id') {
                    $row['meta_value'] = wp_get_attachment_image_src($row['meta_value']);
                }

                if ($IsGrouped) {
                    if ($key === "addresses") {
                        $rows[] = $row;
                    } else {

                        if ($key === "product_variation") {
                            if ($id_product_attribute > 0 && $id_product_attribute != $row['id_product_attribute']) {
                                $productGroupedItems[] = $product_attribute;
                                $product_attribute = array();
                            }
                        }

                        if ($oldIdProduct > 0 && $oldIdProduct != $row[$GroupKey]) {
                            $rows[$oldIdProduct] = $productGroupedItems;
                            $productGroupedItems = array();
                        }

                        if ($key === 'product_imgs') {
                            $postId = $row['post_id'];
                            $row['post_id'] = $this->image[$postId];
                            $rows['product_imgs_all'][] = $row;
                            $productGroupedItems[] = $row;
                            $oldIdProduct = $this->image[$postId];
                        } else if ($key === 'product_imgage_ids') {
                            $images = explode(',', $row['meta_value']);

                            if (count($images) > 0) {
                                foreach ($images as $imgage) {
                                    $this->image[$imgage] = $row['post_id'];
                                    if ($this->imageIds === '') {
                                        $this->imageIds = $imgage;
                                    } else {
                                        $this->imageIds = $this->imageIds . ',' . $imgage;
                                    }
                                }
                            }
                        } else if ($key === "product_meta") {
                            $productGroupedItems[$row['meta_key']] = $row['meta_value'];
                            $oldIdProduct = $row[$GroupKey];
                        } else if ($key === "product_variation") {
                            $n++;
                            $id_product_attribute = $product_attribute['id_product_attribute'] = $row['id_product_attribute'];
                            $product_attribute[$row['meta_key']] = $row['meta_value'];
                            $oldIdProduct = $row[$GroupKey];
                            if ($n == $count) {
                                $productGroupedItems[] = $product_attribute;
                                $rows[$oldIdProduct] = $productGroupedItems;
                                $productGroupedItems = array();
                            }

                        } else if ($key === "product_lang") {
                            $productGroupedItems[] = $row;
                            $oldIdProduct = $row[$GroupKey];

                        } else if ($key === 'product_langs_meta') {
                            $productGroupedItems[$row['id_lang']][] = $row;
                            $oldIdProduct = $row[$GroupKey];
                        } else {
                            $productGroupedItems[] = $row;
                            $oldIdProduct = $row[$GroupKey];
                        }
                    }
                } else {
                    $rows[] = $row;
                }

                if (!$IsHaveResult)
                    $IsHaveResult = true;
            }

            if ($key !== "addresses") {
                if ($IsGrouped) {
                    if ($IsHaveResult && !isset($rows[$oldIdProduct])) {

                        $rows[$oldIdProduct] = $productGroupedItems;
                        $productGroupedItems = array();

                    }
                }
            }

        }

        if ($key === "addresses") {
            $rows = self::convertCustomerAdressStructur($rows);
        }

        return $rows;
    }


    function convertCustomerAdressStructur($address)
    {

        $firstModify = array();
        $SecondModify = array();
        foreach ($address as $key => $value) {
            $firstModify[$value['user_id']][$key] = $value;
            foreach ($firstModify as $key => $value) {
                if (!$key == "") {
                    $SecondModify[$key] = self::arrayColumn($value, 'meta_value', 'meta_key');
                }
            }
        }

        array_walk(
            $SecondModify,
            function (&$value, $key) use ($address) {
                foreach ($address as $adr) {
                    if ($adr['user_id'] == $key) {
                        $id = $adr['umeta_id'];
                    }
                }
                $value['id'] = $id;

                return $value['id_customer'] = $key;
            }
        );

        return $SecondModify;


    }


    function arrayColumn($input, $columnKey, $indexKey = null)
    {
        if (!function_exists('array_column')) {
            $array = array();
            foreach ($input as $value) {
                if (!array_key_exists($columnKey, $value)) {
                    trigger_error("Key \"$columnKey\" does not exist in array");
                    return false;
                }
                if (is_null($indexKey)) {
                    $array[] = $value[$columnKey];
                } else {
                    if (!array_key_exists($indexKey, $value)) {
                        trigger_error("Key \"$indexKey\" does not exist in array");
                        return false;
                    }
                    if (!is_scalar($value[$indexKey])) {
                        trigger_error("Key \"$indexKey\" does not contain scalar value");
                        return false;
                    }
                    $array[$value[$indexKey]] = $value[$columnKey];
                }
            }
            return $array;
        } else {
            return array_column($input, $columnKey, $indexKey);
        }
    }


}

class MPServerAdapter
{

    var $cart = null;
    var $Host = 'localhost';
    var $Port = '3306';
    var $Username = 'root';
    var $Password = '';
    var $Dbname = '';
    var $tablePrefix = '';
    var $imageDir = '';
    var $imageDirCategory = '';
    var $imageDirProduct = '';
    var $imageDirManufacturer = '';
    var $imageDirSupplier = '';
    var $version = '';
    var $char_set = '';
    var $blowfish_key = '';
    var $cookie_key = '';

    function __construct()
    {

    }

    function getCart()
    {
        $cart_type = $this->detectCartType();
        $this->cart = $this->_getCartType($cart_type);
        return $this->cart;
    }

    function _getCartType($cart_type)
    {
        $cart = null;
        $cart_type = strtolower($cart_type);
        $class_name = __CLASS__ . ucfirst($cart_type);
        if (class_exists($class_name)) {
            $cart = new $class_name();
        }
        return $cart;
    }

    function detectCartType()
    {
        return 'wordpress';
    }

    function setHostPort($source)
    {
        $source = trim($source);

        if ($source == '') {
            $this->Host = 'localhost';
            return;
        }

        $conf = explode(':', $source);
        if (isset($conf[0]) && isset($conf[1])) {
            $this->Host = $conf[0];
            $this->Port = $conf[1];
        } elseif ($source[0] == '/') {
            $this->Host = 'localhost';
            $this->Port = $source;
        } else {
            $this->Host = $source;
        }
    }

    function connect()
    {
        $triesCount = 10;
        $link = null;
        while (!$link) {
            if (!$triesCount--) {
                break;
            }

            if (version_compare(PHP_VERSION, '7.0.0', '>=')) {
                $link = @mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
                return $link;
            } else {
                $link = @mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
                if (!$link) {
                    sleep(2);
                }
            }
        }

        if ($link) {
            @mysql_select_db(DB_NAME, $link);
        }
        return $link;
    }

    function getCartVersionFromDb($field, $tableName, $where)
    {
        $_link = null;
        $version = '';

        $_link = $this->connect();
        if (!$_link) {
            return $version;
        }

        $sql = 'SELECT ' . $field . ' AS version FROM ' . $this->tablePrefix . $tableName . ' WHERE ' . $where;

        if (version_compare(PHP_VERSION, '7.0.0', '>=')) {
            $query = mysqli_query($_link, $sql);

            if ($query !== false) {
                $row = mysqli_fetch_assoc($query);

                $version = $row['version'];
            }
        } else {
            $query = mysql_query($sql, $_link);

            if ($query !== false) {
                $row = mysql_fetch_assoc($query);

                $version = $row['version'];
            }
        }

        return $version;
    }

}

class MPServerAdapterPrestashop extends MPServerAdapter
{

    function __construct()
    {
        parent::__construct();
        @require_once MPSTORE_BASE_DIR . '/config/settings.inc.php';

        if (defined('_DB_SERVER_')) {
            $this->setHostPort(_DB_SERVER_);
        } else {
            $this->setHostPort(DB_HOSTNAME);
        }

        if (defined('_DB_USER_')) {
            $this->Username = _DB_USER_;
        } else {
            $this->Username = DB_USERNAME;
        }

        $this->Password = _DB_PASSWD_;

        if (defined('_DB_NAME_')) {
            $this->Dbname = _DB_NAME_;
        } else {
            $this->Dbname = DB_DATABASE;
        }
        $this->tablePrefix = _DB_PREFIX_;
        $this->imageDir = '/img/';
        $this->imageDirCategory = $this->imageDir . 'c/';
        $this->imageDirProduct = $this->imageDir . 'p/';
        $this->imageDirManufacturer = $this->imageDir . 'm/';
        $this->imageDirSupplier = $this->imageDir . 'su/';
        $this->version = _PS_VERSION_;
        $this->cookie_key = _COOKIE_KEY_;
    }

}

class MPServerAdapterWordpress extends MPServerAdapter
{

    function __construct()
    {
        parent::__construct();

//        $config = file_get_contents(MPSTORE_BASE_DIR . 'wp-config.php');
        global $wpdb;
//       preg_match('/define\s*\(\s*\'DB_NAME\',\s*\'(.+)\'\s*\)\s*;/', $config, $match);
//       $this->Dbname = $match[1];
//       preg_match('/define\s*\(\s*\'DB_USER\',\s*\'(.+)\'\s*\)\s*;/', $config, $match);
//       $this->Username = $match[1];
//       preg_match('/define\s*\(\s*\'DB_PASSWORD\',\s*\'(.*)\'\s*\)\s*;/', $config, $match);
//       $this->Password = $match[1];
//       preg_match('/define\s*\(\s*\'DB_HOST\',\s*\'(.+)\'\s*\)\s*;/', $config, $match);
//       $this->setHostPort($match[1]);
//       preg_match('/define\s*\(\s*\'DB_CHARSET\',\s*\'(.*)\'\s*\)\s*;/', $config, $match);
//       $this->char_set = $match[1];
//       preg_match('/\$table_prefix\s*=\s*\'(.*)\'\s*;/', $config, $match);
        $this->tablePrefix = $wpdb->base_prefix;
        $this->imageDir = 'wp-content/uploads/';
//       $this->imageDirCategory    = $this->imageDir;
//       $this->imageDirProduct      = $this->imageDir;
//       $this->imageDirManufacturer = $this->imageDir;
        $this->version = $this->getCartVersionFromDb('option_value', 'options', "option_name = 'woocommerce_db_version'");
    }

}

class MPServerResponse
{

    function __construct()
    {

    }

    function createResponse($result, $msg, $obj)
    {
        $response = array();
        $response['status'] = $result;
        $response['message'] = $msg;
        $response['content'] = $obj;
        echo base64_encode(serialize($response));
        return;
    }

    function error($msg = null, $obj = null)
    {
        $this->createResponse('error', $msg, $obj);
    }

    function success($msg = null, $obj = null)
    {
        $this->createResponse('success', $msg, $obj);
    }

}

$connector = new MPServer();
$connector->run();
header("HTTP/1.1 200 OK");
