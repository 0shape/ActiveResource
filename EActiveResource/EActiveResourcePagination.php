<?php
/**
 * EActiveResourcePagination class file.
 *
 * @author Johannes "Haensel" Bauer <thehaensel@gmail.com>
 */

/**
 * EActiveResourcePagination is the base class used for pagination in combination with ActiveResources
 * 
 */
class EActiveResourcePagination extends CPagination
{   
    /**
    * @return integer the offset of the data. This may be used to set the
    * OFFSET value for fetching the current page of data.

    public function getOffset()
    {
            return $this->getCurrentPage()+1;
    }
     */
    /**
    * @return integer the limit of the data. This may be used to set the
    * LIMIT value for fetching the current page of data.
    * This returns the same value as {@link pageSize}.
    */
    public function getLimit()
    {
            return $this->getPageSize();
    }
}
