<?php
/**
 * EActiveResourceDataProvider class file.
 *
 * @author Johannes "Haensel" Bauer <thehaensel@gmail.com>
 */

/**
 * EActiveResourceDataProvider implements a data provider based on ActiveResource.
 *
 * EActiveResourceDataProvider provides data in terms of ActiveResource objects which are
 * of class {@link modelClass}. It uses the AR {@link EActiveResouce::findAll} method
 * to retrieve the data from a REST api. The {@link criteria} property can be used to
 * specify various "query" options.
 *
 * CActiveDataProvider may be used in the following way:
 * <pre>
 * $dataProvider=new EActiveResourceDataProvider('Post', array(
 *     'criteria'=>array(
 *         'condition'=>'status=1',
 *     ),
 *     'pagination'=>array(
 *         'pageSize'=>20,
 *     ),
 * ));
 * // $dataProvider->getData() will return a list of Post objects
 * </pre>
 *
 * @property EActiveResourceQueryCriteria $criteria The query criteria.
 *
 * @author Johannes "Haensel" Bauer <thehaensel@gmail.com>
 */
class EActiveResourceDataProvider extends CDataProvider
{
    /**
     * @var string the primary ActiveResource class name. The {@link getData()} method
     * will return a list of objects of this class.
     */
    public $modelClass;
    /**
     * @var EActiveResource the AR finder instance (eg <code>Post::model()</code>).
     * This property can be set by passing the finder instance as the first parameter
     * to the constructor. For example, <code>Post::model()->published()</code>.
     */
    public $model;
    /**
     * @var string the name of key attribute for {@link modelClass}. If not set,
     * it means the id of the corresponding database table will be used.
     */
    public $keyAttribute;

    private $_criteria;
    private $_pagination;

    /**
     * Constructor.
     *
     * @param mixed $modelClass the model class (e.g. 'Post') or the model instance
     * (e.g. <code>Post::model()</code>, <code>Post::model()->published()</code>).
     * @param array $config     configuration (name=>value) to be applied as the initial property values of this class.
     */
    public function __construct($modelClass, $config = array())
    {
        if (is_string($modelClass)) {
            $this->modelClass = $modelClass;
            $this->model = EActiveResource::model($this->modelClass);
        } else {
            if ($modelClass instanceof EActiveResource) {
                $this->modelClass = get_class($modelClass);
                $this->model = $modelClass;
            }
        }
        $this->setId($this->modelClass);
        foreach ($config as $key => $value) {
            $this->$key = $value;
        }
    }

    public function getPagination()
    {
        if ($this->_pagination === null) {
            $this->_pagination = new EActiveResourcePagination;
            if (($id = $this->getId()) != '') {
                $this->_pagination->pageVar = $id . '_page';
            }
        }
        return $this->_pagination;
    }

    /**
     * Returns the query criteria.
     *
     * @return EActiveResourceQueryCriteria the query criteria
     */
    public function getCriteria()
    {
        if ($this->_criteria === null) {
            $this->_criteria = new EActiveResourceQueryCriteria;
        }
        return $this->_criteria;
    }

    /**
     * Sets the query criteria.
     *
     * @param mixed $value the query criteria. This can be either a EActiveResourceQueryCriteria object or an array
     *                     representing the query criteria.
     */
    public function setCriteria($value)
    {
        $this->_criteria
            = $value instanceof EActiveResourceQueryCriteria ? $value : new EActiveResourceQueryCriteria($value);
    }

    /**
     * Returns the sorting object.
     *
     * @param string $className the sorting object class name
     *
     * @return mixed the sorting object. If this is false, it means the sorting is disabled.
     */
    public function getSort($className = 'ESort')
    {
        if (($sort = parent::getSort($className)) !== false) {
            $sort->modelClass = $this->modelClass;
        }
        return $sort;
    }

    /**
     * Fetches the data from the persistent data storage.
     *
     * @return array list of data items
     */
    protected function fetchData()
    {
        $criteria = clone $this->getCriteria();

        if (($pagination = $this->getPagination()) !== false) {
            $pagination->setItemCount($this->getTotalItemCount());
            $pagination->applyLimit($criteria);
        }

        $baseCriteria = $this->model->getQueryCriteria(false);

        if (($sort = $this->getSort()) !== false) {
            if ($baseCriteria !== null) {
                $c = clone $baseCriteria;
                $c->mergeWith($criteria);
                $this->model->setQueryCriteria($c);
            } else {
                $this->model->setQueryCriteria($criteria);
            }
            $sort->applyOrder($criteria);
        }

        $this->model->setQueryCriteria($baseCriteria !== null ? clone $baseCriteria : null);
        $data = $this->model->findAll($criteria);
        $this->model->setQueryCriteria($baseCriteria); // restore original criteria
        return $data;
    }

    /**
     * Fetches the data item keys from the persistent data storage.
     *
     * @return array list of data item keys.
     */
    protected function fetchKeys()
    {
        $keys = array();
        foreach ($this->getData() as $i => $data) {
            $key = $this->keyAttribute === null ? $data->idProperty() : $data->{$this->keyAttribute};
            $keys[$i] = is_array($key) ? implode(',', $key) : $key;
        }
        return $keys;
    }

    /**
     * Calculates the total number of data items.
     *
     * @return integer the total number of data items.
     */
    protected function calculateTotalItemCount()
    {
        $baseCriteria = $this->model->getQueryCriteria(false);
        if ($baseCriteria !== null) {
            $baseCriteria = clone $baseCriteria;
        }
        $count = count($this->model->getRequest('collection', $this->getCriteria())->getData());
        $this->model->setQueryCriteria($baseCriteria);
        return $count;
    }
}