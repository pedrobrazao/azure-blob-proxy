<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\InvalidBlobException;
use App\Exception\InvalidContainerException;
use App\Exception\InvalidOperationException;
use App\Validator\BlobNameValidator;
use App\Validator\ContainerNameValidator;
use AzureOss\Storage\Blob\BlobServiceClient;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class GetBlobHandler
{
    public function __construct(
        private readonly BlobServiceClient $blobServiceClient

    ) {}

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $containerVValidator = new ContainerNameValidator($args['container'] ?? '');
        if (false === $containerVValidator->isValid()) {
            throw new InvalidContainerException($containerVValidator->getError());
        }
        
        $blobValidator = new BlobNameValidator($args['blob'] ?? '');
        if (false === $blobValidator->isValid()) {
            throw new InvalidBlobException($blobValidator->getError());
        }

        switch ($request->getQueryParams()['op'] ?? null) {
            case 'content':
                return $this->getContent($response, $args);
                case 'props':
                    return $this->getProperties($response, $args);
        }

        throw new InvalidOperationException();
    }

    private function getContent(ResponseInterface $response, array $args): ResponseInterface
    {
        $client = $this->blobServiceClient->getContainerClient($args['container'])->getBlobClient($args['blob']);
        $blob = $client->downloadStreaming();

        return $response->withStatus(StatusCodeInterface::STATUS_OK)->withHeader('content-type', $blob->properties->contentType)->withBody($blob->content);
    }

    private function getProperties(ResponseInterface $response, array $args): ResponseInterface
    {
        $client = $this->blobServiceClient->getContainerClient($args['container'])->getBlobClient($args['blob']);
        $properties = $client->getProperties();

        $body = $response->getBody();
        $body->write(json_encode($properties));

        return $response->withStatus(StatusCodeInterface::STATUS_OK)->withHeader('content-type', 'application/json')->withBody($body);
    }
}