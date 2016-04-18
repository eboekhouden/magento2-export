<?php
namespace Eboekhouden\Export\Controller\Adminhtml\Sales;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;


class Order extends \Magento\Sales\Controller\Adminhtml\Order\AbstractMassAction
{
	protected $collectionFactory;
	protected $_eHelper;
    protected $_messageManager;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
    	Context $context,
    	Filter $filter,
    	CollectionFactory $collectionFactory,
    	\Eboekhouden\Export\Helper\Export $eHelper,
        \Magento\Framework\Message\ManagerInterface $_messageManager
    )
    {
        parent::__construct($context, $filter);
        $this->collectionFactory = $collectionFactory;
        $this->_eHelper = $eHelper;
        $this->_messageManager = $_messageManager;
    }


    /**
     * Cancel selected orders
     *
     * @param AbstractCollection $collection
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    protected function massAction(AbstractCollection $collection)
    {
        $countCancelOrder = 0;
        $orderIds = array();
        foreach ($collection->getItems() as $order) {
            $orderIds[] = $order->getId();

        }
     	list($iAdded, $iExist, $sErrorMsg, $sInfoMsg) = $response = $this->_eHelper->exportOrders($orderIds);

        return $this->_reportExportResult($iAdded, $iExist, $sErrorMsg, $sInfoMsg);

        /*
        $countNonCancelOrder = $collection->count() - $countCancelOrder;

        if ($countNonCancelOrder && $countCancelOrder) {
            $this->messageManager->addError(__('%1 order(s) cannot be canceled.', $countNonCancelOrder));
        } elseif ($countNonCancelOrder) {
            $this->messageManager->addError(__('You cannot cancel the order(s).'));
        }

        if ($countCancelOrder) {
            $this->messageManager->addSuccess(__('We canceled %1 order(s).', $countCancelOrder));
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath($this->getComponentRefererUrl());
        return $resultRedirect;*/
    }


    protected function _reportExportResult($iAdded, $iExist, $sErrorMsg, $sInfoMsg){

        if (!empty($sInfoMsg))
        {
            $this->messageManager->addNotice($sInfoMsg);
        }

        $iOrdersTransferred = $iAdded + $iExist;

        $sMessage = '<b>' . __('Export naar e-Boekhouden') . '</b><br /><br />' . "\n";
        if (1 == $iOrdersTransferred)
        {
            $sMessage .= ('1 mutatie doorgegeven');
        }
        else
        {
            $sMessage .= __('%1 mutaties doorgegeven', $iOrdersTransferred);
        }
        if (1 == $iExist)
        {
            $sMessage .= __(', waarvan er 1 al bestond');
        }
        elseif (1 < $iExist)
        {
            $sMessage .= __(', waarvan er %1 al bestonden', $iExist);
        }
        $sMessage .= '.<br />' . "\n";

        if (empty($sErrorMsg))
        {
            $this->messageManager->addSuccess($sMessage);
        }
        else
        {
            $sMessage .= '<br />' . "\n";
            $sMessage .= nl2br($sErrorMsg) . "\n";
            $this->messageManager->addError($sMessage);
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('sales/order');
        return $resultRedirect;

    }

}
