<?php namespace App\Core\Datastore;

interface DatastoreInterface
{
  
  public function create($type, array $properties);
  
  public function find($type, array $params);
  
  public function update($type, array $properties);
  
  public function delete($type, $id);
  
}