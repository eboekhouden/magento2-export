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

namespace Eboekhouden\Export\Model\Import;


class Costcenter extends Ebcode
{

    protected $iDefault = 0;

    /**
     * Import Cost center codes from e-Boekhouden.nl.
     *
     * @return array with ($aResult,$sErrorMsg). $aResult: key=code value=desc
     */
    public function importCodes()
    {
        $aResult = array();
        $oData = $this->getCodes('LIST_KOSTENPLAATSEN');
        if (!empty($oData))
        {
            if (!isset($oData->RESULT->KOSTENPLAATSEN))
            {
                $sErrorMsg = __('Fout in van API ontvangen XML: RESULT.KOSTENPLAATSEN is niet gevonden') . "\n";
                $this->_messageManager->addError(nl2br($sErrorMsg));
            }
            else
            {
                foreach ($oData->RESULT->KOSTENPLAATSEN->KOSTENPLAATS as $oCostCenter)
                {
                    $iCode = intval($oCostCenter->KP_ID);
                    $sDesc = $iCode . ' - ' . strval($oCostCenter->OMSCHRIJVING);
                    $aResult[$iCode] = $sDesc;
                }
            }
        }
        return $aResult;
    }

}
