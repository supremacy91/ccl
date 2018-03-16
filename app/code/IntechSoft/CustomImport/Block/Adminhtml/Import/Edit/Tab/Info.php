<?php

namespace IntechSoft\CustomImport\Block\Adminhtml\Import\Edit\Tab;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\Data\FormFactory;
use Magento\Cms\Model\Wysiwyg\Config;
use IntechSoft\CustomImport\Model\System\Config\RootCategories;
use IntechSoft\CustomImport\Model\System\Config\AttributeSet;
use IntechSoft\CustomImport\Model\System\Config\AttributeGroupe;

class Info extends Generic implements TabInterface
{
    /**
     * @var \Magento\Cms\Model\Wysiwyg\Config
     */
    protected $_wysiwygConfig;

    /**
     * @var \IntechSoft\CustomImport\Model\System\Config\RootCategories
     */
    protected $_rootCategories;

    /**
     * @var \IntechSoft\CustomImport\Model\System\Config\AttributeSet
     */
    protected $_attributesSet;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param Config $wysiwygConfig
     * @param Status $newsStatus
     * @param RootCategories $_rootCategories
     * @param AttributeSet $_attributesSet
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        Config $wysiwygConfig,
        RootCategories $rootCategories,
        AttributeSet $attributesSet,
        array $data = []
    ) {
        $this->_rootCategories = $rootCategories;
        $this->_attributesSet = $attributesSet;
        $this->_wysiwygConfig = $wysiwygConfig;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare form fields
     *
     * @return \Magento\Backend\Block\Widget\Form
     */
    protected function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('import');
        $form->setFieldNameSuffix('import');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('General')]
        );


        $fieldset->addField(
            'import_images_file_dir',
            'text',
            [
                'name'          => 'import_images_file_dir',
                'label'         => __('Images File Directory'),
                'note'       => __('For Type "Local Server" use relative path to Magento installation, e.g. var/export, var/import, by default used pub/media/import folder')
            ]
        );

        $fieldset->addField(
            'select_type_attributes',
            'text',
            [
                'name'          => 'select_type_attributes',
                'label'         => __('Attributes Type Select'),
                'note'       => __('write attribute codes with type "select" using "," as separator, e.g. - "color, size, brand". "color" and "size" are default select attributes')
            ]
        );

        $fieldset->addField(
            'clear_select_attributes',
            'checkbox',
            [
                'name'          => 'clear_select_attributes',
                'label'         => __('Clear select Attributes'),
                'checked' => false,
                'value'  => '1'
            ]
        );

        $fieldset->addField(
            'root_category',
            'select',
            [
                'name'      => 'root_category',
                'label'     => __('Choose Root Category'),
                'options'   => $this->_rootCategories->toOptionArray(),
                'note'       => __('use "Default Category" by default')
            ]
        );

        $fieldset->addField(
            'attribute_set',
            'select',
            [
                'name'      => 'attribute_set',
                'label'     => __('Choose attribute Set for new attributes'),
                'options'   => $this->_attributesSet->toOptionArray(),
                'note'       => __('use "Default" attribute set by default')
            ]
        );

        $fieldset->addField(
            'put_remaining_product_stock_to_0',
            'checkbox',
            [
                'name'          => 'put_remaining_product_stock_to_0',
                'label'         => __('Set quantity to 0 for products are absent in csv file'),
                'checked' => false,
                'value'  => '1'
            ]
        );

        $fieldset->addField(
            'upload_file',
            'file',
            [
                'label'         => __('Upload file'),
                'required'      => true
            ]
        );

        /*$data = $model->getData();
        $form->setValues($data);*/
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * Prepare label for tab
     *
     * @return string
     */
    public function getTabLabel()
    {
        return __('Custom Import');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return __('Custom Import');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }
}