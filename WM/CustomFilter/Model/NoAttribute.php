<?php
/**
 * NoAttribute Model
 *
 * @category   WM
 * @package    WM_CustomFilter

 * Class WM_CustomFilter_Block_NoAttribute
 */
class WM_CustomFilter_Model_NoAttribute extends Mage_Catalog_Model_Layer_Filter_Abstract
{
    /**
     * Construct
     */
    public function __construct()
    {
        parent::__construct();
        $this->_requestVar = 'non-attribute';
    }

    /**
     * Name of the filter (label in filter block)
     *
     * @return string
     */
    public function getName()
    {
        return 'Show';
    }

    /**
     * Join table to product table by attribute code
     *
     * @param Varien_Db_Select $select
     * @param string $attributeCode
     * @param null|int $storeId
     * @param null|string $aliasCode Attribute alias name
     * @param null|string $aliasTable Table alias name
     * @param null|string $entity Product entity name or other table name
     */
    protected function _joinByAttribute(Varien_Db_Select $select, $attributeCode, $storeId = null, $aliasCode = null, $aliasTable = null, $entity = null)
    {
        if ($entity === null) {
            /** @var Mage_Catalog_Model_Product $model */
            $model = Mage::getModel('catalog/product');
            $mainTable = $model::ENTITY;
        } else {
            $mainTable = (string)$entity;
        }
        if ($aliasTable === null) {
            $aliasTable = $attributeCode . '_table';
        }
        if ($aliasCode === null) {
            $aliasCode = $attributeCode;
        }
        if ($storeId === null) {
            /** @var Mage_Core_Model_Store $store */
            $store = Mage::app()->getStore();
            $storeId = $store->getId();
        }

        /** @var Mage_Eav_Model_Entity_Attribute $attributeModel */
        /** @var Mage_Eav_Model_Entity_Attribute_Abstract $attributeModelAbs*/
        $attributeModelAbs = Mage::getModel('eav/entity_attribute');
        $attributeModel = $attributeModelAbs->loadByCode($mainTable, $attributeCode);
        $attributeModel->getAttributeId();
        $attributeTable = $mainTable . '_entity_' .$attributeModel->getBackendType();
        $select->joinLeft(
            array($aliasTable => $attributeTable),
            '(`' . $aliasTable .'`.`entity_id` = `e`.`entity_id`)
             AND (`' . $aliasTable .'`.`attribute_id` = ' . $attributeModel->getAttributeId() . ')
             AND (`' . $aliasTable .'`.`store_id` = ' . $storeId . ')',
            array($aliasCode => $aliasTable . '.value')
        );
    }

    /**
     * Apply 'New Arrivals' filter to select object
     *
     * @param Varien_Db_Select $select
     */
    protected function _applyNewArrivalsToSelect(Varien_Db_Select $select)
    {
        /** @var Mage_Core_Model_Date $dateModel */
        $dateModel = Mage::getModel('core/date');
        $currentDate = $dateModel->date('Y-m-d H:i:s');

        $this->_joinByAttribute($select, 'news_to_date', 0);
        $this->_joinByAttribute($select, 'news_from_date', 0);

        $select->where("(news_to_date_table.value >= '" . $currentDate ."' OR news_to_date_table.value IS NULL)
            AND (news_from_date_table.value <= '" . $currentDate . "')");
    }

    /**
     * Apply filter 'New Arrivals' to collection
     */
    protected function _applyArrivalsFilter()
    {
        $collection = $this->getLayer()->getProductCollection();
        $this->_applyNewArrivalsToSelect($collection->getSelect());
    }

    /**
     * Get select object prepared for get count sql
     *
     * @return Varien_Db_Select
     */
    protected function _prepareForCount()
    {
        $collection = $this->getLayer()->getProductCollection();
        $select = clone $collection->getSelect();
        // reset columns, order and limitation conditions
        $select->reset(Zend_Db_Select::COLUMNS);
        $select->reset(Zend_Db_Select::ORDER);
        $select->reset(Zend_Db_Select::LIMIT_COUNT);
        $select->reset(Zend_Db_Select::LIMIT_OFFSET);

        return $select;
    }

    /**
     * Calculate count of new products
     *
     * @return int
     */
    protected function _getCountNewArrivals()
    {
        $select = $this->_prepareForCount();
        $this->_applyNewArrivalsToSelect($select);
        $select->columns('COUNT(DISTINCT e.entity_id) as count');

        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getSingleton('core/resource');
        /** @var Magento_Db_Adapter_Pdo_Mysql $readConnection */
        $readConnection = $resource->getConnection('core_read');
        $res = $readConnection->fetchRow($select);
        return (int)$res['count'];
    }

    /**
     * Apply 'Only on sale' filter to select object
     * @param Varien_Db_Select $select
     */
    protected function _applyOnSaleToSelect(Varien_Db_Select $select)
    {
        $this->_joinByAttribute($select, 'special_price', 0);
        $select->where(
            '(
             IF (`price_index`.`tier_price` IS NOT NULL,
                LEAST(price_index.min_price, price_index.tier_price),
                    `price_index`.`min_price`
                )
                <
                IF (`special_price_table`.`value` IS NOT NULL, `special_price_table`.`value`, price)
             )', 1);
    }

    /**
     * Apply filter 'Only on sale' to collection
     */
    protected function _applyOnSaleFilter()
    {
        $collection = $this->getLayer()->getProductCollection();
        $this->_applyOnSaleToSelect($collection->getSelect());
    }

    /**
     * Calculate product count in on sale filter
     *
     * @return int
     */
    protected function _getCountOnSale()
    {
        $select = $this->_prepareForCount();
        $this->_applyOnSaleToSelect($select);
        $select->columns('COUNT(DISTINCT e.entity_id) as count');

        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getSingleton('core/resource');
        /** @var Magento_Db_Adapter_Pdo_Mysql $readConnection */
        $readConnection = $resource->getConnection('core_read');

        $res = $readConnection->fetchRow($select);

        return (int)$res['count'];
    }

    /**
     * Apply attribute option filter to product collection
     *
     * @param   Zend_Controller_Request_Abstract $request
     * @param   Varien_Object $filterBlock
     * @return  Mage_Catalog_Model_Layer_Filter_Attribute
     */
    public function apply(Zend_Controller_Request_Abstract $request, $filterBlock)
    {
        $filter = $request->getParam($this->_requestVar);
        if ($filter) {
            switch ($filter) {
                case 'arrivals':
                    $this->getLayer()->getState()->addFilter($this->_createItem(
                        'only new',
                        'arrivals'
                    ));
                    $this->_applyArrivalsFilter();
                    break;
                case 'sale':
                    $this->getLayer()->getState()->addFilter($this->_createItem(
                        'only on sale',
                        'sale'
                    ));
                    $this->_applyOnSaleFilter();
                    break;
            }
            $this->_items = array();//clear filter options
        }
        return $this;
    }

    /**
     * Get data array for building filter items
     *
     * result array should have next structure:
     * array(
     *      $index => array(
     *          'label' => $label,
     *          'value' => $value,
     *          'count' => $count
     *      )
     * )
     *
     * @return array
     */
    protected function _getItemsData()
    {
        return array(
            0 => array(
                'label' => 'new arrivals',
                'value' => 'arrivals',
                'count' => $this->_getCountNewArrivals()
            ),
            1 => array(
                'label' => 'on sale',
                'value' => 'sale',
                'count' => $this->_getCountOnSale()
            )
        );
    }

    /**
     * Get attribute model associated with filter
     *
     * @return Mage_Catalog_Model_Resource_Eav_Attribute
     */
    public function getAttributeModel()
    {
        return $this;
    }
}