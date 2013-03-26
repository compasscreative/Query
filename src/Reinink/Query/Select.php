<?php
namespace Reinink\Query;

use \Exception;

class Select
{
    private $class;
    private $table;
    private $fields;
    private $where;
    private $values;
    private $order_by;
    private $limit;

    public function __construct($class, $fields = '*')
    {
        $this->class = $class;
        $this->table = $class::DB_TABLE;
        $this->fields = $fields;
        $this->where = '';
    }

    public function __call($method, $values)
    {
        if (!strncmp($method, 'where', 5)) {

            $method = substr($method, 5);

        } elseif (!strncmp($method, 'and', 3)) {

            $this->where .= ' AND ';
            $method = substr($method, 3);

        } elseif (!strncmp($method, 'or', 2)) {

            $this->where .= ' OR ';
            $method = substr($method, 2);

        } else {

            throw new Exception('Invalid where operator. Allowed: where, and & or.');
        }

        if ($method === false) {

            $this->where .= $values[0] . ' = ?';
            $this->values[] = $values[1];

        } elseif ($method === 'Not') {

            $this->where .= $values[0] . ' != ?';
            $this->values[] = $values[1];

        } elseif ($method === 'Null') {

            $this->where .= $values[0] . ' IS NULL';

        } elseif ($method === 'NotNull') {

            $this->where .= $values[0] . ' IS NOT NULL';

        } elseif ($method === 'Like') {

            $this->where .= $values[0] . ' LIKE ?';
            $this->values[] = $values[1];

        } elseif ($method === 'NotLike') {

            $this->where .= $values[0] . ' NOT LIKE ?';
            $this->values[] = $values[1];

        } elseif ($method === 'In') {

            $this->where .= $values[0] . ' IN (' . str_repeat('?,', count($values[1]) - 1) . '?)';

            foreach ($values[1] as $value) {
                $this->values[] = $value;
            }

        } elseif ($method === 'NotIn') {

            $this->where .= $values[0] . ' NOT IN (' . str_repeat('?,', count($values[1]) - 1) . '?)';

            foreach ($values[1] as $value) {
                $this->values[] = $value;
            }

        } elseif ($method === 'Greater') {

            $this->where .= $values[0] . ' > ?';
            $this->values[] = $values[1];

        } elseif ($method === 'Less') {

            $this->where .= $values[0] . ' < ?';
            $this->values[] = $values[1];

        } elseif ($method === 'GreaterOrEqual') {

            $this->where .= $values[0] . ' >= ?';
            $this->values[] = $values[1];

        } elseif ($method === 'LessOrEqual') {

            $this->where .= $values[0] . ' <= ?';
            $this->values[] = $values[1];

        } else {

            throw new Exception('Invalid where clause (' . $method . ').');
        }

        return $this;
    }

    public function orderBy($fields)
    {
        $this->order_by = $fields;

        return $this;
    }

    public function limit($offset, $limit = null)
    {
        if (is_null($limit)) {
            $this->limit = $offset;
        } else {
            $this->limit = $offset . ', ' . $limit;
        }

        return $this;
    }

    private function build()
    {
        $sql = 'SELECT';
        $sql .= "\n\t" . $this->fields;
        $sql .=  "\n" . 'FROM ';
        $sql .= "\n\t" . $this->table;

        if ($this->where) {
            $sql .= "\n" . 'WHERE';
            $sql .= "\n\t"  . $this->where;
        }

        if ($this->order_by) {
            $sql .= "\n" . 'ORDER BY';
            $sql .= "\n\t" . $this->order_by;
        }

        if ($this->limit) {
            $sql .= "\n" . 'LIMIT ';
            $sql .= $this->limit;
        }

        return $sql;
    }

    public function rows()
    {
        if ($this->fields === '*') {
            return DB::rows($this->build(), $this->values, $this->class);
        } else {
            return DB::rows($this->build(), $this->values);
        }
    }

    public function row()
    {
        if ($this->fields === '*') {
            return DB::row($this->build(), $this->values, $this->class);
        } else {
            return DB::row($this->build(), $this->values);
        }
    }

    public function field()
    {
        return DB::field($this->build(), $this->values);
    }
}
