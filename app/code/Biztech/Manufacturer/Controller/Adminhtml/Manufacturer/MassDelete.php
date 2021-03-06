<?php

namespace Biztech\Manufacturer\Controller\Adminhtml\Manufacturer;

class MassDelete extends \Magento\Backend\App\Action
{
    public function execute()
    {

        $ids = $this->getRequest()->getParam('manufacturer');
        if (!is_array($ids) || empty($ids)) {
            $this->messageManager->addError(__('Please select manufacturer(s).'));
        } else {
            try {
                foreach ($ids as $id) {
                    $_manufacturer = $this->_objectManager->get('Biztech\Manufacturer\Model\Manufacturer')->load($id);
                    $_manufacturerText = $this->_objectManager->get('Biztech\Manufacturer\Model\Manufacturertext')->load($id);
                    $urlKey = $_manufacturerText->getUrlKey();
                    $urlRewriteCollection = $this->_objectManager->get('\Magento\UrlRewrite\Model\UrlRewrite')->getCollection()
                        ->addFieldToFilter('request_path', $urlKey);

                    if (count($urlRewriteCollection) > 0) {
                        foreach ($urlRewriteCollection as $urlRewrite) {
                            if ($urlRewrite->getRequestPath() == $urlKey) {
                                $urlRewrite->delete();
                            }
                        }
                    }

                    $_manufacturer->delete();
                    $_manufacturerText->delete();
                }
                $this->messageManager->addSuccess(
                    __('A total of %1 record(s) have been deleted.', count($ids))
                );
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/');
    }

    /**
     * Check for is allowed
     *
     * @return boolean
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Biztech_Manufacturer::biztech_manufacturer_index');
    }

}
