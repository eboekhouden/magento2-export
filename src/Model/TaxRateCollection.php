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
namespace Eboekhouden\Export\Model;


use Magento\Tax\Model\TaxRateCollection as Original;
use Magento\Tax\Api\Data\TaxRateInterface as TaxRate;

/**
 * Tax rate collection for a grid backed by Services
 */

class TaxRateCollection extends Original
{
    protected function createTaxRateCollectionItem(TaxRate $taxRate)
    {
        $collectionItem = new \Magento\Framework\DataObject();
        $collectionItem->setTaxCalculationRateId($taxRate->getId());
        $collectionItem->setCode($taxRate->getCode());
        $collectionItem->setTaxCountryId($taxRate->getTaxCountryId());
        $collectionItem->setTaxRegionId($taxRate->getTaxRegionId());
        $collectionItem->setRegionName($taxRate->getRegionName());
        $collectionItem->setTaxPostcode($taxRate->getTaxPostcode());
        $collectionItem->setRate($taxRate->getRate());
        $collectionItem->setTaxEbvatcode($taxRate->getTaxEbvatcode());

        $collectionItem->setTitles($this->rateConverter->createTitleArrayFromServiceObject($taxRate));

        if ($taxRate->getZipTo() != null && $taxRate->getZipFrom() != null) {
            /* must be a "1" for existing code (e.g. JavaScript) to work */
            $collectionItem->setZipIsRange("1");
            $collectionItem->setZipFrom($taxRate->getZipFrom());
            $collectionItem->setZipTo($taxRate->getZipTo());
        } else {
            $collectionItem->setZipIsRange(null);
            $collectionItem->setZipFrom(null);
            $collectionItem->setZipTo(null);
        }

        return $collectionItem;
    }
}
