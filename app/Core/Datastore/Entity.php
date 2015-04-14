<?php namespace App\Core\Datastore;

use Exception;
use App\Core\Datastore\Schema;
use Carbon\Carbon;
use Google_Service_Datastore_Entity;
use Google_Service_Datastore_KeyPathElement;
use Google_Service_Datastore_Key;
use Google_Service_Datastore_Property;

/**
 * Entity Class
 *
 * This class wraps Datastore Entity objects so entities can easily be
 * created & manipulated.
 *
 * @package Silver
 * @since 0.1.0
 */
class Entity
{
  
  /**
   * The wrapped datastore entity object
   *
   * @var Google_Service_Datastore_Entity
   */
  protected $entity;
  
  /**
   * The kind of this entity
   *
   * @var string
   */
  protected $kind;
  
  /**
   * Reference to the schema reader
   *
   * @var Schema
   */
  protected $schema;
  
  /**
   * Constructor.
   *
   * @param Schema $schema schema reader
   * @param Google_Service_Datastore_Entity|array|string $x entity object, array of ancestors and kind/id or just the entity kind
   */
  function __construct(Schema $schema, $x)
  {
    $this->schema = $schema;
    if ( $x instanceof Google_Service_Datastore_Entity )
    {
      $this->setWrappedObject($x);
      $this->findEntityKind();
    }
    else
    {
      $this->entity = new Google_Service_Datastore_Entity();
      $this->setKey($x);
    }
  }
  
  /**
   * Returns the source Entity object this class is currently wrapping.
   *
   * @return Google_Service_Datastore_Entity
   */
  public function getWrappedObject()
  {
    return $this->entity;
  }
  
  /**
   * Sets the entity key.
   * 
   * @param string|array|Google_Service_Datastore_Key $paths,.. entity keys including any ancestors or the key object itself
   * @return void
   */
  public function setKey($paths)
  {
    
    if ( $paths instanceof Google_Service_Datastore_Key )
    {
      $key = $paths;
    }
    else
    {
      // Automatic key IDs -> 'car'
      if ( is_string($paths) )
      {
        $paths = [$paths];
      }
      // Custom key name -> ['car', 'r8']
      elseif ( ! is_array($paths[0]) )
      {
        $paths = [$paths];
      }
    
      $p = [];
      foreach ( $paths as $i )
      {
        $kpe = new Google_Service_Datastore_KeyPathElement();
        if ( is_string($i) )
        {
          $kpe->setKind($i);
        }
        else
        {
          $kpe->setKind($i[0]);
          $kpe->setName($i[1]);
        }
        $p[] = $kpe;
      }
      $key = new Google_Service_Datastore_Key();
      $key->setPath($p);
    }
    
    $this->entity->setKey($key);
    $this->findEntityKind();
  }
  
  /**
   * Returns the entity's kind.
   *
   * @return string
   */
  public function getKind()
  {
    return $this->kind;
  }
  
  /**
   * Returns the entity's ID. This ID can be the numeric entity ID
   * or a custom entity name.
   *
   * @return int|string
   */
  public function getId()
  {
    $key = $this->getActualKey();
    $id = $key->getId();
    if ( $id !== null )
    {
      return $id;
    }
    else
    {
      $name = $key->getName();
      return $name; // may return the actual name or just null
    }
  }
  
  /**
   * Sets the entity's ID. If $id is numeric it's an ID, if a string
   * then it is the entity's name.
   *
   * @param int|string $id new entity ID or name
   * @return void
   */
  public function setId($id)
  {
    $key = $this->getActualKey();
    if ( is_numeric($id) )
    {
      $key->setId($id);
    }
    else
    {
      $key->setName($id);
    }
  }
  
  /**
   * Set an entity's properties.
   *
   * @param array $props array of properties in field => value format
   * @return void
   */
  public function setProperties(array $props)
  {
    foreach ( $props as $field => $value )
    {
      $this->setProperty($field, $value);
    }
  }
  
  /**
   * Returns the value of a property.
   *
   * @param string $name name of the property
   * @return mixed
   */
  public function getProperty($name)
  {
    $props = $this->entity->getProperties();
    if ( isset($props[$name]) )
    {
      $prop = $props[$name];
      $type = ucwords($this->schema->getFieldType($this->kind, $name));
      
      // Properly capitalize datetime
      if ( $type == 'Datetime' )
      {
        $type = 'DateTime';
      }
      // List and JSON entities are encoded into JSON strings, unknown fields  default to string
      elseif ( $type == 'List' || $type == 'Entity' || empty($type) )
      {
        $type = 'String';
      }
    
      $value = call_user_func(array($prop, "get{$type}Value"));
      
      // Create carbon objects for date & time
      if ( $type == 'DateTime' )
      {
        $value = Carbon::parse($value);
        $value->tz = date_default_timezone_get();
      }
      // Decode List & JSON entities
      elseif ( $type == 'List' || $type == 'Entity' )
      {
        $value = json_decode($value);
      }
      
      return $value;
    }
    else
    {
      throw new Exception(sprintf('Entity has no property named "%s".', $name));
    }
  }
  
  /**
   * Set the value of this entity's property.
   * This respects data types specified in the schema and if it encounters
   * property not defined in the schema, it will coerce it to string.
   *
   * @param string $field field name
   * @param mixed $value property value
   * @return void
   */
  public function setProperty($field, $value)
  {
    
    $prop = new Google_Service_Datastore_Property();
    $type = ucwords($this->schema->getFieldType($this->kind, $field));
    $indexed = $this->schema->isFieldIndexed($this->kind, $field);
    
    // Properly capitalize datetime and then convert to UTC
    if ( $type == 'Datetime' )
    {
      $type = 'DateTime';
      $value->timezone = 'UTC';
      $value = $value->format('Y-m-d\TH:i:s.000\Z');
    }
    // Convert arrays & objects to JSON strings
    elseif ( $type == 'List' || $type == 'Entity' )
    {
      $type = 'String';
      $value = json_encode($value);
    }
    // For unknown properties, use string type and do not index
    elseif ( $type == null )
    {
      $type = 'String';
      $indexed = false;
    }
    
    call_user_func(array($prop, "set{$type}Value"), $value);
    $prop->setIndexed($indexed);
    
    $props = $this->entity->getProperties();
    $props[$field] = $prop;
    $this->entity->setProperties($props);
  }
  
  /**
   * Sets the source entity, entity we will be manipulating.
   *
   * @param Google_Service_Datastore_Entity $entity the source entity
   * @return void
   */
  protected function setWrappedObject($entity)
  {
    $this->entity = $entity;
  }
  
  /**
   * Returns this entity's actual key path element excluding
   * any declared ancestors.
   *
   * @return Google_Service_Datastore_KeyPathElement
   */
  protected function getActualKey()
  {
    $paths = $this->entity->getKey()->getPath();
    return end($paths);
  }
  
  /**
   * Finds the entity's kind by inspecting the key.
   *
   * @return void
   */
  protected function findEntityKind()
  {
    $this->kind = $this->getActualKey()->getKind();
  }
  
}