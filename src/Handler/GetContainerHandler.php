<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\InvalidContainerException;
use App\Exception\InvalidOperationException;
use App\Validator\ContainerNameValidator;
use AzureOss\Storage\Blob\BlobServiceClient;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class GetContainerHandler
{
    public function __construct(
        private readonly BlobServiceClient $blobServiceClient,
        private readonly ContainerNameValidator $containerNameValidator
    ) {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        if (false === $this->containerNameValidator->validate($args['container'] ?? '')->isValid()) {
            throw new InvalidContainerException($this->containerNameValidator->getError());
        }

        switch ($request->getQueryParams()['op'] ?? null) {
            case 'list':
                return $this->listBlobs($request, $response, $args);
            case 'props':
                return $this->getProperties($response, $args);
        }

        throw new InvalidOperationException();
    }

    private function listBlobs(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $blobs = [];
        $client = $this->blobServiceClient->getContainerClient($args['container']);
        $prefix = $request->getQueryParams()['prefix'] ?? '';

        foreach ($client->getBlobs($prefix) as $blob) {
            $blobs[] = $blob;
        }

        $json = json_encode($blobs);
        $body = $response->getBody();
        $body->write($json);

        return $response->withStatus(200)->withHeader('content-type', 'application/json')->withBody($body);
    }

    private function getProperties(ResponseInterface $response, array $args): ResponseInterface
    {
        $properties = $this->blobServiceClient->getContainerClient($args['container'])->getProperties();
        $json = json_encode($properties);

        $body = Utils::streamFor($json);

        return $response->withStatus(200)->withHeader('content-type', 'application/json')->withBody($body);
    }
}
