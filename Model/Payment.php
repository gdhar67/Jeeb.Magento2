<?php
/**
 * Jeeb payment method model
 *
 * @category    Jeeb
 * @package     Jeeb_Merchant
 * @author      Jeeb
 * @copyright   Jeeb (https://jeeb.io)
 */
namespace Jeeb\Merchant\Model;

use Jeeb\Jeeb;
use Jeeb\Merchant as JeebMerchant;
use Magento\Directory\Model\CountryFactory;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Response\Http;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;

class Payment extends AbstractMethod
{
    const JEEB_MAGENTO_VERSION = '1.0.7';
    const CODE = 'jeeb_merchant';

    protected $_code = 'jeeb_merchant';

    protected $_isInitializeNeeded = true;

    protected $urlBuilder;
    protected $jeeb;
    protected $storeManager;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param Data $paymentData
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @param JeebMerchant $jeeb
     * @param UrlInterface $urlBuilder
     * @param StoreManagerInterface $storeManager
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     * @internal param ModuleListInterface $moduleList
     * @internal param TimezoneInterface $localeDate
     * @internal param CountryFactory $countryFactory
     * @internal param Http $response
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        JeebMerchant $jeeb,
        UrlInterface $urlBuilder,
        StoreManagerInterface $storeManager,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = array()
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );

        $this->urlBuilder = $urlBuilder;
        $this->jeeb = $jeeb;
        $this->storeManager = $storeManager;

        \Jeeb\Jeeb::config(array(
            'app_id' => $this->getConfigData('app_id'),
            'api_key' => $this->getConfigData('api_key'),
            'api_secret' => $this->getConfigData('api_secret'),
            'environment' => $this->getConfigData('sandbox_mode') ? 'sandbox' : 'live',
            'user_agent' => 'Jeeb - Magento 2 Extension v' . self::JEEB_MAGENTO_VERSION
        ));
    }

    /**
     * @param Order $order
     * @return array
     */
    public function getJeebRequest(Order $order)
    {
        // $token = substr(md5(rand()), 0, 32);
        //
        // $payment = $order->getPayment();
        // $payment->setAdditionalInformation('jeeb_order_token', $token);
        // $payment->save();

        $description = array();
        foreach ($order->getAllItems() as $item) {
            $description[] = number_format($item->getQtyOrdered(), 0) . ' × ' . $item->getName();
        }

        $baseUri         = "https://core.jeeb.io/api/" ;
        $signature       = $this->getConfigData('api_key'); // Signature
        $baseCur         = $this->getConfigData('baseCur');
        $lang            = $this->getConfigData('lang') =="none"? NULL : $this->getConfigData('lang');
        $notification    = $this->urlBuilder->getUrl('jeeb/payment/callback');  // Notification Url
        $order_total     = number_format($order->getGrandTotal(), 2, '.', '');
        $target_cur      = $this->getConfigData('targetCur');
        $callBack        = $this->urlBuilder->getUrl('checkout/onepage/success');

        if($baseCur=='toman'){
          $baseCur='irr';
          $order_total *= 10;
        }

        // error_log("Base Uri : ".$baseUri." Signature : ".$signature." CallbackUri : ".$callBack." NotificationUri : ".$notification);
        // error_log("Cost = ". $total);
        \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->info("Base Uri : ".$baseUri." Signature : ".$signature." CallbackUri : ".$callBack." NotificationUri : ".$notification." OrderTotal :".$order_total);


        $amount = convertIrrToBtc($baseUri, $order_total, $signature, $baseCur);

        $params = array(
          'orderNo'          => $order->getIncrementId(),
          'value'            => (float) $amount,
          'webhookUrl'       => $notification,
          'callBackUrl'      => $this->urlBuilder->getUrl('checkout/onepage/success'),
          'allowReject'      => $this->getConfigData('sandbox_mode') ? false : true,
          "coins"            => $target_cur,
          "allowTestNet"     => $this->getConfigData('sandbox_mode') ? true : false,
          "language"         => $lang
        );

        \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->info('Response =>'. var_export($params, TRUE));

        $token = createInvoice($baseUri, $amount, $params, $signature);

        // return $token;

        // redirectPayment($baseUri, $token);

        return array(
                'status' => true,
                'payment_url' => $baseUri."payments/invoice?token=".$token,
                // 'token' => $token
            );

        // $params = array(
        //     'order_id' => $order->getIncrementId(),
        //     'price' => number_format($order->getGrandTotal(), 2, '.', ''),
        //     'currency' => $order->getOrderCurrencyCode(),
        //     'receive_currency' => $this->getConfigData('receive_currency'),
        //     'callback_url' => ($this->urlBuilder->getUrl('jeeb/payment/callback') . '?token=' . $payment->getAdditionalInformation('jeeb_order_token')),
        //     'cancel_url' => $this->urlBuilder->getUrl('checkout/onepage/failure'),
        //     'success_url' => $this->urlBuilder->getUrl('checkout/onepage/success'),
        //     'title' => $this->storeManager->getWebsite()->getName(),
        //     'description' => join($description, ', ')
        // );

        // $cgOrder = \Jeeb\Merchant\Order::create($params);
        //
        // if ($cgOrder) {
            // return array(
            //     'status' => true,
            //     'payment_url' => $baseUri."payments/invoice",
            //     'token' => $token
            // );
        // } else {
        //     return array(
        //         'status' => false
        //     );
        // }
    }

    /**
     * @param Order $order
     */
     public function validateJeebCallback(Order $order)
     {
       \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->info('Entered Jeeb-Notification');

         $postdata = file_get_contents("php://input");
         $json = json_decode($postdata, true);
         if($json['signature']==$this->getConfigData('api_key')){

             // Call Jeeb
             $network_uri = "https://core.jeeb.io/api/";

             if ( $json['stateId']== 2 ) {
               \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->info('Response =>'. var_export($json, TRUE));
               $order
                   ->setState(Order::STATE_PENDING, TRUE)
                   ->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_PENDING))
                   ->save();

             }
             else if ( $json['stateId']== 3 ) {
               \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->info('Response =>'. var_export($json, TRUE));
               $order->setState(Order::STATE_PROCESSING);
               $order->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING));
               $order->save();

             }
             else if ( $json['stateId']== 4 ) {
               \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->info('Response =>'. var_export($json, TRUE));
               $data = array(
                 "token" => $json['token']
               );

               $data_string = json_encode($data);
               $api_key = $this->getConfigData('api_key');
               $url = $network_uri.'payments/'.$api_key.'/confirm';
               // error_log("Signature:".$api_key." Base-Url:".$network_uri." Url:".$url);

               $ch = curl_init($url);
               curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
               curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
               curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
               curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                   'Content-Type: application/json',
                   'Content-Length: ' . strlen($data_string))
               );

               $result = curl_exec($ch);
               $data = json_decode( $result , true);
               // error_log("data = ".var_export($data, TRUE));
               \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->info('Response =>'. var_export($data
               , TRUE));


               if($data['result']['isConfirmed']){
                 // error_log('Payment confirmed by jeeb');
                 $order->setState(Order::STATE_COMPLETE);
                 $order->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_COMPLETE));
                 $order->save();

               }
               else {
                 // error_log('Payment confirmation rejected by jeeb');
               }
             }
             else if ( $json['stateId']== 5 ) {
               \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->info('Response =>'. var_export($json, TRUE));
               $order
                   ->setState(Order::STATE_CANCELED, TRUE)
                   ->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_CANCELED))
                   ->save();

             }
             else if ( $json['stateId']== 6 ) {
               \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->info('Response =>'. var_export($json, TRUE));
               $order
                   ->setState(Order::STATE_CANCELED, TRUE)
                   ->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_CANCELED))
                   ->save();

             }
             else if ( $json['stateId']== 7 ) {
               \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->info('Response =>'. var_export($json, TRUE));
               $order
                   ->setState(Order::STATE_CANCELED, TRUE)
                   ->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_CANCELED))
                   ->save();

             }
             else{
               // error_log('Cannot read state id sent by Jeeb');
               \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->info('Cannot read stateId. Response =>'. var_export($json, TRUE));

             }
       }
      }
    }

function convertIrrToBtc($url, $amount, $signature, $baseCur) {
    // error_log("Entered into Convert Base To Target");

    // return Jeeb::convert_irr_to_btc($url, $amount, $signature);
    $ch = curl_init($url.'currency?'.$signature.'&value='.$amount.'&base='.$baseCur.'&target=btc');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/json')
  );

  $result = curl_exec($ch);
  $data = json_decode( $result , true);

\Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->info('Response =>'. var_export($data, TRUE));
// \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->info('msg to print');
  // error_log('Response =>'. var_export($data, TRUE));
  // Return the equivalent bitcoin value acquired from Jeeb server.
  return (float) $data["result"];

  }



  function createInvoice($url, $amount, $options = array(), $signature) {

      $post = json_encode($options);

      $ch = curl_init($url.'payments/' . $signature . '/issue/');
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          'Content-Type: application/json',
          'Content-Length: ' . strlen($post))
      );

      $result = curl_exec($ch);
      $data = json_decode( $result ,true );
      \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->info('Response =>'. var_export($data, TRUE));

      // error_log('Response =>'. var_export($data, TRUE));

      return $data['result']['token'];

  }

  function redirectPayment($url, $token) {
    // error_log("Entered into auto submit-form");
    // Using Auto-submit form to redirect user with the token
    \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->info('Entered redirect');

    echo "<form id='form' method='post' action='".$url."payments/invoice'>".
            "<input type='hidden' autocomplete='off' name='token' value='".$token."'/>".
           "</form>".
           "<script type='text/javascript'>".
                "document.getElementById('form').submit();".
           "</script>";

    \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->info('Exit redirect');
  }
