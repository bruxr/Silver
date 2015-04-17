<?php namespace App\Core\Datastore;

use PDO;
use Carbon\Carbon;

class SqliteDriver implements DriverInterface
{
  
  protected $pdo;
  
  function __construct($path)
  {
    $this->pdo = new PDO(sprintf('sqlite:%s', $path));
  }
  
  public function getName()
  {
    return 'sqlite';
  }
  
  public function create($kind, array $properties)
  {
    $properties = $this->prepProperties($properties);
    $fields = array_keys($properties);
    $values = $this->prepValues(array_values($properties));
    $qs = array_pad([], count($values), '?');
    $query = sprintf('INSERT INTO %s (%s) VALUES (%s)', $kind, implode(', ', $fields), implode(', ', $qs));
    
    $stmt = $this->pdo->prepare($query);
    $stmt->execute($values);
    
    $properties['id'] = $this->pdo->lastInsertId();
    return $properties;
  }
  
  public function find($query, array $args)
  {
    $args = $this->prepProperties($args);
    $stmt = $this->pdo->prepare($query);
    $stmt->execute($args);
    
    $rows = [];
    while ( $r = $stmt->fetch(PDO::FETCH_ASSOC) )
    {
      $rows[] = $r;
    }
    return $rows;
  }
  
  public function update($kind, array $properties)
  {
    if ( ! isset($properties['id']) )
    {
      throw new Exception(sprintf('Cannot update %s without entity ID.', $kind));
    }
    
    $properties = $this->prepValues($properties);
    $props = $properties;
    unset($properties['id']);
    $to_update = [];
    foreach ( $properties as $f => $v )
    {
      $to_update = sprintf('%s=%s', substr($f, 1), $f);
    }
    
    $query = sprintf('UPDATE %s SET %s WHERE id=:id', $kind, implode(', ', $to_update));
    
    $stmt = $this->pdo->prepare($query);
    $stmt->execute($props);
  }
  
  protected function prepProperties($props)
  {
    foreach ( $props as $key => $val )
    {
      // Catch google cloud datastore keys
      if ( $key == ':id' && is_array($val) )
      {
        $key = end($val);
        $key = $val[1];
      }
      
      if ( $val instanceof Carbon )
      {
        $props[$key] = $val->toW3cString();
      }
      elseif ( is_array($val) || is_object($val) )
      {
        $props[$key] = json_encode($val);
      }
    }
    return $props;
  }
  
}