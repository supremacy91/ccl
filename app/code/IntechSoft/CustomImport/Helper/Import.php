<?php

namespace IntechSoft\CustomImport\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Eav\Model\Entity\Attribute\SetFactory;
use Magento\Framework\Registry;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as TypeConfigurable;
use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\Framework\App\ResourceConnection as AppResourceConnection;

/**
 * Class Import
 *
 * @package IntechSoft\CustomImport\Helper
 */
class Import extends AbstractHelper
{
    const IMAGE_URL_TO_CUT = 'https://calexis.nl/userfiles/files';
    const SPECIAL_LETTER = 'c';
    const CONFIG_META_TITLE_PATH = 'customImportSection/seoGroup/meta_title_prefix';
    const CONFIG_META_DESCRIPTION_PATH = 'customImportSection/seoGroup/meta_description_prefix';

    protected $_registry;
    protected $objectManager;
    protected $headers;
    protected $itemImage;
    protected $_attributeSetFactory;
    protected $directory_list;

    /**
     * @var AppResourceConnection
     */
    private $resource;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var array
     */
    protected $imagesColumnArray = array(
        'base_image',
        'small_image',
        'thumbnail_image');

    public $rootCategory = 'Default Category';
    public $allowedImagesExtensions = array (
        'jpg',
        'png',
        'gif'
    );
    protected $standardAttributes = array(
        'attribute_set_code' => 'Default',
        'product_type' => ProductType::TYPE_SIMPLE,
        'product_websites' => 'base',
        'is_in_stock' => 1,
        'manage_stock' => 0,
        'website_id' => 0,
        'product_online' => 1,
        'meta_title' => '',
        'meta_description' => '',
        'base_image' => '',
        'small_image' => '',
        'thumbnail_image' => '',
        'configurable_variations' => '',
        'default_category' => '',
        'visibility' => 'Not Visible Individually'
    );
    public $attributesMapping = array(
        'SKU' => 'sku',
        'Short Description' => 'short_description',
        'Description' => 'description',
        'Category' => 'categories',
        'GROUP' => 'group',
        'Collection' => 'custom_collection',
        'Size' => 'size',
        'Color' => 'color',
        'Brand' => 'brand',
        'Discount Price' => 'special_price',
        'Selling Price' => 'price',
        'Discount' => 'discount',
        'Stock' => 'qty',
        'Material' => 'material_1',
        'Sole' => 'sole',
        'Innersole' => 'innersole',
        'Interior' => 'interior',
        'Demountable' => 'demountable',
        'Closing' => 'closing',
        'Heel' => 'heel',
        'Plateaulevel' => 'plateaulevel',
        'Sizefits' => 'sizefits',
        'EAN' => 'ean',
        'Origin' => 'origin',
        'Material2' => 'material2',
        'Freetext' => 'freetext',
        'Photos' => 'additional_images',
        'Configurable Variations' => 'configurable_variations',
        'Url Key' => 'url_key',
        'Color Hex' => 'color_hex'
    );

    /**
     * Import constructor.
     *
     * @param ObjectManagerInterface $objectManager
     * @param SetFactory             $attributeSetFactory
     * @param Registry               $registry
     * @param DirectoryList          $directory_list
     * @param AppResourceConnection  $resource
     * @param Context                $context
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        SetFactory $attributeSetFactory,
        Registry $registry,
        DirectoryList $directory_list,
        AppResourceConnection $resource,
        Context $context
    )
    {
        $this->directory_list = $directory_list;
        $this->_attributeSetFactory = $attributeSetFactory;
        $this->objectManager = $objectManager;
        $this->resource = $resource;
        $this->_registry = $registry;
        parent::__construct($context);

        $this->scopeConfig = $context->getScopeConfig();
    }


    public function prepareData($data)
    {
        $this->headers = $this->getCsvHeaders($data[0]);
        $data[0] = $this->headers;
        $data = $this->getConvertedData($data);

        return $data;
    }
 
    public function prepareDataConfigurable($data)
    {
        $this->headers = $this->getCsvHeaders($data[0]);
        $data[0] = $this->headers;
        $data = $this->getConvertedDataConfigurable($data);

        return $data;
    }

    /**
     *
     *Method merges 2 arrays:
     * 1) the mapped names of attributes from csv
     * 2) names of standart attributes of Magento
     * @param $headers
     * @return array
     */
    public function getCsvHeaders($headers)
    {
        $preparedHeaders = array();
        foreach ($headers as $item) {
            $preparedHeaders[] = $this->prepareHeaderItem($item);
        }

        $standardHeaders = array_keys($this->getStandardAttributes());
        for($i = 0, $j = count($standardHeaders); $i < $j; $i++) {
            if(!in_array($standardHeaders[$i], $preparedHeaders)) {
                array_push($preparedHeaders, $standardHeaders[$i]);
            }
        }
        return $preparedHeaders;
    }

    public function getConvertedData($data)
    {
        $this->standardAttributes['product_type'] = ProductType::TYPE_SIMPLE;
        $convertedData = array();

        try {
            $counter = 0;
            foreach ($data as $index => $item) {

                if (!$index) {
                    $convertedData[] = $this->headers;
                    continue;
                }

                $preparedItems = array();
                $this->itemImage = '';
                for ($i = 0, $j = count($this->headers); $i < $j; $i++) {
                    $defaultValue = $this->checkDefaultValue($this->headers[$i]);
                    if (!isset($item[$i]) && $defaultValue !== false) {
                        $preparedItems[] = $this->prepareItem($defaultValue, $this->headers[$i]);
                    } else {
                        if ($this->headers[$i] == 'sku') {
                            $preparedItems[] = self::SPECIAL_LETTER . $this->prepareItem($item[$i], $this->headers[$i]);
                        } else {
                            $preparedItems[] = $this->prepareItem($item[$i], $this->headers[$i]);
                        }
                    }
                }
                $convertedData[] = $preparedItems;
                $counter++;
            }

            $convertedData = $this->checkNameAttribute($convertedData);
            $convertedData = $this->checkUrlKeyAttribute($convertedData);
            $convertedData = $this->addDefaultCategory($convertedData);
            $convertedData = $this->addExtraBrand2($convertedData);
        } catch (LocalizedException $e) {
            $this->_logger->error(__METHOD__);
            $this->_logger->error($e->getMessage());
        }

        return $convertedData;
    }

    public function getStandardAttributes()
    {
        return $this->standardAttributes;
    }

    public function getStandardAttribute($field)
    {
        if (isset($this->standardAttributes[$field])) {
            return $this->standardAttributes[$field];
        } else {
            return false;
        }
    }

    public function setStandardAttribute($field, $value)
    {
        $this->standardAttributes[$field] = $value;
    }

    public function getAttributesMapping()
    {
        return $this->attributesMapping;
    }

    public function getMappedAttribute($field)
    {
        if (isset($this->attributesMapping[$field])) {
            return $this->attributesMapping[$field];
        } else {
            return false;
        }
    }

    public function setAttributeSet($attributeSetId)
    {
        $attributeSet = $this->_attributeSetFactory->create()->load($attributeSetId);
        $this->setStandardAttribute('attribute_set_code',$attributeSet->getAttributeSetName());
    }

    public function setAttributesMapping($field, $value)
    {
        $this->attributesMapping[$field] = $value;
    }

    protected function encodToEncodUtf8($data)
    {
        $enclist = array(
            'UTF-8', 'ASCII', 'Windows-1251', 'Windows-1252', 'Windows-1254', 'ISO-8859-1',
            'ISO-8859-2',
            'ISO-8859-3',
            'ISO-8859-4',
            'ISO-8859-5',
            'ISO-8859-6',
            'ISO-8859-7',
            'ISO-8859-8',
            'ISO-8859-9',
            'ISO-8859-10',
            'ISO-8859-11',
            'ISO-8859-12',
            'ISO-8859-13',
            'ISO-8859-14',
            'ISO-8859-15',
            'ISO-8859-16'
        );

        $enclist = implode(',',$enclist);
        if (mb_detect_encoding($data,  $enclist, true)) {
            $encodedData = mb_convert_encoding($data, 'UTF-8', $enclist);
        } else {
            $encodedData = mb_convert_encoding($data, 'UTF-8', 'Windows-1251');
        }
	
        return $encodedData;
    }

    public function checkNameAttribute($convertedData)
    {
        $typeKey = array_search('product_type',$this->headers);
        $data = array();
        $colorData = array();
        if($nameColumnCount = $this->getColumnIndexByName('name')) {
            for($i = 1; $i < count($convertedData); $i++)
            {
                $convertedData[$i][$nameColumnCount] = $convertedData[$i][$this->getColumnIndexByName('brand')];
                if(!array_key_exists($convertedData[$i][$this->getColumnIndexByName('color')], $colorData)){
                    $colorData[$convertedData[$i][$this->getColumnIndexByName('color')]] = $convertedData[$i][$this->getColumnIndexByName('color_hex')];
                }
            }
            $data = $convertedData;

        } else {
            for($i = 0; $i < count($convertedData); $i++)
            {
                if ($i == 0) {
                    $data[$i] = $convertedData[$i];
                    array_push($data[$i] , 'name');
                } else {
                    $data[$i] = $convertedData[$i];
                    if($convertedData[$i][$typeKey] == 'simple'){
                        array_push($data[$i], $convertedData[$i][$this->getColumnIndexByName('brand')]);
                    }else{
                        array_push($data[$i], $convertedData[$i][$this->getColumnIndexByName('brand')]);
                    }
                    if(!array_key_exists($convertedData[$i][$this->getColumnIndexByName('color')], $colorData)){
                        $colorData[$convertedData[$i][$this->getColumnIndexByName('color')]] = $convertedData[$i][$this->getColumnIndexByName('color_hex')];
                    }
                }

            }
        }
        if(!$this->_registry->registry('color_data')){
            $this->_registry->unregister('color_data');
            $this->_registry->register('color_data', $colorData);
        }
        return $data;
    }

    public function checkUrlKeyAttribute($convertedData)
    {
        $model = $this->objectManager->create('Magento\Catalog\Model\Product');

        $typeKey = array_search('product_type', $this->headers);
        $data = array();
        $urlKey = false;
        for($i = 0; $i < count($convertedData); $i++) {
            if ($i == 0) {
                $data[$i] = $convertedData[$i];
                if(!in_array('url_key', $data[$i])){
                    $urlKey = true;
                    array_push($data[$i] , 'url_key');
                }
            } else {
                $data[$i] = $convertedData[$i];
                if ($urlKey) {
                    if ($convertedData[$i][$typeKey] === 'simple') {
                        $connection = $this->resource->getConnection();
                        $urlKey = array();
                        $urlKey[$model->formatUrlKey($convertedData[$i][$this->getColumnIndexByName('brand')]. '-' . $convertedData[$i][$this->getColumnIndexByName('sku')])] = $convertedData[$i][$this->getColumnIndexByName('sku')];
                        $urlKeyDuplicates = $this->getUrlKeyDuplicates($connection, $urlKey);
                        $urlKey = $model->formatUrlKey($convertedData[$i][$this->getColumnIndexByName('brand')]. '-' . $convertedData[$i][$this->getColumnIndexByName('sku')]);

                        if($urlKeyDuplicates){
                            array_push($data[$i], '');
                        } else {
                            array_push($data[$i], $urlKey);
                        }

                    } else {
                        $connection = $this->resource->getConnection();

                        $convDataBrand  = $convertedData[$i][$this->getColumnIndexByName('brand')];
                        $convDataSku    = $convertedData[$i][$this->getColumnIndexByName('sku')];
                        $convDataDesc  = $convertedData[$i][$this->getColumnIndexByName('short_description')];

                        $convDataDesc = str_replace(array('/', ' '), '-', strtolower($convDataDesc));
//                        $brandPlusDescPlusSku = $convDataBrand . '-' . $convDataGroup . '-'  . $convDataSku;

                        $urlKey = [];
                        $_key = $model->formatUrlKey($convDataBrand) . '-' . $convDataDesc . '-'  . $convDataSku;
                        $urlKey[$_key] = $convDataSku;

                        $urlKeyDuplicates = $this->getUrlKeyDuplicates($connection, $urlKey);

                        $urlKey = $model->formatUrlKey($_key);

                        if(count($urlKeyDuplicates)){
                            array_push($data[$i], $urlKey);
                        }else{
                            array_push($data[$i], $urlKey);
                        }
                    }
                } elseif ($convertedData[$i][$typeKey] !== 'simple') {
                    /*
                    $connection= $this->resource->getConnection();
                    $urlKey = array();

                    $convDataBrand  = $convertedData[$i][$this->getColumnIndexByName('brand')];
                    $convDataSku    = $convertedData[$i][$this->getColumnIndexByName('sku')];
                    $convDataGroup  = $convertedData[$i][$this->getColumnIndexByName('group')];

                    $brandPlusGroup = $convDataBrand . '-' . $convDataGroup;
                    $brandPlusGroupPlusSku = $brandPlusGroup . '-'  . $convDataSku;

                    $urlKey[$model->formatUrlKey($brandPlusGroup)] = $convDataSku;
                    $urlKey[$model->formatUrlKey($brandPlusGroupPlusSku)] = $convDataSku;
                    $urlKeyDuplicates = $this->getUrlKeyDuplicates($connection, $urlKey);
                    $urlKey = $model->formatUrlKey($brandPlusGroupPlusSku);

                    if($urlKeyDuplicates){
                        array_push($data[$i], $urlKey);
                    }else{
                        array_push($data[$i], $urlKey);
                    }*/
                    $connection = $this->resource->getConnection();

                    $convDataBrand  = $convertedData[$i][$this->getColumnIndexByName('brand')];
                    $convDataSku    = $convertedData[$i][$this->getColumnIndexByName('sku')];
                    $convDataDesc  = $convertedData[$i][$this->getColumnIndexByName('short_description')];
                    $convDataDesc = $this->getFormattedShortDescription($convDataDesc);

                    $urlKey = [];
                    $_key = $model->formatUrlKey($convDataBrand) . '-' . $convDataDesc . '-'  . $convDataSku;
                    $urlKey[$_key] = $convDataSku;

                    $urlKeyDuplicates = $this->getUrlKeyDuplicates($connection, $urlKey);

                    if(count($urlKeyDuplicates)){
                        array_push($data[$i], '');
                    }else{
                        $urlKey = $model->formatUrlKey($_key);
                        array_push($data[$i], $urlKey);
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Get formatted product short description.
     * @param $shortDescription
     * @return mixed
     */
    public function getFormattedShortDescription($shortDescription)
    {
        $search = ['/', ' '];
        $replace = '-';
        $subject = strtolower($shortDescription);

        return str_replace($search, $replace, $subject);

    }

    /**
     * @param $connection
     * @param $urlKey
     * @return mixed
     */
    protected function getUrlKeyDuplicates($connection, $urlKey)
    {
        // TODO: Need to check is it works properly?
        return $connection->fetchAssoc($connection->select()->from(
            ['catalog_product_entity_varchar' => $connection->getTableName('catalog_product_entity_varchar')],
            ['value', 'store_id']
        )->joinLeft(
            ['cpe' => $connection->getTableName('catalog_product_entity')],
            "cpe.entity_id = catalog_product_entity_varchar.entity_id"
        )->where('value IN (?)', array_keys($urlKey))
            ->Where('cpe.sku IN (?)', array_values($urlKey)));
    }

    /**
     * Copy column brand to column brand2
     *
     * @param $convertedData
     * @return array
     */
    public function addExtraBrand2($convertedData)
    {
		$brandColumnIndex = $this->getColumnIndexByName('brand');
		$brandColumnIndex_2 = $this->getColumnIndexByName('brand_2');
		if($brandColumnIndex_2 <= 0) {
			$convertedData[0][] = 'brand_2';
			for($i = 1; $i < count($convertedData); $i++)
			{
				$convertedData[$i][] = $convertedData[$i][$brandColumnIndex];
			}
		}
		return $convertedData;
	}

    /**
     * @param $convertedData
     * @return array
     */
    public function addDefaultCategory($convertedData)
    {
        $defaultCategoryColumnIndex = $this->getColumnIndexByName('default_category');
        $categoriesColumnIndex = $this->getColumnIndexByName('categories');

        for($i = 1; $i < count($convertedData); $i++)
        {
            $defaultCategory = $this->getFirstProductCategory($convertedData[$i][$categoriesColumnIndex]);
            $convertedData[$i][$defaultCategoryColumnIndex] = $defaultCategory;
        }
        return $convertedData;
    }

    public function getFirstProductCategory($categories)
    {
        if (strpos($categories, ',') === false ){
            $defaultCategoryPath = $categories;
        } else {
            $categories = explode(',', $categories);
            $defaultCategoryPath =  $categories[0];
        }
        $categoryArray = explode('/', $defaultCategoryPath);
        $defaultCategory = end($categoryArray);
        $defaultCategory = trim($defaultCategory);

        return $defaultCategory;
    }

    /**
     * @param $name
     * @return bool|array|string
     */
    public function getColumnIndexByName($name)
    {
        if ($index = array_keys($this->headers, $name)) {
            return $index[0];
        }

        return false;
    }

    /**
     * @param $headerField
     * @return bool|mixed
     */
    public function checkDefaultValue($headerField)
    {
        if (array_key_exists($headerField, $this->getStandardAttributes())) {
            return $this->getStandardAttribute($headerField);
        }

        return false;
    }

    public function prepareItem($item, $column = false)
    {
        $convertedItem = trim($item);
        $convertedItem = $this->encodToEncodUtf8($convertedItem);
        $convertedItem = str_replace(',', '.', $convertedItem);
        $convertedItem = str_replace('|', ',', $convertedItem);
        // replace Microsoft Word version of single  and double quotations marks (“ ” ‘ ’) with  regular quotes (' and ")
        // previously it was causing "ERROR: Curly quotes used instead of straight quotes" error
        //$convertedItem = iconv('UTF-8', 'ASCII//TRANSLIT', $convertedItem);
        $convertedItem = str_replace(chr(145), "'", $convertedItem);
        $convertedItem = str_replace(chr(146), "'", $convertedItem);
        $convertedItem = str_replace(chr(147), '"', $convertedItem);
        $convertedItem = str_replace(chr(148), '"', $convertedItem);
	  
        if ($column == 'categories') {
            $convertedItem = str_replace(' > ', '/', $convertedItem);
            $convertedItem = $this->rootCategory . '/' . $convertedItem;
        } elseif ($column == 'additional_images') {
            $convertedItem = $this->prepareImagePaths($convertedItem);
        } elseif (in_array($column, $this->imagesColumnArray)) {
            $convertedItem = $this->prepareImagePaths($convertedItem, $column);
        } else {
            $convertedItem = str_replace('ROS?', 'ROS', $convertedItem);
        }

        return $convertedItem;
    }

    public function prepareHeaderItem($item)
    {
        $convertedItem = trim($item);
        $convertedItem = $this->encodToEncodUtf8($convertedItem);
        if (array_key_exists($convertedItem, $this->getAttributesMapping())) {
            $convertedItem = $this->getMappedAttribute($convertedItem);
        } else {
            $convertedItem = str_replace(' ','_', $convertedItem);
            $convertedItem = strtolower($convertedItem);
        }

        return $convertedItem;
    }

    public function prepareImagePaths($path, $column = false)
    {
        if (in_array($column , $this->imagesColumnArray)){
            if(file_exists($this->directory_list->getPath('media'). '/import' . $this->itemImage)){
                $path = $this->itemImage;
            } else {
                $path = '';
            }

        } elseif (gettype($path) == 'string' && $path != '') {
            $path = str_replace(self::IMAGE_URL_TO_CUT, '', $path);

            if (!preg_match("/^[a-zA-Z\d\/\.\_\-\|]*/", $path)) {
                $path = '';
            }

            $path = str_replace('|', ',',$path);
            $images = explode(',', $path);

            $newPath = '';
            foreach($images as $index => $image) {
                if(file_exists($this->directory_list->getPath('media'). '/import' .$image)){
                    if (strlen($image) < 99 && $index == 0) {
                        $newPath .=  $image;
                    } elseif(strlen($image) < 99) {
                        if (empty($newPath)) {
                            $newPath .= $image;
                        } else {
                            $newPath .= ',' . $image;
                        }
                    }
                }
            }
            $path = $newPath;
            $this->itemImage = $images[0];
        }

        return $path;
    }

    public function getConvertedDataConfigurable($data)
    {
        $convertedData = array();
        $counter = 0;
        $group = '';
        $groupKey     = array_search('group', $this->headers);
        $colorKey     = array_search('color', $this->headers);
        $sizeKey      = array_search('size', $this->headers);
        $variationsKey = array_search('configurable_variations', $this->headers);
        $skuKey       = array_search('sku', $this->headers);
        $configurableVariation = '';
        $this->standardAttributes['product_type'] = TypeConfigurable::TYPE_CODE;
        $this->standardAttributes['visibility'] = 'Catalog| Search';
        $countElements = count($data) - 1;

        foreach ($data as $index => $item) {
            if (!$index) {
                $convertedData[] = $this->headers;
                continue;
            }

            $preparedItems = array();
            $this->itemImage = '';
            if(!$group || $group != $item[$groupKey]) {
                for($i = 0; $i < count($this->headers); $i++) {

                    $defaultValue = $this->checkDefaultValue($this->headers[$i]);
                    if (!isset($item[$i]) && $defaultValue !== false) {
                        $preparedItems[] = $this->prepareItem($defaultValue, $this->headers[$i]);
                    } else {
                        if($this->headers[$i] == 'sku'){
                            $preparedItems[] = $this->prepareItem($item[$groupKey], $this->headers[$i]);
                        }else{
                            $preparedItems[] = $this->prepareItem($item[$i], $this->headers[$i]);
                        }
                    }
                }
                if($configurableVariation){
                    $convertedData[$counter][$variationsKey] = $configurableVariation;
                }
                $configurableVariation = '';
                $configurableVariation .= 'sku='. self::SPECIAL_LETTER . (string)$this->prepareItem($item[$skuKey]) . ',color='.$this->prepareItem($item[$colorKey]) . ',size='.$this->prepareItem($item[$sizeKey]);
                $group = $item[$groupKey];
                $convertedData[] = $preparedItems;
                $counter++;
            }else{
                $configurableVariation .= '|sku='. self::SPECIAL_LETTER . (string)$this->prepareItem($item[$skuKey]) . ',color='.$this->prepareItem($item[$colorKey]) . ',size='.$this->prepareItem($item[$sizeKey]);
            }
            if($countElements == $index && $configurableVariation){
                $convertedData[$counter][$variationsKey] = $configurableVariation;
            }
        }
        $convertedData = $this->checkNameAttribute($convertedData);
        $convertedData = $this->fillMetaTags($convertedData);
        $convertedData = $this->checkUrlKeyAttribute($convertedData);
        $convertedData = $this->addDefaultCategory($convertedData);
        $convertedData = $this->addExtraBrand2($convertedData);

        return $convertedData;
    }

    /**
     * Generate and fill the meta tags for every configurable products.
     *
     * @param $convertedData
     * @return mixed
     */
    public function fillMetaTags($convertedData)
    {
        $brandColumnIndex       = $this->getColumnIndexByName('brand');
        $shortDescColumnIndex   = $this->getColumnIndexByName('short_description');
        $metaTitleColumnIndex   = $this->getColumnIndexByName('meta_title');
        $metaDescColumnIndex    = $this->getColumnIndexByName('meta_description');
        $metaTitlePrefix        = $this->getMetaTitlePrefix();
        $metaDescPrefix         = $this->getMetaDescPrefix();

        for($i = 1; $i < count($convertedData); $i++)
        {
            $shortDescriptionValue = $convertedData[$i][$shortDescColumnIndex];
            $brandValue = ucwords($convertedData[$i][$brandColumnIndex]);

            // Template for meta title is: {BRAND} {Description} - Calexis Schoenmode
            $metaTitleValue = $brandValue . ' ' . $shortDescriptionValue /*. ' ' . $metaTitlePrefix */;
            /* Template for meta_description is: {Brand} {Description} online bestellen bij Calexis Schoenmode
             * +100 merken  Veilig (achteraf) betalen Voor 17.00 besteld = morgen in huis  Gratis verzending
             */
            $metaDescriptionValue = $brandValue . ' ' . $shortDescriptionValue . ' ' . $metaDescPrefix;

            $convertedData[$i][$metaTitleColumnIndex] = $metaTitleValue;
            $convertedData[$i][$metaDescColumnIndex] = $metaDescriptionValue;
        }

        return $convertedData;
    }

    /**
     * Get meta_title prefix form customImport config.
     *
     * @return string
     */
    public function getMetaTitlePrefix()
    {
        return $this->scopeConfig->getValue(self::CONFIG_META_TITLE_PATH);
    }

    /**
     * Get meta_description prefix from customImport config.
     *
     * @return string
     */
    public function getMetaDescPrefix()
    {
        return $this->scopeConfig->getValue(self::CONFIG_META_DESCRIPTION_PATH);
    }
}
