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
     * The identity map.
     *
     * @var App\Core\Datastore\IdentityMap
     */
    protected $map;

    /**
     * Constructor. Setups a datastore connection.
     *
     * @param object $driver the database driver
     * @param Schema $schema optional schema describing the database
     */
    public function __construct($driver, Schema $schema = null)
    {
        $this->driver = $driver;
        $this->schema = $schema;
        $this->map = new IdentityMap();
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
        $entity = $this->buildEntity($kind, $properties);
        $this->map->manage($entity);
        return $entity;
    }

    /**
     * Returns the entity that has the provided ID.
     * 
     * @param  string $kind entity kind
     * @param  mixed $id the ID of the entity we want
     * @return App\Core\Datastore\Model
     */
    public function find($kind, $id)
    {
        $entity = null;
        if ( $this->map->has($kind, $id) )
        {
            $entity = $this->map->get($kind, $id);
        }
        else
        {
            $entity = $this->findBy($kind, 'id', $id);
        }
        return $entity;
    }

    /**
     * Returns the very first entity that has a field with the provided value.
     * 
     * @param  string $kind entity kind
     * @param  string $field field to be matched
     * @param  mixed $value the value we want
     * @param  string $op optional. the operator to be used, defaults to '='
     * @return App\Core\Datastore\Model
     */
    public function findBy($kind, $field, $value, $op = '=')
    {
        $results = (new Query($kind, $this))->where($field, $op, $value)->limit(1)->get();
        $entity = null;
        if ( ! empty($results) )
        {
            $entity = $this->conditionallyBuildEntity($kind, $results[0]);
        }
        return $entity;
    }

    /**
     * Returns all entities of a specific kind.
     * 
     * @param  string $kind entity kind
     * @return array
     */
    public function findAll($kind)
    {
        $results = (new Query($kind, $this))->get();
        return $this->buildEntities($kind, $results);
    }

    /**
     * Returns all entities of a specific kind that has a field that
     * matches the provided value.
     * 
     * @param  string $kind entity kind
     * @param  string $field field name
     * @param  mixed $value the value we want
     * @param  string $op optional. the operator to be used, defaults to '='
     * @return array
     */
    public function findAllBy($kind, $field, $value, $op = '=')
    {
        $results = (new Query($kind, $this))->where($field, $op, $value)->get();
        return $this->buildEntities($results);
    }

    /**
     * Queries the database using $query with the provided $params.
     * Take note that this doesn't return entities but an array of rows.
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
        return $this->driver->find($query, $params);
    }

    /**
     * Persists objects to the database.
     *
     * @param App\Core\Datastore\Model $entity item to be saved
     * @return App\Core\Datastore\Model
     */
    public function put(Model $entity)
    {
        $kind = $entity->getKind();
        $props = $entity->getProperties();
        if ( isset($entity->id) )
        {
            $new_props = $this->driver->update($kind, $props);
            $new_entity = false;
        }
        else
        {
            $new_props = $this->driver->create($kind, $props);
            $new_entity = true;
        }
        $entity->hydrate($new_props);

        if ( $new_entity )
        {
            $this->map->updateKey($entity);
        }

        return $entity;
    }

    /**
     * Removes objects from the database.
     *
     * @param App\Core\Datastore\Model $entity item to be deleted
     * @return void
     */
    public function delete(Model $entity)
    {
        $this->driver->delete($entity->getKind(), $entity->id);
        $this->map->forget($entity);
    }

    /**
     * Builds an entity if it doesn't exist yet in the identity map,
     * otherwise reuse the stored entity.
     * 
     * @param string $kind entity kind
     * @param string $props entity properties
     * @return App\Core\Datastore\Model
     */
    protected function conditionallyBuildEntity($kind, array $props = [])
    {
        if ( isset($props['id']) && $this->map->has($kind, $props['id']) )
        {
            $entity = $this->get($kind, $props['id']);
        }
        else
        {
            $entity = $this->buildEntity($kind, $props);
            $this->map->manage($entity);
        }
        return $entity;
    }

    /**
     * Instantiates a new Active Record object with the provided
     * kind and properties.
     * 
     * @param  string $kind entity kind
     * @param  array $props optional. the entity's properties
     * @return App\Core\Datastore\Model
     */
    protected function buildEntity($kind, array $props = [])
    {
        // If it is not a fully qualified name of a class, make it one
        if ( strpos($kind, '\\') === false )
        {
            $class = sprintf('App\Models\%s', Inflector::classify($kind));
        }

        return new $class($props, $this);
    }

    /**
     * Builds active record objects from an array of results from the database.
     *
     * @param string $kind the kind of object to build
     * @param array $items array of rows/entities from the database.
     * @return array
     */
    protected function buildEntities($kind, array $items)
    {
        $results = [];
        foreach ( $items as $item )
        {
          $results[] = $this->conditionallyBuildEntity($kind, $item);
        }
        return $results;
    }

}