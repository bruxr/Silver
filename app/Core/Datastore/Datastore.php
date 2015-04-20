<?php namespace App\Core\Datastore;

use Exception;
use ReflectionClass;
use App\Core\Datastore\Schema;
use App\Core\Datastore\Query;
use Doctrine\Common\Inflector\Inflector;

/**
 * Datastore Class
 *
 * The datastore is responsible for managing the in-memory entities, querying
 * and persisting changes to the database (through drivers) and creating
 * repositories for easily acquiring the entities the app needs.
 *
 * @package Silver
 * @author Brux
 */
class Datastore
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
   * undocumented class variable
   *
   * @var string
   */
  protected $repositories = [];
  
  /**
   * Contains all of the AR objects we manage.
   *
   * @var array
   */
  protected $objects = [];
  
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
   * Returns an entity repository.
   *
   * @param string $kind entity kind
   * @return App\Core\Datastore\Repository
   */
  protected function getRepository($kind)
  {
    if ( ! isset($this->repositories[$kind]) )
    {
      $this->repositories[$kind] = new Repository($kind, $this);
    }
    return $this->repositories[$kind];
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
      $class = sprintf('App\Models\%s', Inflector::classify($kind));
    }
    
    // Use a generic model class if the class doesn't exist
    if ( ! class_exists($class) )
    {
      $class = 'Model';
    }
    
    return new $class($properties, $this);
  }
  
  /**
   * Returns an AR object with $kind or if provided with an ID,
   * a single one that has that ID.
   *
   * @param string $kind kind of object
   * @param int|string $id optional entity ID or name
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
    
    $kind = $this->findKindFromQuery($query);
    return $this->buildObjects($kind, $items);
    
  }
  
  /**
   * Persists objects to the database.
   *
   * @param App\Core\Datastore\Model $items item to be saved
   * @return App\Core\Datastore\Model
   */
  public function put(Model $item)
  {
    $kind = $this->getKindFromObject($item);
    $props = $item->getProperties();
    if ( isset($props['id']) )
    {
      $new_props = $this->driver->update($kind, $props);
    }
    else
    {
      $new_props = $this->driver->create($kind, $props);
      
      // Put the object into our identity map if it's a new object.
      if ( isset($new_props['id']) )
      {
        $this->objects[$kind .'_'. $new_props['id']] = $item;
      }
    }
    $item->hydrate($new_props);
    return $item;
  }
  
  /**
   * Removes objects from the database.
   *
   * @param App\Core\Datastore\Model $item item to be deleted
   * @return void
   */
  public function delete(Model $item)
  {
    $kind = $this->getKindFromObject($obj);
    $this->driver->delete($kind, $item->id);
  }
  
  /**
   * Builds active record objects from an array of results from the database.
   *
   * @param string $kind the kind of object to build
   * @param array $items array of rows/entities from the database.
   * @return array
   */
  protected function buildObjects($kind, array $items)
  {
    $results = [];
    foreach ( $items as $item )
    {
      // If we have an item ID, check if we already queried for that object earlier.
      if ( isset($item['id']) )
      {
        $key = $kind .'_'. $item['id'];
        if ( isset($this->objects[$key]) )
        {
          $obj = $this->objects[$key];
        }
        else
        {
          $obj = $this->create($kind, $item);
          $this->objects[$key] = $obj;
        }
      }
      // Otherwise just create the object.
      else
      {
        $obj = $this->create($kind, $item);
      }
      $results[] = $obj;
    }
    return $results;
  }
  
  /**
   * undocumented function
   *
   * @return void
   * @author Brux
   */
  protected function getKindFromObject($obj)
  {
    $reflect = new ReflectionClass($obj);
    return Inflector::tableize($reflect->getShortName());
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