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
namespace Eboekhouden\Export\Block\Adminhtml\Rate;

use Magento\Tax\Block\Adminhtml\Rate\Form as Original;
use Eboekhouden\Export\Model\Tax\Attribute\Ebtaxcode;


class Form extends Original
{
	// revert template to original module
    protected $_template = 'Magento_Tax::rate/form.phtml';

    protected function _prepareForm()
    {
    	$response = parent::_prepareForm();

    	$fieldset = $this->getForm()->getElement('base_fieldset');


    	// dont want to override __construct so just init here for options
    	$ebtaxcodes = new Ebtaxcode;


        $fieldset->addField(
            'tax_ebvatcode',
            'select',
            [
                'name' => 'tax_ebvatcode',
                'label' => __('e-Boekhouden.nl BTW Code'),
                'title' => __('e-Boekhouden.nl BTW Code'),
                'class' => 'required-entry',
                'required' => true,
                'values' => $ebtaxcodes->toOptionArray()
            ]
        );


    	return $response;

    }
}
