<?php

declare(strict_types=1);

namespace App\Tests\Integration\Handler;

use App\Exception\InvalidOperationException;
use App\Handler\GetStorageHandler;
use App\Tests\Integration\IntegrationTestCase;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;

final class GetStorageHandlerTest extends IntegrationTestCase
{
    public function testListContainers(): void
    {
        // create some containers
        $containers = [];
        $client = $this->getBlobServiceClient();
        for ($i = 0; $i < rand(2, 10); $i++) {
            $name = uniqid('test-');
            $client->getContainerClient($name)->create();
            $this->assertTrue($client->getContainerClient($name)->exists());
            $containers[] = $name;
        }

        // create server request
        $queryParams = ['op' => 'list'];
        $uri = sprintf('http://localhost/?%s', http_build_query($queryParams));
        $request = (new ServerRequest('GET', $uri))->withQueryParams($queryParams);

        /** @var GetStorageHandler $handler */
        $handler = $this->getContainer()->get(GetStorageHandler::class);

        // invoke handler and hold the response
        $response = $handler($request, new Response(), []);

        // assert expected response status code
        $this->assertSame(200, $response->getStatusCode());

        // decode and assert response body
        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);
        $this->assertIsArray($data);

        foreach ($containers as $name) {
            $this->assertArrayHasKey($name, $data);
        }

        // delete the containers
        foreach ($containers as $name) {
            $client->getContainerClient($name)->delete();
        }
    }
}
