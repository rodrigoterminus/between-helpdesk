<?php

namespace AppBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EntryControllerTest extends WebTestCase
{
    public function testFiledownload()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/file/download');
    }

}
