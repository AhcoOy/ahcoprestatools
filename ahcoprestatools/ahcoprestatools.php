<?php

/**
 * 
 * @author Heikki Pals
 * 
 * Ahco OY
 * 
 * 2019 
 */
if (!defined('_PS_VERSION_'))
    exit;

class ahcoprestatools extends Module {

    /**
     *
     * @var type 
     */
    static protected $debug = array();

    /**
     *
     * @var type 
     * 
     */
    static protected $dbTables = array();

    /**
     *
     *
     *
     */
    public function __construct() {
        $this->name = 'ahcoprestatools';
        $this->tab = 'administration';
        $this->version = '1.0';
        $this->author = 'Ahco';
        $this->need_instance = 0;
        $this->displayName = $this->l('Prestashop Tools By Ahco');
        $this->description = $this->l('Data Tools');
        parent::__construct();
    }

    /**
     *
     */
    protected function debug($mixed_object) {
        if (empty(self::$debug)) {
            self::$debug[] = array(
                'time' => date('Y-m-d h:i:s'),
                'debug_object' => array(
                    'function' => __FUNCTION__,
                    'line' => __LINE__,
                    'Module version: ' => $this->version,
                    'Prestashop version: ' => _PS_VERSION_,
                    'shop_id' => $this->context->shop->id,
                    'request_uri' => $_SERVER['SERVER_NAME'] . ' ' . $_SERVER['REQUEST_URI'],
                    'Shop Email' => Configuration::get('PS_SHOP_EMAIL'),
                    'HTTP_USER_AGENT' => $_SERVER['HTTP_USER_AGENT'],
                    '_GET' => $_GET,
                    '_POST' => $_POST,
                    '_SERVER' => $_SERVER,
                    'employee name' => isset($this->context->employee->email) ? ( $this->context->employee->firstname . ' ' . $this->context->employee->lastname) : 'n/a',
                    'employee email' => isset($this->context->employee->email) ? $this->context->employee->email : 'n/a',
                    'customer email' => isset($this->context->customer->email) ? $this->context->customer->email : 'n/a',
                    'customer name' => isset($this->context->customer->email) ? ( $this->context->customer->firstname . ' ' . $this->context->customer->lastname ) : 'n/a'
                ),
            );
        }

        if (is_string($mixed_object)) {
            $mixed_object = htmlspecialchars($mixed_object);
        }

        self::$debug[] = array(
            'time' => date('Y-m-d h:i:s'),
            'debug_object' => $mixed_object
        );
    }

    /**
     * 
     * @return type
     */
    public static function getModuleDebug() {
        return self::$debug;
    }

    /**
     * 
     */
    public function writeErrorLogs() {
        
    }

    /**
     *
     * Return Prestashop version.
     * @return <type>
     *
     * 
     *
     */
    protected function psV() {
        return substr(_PS_VERSION_, 0, 3);
    }

    /**
     *
     * @return <type>
     *
     * Basic installation
     *
     */
    public function install() {
        if (!parent::install()) {
            return false;
        }
        return true;
    }

    /*
     *
     * Basic uninstall
     */

    public function uninstall() {
        parent::uninstall();
        return true;
    }

    /**
     *  CONFIG!
     *
     */
    public function getContent() {
        global $smarty;

        $smarty->assign(array(
            'deleteSqls' => array(),
        ));
        if (isset($_POST['deleteOrder']) && isset($_POST['id_order'])) {
            $deleteOrderId = (int) $_POST['id_order'];
            if ($deleteOrderId) {
                $smarty->assign(array(
                    'deleteSqls' => $this->getDeleteSqls($deleteOrderId, _DB_PREFIX_ . 'orders', 'id_order')
                ));
                $this->deleteDbEntityWithRelations($deleteOrderId, _DB_PREFIX_ . 'orders', 'id_order');
            }
        }

        $smarty->assign(array(
            'duplicateOrders' => $this->getDublicateOrders(),
            'tablesStructrures' => $this->getTablesStructures(),
        ));


        $smarty->assign(array(
            'debugLogs' => htmlspecialchars(print_r(self::$debug, true))
        ));
        $getContent = $this->display(__FILE__, 'getContent.tpl');
        return $getContent;
    }

    /**
     * One cart has one order. By erros somestimes cart has many orders
     * returns array of dupcliate orders
     */
    public function getDublicateOrders() {
        $duplicateCountSql = 'SELECT COUNT(*) as order_count , id_cart FROM `' . _DB_PREFIX_ . 'orders`  '
                . ' GROUP BY id_cart '
                . ' HAVING order_count >= 2 ';
        $duplicateCounts = Db::getInstance()->ExecuteS($duplicateCountSql);
        $this->debug(array(__FUNCTION__, __LINE__, compact('duplicateCounts')));
        if (!$duplicateCounts) {
            return;
        }

        $cartsWithMultipleOrders = array();
        foreach ($duplicateCounts as $duplicateCount) {
            $cartsWithMultipleOrders[] = $duplicateCount['id_cart'];
        }

        $displayOrdersSql = '
            SELECT SQL_CALC_FOUND_ROWS a.`id_order`, 
                                    a.`id_cart`, 
                                    `reference`, 
                                    `total_paid_tax_incl`, 
                                    `payment`, 
                                    a.`date_add`                                       AS 
                                    `date_add`, 
                                    a.id_currency, 
                                    a.id_order                                         AS 
                                    id_pdf, 
                                    CONCAT(LEFT(c.`firstname`, 1)," " , c.`lastname`) AS 
                                    `customer`, 
                                    osl.`name`                                         AS 
                                    `osname`, 
                                    os.`color`, 
                                    IF((SELECT so.id_order 
                                        FROM   `' . _DB_PREFIX_ . 'orders` so 
                                        WHERE  so.id_customer = a.id_customer 
                                               AND so.id_order < a.id_order 
                                        LIMIT  1) > 0, 0, 1)                           AS 
                                    new, 
                                    country_lang.name                                  AS 
                                    cname, 
                                    IF(a.valid, 1, 0) 
                                    badge_success, 
                                    shop.name                                          AS 
                                    shop_name 
                FROM   `' . _DB_PREFIX_ . 'orders` a 
                       LEFT JOIN `' . _DB_PREFIX_ . 'customer` c 
                              ON ( c.`id_customer` = a.`id_customer` ) 
                       INNER JOIN `' . _DB_PREFIX_ . 'address` address 
                               ON address.id_address = a.id_address_delivery 
                       INNER JOIN `' . _DB_PREFIX_ . 'country` country 
                               ON address.id_country = country.id_country 
                       INNER JOIN `' . _DB_PREFIX_ . 'country_lang` country_lang 
                               ON ( country.`id_country` = country_lang.`id_country` 
                                    AND country_lang.`id_lang` = 1 ) 
                       LEFT JOIN `' . _DB_PREFIX_ . 'order_state` os 
                              ON ( os.`id_order_state` = a.`current_state` ) 
                       LEFT JOIN `' . _DB_PREFIX_ . 'order_state_lang` osl 
                              ON ( os.`id_order_state` = osl.`id_order_state` 
                                   AND osl.`id_lang` = 1 ) 
                       LEFT JOIN `' . _DB_PREFIX_ . 'shop` shop 
                              ON a.`id_shop` = shop.`id_shop` 
                WHERE a.id_cart IN ( ' . implode('   ,  ', $cartsWithMultipleOrders) . ' ) 
                ORDER  BY a.id_cart ASC 
            ';

        $orders = Db::getInstance()->ExecuteS($displayOrdersSql);
        $this->debug(array(__FUNCTION__, __LINE__, compact('orders')));
        return $orders;
    }

    /**
     * 
     */
    public function getTablesStructures() {
        if (self::$dbTables) {
            return self::$dbTables;
        }
        $tables = Db::getInstance()->ExecuteS('SHOW TABLES;');
        $dbTables = array();
        foreach ($tables as $table) {
            $dbTableName = current($table);
            if (strpos($dbTableName, _DB_PREFIX_) === 0) {
                $dbTableStructure = Db::getInstance()->ExecuteS('describe ' . $dbTableName);
                $dbTables[] = compact('dbTableName', 'dbTableStructure');
            }
        }
        $this->debug(array(__FUNCTION__, __LINE__, 'no table found', compact('dbTables')));
        self::$dbTables = $dbTables;
        return self::$dbTables;
    }

    /**
     * 
     * @param type $dbTableName
     * @return type string
     */
    public function getDbTablePrimaryKeyFieldName($dbTableName) {
        $dbTables = $this->getTablesStructures();
        $primaryKeyFieldName = $tblFound = null;
        foreach ($dbTables as $dbTable) {
            if ($dbTable['dbTableName'] == $dbTableName) {
                $tblFound = $dbTable;
                break;
            }
        }

        if (!$tblFound) {
            $this->debug(array(__FUNCTION__, __LINE__, 'no table found', compact('dbTableName', 'primaryKeyFieldName')));
            return null;
        }

        foreach ($tblFound['dbTableStructure'] as $column) {
            if ($column['Key'] == 'PRI') {
                $primaryKeyFieldName = $column['Field'];
            }
        }
        $this->debug(array(__FUNCTION__, __LINE__, 'primaryKeyFieldName', compact('dbTableName', 'primaryKeyFieldName')));
        return $primaryKeyFieldName;
    }

    /**
     * 
     * @param type $dbTableName
     * @return type string
     */
    public function getDbTablesWithFieldName($fieldName) {
        $dbTables = $this->getTablesStructures();
        $resultTables = array();
        foreach ($dbTables as $dbTable) {
            foreach ($dbTable['dbTableStructure'] as $column) {
                if ($column['Field'] == $fieldName) {
                    $resultTables[] = $dbTable['dbTableName'];
                    break;
                }
            }
        }
        return $resultTables;
    }

    /**
     * 
     * @param type $primaryKeyValue
     * @param type $primaryTable
     * @param type $foreignRelationColumn
     * @return type array()
     */
    public function getDeleteSqls($primaryKeyValue, $primaryTable, $foreignRelationColumn) {
        if (!$primaryKeyValue) {
            return array();
        }
        $tablePrimaryKeyFieldName = $this->getDbTablePrimaryKeyFieldName($primaryTable);
        if (!$tablePrimaryKeyFieldName) {
            $this->debug(array(__FUNCTION__, __LINE__, 'NO !$tablePrimaryKeyFieldName', compact('primaryKeyValue', 'primaryTable', 'foreignRelationColumn', 'tablePrimaryKeyFieldName')));
            return array();
        }
        $sqls = array();
        $sqls[] = 'BEGIN;';
        $sqls[] = 'DELETE FROM `' . $primaryTable . '` WHERE '
                . '`' . $tablePrimaryKeyFieldName . '` = '
                . (int) $primaryKeyValue
                . ' LIMIT 1;';

        $entityRelatedTables = $this->getDbTablesWithFieldName($foreignRelationColumn);
        if (!$entityRelatedTables) {
            $this->debug(array(__FUNCTION__, __LINE__, 'NO $entityRelatedTables', compact('primaryKeyValue', 'primaryTable', 'foreignRelationColumn', 'tablePrimaryKeyFieldName', 'entityRelatedTables')));
        }
        foreach ($entityRelatedTables as $entityRelatedTable) {
            $sqls[] = 'DELETE FROM `' . $entityRelatedTable . '` WHERE '
                    . '`' . $foreignRelationColumn . '` = '
                    . (int) $primaryKeyValue;
        }

        $sqls[] = ' COMMIT ;';
        if (!$entityRelatedTables) {
            $this->debug(array(__FUNCTION__, __LINE__, 'NO $entityRelatedTables', compact('sqls')));
        }
        return $sqls;
    }

    /**
     * 
     * 
     * @param type $primaryKeyValue   integer value      e.g. 1
     * @param type $primaryTable      string      e.g. orders
     * @param type $foreignRelationColumn  string  e.g. id_order
     * @return boolean
     * 
     */
    public function deleteDbEntityWithRelations($primaryKeyValue, $primaryTable, $foreignRelationColumn) {
        $delSqls = $this->getDeleteSqls($primaryKeyValue, $primaryTable, $foreignRelationColumn);
        foreach ($delSqls as $delSql) {
            $success = true;
            try {
                if (!Db::getInstance()->execute($delSql)) {
                    $error = Db::getInstance()->getNumberError();
                    if ($error) {
                        $this->debug(array(
                            __FUNCTION__,
                            __LINE__,
                            'SQL' => $s,
                            'getNumberError' => $error,
                            'getMsgError' => Db::getInstance()->getNumberError(),
                        ));
                        $success = false;
                    }
                    $success = false;
                }
            } catch (Exception $e) {
                $this->debug(array(__FUNCTION__, __LINE__, 'Exception on SQL : ' . $delSql));
                $this->debug(array(__FUNCTION__, __LINE__, $e->getCode()));
                $this->debug(array(__FUNCTION__, __LINE__, $e->getMessage()));
                $success = false;
            }

            if ($success == false) {
                return false;
            } else {
                $this->debug(__FUNCTION__ . '() SUCCESS: ' . $delSql);
            }
        }
        return true;
    }

}
