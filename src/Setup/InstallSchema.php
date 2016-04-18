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
namespace Eboekhouden\Export\Setup;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{

    /**
     * EAV setup factory
     *
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        $connection = $installer->getConnection();

        $columns = [

            [
                'table' => $installer->getTable('tax_calculation_rate'),
                'name' => 'tax_ebvatcode',
                'definition' => [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_VARBINARY,
                    'length' => '20',
                    'nullable' => true,
                    'comment' => 'Eboekhouden ebvatcode',
                ],
            ],
            [
                'table' => $installer->getTable('sales_invoice'),
                'name' => 'eboekhouden_mutatie',
                'definition' => [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'nullable' => true,
                    'comment' => 'Eboekhouden Mutatie',
                ],
            ],
			[
				'table' => $installer->getTable('sales_invoice_grid'),
				'name' => 'eboekhouden_mutatie',
				'definition' => [
					'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
					'nullable' => true,
					'comment' => 'Eboekhouden Mutatie',
				],
			],
            [
                'table' => $installer->getTable('sales_creditmemo'),
                'name' => 'eboekhouden_mutatie',
                'definition' => [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'nullable' => true,
                    'comment' => 'Eboekhouden Mutatie',
                ],
            ],
            [
                'table' => $installer->getTable('sales_creditmemo_grid'),
                'name' => 'eboekhouden_mutatie',
                'definition' => [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'nullable' => true,
                    'comment' => 'Eboekhouden Mutatie',
                ],
            ]
        ];

        foreach ($columns as $column) {
            $connection->addColumn($column['table'], $column['name'], $column['definition']);
        }
        $installer->endSetup();
    }

}
