<?php

namespace Biztech\Manufacturer\Controller\Adminhtml\Manufacturer;

use Magento\Framework\App\Filesystem\DirectoryList;

class Save extends \Magento\Backend\App\Action {

	protected $_adapterFactory;
	protected $_uploader;
	protected $_filesystem;
	protected $_storeConfig;
	protected $_manufacturerModel;
	protected $_manufacturertextModel;
	protected $_productModel;
	protected $_helperData;
	protected $_urlRewrite;
	protected $_urlRewriteFactory;
	protected $_urlPersist;

	public function __construct(
		\Magento\Backend\App\Action\Context $context,
		\Magento\Framework\Image\AdapterFactory $adapterFactory,
		\Magento\Framework\File\UploaderFactory $uploader,
		\Magento\Framework\Filesystem $filesystem,
		\Biztech\Manufacturer\Model\Config $config,
		\Biztech\Manufacturer\Model\Manufacturer $manufacturer,
		\Biztech\Manufacturer\Model\Manufacturertext $manufacturertext,
		\Magento\Catalog\Model\Product $productModel,
		\Biztech\Manufacturer\Helper\Data $helperData,
		\Magento\UrlRewrite\Model\UrlRewrite $urlRewrite,
		\Magento\UrlRewrite\Service\V1\Data\UrlRewriteFactory $urlRewriteFactory,
		\Magento\UrlRewrite\Model\UrlPersistInterface $urlPersist
	) {
		$this->_adapterFactory = $adapterFactory;
		$this->_uploader = $uploader;
		$this->_filesystem = $filesystem;
		$this->_storeConfig = $config;
		$this->_manufacturerModel = $manufacturer;
		$this->_manufacturertextModel = $manufacturertext;
		$this->_productModel = $productModel;
		$this->_helperData = $helperData;
		$this->_urlRewrite = $urlRewrite;
		$this->_urlRewriteFactory = $urlRewriteFactory;
		$this->_urlPersist = $urlPersist;
		parent::__construct($context);
	}

	/**
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
	public function execute() {
		$data = $this->getRequest()->getParams();
		$storeId = $this->getRequest()->getParam('store_id', 0);
		$collection = $this->_manufacturerModel->getCollection()
			->addFieldToFilter('manufacturer_name', $data['manufacturer_name'])->getData();
		if (count($collection) > 0 && $this->getRequest()->getParam('manufacturer_id') != $collection[0]['manufacturer_id']) {
			$this->messageManager->addError(__('Manufacturer Already Exists'));
			$this->_redirect('*/*/', ['store' => $data['store_id']]);
			return;
		} else {
			if ($data) {
				/*$manufacturer_name = '';
					                $attribute = $this->_storeConfig->getCurrentStoreConfigValue('manufacturer/general/brandlist_attribute');
					                $attr = $this->_productModel->getResource()->getAttribute($attribute);
					                if ($attr->usesSource()) {
					                    $manufacturer_name = $attr->getSource()->getOptionText($data['manufacturer_name']);
				*/
				$manufacturer_name = $data['manufacturer_name'];
				$data['brand_name'] = $manufacturer_name;

				if (isset($_FILES['filename']['name']) && $_FILES['filename']['name'] != '') {
					try {
						$data = $this->_uploadFile($data, $manufacturer_name);
					} catch (\Magento\Framework\Exception\LocalizedException $e) {
						$this->messageManager->addError(__($e->getMessage()));
						$this->_getSession()->setFormData($data);
						$this->_redirect('*/*/edit', ['manufacturer_id' => $this->getRequest()->getParam('manufacturer_id')]);
						return;
					}
				} else {
					$_FILES['filename'] = $_FILES['filename']['name'];
				}

				if (isset($data['url_key'])) {
					$data['url_key'] = $this->_helperData->clearUrlKey($data['url_key']);
				}

				try {

					if ($manufacturer_name == '' || $manufacturer_name == null) {
						/*$manufacturer_name = $attr->getSource()->getOptionText($this->_manufacturerModel->load($this->getRequest()->getParam('manufacturer_id'))->getManufacturerName());*/
						$manufacturer_name = $data['manufacturer_name'];
					}
					$model = $this->_manufacturerModel->setData($data)
						->setBrandName($manufacturer_name)
						->setId($this->getRequest()->getParam('manufacturer_id'));
					if ($model->getManufacturerId() || (!$model->getManufacturerId() && isset($data['url_key']))) {
						if ($this->updateUrlKey($model) == null) {
							return;
						}
					}
					$model->save();
					$collection_text = $this->_manufacturertextModel
						->getCollection()
						->addFieldToFilter('manufacturer_id', $model->getManufacturerId());
					if (!$this->getRequest()->getParam('manufacturer_id')) {
						$this->_manufacturertextModel->setData($data)
							->setManufacturerId($model->getManufacturerId())->setStoreId($storeId);
						$this->_manufacturertextModel->save();
						foreach ($this->_storeConfig->getStoreManager()->getStores() as $store) {
							$this->_manufacturertextModel->setData($data)
								->setManufacturerId($model->getManufacturerId())
								->setStoreId($store->getId());

							$this->_manufacturertextModel->save();
						}
					} else {
						
						if ($data['store_id'] == 0) {
							$text_data = $collection_text->addFieldToFilter('store_id', $storeId)->getData();
							// $this->saveManufacturerData($data, $data['store_id'], $text_data[0]['text_id'], $model->getManufacturerId());
							foreach ($this->_storeConfig->getStoreManager()->getStores() as $store) {
								$collection_text = '';
								$collection_text = $this->_manufacturertextModel
									->getCollection()
									->addFieldToFilter('manufacturer_id', $model->getManufacturerId());
								$text_data = $collection_text->addFieldToFilter('store_id', $store->getId())->getData();
								if (isset($text_data[0])) {
									$this->saveManufacturerData($data, $store->getId(), $text_data[0]['text_id'], $model->getManufacturerId());
								} else {
									$this->_manufacturertextModel->setData($data)
										->setManufacturerId($model->getManufacturerId())
										->setStoreId($store->getId());

									$this->_manufacturertextModel->save();
								}
							}
						} else {
							$text_data = $collection_text->addFieldToFilter('store_id', $storeId)->getData();
							$this->_manufacturertextModel->setData($data)
								->setManufacturerId($model->getManufacturerId())
								->setTextId($text_data[0]['text_id'])
								->setStoreId($storeId);
							$this->_manufacturertextModel->save();
						}
					}
					$this->_manufacturerModel->save();
					$this->_manufacturertextModel->save();
					$this->messageManager->addSuccess('Manufacturer Saved Successfully!');
					$this->_getSession()->setFormData(false);
				} catch (Exception $e) {
					$this->messageManager->addError($e->getMessage());
					$this->_getSession()->setFormData($data);
				}
			}
		}

		if ($this->getRequest()->getParam('back') == 'edit') {
			$manufacturerId = $this->getRequest()->getParam('manufacturer_id') ? $this->getRequest()->getParam('manufacturer_id') : $model->getManufacturerId();
			$this->_redirect('*/*/edit', ['manufacturer_id' => $manufacturerId, 'store' => $storeId]);
		} else {
			$this->_redirect('*/*/');
		}
	}

	protected function saveManufacturerData($data, $storeId, $textId, $manuId) {
		$modelText = $this->_manufacturertextModel->load($textId);
		return $modelText
			->setData($data)
			->setManufacturerId($manuId)
			->setStoreId($storeId)
			->setTextId($textId)
			->save();
	}

	protected function updateUrlKey($model) {

		$id = $model->getId();

		$url_key = 'merken/' . $model->getUrlKey();
		$storeId = $this->getRequest()->getParam('store_id', 0);

		if (is_null($url_key) || !is_string($url_key)) {
			throw new \Magento\Framework\Exception\LocalizedException(__("Unkown Error"));
		}

		if ($storeId !== null) {
			$urlRewriteCollection = $this->_urlRewrite->getCollection()
				->addFieldToFilter('request_path', $url_key);

			if (count($urlRewriteCollection) > 0) {
				foreach ($urlRewriteCollection as $urlRewrite) {
					if ($urlRewrite->getRequestPath() == $url_key && $urlRewrite->getEntityId() == $id) {
						$urlRewrite->delete();
					} else {
						$this->messageManager->addError('URL Key already Exists ! URL key should be unique!');
						$this->_redirect($this->_redirect->getRefererUrl());
						return null;
					}
				}
			}

			try {
				$model->save();
				$id = $model->getId();
				if ($storeId == 0) {
					foreach ($this->_storeConfig->getStoreManager()->getStores() as $store) {
						$this->_objectManager->create('Magento\UrlRewrite\Helper\UrlRewrite')->validateRequestPath($url_key);
						$urls[] = $this->createUrlRewrite($store->getId(), $url_key, $id);
					}
					$this->_urlPersist->replace($urls);
				} else {

					$this->_objectManager->get('Magento\UrlRewrite\Helper\UrlRewrite')->validateRequestPath($url_key);
					$url[] = $this->createUrlRewrite($storeId, $url_key, $id);

					$this->_urlPersist->replace($url);
				}
			} catch (Exception $e) {
				$this->messageManager->addError($e->getMessage());
			}
			return true;
		}
	}
	protected function createUrlRewrite($storeId, $url_key, $id, $redirectType = 0) {
		return $this->_urlRewriteFactory->create()->setStoreId($storeId)
			->setEntityType('manufacturer')
			->setEntityId($id)
			->setRequestPath($url_key)
			->setTargetPath('merken/index/view/id/' . $id)
			->setIsAutogenerated(1)
			->setRedirectType($redirectType);
	}
	protected function _uploadFile($data, $manufacturer_name) {
		$upload_dimension = explode('x', $this->_storeConfig->getCurrentStoreConfigValue('manufacturer/general/image_upload_width_height'));
		if ($upload_dimension[0] == '' || $upload_dimension[1] == '') {
			$upload_dimension = [300, 300];
		}

		$replace = ["'", " ", "!", "%", "@", "$", '#'];
		$new_manufacturer_name = str_replace($replace, "_", $manufacturer_name);

		list($width, $height) = getimagesize($_FILES['filename']['tmp_name']);
		if ($width >= $upload_dimension[0] && $height >= $upload_dimension[1]) {
			$uploader = $this->_uploader->create(['fileId' => $_FILES['filename']]);
			$uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png']);
			$imageAdapter = $this->_adapterFactory->create();
			$uploader->addValidateCallback('image', $imageAdapter, 'validateUploadFile');
			$uploader->setAllowRenameFiles(false);
			$uploader->setFilesDispersion(true);
			$fileName = $uploader->getCorrectFileName($_FILES['filename']['name']);
			$dispersionPath = $uploader->getDispretionPath($fileName);

			$base_media_path = 'Manufacturer';
			// $base_media_path = $this->_helperData->getManufacturerImageUploadPath();

			$mediaDirectory = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA);
			$result = $uploader->save($mediaDirectory->getAbsolutePath($base_media_path));

			$replace = ["'", " ", "!", "%", "@", "$", '#'];
			unset($result['tmp_name']);
			unset($result['path']);

			$data['filename'] = $base_media_path . $result['file'];

			$this->_resizeImage($dispersionPath, $uploader);
		} else {
			throw new \Magento\Framework\Exception\LocalizedException(__("Image dimenssions must be greater than equal to $upload_dimension[0]px width and $upload_dimension[1]px height"));
		}

		$fileName = $_FILES['filename']['name'];
		$fileName = explode('.', $fileName);

		if ($uploader->checkAllowedExtension($fileName[1])) {
			$data['filename'] = $uploader->getUploadedFileName();
		} else {
			throw new \Magento\Framework\Exception\LocalizedException(__("Upload Image Files Only"));
		}

		return $data;
	}

	private function _resizeImage($manufacturer_name, $uploader) {
		$layered_navigation_dimension = explode('x', $this->_storeConfig->getCurrentStoreConfigValue('manufacturer/left_configuration/layered_navigation_dimension'));
		$manufacturer_list_dimension = explode('x', $this->_storeConfig->getCurrentStoreConfigValue('manufacturer/manufacturer_brand_list/manufacturer_list_dimension'));
		$product_view_dimension = explode('x', $this->_storeConfig->getCurrentStoreConfigValue('manufacturer/manufacturer_product_view/product_view_dimension'));
		$fileName = $uploader->getUploadedFileName();

		$path = $this->_helperData->getManufacturerImageUploadPath();

		$replace = ["'", " ", "!", "%", "@", "$", '#'];
		$replace_name = ["'", " ", "!", "%", "@", "$", '#'];
		$new_manufacturer_name = str_replace($replace_name, "_", $manufacturer_name);
		$imageUrl = $this->_helperData->getImageUrl($fileName);

		$imageResized = $this->_helperData->getManufacturerImageUploadPath($fileName, 'small_thumb');
		$dirImg = $this->_objectManager->get('\Magento\Framework\Filesystem\DirectoryList')->getRoot() . DIRECTORY_SEPARATOR . 'pub' . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . 'Manufacturer' . $fileName;

		$this->_helperData->setManufacturerImageResize($dirImg, $layered_navigation_dimension[0], $layered_navigation_dimension[1], $imageResized);
		$imageResizedListPage = $this->_helperData->getManufacturerImageUploadPath($fileName, 'large_thumb');
		$this->_helperData->setManufacturerImageResize($dirImg, $manufacturer_list_dimension[0], $manufacturer_list_dimension[1], $imageResizedListPage);
		$imageResizedProductPage = $this->_helperData->getManufacturerImageUploadPath($fileName, 'product_thumb');
		$this->_helperData->setManufacturerImageResize($dirImg, $manufacturer_list_dimension[0], $manufacturer_list_dimension[1], $imageResizedProductPage);
	}

	/**
	 * Check for is allowed
	 *
	 * @return boolean
	 */
	protected function _isAllowed() {
		return $this->_authorization->isAllowed('Biztech_Manufacturer::biztech_manufacturer_index');
	}

}
