<?php
/**
 * BukoliDelivery: module for PrestaShop 1.5-1.6
 *
 * @author    muratbastas <muratbsts@gmail.com>
 * @copyright 2017 muratbastas
 * @license   http://addons.prestashop.com/en/content/12-terms-and-conditions-of-use Terms and conditions of use (EULA)
 */

require __DIR__ . "/bukoli-php/BukoliBootstrap.php";

use Bukoli\Bukoli;
use Bukoli\Model\IntegrationEndUserInfo;
use Bukoli\Model\IntegrationOrderDetailInfo;
use Bukoli\Model\IntegrationOrderInfo;
use Bukoli\Request\OrderInsert;

class BukoliDetails extends ObjectModel
{
    public $id;
    public $id_order;
    public $details;
    public $date_upd;

    public static $definition = array(
        'table' => 'bukoli_details',
        'primary' => 'id_bukoli_details',
        'fields' => array(
            'id_order' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'details'  => array('type' => self::TYPE_STRING, 'validate' => 'isMessage'),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
        ),
    );

    public static function loadByOrderId($id_order)
    {
        $collection = new Collection('BukoliDetails');
        $collection->where('id_order', '=', (int)$id_order);

        if ($collection->getFirst()) {
            return $collection->getFirst();
        } else {
            return new self();
        }
    }

    public static function pushOrderToService($order, $cookie)
    {
        $customerPassword = 'P2LGDNH3SY4MU2Z5AFKV';
        $RequestOrderId = $order['objOrder']->reference;
        $IrsaliyeNo = $order['objOrder']->reference;
        $BukoliPoint = $cookie->bukoli_details;
        $EndUserCode = $cookie->id_customer;
        $EndUserFirstName = $cookie->customer_firstname;
        $EndUserLastName = $cookie->customer_lastname;
        $EndUserEmail = $cookie->email;
        $OrderDate = date('YmdHis');

        // var_dump($OrderDate); die;

        Bukoli::init($customerPassword);
        // Bukoli::init($customerPassword, 'https://Bukoli.borusan.com/IntegrationServiceV2/JetonOrderService.asmx?wsdl');

        $orderInsert = new OrderInsert();

        $orderInfo = new IntegrationOrderInfo();
        // Required
        $orderInfo->setRequestOrderId($RequestOrderId);
        $orderInfo->setSelectedJetonPointCode($BukoliPoint);
        $orderInfo->setIrsaliyeNo($IrsaliyeNo);
        $orderInfo->setOrderDate($OrderDate);

        /*
         *  End User
         */
        $endUser = new IntegrationEndUserInfo();
        // Required
        $endUser->setEndUserCode($EndUserCode);
        $endUser->setFirstName($EndUserFirstName);
        $endUser->setLastName($EndUserLastName);
        $endUser->setEmail($EndUserEmail);

        $orderInfo->setEndUserData($endUser);

        $orderInsert->setIntegrationOrderInfo($orderInfo);

        try {
            $response = $orderInsert->request();
            if ($response->getStatus() == 1) {
                // Success
                $result = "";
                $result .= 'Status: ' . $response->getStatus() . '<br/>';
                $result .= 'Message: ' . $response->getMessage() . '<br/>';
                $result .= 'JetonOrderId: ' . $response->getJetonOrderId() . '<br/>';
                $result .= 'TrackingNo: ' . $response->getTrackingNo() . '<br/>';
                var_dump($result);
                die;
            } else {
                // Fail
                $error = "";
                $error .= 'Status: ' . $response->getStatus() . '<br/>';
                $error .= 'Message: ' . $response->getMessage() . '<br/>';
                $error .= 'JetonOrderId: ' . $response->getJetonOrderId() . '<br/>';
                $error .= 'TrackingNo: ' . $response->getTrackingNo() . '<br/>';
                var_dump($error);
                die;
            }
        } catch (SoapFault $e) {
            // Soap Exception
            $error = str_replace(PHP_EOL, '<br/>', $e->getMessage());
            echo($error);
            die;
        }
    }
}
