<?php namespace App\Core\Datastore;

use Symfony\Component\Yaml\Parser;

/**
 * Schema Class
 *
 * Handles the schema field and accessing field information and indices
 * declared inside it.
 *
 * Take note that most methods support a entity:field parameter, where methods
 * can be called with the 2 entity & field parameters combined into one. e.g:
 * getFieldType('car:engine') is the same as getFieldType('car', 'engine')
 *
 * @package Silver
 * @author Brux
 */
class Schema
{
  
  /**
   * The schema.
   *
   * @var array
   */
  protected $schema;
  
  /**
   * Loads the schema from our schema file.
   *
   * @param Parser $parser YAML Parser
   * @param string $path path to the schema file
   */
  function __construct(Parser $parser, $path)
  {
    $this->schema = $parser->parse(file_get_contents($path));
  }
  
  /**
   * Returns the data type for a field.
   *
   * @param string $kind the kind of entity or entity:field
   * @param string $field optional entity field
   * @return string
   */
  public function getFieldType($kind, $field = null)
  {
    if ( $field === null )
    {
      list($kind, $field) = explode(':', $kind);
    }
    
    if ( isset($this->schema[$kind]) && isset($this->schema[$kind][$field]) )
    {
      if ( is_array($this->schema[$kind][$field]) )
      {
        return $this->schema[$kind][$field]['type'];
      }
      else
      {
        return $this->schema[$kind][$field];
      }
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
    if ( $field === null )
    {
      list($kind, $field) = explode(':', $kind);
    }
    
    if ( isset($this->schema[$kind]) && isset($this->schema[$kind][$field]) )
    {
      if ( is_array($this->schema[$kind][$field]) )
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
   * Adds a schema for an entity kind.
   *
   * @param string $kind entity kind
   * @param array $schema entity schema
   * @return void
   */
  public function setSchema($kind, array $schema)
  {
    $this->schema[$kind] = $schema;
  }
  
}