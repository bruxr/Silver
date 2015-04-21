<?php namespace App\Core\Datastore;

class IdentityMap
{

    protected $map = [];

    public function has($kind, $id)
    {
        $key = $this->generateKeyFromKindAndId($kind, $id);
        return isset($this->map[$key]);
    }

    public function manage(Model $entity)
    {
        $key = $this->generateKeyFromEntity($entity);
        if ( isset($this->map[$key]) )
        {
            throw new \Exception(sprintf('Entity key "%s" is already in use.', $key));
        }
        else
        {
            $this->map[$key] = $entity;
        }
    }

    public function get($kind, $id)
    {
        $key = $this->generateKeyFromKindAndId($kind, $id);
        if ( isset($this->map[$key]) )
        {
            
            return $this->map[$key];
        }
        else
        {
            return null;
        }
    }

    public function forget(Model $entity)
    {
        $key = $this->generateKeyFromEntity($entity);
        if ( isset($this->map[$key]) )
        {
            unset($this->map[$key]);
        }
    }

    public function updateKey(Model $entity)
    {
        if ( ! $entity->has('id') )
        {
            throw new \Exception('Updating an entity requires the entity has an ID.');
        }

        $kind = $entity->getKind();
        $old_key = $this->generateKeyFromHash($kind, $entity);
        if ( ! isset($this->map[$old_key]) )
        {
            throw new \Exception(sprintf('Cannot upgrade entity with key "%s", old key doesn\'t exist.', $old_key));
        }
        else
        {
            unset($this->map[$old_key]);
            $this->manage($entity);
        }
    }

    protected function generateKeyFromKindAndId($kind, $id)
    {
        return $kind .'_'. $id;
    }

    protected function generateKeyFromHash($kind, $obj)
    {
        return $kind .'_'. spl_object_hash($obj);
    }

    protected function generateKeyFromEntity(Model $entity)
    {
        $kind = $entity->getKind();
        if ( isset($entity->id) )
        {
            return $this->generateKeyFromKindAndId($kind, $entity->id);
        }
        else
        {
            return $this->generateKeyFromHash($kind, $entity);
        }
    }

}