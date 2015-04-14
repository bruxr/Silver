<?php namespace App\Core\Datastore;

use Exception;
use App\Core\Datastore\Entity;
use App\Core\Datastore\Schema;
use Google_Client;
use Google_Service_Datastore as Datastore;
use Google_Service_Datastore_Key as Key;
use Google_Service_Datastore_Mutation as Mutation;
use Google_Service_Datastore_CommitRequest as CommitRequest;
use Google_Service_Datastore_LookupRequest as LookupRequest;

/**
 * Datastore Class
 *
 * Main class for interfacing with the Google Cloud Datastore. This class
 * is responsible for persisting, retrieving and removing entities
 * in the Google Cloud Datastore.
 *
 * @package Silver
 * @author Brux
 */
class DS
{
  
  /**
   * Datastore dataset instance used for querying.
   *
   * @var object
   */
  protected $dataset;
  
  /**
   * Reference to the schema reader.
   *
   * @var Schema
   */
  protected $schema;
  
  /**
   * Constructor. Setups a datastore connection.
   *
   * @param Google_Client $client google client instance
   * @param string $dataset_id  
   */
  public function __construct(Schema $schema, Google_Client $client, $dataset_id)
  {
    $this->connect($client);
    $this->dataset_id = $dataset_id;
    $this->schema = $schema;
  }
  
  /**
   * Fetches entities from the Datastore that match the provided $key.
   *
   * @param string|array $key input key
   * @return array
   */
  public function get($key)
  {
    $temp = new Entity($this->schema, $key);
    $resp = $this->lookup($temp->getWrappedObject()->getKey());
    $entities = [];
    foreach ( $resp->getFound() as $i )
    {
      $i = $i->getEntity();
      $entities[] = new Entity($this->schema, $i);
    }
    return $entities;
  }
  
  /**
   * Creates or updates existing entities in the Datastore.
   *
   * @param Entity $entity input entity
   * @return Entity
   */
  public function put(Entity $entity)
  {
    $mutation = new Mutation();
    $e = $entity->getWrappedObject();
    if ( $entity->getId() === null )
    {
      $mutation->setInsertAutoId([$e]);
    }
    else
    {
      $mutation->setUpsert([$e]);
    }
    $resp = $this->commit($mutation);
    if ( $entity->getId() === null )
    {
      $key = $resp->getMutationResult()->getInsertAutoIdKeys()[0];
      $entity->setKey($key);
    }
    return $entity;
  }
  
  /**
   * Requests entities that match $keys be deleted.
   * Make sure $keys is an array of $keys and not a key array containing
   * an entities ancestors! Doing that may delete not just your entity but
   * it's ancestors as well.
   *
   * @param array $keys array of keys.
   * @return void
   */
  public function delete(array $keys)
  {
    $mutation = new Mutation();
    $deletables = [];
    foreach ( $keys as $key )
    {
      $t = new Entity($this->schema, $key);
      if ( $t->getId() !== null )
      {
        $deletables[] = $t->getWrappedObject()->getKey();
      }
      else
      {
        throw new Exception(sprintf('Cannot delete entity "%s" without ID or name.', $t->getKind()));
      }
    }
    $mutation->setDelete($deletables);
    $x = $this->commit($mutation);
  }
  
  /**
   * Fetches the dataset we need for querying.
   *
   * @return void
   */
  protected function connect($client)
  {
    $service = new Datastore($client);
    $this->dataset = $service->datasets;
  }
  
  /**
   * Sends a commit request with the provided mutation to the Datastore.
   *
   * @param Google_Service_Datastore_Mutation $mutation mutation
   * @param array $opts optional commit options
   * @return Google_Service_Datastore_CommitResponse
   */
  protected function commit(Mutation $mutation, $opts = [])
  {
    $req = new CommitRequest();
    $req->setMode('NON_TRANSACTIONAL');
    $req->setMutation($mutation);
    return $this->dataset->commit($this->dataset_id, $req, $opts);
  }
  
  /**
   * Sends a lookup request using the provided key.
   *
   * @param Google_Service_Datastore_Key $key the key used for lookup
   * @param array $opts optional request options
   * @return 
   */
  protected function lookup(Key $key, $opts = [])
  {
    $req = new LookupRequest();
    $req->setKeys([$key]);
    return $this->dataset->lookup($this->dataset_id, $req, $opts);
  }
  
}