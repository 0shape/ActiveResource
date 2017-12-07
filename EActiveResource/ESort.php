<?php
/**
 * ESort is an extension of CSort. It is specifically designed to be used together with {@link EActiveResource}.
 */
class ESort extends CSort
{
    /**
     * @var array list of attributes that are allowed to be sorted.
     * For a description of the property, read the description of {@link CSort::attributes}.
     * There is one difference though: when defining the 'desc' element of a virtual attribute, the proper format is:
     * <pre>
     * 'user' => array(
     *     'asc' => 'first_name',
     *     'desc' => 'first_name.DESC',
     * )
     * </pre>
     * Note the '.' (dot) separator after the attribute name and the 'DESC' keyword.
     */
    public $attributes = array();

    /**
     * Returns the real definition of an attribute given its name.
     *
     * The resolution is based on {@link attributes} and {@link EActiveResource::attributeNames}.
     * <ul>
     * <li>When {@link attributes} is an empty array, if the name refers to an attribute of {@link modelClass},
     * then the name is returned back.</li>
     * <li>When {@link attributes} is not empty, if the name refers to an attribute declared in {@link attributes},
     * then the corresponding virtual attribute definition is returned. If {@link attributes} contains a star ('*')
     * element, the name will also be used to match against all model attributes.</li>
     * <li>In all other cases, false is returned, meaning the name does not refer to a valid attribute.</li>
     * </ul>
     *
     * @param string $attribute the attribute name that the user requests to sort on
     *
     * @return mixed the attribute name or the virtual attribute definition. False if the attribute cannot be sorted.
     */
    public function resolveAttribute($attribute)
    {
        if ($this->attributes !== array()) {
            $attributes = $this->attributes;
        } elseif ($this->modelClass !== null) {
            $attributes = EActiveResource::model($this->modelClass)->attributeNames();
        } else {
            return false;
        }
        foreach ($attributes as $name => $definition) {
            if (is_string($name)) {
                if ($name === $attribute) {
                    return $definition;
                }
            } elseif ($definition === '*') {
                if ($this->modelClass !== null && EActiveResource::model($this->modelClass)->hasAttribute($attribute)) {
                    return $attribute;
                }
            } elseif ($definition === $attribute) {
                return $attribute;
            }
        }
        return false;
    }

    /**
     * Resolves the attribute label for the specified attribute.
     * This will invoke {@link EActiveResource::getAttributeLabel} to determine what label to use.
     * If the attribute refers to a virtual attribute declared in {@link attributes},
     * then the label given in the {@link attributes} will be returned instead.
     *
     * @param string $attribute the attribute name.
     *
     * @return string the attribute label
     */
    public function resolveLabel($attribute)
    {
        $definition = $this->resolveAttribute($attribute);
        if (is_array($definition)) {
            if (isset($definition['label'])) {
                return $definition['label'];
            }
        } elseif (is_string($definition)) {
            $attribute = $definition;
        }
        if ($this->modelClass !== null) {
            return EActiveResource::model($this->modelClass)->getAttributeLabel($attribute);
        } else {
            return $attribute;
        }
    }

    /**
     * @param EActiveResourceQueryCriteria $criteria the query criteria
     *
     * @return string the order-by columns represented by this sort object.
     */
    public function getOrderBy($criteria = null)
    {
        $directions = $this->getDirections();
        if (empty($directions)) {
            return is_string($this->defaultOrder) ? $this->defaultOrder : '';
        } else {
//            if ($this->modelClass !== null) {
//                $schema = CActiveRecord::model($this->modelClass)->getDbConnection()->getSchema();
//            }
            $orders = array();
            foreach ($directions as $attribute => $descending) {
                $definition = $this->resolveAttribute($attribute);
                if (is_array($definition)) {
                    if ($descending) {
//                        $orders[] = isset($definition['desc']) ? $definition['desc'] : $attribute . ' DESC';
                        $orders[] = isset($definition['desc']) ? $definition['desc'] : $attribute . '.DESC';
                    } else {
                        $orders[] = isset($definition['asc']) ? $definition['asc'] : $attribute;
                    }
                } elseif ($definition !== false) {
                    $attribute = $definition;
//                    if (isset($schema)) {
//                        if (($pos = strpos($attribute, '.')) !== false) {
//                            $attribute = $schema->quoteTableName(substr($attribute, 0, $pos)) . '.'
//                                . $schema->quoteColumnName(substr($attribute, $pos + 1));
//                        } else {
//                            $attribute = (
//                            $criteria === null || $criteria->alias === null
//                                ? CActiveRecord::model($this->modelClass)->getTableAlias(true)
//                                : $schema->quoteTableName($criteria->alias))
//                                . '.' . $schema->quoteColumnName($attribute);
//                        }
//                    }
//                    $orders[] = $descending ? $attribute . ' DESC' : $attribute;
                    $orders[] = $descending ? $attribute . '.DESC' : $attribute;
                }
            }
            return implode(', ', $orders);
        }
    }

    /**
     * Modifies the query criteria by changing its {@link EActiveResourceQueryCriteria::order} property.
     * This method will use {@link directions} to determine which columns need to be sorted.
     * They will be put in the ORDER BY clause. If the criteria already has non-empty {@link CDbCriteria::order} value,
     * the new value will be appended to it.
     *
     * @param EActiveResourceQueryCriteria $criteria the query criteria
     */
    public function applyOrder($criteria)
    {
        $order = $this->getOrderBy($criteria);
        if (!empty($order)) {
            if (!empty($criteria->order)) {
                $criteria->order .= ', ';
            }
            $criteria->order .= $order;
        }
    }
}
