<?php

namespace Eboekhouden\Export\Helper;

use \Magento\Tax\Model\Calculation;

Class Export extends \Magento\Framework\App\Helper\AbstractHelper{
    private $orderFactory;
    private $invoiceFactory;
    private $creditmemoFactory;
    private $helper;
    private $oAccountNumberHelper;
    private $countryFactory;
    private $taxCalculator;
    private $taxManagement;
    private $taxrateFactory;
    private $logger;

    public function __construct(
        Data $helper,
        Accountnumber $oAccountNumberHelper,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\Order\InvoiceFactory $invoiceFactory,
        \Magento\Sales\Model\Order\Creditmemo $creditmemoFactory,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        Calculation $taxCalculator,
        \Magento\Tax\Model\Calculation\RateFactory $taxrateFactory,
        \Magento\Tax\Model\Sales\Order\TaxManagement $taxManagement,
        \Psr\Log\LoggerInterface $logger
    ){
        $this->helper = $helper;
        $this->orderFactory = $orderFactory;
        $this->invoiceFactory = $invoiceFactory;
        $this->creditmemoFactory = $creditmemoFactory;
        $this->oAccountNumberHelper = $oAccountNumberHelper;
        $this->countryFactory = $countryFactory;
        $this->taxCalculator = $taxCalculator;
        $this->taxManagement = $taxManagement;
        $this->taxrateFactory = $taxrateFactory;
        $this->logger = $logger;
    }


    public function exportOrders($aOrderIds){
        if(!is_array($aOrderIds)){
            $aOrderIds = array($aOrderIds);
        }

        $sErrorMsg = '';
        $sInfoMsg = '';
        $iCountAdded = 0;
        $iCountExist = 0;

        sort($aOrderIds);
        foreach ($aOrderIds as $sOrderId)
        {
            $oOrder = $this->orderFactory->create()->load($sOrderId);
            $oInvoiceColl = $oOrder->getInvoiceCollection();
            $aInvoiceIds = $oInvoiceColl->getAllIds();

            list($iThisAdded, $iThisExist, $sThisErrorMsg, $sThisInfoMsg) = $this->exportInvoices($aInvoiceIds);
            $iCountAdded += $iThisAdded;
            $iCountExist += $iThisExist;
            $sErrorMsg .= $sThisErrorMsg;
            $sInfoMsg .= $sThisInfoMsg;
        }

        return array($iCountAdded, $iCountExist, $sErrorMsg, $sInfoMsg);

    }


    public function exportInvoices($aInvoiceIds){
        $sErrorMsg = '';
        $sInfoMsg = '';
        $iCountAdded = 0;
        $iCountExist = 0;

        sort($aInvoiceIds);
        foreach ($aInvoiceIds as $sInvoiceId)
        {
            $oInvoice = $this->invoiceFactory->create()->load($sInvoiceId);
            /* @var $oInvoice Mage_Sales_Model_Order_Invoice */
            $response = $this->_exportObject($oInvoice);
            list($iThisAdded, $iThisExist, $sThisErrorMsg, $sThisInfoMsg) = $response;
            $iCountAdded += $iThisAdded;
            $iCountExist += $iThisExist;
            $sErrorMsg .= $sThisErrorMsg;
            $sInfoMsg .= $sThisInfoMsg;
        }
        return array($iCountAdded, $iCountExist, $sErrorMsg, $sInfoMsg);
    }

    public function exportCreditmemo($aCreditmemoIds)
    {
        $sErrorMsg = '';
        $sInfoMsg = '';
        $iCountAdded = 0;
        $iCountExist = 0;

        sort($aCreditmemoIds);
        foreach ($aCreditmemoIds as $iCreditmemoId)
        {
            $oCreditMemo = $this->creditmemoFactory->load($iCreditmemoId);
            /* @var $oInvoice Mage_Sales_Model_Order_Creditmemo */
            list($iThisAdded, $iThisExist, $sThisErrorMsg, $sThisInfoMsg) = $this->_exportObject($oCreditMemo);
            $iCountAdded += $iThisAdded;
            $iCountExist += $iThisExist;
            $sErrorMsg .= $sThisErrorMsg;
            $sInfoMsg .= $sThisInfoMsg;
        }
        return array($iCountAdded, $iCountExist, $sErrorMsg, $sInfoMsg);
    }


     protected function _exportObject($oContainer){



        $sXml = '';
        $iCountAdded = 0;
        $iCountExist = 0;
        $sErrorMsg = '';
        $sInfoMsg = '';


        $iStoreId = $oContainer->getStoreId();
        if (empty($iStoreId))
        {
            $iStoreId = 0;
        }

        $iOrderTime = strtotime($oContainer->getCreatedAt());

        $oOrder = $oContainer->getOrder();

        /* @var $oOrder Mage_Sales_Model_Order */
        $sOrderNr = $oOrder->getIncrementId();
        $sInvoiceNr = 'X' . $oContainer->getIncrementId();
        $sExportType = __('object');


        if ($oContainer instanceof \Magento\Sales\Model\Order\Invoice)
        {
            $sExportType = __('factuur');
            $sInvoiceNr = $oContainer->getIncrementId();
        }
        elseif ($oContainer instanceof \Magento\Sales\Model\Order\Creditmemo)
        {
            $sExportType = __('creditering');
            $sInvoiceNr = 'C' . $oContainer->getIncrementId();
        }

        $aSettings = $this->helper->getConnectorSettings($iStoreId);

        $oBillingAddress = $oContainer->getBillingAddress();
        $oShippingAddress = $oContainer->getShippingAddress();
        if(!$oShippingAddress){
            $oShippingAddress = $oBillingAddress;
        }


        if (empty($aSettings['bConOK']))
        {
            # Skip the rest
        }
        elseif (empty($oBillingAddress))
        {
            $sErrorMsg .= __('Fout in %s %s: geen factuuradres gevonden. ' . "\n"
                ,$sExportType
                ,$oContainer->getIncrementId()
            );
        }
        else
        {
            $sCompanyName = $oBillingAddress->getCompany();
            if (empty($sCompanyName))
            {
                $sCompanyName = $oBillingAddress->getName();
            }

            $iExistingMutatieNr = $oContainer->getEboekhoudenMutatie();

            $sObjectDescription = __('Magento %1 %2, order %3'
                , $sExportType
                , $oContainer->getIncrementId()
                , $sOrderNr);

            $sXml .= '
  <MUTATIE>';
            if (!empty($iExistingMutatieNr))
            {
                $sXml .= '
    <MUTNR>' . $this->helper->xmlPrepare($iExistingMutatieNr) . '</MUTNR>';
            }
            $sStreetFull = $oBillingAddress->getStreet();
            $sStreetFull = implode(" ", $sStreetFull);
            $sEmail = $oOrder->getCustomerEmail();
            $sTaxvat = $oOrder->getCustomerTaxvat();
            $sTaxvat = strtoupper(preg_replace('|\W|', '', $sTaxvat));

            $iBalanceAccount = 1300;
            $iBalanceAccount = $this->oAccountNumberHelper->getBalanceAccountNumber($iBalanceAccount,$oContainer);
            if (empty($iBalanceAccount))
            {
                $iBalanceAccount = 1300;
            }


            $countryName = $this->countryFactory->create()->load($oBillingAddress->getCountryId())->getName();//->getLoadedRegionCollection()->toOptionArray();


            $sXml .= '
    <NAW>
      <BEDRIJF>' . $this->helper->xmlPrepare($sCompanyName) . '</BEDRIJF>
      <ADRES>' . $this->helper->xmlPrepare($sStreetFull) . '</ADRES>
      <POSTCODE>' . $this->helper->xmlPrepare($oBillingAddress->getPostcode()) . '</POSTCODE>
      <PLAATS>' . $this->helper->xmlPrepare($oBillingAddress->getCity()) . '</PLAATS>
      <LAND>' . $this->helper->xmlPrepare($countryName) . '</LAND>
      <LANDCODE>' . $this->helper->xmlPrepare($oBillingAddress->getCountryId()) . '</LANDCODE>
      <TELEFOON>' . $this->helper->xmlPrepare($oBillingAddress->getTelephone()) . '</TELEFOON>
      <EMAIL>' . $this->helper->xmlPrepare($sEmail) . '</EMAIL>
      <OBNUMMER>' . $this->helper->xmlPrepare($sTaxvat) . '</OBNUMMER>
    </NAW>
    <SOORT>' . $this->helper->xmlPrepare(2) . '</SOORT>
    <REKENING>' . $this->helper->xmlPrepare($iBalanceAccount) . '</REKENING>
    <OMSCHRIJVING>' . $this->helper->xmlPrepare($sObjectDescription) . '</OMSCHRIJVING>
    <FACTUUR>' . $this->helper->xmlPrepare($sInvoiceNr) . '</FACTUUR>
    <BETALINGSKENMERK>' . $this->helper->xmlPrepare($sOrderNr) . '</BETALINGSKENMERK>
    <BETALINGSTERMIJN>' . $this->helper->xmlPrepare(30) . '</BETALINGSTERMIJN>
    <DATUM>' . $this->helper->xmlPrepare(date('d-m-Y', $iOrderTime)) . '</DATUM>
    <INEX>' . $this->helper->xmlPrepare('EX') . '</INEX>
    <MUTATIEREGELS>';

            $aOrderItems = $oContainer->getItemsCollection();
            $fDiscountLeft = $oContainer->getBaseDiscountAmount();

            $totalBaseAmountItems = 0;
            $totalBaseAmountInclTaxItems = 0;
            $totalBaseTaxItems = 0;

/*
            $aTaxInfo = $oOrder->getFullTaxInfo();
            $aValidTaxPerc = array();
            foreach ( $aTaxInfo as $aTaxInfoItem )
            {
                foreach ( $aTaxInfoItem['rates'] as $aRateInfo )
                {
                    #debug($aRateInfo);
                    $aValidTaxPerc[ $aRateInfo['percent'] ] = 1;
                }
            }
*/

            $iGbRekening = $iCostcenter = 0;
            foreach ($aOrderItems as $oItem) {
                #$oItem->calcRowTotal();

                $sType = $this->_getItemType($oItem);

                $BEDRAGEXCL = $oItem->getRowTotal() - $oItem->getDiscountAmount();
                $BEDRAGINCL = $BEDRAGEXCL + $oItem->getTaxAmount();

                #$BEDRAGINCL = $oItem->getRowTotalInclTax() - $oItem->getDiscountAmount();


                $obj = new \Magento\Framework\DataObject();
                $obj->setData('itemtype', 'product');
                $obj->setData('producttype', $sType);
                $obj->setData('BEDRAGINCL',$BEDRAGINCL);
                $obj->setData('BEDRAGEXCL',$BEDRAGEXCL);
                $obj->setData('BTWBEDRAG',$BEDRAGINCL - $BEDRAGEXCL);

                $sVatCode = $this->_Find_Ebvatcode(
                    'product',
                    $oItem->getOrderItem()->getId(),
                    $oOrder
                );

               # $f = new \ReflectionClass($oItem);



               # echo '<pre>';
               # echo $oItem->getDiscountTaxCompensationAmount();

           # print_r($f->getMethods());
        #print_r($oItem->getFinalPrice());
      #print_r($obj->debug());
        #print_r($oItem->debug());

                /*
                    (int) $oItem->getOrderItem()->getTaxPercent(),
                    $oOrder,
                    $oItem->getOrderItem()->getProduct()->getTaxClassId()
                );
                */

                $obj->setData('VatCode', $sVatCode);

                $product = $oItem->getOrderItem()->getProduct();
                $product->setStoreId($iStoreId);
                if (!empty($product) && $product->hasData('sku'))
                {
                    #$sProductCode = $product->getSku();
                    $iGbRekening = $product->getEboekhoudenGrootboekrekening();
                    $iCostcenter = $product->getEboekhoudenCostcenter();
                }
                $obj->setData('GbRekening',$iGbRekening);
                $obj->setData('Costcenter',$iCostcenter);

                $sXml .= $this->_getItemXml($aSettings,$oContainer,$obj);

                $totalBaseAmountItems += $BEDRAGEXCL;
                $totalBaseAmountInclTaxItems += $BEDRAGINCL;
            }

           # debug($oContainer->debug());
            #/*

            // Add shipping
            if (0 < $oContainer->getShippingAmount())
            {

                $obj = new \Magento\Framework\DataObject();
                $obj->setData('itemtype', 'product');
                $obj->setData('producttype', $sType);
                $obj->setData('BEDRAGINCL',$oContainer->getShippingInclTax());
                $obj->setData('BEDRAGEXCL',$oContainer->getShippingAmount());
                $obj->setData('BTWBEDRAG',$oContainer->getShippingInclTax() - $oContainer->getShippingAmount());

                $sVatCode = $this->_Find_Ebvatcode(
                    'shipping',
                    false,
                    $oOrder
                );

                $obj->setData('sVatCode', $sVatCode);
                $sXml .= $this->_getItemXml($aSettings,$oContainer,$obj);

                $totalBaseAmountItems = $oContainer->getShippingAmount();
                $totalBaseAmountInclTaxItems += $oContainer->getShippingInclTax();
            }

          #  debug($oContainer->debug());


            // Add adjustment in case it exists (for credit memos)
            if ((float)$oContainer->getAdjustment() > 0) {
                #debug($aSettings);
                $obj = new \Magento\Framework\DataObject();
                $obj->setData('itemtype', 'adjustment');
                $obj->setData('BEDRAGINCL',$oContainer->getAdjustment());
                $obj->setData('BEDRAGEXCL',$oContainer->getAdjustment());
                $obj->setdata('BTWBEDRAG',0);
                $sXml .= $this->_getItemXml($aSettings,$oContainer,$obj);

                $totalBaseAmountItems += $oContainer->getAdjustment();
                $totalBaseAmountInclTaxItems += $oContainer->getAdjustment();
            }
            #debug($oContainer->debug());
            if (!$oContainer instanceof \Magento\Sales\Model\Order\Invoice\Creditmemo) {

              #  echo $totalBaseAmountInclTaxItems;
              #  echo '<pre>';
              #  print_r($oContainer->debug());

                $total = $oContainer->getGrandTotal();
                if (0.0001 < abs($total - $totalBaseAmountInclTaxItems)) {
                    $BEDRAGINCL = $oContainer->getGrandTotal() - $totalBaseAmountInclTaxItems;
                    $BEDRAGEXCL = $oContainer->getSubTotal() - $totalBaseAmountItems;

                    $obj = new \Magento\Framework\DataObject();
                    $obj->setData('itemtype', 'payment');
                    $obj->setData('BEDRAGINCL',$BEDRAGINCL);
                    $obj->setData('BEDRAGEXCL',$BEDRAGEXCL);
                    $obj->setdata('BTWBEDRAG',$BEDRAGINCL - $BEDRAGEXCL);
                    $sXml .= $this->_getItemXml($aSettings,$oContainer,$obj);
                 # echo '<textarea style="width:800px; height:1000px;">', $sXml, '</textarea>';
                 # die();
                }


            }

            $sXml .= '
    </MUTATIEREGELS>
  </MUTATIE>';


         #   $this->logger->addDebug($sXml);


            $sPostAction = (!empty($iExistingMutatieNr)) ? 'ALTER_MUTATIE' : 'ADD_MUTATIE';
            list($sThisMutatieNr, $iThisExist, $sThisErrorMsg, $sThisInfoMsg) = $this->_postMutatieXml($sXml,
                                                                                                       $aSettings,
                                                                                                       $sPostAction);
            $iCountExist += $iThisExist;
            $sErrorMsg .= $sThisErrorMsg;
            $sInfoMsg .= $sThisInfoMsg;


            if (!empty($sThisMutatieNr)) # can be boolean or string
            {
                $iCountAdded++;
                if (is_string($sThisMutatieNr))
                {
                    // Do NOT save whole $oContainer, because the fixes to all items will be written to database!
                    $oContainer->setEboekhoudenMutatie($sThisMutatieNr);
                    $oContainer->save();
                    #$oContainer->getResource()->saveAttribute( $oContainer, 'eboekhouden_mutatie' );
                }
            }

            if ($oContainer instanceof \Magento\Sales\Model\Order\Invoice)
            {
                $oCreditMemoColl = $oOrder->getCreditmemosCollection();
                /* @var $oCreditMemoColl Mage_Sales_Model_Mysql4_Order_Creditmemo_Collection */
                if (!empty($oCreditMemoColl) && $oCreditMemoColl->count())
                {
                    foreach ($oCreditMemoColl as $oCreditMemo)
                        /* @var $oCreditMemo Mage_Sales_Model_Order_Creditmemo */
                    {
                        list($sThisAdded, $iThisExist, $sThisErrorMsg, $sThisInfoMsg) = $this->_exportObject($oCreditMemo,
                                                                                                             $aSettings);
                        $iCountAdded += $sThisAdded;
                        $iCountExist += $iThisExist;
                        $sErrorMsg .= $sThisErrorMsg;
                        $sInfoMsg .= $sThisInfoMsg;
                    }
                }
            }
        }
        return array($iCountAdded, $iCountExist, $sErrorMsg, $sInfoMsg);
    }

    private function _getItemXml($aSettings, $oContainer, $obj){
        $sComment = 'type:';
        $iGbRekening = $iCostcenter = $sVatCode = false;

        if($obj->getData('itemtype') == 'adjustment'){
            $sComment .= 'adjustment_fee';
            $iGbRekening = $aSettings['sAdjustmentLedgerAcc'];
        }

        if($obj->getData('itemtype') == 'payment'){
            $sComment .= 'paymentt_fee';
            $iGbRekening = $aSettings['sPaymentFeeLedgerAcc'];
        }
        if($obj->getData('itemtype') == 'product'){
            $sComment .= $obj->getData('producttype');//. ' [BTW '. (int)  . '%]';
            if($obj->getData('producttype') == 'dummy'  ||  $obj->getData('producttype') == 'bundle'){
                return'';
            }
            $iGbRekening = $obj->getData('GbRekening');
            $iCostcenter = $obj->getData('Costcenter');
        }
        if($obj->getData('itemtype') == 'shipping'){
            $sComment .= 'shipping';
            $iGbRekening = $aSettings['sShipLedgerAcc'];
            $iCostcenter = $aSettings['sShipCostcenter'];
        }



        if((int) $obj->getData('BTWBEDRAG') > 0){
            $sComment .= ' [BTW '. (int) (($obj->getData('BTWBEDRAG') / $obj->getData('BEDRAGEXCL')) * 100) . '%]';
        }

        if($obj->getData('VatCode')){
            $sVatCode = $obj->getData('VatCode');
        }
        if(!$sVatCode){
            $sVatCode = 'GEEN';
        }


        $iGbRekening = $this->oAccountNumberHelper->getLedgerAccountNumber($iGbRekening,$oContainer,$obj);
        if (empty($iGbRekening)) {
            $iGbRekening = 8000;
        }
        $iCostcenter = $this->oAccountNumberHelper->getCostCenterNumber($iCostcenter,$oContainer,$obj);
        if (empty($iCostcenter)) {
            $iCostcenter = 0;
        }

        $fPriceIn = $obj->getData('BEDRAGINCL');
        $fPriceEx = $obj->getData('BEDRAGEXCL');
        $fTaxAmount = $obj->getData('BTWBEDRAG');

        if ($oContainer instanceof  \Magento\Sales\Model\Order\Invoice\Creditmemo){
            $sComment = 'Refund: ' . $sComment;
            $fPriceIn = -1 * $fPriceIn;
            $fPriceEx = -1 * $fPriceEx;
            $fTaxAmount = -1 * $fTaxAmount;
        }

        return '<MUTATIEREGEL>
                    <!-- ' . $this->helper->xmlPrepare($sComment) . ' -->
                    <BEDRAGINCL>' . $this->helper->_xmlAmountPrepare($fPriceIn) . '</BEDRAGINCL>
                    <BEDRAGEXCL>' . $this->helper->_xmlAmountPrepare($fPriceEx) . '</BEDRAGEXCL>
                    <BTWBEDRAG>' . $this->helper->_xmlAmountPrepare($fTaxAmount) . '</BTWBEDRAG>
                    <BTWPERC>'. $this->helper->xmlPrepare($sVatCode) . '</BTWPERC>
                    <TEGENREKENING>' . $this->helper->xmlPrepare($iGbRekening) . '</TEGENREKENING>
                    <KOSTENPLAATS>' . $this->helper->xmlPrepare($iCostcenter) . '</KOSTENPLAATS>
                </MUTATIEREGEL>';
    }

    private function _getItemType( &$oItem )
    {
        $oOrderItem = null;
        if ( $oItem instanceof \Magento\Sales\Model\Order\Item)
        {
            $oOrderItem = $oItem;
        }
        else
        {
            $oOrderItem = $oItem->getOrderItem();
        }

        $sProductId = $oItem->getProductId();
        $sType = 'unknown';
        if ( method_exists($oItem, 'isDummy') && $oItem->isDummy() )
        {
            $sType = 'dummy';
        }
        elseif ( preg_match('|^weee_|', $sProductId) )
        {
            $sType = 'weee';
        }
        elseif ('shipping' == $sProductId)
        {
            $sType = 'shipping';
        }
        elseif ('adjustment_fee' == $sProductId)
        {
            $sType = 'adjustment';
        }
        elseif ('payment_fee' == $sProductId)
        {
            $sType = 'paymentfee';
        }
        elseif ( !empty($oOrderItem) )
        {
            $sType = $oOrderItem->getProductType();
            if ( $oOrderItem->getParentItemId() )
            {
                $sType = 'child';
            }
        }
        else
        {
            $sType = 'no_orderitem';
        }
        return $sType;
   }

     private function _Find_Ebvatcode($tax_type,$itemId, $oOrder)
    {


        $sVatCode = false;
        $sMagCode = false;

        if (empty($sVatCode) && empty($sMagCode))
        {

            // Try finding by percentage in the Order's Full Tax Info
            $aVatPercToMagCode = array();
            $aTaxInfo = $this->taxManagement->getOrderTaxDetails($oOrder->getId());
            if($aTaxInfo->getItems()){
                foreach ($aTaxInfo->getItems() as $aTaxRow)
                {
                    if($aTaxRow->getType() == $tax_type){
                        if($tax_type == 'product' && $aTaxRow->getItemId() != $itemId){
                            continue;
                        }
                        foreach($aTaxRow['applied_taxes'] as $aTaxRate){
                            $sMagCode = $aTaxRate['code'];
                            break;
                        }
                        break;
                    }
                }
            }
        }

        if (!empty($sMagCode))
        {
            $oRateModel = $this->taxrateFactory->create()->load($sMagCode,'code');
            $sRateEbvatcode = $oRateModel->getTaxEbvatcode();
            if (!empty($sRateEbvatcode))
            {
                $sVatCode = $sRateEbvatcode;
            }
        }
        /*
        if (empty($sVatCode))
        {
            // Receiving vatcode failed, use fallback vat code choosing method
            if (0 == $fVatPercent)
            {
                $sVatCode = 'GEEN';
            }
            elseif (6 == $fVatPercent)
            {
                $sVatCode = 'LAAG_VERK';
            }
            elseif (19 == $fVatPercent)
            {
                $sVatCode = 'HOOG_VERK';
            }
            else
            {
                $sVatCode = 'HOOG_VERK_21';
            }
        }*/

        return $sVatCode;
    }

     /**
     * Post the XML of one order or invoice to the e-Boekhouden API
     *
     * @param string  $sXml   the XML to post
     * @return array          with values ($iOrdersMutatie,$iOrdersExist,$sErrorMsg,$sInfoMsg)
     */
    protected function _postMutatieXml($sMutatieXml, $aSettings, $sAction = 'ADD_MUTATIE')
    {
        $sErrorMsg = '';
        $sInfoMsg = '';
        $sMutatieNr = false;
        $iOrdersExist = 0;

        $sXml = '<?xml version="1.0" encoding="UTF-8" ?>';
        $sXml .= '
<API>
  <ACTION>' . $this->helper->xmlPrepare($sAction) . '</ACTION>
  <VERSION>' . $this->helper->xmlPrepare('1.0') . '</VERSION>
  <SOURCE>' . $this->helper->xmlPrepare('Magento') . '</SOURCE>
  <AUTH>
    <GEBRUIKERSNAAM>' . $this->helper->xmlPrepare($aSettings['sConUser']) . '</GEBRUIKERSNAAM>
    <WACHTWOORD>' . $this->helper->xmlPrepare($aSettings['sConWord']) . '</WACHTWOORD>
    <GUID>' . $this->helper->xmlPrepare($aSettings['sConGuid']) . '</GUID>
  </AUTH>';
        $sXml .= $sMutatieXml;
        $sXml .= '
</API>';



        $oClient = new \Zend\Http\Client();
        $oClient->setUri('https://secure.e-boekhouden.nl/bh/api.asp');
        $oClient->setParameterPost(array('xml'=> $sXml));
        $oClient->setMethod('POST');
        $oResponse = $oClient->send();

        if (!$oResponse->isOk())
        {
            $sErrorMsg .= __('HTTP fout %1 ontvangen van API: %2', $oResponse->getStatus(),
                                                          $oResponse->getMessage()) . "\n";
        }
        else
        {
            $sResponse = $oResponse->getBody();
            if (empty($sResponse))
            {
                $sErrorMsg .= __('Fout: Leeg antwoord ontvangen van API') . "\n";
            }
            else
            {
                $oData = @simplexml_load_string($sResponse);

               # debug($oData);
               # die();
                if (empty($oData))
                {
                    $sShowResponse = htmlspecialchars(strip_tags($sResponse));
                    $sShowResponse = preg_replace('#\s*\n#', "\n", $sShowResponse);
                    $sErrorMsg .= __('Fout in van API ontvangen XML: parsen mislukt') . "\n" . $sShowResponse . "\n";
                }
                elseif (empty($oData->RESULT))
                {
                    $sErrorMsg .= __('Fout in van API ontvangen XML: "RESULT" veld is leeg') . "\n";
                }
                elseif ('ERROR' == strval($oData->RESULT))
                {
                    if ('M006' == strval($oData->ERROR->CODE))
                    {
                        $iOrdersExist++;
                    }
                    else
                    {
                        $sErrorMsg .= __('Fout %1: %2', $oData->ERROR->CODE,
                                                                      $oData->ERROR->DESCRIPTION) . "\n";
                    }
                }
                elseif ('OK' == strval($oData->RESULT))
                {
                    # Inititiate sMutatieNr to true, for the situation that the mutatie exists in EB.nl, and the MutatieNr stays the same
                    $sMutatieNr = true;
                }
                else
                {
                    $sErrorMsg .= __('Onbekend resultaat van API ontvangen: %1' . $oData->RESULT) . "\n";
                }
                if (!empty($oData->MUTNR))
                {
                    $sMutatieNr = strval($oData->MUTNR);
                }
            }
        }
        return array($sMutatieNr, $iOrdersExist, $sErrorMsg, $sInfoMsg);
    }
}

