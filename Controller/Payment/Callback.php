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
    }

    /**
     * Default customer account page
     *
     * @return void
     */
    public function execute()
    {
        $request_order_id = (filter_input(INPUT_POST, 'order_id') ? filter_input(INPUT_POST, 'order_id') : filter_input(INPUT_GET, 'order_id'));

        $order = $this->order->loadByIncrementId($request_order_id);
        $this->jeebPayment->validateJeebCallback($order);

        $this->getResponse()->setBody('OK');
    }
}
