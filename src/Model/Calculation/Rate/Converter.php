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
namespace Eboekhouden\Export\Model\Calculation\Rate;

use Magento\Tax\Model\Calculation\Rate\Converter as Original;

/**
 * Tax Rate Model converter.
 *
 * Converts a Tax Rate Model to a Data Object or vice versa.
 */
class Converter extends Original
{

    public function populateTaxRateData($formData)
    {
        $taxRate = $this->taxRateDataObjectFactory->create();
        $taxRate->setId($this->extractFormData($formData, 'tax_calculation_rate_id'))
            ->setTaxCountryId($this->extractFormData($formData, 'tax_country_id'))
            ->setTaxRegionId($this->extractFormData($formData, 'tax_region_id'))
            ->setTaxPostcode($this->extractFormData($formData, 'tax_postcode'))
            ->setCode($this->extractFormData($formData, 'code'))
            ->setRate($this->extractFormData($formData, 'rate'))
            ->setTaxEbvatcode($this->extractFormData($formData, 'tax_ebvatcode'));

        if (isset($formData['zip_is_range']) && $formData['zip_is_range']) {
            $taxRate->setZipFrom($this->extractFormData($formData, 'zip_from'))
                ->setZipTo($this->extractFormData($formData, 'zip_to'))->setZipIsRange(1);
        }

        if (isset($formData['title'])) {
            $titles = [];
            foreach ($formData['title'] as $storeId => $value) {
                $titles[] = $this->taxRateTitleDataObjectFactory->create()->setStoreId($storeId)->setValue($value);
            }
            $taxRate->setTitles($titles);
        }

        return $taxRate;
    }
}
