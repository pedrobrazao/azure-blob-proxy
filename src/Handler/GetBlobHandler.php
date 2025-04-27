<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\InvalidBlobException;
use App\Exception\InvalidContainerException;
use App\Exception\InvalidOperationException;
use App\Validator\BlobNameValidator;
use App\Validator\ContainerNameValidator;
use AzureOss\Storage\Blob\BlobServiceClient;
use AzureOss\Storage\Blob\Sas\BlobSasBuilder;
use DateTimeImmutable;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class GetBlobHandler
{
    public function __construct(
        private readonly BlobServiceClient $blobServiceClient,
        private readonly ContainerNameValidator $containerNameValidator,
        private readonly BlobNameValidator $blobNameValidator

    ) {}

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        if (false === $this->containerNameValidator->validate($args['container'])->isValid()) {
            throw new InvalidContainerException($this->containerNameValidator->getError());
        }
        
        if (false === $this->blobNameValidator->validate($args['blob'])->isValid()) {
            throw new InvalidBlobException($this->blobNameValidator->getError());
        }

        switch ($request->getQueryParams()['op'] ?? null) {
            case 'content':
                return $this->getContent($response, $args);
                case 'props':
                    return $this->getProperties($response, $args);
                    case 'tags':
                        return $this->getTags($response, $args);
                    case 'sas':
                        return $this->getSasUrl($request, $response, $args);
        }

        throw new InvalidOperationException();
    }

    private function getContent(ResponseInterface $response, array $args): ResponseInterface
    {
        $client = $this->blobServiceClient->getContainerClient($args['container'])->getBlobClient($args['blob']);
        $blob = $client->downloadStreaming();
        $body = $blob->content;
        $contentType = $blob->properties->contentType;

        return $response->withStatus(200)->withHeader('content-type', $contentType)->withBody($body);
    }

    private function getProperties(ResponseInterface $response, array $args): ResponseInterface
    {
        $client = $this->blobServiceClient->getContainerClient($args['container'])->getBlobClient($args['blob']);
        $properties = $client->getProperties();
        $json = json_encode($properties);

        $body = Utils::streamFor($json);;

        return $response->withStatus(200)->withHeader('content-type', 'application/json')->withBody($body);
    }

    private function getTags(ResponseInterface $response, array $args): ResponseInterface
    {
        $client = $this->blobServiceClient->getContainerClient($args['container'])->getBlobClient($args['blob']);
        $tags = $client->getTags();
        $json = json_encode(($tags));

        $body = Utils::streamFor($json);;

        return $response->withStatus(200)->withHeader('content-type', 'application/json')->withBody($body);
    }

    private function getSasUrl(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $ttl = (int) $request->getQueryParams()['ttl'] ?? 3600;
        $perms = $request->getQueryParams()['perms'] ?? 'r';

        $builder = BlobSasBuilder::new()->setContainerName($args['container'])
        ->setBlobName($args['blob'])
        ->setPermissions($perms)
        ->setExpiresOn(new DateTimeImmutable(date('c', time() + $ttl)));

        $client = $this->blobServiceClient->getContainerClient($args['container'])->getBlobClient($args['blob']);
        $uri = $client->generateSasUri($builder);

        $body = Utils::streamFor($uri);

        return $response->withStatus(200)->withHeader('content-type', 'text/plain')->withBody($body);
    }
}