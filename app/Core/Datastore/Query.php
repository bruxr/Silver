<?php namespace App\Core\Datastore;

use Exception;

/**
 * Datastore Query Class
 *
 * This class makes it easy to perform queries to the Cloud Datastore
 * by providing a fluent and understandable interfaces/functions.
 *
 * @package Silver
 * @author Brux
 * @since 0.1.0
 */
class Query
{
  
  /**
   * Reference to the datastore.
   *
   * @var App\Core\Datastore\DS
   */
  protected $ds;
  
  /**
   * Entity kind
   *
   * @var string
   */
  protected $kind;
  
  /**
   * Array of parameters to the query
   *
   * @var array
   */
  protected $params = [];
  
  /**
   * The fields we want
   *
   * @var array
   */
  protected $select = ['*'];
  
  /**
   * Contains the query conditions
   *
   * @var string
   */
  protected $where = '';
  
  /**
   * The GROUP BY field
   *
   * @var string
   */
  protected $groupBy = '';
  
  /**
   * The ORDER BY field
   *
   * @var string
   */
  protected $orderBy = '';
  
  /**
   * The ORDER BY direction
   *
   * @var string
   */
  protected $orderByDirection;
  
  /**
   * The LIMIT number
   *
   * @var int
   */
  protected $limit = null;
  
  /**
   * The OFFSET number
   *
   * @var int
   */
  protected $offset = null;
  
  /**
   * Constructor
   *
   * @param string $kind kind of entity this query will be using
   * @param DS $ds optional. reference to the datastore this query will be run to
   * @author Brux
   */
  function __construct($kind, $ds = null)
  {
    $this->ds = $ds;
    $this->kind = $kind;
  }
  
  /**
   * Selects custom fields
   *
   * @param string $field,... the fields/columns you want to fetch
   * @return this
   */
  public function select()
  {
    $this->select = func_get_args();
  }
  
  /**
   * Adds a where clause to the query.
   *
   * Usage:
   * 1. Simple key and value
   * where('name', 'John') -> WHERE name = 'John'
   *
   * 2. Custom comparison
   * where('height', '>', 500) -> WHERE height > 500
   *
   * 3. Custom query
   * where('name = ? AND height > 200', ['Tony']);
   *
   * @return this
   */
  public function where()
  {
    $args = func_get_args();
    $num_args = count($args);
    
    // Custom queries
    if ( $num_args == 2 && is_array($args[1]) )
    {
      $this->where = $args[0];
      $this->params = $args[1];
    }
    else
    {
      // AND equality condition
      if ( $num_args == 2 )
      {
        list($field, $value) = $args;
        $op = '=';
      }
      // AND with custom operation condition
      else
      {
        list($field, $op, $value) = $args;
      }
      $this->appendWhere('AND', $field, $op, $value); 
    }
    return $this;
  }
  
  /**
   * Alias of where()
   *
   * @return this
   */
  public function andWhere()
  {
    return call_user_func_array(array($this, 'where'), func_get_args());
  }
  
  /**
   * Adds a where clause to the query with OR as a logical operator.
   *
   * Usage:
   * 1. Simple key and value
   * where('name', 'John') -> WHERE name = 'John'
   *
   * 2. Custom comparison
   * where('height', '>', 500) -> height > 500
   *
   * @return this
   */
  public function orWhere()
  {
    if ( func_num_args() == 2 )
    {
      list($field, $value) = func_get_args();
      $op = '=';
    }
    else
    {
      list($field, $op, $value) = func_get_args();
    }
    $this->appendWhere('OR', $field, $op, $value); 
    return $this;
  }
  
  /**
   * Adds a GROUP BY clause.
   *
   * @param string $field group items by this field
   * @return this
   */
  public function groupBy($field)
  {
    $this->groupBy = $field;
    return $this;
  }
  
  /**
   * Adds a ORDER BY clause.
   *
   * @param string $field order items by this field
   * @param string $direction optional direction, defaults to ASC (ascending)
   * @return this
   */
  public function orderBy($field, $direction = 'ASC')
  {
    $this->orderBy = $field;
    $this->orderByDirection = $direction;
    return $this;
  }
  
  /**
   * Adds a LIMIT clause.
   *
   * @param int $num limit items to this number
   * @return this
   */
  public function limit($num)
  {
    $this->limit = $num;
    return $this;
  }
  
  /**
   * Adds an OFFSET clause.
   *
   * @param int $num skip $num items
   * @return this
   */
  public function offset($num)
  {
    $this->offset = $num;
    return $this;
  }
  
  /**
   * Sends the query to the datastore.
   *
   * @return array
   */
  public function get()
  {
    if ( $this->ds === null )
    {
      throw new Exception('No datastore to use!');
    }
    return $this->ds->query($this->getQuery(), $this->getParams());
  }
  
  /**
   * Returns the SQL query string.
   *
   * @return string
   */
  public function getQuery()
  {
    $query = 'SELECT ';
    $query .= implode(', ', $this->select) . ' ';
    $query .= "FROM $this->kind ";
    
    if ( ! empty($this->where) )
    {
      $query .= "WHERE $this->where ";
    }
    
    if ( ! empty($this->groupBy) )
    {
      $query .= "GROUP BY $this->groupBy ";
    }
    
    if ( ! empty($this->orderBy) )
    {
      $query .= "ORDER BY $this->orderBy $this->orderByDirection ";
    }
    
    if ( $this->limit !== null )
    {
      $query .= "LIMIT $this->limit ";
    }
    
    if ( $this->offset !== null )
    {
      $query .= "OFFSET $this->offset";
    }
    
    return trim($query);
  }
  
  /**
   * Returns the SQL query parameters.
   *
   * @return array
   */
  public function getParams()
  {
    return $this->params;
  }
  
  protected function appendWhere($logical_op, $field, $op, $value)
  {
    $where = '';
    if ( ! empty($this->where) )
    {
      $where = " $logical_op ";
    }
    $this->where .= $where;
    
    $param_name = $this->generateParamName($field);
    $this->where .= sprintf('%s %s %s', $field, $op, $param_name);
    $this->params[$param_name] = $value;
  }
  
  protected function generateParamName($name)
  {
    $new_name = sprintf(':%s', $name);
    $i = 2;
    while ( isset($this->params[$new_name]) )
    {
      $new_name = sprintf(':%s%i', $name, $i);
      $i++;
    }
    return $new_name;
  }
  
}