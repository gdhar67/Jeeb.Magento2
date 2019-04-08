<?php
/**
 * Jeeb Callback controller
 *
 * @category    Jeeb
 * @package     Jeeb_Merchant
 * @author      Jeeb
 * @copyright   Jeeb (https://jeeb.com)
 * @license     https://github.com/jeeb/magento2-plugin/blob/master/LICENSE The MIT License (MIT)
 */
namespace Jeeb\Merchant\Controller\Payment;

use Jeeb\Merchant\Model\Payment as JeebPayment;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\Order;

class Callback extends Action
{
    protected $order;
    protected $jeebPayment;

    /**
     * @param Context $context
     * @param Order $order
     * @param Payment|JeebPayment $jeebPayment
     * @internal param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        Order $order,
        JeebPayment $jeebPayment
    )
    {
        parent::__construct($context);

        $this->order = $order;
        $this->jeebPayment = $jeebPayment;

        $this->execute();
    }

    /**
     * Default customer account page
     *
     * @return void
     */
    public function execute()
    {
        \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->info('Entered into callback');
        $postdata = file_get_contents("php://input");
        $json = json_decode($postdata, true);
        \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->info('Response =>'. var_export($json, TRUE));

        $order = $this->order->loadByIncrementId($json['orderNo']);
        $this->jeebPayment->validateJeebCallback($order);

        $this->getResponse()->setBody('OK');
    }
}
