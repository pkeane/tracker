<?php

class Pop_Db_Exception extends Exception {}

class Pop_Db implements IteratorAggregate
{

    public $db;
    public $id;
    private $fields = array(); 
    private $columns = array();
    protected $table;
    protected $order_by;
    protected $limit;
    protected $qualifiers = array();

    public function __construct() 
    { 
        $table = $this->getTable();
        $this->db = new PDO('sqlite:'.SQLITE_PATH);
        $this->table = $table;
        $sth = $this->db->prepare("PRAGMA table_info($table)");
        $sth->execute();
        while ($row = $sth->fetch()) {
            if ('id' != $row['name']) {
                $this->fields[$row['name']] = null;
            }
        }
    }

    public function __get( $key )
    {
        if ( array_key_exists( $key, $this->fields ) ) {
            return $this->fields[ $key ];
        }
        //automatically call accessor method if it exists
        $classname = get_class($this);
        $method = 'get'.ucfirst($key);
        if (method_exists($classname,$method)) {
            return $this->{$method}();
        }	
    }

    public function __set( $key, $value )
    {
        if ('id' == $key) {
            $this->id = $value;
        }
        if ( array_key_exists( $key, $this->fields ) ) {
            $this->fields[ $key ] = $value;
            return true;
        }
        return false;
    }

    public function setColumns($coll_array) 
    {
        $this->columns = $coll_array;
    }

    public function load($id)
    {
        $table = $this->getTable();
        $sth = $this->db->prepare("SELECT * FROM $table WHERE id = ?");
        if (!$sth) {
            throw new PDOException('cannot create statement handle');
        }
        $sth->execute(array($id));
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        foreach ($row as $k => $v) {
            if ('id' == $k) { $this->id = $v; }
            $this->fields[$k] = $v;
        }
        return $this;
    }

    function find()
    {
        $dbh = $this->db;
        $table = $this->getTable();
        $sets = array();
        $bind = array();
        $limit = '';
        foreach( array_keys( $this->fields ) as $field ) {
            if (isset($this->fields[ $field ]) && 'id' != $field) {
                $sets []= "$field = ?";
                $bind[] = $this->fields[ $field ];
            }
        }
        foreach ($this->qualifiers as $qual) {
            $f = $qual['field'];
            $op = $qual['operator'];
            //allows is to add 'is null' qualifier
            if ('null' == $qual['value']) {
                $v = $qual['value'];
            } else {
                $v = $dbh->quote($qual['value']);
            }
            $sets[] = "$f $op $v";
        }

        if (count($this->columns)) {
            $column_string = join(',',$this->columns);
        } else {
            $column_string = '*';
        }

        $where = join( " AND ", $sets );
        if ($where) {
            $sql = "SELECT ".$column_string." FROM ".$table. " WHERE ".$where;
        } else {
            $sql = "SELECT ".$column_string." FROM ".$table;
        }
        if (isset($this->order_by)) {
            $sql .= " ORDER BY $this->order_by";
        }
        if (isset($this->limit)) {
            $sql .= " LIMIT $this->limit";
        }
        $sth = $dbh->prepare( $sql );
        if (!$sth) {
            throw new PDOException('cannot create statement handle');
        }

        $sth->execute($bind);
        $list = array();
        while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
            $class = get_class($this);
            $obj = new $class;
            foreach ($row as $k => $v) {
                $obj->$k = $v;
            }
            $list[] = $obj;
        }
        return $list;
    }


    function findOne()
    {
        $this->setLimit(1);
        $set = $this->find();
        if (count($set)) {
            $this->id = $set[0]->id;
            foreach ($set[0] as $k => $v) {
                if ( array_key_exists( $k, $this->fields ) ) {
                    $this->fields[ $k ] = $v;
                }
            }
            return $this;
        }
        return false;
    }

    function findAll($return_empty_array=false)
    {
        $set = array();
        $iter = $this->find();
        foreach ($iter as $it) {
            $set[$it->id] = clone($it);
        }
        if (count($set)) {
            return $set;
        } else {
            if ($return_empty_array) {
                return $set;
            }
            return false;
        }
    }

    public function insert()
    { 
        $db = $this->db;
        $fields = array();
        $inserts = array();
        $bind = array();
        foreach( array_keys( $this->fields ) as $field )
        {
            $fields[]= $field;
            $inserts[]= "?";
            $bind[] = $this->fields[ $field ];
        }
        $field_set = join( ", ", $fields );
        $insert = join( ", ", $inserts );
        $sql = "INSERT INTO ".$this->table. 
            " ( $field_set ) VALUES ( $insert )";
        $sth = $db->prepare( $sql );
        if (! $sth) {
            $error = $db->errorInfo();
            throw new Exception("problem on insert: " . $error[2]);
        }
        if ($sth->execute($bind)) {
            $last_id = $db->lastInsertId();
            $this->id = $last_id;
            return $last_id;
        } else { 
            $error = $sth->errorInfo();
            throw new Exception("could not insert: " . $error[2]);
        }
    }

    public function addWhere($field,$value,$operator)
    {
        if ( 
            in_array(strtolower($operator),array('is not','is','ilike','like','not ilike','not like','=','!=','<','>','<=','>='))
        ) {
            $this->qualifiers[] = array(
                'field' => $field,
                'value' => $value,
                'operator' => $operator
            );
        } else {
            throw new Pop_Db_Exception('addWhere problem');
        }
    }

    function setLimit($limit)
    {
        $this->limit = $limit;
    }

    function orderBy($ob)
    {
        $this->order_by = $ob;
    }

    public function getHasOne($classname)
    {
        $related = new $classname();
        $related_table = $related->getTable();

        $sth = $this->db->prepare("SELECT * FROM $related_table WHERE id = ?");
        if (!$sth) {
            throw new PDOException('cannot create statement handle');
        }
        $sth->execute(array($this->{$related_table.'_id'}));
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $related->id = $row['id'];
        foreach ($row as $k => $v) {
            if ( array_key_exists( $k, $related->fields ) ) {
                $related->fields[ $k ] = $v;
            }
        }
        $this->$related_table = $related;
        return $related;
    }

    public function getHasMany($classname)
    {
        $list = array();
        $related = new $classname();
        $related_table = $related->getTable();
        $fk = $this->table.'_id';
        $sql = "SELECT * FROM $related_table WHERE $fk = ?";
        $sth = $this->db->prepare($sql);
        if (!$sth) {
            throw new PDOException('cannot create statement handle');
        }
        $sth->execute(array($this->id));
        while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
            foreach ($row as $k => $v) {
                $related->$k = $v;
            }
            $list[] = $related;
        }
        $plural = $related_table.'s';
        $this->$plural = $list;
        return $list;
    }

    public function getTable()
    {
        if ($this->table) {
            return $this->table;
        } else {
            return strtolower(get_class($this));
        }
    } 

    function update()
    {
        foreach( $this->fields as $key => $val) {
            $fields[]= $key." = ?";
            $values[]= $val;
        }
        $set = join( ",", $fields );
        $sql = "UPDATE {$this->{'table'}} SET $set WHERE id=?";
        $values[] = $this->id;
        $sth = $this->db->prepare( $sql );
        if (!$sth->execute($values)) {
            $errs = $sth->errorInfo();
            if (isset($errs[2])) {
                throw new PDOException('could not update '.$errs[2]);
            }
        } else {
            return true;
        }
    }

    function delete()
    {
        $sth = $this->db->prepare('DELETE FROM '.$this->table.' WHERE id = ?');
        if (!$sth) {
            throw new PDOException('cannot create statement handle');
        }
        return $sth->execute(array($this->id));
    }

    //implement SPL IteratorAggregate:
    //now simply use 'foreach' to iterate 
    //over object properties
    public function getIterator()
    {
        return new ArrayObject($this->fields);
    }

    public function asArray()
    {
        foreach ($this as $k => $v) {
            $my_array[$k] = $v;
        }
        return $my_array;
    }

}

