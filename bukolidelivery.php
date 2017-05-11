<?php
/**
 * BukoliDelivery: module for PrestaShop 1.5-1.6
 *
 * @author    muratbastas <muratbsts@gmail.com>
 * @copyright 2017 muratbastas
 * @license   http://addons.prestashop.com/en/content/12-terms-and-conditions-of-use Terms and conditions of use (EULA)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_.'bukolidelivery/classes/BukoliDetails.php';

class BukoliDelivery extends CarrierModule
{
    const CONF_CARRIER_ID        = 'BUKOLIDELIVERY_CARRIER_ID';
    const CONF_CARRIER_REFERENCE = 'BUKOLIDELIVERY_CARRIER_REFERENCE';

    public $id_carrier;

    public function __construct()
    {
        $this->name = 'bukolidelivery';
        $this->tab = 'shipping_logistics';
        $this->version = '1.1.1';
        $this->author = 'muratbastas';
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Delivery by Bukoli');
        $this->description = $this->l('Adds Bukoli delivery method.');
    }

    public function getTemplate($area, $file)
    {
        return 'views/templates/'.$area.'/'.$file;
    }

    public function install()
    {

        return parent::install()
            && $this->installDB()
            && $this->createCarrier()
            && $this->registerHook('displayHeader')
            && $this->registerHook('actionCarrierUpdate')
            && $this->registerHook('actionValidateOrder')
            && $this->registerHook('displayOrderConfirmation')
            && $this->registerHook('displayAdminOrder');
    }

    protected function uninstallDB()
    {
        $sql = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'bukoli_details`';

        return Db::getInstance()->execute($sql);
    }

    protected function installDB()
    {
        $sql = '
			CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'bukoli_details` (
				`id_bukoli_details` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT,
				`id_order` INT( 11 ) UNSIGNED,
                `details` TEXT,
				`response` TEXT,
				`date_upd` DATETIME NOT NULL,
				PRIMARY KEY (`id_bukoli_details`)
			) ENGINE = '._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8
		';

        return Db::getInstance()->execute($sql);
    }

    protected function createCarrier()
    {
        $carrier = new Carrier();
        $carrier->name = 'Bukoli';
        $carrier->active = true;
        $carrier->deleted = 0;
        $carrier->is_free = true;
        $carrier->shipping_handling = false;
        $carrier->range_behavior = 0;
        $carrier->delay[Configuration::get('PS_LANG_DEFAULT')] = $this->l('Pick up at one of delivery points.');
        $carrier->is_module = true;
        $carrier->external_module_name = $this->name;
        $carrier->need_range = true;
        $carrier->shipping_external = false;

        if ($carrier->add()) {
            $groups = Group::getGroups(true);
            foreach ($groups as $group) {
                Db::getInstance()->autoExecute(
                    _DB_PREFIX_.'carrier_group',
                    array(
                        'id_carrier' => (int)$carrier->id,
                        'id_group' => (int)$group['id_group']
                    ),
                    'INSERT'
                );
            }

            $range_price = new RangePrice();
            $range_price->id_carrier = $carrier->id;
            $range_price->delimiter1 = '0';
            $range_price->delimiter2 = '1000000';
            $range_price->add();

            $range_weight = new RangeWeight();
            $range_weight->id_carrier = $carrier->id;
            $range_weight->delimiter1 = '0';
            $range_weight->delimiter2 = '1000000';
            $range_weight->add();

            $zones = Zone::getZones(true);
            foreach ($zones as $z) {
                Db::getInstance()->autoExecute(
                    _DB_PREFIX_.'carrier_zone',
                    array(
                        'id_carrier' => (int)$carrier->id,
                        'id_zone' => (int)$z['id_zone']
                    ),
                    'INSERT'
                );

                Db::getInstance()->autoExecuteWithNullValues(
                    _DB_PREFIX_.'delivery',
                    array(
                        'id_carrier' => $carrier->id,
                        'id_range_price' => (int)$range_price->id,
                        'id_range_weight' => null,
                        'id_zone' => (int)$z['id_zone'],
                        'price' => '25'
                    ),
                    'INSERT'
                );

                Db::getInstance()->autoExecuteWithNullValues(
                    _DB_PREFIX_.'delivery',
                    array(
                        'id_carrier' => $carrier->id,
                        'id_range_price' => null,
                        'id_range_weight' => (int)$range_weight->id,
                        'id_zone' => (int)$z['id_zone'],
                        'price' => '25'
                    ),
                    'INSERT'
                );
            }

            Tools::copy(
                dirname(__FILE__).'/views/img/bukolidelivery.jpg',
                _PS_SHIP_IMG_DIR_.'/'.(int)$carrier->id.'.jpg'
            );

            Configuration::updateValue(self::CONF_CARRIER_ID, $carrier->id);
            Configuration::updateValue(self::CONF_CARRIER_REFERENCE, $carrier->id);
        }

        return true;
    }

    public function getContent()
    {
        $output = null;

        if (Tools::isSubmit('submit'.$this->name))
        {
            $bukolidelivery = strval(Tools::getValue('BUKOLIDELIVERY'));
            if (!$bukolidelivery
              || empty($bukolidelivery)
              || !Validate::isGenericName($bukolidelivery)) {
                $output .= $this->displayError($this->l('Invalid Configuration value'));
            } else {
                Configuration::updateValue('BUKOLIDELIVERY', $bukolidelivery);
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            }
        }
        return $output.$this->displayForm();
    }

    public function displayForm()
    {
        // Get default language
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        // Init Fields form array
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Settings'),
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Customer password'),
                    'name' => 'BUKOLIDELIVERY',
                    'size' => 55,
                    'required' => true
                )
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            )
        );

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        // Language
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit'.$this->name;
        $helper->toolbar_btn = array(
            'save' =>
            array(
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                '&token='.Tools::getAdminTokenLite('AdminModules'),
            ),
            'back' => array(
                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            )
        );

        // Load current value
        $helper->fields_value['BUKOLIDELIVERY'] = Configuration::get('BUKOLIDELIVERY');

        return $helper->generateForm($fields_form);
    }

    protected function deleteCarrier()
    {
        $id_carrier = (int)Configuration::get(self::CONF_CARRIER_ID);
        $carrier = new Carrier($id_carrier);
        if (!Validate::isLoadedObject($carrier)) {
            return true;
        }

        if (!$carrier->delete()) {
            return false;
        }

        $carrier_icon = _PS_SHIP_IMG_DIR_.'/'.$id_carrier.'.jpg';
        if (file_exists($carrier_icon)) {
            unlink($carrier_icon);
        }

        return true;
    }

    public function uninstall()
    {
        return parent::uninstall()
            && $this->uninstallDB()
            && $this->deleteCarrier();
    }

    public function getOrderShippingCost($params, $shipping_cost)
    {
        throw new PrestaShopModuleException($this->l('Invalid method call.'));
    }

    public function getOrderShippingCostExternal($params)
    {
        throw new PrestaShopModuleException($this->l('Invalid method call.'));
    }

    public function hookActionCarrierUpdate($params)
    {
        if ($params['carrier']->id_reference == Configuration::get(self::CONF_CARRIER_REFERENCE)) {
            Configuration::updateValue(self::CONF_CARRIER_ID, $params['carrier']->id);
        }
    }

    public function hookDisplayHeader($params)
    {
        $context = Context::getContext();
        $protocol = Tools::getCurrentUrlProtocolPrefix();

        if (isset($context->controller->php_self) && in_array($context->controller->php_self, array('order-opc', 'order'))) {
            $this->context->controller->addCSS($this->_path.'views/css/bukolidelivery.css');
            $this->context->controller->addJS($this->_path.'views/js/bukolidelivery.js');
            $this->context->controller->addJS('https://bukoli.borusan.com/JetonAPI/jeton.load.api-min.js');

            $this->smarty->assign('bukolidelivery_carrier_id', Configuration::get(self::CONF_CARRIER_ID));

            return $this->display(__FILE__, 'header.tpl');
        }
    }

    public function hookDisplayOrderConfirmation($params)
    {
        $order = $params['objOrder'];
        $orders = Order::getByReference($order->reference)->getResults();
        $id_carrier = (int)Configuration::get(self::CONF_CARRIER_ID);
        foreach ($orders as $order) {
            if ($order->id_carrier != $id_carrier || !$order->id) {
                continue;
            }

            $bukoli_details = BukoliDetails::loadByOrderId($order->id);
            if (!$bukoli_details->id) {
                $bukoli_details->id_order = (int)$order->id;
                $bukoli_details->details = pSQL($this->context->cookie->bukoli_details);
                $bukoli_details->response = pSQL(BukoliDetails::pushOrderToService($params, $this->context->cookie, Configuration::get('BUKOLIDELIVERY')));

                if ($bukoli_details->add()) {
                    unset($this->context->cookie->bukoli_details);
                }
            }
        }
    }

    public function hookDisplayAdminOrder($params)
    {
        $bukoli_details = BukoliDetails::loadByOrderId($params['id_order']);

        $result = json_decode(stripslashes($bukoli_details->response));

        $this->context->smarty->assign(array(
            'bukoli_details' => $bukoli_details,
            'order' => $result->ORDER,
            'track' => $result->TRACK,
            'ps_version' => (float)_PS_VERSION_
        ));

        return $this->display(__file__, $this->getTemplate('admin', 'productAdminTab.tpl'));
    }
}
