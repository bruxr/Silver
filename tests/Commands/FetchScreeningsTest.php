<?php

class FetchScreeningsTest extends TestCase
{

    public function setUp()
    {
        $scraper = $this->getMockBuilder('App\Scrapers\Abreeza')
                        ->disableOriginalConstructor()
                        ->getMock();
        $scraper->method('fetch')
                ->willReturn(null);
        $scraper->method('getMovies')
                ->willReturn([

                ]);

        $this->container = new \Pimple\Container();
        $this->container['App\Scrapers\Abreeza'] = function($c) use ($scraper) {
            return $scraper;
        };

        $this->ds = $this->getMockBuilder('App\Core\Datastore\Datastore')
                         ->disableOriginalConstructor()
                         ->getMock();
    }

    public function testExecute()
    {
        $cmd = new App\Commands\FetchScreenings(['from' => 'Abreeza']);
        $cmd->execute($this->container, $this->ds);
    }

}