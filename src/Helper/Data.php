<?php
/**
 * NOTICE OF LICENSE
 *
 * The MIT License
 *
 * Copyright (c) 2016 e-Boekhouden.nl
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package    Eboekhouden_Export
 * @copyright  Copyright (c) 2016 e-Boekhouden.nl
 * @license    http://opensource.org/licenses/mit-license.php  The MIT License
 * @author     e-Boekhouden.nl
 */

namespace Eboekhouden\Export\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

	private $_ModuleResource;
    private $_scopeConfig;
    protected $_request;
    private $_urlInterface;
    private $_messageManager;

	public function __construct(
		\Magento\Framework\Module\ModuleResource $ModuleResource,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Framework\Message\ManagerInterface $messageManager
	){
	   $this->_ModuleResource = $ModuleResource;
       $this->_scopeConfig = $scopeConfig;
       $this->_request = $request;
       $this->_urlInterface = $urlInterface;
       $this->_messageManager = $messageManager;
	}
    public function getExtensionVersion()
    {
    	$model = $this->_ModuleResource->getDataVersion('Eboekhouden_Export');
        return $model;
    }





    public function getConnectorSettings($mStore = null){
    	$sErrorMsg = '';
        $aSettings = array();

        #$mStore = 2;

        $aSettings['bConOK'] = 0;
        $aSettings['sConUser'] = trim($this->_scopeConfig->getValue('eboekhouden/connector/username', \Magento\Store\Model\ScopeInterface::SCOPE_STORE,$mStore));
        $aSettings['sConWord'] = trim($this->_scopeConfig->getValue('eboekhouden/connector/securitycode1', \Magento\Store\Model\ScopeInterface::SCOPE_STORE,$mStore));
        $aSettings['sConGuid'] = trim($this->_scopeConfig->getValue('eboekhouden/connector/securitycode2', \Magento\Store\Model\ScopeInterface::SCOPE_STORE,$mStore));
        $aSettings['sShipLedgerAcc'] = intval( trim($this->_scopeConfig->getValue('eboekhouden/settings/shippingledgeraccount', \Magento\Store\Model\ScopeInterface::SCOPE_STORE,$mStore)));
        $aSettings['sAdjustmentLedgerAcc'] = intval( trim($this->_scopeConfig->getValue('eboekhouden/settings/adjustmentledgeraccount', \Magento\Store\Model\ScopeInterface::SCOPE_STORE,$mStore)));
        $aSettings['sPaymentFeeLedgerAcc'] = intval( trim($this->_scopeConfig->getValue('eboekhouden/settings/paymentfeeledgeraccount', \Magento\Store\Model\ScopeInterface::SCOPE_STORE,$mStore)));
        $aSettings['sShipCostcenter'] = intval( trim($this->_scopeConfig->getValue('eboekhouden/settings/shippingcostcenter', \Magento\Store\Model\ScopeInterface::SCOPE_STORE,$mStore)));

        if ( empty($aSettings['sShipLedgerAcc']) )
        {
            $aSettings['sShipLedgerAcc'] = 8000;
        }
        if ( empty($aSettings['sShipCostcenter']) )
        {
            $aSettings['sShipCostcenter'] = 0;
        }

        if (empty($aSettings['sConUser']) || empty($aSettings['sConWord']) || empty($aSettings['sConGuid']))
        {

            $sCurrentUrl = $this->_urlInterface->getCurrentUrl();
            if (
                !$this->_request->getParam('eboekhouden_config_error', 0) &&
                !preg_match('|/system_config/|i',$sCurrentUrl)
               )
            {
                $aSettings['bConOK'] = 0;
                $sErrorMsg .= __('Configuratie is niet volledig ingevuld, ga naar het menu "%1","%2" en kies "e-Boekhouden.nl" uit de zijbalk. Vul de gegevens in onder "Connector Login Gegevens"',__('Stores'), __('Configuration'));

                $this->_messageManager->addError($sErrorMsg);
               # Mage::getSingleton('core/session')->addError($sErrorMsg);
                $this->_request->setParam('eboekhouden_config_error', 1);
            }
        }
        else
        {
            $aSettings['bConOK'] = 1;
        }


        return $aSettings;
    }

     /**
     * Prepare an output string for use in XML for e-Boekhouden.nl
     *
     * @param string $sValue
     * @return string
     */
    public function xmlPrepare($sValue)
    {
        $sResult = $sValue;
        $sResult = html_entity_decode($sResult); // remove previous HTML encoding
        // No utf8_encode() needed, all data in Magento is UTF-8.
        $sResult = htmlspecialchars($sResult, ENT_QUOTES, 'UTF-8'); // encode < > & ' "
        return $sResult;
    }

     /**
     * Prepare an output string for use in XML for e-Boekhouden.nl
     *
     * @param string $sValue
     * @return string
     */
    public function _xmlAmountPrepare($fValue)
    {
        return $this->xmlPrepare(round(floatval($fValue), 2));
    }


}
