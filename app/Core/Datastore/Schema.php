<?php namespace App\Core\Datastore;

/**
 * Schema Class
 *
 * This class is responsible for loading and managing entity field
 * descriptions, data types and indices.
 *
 * @package Silver
 * @author Brux
 * @since  0.1.0
 */
class Schema
{
  
    /**
     * The schema.
     *
     * @var array
     */
    protected $schema = [];

    /**
     * Entities that contain timestamps (updated & created).
     * 
     * @var array
     */
    protected $has_timestamps = [];
  
    /**
     * Loads the schema from our schema file.
     *
     * @param string $path optional path to the schema file
     */
    function __construct($path = null)
    {
        if ( $path !== null )
        {
            include $path;
        }
    }

    /**
     * Describes an entity's fields.
     * 
     * @param  string $entity the entity we're describing
     * @param  array  $fields the fields and their descriptions
     * @return void
     */
    public function describe($kind, array $fields)
    {
        if ( isset($fields['timestamps']) && $fields['timestamps'] === true )
        {
            $this->has_timestamps[] = $kind;
            unset($fields['timestamps']);
            $fields['updated'] = 'datetime';
            $fields['created'] = 'datetime';
        }

        foreach ( $fields as $field => $data )
        {
            if ( is_string($data) )
            {
                $fields[$field] = ['as' => $data];
            }
        }

        if ( isset($this->schema[$kind]) )
        {
            throw new \InvalidArgumentException(sprintf('The schema for "%s" already exists.', $kind));
        }
        else
        {
            $this->schema[$kind] = $fields;
        }
    }

  
    /**
     * Returns the data type for a field.
     *
     * @param string $kind the kind of entity or entity:field
     * @param string $field entity field
     * @return string
     */
    public function getFieldType($kind, $field = null)
    {
        if ( isset($this->schema[$kind]) && isset($this->schema[$kind][$field]) )
        {
            return $this->schema[$kind][$field]['as'];
        }
        else
        {
            return null;
        }
    }
  
    /**
    * Returns TRUE if a field is used as an index.
    *
    * @param string $kind the kind of entity or entity:field
    * @param string $field optional entity field
    * @return bool
    */
    public function isFieldIndexed($kind, $field = null)
    {
        if ( isset($this->schema[$kind]) && isset($this->schema[$kind][$field]) )
        {
            if ( isset($this->schema[$kind][$field]['indexed']) )
            {
                return $this->schema[$kind][$field]['indexed'];
            }
            else
            {
                return false;
            }
        }
        else
        {
            return false;
        }
    }

    /**
     * Returns the entity referenced by reference fields.
     * 
     * @param  string $kind  entity kind
     * @param  string $field field name
     * @return string
     */
    public function getReferencedEntity($kind, $field)
    {
        if ( isset($this->schema[$kind]) && isset($this->schema[$kind][$field]) )
        {
            if ( $this->getFieldType($kind, $field) == 'reference' )
            {
                return $this->schema[$kind][$field]['to'];
            }
            else
            {
                return null;
            }
        }
        else
        {
            return null;
        }
    }

    /**
     * Returns TRUE if an entity uses updated & created fields (timestamps).
     * 
     * @param  string $kind entity kind
     * @return boolean
     */
    public function hasTimestamps($kind)
    {
        return in_array($kind, $this->has_timestamps);
    }
  
}