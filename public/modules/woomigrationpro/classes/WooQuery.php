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

class WooQuery
{
    // --- Query builder vars:
    // wooComerce version 4.9.8

    protected $source_cart;
    protected $tp;
    protected $offset;
    protected $row_count = 10;
    protected $version;
    protected $languages;
    protected $recent_data = false;

    // --- Constructor / destructor:

    public function __construct()
    {
    }

    // --- Configuration methods:

    public function setRowCount($number)
    {
        $this->row_count = (int)$number;
    }

    public function setLanguages($string)
    {
        $this->languages = pSQL($string);
    }

    public function setVersion($string)
    {
        $this->version = pSQL($string);
    }

    public function setCart($string)
    {
        $this->source_cart = pSQL($string);
    }

    public function setPrefix($string)
    {
        $this->tp = pSQL($string);
    }

    public function setOffset($number)
    {
        $this->offset = (int)$number;
    }
    public function setRecentData($recent_data)
    {
        $this->recent_data = (bool)$recent_data;
    }

    // --- get query string methods:

    public function getDefaultShopValues()
    {
        $q = array();
        $q['default_currency'] = "SELECT `option_id` as `source_id`, `option_value` as `source_name` FROM " . pSQL($this->tp) . "options WHERE option_name = 'woocommerce_currency'";
        $q['root_category'] = "SELECT `option_id` as `source_id`, `option_value` as `source_name` FROM " . pSQL($this->tp) . "options WHERE `option_name` = 'default_category'";
        $q['woocommerce_prices_include_tax'] = 'SELECT * FROM ' . pSQL($this->tp) . 'options where option_name="woocommerce_prices_include_tax"';
        $q['default_country'] = 'SELECT * FROM ' . pSQL($this->tp) . 'options where option_name="woocommerce_default_country"';

        return $q;
    }

    public function getMappingInfo()
    {
        $q = array();

        $q['multi_shops'] = 'SELECT `option_id` as `source_id`, `option_value` as `source_name` FROM  `' . pSQL($this->tp) . 'options` WHERE `option_name` ="blogname"';
        $q['languages'] = 'SELECT id AS source_id,english_name AS source_name FROM ' . pSQL($this->tp) . 'icl_languages WHERE active = 1';
//        $q['currencies'] = "SELECT `option_id` as `source_id`, `option_value` as `source_name` FROM " . pSQL($this->tp) . "options WHERE option_name LIKE '%currency'";
        $q['order_states'] = "";

        return $q;
    }

    public function getCountInfo($wpml)
    {
        $q = array();

        if (Tools::getValue('entities_taxes') == 1 && !Tools::getValue('migrate_recent_data')) {
            $q['taxes'] = 'SELECT count(1) as c FROM ' . pSQL($this->tp) . 'woocommerce_tax_rates';
        }

        if (Tools::getValue('entities_manufacturers') == 1 && !Tools::getValue('migrate_recent_data')) {
            $q['manufacturers'] = 'SELECT count(1) as c FROM ' . pSQL($this->tp) . 'term_taxonomy AS TT JOIN ' . pSQL($this->tp) . 'terms AS T ON T.term_id=TT.term_id WHERE TT.taxonomy="brand"';
        }

        if (Tools::getValue('entities_categories') == 1 && !Tools::getValue('migrate_recent_data')) {
            if ($wpml) {
                $q['categories'] = 'select count(1) as `c` from ' . pSQL($this->tp) . 'icl_translations as tr join ' . pSQL($this->tp) . 'terms as ter on ter.term_id=tr.element_id 
                                    join ' . pSQL($this->tp) . 'term_taxonomy as tax  on tax.term_id=tr.element_id
                                    where element_type="tax_product_cat" and source_language_code is null';
            } else {
                $q['categories'] = 'select count(*) as c  from (select count(*) FROM ' . pSQL($this->tp) . 'terms as t left join 
                                   ' . pSQL($this->tp) . 'term_taxonomy as tax  on tax.term_id= t.term_id left join ' . pSQL($this->tp) . 'termmeta as tm on 
                                   tm.term_id=t.term_id where tax.taxonomy="product_cat" GROUP BY tax.term_id ) as cats';
            }
        }

        if (Tools::getValue('entities_products') == 1) {
            if ($wpml) {
                if (Tools::getValue('migrate_recent_data')) {
                    $last_migrated_product_id = Configuration::get('woomigrationpro_product');
                    $q['products'] = 'SELECT COUNT(1) AS `c` FROM ' . pSQL($this->tp) . 'icl_translations AS tr 
                                  JOIN ' . pSQL($this->tp) . 'posts as p on tr.element_id=p.ID 
                                  WHERE tr.element_type="post_product" AND p.ID > ' . (int)$last_migrated_product_id . ' AND tr.source_language_code IS NULL AND p.post_status NOT IN ("inherit","auto-draft","trash")';
                } else {
                    $q['products'] = 'SELECT COUNT(1) AS `c` FROM ' . pSQL($this->tp) . 'icl_translations AS tr 
                                  JOIN ' . pSQL($this->tp) . 'posts as p on tr.element_id=p.ID 
                                  WHERE tr.element_type="post_product" AND tr.source_language_code IS NULL AND p.post_status NOT IN ("inherit","auto-draft","trash")';
                }
            } else {
                if (Tools::getValue('migrate_recent_data')) {
                    $last_migrated_product_id = Configuration::get('woomigrationpro_product');
                    $q['products'] = "SELECT count(1) as c FROM " . pSQL($this->tp) . "posts WHERE  post_type='product' AND ID > " . (int)$last_migrated_product_id;
                } else {
                    $q['products'] = "SELECT count(1) as c FROM " . pSQL($this->tp) . "posts WHERE  post_type='product'";
                }
            }
        }

        if (Tools::getValue('entities_customers') == 1) {
            if (Tools::getValue('migrate_recent_data')) {
                $last_migrated_customer_id = Configuration::get('woomigrationpro_customer');
                $q['customers'] = 'SELECT COUNT(1) AS "c" FROM  ' . pSQL($this->tp) . 'users AS usr JOIN  ' . pSQL($this->tp) . 'usermeta 
                                    AS usrmeta ON usrmeta.user_id=usr.id WHERE usrmeta.meta_key="' . pSQL($this->tp) . 'capabilities" AND usr.id > ' . (int)$last_migrated_customer_id . ' AND usrmeta.meta_value LIKE
                                     "%customer%"';
            } else {
                $q['customers'] = 'SELECT COUNT(1) AS "c" FROM  ' . pSQL($this->tp) . 'users AS usr JOIN  ' . pSQL($this->tp) . 'usermeta 
                                    AS usrmeta ON usrmeta.user_id=usr.id WHERE usrmeta.meta_key="' . pSQL($this->tp) . 'capabilities" AND usrmeta.meta_value LIKE
                                     "%customer%"';
            }
        }

        if (Tools::getValue('entities_orders') == 1 && Tools::getIsset('entities_customers') == 1) {
            if (Tools::getValue('migrate_recent_data')) {
                $last_migrated_order_id = Configuration::get('woomigrationpro_order');
                $q['orders'] = 'SELECT COUNT(1) AS "c" FROM `' . pSQL($this->tp) . 'posts` WHERE post_type="shop_order" AND id > ' . (int)$last_migrated_order_id . ' AND post_status NOT IN ("inherit","auto-draft","trash","draft")';
            } else {
                $q['orders'] = 'SELECT COUNT(1) AS "c" FROM `' . pSQL($this->tp) . 'posts` WHERE post_type="shop_order" AND post_status NOT IN ("inherit","auto-draft","trash","draft")';
            }
        }

        return $q;
    }


    //Manufactures

    public function manufactures()
    {
        $q = array();

        if ($this->version > '3.2.5') {
            $q['manufacturers'] = 'SELECT  TT.term_id as id_manufacturer,T.name,T.slug,TM.meta_value as id_image ,p.guid as url
                               FROM ' . pSQL($this->tp) . 'term_taxonomy AS TT 
                               LEFT JOIN ' . pSQL($this->tp) . 'terms AS T ON T.term_id=TT.term_id 
                               LEFT JOIN ' . pSQL($this->tp) . 'termmeta as TM ON TT.term_id=TM.term_id
                               LEFT JOIN ' . pSQL($this->tp) . 'posts AS p ON p.ID = TM.meta_value
                               WHERE TT.taxonomy="brand"  and T.slug NOT LIKE CONCAT(\'%\', (SELECT l.code FROM wp_icl_languages l  WHERE  l.active = 1 and l.major = 0), \'%\')
                                ORDER BY TT.term_id ASC LIMIT ' . (int)$this->offset . ',' . (int)$this->row_count;
        } else {
            $q['manufacturers'] = 'SELECT  TT.term_id as id_manufacturer,T.name,T.slug,TM.meta_value as id_image ,p.guid as url
                               FROM ' . pSQL($this->tp) . 'term_taxonomy AS TT 
                               LEFT JOIN ' . pSQL($this->tp) . 'terms AS T ON T.term_id=TT.term_id 
                               LEFT JOIN ' . pSQL($this->tp) . 'termmeta as TM ON TT.term_id=TM.term_id
                               LEFT JOIN ' . pSQL($this->tp) . 'posts AS p ON p.ID = TM.meta_value
                               WHERE TT.taxonomy="brand" 
                                ORDER BY TT.term_id ASC LIMIT ' . (int)$this->offset . ',' . (int)$this->row_count;
        }

        $groupedqueriesconfiguration = array();
        $q['groupedqueriesconfiguration'] = $groupedqueriesconfiguration;

        return $q;
    }

    //taxes
    public function taxes()
    {
        $q = array();

        $q['tax_rules'] = 'SELECT  tax_rate_name as name, tax_rate_class as tax_rules_group,tax_rate_id as id_tax_rule,tax_rate_country as id_country,tax_rate_state as id_state,tax_rate_class as class 
                            FROM ' . pSQL($this->tp) . 'woocommerce_tax_rates  
                            ORDER BY tax_rate_id LIMIT ' . (int)$this->offset . ',' . (int)$this->row_count;

        $q['taxes'] = 'SELECT tax_rate_id as id_tax, tax_rate as rate, tax_rate_name as name 
                        FROM ' . pSQL($this->tp) . 'woocommerce_tax_rates  AS tax_rule
                        INNER JOIN (
                            SELECT  tax_rate_id as id_tax_rule
                            FROM ' . pSQL($this->tp) . 'woocommerce_tax_rates  
                            ORDER BY tax_rate_id LIMIT ' . (int)$this->offset . ',' . (int)$this->row_count . '
                          ) AS tax    ON (id_tax_rule = tax_rate_id ) ORDER BY tax_rate_id ';

        $groupedqueriesconfiguration = array();
        $q['groupedqueriesconfiguration'] = $groupedqueriesconfiguration;

        return $q;
    }

    // --- Category methods:

    public function category($wpml)
    {
        $q = array();
        if ($wpml) {
            $q['category'] = 'select tr.trid as ID, tax.term_id AS id_category, parent AS id_parent,ter.name,ter.slug,tax.description 
                                from ' . pSQL($this->tp) . 'icl_translations as tr 
                                join ' . pSQL($this->tp) . 'terms as ter on ter.term_id=tr.element_id 
                                join ' . pSQL($this->tp) . 'term_taxonomy as tax  on tax.term_id=tr.element_id 
                                where element_type="tax_product_cat" and source_language_code is null GROUP BY tax.term_id LIMIT ' . (int)$this->offset . ',' . (int)$this->row_count;
        } else {
            $q['category'] = 'select tax.term_id AS id_category, parent AS id_parent,t.name,t.slug,tax.description  
                               from ' . pSQL($this->tp) . 'terms as t 
                               left join ' . pSQL($this->tp) . 'term_taxonomy as tax  on tax.term_id= t.term_id 
                               left join ' . pSQL($this->tp) . 'termmeta as tm on tm.term_id=t.term_id
                               where tax.taxonomy="product_cat"  GROUP BY tax.term_id ORDER BY id_category ASC LIMIT ' . (int)$this->offset . ',' . (int)$this->row_count;
        }


        $q['category_lang'] = 'SELECT  tr.trid AS id, te.term_id AS id_category, lang.id AS id_lang, te.name, te.slug as link_rewrite, ta.description 
                                from    ' . pSQL($this->tp) . 'terms te      
                                INNER JOIN ' . pSQL($this->tp) . 'term_taxonomy AS ta  ON ta.term_id= te.term_id 
                                inner join  ' . pSQL($this->tp) . 'icl_translations tr on tr.element_id = te.term_id
                                INNER join (
                                    select tr1.trid 
                                                    from ' . pSQL($this->tp) . 'icl_translations as tr1 
                                                    join ' . pSQL($this->tp) . 'terms as ter1 on ter1.term_id=tr1.element_id 
                                                    join ' . pSQL($this->tp) . 'term_taxonomy as tax1  on tax1.term_id=tr1.element_id
                                                    where element_type="tax_product_cat" and source_language_code is null GROUP BY tax1.term_id LIMIT ' . (int)$this->offset . ',' . (int)$this->row_count . '
                                    ) cat on cat.trid = tr.trid
                                JOIN ' . pSQL($this->tp) . 'icl_languages AS lang ON lang.code = tr.language_code
                                WHERE element_type="tax_product_cat"  
                                ORDER BY id ASC';


        /*
        'SELECT tr.trid AS id, ter.term_id AS id_category, lang.id AS id_lang,ter.name,ter.slug as link_rewrite,tax.description FROM ' . pSQL($this->tp) . 'icl_translations AS tr
                                 INNER JOIN ( SELECT  tax.term_id AS id_category
                                              FROM ' . pSQL($this->tp) . 'term_taxonomy AS tax
                                              WHERE tax.taxonomy="product_cat" GROUP BY tax.term_id ORDER BY id_category ASC LIMIT ' . (int)$this->offset . ',' . (int)$this->row_count . '
                                            )  AS category ON category.id_category = tr.trid
                                 JOIN ' . pSQL($this->tp) . 'terms AS ter ON ter.term_id=tr.element_id JOIN ' . pSQL($this->tp) . 'term_taxonomy AS tax  ON tax.term_id=tr.element_id JOIN ' . pSQL($this->tp) . 'icl_languages AS lang ON lang.code= tr.language_code
                                 WHERE element_type="tax_product_cat" ORDER BY tr.trid ASC';

*/
        $q['category_img'] = 'SELECT term_id as id_category, meta_key,meta_value
                              FROM ' . pSQL($this->tp) . 'termmeta
                              INNER JOIN ( SELECT  tax.term_id AS id_category
                                              FROM ' . pSQL($this->tp) . 'term_taxonomy AS tax
                                              WHERE tax.taxonomy="product_cat" GROUP BY tax.term_id LIMIT ' . (int)$this->offset . ',' . (int)$this->row_count . '
                                        ) AS category ON category.id_category = term_id
                              WHERE  meta_key="thumbnail_id" ORDER BY term_id';

        $groupedqueriesconfiguration = array();
        $groupedqueriesconfiguration['category_lang'] = 'id';
//        $groupedqueriesconfiguration['category_img'] = 'id_category';
        $q['groupedqueriesconfiguration'] = $groupedqueriesconfiguration;

        return $q;
    }


    public function singleCategory($wpml, $id_category)
    {
        $q = array();
        if ($wpml) {
            $q['category'] = 'select tax.term_id AS id_category, parent AS id_parent,ter.name,ter.slug,tax.description 
                                from ' . pSQL($this->tp) . 'icl_translations as tr 
                                join ' . pSQL($this->tp) . 'terms as ter on ter.term_id=tr.element_id 
                                join ' . pSQL($this->tp) . 'term_taxonomy as tax  on tax.term_id=tr.element_id
                                where element_type="tax_product_cat" and tax.term_id =' . (int)$id_category . ' and source_language_code is null GROUP BY tax.term_id';
        } else {
            $q['category'] = 'select tax.term_id AS id_category, parent AS id_parent,t.name,t.slug,tax.description
                               from ' . pSQL($this->tp) . 'terms as t 
                               left join ' . pSQL($this->tp) . 'term_taxonomy as tax  on tax.term_id= t.term_id 
                               left join ' . pSQL($this->tp) . 'termmeta as tm on tm.term_id=t.term_id
                               where tax.taxonomy="product_cat" and tax.term_id =' . (int)$id_category . ' GROUP BY tax.term_id ORDER BY id_category ASC';
        }

        $q['category_lang'] = 'SELECT tr.trid AS id, ter.term_id AS id_category, lang.id AS id_lang,ter.name,ter.slug as link_rewrite,tax.description FROM ' . pSQL($this->tp) . 'icl_translations AS tr
                                 INNER JOIN ( SELECT  tax.term_id AS id_category
                                              FROM ' . pSQL($this->tp) . 'term_taxonomy AS tax
                                              WHERE tax.taxonomy="product_cat" and tax.term_id =' . (int)$id_category . ' GROUP BY tax.term_id
                                            )  AS category ON category.id_category = tr.trid
                                 JOIN ' . pSQL($this->tp) . 'terms AS ter ON ter.term_id=tr.element_id JOIN ' . pSQL($this->tp) . 'term_taxonomy AS tax  ON tax.term_id=tr.element_id JOIN ' . pSQL($this->tp) . 'icl_languages AS lang ON lang.code= tr.language_code
                                 WHERE element_type="tax_product_cat"  and tax.term_id =' . (int)$id_category . ' ORDER BY tr.trid ASC';


        $q['category_img'] = 'SELECT term_id as id_category, meta_key,meta_value
                              FROM ' . pSQL($this->tp) . 'termmeta
                              INNER JOIN ( SELECT  tax.term_id AS id_category
                                              FROM ' . pSQL($this->tp) . 'term_taxonomy AS tax
                                              WHERE tax.taxonomy="product_cat" GROUP BY tax.term_id LIMIT ' . (int)$this->offset . ',' . (int)$this->row_count . '
                                        ) AS category ON category.id_category = term_id
                              WHERE  meta_key="thumbnail_id"  and  term_id =' . (int)$id_category . ' ORDER BY term_id';
        $groupedqueriesconfiguration = array();
        $groupedqueriesconfiguration['category_lang'] = 'id_category';
//        $groupedqueriesconfiguration['category_img'] = 'id_category';
        $q['groupedqueriesconfiguration'] = $groupedqueriesconfiguration;

        return $q;
    }


    private function productWpml()
    {
        $q = array();
        $q['product'] = "SELECT ID , tr.trid as id_product, post_author as id_supplier, post_date as date_add, post_modified as post_upd,post_status as active, 
                    menu_order as default_category ,post_name, post_title, post_content, post_excerpt
                    FROM " . pSQL($this->tp) . "icl_translations AS tr JOIN " . pSQL($this->tp) . "posts as p on tr.element_id=p.ID WHERE tr.element_type='post_product' 
                    AND tr.source_language_code IS NULL AND p.post_status NOT IN ('inherit','auto-draft','trash') ORDER BY ID ASC LIMIT " . (int)$this->offset . ',' . (int)$this->row_count;

        $q['product_meta'] = '(select distinct tr.term_taxonomy_id as id_product,  REPLACE( tt.taxonomy, \'pa_\', \'\')    as meta_key,   t.name    as meta_value from   ' . pSQL($this->tp) . 'terms t 
                    inner join  ' . pSQL($this->tp) . 'term_taxonomy tt on t.term_id = tt.term_id
                    inner  JOIN ' . pSQL($this->tp) . 'term_relationships tr  on tt.term_id = tr.term_taxonomy_id
                    inner JOIN (                
                        SELECT ID , tr.trid as id_product, post_author as id_supplier, post_date as date_add, post_modified as post_upd,post_status as active, 
                        menu_order as default_category ,post_name, post_title, post_content, post_excerpt
                        FROM ' . pSQL($this->tp) . 'icl_translations AS tr JOIN ' . pSQL($this->tp) . 'posts as p on tr.element_id=p.ID WHERE tr.element_type=\'post_product\' 
                        AND tr.source_language_code IS NULL AND p.post_status NOT IN (\'inherit\',\'auto-draft\',\'trash\') ORDER BY ID ASC LIMIT ' . (int)$this->offset . ',' . (int)$this->row_count . '
                                                                )  p  on tr.term_taxonomy_id = p.ID
                                                                WHERE tt.taxonomy like \'pa_%\')
                    UNION
                    (select distinct pm.post_id as id_product, pm.meta_key, pm.meta_value from   ' . pSQL($this->tp) . 'postmeta pm
                    inner JOIN (      
                        SELECT ID , tr.trid as id_product, post_author as id_supplier, post_date as date_add, post_modified as post_upd,post_status as active, 
                        menu_order as default_category ,post_name, post_title, post_content, post_excerpt
                        FROM ' . pSQL($this->tp) . 'icl_translations AS tr JOIN ' . pSQL($this->tp) . 'posts as p on tr.element_id=p.ID WHERE tr.element_type=\'post_product\' 
                        AND tr.source_language_code IS NULL AND p.post_status NOT IN (\'inherit\',\'auto-draft\',\'trash\') ORDER BY ID ASC LIMIT ' . (int)$this->offset . ',' . (int)$this->row_count . '
                                                                )  p  on pm.post_id  = p.ID
                     WHERE pm.meta_key NOT IN (\'_product_attributes\') )
                     ORDER BY id_product  ASC ';

        /////

        $q['product_lang'] = "SELECT tr.trid as id_product, lang.id as id_lang , p.* 
            FROM " . pSQL($this->tp) . "icl_translations AS tr JOIN " . pSQL($this->tp) . "posts as p on tr.element_id=p.ID 
            JOIN " . pSQL($this->tp) . "icl_languages as lang on lang.code=tr.language_code
            INNER JOIN(
                               
                SELECT ID , tr.trid as id_product, post_author as id_supplier, post_date as date_add, post_modified as post_upd,post_status as active, 
                menu_order as default_category ,post_name, post_title, post_content, post_excerpt
                FROM " . pSQL($this->tp) . "icl_translations AS tr JOIN " . pSQL($this->tp) . "posts as p on tr.element_id=p.ID WHERE tr.element_type='post_product' 
                AND tr.source_language_code IS NULL AND p.post_status NOT IN ('inherit','auto-draft','trash') ORDER BY ID ASC LIMIT " . (int)$this->offset . "," . (int)$this->row_count . "


                      ) AS product ON product.id_product = tr.trid
            WHERE tr.element_type='post_product' AND p.post_status NOT IN ('inherit','auto-draft','draft') ORDER BY tr.trid ASC ";

        $q['product_langs_meta'] = "SELECT pm.post_id, t.trid,  pm.*, l.id as id_lang FROM " . pSQL($this->tp) . "postmeta pm
            INNER join " . pSQL($this->tp) . "icl_translations t  on t.element_id = pm.post_id
            INNER join " . pSQL($this->tp) . "icl_languages l on l.code = t.language_code
            INNER join (
                SELECT ID , tr.trid as id_product  
                FROM " . pSQL($this->tp) . "icl_translations AS tr 
                JOIN " . pSQL($this->tp) . "posts as p on tr.element_id=p.ID WHERE tr.element_type='post_product' 
                AND tr.source_language_code IS NULL AND p.post_status NOT IN ('inherit','auto-draft','trash') ORDER BY ID ASC LIMIT " . (int)$this->offset . "," . (int)$this->row_count . "
                ) p on p.id_product = t.trid
            where meta_key IN ('_yoast_wpseo_metadesc', '_yoast_wpseo_focuskw', '_yoast_wpseo_title')
            ORDER By pm.post_id,  l.id";

        $q['product_variation'] = 'SELECT  p.post_parent id_product, p.ID as id_product_attribute,  pm.meta_id, pm.post_id, pm.meta_key, pm.meta_value  
            FROM  ' . pSQL($this->tp) . 'postmeta pm 
            INNER join ' . pSQL($this->tp) . 'posts p on p.ID = pm.post_id
            INNER JOIN (
                SELECT ID , tr.trid as id_product, post_author as id_supplier, post_date as date_add, post_modified as post_upd,post_status as active, 
                                menu_order as default_category ,post_name, post_title, post_content, post_excerpt
                                FROM ' . pSQL($this->tp) . 'icl_translations AS tr JOIN ' . pSQL($this->tp) . 'posts as p on tr.element_id=p.ID WHERE tr.element_type=\'post_product\' 
                                AND tr.source_language_code IS NULL AND p.post_status NOT IN (\'inherit\',\'auto-draft\',\'trash\') ORDER BY ID ASC LIMIT  ' . (int)$this->offset . ',' . (int)$this->row_count . '
            ) p1 on p1.ID = p.post_parent 
             WHERE p.post_type = \'product_variation\' 
            ORDER BY p.post_parent ASC';

        /*$q['product_combination'] = 'SELECT DISTINCT  p.ID id_product,   g.group_id, tr.name, REPLACE( t.taxonomy, \'pa_\', \'\') as attribute_name   from ' . pSQL($this->tp) . 'terms tr
            INNER JOIN ' . pSQL($this->tp) . 'term_taxonomy t ON tr.term_id = t.term_id
            INNER JOIN ' . pSQL($this->tp) . 'term_relationships trr on trr.term_taxonomy_id = t.term_taxonomy_id
            INNER JOIN (
                SELECT ID FROM ' . pSQL($this->tp) . 'icl_translations AS tr JOIN ' . pSQL($this->tp) . 'posts as p on tr.element_id=p.ID WHERE tr.element_type=\'post_product\'
                AND tr.source_language_code IS NULL AND p.post_status NOT IN (\'inherit\',\'auto-draft\',\'trash\') ORDER BY ID ASC  LIMIT  ' . (int)$this->offset . ',' . (int)$this->row_count . '
            ) p on p.ID =  trr.object_id
            INNER JOIN ( SELECT attribute_id group_id,  CONCAT( \'pa_\',  attribute_name) attribute_name
            FROM  ' . pSQL($this->tp) . 'woocommerce_attribute_taxonomies)  g on g.attribute_name =  t.taxonomy
            WHERE  t.taxonomy like \'%pa_%\'
            ORDER BY  p.ID  ASC';*/


        $q['tag'] = "SELECT tr.trid, rel.object_id as id_product,tax.term_id as id_tag,ter.name as tag_name  
                            FROM " . pSQL($this->tp) . "term_relationships as rel 
                            join " . pSQL($this->tp) . "term_taxonomy as tax on tax.term_taxonomy_id=rel.term_taxonomy_id
                            INNER JOIN(
                                                 
                SELECT ID , tr.trid as id_product, post_author as id_supplier, post_date as date_add, post_modified as post_upd,post_status as active, 
                menu_order as default_category ,post_name, post_title, post_content, post_excerpt
                FROM " . pSQL($this->tp) . "icl_translations AS tr JOIN " . pSQL($this->tp) . "posts as p on tr.element_id=p.ID WHERE tr.element_type='post_product' 
                AND tr.source_language_code IS NULL AND p.post_status NOT IN ('inherit','auto-draft','trash') ORDER BY ID ASC LIMIT " . (int)$this->offset . "," . (int)$this->row_count . "

                                      ) AS product ON product.ID = rel.object_id
                            join " . pSQL($this->tp) . "terms as ter on ter.term_id=tax.term_id join " . pSQL($this->tp) . "icl_translations as tr on tr.element_id=tax.term_id 
                            where tax.taxonomy='product_tag' and tr.element_type='tax_product_tag'   ORDER BY object_id";


        $q['product_manufacturer'] = 'SELECT object_id as id_product, term_id as id_manufacturer 
                FROM ' . pSQL($this->tp) . 'term_taxonomy AS TT 
                JOIN ' . pSQL($this->tp) . 'term_relationships AS TREL ON TT.term_taxonomy_id=TREL.term_taxonomy_id 
                INNER JOIN(
                                      
                SELECT ID , tr.trid as id_product, post_author as id_supplier, post_date as date_add, post_modified as post_upd,post_status as active, 
                menu_order as default_category ,post_name, post_title, post_content, post_excerpt
                FROM ' . pSQL($this->tp) . 'icl_translations AS tr JOIN ' . pSQL($this->tp) . 'posts as p on tr.element_id=p.ID WHERE tr.element_type=\'post_product\' 
                AND tr.source_language_code IS NULL AND p.post_status NOT IN (\'inherit\',\'auto-draft\',\'trash\') ORDER BY ID ASC LIMIT ' . (int)$this->offset . ',' . (int)$this->row_count . '

                        ) AS product ON product.ID =  TREL.object_id
                WHERE TT.taxonomy=\'brand\' 
                ORDER BY object_id ASC ';

        $q['product_tag'] = "SELECT tr.trid, rel.object_id as id_product,tax.term_id as id_tag,ter.name as tag_name  
                                      FROM " . pSQL($this->tp) . "term_relationships as rel 
                                      join " . pSQL($this->tp) . "term_taxonomy as tax on tax.term_taxonomy_id=rel.term_taxonomy_id
                                      INNER JOIN(
                                                                                                            
                SELECT ID , tr.trid as id_product, post_author as id_supplier, post_date as date_add, post_modified as post_upd,post_status as active, 
                menu_order as default_category ,post_name, post_title, post_content, post_excerpt
                FROM " . pSQL($this->tp) . "icl_translations AS tr JOIN " . pSQL($this->tp) . "posts as p on tr.element_id=p.ID WHERE tr.element_type='post_product' 
                AND tr.source_language_code IS NULL AND p.post_status NOT IN ('inherit','auto-draft','trash') ORDER BY ID ASC LIMIT " . (int)$this->offset . "," . (int)$this->row_count . "

                                                ) AS product ON product.ID = rel.object_id
                                      join " . pSQL($this->tp) . "terms as ter on ter.term_id=tax.term_id join " . pSQL($this->tp) . "icl_translations as tr on tr.element_id=tax.term_id 
                                      where tax.taxonomy='product_tag' and tr.element_type='tax_product_tag'   ORDER BY object_id";

        $q['product_cat'] = "select object_id as id_product,term_id as id_category from " . pSQL($this->tp) . "term_relationships as rel 
                                join " . pSQL($this->tp) . "term_taxonomy as tax on tax.term_taxonomy_id=rel.term_taxonomy_id
                                INNER JOIN(
                                                                                                    
                SELECT ID , tr.trid as id_product, post_author as id_supplier, post_date as date_add, post_modified as post_upd,post_status as active, 
                menu_order as default_category ,post_name, post_title, post_content, post_excerpt
                FROM " . pSQL($this->tp) . "icl_translations AS tr JOIN " . pSQL($this->tp) . "posts as p on tr.element_id=p.ID WHERE tr.element_type='post_product' 
                AND tr.source_language_code IS NULL AND p.post_status NOT IN ('inherit','auto-draft','trash') ORDER BY ID ASC LIMIT " . (int)$this->offset . "," . (int)$this->row_count . "

                                        ) AS product ON product.ID =  rel.object_id
                                where tax.taxonomy='product_cat'  ORDER BY object_id ASC";

        /*

                                            $q['product_manufacturer'] = 'SELECT object_id as id_product, term_id as id_manufacturer
                                                                            FROM ' . pSQL($this->tp) . 'term_taxonomy AS TT
                                                                            JOIN ' . pSQL($this->tp) . 'term_relationships AS TREL ON TT.term_taxonomy_id=TREL.term_taxonomy_id
                                                                            INNER JOIN(

                                                        SELECT ID , tr.trid as id_product, post_author as id_supplier, post_date as date_add, post_modified as post_upd,post_status as active,
                                                        menu_order as default_category ,post_name, post_title, post_content, post_excerpt
                                                        FROM ' . pSQL($this->tp) . 'icl_translations AS tr JOIN ' . pSQL($this->tp) . 'posts as p on tr.element_id=p.ID WHERE tr.element_type=\'post_product\'
                                                        AND tr.source_language_code IS NULL AND p.post_status NOT IN (\'inherit\',\'auto-draft\',\'trash\') ORDER BY ID ASC LIMIT " . (int)$this->offset . "," . (int)$this->row_count . "

                                                                                    ) AS product ON product.ID =  TREL.object_id
                                                                            WHERE TT.taxonomy=\'brand\'
                                                                            ORDER BY object_id ASC ';
         */

        #section 4
        if ((int)$this->offset === 0) {
            $q['attribute_group'] = 'SELECT * FROM ' . pSQL($this->tp) . 'woocommerce_attribute_taxonomies';
            $q['attribute_group_lang'] = "select s.id,s.value as attribute_name,l.id as id_lang, st.value 
                        from " . pSQL($this->tp) . "icl_strings as s 
                        join " . pSQL($this->tp) . "icl_string_translations as st 
                        on s.id=st.string_id 
                        join " . pSQL($this->tp) . "icl_languages as l on l.code=st.`language`
                            where s.value in ( SELECT CONCAT( 'tax_pa_',  attribute_name)  FROM  " . pSQL($this->tp) . "woocommerce_attribute_taxonomies ) 
                            group by st.value order by s.id  ORDER BY s.id ASC ";

            $q['attribute'] = "select  g.attribute_id,  tr.trid as id_attribute, lang.id as id_lang, tr.element_type as attribute_group, t.name as name from " . pSQL($this->tp) . "icl_translations as tr 
            join " . pSQL($this->tp) . "terms as t on t.term_id=tr.element_id 
            join " . pSQL($this->tp) . "icl_languages as lang on tr.language_code=lang.code
            join ( SELECT attribute_id , CONCAT( 'tax_pa_',  attribute_name) as attribute_name  FROM  " . pSQL($this->tp) . "woocommerce_attribute_taxonomies ) g ON g.attribute_name = element_type
            WHERE tr.source_language_code IS NULL
            ORDER BY t.term_id ASC";


            $q['attribute_lang'] = "select tr.trid as id_attribute, lang.id as id_lang, tr.element_type as attribute_group, t.name as name 
                    from " . pSQL($this->tp) . "icl_translations as tr 
                    join " . pSQL($this->tp) . "terms as t on t.term_id=tr.element_id join " . pSQL($this->tp) . "icl_languages as lang on tr.language_code=lang.code
                    WHERE  element_type IN( SELECT CONCAT( 'tax_pa_',  attribute_name)  FROM  " . pSQL($this->tp) . "woocommerce_attribute_taxonomies ) 
                    ORDER BY t.term_id ASC ";
        }
        // Query for get image ids
        $q['product_imgage_ids'] = "(SELECT  post_meta.* FROM   " . pSQL($this->tp) . "postmeta AS post_meta
                        INNER JOIN(
                                                 
                SELECT ID , tr.trid as id_product, post_author as id_supplier, post_date as date_add, post_modified as post_upd,post_status as active, 
                menu_order as default_category ,post_name, post_title, post_content, post_excerpt
                FROM " . pSQL($this->tp) . "icl_translations AS tr JOIN " . pSQL($this->tp) . "posts as p on tr.element_id=p.ID WHERE tr.element_type='post_product' 
                AND tr.source_language_code IS NULL AND p.post_status NOT IN ('inherit','auto-draft','trash') ORDER BY ID ASC LIMIT " . (int)$this->offset . "," . (int)$this->row_count . "

                            
                            )  AS product ON product.ID = post_meta.post_id and   post_meta.meta_key = '_product_image_gallery'   and post_meta.meta_value is not null AND post_meta.meta_value <> '' )
                    union
                    (SELECT  post_meta.* FROM   " . pSQL($this->tp) . "postmeta AS post_meta
                    INNER JOIN(
                                                    
                SELECT ID , tr.trid as id_product, post_author as id_supplier, post_date as date_add, post_modified as post_upd,post_status as active, 
                menu_order as default_category ,post_name, post_title, post_content, post_excerpt
                FROM " . pSQL($this->tp) . "icl_translations AS tr JOIN " . pSQL($this->tp) . "posts as p on tr.element_id=p.ID WHERE tr.element_type='post_product' 
                AND tr.source_language_code IS NULL AND p.post_status NOT IN ('inherit','auto-draft','trash') ORDER BY ID ASC LIMIT " . (int)$this->offset . "," . (int)$this->row_count . "

                                )  AS product ON product.ID = post_meta.post_id and   post_meta.meta_key = '_thumbnail_id' 
                            and post_meta.meta_value is not null AND post_meta.meta_value <> '')
                    ORDER BY post_id";
        //Query for get image urls
        $q['product_imgs'] = 'SELECT p.ID as id_image , p.* , pm.* FROM ' . pSQL($this->tp) . 'posts as p 
                join ' . pSQL($this->tp) . 'postmeta as pm on p.ID = pm.post_id 
                WHERE meta_key = "_wp_attached_file " ';

        $q['product_download'] = "SELECT post_meta.* FROM " . pSQL($this->tp) . "postmeta post_meta
                    INNER JOIN(
                         
                        SELECT ID , tr.trid as id_product, post_author as id_supplier, post_date as date_add, post_modified as post_upd,post_status as active, 
                        menu_order as default_category ,post_name, post_title, post_content, post_excerpt
                        FROM " . pSQL($this->tp) . "icl_translations AS tr JOIN " . pSQL($this->tp) . "posts as p on tr.element_id=p.ID WHERE tr.element_type='post_product' 
                        AND tr.source_language_code IS NULL AND p.post_status NOT IN ('inherit','auto-draft','trash') ORDER BY ID ASC LIMIT " . (int)$this->offset . "," . (int)$this->row_count . "
        
                                                            )  AS product ON product.ID = post_meta.post_id and   post_meta.meta_key = '_downloadable_files'   
                        where meta_key = '_downloadable_files'  and (meta_value <> 'a:0:{}' AND meta_value <> '') ORDER BY  post_meta.post_id ASC";


        #region Configuration for grouped tables

        $groupedqueriesconfiguration = array();
        //For Product second related queries
        // $groupedqueriesconfiguration['attribute'] = 'term_id';
        $groupedqueriesconfiguration['attribute_lang'] = 'id_attribute';
        $groupedqueriesconfiguration['attribute_group_lang'] = 'id';
        $groupedqueriesconfiguration['product_meta'] = 'id_product';
        $groupedqueriesconfiguration['product_cat'] = 'id_product';
        $groupedqueriesconfiguration['product_lang'] = 'id_product';
        $groupedqueriesconfiguration['product_langs_meta'] = 'trid';
        $groupedqueriesconfiguration['product_imgs'] = 'post_id';
        $groupedqueriesconfiguration['product_imgage_ids'] = 'post_id';
        $groupedqueriesconfiguration['product_download'] = 'post_id';
        $groupedqueriesconfiguration['product_tag_lang'] = 'id_tag';
        $groupedqueriesconfiguration['product_tag'] = 'id_product';
        $groupedqueriesconfiguration['product_variation'] = 'id_product'; // product_combination
        $groupedqueriesconfiguration['product_combination'] = 'id_product';
        $q['groupedqueriesconfiguration'] = $groupedqueriesconfiguration;

        return $q;
    }

    // --- Product method:
    public function product($wpml)
    {
        $q = array();
        if (!$this->recent_data) {
            if ($wpml) {
                return $this->productWpml();
            } else {
                $q['product'] = "SELECT ID ,ID as id_product, post_author as id_supplier, post_date as date_add,post_status as active, post_modified as post_upd, menu_order as default_category,post_name, post_title, post_content, post_excerpt
                        FROM " . pSQL($this->tp) . "posts WHERE   post_type='product'  ORDER BY ID ASC LIMIT " . (int)$this->offset . ',' . (int)$this->row_count;

                $q['product_meta'] = '(select distinct tr.term_taxonomy_id as id_product,  REPLACE( tt.taxonomy, \'pa_\', \'\')    as meta_key,   t.name    as meta_value from   ' . pSQL($this->tp) . 'terms t 
             inner join  ' . pSQL($this->tp) . 'term_taxonomy tt on t.term_id = tt.term_id
             inner  JOIN ' . pSQL($this->tp) . 'term_relationships tr  on tt.term_id = tr.term_taxonomy_id
             inner JOIN (                
                                                       SELECT * FROM  ' . pSQL($this->tp) . 'posts 
                                                       WHERE   post_type=\'product\' ORDER BY ID ASC 
                                                       LIMIT ' . (int)$this->offset . ',' . (int)$this->row_count . '
                                                         )  p  on tr.term_taxonomy_id = p.ID
                                                         WHERE tt.taxonomy like \'pa_%\')
             UNION
             (select distinct pm.post_id as id_product, pm.meta_key, pm.meta_value from   ' . pSQL($this->tp) . 'postmeta pm
             inner JOIN (                
                                                       SELECT * FROM  ' . pSQL($this->tp) . 'posts 
                                                       WHERE   post_type=\'product\' ORDER BY ID ASC 
                                                       LIMIT ' . (int)$this->offset . ',' . (int)$this->row_count . '
                                                         )  p  on pm.post_id = p.ID
              WHERE pm.meta_key NOT IN (\'_product_attributes\') )
              ORDER BY id_product  ASC ';
            }



            $q['product_lang'] = "SELECT tr.trid as id_product, lang.id as id_lang , p.* 
                                  FROM " . pSQL($this->tp) . "icl_translations AS tr JOIN " . pSQL($this->tp) . "posts as p on tr.element_id=p.ID 
                                  JOIN " . pSQL($this->tp) . "icl_languages as lang on lang.code=tr.language_code
                                  INNER JOIN(
                                          SELECT ID ,ID as id_product FROM " . pSQL($this->tp) . "posts 
                                          WHERE   post_type='product' ORDER BY ID ASC 
                                          LIMIT " . (int)$this->offset . "," . (int)$this->row_count . "
                                            ) AS product ON product.id_product = tr.trid
                                  WHERE tr.element_type='post_product' AND p.post_status NOT IN ('inherit','auto-draft','draft') ORDER BY tr.trid ASC ";


            $q['product_langs_meta'] = "SELECT pm.post_id, t.trid,  pm.*, l.id as id_lang FROM " . pSQL($this->tp) . "postmeta pm
                        INNER join " . pSQL($this->tp) . "icl_translations t  on t.element_id = pm.post_id
                        INNER join " . pSQL($this->tp) . "icl_languages l on l.code = t.language_code
                        INNER join (
                            SELECT ID , tr.trid as id_product  
                            FROM " . pSQL($this->tp) . "icl_translations AS tr 
                            JOIN " . pSQL($this->tp) . "posts as p on tr.element_id=p.ID WHERE tr.element_type='post_product' 
                            AND tr.source_language_code IS NULL AND p.post_status NOT IN ('inherit','auto-draft','trash') ORDER BY ID ASC LIMIT " . (int)$this->offset . "," . (int)$this->row_count . "
                            ) p on p.id_product = t.trid
                        where meta_key IN ('_yoast_wpseo_metadesc', '_yoast_wpseo_focuskw', '_yoast_wpseo_title')
                        ORDER By pm.post_id,  l.id";

            $q['product_variation'] = 'SELECT  p.post_parent id_product, p.ID as id_product_attribute,  pm.meta_id, pm.post_id, pm.meta_key, pm.meta_value  
            FROM  ' . pSQL($this->tp) . 'postmeta pm 
            INNER join ' . pSQL($this->tp) . 'posts p on p.ID = pm.post_id
            INNER JOIN (
                SELECT * FROM ' . pSQL($this->tp) . 'posts 
                WHERE   post_type=\'product\'  ORDER BY ID ASC 
                LIMIT    ' . (int)$this->offset . ',' . (int)$this->row_count . '
            ) p1 on p1.ID = p.post_parent 
             WHERE  p.post_type = \'product_variation\' 
            ORDER BY p.post_parent ASC';

            /*$q['product_combination'] = 'SELECT p1.ID id_product_attribute,  p.ID id_product,  tr.term_id as id_attribute, tr.name, ag.attribute_label group_name   FROM `' . pSQL($this->tp) . 'posts` p1
                INNER JOIN ( SELECT * FROM `' . pSQL($this->tp) . 'posts` WHERE   post_type=\'product\'  ORDER BY ID ASC 
                LIMIT    ' . (int)$this->offset . ',' . (int)$this->row_count . ') p on p.ID =  p1.post_parent   and p1.post_type = \'product_variation\'
                INNER JOIN `' . pSQL($this->tp) . 'postmeta` pm on  pm.post_id = p1.ID AND pm.meta_key  LIKE "attribute_pa_%"
                INNER JOIN `' . pSQL($this->tp) . 'terms` tr on  tr.slug = pm.meta_value
                INNER JOIN `' . pSQL($this->tp) . 'woocommerce_attribute_taxonomies` ag ON ag.attribute_name =  REPLACE(pm.meta_key, \'attribute_pa_\', \'\')
                GROUP BY tr.name, p1.ID ORDER BY `id_product_attribute` ASC';

            $q['product_combination1'] = 'SELECT p1.ID id_product_attribute,  p.ID id_product,  tr.term_id as id_attribute, tr.name, ag.attribute_label group_name   FROM `' . pSQL($this->tp) . 'posts` p1  
                INNER JOIN ( SELECT * FROM `' . pSQL($this->tp) . 'posts` WHERE   post_type=\'product\'  ORDER BY ID ASC 
                LIMIT    ' . (int)$this->offset . ',' . (int)$this->row_count . ') p on p.ID =  p1.post_parent   and p1.post_type = \'product_variation\'
                INNER JOIN `' . pSQL($this->tp) . 'postmeta` pm on  pm.post_id = p1.ID AND pm.meta_key  LIKE "attribute_%"
                INNER JOIN `' . pSQL($this->tp) . 'terms` tr on  tr.slug = pm.meta_value
                INNER JOIN `' . pSQL($this->tp) . 'woocommerce_attribute_taxonomies` ag ON ag.attribute_name =  REPLACE(pm.meta_key, \'attribute_\', \'\')
                GROUP BY tr.name, p1.ID ORDER BY `id_product_attribute` ASC';*/

            $q['tag'] = "select * from " . pSQL($this->tp) . "term_taxonomy as tt
                            join " . pSQL($this->tp) . "terms as t on tt.term_id = t.term_id
                            join " . pSQL($this->tp) . "term_relationships as t_rel on t_rel.term_taxonomy_id = tt.term_taxonomy_id 
                            INNER JOIN(
                                      SELECT * FROM " . pSQL($this->tp) . "posts 
                                      WHERE   post_type='product' ORDER BY ID ASC 
                                      LIMIT " . (int)$this->offset . "," . (int)$this->row_count . "
                                        ) AS product ON product.ID = object_id
                            where tt.taxonomy='product_tag' group by t.term_id  ORDER BY object_id";

            $q['product_tag'] = "select * from " . pSQL($this->tp) . "term_taxonomy as tt
                            join " . pSQL($this->tp) . "terms as t on tt.term_id = t.term_id
                            join " . pSQL($this->tp) . "term_relationships as t_rel on t_rel.term_taxonomy_id = tt.term_taxonomy_id 
                            INNER JOIN(
                                      SELECT * FROM " . pSQL($this->tp) . "posts 
                                      WHERE   post_type='product' ORDER BY ID ASC 
                                      LIMIT " . (int)$this->offset . "," . (int)$this->row_count . "
                                        ) AS product ON product.ID = object_id
                            where tt.taxonomy='product_tag' group by t.term_id  ORDER BY object_id";

            $q['product_cat'] = "select object_id as id_product,term_id as id_category from " . pSQL($this->tp) . "term_relationships as rel 
                                join " . pSQL($this->tp) . "term_taxonomy as tax on tax.term_taxonomy_id=rel.term_taxonomy_id
                                INNER JOIN(
                                      SELECT * FROM " . pSQL($this->tp) . "posts 
                                      WHERE   post_type='product' ORDER BY ID ASC 
                                      LIMIT " . (int)$this->offset . "," . (int)$this->row_count . "
                                        ) AS product ON product.ID =  rel.object_id
                                where tax.taxonomy='product_cat'  ORDER BY object_id ASC";

            $q['product_manufacturer'] = 'SELECT object_id as id_product, tt.term_id as id_manufacturer 
                                                FROM ' . pSQL($this->tp) . 'term_taxonomy as tt
                                                join ' . pSQL($this->tp) . 'terms as t on tt.term_id = t.term_id
                                                join ' . pSQL($this->tp) . 'term_relationships as t_rel on t_rel.term_taxonomy_id = tt.term_taxonomy_id 
                                                INNER JOIN(
                                                        SELECT * FROM ' . pSQL($this->tp) . 'posts 
                                                        WHERE   post_type=\'product\' ORDER BY ID ASC 
                                                        LIMIT ' . (int)$this->offset . ',' . (int)$this->row_count . '
                                                        ) AS product ON product.ID =  object_id
                                                WHERE tt.taxonomy=\'brand\' 
                                                ORDER BY object_id ASC ';
            #section 4
            if ((int)$this->offset === 0) {
                $q['attribute_group'] = 'SELECT * FROM ' . pSQL($this->tp) . 'woocommerce_attribute_taxonomies';

                $q['attribute'] = 'SELECT  g.attribute_id,  t.term_id as id_attribute, tax.taxonomy as attribute_group,t.name,t.slug FROM ' . pSQL($this->tp) . 'term_taxonomy as tax 
                                        join ' . pSQL($this->tp) . 'terms as t on t.term_id=tax.term_id
                                        join ( SELECT attribute_id , CONCAT( \'tax_pa_\',  attribute_name) as attribute_name  FROM  ' . pSQL($this->tp) . 'woocommerce_attribute_taxonomies ) g ON g.attribute_name = tax.taxonomy
                                        where tax.taxonomy in ( SELECT CONCAT( \'tax_pa_\',  attribute_name)  FROM  ' . pSQL($this->tp) . 'woocommerce_attribute_taxonomies )
                                        UNION
                                        SELECT  g.attribute_id,  t.term_id as id_attribute, tax.taxonomy as attribute_group,t.name,t.slug FROM ' . pSQL($this->tp) . 'term_taxonomy as tax 
                                        join ' . pSQL($this->tp) . 'terms as t on t.term_id=tax.term_id
                                        join ( SELECT attribute_id , CONCAT( \'pa_\',  attribute_name) as attribute_name  FROM  ' . pSQL($this->tp) . 'woocommerce_attribute_taxonomies ) g ON g.attribute_name = tax.taxonomy
                                        where tax.taxonomy in ( SELECT CONCAT( \'pa_\',  attribute_name)  FROM  ' . pSQL($this->tp) . 'woocommerce_attribute_taxonomies )
                                        ORDER BY id_attribute ASC ';
            }
            // Query for get image ids
            $q['product_imgage_ids'] = "(SELECT  post_meta.* FROM   " . pSQL($this->tp) . "postmeta AS post_meta
                                            INNER JOIN(
                                            SELECT * FROM   " . pSQL($this->tp) . "posts 
                                            WHERE   post_type='product' ORDER BY ID ASC 
                                            LIMIT " . (int)$this->offset . "," . (int)$this->row_count . ")  AS product ON product.ID = post_meta.post_id and   post_meta.meta_key = '_product_image_gallery'   and post_meta.meta_value is not null AND post_meta.meta_value <> '' )
                                        union
                                        (SELECT  post_meta.* FROM   " . pSQL($this->tp) . "postmeta AS post_meta
                                        INNER JOIN(
                                                SELECT * FROM   " . pSQL($this->tp) . "posts 
                                                WHERE   post_type='product' ORDER BY ID ASC LIMIT " . (int)$this->offset . "," . (int)$this->row_count . "
                                                    )  AS product ON product.ID = post_meta.post_id and   post_meta.meta_key = '_thumbnail_id' 
                                                and post_meta.meta_value is not null AND post_meta.meta_value <> '')
                                         ORDER BY post_id";
            //Query for get image urls
            $q['product_imgs'] = 'SELECT p.ID as id_image , p.* , pm.* FROM ' . pSQL($this->tp) . 'posts as p 
                                    join ' . pSQL($this->tp) . 'postmeta as pm on p.ID = pm.post_id 
                                    WHERE meta_key = "_wp_attached_file " ';

            $q['product_download'] = "SELECT post_meta.* FROM " . pSQL($this->tp) . "postmeta post_meta
                                        INNER JOIN(
                                            SELECT * FROM " . pSQL($this->tp) . "posts 
                                            WHERE   post_type='product' ORDER BY ID ASC 
                                            LIMIT " . (int)$this->offset . "," . (int)$this->row_count . "
                                                                                )  AS product ON product.ID = post_meta.post_id and   post_meta.meta_key = '_downloadable_files'   
                                            where meta_key = '_downloadable_files'  and (meta_value <> 'a:0:{}' AND meta_value <> '') ORDER BY  post_meta.post_id ASC";
        } else {
            $last_migrated_product_id = Configuration::get('woomigrationpro_product');
            if ($wpml) {
                $q['product'] = "SELECT ID , tr.trid as id_product, post_author as id_supplier, post_date as date_add, post_modified as post_upd,post_status as active, 
                    menu_order as default_category ,post_name, post_title, post_content, post_excerpt
                    FROM " . pSQL($this->tp) . "icl_translations AS tr JOIN " . pSQL($this->tp) . "posts as p on tr.element_id=p.ID WHERE tr.element_type='post_product' 
                    AND ID > " . (int)$last_migrated_product_id . " AND  tr.source_language_code IS NULL AND p.post_status NOT IN ('inherit','auto-draft','trash') ORDER BY ID ASC LIMIT " . (int)$this->row_count;
            } else {
                $q['product'] = "SELECT ID ,ID as id_product, post_author as id_supplier, post_date as date_add,post_status as active, post_modified as post_upd, menu_order as default_category,post_name, post_title, post_content, post_excerpt
                        FROM " . pSQL($this->tp) . "posts WHERE   post_type='product' AND ID > " . (int)$last_migrated_product_id . " ORDER BY ID ASC LIMIT " . (int)$this->row_count;

                $q['product_meta'] = '(select distinct tr.term_taxonomy_id as id_product,  REPLACE( tt.taxonomy, \'pa_\', \'\')    as meta_key,   t.name    as meta_value from   ' . pSQL($this->tp) . 'terms t 
             inner join  ' . pSQL($this->tp) . 'term_taxonomy tt on t.term_id = tt.term_id
             inner  JOIN ' . pSQL($this->tp) . 'term_relationships tr  on tt.term_id = tr.term_taxonomy_id
             inner JOIN (                
                                                       SELECT * FROM  ' . pSQL($this->tp) . 'posts 
                                                       WHERE   post_type=\'product\'  AND ID > ' . (int)$last_migrated_product_id . ' ORDER BY ID ASC 
                                                       LIMIT ' .(int)$this->row_count . '
                                                         )  p  on tr.term_taxonomy_id = p.ID
                                                         WHERE tt.taxonomy like \'pa_%\')
             UNION
             (select distinct pm.post_id as id_product, pm.meta_key, pm.meta_value from   ' . pSQL($this->tp) . 'postmeta pm
             inner JOIN (                
                                                       SELECT * FROM  ' . pSQL($this->tp) . 'posts 
                                                       WHERE   post_type=\'product\'  AND ID > ' . (int)$last_migrated_product_id . ' ORDER BY ID ASC 
                                                       LIMIT ' .(int)$this->row_count . '
                                                         )  p  on pm.post_id = p.ID
              WHERE pm.meta_key NOT IN (\'_product_attributes\') )
              ORDER BY id_product  ASC ';

                $q['product_lang'] = "SELECT tr.trid as id_product, lang.id as id_lang , p.* 
                                  FROM " . pSQL($this->tp) . "icl_translations AS tr JOIN " . pSQL($this->tp) . "posts as p on tr.element_id=p.ID 
                                  JOIN " . pSQL($this->tp) . "icl_languages as lang on lang.code=tr.language_code
                                  INNER JOIN(
                                          SELECT ID ,ID as id_product FROM " . pSQL($this->tp) . "posts 
                                          WHERE   post_type=\'product\'  AND ID > " . (int)$last_migrated_product_id . " ORDER BY ID ASC 
                                                       LIMIT ' .(int)$this->row_count . '
                                            ) AS product ON product.id_product = tr.trid
                                  WHERE tr.element_type='post_product' AND p.post_status NOT IN ('inherit','auto-draft','draft') ORDER BY tr.trid ASC ";


                $q['product_langs_meta'] = "SELECT pm.post_id, t.trid,  pm.*, l.id as id_lang FROM " . pSQL($this->tp) . "postmeta pm
                        INNER join " . pSQL($this->tp) . "icl_translations t  on t.element_id = pm.post_id
                        INNER join " . pSQL($this->tp) . "icl_languages l on l.code = t.language_code
                        INNER join (
                            SELECT ID , tr.trid as id_product  
                            FROM " . pSQL($this->tp) . "icl_translations AS tr 
                            JOIN " . pSQL($this->tp) . "posts as p on tr.element_id=p.ID WHERE tr.element_type='post_product' AND p.ID > " . (int)$last_migrated_product_id . " 
                            AND tr.source_language_code IS NULL AND p.post_status NOT IN ('inherit','auto-draft','trash') ORDER BY ID ASC LIMIT " . (int)$this->row_count . "
                            ) p on p.id_product = t.trid
                        where meta_key IN ('_yoast_wpseo_metadesc', '_yoast_wpseo_focuskw', '_yoast_wpseo_title')
                        ORDER By pm.post_id,  l.id";

                $q['product_variation'] = 'SELECT  p.post_parent id_product, p.ID as id_product_attribute,  pm.meta_id, pm.post_id, pm.meta_key, pm.meta_value  
                            FROM  ' . pSQL($this->tp) . 'postmeta pm 
                            INNER join ' . pSQL($this->tp) . 'posts p on p.ID = pm.post_id
                            INNER JOIN (
                                SELECT * FROM ' . pSQL($this->tp) . 'posts 
                                 WHERE   post_type=\'product\'  AND ID > ' . (int)$last_migrated_product_id . ' ORDER BY ID ASC 
                                                                       LIMIT ' .(int)$this->row_count . '
                            ) p1 on p1.ID = p.post_parent 
                             WHERE  p.post_type = \'product_variation\' 
                            ORDER BY p.post_parent ASC';

                $q['tag'] = "select * from " . pSQL($this->tp) . "term_taxonomy as tt
                            join " . pSQL($this->tp) . "terms as t on tt.term_id = t.term_id
                            join " . pSQL($this->tp) . "term_relationships as t_rel on t_rel.term_taxonomy_id = tt.term_taxonomy_id 
                            INNER JOIN(
                                      SELECT * FROM " . pSQL($this->tp) . "posts 
                                       WHERE   post_type='product'  AND ID > " . (int)$last_migrated_product_id . " ORDER BY ID ASC 
                                                       LIMIT " .(int)$this->row_count . "
                                        ) AS product ON product.ID = object_id
                            where tt.taxonomy='product_tag' group by t.term_id  ORDER BY object_id";

                $q['product_tag'] = "select * from " . pSQL($this->tp) . "term_taxonomy as tt
                            join " . pSQL($this->tp) . "terms as t on tt.term_id = t.term_id
                            join " . pSQL($this->tp) . "term_relationships as t_rel on t_rel.term_taxonomy_id = tt.term_taxonomy_id 
                            INNER JOIN(
                                      SELECT * FROM " . pSQL($this->tp) . "posts 
                                       WHERE   post_type='product'  AND ID > " . (int)$last_migrated_product_id . " ORDER BY ID ASC 
                                                       LIMIT " .(int)$this->row_count . "
                                        ) AS product ON product.ID = object_id
                            where tt.taxonomy='product_tag' group by t.term_id  ORDER BY object_id";

                $q['product_cat'] = "select object_id as id_product,term_id as id_category from " . pSQL($this->tp) . "term_relationships as rel 
                                join " . pSQL($this->tp) . "term_taxonomy as tax on tax.term_taxonomy_id=rel.term_taxonomy_id
                                INNER JOIN(
                                      SELECT * FROM " . pSQL($this->tp) . "posts 
                                      WHERE   post_type='product'  AND ID > " . (int)$last_migrated_product_id . " ORDER BY ID ASC 
                                                       LIMIT " .(int)$this->row_count . "
                                        ) AS product ON product.ID =  rel.object_id
                                where tax.taxonomy='product_cat'  ORDER BY object_id ASC";

                $q['product_manufacturer'] = 'SELECT object_id as id_product, tt.term_id as id_manufacturer 
                                                FROM ' . pSQL($this->tp) . 'term_taxonomy as tt
                                                join ' . pSQL($this->tp) . 'terms as t on tt.term_id = t.term_id
                                                join ' . pSQL($this->tp) . 'term_relationships as t_rel on t_rel.term_taxonomy_id = tt.term_taxonomy_id 
                                                INNER JOIN(
                                                        SELECT * FROM ' . pSQL($this->tp) . 'posts 
                                                         WHERE   post_type=\'product\'  AND ID > " . (int)$last_migrated_product_id . " ORDER BY ID ASC 
                                                       LIMIT " .(int)$this->row_count . "
                                                        ) AS product ON product.ID =  object_id
                                                WHERE tt.taxonomy=\'brand\' 
                                                ORDER BY object_id ASC ';
                #section 4
                if ((int)$this->offset === 0) {
                    $q['attribute_group'] = 'SELECT * FROM ' . pSQL($this->tp) . 'woocommerce_attribute_taxonomies';

                    $q['attribute'] = 'SELECT  g.attribute_id,  t.term_id as id_attribute, tax.taxonomy as attribute_group,t.name,t.slug FROM ' . pSQL($this->tp) . 'term_taxonomy as tax 
                                        join ' . pSQL($this->tp) . 'terms as t on t.term_id=tax.term_id
                                        join ( SELECT attribute_id , CONCAT( \'tax_pa_\',  attribute_name) as attribute_name  FROM  ' . pSQL($this->tp) . 'woocommerce_attribute_taxonomies ) g ON g.attribute_name = tax.taxonomy
                                        where tax.taxonomy in ( SELECT CONCAT( \'tax_pa_\',  attribute_name)  FROM  ' . pSQL($this->tp) . 'woocommerce_attribute_taxonomies )
                                        UNION
                                        SELECT  g.attribute_id,  t.term_id as id_attribute, tax.taxonomy as attribute_group,t.name,t.slug FROM ' . pSQL($this->tp) . 'term_taxonomy as tax 
                                        join ' . pSQL($this->tp) . 'terms as t on t.term_id=tax.term_id
                                        join ( SELECT attribute_id , CONCAT( \'pa_\',  attribute_name) as attribute_name  FROM  ' . pSQL($this->tp) . 'woocommerce_attribute_taxonomies ) g ON g.attribute_name = tax.taxonomy
                                        where tax.taxonomy in ( SELECT CONCAT( \'pa_\',  attribute_name)  FROM  ' . pSQL($this->tp) . 'woocommerce_attribute_taxonomies )
                                        ORDER BY id_attribute ASC ';
                }
                // Query for get image ids
                $q['product_imgage_ids'] = "(SELECT  post_meta.* FROM   " . pSQL($this->tp) . "postmeta AS post_meta
                                            INNER JOIN(
                                            SELECT * FROM   " . pSQL($this->tp) . "posts 
                                             WHERE   post_type='product'  AND ID > " . (int)$last_migrated_product_id . " ORDER BY ID ASC 
                                                       LIMIT " .(int)$this->row_count . ") AS product ON product.ID = post_meta.post_id and   post_meta.meta_key = '_product_image_gallery'   and post_meta.meta_value is not null AND post_meta.meta_value <> '' )
                                        union
                                        (SELECT  post_meta.* FROM   " . pSQL($this->tp) . "postmeta AS post_meta
                                        INNER JOIN(
                                                SELECT * FROM   " . pSQL($this->tp) . "posts 
                                                 WHERE   post_type='product'  AND ID > " . (int)$last_migrated_product_id . " ORDER BY ID ASC 
                                                       LIMIT " .(int)$this->row_count . "
                                                    )  AS product ON product.ID = post_meta.post_id and   post_meta.meta_key = '_thumbnail_id' 
                                                and post_meta.meta_value is not null AND post_meta.meta_value <> '')
                                         ORDER BY post_id";
                //Query for get image urls
                $q['product_imgs'] = 'SELECT p.ID as id_image , p.* , pm.* FROM ' . pSQL($this->tp) . 'posts as p 
                                    join ' . pSQL($this->tp) . 'postmeta as pm on p.ID = pm.post_id 
                                    WHERE meta_key = "_wp_attached_file " ';

                $q['product_download'] = "SELECT post_meta.* FROM " . pSQL($this->tp) . "postmeta post_meta
                                        INNER JOIN(
                                            SELECT * FROM " . pSQL($this->tp) . "posts 
                                             WHERE   post_type='product'  AND ID > " . (int)$last_migrated_product_id . " ORDER BY ID ASC 
                                                       LIMIT " .(int)$this->row_count . "
                                                                                )  AS product ON product.ID = post_meta.post_id and   post_meta.meta_key = '_downloadable_files'   
                                            where meta_key = '_downloadable_files'  and (meta_value <> 'a:0:{}' AND meta_value <> '') ORDER BY  post_meta.post_id ASC";
            }
        }

        #region Configuration for grouped tables

        $groupedqueriesconfiguration = array();
        $groupedqueriesconfiguration['attribute_lang'] = 'id_attribute';
        $groupedqueriesconfiguration['attribute_group_lang'] = 'id';
        $groupedqueriesconfiguration['product_meta'] = 'id_product';
        $groupedqueriesconfiguration['product_cat'] = 'id_product';
        $groupedqueriesconfiguration['product_manufacturer'] = 'id_product';
        $groupedqueriesconfiguration['product_lang'] = 'id_product';
        $groupedqueriesconfiguration['product_langs_meta'] = 'post_id';
        $groupedqueriesconfiguration['product_imgage_ids'] = 'post_id';
        $groupedqueriesconfiguration['product_imgs'] = 'post_id';
        $groupedqueriesconfiguration['product_download'] = 'post_id';
        $groupedqueriesconfiguration['product_tag_lang'] = 'id_tag';
        $groupedqueriesconfiguration['product_tag'] = 'object_id';
        $groupedqueriesconfiguration['product_variation'] = 'id_product';
        $groupedqueriesconfiguration['product_combination'] = 'id_product_attribute';
        $q['groupedqueriesconfiguration'] = $groupedqueriesconfiguration;

        return $q;
    }

    // --- Product method:


    public function getDefaultTaxRate($default_countr_iso_code)
    {
        $q = array();
        if (isset($default_countr_iso_code)) {
            $q['default_country_tax_rate'] = 'SELECT tax_rate as rate FROM ' . pSQL($this->tp) . 'woocommerce_tax_rates WHERE tax_rate_country="' . pSQL($default_countr_iso_code) . '"';
        }
        $q['default_country_tax_rate'] = 'SELECT tax_rate as rate FROM ' . pSQL($this->tp) . 'woocommerce_tax_rates';

        return $q;
    }

    // --- Order method:

    public function order($woo_version)
    {
        $q = array();
        if (!$this->recent_data) {
            // # section 1
            $q['order'] = 'SELECT * , ID as id_order  FROM ' . pSQL($this->tp) . 'posts WHERE post_type = "shop_order" AND post_status NOT IN ("inherit","auto-draft","trash","draft") ORDER BY ID  ASC LIMIT  ' .
                (int)$this->offset . ',' . (int)$this->row_count;

            // # section 2

            $q['order_detail'] = 'SELECT *   FROM ' . pSQL($this->tp) . 'postmeta 
                                  INNER JOIN (
                                              SELECT * , ID as id_order  FROM ' . pSQL($this->tp) . 'posts
                                              WHERE post_type = "shop_order" AND post_status NOT IN ("inherit","auto-draft","trash","draft") ORDER BY ID  ASC LIMIT  ' . (int)$this->offset . ',' . (int)$this->row_count . '
                                              )  AS orders ON orders.id_order = post_id 
                                  WHERE meta_key IN ("_customer_user","_order_currency","_payment_method_title","_cart_discount","_order_total","_order_shipping","_order_shipping_tax")
                                  ORDER BY post_id  ASC';

            $q['order_item'] = 'select * from ' . pSQL($this->tp) . 'woocommerce_order_items 
                                INNER JOIN (
                                              SELECT * , ID as id_order  FROM ' . pSQL($this->tp) . 'posts
                                              WHERE post_type = "shop_order" AND post_status NOT IN ("inherit","auto-draft","trash","draft") ORDER BY ID  ASC LIMIT  ' . (int)$this->offset . ',' . (int)$this->row_count . '
                                              )  AS orders ON orders.id_order = order_id 
                                where order_item_type="line_item"  order by order_id';

            $q['order_item_shipping'] = 'select * from ' . pSQL($this->tp) . 'woocommerce_order_items 
                                         INNER JOIN (
                                              SELECT * , ID as id_order  FROM ' . pSQL($this->tp) . 'posts
                                              WHERE post_type = "shop_order" AND post_status NOT IN ("inherit","auto-draft","trash","draft") ORDER BY ID  ASC LIMIT  ' . (int)$this->offset . ',' . (int)$this->row_count . '
                                              )  AS orders ON orders.id_order = order_id 
                                         where order_item_type="shipping" order by order_id';
            $q['order_item_tax'] = 'select * from ' . pSQL($this->tp) . 'woocommerce_order_items 
                                    INNER JOIN (
                                              SELECT * , ID as id_order  FROM ' . pSQL($this->tp) . 'posts
                                              WHERE post_type = "shop_order" AND post_status NOT IN ("inherit","auto-draft","trash","draft") ORDER BY ID  ASC LIMIT  ' . (int)$this->offset . ',' . (int)$this->row_count . '
                                              )  AS orders ON orders.id_order = order_id 
                                    where order_item_type="tax"  order by order_id';
            if ($woo_version == "2.1.12") {
                $q['order_history'] = 'SELECT tr.object_id  AS id_order ,t.name AS post_status , t.term_id FROM ' . pSQL($this->tp) . 'term_relationships AS  tr
                                       INNER JOIN (
                                              SELECT * , ID as id_order  FROM ' . pSQL($this->tp) . 'posts
                                              WHERE post_type = "shop_order" AND post_status NOT IN ("inherit","auto-draft","trash","draft") ORDER BY ID  ASC LIMIT  ' . (int)$this->offset . ',' . (int)$this->row_count . '
                                              )  AS orders ON orders.id_order = tr.object_id 
                                       INNER JOIN  ' . pSQL($this->tp) . 'term_taxonomy as tt on tt.term_taxonomy_id = tr.term_taxonomy_id
                                       INNER JOIN  ' . pSQL($this->tp) . 'terms as t on t.term_id = tt.term_id WHERE  tt.taxonomy ="shop_order_status"  order by tr.object_id';
            } else {
                $q['order_history'] = 'select posts.ID as id_order, posts.post_author as id_employee, posts.post_status, posts.post_date as date_add 
                                      from ' . pSQL($this->tp) . 'posts AS posts
                                      INNER JOIN (
                                              SELECT * , ID as id_order  FROM ' . pSQL($this->tp) . 'posts
                                              WHERE post_type = "shop_order" AND post_status NOT IN ("inherit","auto-draft","trash","draft") ORDER BY ID  ASC LIMIT  ' . (int)$this->offset . ',' . (int)$this->row_count . '
                                              )  AS orders ON orders.id_order = posts.ID 
                                      where posts.post_type="shop_order" order by posts.ID';
            }
            $q['billing_address'] = 'SELECT * FROM ' . pSQL($this->tp) . 'postmeta 
                                    INNER JOIN (
                                              SELECT * , ID as id_order  FROM ' . pSQL($this->tp) . 'posts
                                              WHERE post_type = "shop_order" AND post_status NOT IN ("inherit","auto-draft","trash","draft") ORDER BY ID  ASC LIMIT  ' . (int)$this->offset . ',' . (int)$this->row_count . '
                                              )  AS orders ON orders.id_order = post_id 
                                     WHERE   meta_key IN  ("_customer_user","_billing_first_name","_billing_last_name","_billing_company","_billing_email","_billing_phone","_billing_country","_billing_address_1","_billing_address_2","_billing_city","_billing_state","_billing_postcode")
                                     ORDER BY post_id';

            $q['shipping_address'] = 'SELECT * FROM ' . pSQL($this->tp) . 'postmeta 
                                      INNER JOIN (
                                              SELECT * , ID as id_order  FROM ' . pSQL($this->tp) . 'posts
                                              WHERE post_type = "shop_order" AND post_status NOT IN ("inherit","auto-draft","trash","draft") ORDER BY ID  ASC LIMIT  ' . (int)$this->offset . ',' . (int)$this->row_count . '
                                              )  AS orders ON orders.id_order = post_id 
                                      WHERE  meta_key IN  ("_customer_user","_shipping_first_name","_shipping_last_name","_shipping_company","_shipping_country","_shipping_address_1","_shipping_address_2","_shipping_city","_shipping_state","_shipping_postcode")
                                      ORDER BY post_id';


            // # section 3

            $q['line'] = 'SELECT * FROM ' . pSQL($this->tp) . 'woocommerce_order_itemmeta AS order_item_meta
                          INNER JOIN (
                             SELECT * FROM ' . pSQL($this->tp) . 'woocommerce_order_items 
                                INNER JOIN (
                                              SELECT * , ID as id_order  FROM ' . pSQL($this->tp) . 'posts
                                              WHERE post_type = "shop_order" AND post_status NOT IN ("inherit","auto-draft","trash","draft") ORDER BY ID  ASC LIMIT  ' . (int)$this->offset . ',' . (int)$this->row_count . '
                                              )  AS orders ON orders.id_order = order_id 
                                WHERE order_item_type="line_item"  ORDER BY order_id
                                ) AS order_line ON order_line.order_item_id = order_item_meta.order_item_id';

            $q['tax'] = 'SELECT i.order_id,im.* FROM ' . pSQL($this->tp) . 'woocommerce_order_items as i join ' . pSQL($this->tp) . 'woocommerce_order_itemmeta as im on im.order_item_id=i.order_item_id 
                         INNER JOIN(
                             SELECT * FROM ' . pSQL($this->tp) . 'woocommerce_order_items 
                                INNER JOIN (
                                          SELECT * , ID as id_order  FROM ' . pSQL($this->tp) . 'posts
                                          WHERE post_type = "shop_order" AND post_status NOT IN ("inherit","auto-draft","trash","draft") ORDER BY ID  ASC LIMIT  ' . (int)$this->offset . ',' . (int)$this->row_count . '
                                          )  AS orders ON orders.id_order = order_id WHERE order_item_type="tax"  ORDER BY order_id
                                    )  AS item_tax ON item_tax.order_item_id = im.order_item_id';

            $q['shipping'] = 'SELECT i.order_id,im.* FROM ' . pSQL($this->tp) . 'woocommerce_order_items as i join ' . pSQL($this->tp) . 'woocommerce_order_itemmeta as im on im.order_item_id=i.order_item_id 
                              INNER JOIN(
                                  SELECT * FROM ' . pSQL($this->tp) . 'woocommerce_order_items 
                                     INNER JOIN (
                                          SELECT * , ID as id_order  FROM ' . pSQL($this->tp) . 'posts
                                          WHERE post_type = "shop_order" AND post_status NOT IN ("inherit","auto-draft","trash","draft") ORDER BY ID  ASC LIMIT  ' . (int)$this->offset . ',' . (int)$this->row_count . '
                                          )  AS orders ON orders.id_order = order_id WHERE order_item_type="shipping" ORDER BY order_id
                              ) AS order_shipping ON  order_shipping.order_item_id = im.order_item_id ';
        } else {
            // # section 1
            $last_migrated_order_id = Configuration::get('woomigrationpro_order');
            $q['order'] = 'SELECT * , ID as id_order  FROM ' . pSQL($this->tp) . 'posts WHERE post_type = "shop_order" AND ID > ' . (int)$last_migrated_order_id . ' AND post_status NOT IN ("inherit","auto-draft","trash","draft") ORDER BY ID  ASC LIMIT  '. (int)$this->row_count;

            // # section 2

            $q['order_detail'] = 'SELECT *   FROM ' . pSQL($this->tp) . 'postmeta 
                                  INNER JOIN (
                                              SELECT * , ID as id_order  FROM ' . pSQL($this->tp) . 'posts
                                              WHERE post_type = "shop_order" AND ID > ' . (int)$last_migrated_order_id . ' AND  post_status NOT IN ("inherit","auto-draft","trash","draft") ORDER BY ID  ASC LIMIT  ' . (int)$this->row_count . '
                                              )  AS orders ON orders.id_order = post_id 
                                  WHERE meta_key IN ("_customer_user","_order_currency","_payment_method_title","_cart_discount","_order_total","_order_shipping","_order_shipping_tax")
                                  ORDER BY post_id  ASC';

            $q['order_item'] = 'select * from ' . pSQL($this->tp) . 'woocommerce_order_items 
                                INNER JOIN (
                                              SELECT * , ID as id_order  FROM ' . pSQL($this->tp) . 'posts
                                              WHERE post_type = "shop_order" AND ID > ' . (int)$last_migrated_order_id . ' AND post_status NOT IN ("inherit","auto-draft","trash","draft") ORDER BY ID  ASC LIMIT  ' . (int)$this->row_count . '
                                              )  AS orders ON orders.id_order = order_id 
                                where order_item_type="line_item"  order by order_id';

            $q['order_item_shipping'] = 'select * from ' . pSQL($this->tp) . 'woocommerce_order_items 
                                         INNER JOIN (
                                              SELECT * , ID as id_order  FROM ' . pSQL($this->tp) . 'posts
                                              WHERE post_type = "shop_order" AND ID > ' . (int)$last_migrated_order_id . ' AND post_status NOT IN ("inherit","auto-draft","trash","draft") ORDER BY ID  ASC LIMIT  ' . (int)$this->row_count . '
                                              )  AS orders ON orders.id_order = order_id 
                                         where order_item_type="shipping" order by order_id';
            $q['order_item_tax'] = 'select * from ' . pSQL($this->tp) . 'woocommerce_order_items 
                                    INNER JOIN (
                                              SELECT * , ID as id_order  FROM ' . pSQL($this->tp) . 'posts
                                              WHERE post_type = "shop_order" AND ID > ' . (int)$last_migrated_order_id . ' AND post_status NOT IN ("inherit","auto-draft","trash","draft") ORDER BY ID  ASC LIMIT  ' . (int)$this->row_count . '
                                              )  AS orders ON orders.id_order = order_id 
                                    where order_item_type="tax"  order by order_id';
            if ($woo_version == "2.1.12") {
                $q['order_history'] = 'SELECT tr.object_id  AS id_order ,t.name AS post_status , t.term_id FROM ' . pSQL($this->tp) . 'term_relationships AS  tr
                                       INNER JOIN (
                                              SELECT * , ID as id_order  FROM ' . pSQL($this->tp) . 'posts
                                              WHERE post_type = "shop_order" AND ID > ' . (int)$last_migrated_order_id . ' AND post_status NOT IN ("inherit","auto-draft","trash","draft") ORDER BY ID  ASC LIMIT  ' . (int)$this->row_count . '
                                              )  AS orders ON orders.id_order = tr.object_id 
                                       INNER JOIN  ' . pSQL($this->tp) . 'term_taxonomy as tt on tt.term_taxonomy_id = tr.term_taxonomy_id
                                       INNER JOIN  ' . pSQL($this->tp) . 'terms as t on t.term_id = tt.term_id WHERE  tt.taxonomy ="shop_order_status"  order by tr.object_id';
            } else {
                $q['order_history'] = 'select posts.ID as id_order, posts.post_author as id_employee, posts.post_status, posts.post_date as date_add 
                                      from ' . pSQL($this->tp) . 'posts AS posts
                                      INNER JOIN (
                                              SELECT * , ID as id_order  FROM ' . pSQL($this->tp) . 'posts
                                              WHERE post_type = "shop_order" AND ID > ' . (int)$last_migrated_order_id . '  AND post_status NOT IN ("inherit","auto-draft","trash","draft") ORDER BY ID  ASC LIMIT  ' . (int)$this->row_count . '
                                              )  AS orders ON orders.id_order = posts.ID 
                                      where posts.post_type="shop_order" order by posts.ID';
            }

            $q['billing_address'] = 'SELECT * FROM ' . pSQL($this->tp) . 'postmeta 
                                    INNER JOIN (
                                              SELECT * , ID as id_order  FROM ' . pSQL($this->tp) . 'posts
                                              WHERE post_type = "shop_order" AND ID > ' . (int)$last_migrated_order_id . ' AND post_status NOT IN ("inherit","auto-draft","trash","draft") ORDER BY ID  ASC LIMIT  ' . (int)$this->row_count . '
                                              )  AS orders ON orders.id_order = post_id 
                                     WHERE   meta_key IN  ("_customer_user","_billing_first_name","_billing_last_name","_billing_company","_billing_email","_billing_phone","_billing_country","_billing_address_1","_billing_address_2","_billing_city","_billing_state","_billing_postcode")
                                     ORDER BY post_id';

            $q['shipping_address'] = 'SELECT * FROM ' . pSQL($this->tp) . 'postmeta 
                                      INNER JOIN (
                                              SELECT * , ID as id_order  FROM ' . pSQL($this->tp) . 'posts
                                              WHERE post_type = "shop_order" AND ID > ' . (int)$last_migrated_order_id . ' AND post_status NOT IN ("inherit","auto-draft","trash","draft") ORDER BY ID  ASC LIMIT  ' . (int)$this->row_count . '
                                              )  AS orders ON orders.id_order = post_id 
                                      WHERE  meta_key IN  ("_customer_user","_shipping_first_name","_shipping_last_name","_shipping_company","_shipping_country","_shipping_address_1","_shipping_address_2","_shipping_city","_shipping_state","_shipping_postcode")
                                      ORDER BY post_id';


            // # section 3

            $q['line'] = 'SELECT * FROM ' . pSQL($this->tp) . 'woocommerce_order_itemmeta AS order_item_meta
                          INNER JOIN (
                             SELECT * FROM ' . pSQL($this->tp) . 'woocommerce_order_items 
                                INNER JOIN (
                                              SELECT * , ID as id_order  FROM ' . pSQL($this->tp) . 'posts
                                              WHERE post_type = "shop_order" AND ID > ' . (int)$last_migrated_order_id . ' AND post_status NOT IN ("inherit","auto-draft","trash","draft") ORDER BY ID  ASC LIMIT  ' . (int)$this->row_count . '
                                              )  AS orders ON orders.id_order = order_id 
                                WHERE order_item_type="line_item"  ORDER BY order_id
                                ) AS order_line ON order_line.order_item_id = order_item_meta.order_item_id';

            $q['tax'] = 'SELECT i.order_id,im.* FROM ' . pSQL($this->tp) . 'woocommerce_order_items as i join ' . pSQL($this->tp) . 'woocommerce_order_itemmeta as im on im.order_item_id=i.order_item_id 
                         INNER JOIN(
                             SELECT * FROM ' . pSQL($this->tp) . 'woocommerce_order_items 
                                INNER JOIN (
                                          SELECT * , ID as id_order  FROM ' . pSQL($this->tp) . 'posts
                                          WHERE post_type = "shop_order" AND ID > ' . (int)$last_migrated_order_id . ' AND post_status NOT IN ("inherit","auto-draft","trash","draft") ORDER BY ID  ASC LIMIT  ' . (int)$this->row_count . '
                                          )  AS orders ON orders.id_order = order_id WHERE order_item_type="tax"  ORDER BY order_id
                                    )  AS item_tax ON item_tax.order_item_id = im.order_item_id';

            $q['shipping'] = 'SELECT i.order_id,im.* FROM ' . pSQL($this->tp) . 'woocommerce_order_items as i join ' . pSQL($this->tp) . 'woocommerce_order_itemmeta as im on im.order_item_id=i.order_item_id 
                              INNER JOIN(
                                  SELECT * FROM ' . pSQL($this->tp) . 'woocommerce_order_items 
                                     INNER JOIN (
                                          SELECT * , ID as id_order  FROM ' . pSQL($this->tp) . 'posts
                                          WHERE post_type = "shop_order" AND ID > ' . (int)$last_migrated_order_id . ' AND post_status NOT IN ("inherit","auto-draft","trash","draft") ORDER BY ID  ASC LIMIT  ' . (int)$this->row_count . '
                                          )  AS orders ON orders.id_order = order_id WHERE order_item_type="shipping" ORDER BY order_id
                              ) AS order_shipping ON  order_shipping.order_item_id = im.order_item_id ';
        }

        #region Configuration for grouped tables

        $groupedqueriesconfiguration = array();
        //For Product second related queries
        $q['groupedqueriesconfiguration'] = $groupedqueriesconfiguration;

        return $q;
    }

    // --- Customer methods:

    public function customers()
    {
        $q = array();
        if ($this->recent_data) {
            $last_migrated_customer_id = Configuration::get('woomigrationpro_customer');
            $q['customer'] = 'SELECT ID as id_customer, user_activation_key AS secure_key, display_name AS name, user_email AS email, user_pass AS passwd, user_registered AS date_add,user_url AS website  FROM ' . pSQL($this->tp) . 'users AS usr JOIN
                ' . pSQL($this->tp) . 'usermeta AS usrmeta ON usrmeta.user_id=usr.id WHERE usrmeta.meta_key="' . pSQL($this->tp) . 'capabilities" AND ID >' . (int)$last_migrated_customer_id . ' AND usrmeta.meta_value LIKE "%customer%" ORDER BY ID ASC LIMIT ' . (int)$this->row_count;

            $q['customer_second'] = 'SELECT user_id as id_customer, u.* FROM ' . pSQL($this->tp) . 'usermeta as u 
                                 INNER JOIN (
                                            SELECT ID as id_customer, user_activation_key AS secure_key, display_name AS name, user_email AS email, user_pass AS passwd, user_registered AS date_add,user_url AS website  
                                            FROM ' . pSQL($this->tp) . 'users AS usr JOIN
                                            ' . pSQL($this->tp) . 'usermeta AS usrmeta ON usrmeta.user_id=usr.id WHERE usrmeta.meta_key="' . pSQL($this->tp) . 'capabilities" AND ID >' . (int)$last_migrated_customer_id . ' AND usrmeta.meta_value LIKE "%customer%" ORDER BY ID ASC LIMIT ' . (int)$this->row_count . '
                                            ) AS user ON user.id_customer = u.user_id 
                                 WHERE u.meta_key IN ("first_name","last_name")';
            $q['addresses'] = 'SELECT * FROM ' . pSQL($this->tp) . 'usermeta 
                                         INNER JOIN (
                                            SELECT ID as id_customer, user_activation_key AS secure_key, display_name AS name, user_email AS email, user_pass AS passwd, user_registered AS date_add,user_url AS website  
                                            FROM ' . pSQL($this->tp) . 'users AS usr JOIN
                                            ' . pSQL($this->tp) . 'usermeta AS usrmeta ON usrmeta.user_id=usr.id WHERE usrmeta.meta_key="' . pSQL($this->tp) . 'capabilities" AND ID >' . (int)$last_migrated_customer_id . ' AND usrmeta.meta_value LIKE "%customer%" ORDER BY ID ASC LIMIT ' . (int)$this->row_count . '
                                            ) AS user ON user.id_customer = user_id  
                                     WHERE meta_key IN  
                                     ("billing_first_name","billing_last_name","billing_company","billing_email","billing_phone","billing_country","billing_address_1","billing_address_2","billing_city","billing_state","billing_postcode","last_update","shipping_first_name","shipping_last_name","shipping_company","shipping_email","shipping_phone","shipping_country","shipping_address_1","shipping_address_2","shipping_city","shipping_state","shipping_postcode","last_update") ';

//            $q['billing_address'] = 'SELECT * FROM ' . pSQL($this->tp) . 'usermeta
//                                         INNER JOIN (
//                                            SELECT ID as id_customer, user_activation_key AS secure_key, display_name AS name, user_email AS email, user_pass AS passwd, user_registered AS date_add,user_url AS website
//                                            FROM ' . pSQL($this->tp) . 'users AS usr JOIN
//                                            ' . pSQL($this->tp) . 'usermeta AS usrmeta ON usrmeta.user_id=usr.id WHERE usrmeta.meta_key="'. pSQL($this->tp) . 'capabilities" AND usrmeta.meta_value LIKE "%customer%" ORDER BY ID ASC LIMIT ' . (int)$this->row_count. '
//                                            ) AS user ON user.id_customer = user_id
//                                     WHERE  user.ID >' . (int)$last_migrated_customer_id . ' AND meta_key IN
//                                     ("billing_first_name","billing_last_name","billing_company","billing_email","billing_phone","billing_country","billing_address_1","billing_address_2","billing_city","billing_state","billing_postcode","last_update")
//                                     ORDER BY user_id';
//
//            $q['shipping_address'] = 'SELECT * FROM ' . pSQL($this->tp) . 'usermeta
//                                      INNER JOIN (
//                                            SELECT ID as id_customer, user_activation_key AS secure_key, display_name AS name, user_email AS email, user_pass AS passwd, user_registered AS date_add,user_url AS website
//                                            FROM ' . pSQL($this->tp) . 'users AS usr JOIN
//                                            ' . pSQL($this->tp) . 'usermeta AS usrmeta ON usrmeta.user_id=usr.id WHERE usrmeta.meta_key="'. pSQL($this->tp) . 'capabilities" AND usrmeta.meta_value LIKE "%customer%" ORDER BY ID ASC LIMIT ' . (int)$this->row_count. '
//                                            ) AS user ON user.id_customer = user_id
//                                      WHERE user.ID >' . (int)$last_migrated_customer_id . ' AND meta_key IN
//                                      ("shipping_first_name","shipping_last_name","shipping_company","shipping_email","shipping_phone","shipping_country","shipping_address_1","shipping_address_2","shipping_city","shipping_state","shipping_postcode","last_update")
//                                      ORDER BY user_id';
        } else {
            $q['customer'] = 'SELECT ID as id_customer, user_activation_key AS secure_key, display_name AS name, user_email AS email, user_pass AS passwd, user_registered AS date_add,user_url AS website  FROM ' . pSQL($this->tp) . 'users AS usr JOIN
                ' . pSQL($this->tp) . 'usermeta AS usrmeta ON usrmeta.user_id=usr.id WHERE usrmeta.meta_key="' . pSQL($this->tp) . 'capabilities" AND usrmeta.meta_value LIKE "%customer%" ORDER BY ID ASC LIMIT ' . (int)$this->offset . ',' . (int)$this->row_count;

            $q['customer_second'] = 'SELECT user_id as id_customer, u.* FROM ' . pSQL($this->tp) . 'usermeta as u 
                                 INNER JOIN (
                                            SELECT ID as id_customer, user_activation_key AS secure_key, display_name AS name, user_email AS email, user_pass AS passwd, user_registered AS date_add,user_url AS website  
                                            FROM ' . pSQL($this->tp) . 'users AS usr JOIN
                                            ' . pSQL($this->tp) . 'usermeta AS usrmeta ON usrmeta.user_id=usr.id WHERE usrmeta.meta_key="' . pSQL($this->tp) . 'capabilities" AND usrmeta.meta_value LIKE "%customer%" ORDER BY ID ASC LIMIT ' . (int)$this->offset . ',' . (int)$this->row_count . '
                                            ) AS user ON user.id_customer = u.user_id      
                                 WHERE  u.meta_key IN ("first_name","last_name") ORDER BY u.user_id';

            $q['customer_secondd'] = 'SELECT user_id as id_customer, u.* FROM ' . pSQL($this->tp) . 'usermeta as u 
                                 INNER JOIN (
                                            SELECT ID as id_customer, user_activation_key AS secure_key, display_name AS name, user_email AS email, user_pass AS passwd, user_registered AS date_add,user_url AS website  
                                            FROM ' . pSQL($this->tp) . 'users AS usr JOIN
                                            ' . pSQL($this->tp) . 'usermeta AS usrmeta ON usrmeta.user_id=usr.id WHERE usrmeta.meta_key="' . pSQL($this->tp) . 'capabilities" AND usrmeta.meta_value LIKE "%customer%" ORDER BY ID ASC LIMIT ' . (int)$this->offset . ',' . (int)$this->row_count . '
                                            ) AS user ON user.id_customer = u.user_id      
                                 WHERE  u.meta_key IN ("first_name","last_name") ORDER BY u.user_id';

            $q['addresses'] = 'SELECT * FROM ' . pSQL($this->tp) . 'usermeta 
                                         INNER JOIN (
                                            SELECT ID as id_customer, user_activation_key AS secure_key, display_name AS name, user_email AS email, user_pass AS passwd, user_registered AS date_add,user_url AS website  
                                            FROM ' . pSQL($this->tp) . 'users AS usr JOIN
                                            ' . pSQL($this->tp) . 'usermeta AS usrmeta ON usrmeta.user_id=usr.id WHERE usrmeta.meta_key="' . pSQL($this->tp) . 'capabilities" AND usrmeta.meta_value LIKE "%customer%" ORDER BY ID ASC LIMIT ' . (int)$this->offset . ',' . (int)$this->row_count . '
                                            ) AS user ON user.id_customer = user_id  
                                     WHERE  meta_key IN  
                                     ("billing_first_name","billing_last_name","billing_company","billing_email","billing_phone","billing_country","billing_address_1","billing_address_2","billing_city","billing_state","billing_postcode","last_update","shipping_first_name","shipping_last_name","shipping_company","shipping_email","shipping_phone","shipping_country","shipping_address_1","shipping_address_2","shipping_city","shipping_state","shipping_postcode","last_update")
                                     ORDER BY user_id';

//            $q['billing_address'] = 'SELECT * FROM ' . pSQL($this->tp) . 'usermeta
//                                         INNER JOIN (
//                                            SELECT ID as id_customer, user_activation_key AS secure_key, display_name AS name, user_email AS email, user_pass AS passwd, user_registered AS date_add,user_url AS website
//                                            FROM ' . pSQL($this->tp) . 'users AS usr JOIN
//                                            ' . pSQL($this->tp) . 'usermeta AS usrmeta ON usrmeta.user_id=usr.id WHERE usrmeta.meta_key="'. pSQL($this->tp) . 'capabilities" AND usrmeta.meta_value LIKE "%customer%" ORDER BY ID ASC LIMIT ' . (int)$this->offset . ',' . (int)$this->row_count. '
//                                            ) AS user ON user.id_customer = user_id
//                                     WHERE  meta_key IN
//                                     ("billing_first_name","billing_last_name","billing_company","billing_email","billing_phone","billing_country","billing_address_1","billing_address_2","billing_city","billing_state","billing_postcode","last_update")
//                                     ORDER BY user_id';
//
//            $q['shipping_address'] = 'SELECT * FROM ' . pSQL($this->tp) . 'usermeta
//                                      INNER JOIN (
//                                            SELECT ID as id_customer, user_activation_key AS secure_key, display_name AS name, user_email AS email, user_pass AS passwd, user_registered AS date_add,user_url AS website
//                                            FROM ' . pSQL($this->tp) . 'users AS usr JOIN
//                                            ' . pSQL($this->tp) . 'usermeta AS usrmeta ON usrmeta.user_id=usr.id WHERE usrmeta.meta_key="'. pSQL($this->tp) . 'capabilities" AND usrmeta.meta_value LIKE "%customer%" ORDER BY ID ASC LIMIT ' . (int)$this->offset . ',' . (int)$this->row_count. '
//                                            ) AS user ON user.id_customer = user_id
//                                      WHERE  meta_key IN
//                                      ("shipping_first_name","shipping_last_name","shipping_company","shipping_email","shipping_phone","shipping_country","shipping_address_1","shipping_address_2","shipping_city","shipping_state","shipping_postcode","last_update")
//                                      ORDER BY user_id';
        }


        #region Configuration for grouped tables

        $groupedqueriesconfiguration = array();
        //For Product second related queries
        $groupedqueriesconfiguration['addresses'] = 'id_customer';
        $q['groupedqueriesconfiguration'] = $groupedqueriesconfiguration;

        return $q;
    }


    public function address($id_customers)
    {
        $q = array();

        return $q;
    }

    // helper query
    public function getCountries()
    {
        $q = array();
        $q['countries'] = 'SELECT * FROM ';
        $q['continents'] = 'SELECT * FROM ';
        return $q;
    }
}
