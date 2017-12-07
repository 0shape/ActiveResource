<?php
/**
 * EActiveResourceQueryCriteria class file.
 *
 * @author Johannes "Haensel" Bauer <thehaensel@gmail.com>
 */

/**
 * EActiveResourceQueryCriteria represents a query criteria, such as conditions, ordering by, limit/offset
 *
 * @author Johannes "Haensel" Bauer <thehaensel@gmail.com>
 */
class EActiveResourceQueryCriteria extends CComponent
{
        const PARAM_PREFIX=':earp';
        /**
         * @var integer the global counter for anonymous binding parameters.
         * This counter is used for generating the name for the anonymous parameters.
         */
        public static $paramCount=0;
        
        /**
         * @var string query condition string
         */
        public $condition='';
        /**
         * @var array list of query parameter values indexed by parameter placeholders.
         * For example, <code>array(':name'=>'Dan', ':age'=>31)</code>.
         */
        public $params=array();
        /**
         * @var integer maximum number of records to be returned. If -1, it means no limit.
         */
        public $limit=-1;
        /**
         * @var integer zero-based offset from where the records are to be returned. If -1, it means starting from the beginning.
         */
        public $offset=-1;
        /**
         * @var string how to sort the query results
         */
        public $order='';

        /**
         * @var string the name of the AR attribute whose value should be used as index of the query result array.
         * Defaults to null, meaning the result array will be zero-based integers.
         */
        public $index;
        
        public $offsetKey='page';
        public $limitKey='count';
        public $sortKey='sort';
        
        /**
     * @var mixed scopes to apply
         *
     * This property is effective only when passing criteria to
         * the one of the following methods:
     * <ul>
     * <li>{@link CActiveRecord::find()}</li>
     * <li>{@link CActiveRecord::findAll()}</li>
     * <li>{@link CActiveRecord::findById()}</li>
     * </ul>
         *
         * Can be set to one of the following:
         * <ul>
         * <li>One scope: $criteria->scopes='scopeName';</li>
         * <li>Multiple scopes: $criteria->scopes=array('scopeName1','scopeName2');</li>
         * <li>Scope with parameters: $criteria->scopes=array('scopeName'=>array($params));</li>
         * <li>Multiple scopes with parameters: $criteria->scopes=array('scopeName1'=>array($params1),'scopeName2'=>array($params2));</li>
         * <li>Multiple scopes with the same name: array(array('scopeName'=>array($params1)),array('scopeName'=>array($params2)));</li>
         * </ul>
         */
        public $scopes;

        /**
         * Constructor.
         * @param array $data criteria initial property values (indexed by property name)
         */
        public function __construct($data=array())
        {
                foreach($data as $name=>$value)
                        $this->$name=$value;
                if(Yii::app()->getComponent('activeresource')!=null && Yii::app()->getComponent('activeresource') instanceof EActiveResourceConnection)
                {
                    $this->limitKey=Yii::app()->activeresource->limitKey;
                    $this->offsetKey=Yii::app()->activeresource->offsetKey;
                    $this->sortKey=Yii::app()->activeresource->sortKey;
                }
        }

        /**
         * Remaps criteria parameters on unserialize to prevent name collisions.
         */
        public function __wakeup()
        {
                $map=array();
                $params=array();
                foreach($this->params as $name=>$value)
                {
                        $newName=self::PARAM_PREFIX.self::$paramCount++;
                        $map[$name]=$newName;
                        $params[$newName]=$value;
                }
                $this->condition=strtr($this->condition,$map);
                $this->params=$params;
        }

        /**
         * Appends a condition to the existing {@link condition}.
         * The new condition and the existing condition will be concatenated with '&'.
         * After calling this method, the {@link condition} property will be modified.
         * @param mixed $condition the new condition. It can be either a string or an array of strings.
         * @return EActiveResourceQueryCriteria the criteria object itself
         */
        public function addCondition($condition)
        {
                if(is_array($condition))
                {
                        if($condition===array())
                                return $this;
                        $condition=implode('&',$condition);
                }
                if($this->condition==='')
                        $this->condition=$condition;
                else
                        $this->condition=$this->condition.'&'.$condition;
                return $this;
        }

        /**
         * Merges with another criteria.
         * @param mixed $criteria the criteria to be merged with. Either an array or EActiveResourceQueryCriteria.
         */
        public function mergeWith($criteria)
        {
                if(is_array($criteria))
                        $criteria=new self($criteria);

                if($this->condition!==$criteria->condition)
                {
                        if($this->condition==='')
                                $this->condition=$criteria->condition;
                        else if($criteria->condition!=='')
                                $this->condition="{$this->condition}&{$criteria->condition}";
                }

                if($this->params!==$criteria->params)
                        $this->params=array_merge($this->params,$criteria->params);

                if($criteria->limit>0)
                        $this->limit=$criteria->limit;

                if($criteria->offset>=0)
                        $this->offset=$criteria->offset;

                if($this->order!==$criteria->order)
                {
                        if($this->order==='')
                                $this->order=$criteria->order;
                        else if($criteria->order!=='')
                                $this->order=$criteria->order.', '.$this->order;
                }

                if($criteria->index!==null)
                        $this->index=$criteria->index;

                if(empty($this->scopes))
                        $this->scopes=$criteria->scopes;
                else if(!empty($criteria->scopes))
                {
                        $scopes1=(array)$this->scopes;
                        $scopes2=(array)$criteria->scopes;
                        foreach($scopes1 as $k=>$v)
                        {
                                if(is_integer($k))
                                        $scopes[]=$v;
                                else if(isset($scopes2[$k]))
                                        $scopes[]=array($k=>$v);
                                else
                                        $scopes[$k]=$v;
                        }
                        foreach($scopes2 as $k=>$v)
                        {
                                if(is_integer($k))
                                        $scopes[]=$v;
                                else if(isset($scopes1[$k]))
                                        $scopes[]=array($k=>$v);
                                else
                                        $scopes[$k]=$v;
                        }
                        $this->scopes=$scopes;
                }
        }

        /**
         * @return array the array representation of the criteria
         */
        public function toArray()
        {
                $result=array();
                foreach(array('condition', 'params', 'limit', 'offset', 'order', 'scopes', 'index') as $name)
                        $result[$name]=$this->$name;
                return $result;
        }
        
        /**
         * Builds the string used to build the "query" => the uri. If no offset or limit is set these parameters will not be appended
         * @return type 
         */
        public function buildQueryString()
        {
            $queryString="";
            if($this->condition!=="" || $this->limit!=="" || $this->offset!=="" || $this->order!=="")
                $queryString="?";
            
            $parameters=array($this->condition);
            
            if($this->offset>0)
                // array_push($parameters, $this->offsetKey.'='.$this->offset);
                array_push($parameters, $this->offsetKey.'='.$this->offset / $this->limit);
            if($this->limit>0)
                array_push($parameters, $this->limitKey.'='.$this->limit);
            if($this->order!=="")
                array_push($parameters, $this->sortKey.'='.$this->order);
            
            $queryString.=implode('&',$parameters);
            
            $queryString=strtr($queryString,$this->params);
            
            return $queryString;
        }
}