<?php


namespace Eboekhouden\Export\Block\Config;

use Magento\Config\Block\System\Config\Form\Field;

Class Version extends Field {

	public function  __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Eboekhouden\Export\Helper\Data $helper,
        array $data = array()
    ) {
        $this->helper = $helper;
        parent::__construct($context, $data);
    }


 	protected function _renderValue(\Magento\Framework\Data\Form\Element\AbstractElement $element) {
		return '<td class="scope-label"><span>'.  $this->helper->getExtensionVersion() . '</span></td>';
	}

}
