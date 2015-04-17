<?php namespace App\Core\Datastore;

use Exception;
use ReflectionClass;
use App\Core\Datastore\Schema;
use App\Core\Datastore\Query;
use Doctrine\Common\Inflector\Inflector;

/**
 * Datastore Class
 *
 * This class is responsible for creating and managing active record objects.
 *
 * @package Silver
 * @author Brux
 */
class DS
{
  
  /**
   * The driver we are currently using
   *
   * @var Driver
   */
  protected $driver;
  
  /**
   * Reference to the schema reader.
   *
   * @var Schema
   */
  protected $schema;
  
  /**
   * Constructor. Setups a datastore connection.
   *
   * @param object $driver the database driver
   * @param Schema $schema optional schema for this database
   */
  public function __construct($driver, Schema $schema = null)
  {
    $this->driver = $driver;
    $this->schema = $schema;
  }
  
  /**
   * Returns a reference to the schema we are using.
   *
   * @return Schema
   */
  public function getSchema()
  {
    return $this->schema;
  }
  
  /**
   * Creates a new Active Record object for the provided $kind.
   *
   * @param string $kind kind of object
   * @param array $properties optional. initial object properties
   * @return App\Core\Datastore\Model
   */
  public function create($kind, array $properties = [])
  {
    // If it is not a fully qualified name of a class, make it one
    if ( strpos($kind, '\\') === false )
    {
      $kind = sprintf('App\Models\%s', Inflector::classify($kind));
    }
    return new $kind($properties, $this);
  }
  
  /**
   * Returns an AR object with $kind or if provided with an ID,
   * a single one that has that ID.
   *
   * @param string $kind kind of object
   * @param int|string $id entity ID or name
   * @return App\Core\Datastore\Model
   */
  public function find($kind, $id = null)
  {
    if ( $id === null )
    {
      return new Query($kind, $this);
    }
    else
    {
      $q = new Query($kind, $this);
      $res = $q->where('id', $id)->get();
      if ( ! empty($res) ) 
      {
        return $res[0];
      }
      else
      {
        return null;
      }
    }
  }
  
  /**
   * Queries the database using $query with the provided $params.
   *
   * @param string $query SQL query
   * @param array $params array of params
   * @return array
   */
  public function query($query, array $params = [])
  {
    if ( $query instanceof Query )
    {
      $params= $query->getParams();
      $query = $query->getQuery();
    }
    
    $res = [];
    $items = $this->driver->find($query, $params);
    
    if ( count($items) > 0 )
    {
      // If the class doesn't exist, use Model
      $class = 'App\\Models\\' . Inflector::classify($this->findKindFromQuery($query));
      if ( ! class_exists($class) )
      {
        $class = 'App\Core\Datastore\Model';
      }
      
      // Build the objects
      foreach ( $items as $item )
      {
        $res[] = new $this->create($item, $this);
      }
    }
    return $res;
  }
  
  /**
   * Persists objects to the database.
   *
   * @param App\Core\Datastore\Model $items item to be saved
   * @return App\Core\Datastore\Model
   */
  public function put($item)
  {
    $class = new ReflectionClass($item);
    $kind = Inflector::tableize($class->getShortName());
    $props = $item->getProperties();
    if ( isset($props['id']) )
    {
      $this->driver->update($kind, $props);
    }
    else
    {
      $props = $this->driver->create($kind, $props);
      $item->id = $props['id'];
    }
    return $item;
  }
  
  /**
   * Removes objects from the database.
   *
   * @param App\Core\Datastore\Model $item item to be deleted
   * @return void
   */
  public function delete($item)
  {
    $class = new ReflectionClass($item);
    $kind = Inflector::tablize($class->getShortName());
    $this->driver->delete($kind, $item->id);
  }
  
  /**
   * undocumented function
   *
   * @return string
   */
  protected function findKindFromQuery($query)
  {
    if ( preg_match('/FROM\s(.+?)\s/i', $query, $matches) )
    {
      return Inflector::singularize($matches[1]);
    }
    else
    {
      throw new Exception(sprintf('Failed to find entity kind from query "%s".', $query));
    }
  }
  
}