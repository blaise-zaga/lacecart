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
namespace Pop\Db\Sql;

/**
 * Abstract SQL class
 *
 * @category   Pop
 * @package    Pop_Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2015 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    2.0.1
 */
abstract class AbstractSql
{

    /**
     * SQL columns
     * @var array
     */
    protected $columns = [];

    /**
     * SQL object
     * @var \Pop\Db\Sql
     */
    protected $sql = null;

    /**
     * ORDER BY value
     * @var string
     */
    protected $orderBy = null;

    /**
     * LIMIT value
     * @var mixed
     */
    protected $limit = null;

    /**
     * OFFSET value
     * @var int
     */
    protected $offset = null;

    /**
     * Constructor
     *
     * Instantiate the SQL object.
     *
     * @param  \Pop\Db\Sql $sql
     * @param  mixed       $columns
     * @return AbstractSql
     */
    public function __construct(\Pop\Db\Sql $sql, $columns = null)
    {
        $this->setSql($sql);
        if (null !== $columns) {
            if (!is_array($columns)) {
                $columns = [$columns];
            }
            $this->columns = $columns;
        }
    }

    /**
     * Get the SQL object
     *
     * @param \Pop\Db\Sql $sql
     * @return AbstractSql
     */
    public function setSql(\Pop\Db\Sql $sql)
    {
        $this->sql = $sql;
        return $this;
    }

    /**
     * Set the SQL object
     *
     * @return \Pop\Db\Sql
     */
    public function getSql()
    {
        return $this->sql;
    }

    /**
     * Set the ORDER BY value
     *
     * @param mixed  $by
     * @param string $order
     * @return AbstractSql
     */
    public function orderBy($by, $order = 'ASC')
    {
        $byColumns = null;

        if (is_array($by)) {
            $quotedAry = [];
            foreach ($by as $value) {
                $quotedAry[] = $this->sql->quoteId(trim($value));
            }
            $byColumns = implode(', ', $quotedAry);
        } else if (strpos($by, ',') !== false) {
            $ary = explode(',' , $by);
            $quotedAry = [];
            foreach ($ary as $value) {
                $quotedAry[] = $this->sql->quoteId(trim($value));
            }
            $byColumns = implode(', ', $quotedAry);
        } else {
            $byColumns = $this->sql->quoteId(trim($by));
        }

        $this->orderBy .= ((null !== $this->orderBy) ? ', ' : '') . $byColumns;
        $order = strtoupper($order);

        if (strpos($order, 'RAND') !== false) {
            $this->orderBy = ($this->sql->getDbType() == \Pop\Db\Sql::SQLITE) ? ' RANDOM()' : ' RAND()';
        } else if (($order == 'ASC') || ($order == 'DESC')) {
            $this->orderBy .= ' ' . $order;
        }

        return $this;
    }

    /**
     * Set the LIMIT value
     *
     * @param mixed $limit
     * @return AbstractSql
     */
    public function limit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Set the OFFSET value
     *
     * @param  int $offset
     * @return AbstractSql
     */
    public function offset($offset)
    {
        $this->offset = (int)$offset;
        return $this;
    }

    /**
     * Abstract render method
     *
     * @return string
     */
    abstract public function render();

}
