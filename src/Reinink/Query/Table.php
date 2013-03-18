<?php
/**
 * An simple ORM for basic insert, update and delete operations.
 *
 * @package  Query
 * @version  1.2.0
 * @author   Jonathan Reinink <jonathan@reininks.com>
 * @link     https://github.com/reinink/Query
 */

namespace Reinink\Query;

use \Exception;
use \ReflectionClass;

abstract class Table
{
    public function __get($property)
    {
        $method = str_replace(' ', '', ucwords(str_replace('_', ' ', $property)));

        if (method_exists($this, 'get' . $method)) {
            return call_user_func(array($this, 'get' . $method));
        }

        if (property_exists($this, $property)) {
            return $this->$property;
        }
    }

    public function __set($property, $value)
    {
        $method = str_replace(' ', '', ucwords(str_replace('_', ' ', $property)));

        if (method_exists($this, 'set' . $method)) {
            return call_user_func_array(array($this, 'set' . $method), array($value));
        }

        if (property_exists($this, $property)) {
            $this->$property = $value;
        }

        return $this;
    }

    public function insert()
    {
        if (isset($this->id)) {
            throw new Exception('Primary key is already set.');
        }

        $class = get_called_class();

        $model = new ReflectionClass(get_called_class());

        foreach ($model->getProperties() as $property) {

            if ($property->isProtected() and
               !$property->isStatic() and
                $property->getName() !== 'id') {

                $values[$property->getName()] = $this->{$property->getName()};
            }
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $class::DB_TABLE,
            implode(', ', array_keys($values)),
            ':' . implode(', :', array_keys($values))
        );

        DB::query($sql, $values);

        $this->id = DB::connection()->lastInsertId();
    }

    public function update()
    {
        if (!isset($this->id)) {
            throw new Exception('Primary key is not set.');
        }

        $class = get_called_class();

        $model = new ReflectionClass(get_called_class());

        foreach ($model->getProperties() as $property) {

            if ($property->isProtected() and
               !$property->isStatic() and
                $property->getName() !== 'id') {

                $values[$property->getName()] = $this->{$property->getName()};
            }
        }

        $sql = sprintf(
            'UPDATE %s SET %s WHERE id = :id',
            $class::DB_TABLE,
            call_user_func(
                function () use ($values) {

                    foreach ($values as $name => $value) {

                        if (isset($sql)) {

                            $sql .= ', ' . $name . ' = :' . $name;

                        } else {

                            $sql = $name . ' = :' . $name;
                        }
                    }

                    return $sql;
                }
            )
        );

        DB::query($sql, array_merge(array('id' => $this->id), $values));
    }

    public function delete()
    {
        if (!isset($this->id)) {
            throw new Exception('Primary key is not set.');
        }

        $class = get_called_class();

        $sql = sprintf('DELETE FROM %s WHERE id = :id', $class::DB_TABLE);

        DB::query($sql, array('id' => $this->id));
    }

    public static function select($fields = '*')
    {
        $class = get_called_class();

        if (is_numeric($fields)) {

            $sql = sprintf('SELECT * FROM %s WHERE id = :id', static::DB_TABLE);

            return DB::row($sql, array('id' => $fields), $class);

        } else {

            return new Select(get_called_class(), $fields);
        }
    }
}
