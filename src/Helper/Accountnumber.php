<?php

/*
 * The purpose of this class is to be overridden by specific shop implementations.
 */

//  Internal use in the Eboekhouden_Export extension:
//  $oAccountNumberHelper =  /* @var Eboekhouden_Export_Helper_AccountNumber $oAccountNumberHelper */

namespace Eboekhouden\Export\Helper;

class AccountNumber extends \Magento\Framework\App\Helper\AbstractHelper
{

    function getBalanceAccountNumber( $iCurrentValue, $oContainer )
    {
        return $iCurrentValue;
    }

    function getLedgerAccountNumber( $iCurrentValue, $oContainer, $oItem )
    {
        return $iCurrentValue;
    }

    function getCostCenterNumber( $iCurrentValue, $oContainer, $oItem )
    {
        return $iCurrentValue;
    }
}
