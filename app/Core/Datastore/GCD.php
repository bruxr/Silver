<?php namespace App\Core\Datastore;

use Exception;
use Carbon\Carbon;
use Google_Client;
use Google_Service_Datastore as Datastore;
use Google_Service_Datastore_Entity as Entity;
use Google_Service_Datastore_Key as Key;
use Google_Service_Datastore_KeyPathElement as KeyPathElement;
use Google_Service_Datastore_Property as Property;
use Google_Service_Datastore_Mutation as Mutation;
use Google_Service_Datastore_GqlQuery as GqlQuery;
use Google_Service_Datastore_GqlQueryArg as GqlQueryArg;
use Google_Service_Datastore_Value as Value;
use Google_Service_Datastore_CommitRequest as CommitRequest;
use Google_Service_Datastore_LookupRequest as LookupRequest;
use Google_Service_Datastore_RunQueryRequest as RunQueryRequest;

/**
 * Google Cloud Datastore
 * 
 * This class is responsible for turning our CRUD requests into the one
 * the the Google Cloud Datastore accepts.
 *
 * Missing Features:
 * - Transactions
 * - Batching
 * - Doing an update will replace the properties of that entity with
 *   the new properties you passed.
 *
 * @package Silver
 * @author Brux
 * @since 0.1.0
 */
class GCD implements DatastoreInterface
{
  
  /**
   * Dataset object
   *
   * @var object
   */
  protected $dataset;
  
  /**
   * Dataset ID
   *
   * @var string
   */
  protected $dataset_id;
  
  /**
   * Schema Reader
   *
   * @var App\Core\Datastore\Schema
   */
  protected $schema;
  
  /**
   * Constructor
   *
   * @param string $dataset_id the dataset ID
   * @param Google_Client $client reference to the google api client
   * @param App\Core\Datastore\Schema $schema reference to the schema reader
   */
  public function __construct($dataset_id, Google_Client $client, Schema $schema)
  {
    $this->dataset_id = $dataset_id;
    $this->client = $client;
    $this->schema = $schema;
    
    $service = new Datastore($client);
    $this->dataset = $service->datasets;
  }
  
  public function getName()
  {
    return 'gcd';
  }
  
  public function create($kind, array $properties)
  {
    // If we have an id, use it
    if ( isset($properties['id']) )
    {
      $k = [[$kind, $properties['id']]];
      $auto_id = false;
      unset($properties['id']);
    }
    // Otherwise, just use the kind
    else
    {
      $k = [[$kind]];
      $auto_id = true;
    }
    
    // Append ancestors if we have one
    if ( isset($properties['_ancestors']) )
    {
      array_push($properties['_ancestors'], $k[0]);
      $k = $properties['_ancestors'];
    }
    $ent = new Entity();
    $ent->setKey($this->buildKey($k));
    
    // Start build our property map
    $props = [];
    foreach ( $properties as $field => $value )
    {
      // Skip properties with a leading underscore
      if ( substr($field, 0, 1) == '_' )
      {
        continue;
      }
      
      $prop = new Property();
      $type = ucwords($this->schema->getFieldType($kind, $field));
      $indexed = $this->schema->isFieldIndexed($kind, $field);
      
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
      elseif ( $type === '' )
      {
        $type = 'String';
        $indexed = false;
      }
    
      call_user_func(array($prop, "set{$type}Value"), $value);
      $prop->setIndexed($indexed);
    
      $props[$field] = $prop;
    }
    $ent->setProperties($props);
    
    // Build mutation and then commit
    $mutation = new Mutation();
    if ( $auto_id )
    {
      $mutation->setInsertAutoId([$ent]);
    }
    else
    {
      $mutation->setUpsert([$ent]);
    }
    $resp = $this->commit($mutation);
    
    // If the entity needs an auto ID, add it to it's properties
    if ( $auto_id )
    {
      $key = $resp->getMutationResult()->getInsertAutoIdKeys()[0];
      $properties['id'] = (int) $key->getPath()[0]->getId();
    }
    return $properties;
  }
  
  public function find($query, array $args = [])
  {
    
    $kind = $this->extractKindFromGQL($query);
    
    // If this is a ID query, we need a custom query
    if ( isset($args[':id']) )
    {
      $query = 'SELECT * WHERE __key__ = :id';
    }
    
    // Convert from PDO to GQL named arguments
    $query = $this->convertPdoQueryToGQL($query);
    $args = $this->convertPdoArgsToGQL($args);
    
    // Build our array of arguments
    $gql_args = [];
    foreach ( $args as $name => $value )
    {
      $type = ucwords($this->schema->getFieldType($kind, $name));
      // Keys need special treatment
      if ( $name == 'id' )
      {
        $type = 'Key';
        $value = $this->buildKey([[$kind, $value]]);
      }
      // Properly capitalize datetime
      elseif ( $type == 'Datetime' )
      {
        $type = 'DateTime';
      }
      // List & JSON entities are stored as strings
      elseif ( $type == 'List' && $type == 'Entity' )
      {
        $type = 'String';
        $value = json_encode($value);
      }
      elseif ( $type == '' )
      {
        $type = 'String';
      }
      
      $val = new Value();
      call_user_func(array($val, "set{$type}Value"), $value);
      
      $arg = new GqlQueryArg();
      $arg->setName($name);
      $arg->setValue($val);
      $gql_args[] = $arg;
    }
    
    // Build the query object
    $gql_query = new GqlQuery();
    $gql_query->setQueryString($query);
    if ( ! empty($gql_args) )
    {
      $gql_query->setNameArgs($gql_args);
    }
    //$gql_query->setAllowLiteral(true);
    
    $req = new RunQueryRequest();
    $req->setGqlQuery($gql_query);
    $resp = $this->query($req);
    
    $res = [];
    foreach ( $resp->getBatch()->getEntityResults() as $ent )
    {
      $res[] = $this->entityToArray($ent->getEntity(), $kind);
    }
    
    return $res;
  }
  
  public function update($kind, array $properties)
  {
    if ( ! isset($properties['id']) )
    {
      throw new Exception(sprintf('Cannot update %s without entity ID.', $kind));
    }
    $resp = $this->create($kind, $properties);
    return is_array($resp);
  }
  
  public function delete($kind, $id)
  {
    $key = $this->buildKey([[$kind, $id]]);
    $mutation = new Mutation();
    $mutation->setDelete([$key]);
    $resp = $this->commit($mutation);
    return $resp->getMutationResult()->getIndexUpdates() > 0;
  }
  
  // Converts PDO style named arguments to GQL in a SQL query (:arg to @arg)
  protected function convertPdoQueryToGQL($query)
  {
    return preg_replace('/:([a-z0-9]+)/i', '@$1', $query);
  }
  
  // Converts PDO style named arguments to GQL in parameter arrays (:arg to @arg)
  protected function convertPdoArgsToGQL($args)
  {
    $new_args = [];
    foreach ( $args as $k => $v )
    {
      $new_args[substr($k, 1)] = $v;
    }
    return $new_args;
  }
  
  protected function extractKindFromGQL($query)
  {
    $kind = null;
    if ( preg_match('/FROM\s([a-z0-9_-]+)(?:\sWHERE|GROUP|ORDER|LIMIT|OFFSET)?/i', $query, $matches) )
    {
      $kind = $matches[1];
    }
    else
    {
      throw new Exception(sprintf('Failed to find entity kind from GQL "%s".', $query));
    }
    return $kind;
  }
  
  protected function buildKey(array $keys)
  {
    $paths = [];
    foreach ( $keys as $k )
    {
      $kpe = new KeyPathElement();
      $kpe->setKind($k[0]);
      
      if ( count($k) == 2 )
      {
        if ( is_int($k[1]) )
        {
          $kpe->setId($k[1]);
        }
        else
        {
          $kpe->setName($k[1]);
        }
      }
      $paths[] = $kpe;
    }
    $key = new Key();
    $key->setPath($paths);
    return $key;
  }
  
  protected function entityToArray($entity, $kind)
  {
    $item = [];
    
    // Get the ID or name
    $kpe = $entity->getKey()->getPath()[0];
    if ( $kpe->getName() === null )
    {
      $item['id'] = (int) $kpe->getId();
    }
    else
    {
      $item['id'] = $kpe->getName();
    }
    
    // Convert the properties to actual types
    foreach ( $entity->getProperties() as $field => $prop )
    {
      $type = ucwords($this->schema->getFieldType($kind, $field));
      $decode = false;
      if ( $type == 'Datetime' )
      {
        $type = 'DateTime';
      }
      elseif ( $type == 'List' || $type == 'Entity' || $type === '' )
      {
        $type = 'String';
        $decode = true;
      }
      $value = call_user_func(array($prop, "get{$type}Value"));
      
      if ( $type == 'DateTime' )
      {
        $value = Carbon::parse($value);
        $value->tz = date_default_timezone_get();
      }
      elseif ( $decode )
      {
        $value = json_decode($value);
      }
      $item[$field] = $value;
    }
    
    return $item;
  }
  
  protected function commit(Mutation $mutation, array $opts = [])
  {
    $req = new CommitRequest();
    $req->setMode('NON_TRANSACTIONAL');
    $req->setMutation($mutation);
    return $this->dataset->commit($this->dataset_id, $req, $opts);
  }
  
  protected function lookup(LookupRequest $req, array $opts = [])
  {
    return $this->dataset->lookup($this->dataset_id, $req, $opts);
  }
  
  protected function query(RunQueryRequest $req, $opts = [])
  {
    return $this->dataset->runQuery($this->dataset_id, $req, $opts);
  }
  
}