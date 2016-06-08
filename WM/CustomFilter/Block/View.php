<?php
/**
 * View.php
 * 
 * @category   WM
 * @package    WM_CustomFilter
 *
 *
 * Class WM_CustomFilter_Block_View
 */
class WM_CustomFilter_Block_View extends Mage_Catalog_Block_Layer_View
{
    /**
     * Name of custom filter block
     * @var string
     */
    protected $_noAttributeBlockName = 'wm_customfilter/filter_NoAttribute';

    /**
     * Add custom block in frontend
     * @return Mage_Catalog_Block_Layer_View
     */
    protected function _prepareLayout()
    {
        /** @var WM_CustomFilter_Block_Filter_NoAttribute $noAttributeBlock */
        $noAttributeBlock = $this->getLayout()->createBlock($this->_noAttributeBlockName);
        $noAttributeBlock
            ->setLayer($this->getLayer())
            ->init();
        $this->setChild('no_attribute', $noAttributeBlock);
        return parent::_prepareLayout();

    }

    /**
     * Get custom filter block
     *
     * @return Mage_Catalog_Block_Layer_Filter_Category
     */
    protected function _getCustomFilter()
    {
        return $this->getChild('no_attribute');
    }

    /**
     * Get all layer filters
     *
     * @return array
     */
    public function getFilters()
    {
        $filters = parent::getFilters();
        if ($customFilter = $this->_getCustomFilter()) {
            $filters = array_merge(array($customFilter), $filters);
        }
        return $filters;
    }
}