<?php namespace App\Core\Datastore\Drivers;

interface DriverInterface
{
  
  public function getName();
  
  public function create($type, array $properties);
  
  public function find($type, array $params);
  
  public function update($type, array $properties);
  
  public function delete($type, $id);
  
}