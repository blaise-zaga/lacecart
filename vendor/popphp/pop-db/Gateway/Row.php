<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2015 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db\Gateway;

/**
 * Row gateway class
 *
 * @category   Pop
 * @package    Pop_Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2015 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    2.0.1
 */
class Row extends AbstractGateway
{

    /**
     * Primary values
     * @var array
     */
    protected $primaryValues = [];

    /**
     * Row column values
     * @var array
     */
    protected $columns = [];

    /**
     * Constructor
     *
     * Instantiate the Gateway\Row object.
     *
     * @param  \Pop\Db\Sql $sql
     * @param  mixed       $keys
     * @param  string      $table
     * @throws Exception
     * @return Row
     */
    public function __construct(\Pop\Db\Sql $sql, $keys = null, $table = null)
    {
        if (null !== $keys) {
            $this->setPrimaryKeys($keys);
        }
        parent::__construct($sql, $table);
    }

    /**
     * Get the columns
     *
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Set the columns
     *
     * @param  array $columns
     * @return Row
     */
    public function setColumns(array $columns = [])
    {
        $this->columns = $columns;
        if (count($this->primaryValues) == 0) {
            foreach ($this->primaryKeys as $key) {
                if (isset($this->columns[$key])) {
                    $this->primaryValues[] = $this->columns[$key];
                }
            }
        }
        return $this;
    }

    /**
     * Find row by primary key values
     *
     * @param  mixed $values
     * @throws Exception
     * @return void
     */
    public function find($values)
    {
        if (count($this->primaryKeys) == 0) {
            throw new Exception('Error: The primary key(s) have not been set.');
        }

        $this->primaryValues = (is_array($values)) ? $values : [$values];

        if (count($this->primaryKeys) != count($this->primaryValues)) {
            throw new Exception('Error: The number of primary key(s) and primary value(s) do not match.');
        }

        if (null === $this->table) {
            throw new Exception('Error: The table has not been set');
        }

        $this->sql->from($this->table)->select(new \Pop\Db\Sql\Select($this->sql));
        $params = [];

        foreach ($this->primaryKeys as $i => $primaryKey) {
            $placeholder = $this->sql->getPlaceholder();

            if ($placeholder == ':') {
                $placeholder .= $primaryKey;
            } else if ($placeholder == '$') {
                $placeholder .= ($i + 1);
            }
            $this->sql->select()->where->equalTo($primaryKey, $placeholder);
            $params[$primaryKey] = $this->primaryValues[$i];
        }

        $this->sql->select()->limit(1);

        $this->sql->db()->prepare((string)$this->sql)
                        ->bindParams($params)
                        ->execute();

        $rows = $this->sql->db()->fetchResult();

        if (isset($rows[0])) {
            $this->columns = $rows[0];
        }
    }

    /**
     * Save (insert new or update existing) row in the table
     *
     * @param  boolean $new
     * @throws Exception
     * @return Row
     */
    public function save($new = false)
    {
        if (null === $this->table) {
            throw new Exception('Error: The table has not been set');
        }

        $columns = [];
        $params  = [];

        // If the row was found and exists, then update
        if (!$new) {
            $i = 1;
            foreach ($this->columns as $column => $value) {
                if (!in_array($column, $this->primaryKeys)) {
                    $placeholder = $this->sql->getPlaceholder();

                    if ($placeholder == ':') {
                        $placeholder .= $column;
                    } else if ($placeholder == '$') {
                        $placeholder .= $i;
                    }
                    $columns[$column] = $placeholder;
                    $params[$column]  = $value;
                    $i++;
                }
            }

            $this->sql->from($this->table)->update(new \Pop\Db\Sql\Update($this->sql, $columns));

            foreach ($this->primaryKeys as $key => $primaryKey) {
                $placeholder = $this->sql->getPlaceholder();

                if ($placeholder == ':') {
                    $placeholder .= $primaryKey;
                } else if ($placeholder == '$') {
                    $placeholder .= $i;
                }
                $this->sql->update()->where->equalTo($primaryKey, $placeholder);
                if (isset($this->primaryValues[$key])) {
                    if (substr($placeholder, 0 , 1) == ':') {
                        $params[$this->primaryKeys[$key]] = $this->primaryValues[$key];
                    } else {
                        $params[$key] = $this->primaryValues[$key];
                    }
                } else if (isset($this->columns[$this->primaryKeys[$key]])) {
                    if (substr($placeholder, 0 , 1) == ':') {
                        $params[$this->primaryKeys[$key]] = $this->columns[$this->primaryKeys[$key]];
                    } else {
                        $params[$key] = $this->columns[$this->primaryKeys[$key]];
                    }

                } else {
                    throw new Exception('Error: The value of \'' . $key . '\' is not set');
                }
                $i++;
            }

            $this->sql->db()->prepare((string)$this->sql)
                            ->bindParams($params)
                            ->execute();
        // Else, insert new
        } else {
            $i = 1;
            foreach ($this->columns as $column => $value) {
                $placeholder = $this->sql->getPlaceholder();

                if ($placeholder == ':') {
                    $placeholder .= $column;
                } else if ($placeholder == '$') {
                    $placeholder .= $i;
                }
                $columns[$column] = $placeholder;
                $params[$column]  = $value;
                $i++;
            }
            $this->sql->from($this->table)->insert(new \Pop\Db\Sql\Insert($this->sql, $columns));

            $this->sql->db()->prepare((string)$this->sql)
                            ->bindParams($params)
                            ->execute();

            if ((count($this->primaryKeys) == 1) && !isset($this->columns[$this->primaryKeys[0]])) {
                $this->columns[$this->primaryKeys[0]] = $this->sql->db()->lastId();
            }
        }

        return $this;
    }

    /**
     * Delete row from the table
     *
     * @throws Exception
     * @return Row
     */
    public function delete()
    {
        if (count($this->primaryKeys) == 0) {
            throw new Exception('Error: The primary key(s) have not been set.');
        }

        if (count($this->primaryKeys) != count($this->primaryValues)) {
            throw new Exception('Error: The number of primary key(s) and primary value(s) do not match.');
        }

        if (null === $this->table) {
            throw new Exception('Error: The table has not been set');
        }

        $this->sql->from($this->table)->delete(new \Pop\Db\Sql\Delete($this->sql));
        $params = [];

        foreach ($this->primaryKeys as $i => $primaryKey) {
            $placeholder = $this->sql->getPlaceholder();

            if ($placeholder == ':') {
                $placeholder .= $primaryKey;
            } else if ($placeholder == '$') {
                $placeholder .= ($i + 1);
            }
            $this->sql->delete()->where->equalTo($primaryKey, $placeholder);
            $params[$primaryKey] = $this->primaryValues[$i];
        }

        $this->sql->db()->prepare((string)$this->sql)
                        ->bindParams($params)
                        ->execute();

        $this->columns       = [];
        $this->primaryValues = [];

        return $this;
    }

    /**
     * Magic method to set the property to the value of $this->columns[$name].
     *
     * @param  string $name
     * @param  mixed $value
     * @return void
     */
    public function __set($name, $value)
    {
        $this->columns[$name] = $value;
    }

    /**
     * Magic method to return the value of $this->columns[$name].
     *
     * @param  string $name
     * @return mixed
     */
    public function __get($name)
    {
        return (isset($this->columns[$name])) ? $this->columns[$name] : null;
    }

    /**
     * Magic method to return the isset value of $this->columns[$name].
     *
     * @param  string $name
     * @return boolean
     */
    public function __isset($name)
    {
        return isset($this->columns[$name]);
    }

    /**
     * Magic method to unset $this->columns[$name].
     *
     * @param  string $name
     * @return void
     */
    public function __unset($name)
    {
        if (isset($this->columns[$name])) {
            unset($this->columns[$name]);
        }
    }

}
