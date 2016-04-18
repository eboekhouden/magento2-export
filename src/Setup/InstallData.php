<?php

namespace Eboekhouden\Export\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface
{
/**
     * EAV setup factory
     *
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * Init
     *
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }

     public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        /**
         * Add attributes to the eav/attribute
         */

        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'eboekhouden_grootboekrekening',
            [
                'group' => 'e-Boekhouden.nl',
                'type' => 'int',
                'backend' => '',
                'frontend' => '',
                'label' => 'Grootboekrek. e-Boekhouden.nl',
                'input' => 'select',
                'class' => '',
                'source' => 'Eboekhouden\Export\Model\Product\Attribute\Ledgeraccount',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible' => true,
                'required' => false,
                'user_defined' => false,
                'default' => 0,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'used_in_product_listing' => true,
                'unique' => false,
                'apply_to' => ''
            ]
        );

         $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'eboekhouden_costcenter',
            [
                'group' => 'e-Boekhouden.nl',
                'type' => 'int',
                'backend' => '',
                'frontend' => '',
                'label' => 'Kostenplaats e-Boekhouden.nl',
                'input' => 'select',
                'class' => '',
                'source' => 'Eboekhouden\Export\Model\Product\Attribute\Costcenter',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible' => true,
                'required' => false,
                'user_defined' => false,
                'default' => 0,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'used_in_product_listing' => true,
                'unique' => false,
                'apply_to' => ''
            ]
        );
    }
}
