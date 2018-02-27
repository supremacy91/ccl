<?php

namespace Biztech\Manufacturer\Setup;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * @codeCoverageIgnore
 */
class UpgradeSchema implements UpgradeSchemaInterface {
    protected $_resultPage;
    /**
     * {@inheritdoc}
     */

      public function __construct(
        \Magento\Cms\Model\PageFactory $pageFactory
    ) {
        $this->_pageFactory = $pageFactory;
    }

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context){
        $setup->startSetup();
        
        if (version_compare($context->getVersion(), '1.0.1') < 0) {            
            // initial version
        }

        if (version_compare($context->getVersion(), '1.0.3') < 0) {
            $page = $this->_pageFactory->create();
            $page->setTitle('Dames Heren Merken')
                ->setIdentifier('merken/dames-heren-merken')
                ->setIsActive(true)
                ->setPageLayout('1column')
                ->setStores(array(0))
                ->setContent('{{block class="Biztech\Manufacturer\Block\Dames\Index" name="dames" as="dames" template="Biztech_Manufacturer::manufacturer/dames_heren_merken.phtml"}}')
                ->save();
        }

        $setup->endSetup();

    }
}
