<?php
/**
 * A simple query builder for select statements.
 *
 * @package  Query
 * @version  1.0
 * @author   Jonathan Reinink <jonathan@reininks.com>
 * @link     https://github.com/reinink/Query
 */

namespace Reinink\Query;

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
		if (!strncmp($method, 'and_', strlen('and_')))
		{
			$this->where .= ' AND ';
			$method = substr($method, 4);
		}
		else if (!strncmp($method, 'or_', strlen('or_')))
		{
			$this->where .= ' OR ';
			$method = substr($method, 3);
		}

		if (substr($method, -strlen('_not')) === '_not')
		{
			$this->where .= substr($method, 0, -4) . ' != ?';
			$this->values[] = $values[0];
		}
		else if (substr($method, -strlen('_not_null')) === '_not_null')
		{
			$this->where .= substr($method, 0, -9) . ' IS NOT NULL';
		}
		else if (substr($method, -strlen('_null')) === '_null')
		{
			$this->where .= substr($method, 0, -5) . ' IS NULL';
		}
		else if (substr($method, -strlen('_not_like')) === '_not_like')
		{
			$this->where .= substr($method, 0, -9) . ' NOT LIKE ?';
			$this->values[] = $values[0];
		}
		else if (substr($method, -strlen('_like')) === '_like')
		{
			$this->where .= substr($method, 0, -5) . ' LIKE ?';
			$this->values[] = $values[0];
		}
		else if (substr($method, -strlen('_not_in')) === '_not_in')
		{
			$this->where .= substr($method, 0, -7) . ' NOT IN (' . str_repeat('?,', count($values[0]) - 1) . '?)';

			foreach ($values[0] as $value)
			{
				$this->values[] = $value;
			}
		}
		else if (substr($method, -strlen('_in')) === '_in')
		{
			$this->where .= substr($method, 0, -3) . ' IN (' . str_repeat('?,', count($values[0]) - 1) . '?)';

			foreach ($values[0] as $value)
			{
				$this->values[] = $value;
			}
		}
		else if (substr($method, -strlen('_greater')) === '_greater')
		{
			$this->where .= substr($method, 0, -8) . ' > ?';
			$this->values[] = $values[0];
		}
		else if (substr($method, -strlen('_less')) === '_less')
		{
			$this->where .= substr($method, 0, -5) . ' < ?';
			$this->values[] = $values[0];
		}
		else if (substr($method, -strlen('_greater_equal')) === '_greater_equal')
		{
			$this->where .= substr($method, 0, -14) . ' >= ?';
			$this->values[] = $values[0];
		}
		else if (substr($method, -strlen('_less_equal')) === '_less_equal')
		{
			$this->where .= substr($method, 0, -11) . ' <= ?';
			$this->values[] = $values[0];
		}
		else
		{
			$this->where .= $method . ' = ?';
			$this->values[] = $values[0];
		}

		return $this;
	}

	public function order_by($fields)
	{
		$this->order_by = $fields;

		return $this;
	}

	public function limit($offset, $limit = null)
	{
		if (is_null($limit))
		{
			$this->limit = $offset;
		}
		else
		{
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

		if ($this->where)
		{
			$sql .= "\n" . 'WHERE';
			$sql .= "\n\t"  . $this->where;
		}

		if ($this->order_by)
		{
			$sql .= "\n" . 'ORDER BY';
			$sql .= "\n\t" . $this->order_by;
		}

		if ($this->limit)
		{
			$sql .= "\n" . 'LIMIT ';
			$sql .= $this->limit;
		}

		return $sql;
	}

	public function rows()
	{
		if ($this->fields === '*')
		{
			return DB::rows($this->build(), $this->values, $this->class);
		}
		else
		{
			return DB::rows($this->build(), $this->values);
		}
	}

	public function row()
	{
		if ($this->fields === '*')
		{
			return DB::row($this->build(), $this->values, $this->class);
		}
		else
		{
			return DB::row($this->build(), $this->values);
		}
	}

	public function field()
	{
		return DB::field($this->build(), $this->values);
	}
}