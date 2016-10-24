<?php

namespace AppBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class NotificationControllerTest extends WebTestCase
{
    public function testLoad()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/load');
    }

}
