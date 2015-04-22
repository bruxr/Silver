<?php namespace App\Core\Datastore;

use ReflectionClass;
use Carbon\Carbon;
use Doctrine\Common\Inflector\Inflector;

/**
 * Model Class
 *
 * A basic ActiveRecord implementation for the Silver app. Models contain logic
 * that pertains to the app's data, e.g. validation, formatting
 * and conversion.
 *
 * @package Silver
 * @author Brux
 * @since 0.1.0
 */
abstract class Model implements \JsonSerializable
{

  /**
   * Reference to the Datastore class
   *
   * @var App\Core\Datastore\DS
   */
  protected $ds;

  /**
   * The object's persisted properties.
   *
   * @var array
   */
  protected $properties = [];

  /**
   * Contains properties that aren't persisted yet.
   *
   * @var array
   */
  protected $dirty = [];

  /**
   * The kind of this entity. This is only filled when
   * invoking Model::getKind().
   *
   * @var string
   */
  protected $kind = null;

  /**
   * Constructor
   *
   * @param array $properties optional. initial properties of this object
   * @param DS $ds optional. reference to the datastore class
   * @param string $kind optional kind of entity. this is left out if instantiating
   *                     from sub classes.
   */
  function __construct(array $properties = [], Datastore $ds = null)
  {
    $this->ds = $ds;
    $this->hydrate($properties);
  }

  /**
   * Fills the object's properties without marking them as dirty.
   *
   * @param array $props array of properties
   * @return void
   */
  public function hydrate(array $props = [])
  {
    foreach ( $props as $name => $value )
    {
      $this->properties[$name] = $value;
    }
  }

  /**
   * Retrieve a property value.
   *
   * @param string $name property name
   * @return mixed
   */
  public function get($name)
  {
    if ( isset($this->dirty[$name]) )
    {
      return $this->dirty[$name];
    }
    elseif( isset($this->properties[$name]) )
    {
      return $this->properties[$name];
    }
    else
    {
      throw new \Exception(sprintf('%s has no property named %s.', get_class($this), $name));
    }
  }

  /**
   * Sets a property value.
   *
   * @param string $name property name
   * @param mixed $value new property value
   * @return this
   */
  public function set($name, $value)
  {
    $this->dirty[$name] = $value;
    return $this;
  }

  /**
   * Returns TRUE if a property is present.
   *
   * @param string $name property name
   * @return bool
   */
  public function has($name)
  {
    if ( isset($this->properties[$name]) || isset($this->dirty[$name]) )
    {
      return true;
    }
    else
    {
      return false;
    }
  }

  /**
   * Returns TRUE if this is a new record.
   *
   * @return bool
   */
  public function isNew()
  {
    return !isset($this->properties['id']);
  }

  /**
   * Returns TRUE if this model has unsaved properties or if $name is
   * provided, returns TRUE if this model has an unsaved property named $name.
   *
   * @param string $name optional name of property to check
   * @return bool
   */
  public function isDirty($name = null)
  {
    if ( $name === null )
    {
      return !empty($this->dirty);
    }
    else
    {
      return isset($this->dirty[$name]);
    }
  }

  /**
   * Returns an array of properties that hasn't been saved yet.
   *
   * @return array
   */
  public function getDirtyProperties()
  {
    return $this->dirty;
  }

  /**
   * Returns the properties as an array.
   *
   * @return array
   */
  public function getProperties()
  {
    $props = array_merge($this->properties, $this->dirty);
    return $props;
  }

  /**
   * Saves the model to the datastore.
   *
   * @param App\Core\Datastore\DS $ds optional. save the model to this Datastore
   * @return this
   */
  public function save(Datastore $ds = null)
  {
    if ( $this->isDirty() )
    {
      if ( $ds === null && $this->ds !== null )
      {
        $ds = $this->ds;
      }
      elseif ( $ds === null && $this->ds === null )
      {
        throw new \Exception('Cannot save model without a datastore.');
      }
      $ds->put($this);
    }
    return $this;
  }

  /**
   * Clears any unsaved properties, making the entity "clean" again.
   *
   * @return void
   */
  public function refresh()
  {
    $this->dirty = [];
  }

  /**
   * Returns the kind of this entity.
   *
   * @return string
   */
  public function getKind()
  {
    if ( $this->kind === null )
    {
      $reflect = new ReflectionClass($this);
      $this->kind = Inflector::tableize($reflect->getShortName());
    }
    return $this->kind;
  }

  /**
   * Returns the entity where this entity belongs to.
   *
   * @param  string $kind kind of entity this entity belongs to
   * @param  array $opts optional array of options. can contain:
   *                     - foreign_key: the name of the field pointing to the
   *                                    entity we belong to.
   *                     - datastore: the datastore to use
   * @return App\Core\Datastore\Model
   */
  public function belongsTo($kind, array $opts = [])
  {
    $defaults = [
      'foreign_key' => Inflector::tableize($kind) . '_id',
      'datastore' => $this->ds
    ];
    $opts = array_merge($defaults, $opts);
    extract($opts);

    $id = $this->get($foreign_key);
    if ( $id === null )
    {
      return null;
    }
    else
    {
      return $datastore->find($kind, $id);
    }
  }

  /**
   * Returns the entities this entity has/owns.
   *
   * @param  string $kind kind of entities this entity owns
   * @param  array $opts optional array of options. can contain:
   *                     - conditions: more conditions to limit results
   *                     - foreign_key: the name of the field pointing to the
   *                                    entity we belong to.
   *                     - datastore: the datastore to use
   * @return array
   */
  public function hasMany($kind, array $opts = [])
  {
    $defaults = [
      'conditions' => [],
      'foreign_key' => Inflector::tableize($kind) . '_id',
      'datastore' => $this->ds
    ];
    $opts = array_merge($defaults, $opts);
    extract($opts);

    $conditions[$foreign_key] = $this->get('id');

    return $datastore->findCustom($kind, $conditions);
  }

  /**
   * Allows entities to be easily encoded to JSON using json_encode().
   *
   * Take note that this will return dirty and original properties.
   * Make sure to refresh() before encoding if you only want
   * properties that were persisted to the database.
   *
   * @return array
   */
  public function jsonSerialize()
  {
    $props = $this->getProperties();
    foreach ( $props as $field => $value )
    {
      if ( $value instanceof Carbon )
      {
        $props[$field] = $value->toIso8601String();
      }
    }
    return $props;
  }

  /**
   * Catches retrieving of this object's properties.
   *
   * @param string $name property name
   * @return mixed
   */
  public function __get($name)
  {
    return $this->get($name);
  }

  /**
   * Catches setting of this object's properties.
   *
   * @param string $name property name
   * @param mixed $value new property value
   * @return this
   */
  public function __set($name, $value)
  {
    return $this->set($name, $value);
  }

  /**
   * Returns TRUE if a property is present.
   *
   * @param string $name property name
   * @return bool
   */
  public function __isset($name)
  {
    return $this->has($name);
  }

}