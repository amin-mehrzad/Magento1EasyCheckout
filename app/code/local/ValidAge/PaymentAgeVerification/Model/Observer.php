<?php

class ValidAge_PaymentAgeVerification_Model_Observer
{

public function ageVerification(Varien_Event_Observer $observer)
{
    $session_id = Mage::getModel('core/cookie')->get('validage_session_id');
    $cyaCookie = Mage::getModel('core/cookie')->get('validage_token');

    Mage::getModel('core/cookie')->delete($session_id,'/');
    Mage::getModel('core/cookie')->delete('validage_session_id','/');


    $order       = $observer->getEvent()->getOrder();
    $orderData   = $order->getData();
    $orderIncrementId = $order->getIncrementId();
    $custName    = $order->getCustomerFirstname();
    $orderPrice  = $order->getGrandTotal();
    $orderId     = $order->getId();
    
    //$mobile      = trim($order->getShippingAddress()->getData('telephone'));
    


    $session_data = [
        "person" => [],
        "profile" => [],
        "validage_token" => $cyaCookie,
        "session_id" => $session_id,
        "website_version" => "Magento1",
        "order" => [
            "order_data" => $order->getData(),
            "order_number" => $order->getIncrementId(),
            "order_total" => $order->getGrandTotal(),
            "order_status" => $order->getStatus(),
            "order_date" => $order->getCreatedAt(),
            "order_billing" => $order->getBillingAddress()->getData(),
            "order_shipping" => $order->getShippingAddress()->getData(),
        ],
    ];


        $key = Mage::getStoreConfig('validage_options/validage_group/validage_public_key',Mage::app()->getStore());
        $secretKey = Mage::getStoreConfig('validage_options/validage_group/validage_secret_key',Mage::app()->getStore());
        $session_data["website_key"]= $key;
            $url = 'https://cloud.validage.com/person/easycheck_magento2';            
            $authorization = "Authorization: Bearer ".$secretKey;
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization ));
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($session_data));
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            $result = curl_exec($ch);
            curl_close($ch);
            $encodedSessionData = json_encode($session_data,true);
            $encodedResult= json_decode($result, true);
            if ($encodedResult["cya_code"] == 401) {
				$comment="(ValidAge) ERROR: ".$encodedResult["cya_message"]." | Reference: ".$encodedResult["cya_reference"];
				$order->setHoldBeforeState($order->getState())
					->setHoldBeforeStatus($order->getStatus())
					->setState(Mage_Sales_Model_Order::STATE_HOLDED, Mage_Sales_Model_Order::STATE_HOLDED, $comment, true)
					->save();
            }
            if ($encodedResult["cya_code"] == 201) {
                $order->addStatusHistoryComment("(ValidAge) WARNING: ".$encodedResult["cya_message"]." | Reference: ".$encodedResult["cya_reference"]);
                $order->save();
            }
            if ($encodedResult["cya_code"] == 200) {
                $order->addStatusHistoryComment("(ValidAge) SUCCESS: ".$encodedResult["cya_message"]." | Reference: ".$encodedResult["cya_reference"]);
                $order->save();
            }

    //Mage::log("{$encodedSessionData}", null, 'agevalidate.log');
    //Mage::log("{$result}", null, 'agevalidate.log');
    //Mage::log("{$publicKey}", null, 'agevalidate.log');
 //print_r($decodedOrder,true);
 //echo "I am here";
  //  echo '<script type="text/javascript">alert();</script>';

}

public function changeOrderStatus(Varien_Event_Observer $observer)
{


    $order       = $observer->getEvent()->getOrder();
    $orderData   = $order->getData();
    $orderIncrementId = $order->getIncrementId();
    $custName    = $order->getCustomerFirstname();
    $orderPrice  = $order->getGrandTotal();
    $orderId     = $order->getId();
    $status = $observer->getEvent()->getOrder()->getStatus();
	$state = $observer->getEvent()->getOrder()->getState();

	
	
    $session_data = array(
                 "profile"   =>[
                    "firstname"         =>"",
                     "lastname"          =>"",
                     "street"            =>[
                                             "0" =>"",
                                             "1" =>"",
                                             "2" =>""
                     ],
                     "city"              =>"",
                     "region"            =>"",
                     "region_id"         =>"",
                     "postcode"          =>"",
                     "country"           =>"",
                     "country_id"        =>"",
                     "dob"               =>"",
                     "ssn"               =>""
                 ],
                 "person"    =>[
                     "email"             =>"",
                     "telephone"         =>"",
                     "password"          =>"",
                     "confirmPassword"   =>"",
                     "confirmationCode"  =>""         
                 ],
                 "token"     =>"",
                 "session_id"=>"",
                 "order"     =>[
                     "order_data"        =>"",
                     "order_number"      =>$orderIncrementId,
                     "order_total"       =>"",
                     "order_status"      =>$status,
                     "order_date"        =>""
                ]
             );    


        $key = Mage::getStoreConfig('validage_options/validage_group/validage_public_key',Mage::app()->getStore());
        $secretKey = Mage::getStoreConfig('validage_options/validage_group/validage_secret_key',Mage::app()->getStore());
        $session_data["website_key"]= $key;
            $url = 'https://cloud.validage.com/person/changestate';            
            $authorization = "Authorization: Bearer ".$secretKey;
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization ));
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($session_data));
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            $result = curl_exec($ch);
            curl_close($ch);

            $encodedResult= json_decode($result, true);

    Mage::log("------------", null, 'agevalidate.log');
	Mage::log("{$status}--{$state}", null, 'agevalidate.log');

    Mage::log(print_r($encodedResult, TRUE), null, 'agevalidate.log');

}

}
?>