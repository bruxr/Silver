<?php namespace App\Core\Datastore;

trait Sluggable {

    public function slugify($source)
    {
        if ( $this->ds === null )
        {
            throw new \Exception('Cannot create slugs without an initial datastore.');
        }

        $this->on("set_$source", function($input) {
            if ( $this->isNew() )
            {
                $base =
                $slug = $this->createSlug($input);
                $i = 2;
                $existing_slugs = $this->ds->listIds($this->getKind());
                while ( in_array($slug, $existing_slugs) )
                {
                    $slug = $base . $i;
                    $i++;
                }
                $this->set('id', $slug);
            }
        });
    }

    /**
     * Creates a URL friendly version of a string.
     * 
     * @param  string $string input string.
     * @return string
     * @link   http://stackoverflow.com/a/2103815 Thanks Alix!
     */
    private function createSlug($string)
    {
        return strtolower(trim(preg_replace('~[^0-9a-z]+~i', '-', html_entity_decode(preg_replace('~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i', '$1', htmlentities($string, ENT_QUOTES, 'UTF-8')), ENT_QUOTES, 'UTF-8')), '-'));
    }

}