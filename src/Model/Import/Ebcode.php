<?php
/**
 * NOTICE OF LICENSE
 *
 * The MIT License
 *
 * Copyright (c) 2012 e-Boekhouden.nl
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

namespace Eboekhouden\Export\Model\Import;

use Zend\Http\Client;


class Ebcode
{
    // admin session
    protected $_session;

    protected $_messageManager;


    // eboekhoud helper
    protected $_helper;

    protected $iDefault = 0; // can be overridden in sub classes

    private $_scopeConfig;



    public function __construct(
        \Magento\Backend\Model\Auth\Session $authSession,
        \Eboekhouden\Export\Helper\Data $helper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Message\ManagerInterface $_messageManager
    ) {
        $this->_session = $authSession;
        $this->_helper = $helper;
        $this->_scopeConfig = $scopeConfig;
        $this->_messageManager = $_messageManager;

    }

    public function importCodesForDropdown($bShowConfigIncompleteMsg = true)
    {
        $aResult = array();
        $aCodes = $this->importCodes($bShowConfigIncompleteMsg);
        if (!is_array($aCodes))
        {
            $aCodes = array();
        }
        $aDefaultOption = array();
        $aDefaultOption['value'] = $this->iDefault;
        if (!empty($aCodes[$aDefaultOption['value']]))
        {
            $aDefaultOption['label'] = $aCodes[$aDefaultOption['value']];
        }
        else
        {
            $aDefaultOption['label'] = $aDefaultOption['value'] . ' -';
        }
        $aDefaultOption['label'] .= ' ' . __('(standaard)');
        $aResult[] = $aDefaultOption;

        if (is_array($aCodes))
        {
            foreach ($aCodes as $sKey => $sValue)
            {
                $aOption = array();
                $aOption['value'] = trim($sKey);
                $aOption['label'] = $sValue;
                $aResult[] = $aOption;
            }
        }
        return $aResult;
    }

    function getCodes($sAction)
    {
        $oResult = false;

        if($this->_session->isLoggedIn())
        {
            $sErrorMsg = '';
            $sInfoMsg = '';

            $oClient = new Client('https://secure.e-boekhouden.nl/bh/api.asp');

            /* @var $oHelper Eboekhouden_Export_Helper_Data */
            $aSettings = $this->_helper->getConnectorSettings();

            if (!empty($aSettings['bConOK']))
            {
                $sXml = '<?xml version="1.0"?>' . "\n";
                $sXml .= '
        <API>
          <ACTION>' . $this->_helper->xmlPrepare($sAction) . '</ACTION>
          <VERSION>1.0</VERSION>
          <SOURCE>Magento</SOURCE>
          <AUTH>
            <GEBRUIKERSNAAM>' . $this->_helper->xmlPrepare($aSettings['sConUser']) . '</GEBRUIKERSNAAM>
            <WACHTWOORD>' . $this->_helper->xmlPrepare($aSettings['sConWord']) . '</WACHTWOORD>
            <GUID>' . $this->_helper->xmlPrepare($aSettings['sConGuid']) . '</GUID>
          </AUTH>
        </API>';
                if ($this->_scopeConfig->getValue('eboekhouden/settings/showxml',\Magento\Store\Model\ScopeInterface::SCOPE_STORE))
                {
                    $sInfoMsg .= 'posted xml:<BR>' . "\n";
                    $sInfoMsg .= '<xmp style="font-weight:normal">';
                    $sInfoMsg .= $sXml . "\n";
                    $sInfoMsg .= '</xmp><BR>' . "\n";
                }

                $oClient->setParameterPost(array('xml' =>  $sXml));
                $oClient->setMethod('POST');

                $oResponse = $oClient->send();


                if (!$oResponse->isOk())
                {
                    $sErrorMsg .= __('HTTP fout %1 ontvangen van API: %2', $oResponse->getStatusCode(), $oResponse->getReasonPhrase()) . "\n";
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
                        if ($this->_scopeConfig->getValue('eboekhouden/settings/showxml',\Magento\Store\Model\ScopeInterface::SCOPE_STORE))
                        {
                            $sInfoMsg .= 'response xml:<BR>' . "\n";
                            $sInfoMsg .= '<xmp style="font-weight:normal">';
                            $sInfoMsg .= $sResponse . "\n";
                            $sInfoMsg .= '</xmp><BR>' . "\n";
                        }

                        $oResult = @simplexml_load_string($sResponse);
                        if (empty($oResult))
                        {
                            $oResult = false;
                            $sShowResponse = htmlspecialchars(strip_tags($sResponse));
                            $sShowResponse = preg_replace('#\s*\n#', "\n", $sShowResponse);
                            $sErrorMsg .= __('Fout in van API ontvangen XML: parsen mislukt') . "\n" . $sShowResponse . "\n";
                        }
                    }
                }

                if ($sInfoMsg)
                {
                    $this->_messageManager->addNotice($sInfoMsg);
                }
                if ($sErrorMsg)
                {
                    $this->_messageManager->addError(nl2br($sErrorMsg));
                }
            } // if connection ok
        } // if isAdmin
        return $oResult;
    }



}
