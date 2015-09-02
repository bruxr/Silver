<?php namespace App\Commands;

use App\Core\Bus\Command;
use App\Core\Datastore\Datastore;
use Pimple\Container;

class FetchScreenings extends Command
{

    protected static $MALLS = ['Abreeza', 'GaisanoGrand', 'GaisanoMall', 'Nccc', 'SmCityDavao', 'SmLanang'];

    public function execute(Container $container, Datastore $ds)
    {
        $mall = ( ! empty($this->data['from']) ) ? $this->data['from'] : '';

        if ( empty($mall) )
        {
            throw new \InvalidArgumentException('Cannot fetch screenings without a mall.');
        }
        elseif ( ! in_array($mall, self::$MALLS) )
        {
            throw new \InvalidArgumentException(sprintf('Unknown mall "%s".', $mall));
        }
        else
        {
            $scraper_path = "App\\Scrapers\\$mall";
            $scraper = $container[$scraper_path];
            $movies = $scraper->fetch()->getMovies();
            $this->processMovies($movies);
        }
    }

    public function processMovies($movies)
    {
        
    }

}