<?php

namespace IntechSoft\CustomImport\Model;

use \Magento\Framework\App\Filesystem\DirectoryList;
use \Magento\Eav\Model\Config;
use \Magento\Catalog\Model\Product as ProductEntity;
use \Magento\Catalog\Model\AbstractModel;

class Attributes extends AbstractModel
{
    const ATTRIBUTE_IMAGE_FOLDER = 'attribute/swatch';

    /**
     * @var \Magento\UrlRewrite\Service\V1\Data\UrlRewriteFactory
     */
    private $_urlRewriteFactory;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory
     */
    protected $attrOptionCollectionFactory;

    /**
     * @var \Magento\Eav\Model\Entity\Attribute\SetFactory
     */
    protected $_attributeSetFactory;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactoryt
     */
    protected $_groupCollectionFactory;

    /**
     * @var \Magento\Catalog\Helper\Product
     */
    protected $productHelper;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory
     */
    protected $attributeFactory;

    protected $_entityTypeId;

    protected $csvFile;

    /**
     * @var \Magento\Eav\Model\AttributeRepository
     */
    protected $_attributeRepository;

    /**
     * @var \Magento\Eav\Api\AttributeOptionManagementInterface
     */
    protected $_attributeOptionManagement;

    /**
     * @var \Magento\Framework\File\Csv $csvProcessor
     */
    protected $csvProcessor;

    /**
     * @var \Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory
     */
    protected $optionLabelFactory;

    /**
     * @var \Magento\Eav\Api\Data\AttributeOptionInterfaceFactory
     */
    protected $optionFactory;

    /**
     * @var \Magento\Swatches\Helper\Media
     */
    protected $_swatchHelper;

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $mediaDirectory;

    /**
     * @var \IntechSoft\CustomImport\Helper\Import
     */
    protected $customImportHelper;

    /**
     * @var \IntechSoft\CustomImport\Helper\Import
     */
    protected $_attributeUninstaller;

    /**
     * @var array
     */
    protected $_collectedAttributes = [];

    /**
     * @var array
     */
    protected $csvFileData = [];

    /**
     * @var array
     */
//    protected $_selectAttributes = ['color', 'size', 'brand', 'brand_2'];
    protected $_selectAttributes = ['color', 'size', 'brand', 'brand_2', 'color_hex'];

    /**
     * @var array
     */
    protected $_exceptAttributeCodes = ['configurable_variations', 'additional_images', 'color_hex', 'freetext'];

    /**
     * @var bool
     */
    public $allowToContinueImport = true;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    protected $attrbuteSettings = array(
        'attribute_set_id' => '4',
        'attribute_group_code' => 'product-details',
        'select_type_attributes' => '',
        'clear_select_attributes' => ''
    );

    /**
     * Attributes constructor.
     *
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory $attrOptionCollectionFactory
     * @param \Magento\Eav\Model\Entity\Attribute\SetFactory                             $attributeSetFactory
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory  $groupCollectionFactory
     * @param \Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory                  $attributeFactory
     * @param \Magento\Catalog\Helper\Product                                            $productHelper
     * @param \Magento\Eav\Model\AttributeRepository                                     $attributeRepository
     * @param \Magento\Eav\Api\AttributeOptionManagementInterface                        $attributeOptionManagement
     * @param \Magento\Framework\File\Csv                                                $csvProcessor
     * @param \Magento\Framework\ObjectManagerInterface                                  $objectmanager
     * @param \Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory                 $optionLabelFactory
     * @param \Magento\Eav\Api\Data\AttributeOptionInterfaceFactory                      $optionFactory
     * @param \Magento\Framework\Registry                                                $registry
     * @param \Magento\Swatches\Helper\Media                                             $swatchHelper
     * @param \Magento\Framework\Filesystem                                              $filesystem
     * @param \IntechSoft\CustomImport\Helper\Import                                     $customImportHelper
     * @param Attribute\Uninstall                                                        $attributeUninstaller
     * @param \Biztech\Manufacturer\Model\Config                                         $config
     * @param \Biztech\Manufacturer\Model\Manufacturer                                   $manufacturer
     * @param \Biztech\Manufacturer\Model\Manufacturertext                               $manufacturertext
     * @param \Biztech\Manufacturer\Helper\Data                                          $helperData
     * @param \Magento\UrlRewrite\Model\UrlRewrite                                       $urlRewrite
     * @param \Magento\UrlRewrite\Service\V1\Data\UrlRewriteFactory                      $urlRewriteFactory
     * @param \Magento\UrlRewrite\Model\UrlPersistInterface                              $urlPersist
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory $attrOptionCollectionFactory,
        \Magento\Eav\Model\Entity\Attribute\SetFactory  $attributeSetFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory $groupCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory $attributeFactory,
        \Magento\Catalog\Helper\Product $productHelper,
        \Magento\Eav\Model\AttributeRepository $attributeRepository,
        \Magento\Eav\Api\AttributeOptionManagementInterface $attributeOptionManagement,
        \Magento\Framework\File\Csv $csvProcessor,
        \Magento\Framework\ObjectManagerInterface $objectmanager,
        \Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory $optionLabelFactory,
        \Magento\Eav\Api\Data\AttributeOptionInterfaceFactory $optionFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Swatches\Helper\Media $swatchHelper,
        \Magento\Framework\Filesystem $filesystem,
        \IntechSoft\CustomImport\Helper\Import $customImportHelper,
        \IntechSoft\CustomImport\Model\Attribute\Uninstall $attributeUninstaller,
		/*Custom code to add brand in biztech module*/
		\Biztech\Manufacturer\Model\Config $config,
        \Biztech\Manufacturer\Model\Manufacturer $manufacturer,
        \Biztech\Manufacturer\Model\Manufacturertext $manufacturertext,
		\Biztech\Manufacturer\Helper\Data $helperData,
        \Magento\UrlRewrite\Model\UrlRewrite $urlRewrite,
        \Magento\UrlRewrite\Service\V1\Data\UrlRewriteFactory $urlRewriteFactory,
        \Magento\UrlRewrite\Model\UrlPersistInterface $urlPersist
		/*Custom code to add brand in biztech module*/
    )
    {
        $this->attrOptionCollectionFactory = $attrOptionCollectionFactory;
        $this->_attributeSetFactory = $attributeSetFactory;
        $this->productHelper = $productHelper;
        $this->_groupCollectionFactory = $groupCollectionFactory;
        $this->attributeFactory = $attributeFactory;
        $this->_attributeRepository = $attributeRepository;
        $this->_attributeOptionManagement = $attributeOptionManagement;
        $this->csvProcessor = $csvProcessor;
        $this->optionLabelFactory = $optionLabelFactory;
        $this->optionFactory = $optionFactory;
        // TODO: should be declare field, not automatic declaration
        $this->objectManager = $objectmanager;
        $this->_registry = $registry;
        $this->_swatchHelper = $swatchHelper;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->customImportHelper = $customImportHelper;
        $this->_attributeUninstaller = $attributeUninstaller;

        // TODO: Need to refactor it. Should be created fields, it should not be declared automatically
		/*Custom code to add brand in biztech module*/
		$this->_storeConfig = $config;
        $this->_manufacturerModel = $manufacturer;
        $this->_manufacturertextModel = $manufacturertext;
		$this->_helperData = $helperData;
        $this->_urlRewrite = $urlRewrite;
        $this->_urlRewriteFactory = $urlRewriteFactory;
        $this->_urlPersist = $urlPersist;
		/*Custom code to add brand in biztech module*/

        $this->_entityTypeId = $this->objectManager->create(
            'Magento\Eav\Model\Entity'
        )->setType(
            ProductEntity::ENTITY
        )->getTypeId();
    }

    /**
     * @param $csvFile
     */
    public function checkAddAttributes($csvFile)
    {
        $this->setCsvFile($csvFile);
        $this->setCsvFileData();

        // ### Need to understand for what this method!
        $this->setSelectAttributes();
        $this->collectAttributes();
        $this->collectAttributeOptions();

        // ### It seems that new attributes just add here. there is no update functions!
        $this->addNewAttributes();
    }

    /**
     * set csv file name
     * @param $csvFile
     */
    protected function setCsvFile($csvFile)
    {
        $this->csvFile = $csvFile;
    }

    /**
     * @param $settings
     */
    public function setAttributeSettings($settings)
    {
        if (is_array($settings) && count($settings) > 0) {
            foreach ($settings as $name => $value) {
                if (isset($this->attrbuteSettings[$name]) && $value != '') {
                    $this->attrbuteSettings[$name] = $value;
                }
            }
        }
    }

    protected function setSelectAttributes()
    {
        if ($this->attrbuteSettings['select_type_attributes'] != '') {
            $selectAttributesArray = explode(',', $this->attrbuteSettings['select_type_attributes']);
            foreach ($selectAttributesArray as $attribute) {
                $attribute = trim($attribute);
                if (isset($this->customImportHelper->attributesMapping[$attribute])){
                    $attribute = $this->customImportHelper->attributesMapping[$attribute];
                } elseif (isset($this->customImportHelper->attributesMapping[ucfirst($attribute)])) {
                    $attribute = $this->customImportHelper->attributesMapping[ucfirst($attribute)];
                }
                if (!in_array($attribute, $this->_selectAttributes)) {
                    $this->_selectAttributes[] = $attribute;
                }
            }
        }
    }

    /**
     * @param $attributeCode
     * @param $type
     * @return $this
     * @throws \Exception
     */
    public function createAttributesAndOptions($attributeCode, $type)
    {

        $attribute = $this->createAttribute($attributeCode, $type);

        // Except attribute codes
        $exceptAttributeCodes = $this->_exceptAttributeCodes;

        if ($attribute && !in_array($attributeCode, $exceptAttributeCodes)) {
            $attributeSet = $this->_attributeSetFactory->create();
            $attributeSet->setEntityTypeId($this->_entityTypeId)->load('Default');
            $productDetailsGroupe = $this->_groupCollectionFactory->create()
                ->addFieldToFilter('attribute_group_code', $this->attrbuteSettings['attribute_group_code'])
                ->addFieldToFilter('attribute_set_id', $this->attrbuteSettings['attribute_set_id'])
                ->getFirstItem();

            $attribute->setAttributeSetId($productDetailsGroupe->getAttributeSetId());
            $attribute->setAttributeGroupId($productDetailsGroupe->getAttributeGroupId());
            $attribute->save();

            if ($type == 'select') {
                $this->prepareOptions($attribute);
            }
        }

        return $this;
    }

    /**
     * @param $attributeCode
     * @param $type
     * @return bool
     * @throws \Exception
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    protected function createAttribute($attributeCode, $type)
    {
        if ($type == 'select') {
            $attributeCode = array_keys($attributeCode)[0];
        }
        $attribute = $this->attributeFactory->create();

        $attribute->loadByCode(ProductEntity::ENTITY, $attributeCode);

        // TODO: find out about possibility delete "color" and "size" attributes
        if (isset($this->attrbuteSettings['clear_select_attributes']) && $this->attrbuteSettings['clear_select_attributes'] && $type == 'select') {
            $this->_attributeUninstaller->uninstallAttribute($attribute->getAttributeId());
        }

        $swatchInputType = 'select';
        if (in_array($attributeCode, $this->_selectAttributes)){
            if($attributeCode == 'color') {
                $swatchInputType = 'swatch_visual';
            } else {
                $swatchInputType = 'swatch_text';
            }
        }

        $skipAttributeCode = array_merge(['qty', 'additional_images'], $this->_exceptAttributeCodes);
        if (is_null($attribute->getId()) && !in_array($attributeCode, $skipAttributeCode)) {
            $attribute->addData([
                'entity_type_id'    => $this->_entityTypeId,
                'attribute_code'    => $attributeCode,
                'frontend_input'    => $type,
                'is_required'       => 0,
                'source_model'      => $this->productHelper->getAttributeSourceModelByInputType($type),
                'backend_model'     => $this->productHelper->getAttributeBackendModelByInputType($type),
                'backend_type'      => $attribute->getBackendTypeByInput($type),
                'frontend_label'    => array(ucfirst($attributeCode)),
                'is_global'         => \Magento\Catalog\Model\ResourceModel\Eav\Attribute::SCOPE_GLOBAL,
                /*'swatch_input_type' => ($attributeCode == 'color' ? 'swatch_visual' :
                    ($attributeCode == 'size' ? 'swatch_text' : 'select')),*/
                'swatch_input_type' => $swatchInputType,
                'is_unique'         => 0,
                'is_user_defined'   => 1
            ]);

            return $attribute;
        }

        if ($type == 'select') {
            $this->prepareOptions($attribute);
        }

        return false;
    }
	
	protected function updateUrlKey($model)
    {
        $id = $model->getId();

        $url_key = $model->getUrlKey();
        $storeId = 0;

        if (is_null($url_key) || !is_string($url_key)) {
            throw new \Magento\Framework\Exception\LocalizedException(__("Unknown Error: method - udpateUrlKey()"));
        }

        if ($storeId !== null) {
            $urlRewriteCollection = $this->_urlRewrite->getCollection()
                ->addFieldToFilter('request_path', $url_key);

            if (count($urlRewriteCollection) > 0) {
                foreach ($urlRewriteCollection as $urlRewrite) {
                    if ($urlRewrite->getRequestPath() == $url_key && $urlRewrite->getEntityId() == $id) {
                        $urlRewrite->delete();
                    }
                }
            }

            try {
                $model->save();
                $id = $model->getId();
                if ($storeId == 0) {
                    foreach ($this->_storeConfig->getStoreManager()->getStores() as $store) {
                        $this->objectManager->create('Magento\UrlRewrite\Helper\UrlRewrite')->validateRequestPath($url_key);
                        $urls[] = $this->createUrlRewrite($store->getId(), $url_key, $id);
                    }
                    $this->_urlPersist->replace($urls);
                } else {

                    $this->objectManager->get('Magento\UrlRewrite\Helper\UrlRewrite')->validateRequestPath($url_key);
                    $url[] = $this->createUrlRewrite($storeId, $url_key, $id);

                    $this->_urlPersist->replace($url);
                }
            } catch (Exception $e) {
                //$this->messageManager->addError($e->getMessage());
            }
            return true;
        }
    }

    /**
     * @param     $storeId
     * @param     $url_key
     * @param     $id
     * @param int $redirectType
     * @return mixed
     */
    protected function createUrlRewrite($storeId, $url_key, $id, $redirectType = 0)
    {
        return $this->_urlRewriteFactory->create()->setStoreId($storeId)
            ->setEntityType('manufacturer')
            ->setEntityId($id)
            ->setRequestPath($url_key)
            ->setTargetPath('manufacturer/index/view/id/' . $id)
            ->setIsAutogenerated(1)
            ->setRedirectType($redirectType);
    }


    /**
     * Prepare options for attributes
     *
     * @param $attribute
     * @throws \Exception
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function customPrepareOptions($attribute) {
            if ($newOptions = $this->getNewOptions($attribute)){
                $attributeName = $attribute;
                $attribute = $this->_attributeRepository->get(ProductEntity::ENTITY, $attributeName);
                $attributeId = $attribute->getAttributeId();
                $attributeCode = $attribute->getAttributeCode();


              //  foreach ($newOptions as $optionName) {
                   /* $optionLabel = $this->optionLabelFactory->create();
                    $optionLabel->setStoreId(0);
                    $optionLabel->setLabel($optionName);
                    //$optionLabel->setDefault($optionName);

                    $option = $this->optionFactory->create();
                    $option->setLabel($optionLabel);
                    $option->setStoreLabels([$optionLabel]);
                    $option->setSortOrder(0);
                    $option->setIsDefault(false);*/

                   /* $option=array();
                    $option['attribute_id'] = $attributeId;
                    foreach($newOptions as $key=>$value){
                        $option['value'][$value][0]=$value;

                    }*/

                   // $attribute_arr = ["JRTest", "5JRTest1"];
                    /*$attributeInfo=$this->_attributeFactory->getCollection()
                        ->addFieldToFilter('attribute_code',['eq'=>"size"])
                        ->getFirstItem();
                    $attribute_id = $attributeInfo->getAttributeId();*/
                    $option=array();
                    $option['attribute_id'] = $attributeId;
                    foreach($newOptions as $key=>$value){
                        $option['value'][$value][0]=$value;
                  //  }

                    $eavSetup = $this->objectManager->create('\Magento\Eav\Setup\EavSetup');
                    $eavSetup->addAttributeOption($option);
                    
    
              
                }

           
            }
    }

    /**
     * @param $attribute
     * @throws \Exception
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function prepareOptions($attribute)
    {
        if ($newOptions = $this->getNewOptions($attribute)){
            $attributeName = $attribute;
            $attribute = $this->_attributeRepository->get('catalog_product', $attributeName);
            $attributeId = $attribute->getAttributeId();
            $attributeCode = $attribute->getAttributeCode();

            $option=array();
            $option['attribute_id'] = $attributeId;
            foreach($newOptions as $key=>$value){
                $option['value'][$value][0]=$value;
            }

            //# Insert new option values to EAV
            $eavSetup = $this->objectManager->create('\Magento\Eav\Setup\EavSetup');
            $eavSetup->addAttributeOption($option);

            foreach ($newOptions as $optionName) {
                if($attributeCode == 'brand') {
                    $proceed = false;
                    $collection = $this->_manufacturerModel->getCollection()
                        ->addFieldToFilter('manufacturer_name', $optionName)->getData();
                    if (count($collection) > 0) {
                        $proceed = false;
                    } else {
                        $proceed = true;
                    }
                } else {
                    $proceed = true;
                }
                if($proceed) {
//                    if($attribute->getAttributeCode() == 'color_hex') {
//                        print_r($newOptions);
//                        exit;
//                    }

                    /*Custom code to add brand in biztech module*/
                    if($attribute->getattributeCode() == 'brand') {
                        $data = array();
                        $data['manufacturer_name'] = $optionName;
                        $data['brand_name'] = $optionName;
                        $data['url_key'] = $this->_helperData->clearUrlKey($optionName);
                        $model = $this->_manufacturerModel->setData($data)->save();
                        if ($model->getManufacturerId()) {
                            if ($this->updateUrlKey($model) == null) {
                                return;
                            }
                        }
                        $model->save();
                        $collection_text = $this->_manufacturertextModel
                            ->getCollection()
                            ->addFieldToFilter('manufacturer_id', $model->getManufacturerId());

                        $this->_manufacturertextModel->setData($data)
                            ->setManufacturerId($model->getManufacturerId())->setStoreId(0)->setStatus(1);
                        $this->_manufacturertextModel->save();
                        foreach ($this->_storeConfig->getStoreManager()->getStores() as $store) {
                            $this->_manufacturertextModel->setData($data)
                                ->setManufacturerId($model->getManufacturerId())
                                ->setStoreId($store->getId())
                                ->setStatus(1);

                            $this->_manufacturertextModel->save();
                        }
                        $this->_manufacturerModel->save();
                        $this->_manufacturertextModel->save();
                    }
                    /*Custom code to add brand in biztech module*/
                }
            }
        }
    }

    /*
     * @deprecated
     */
    public function prepareOptions1($attribute)
    {		
        if ($newOptions = $this->getNewOptions($attribute)){
            $attributeName = $attribute;
            $attribute = $this->_attributeRepository->get('catalog_product', $attributeName);
            $attributeId = $attribute->getAttributeId();
            $attributeCode = $attribute->getAttributeCode();


            foreach ($newOptions as $optionName) {
				if($attributeCode == 'brand') {
					$proceed = false;
					$collection = $this->_manufacturerModel->getCollection()
						->addFieldToFilter('manufacturer_name', $optionName)->getData();
					if (count($collection) > 0) {
						$proceed = false;
					} else {
						$proceed = true;
					}
				} else {
					$proceed = true;
				}
				if($proceed) {
					$optionLabel = $this->optionLabelFactory->create();
                    //$optionLabel = $this->optionFactory->create();
					$optionLabel->setStoreId(0);
					$optionLabel->setLabel($optionName);
					//$optionLabel->setDefault($optionName);

					$option = $this->optionFactory->create();
					$option->setLabel($optionLabel);
					$option->setStoreLabels([$optionLabel]);
					$option->setSortOrder(0);
					$option->setIsDefault(false);

                    //$attribute->addData(array('option' => $option));
                    //$attribute->save();

					if (!$this->_attributeOptionManagement->add('catalog_product', $attributeCode, $option)) {
						$this->allowToContinueImport;
					}
					if($attribute->getattributeCode() == 'color_hex') {
						print_r($newOptions);
						exit;
					}

					/*Custom code to add brand in biztech module*/
					if($attribute->getattributeCode() == 'brand') {
						$data = array();
						$data['manufacturer_name'] = $optionName;
						$data['brand_name'] = $optionName;
						$data['url_key'] = $this->_helperData->clearUrlKey($optionName);
						$model = $this->_manufacturerModel->setData($data)->save();
						if ($model->getManufacturerId()) {
							if ($this->updateUrlKey($model) == null) {
								return;
							}
						}
						$model->save();
						$collection_text = $this->_manufacturertextModel
								->getCollection()
								->addFieldToFilter('manufacturer_id', $model->getManufacturerId());

						$this->_manufacturertextModel->setData($data)
							->setManufacturerId($model->getManufacturerId())->setStoreId(0)->setStatus(1);
						$this->_manufacturertextModel->save();
						foreach ($this->_storeConfig->getStoreManager()->getStores() as $store) {
							$this->_manufacturertextModel->setData($data)
								->setManufacturerId($model->getManufacturerId())
								->setStoreId($store->getId())
								->setStatus(1);

							$this->_manufacturertextModel->save();
						}
						$this->_manufacturerModel->save();
						$this->_manufacturertextModel->save();
					}
					/*Custom code to add brand in biztech module*/
				}
            }
        }
    }

    /**
     * collect attributes from csv file
     */
    protected function collectAttributes()
    {
        $this->_collectedAttributes = array();
        foreach ($this->csvFileData[0] as $attributeCode) {
            $this->_collectedAttributes[] = $attributeCode;
        }
    }

    /**
     * collect attributes options for attributes type - select
     */
    protected function collectAttributeOptions()
    {
        $collectedOptions = array();
        foreach ($this->csvFileData as $index => $item) {
            if ($index == 0){
                continue;
            }
            if (count($item) == count($this->_collectedAttributes)) {
                for($i = 0 ; $i < count($this->_collectedAttributes); $i++){
                    if (in_array($this->_collectedAttributes[$i], $this->_selectAttributes)) {
                        $collectedOptions[$this->_collectedAttributes[$i]][] = $item[$i];
                    }

                }
            }
        }

        // ### Here - collect unique attribute options
        foreach ($collectedOptions as $attributeName => $options) {
            $attributeIndex = $this->getColumnImdexByName($attributeName);
            $this->_collectedAttributes[$attributeIndex] = array($attributeName => array_unique($options));
        }
    }

    /**
     * return column index
     * @param $name
     * @return array|bool|int|string
     */
    public function getColumnImdexByName($name)
    {
        if (in_array($name, $this->_selectAttributes)) {
            foreach ($this->_collectedAttributes as $index => $attribute) {
                if (isset($attribute[$name]) && is_array($attribute)){
                    return $index;
                }
            }
        }

        if ($index = array_keys($this->_collectedAttributes, $name)) {
            return $index[0];
        }
        return false;
    }

    /**
     * set data from csv file
     */
    protected function setCsvFileData()
    {
        $this->csvFileData = $this->csvProcessor->getData($this->csvFile);
    }

    /**
     * Add new attributes
     */
    protected function addNewAttributes()
    {
        foreach ($this->_collectedAttributes as $attributeCode) {
            if(is_array($attributeCode)){
                $this->createAttributesAndOptions($attributeCode, 'select');
            } else {
                $this->createAttributesAndOptions($attributeCode, 'text');
            }
        }
    }


    /**
     * Return new options for attribute type select.
     *
     * @param $attributeName
     * @return array|bool
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException
     */
    protected function getNewOptions($attributeName)
    {
        if ($attributeName && !$attributeName->getAttributeCode()) {
            return false;
        }

        $attributeId = $this->_attributeRepository->get('catalog_product', $attributeName)->getAttributeId();
        $options = $this->_attributeOptionManagement->getItems('catalog_product', $attributeId);
        $attributeIndex = $this->getColumnImdexByName($attributeName->getAttributeCode());
        $newOptions = $this->_collectedAttributes[$attributeIndex][$attributeName->getAttributeCode()];
        foreach($options as $option) {
            if (in_array($option->getLabel(), $newOptions)) {
                $newOptions = array_diff($newOptions, array($option->getLabel()));
            }
        }
        if (!count($newOptions)) {
            return false;
        }
        return $newOptions;
    }

    public function convertSizeToSwatches($storeId)
    {
        $attribute = $this->_attributeRepository->get(ProductEntity::ENTITY, 'size');
        if (!$attribute) {
            return;
        }
        $existOption['option'] = $this->addExistingOptions($attribute);
        $attributeData['swatchtext'] = $this->getOptionSwatchText($existOption,$storeId);
        $attribute->addData($attributeData);
        $attribute->save();
    }


    /**
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function convertColorToSwatches()
    {
        $colorMap = $this->_registry->registry('color_data');
        $this->_registry->unregister('color_data');
        if($colorMap) {
            $attribute = $this->_attributeRepository->get(ProductEntity::ENTITY, 'color');
            if (!$attribute) {
                return;
            }
            $attributeData['option'] = $this->addExistingOptions($attribute);
            $attributeData['frontend_input'] = 'select';
            $attributeData['swatch_input_type'] = 'visual';
            $attributeData['update_product_preview_image'] = 1;
            $attributeData['use_product_image_for_swatch'] = 0;
            $attributeData['optionvisual'] = $this->getOptionSwatch($attributeData);
            $attributeData['swatchvisual'] = $this->getOptionSwatchVisual($attributeData, $colorMap);
            $attribute->addData($attributeData);
            $attribute->save();
        }
    }

    /**
     * @param array $attributeData
     * @return array
     */
    private function getOptionSwatchVisual(array $attributeData, $colorMap)
    {

        $optionSwatch = ['value' => []];

            foreach ($attributeData['option'] as $optionKey => $optionValue) {
                if(isset($optionValue)){
                    if (substr($optionValue, 0, 1) == '#' && strlen($optionValue) == 7) {
                        $optionSwatch['value'][$optionKey] = $optionValue;
                    } else if (array_key_exists($optionValue, $colorMap)) {
                        if (strpos($colorMap[$optionValue], "#") !== false){
                            $optionSwatch['value'][$optionKey] = $colorMap[$optionValue];
                        } else {
                            $image = $colorMap[$optionValue];
                            $this->generateSwatchVariations($image);
                            $optionSwatch['value'][$optionKey] = "/" . $image;
                        }

                    } else {
                        $optionSwatch['value'][$optionKey] = '#ffffff';
                    }
                }
            }
        return $optionSwatch;
    }

    /**
     * @param array $attributeData
     * @return array
     */
    protected function getOptionSwatch(array $attributeData)
    {
        $optionSwatch = ['order' => [], 'value' => [], 'delete' => []];
        $i = 0;
        foreach ($attributeData['option'] as $optionKey => $optionValue) {
            $optionSwatch['delete'][$optionKey] = '';
            $optionSwatch['order'][$optionKey] = (string)$i++;
            $optionSwatch['value'][$optionKey] = [$optionValue, ''];
        }
        return $optionSwatch;
    }

    protected function generateSwatchVariations($image)
    {
        $absoluteImagePath = $this->mediaDirectory->getAbsolutePath($this->getAttributeSwatchPath($image));
        if (file_exists($absoluteImagePath)) {
            $this->_swatchHelper->generateSwatchVariations($image);
        }

    }

    public function getAttributeSwatchPath($image)
    {
        return self::ATTRIBUTE_IMAGE_FOLDER . '/' . $image;
    }

    /**
     * @param $attributeId
     * @return void
     */
    private function loadOptionCollection($attributeId)
    {
        if (empty($this->optionCollection[$attributeId])) {
            $this->optionCollection[$attributeId] = $this->attrOptionCollectionFactory->create()
                ->setAttributeFilter($attributeId)
                ->setPositionOrder('asc', true)
                ->load();
        }
    }

    private function addExistingOptions($attribute)
    {
        $options = [];
        $attributeId = $attribute->getId();
        if ($attributeId) {
            $this->loadOptionCollection($attributeId);
            /** @var \Magento\Eav\Model\Entity\Attribute\Option $option */
            foreach ($this->optionCollection[$attributeId] as $option) {
                $options[$option->getId()] = $option->getValue();
            }
        }
        return $options;
    }

    private function getOptionSwatchText(array $attributeData, $storeId)
    {
        $optionSwatch = ['value' => []];
        foreach ($attributeData['option'] as $optionKey => $optionValue) {
            $optionSwatch['value'][$optionKey] = array($storeId=>$optionValue);
        }
        return $optionSwatch;
    }
}
