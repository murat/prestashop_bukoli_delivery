<?php
/**
 * BukoliDelivery: module for PrestaShop 1.5-1.6
 *
 * @author    muratbastas <muratbsts@gmail.com>
 * @copyright 2017 muratbastas
 * @license   http://addons.prestashop.com/en/content/12-terms-and-conditions-of-use Terms and conditions of use (EULA)
 */

class BukoliDeliveryAjaxModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $action = Tools::getValue('action');
        if (!empty($action) && method_exists($this, 'ajaxProcess'.Tools::ucfirst(Tools::toCamelCase($action)))) {
            return $this->{'ajaxProcess'.Tools::toCamelCase($action)}();
        } elseif (!empty($action) && method_exists($this, 'process'.Tools::ucfirst(Tools::toCamelCase($action)))) {
            return $this->{'process'.Tools::toCamelCase($action)}();
        }
    }

    protected function ajaxProcessSaveBukoliDetails()
    {
        // $details = Tools::getValue('details');
        // $bukoli_details = Tools::jsonDecode($details);
        // $this->context->cookie->bukoli_details = $bukoli_details->address;
        $this->context->cookie->bukoli_details = $_POST['PointCode'];

        die($this->context->cookie->bukoli_details);
    }

    protected function ajaxProcessGetAddress()
    {
        $bukoli_details = false;
        if (!empty($this->context->cookie->bukoli_details)) {
            $bukoli_details = $this->context->cookie->bukoli_details;
        }

        die(Tools::jsonEncode(array('address' => $bukoli_details)));
    }
}
