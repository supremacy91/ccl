<?php
namespace IntechSoft\CustomImport\Plugin;

use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Registry;
use IntechSoft\CustomImport\Model\Import as CustomImportModel;

class InsertOptionData{

    private $setup;
    private $registry;

    public function __construct(
        ModuleDataSetupInterface $setup,
        Registry $registry
    ) {
        $this->setup = $setup;
        $this->registry = $registry;
    }

    public function aroundAddAttributeOption(EavSetup $subject, $proceed, $option)
    {
        // We should determine is Custom Import is working.
        $isCustomImport = $this->registry->registry(CustomImportModel::REGISTER_KEY);
        $isCustomImportForm = $this->registry->registry(CustomImportModel::REGISTER_KEY_FROM);

        if ($isCustomImport || $isCustomImportForm) {
            $optionTable = $this->setup->getTable('eav_attribute_option');
            $optionValueTable = $this->setup->getTable('eav_attribute_option_value');

            if (isset($option['value'])) {
                foreach ($option['value'] as $optionId => $values) {
                    //$intOptionId = (int)$optionId;
                    $intOptionId = is_numeric($optionId) ? (int)$optionId : 0;
                    if (!empty($option['delete'][$optionId])) {
                        if ($intOptionId) {
                            $condition = ['option_id =?' => $intOptionId];
                            $this->setup->getConnection()->delete($optionTable, $condition);
                        }
                        continue;
                    }

                    if (!$intOptionId) {
                        $data = [
                            'attribute_id' => $option['attribute_id'],
                            'sort_order' => isset($option['order'][$optionId]) ? $option['order'][$optionId] : 0,
                        ];
                        $this->setup->getConnection()->insert($optionTable, $data);
                        $intOptionId = $this->setup->getConnection()->lastInsertId($optionTable);
                    } else {
                        $data = [
                            'sort_order' => isset($option['order'][$optionId]) ? $option['order'][$optionId] : 0,
                        ];
                        $this->setup->getConnection()->update($optionTable, $data, ['option_id=?' => $intOptionId]);
                    }

                    // Default value
                    if (!isset($values[0])) {
                        throw new \Magento\Framework\Exception\LocalizedException(
                            __('Default option value is not defined')
                        );
                    }
                    $condition = ['option_id =?' => $intOptionId];
                    $this->setup->getConnection()->delete($optionValueTable, $condition);
                    foreach ($values as $storeId => $value) {
                        $data = ['option_id' => $intOptionId, 'store_id' => $storeId, 'value' => $value];
                        $this->setup->getConnection()->insert($optionValueTable, $data);
                    }

                    // Some hardcoded attribute IDs: 173 - color, 172 - size.
                    if ($option['attribute_id'] === '173') {
                            $colorMap = $this->registry->registry('color_data');
                            foreach ($values as $storeIdKey => $currentValue) {
                                $currentHexValue = isset($colorMap[$currentValue]) ? $colorMap[$currentValue] : '';
                                if (!$currentHexValue) {
                                    continue;
                                }
                                $this->addSwatchValues($intOptionId, $storeIdKey, 1, $currentHexValue);
                            }
                    } else if ($option['attribute_id'] === '172') {
                        foreach ($values as $storeIdKey => $currentValue) {
                            $this->addSwatchValues($intOptionId, $storeIdKey, 0, $currentValue);
                        }
                    }
                }
            } elseif (isset($option['values'])) {
                foreach ($option['values'] as $sortOrder => $label) {
                    // add option
                    $data = ['attribute_id' => $option['attribute_id'], 'sort_order' => $sortOrder];
                    $this->setup->getConnection()->insert($optionTable, $data);
                    $intOptionId = $this->setup->getConnection()->lastInsertId($optionTable);

                    $data = ['option_id' => $intOptionId, 'store_id' => 0, 'value' => $label];
                    $this->setup->getConnection()->insert($optionValueTable, $data);
                }
            }
        } else {
            $proceed($option);
        }
    }

    /**
     * Added swatch value to db.
     *
     * @param     $optionId
     * @param int $storeId
     * @param int $type
     * @param     $value
     */
    private function addSwatchValues($optionId, $storeId = 0, $type = 0, $value)
    {
        $optionSwatchTable = $this->setup->getTable('eav_attribute_option_swatch');

        $dataHex = ['option_id' => $optionId, 'store_id' => $storeId, 'type' => $type, 'value' => $value];
        $this->setup->getConnection()->insertOnDuplicate($optionSwatchTable, $dataHex, array('value'));
    }
}
