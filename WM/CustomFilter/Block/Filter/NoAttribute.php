<?php
/**
 * NoAttribute Block
 * 
 * @category   WM
 * @package    WM_CustomFilter
 *
 *
 * Class WM_CustomFilter_Block_Filter_NoAttribute
 */
class WM_CustomFilter_Block_Filter_NoAttribute extends Mage_Catalog_Block_Layer_Filter_Abstract
{
    /**
     * Resource instance
     *
     * @var ...
     */
    protected $_resource;

    /**
     * Construct attribute filter
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->_filterModelName = 'wm_customfilter/NoAttribute';
    }

    /**
     * Retrieve model instance
     *
     * @return WM_CustomFilter_Model_NoAttribute
     */
    protected function _getResource()
    {
        if (is_null($this->_resource)) {
            $this->_resource = Mage::getResourceModel('wm_customfilter/NoAttribute');
        }
        return $this->_resource;
    }
}