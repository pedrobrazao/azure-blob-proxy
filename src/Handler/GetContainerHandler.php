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

final readonly class GetContainerHandler
{
    public function __construct(
        private BlobServiceClient $blobServiceClient,
        private ContainerNameValidator $containerNameValidator
    ) {
    }

    /**
     * @param array<string, string> $args
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        if (false === $this->containerNameValidator->validate($args['container'] ?? '')->isValid()) {
            throw new InvalidContainerException($this->containerNameValidator->getError());
        }
        return match ($request->getQueryParams()['op'] ?? null) {
            'list' => $this->listBlobs($request, $response, $args),
            'props' => $this->getProperties($response, $args),
            default => throw new InvalidOperationException(),
        };
    }

    /**
     * @param array<string, string> $args
     */
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

    /**
     * @param array<string, string> $args
     */
    private function getProperties(ResponseInterface $response, array $args): ResponseInterface
    {
        $properties = $this->blobServiceClient->getContainerClient($args['container'])->getProperties();
        $json = json_encode($properties);

        $body = Utils::streamFor($json);

        return $response->withStatus(200)->withHeader('content-type', 'application/json')->withBody($body);
    }
}
