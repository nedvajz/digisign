<?php

declare(strict_types=1);

namespace DigitalCz\DigiSign\Endpoint;

use DigitalCz\DigiSign\DigiSignClient;
use DigitalCz\DigiSign\Exception\EmptyResultException;
use DigitalCz\DigiSign\Exception\ResponseException;
use DigitalCz\DigiSign\Exception\RuntimeException;
use DigitalCz\DigiSign\Resource\BaseResource;
use DigitalCz\DigiSign\Resource\Collection;
use DigitalCz\DigiSign\Resource\ListResource;
use DigitalCz\DigiSign\Resource\ResourceInterface;
use DigitalCz\DigiSign\Stream\FileResponse;
use Psr\Http\Message\ResponseInterface;

/**
 * @template T of ResourceInterface
 */
abstract class ResourceEndpoint implements EndpointInterface
{
    /** @var EndpointInterface  */
    protected $parent;

    /** @var string  */
    private $resourcePath;

    /** @var class-string<T> */
    private $resourceClass;

    /** @var mixed[] */
    private $resourceOptions;

    /**
     * @param class-string<T> $resourceClass
     * @param mixed[] $resourceOptions
     */
    public function __construct(
        EndpointInterface $parent,
        string $resourcePath,
        string $resourceClass = BaseResource::class,
        array $resourceOptions = []
    ) {
        $this->parent = $parent;
        $this->resourcePath = $resourcePath;
        $this->resourceClass = $resourceClass;
        $this->resourceOptions = $resourceOptions;
    }

    /**
     * @param mixed[] $options
     */
    public function request(string $method, string $path = '', array $options = []): ResponseInterface
    {
        $path = $this->preparePath($path);
        $options = $this->prepareOptions($options);

        return $this->parent->request($method, $path, $options);
    }

    /**
     * @param mixed[] $options
     */
    public function stream(string $method, string $path = '', array $options = []): FileResponse
    {
        return new FileResponse($this->request($method, $path, $options));
    }

    /**
     * @return class-string<T>
     */
    protected function getResourceClass(): string
    {
        return $this->resourceClass;
    }

    protected function getResourcePath(): string
    {
        return $this->resourcePath;
    }

    /**
     * @return mixed[]
     */
    protected function getResourceOptions(): array
    {
        return $this->resourceOptions;
    }

    /**
     * @param mixed[] $body
     * @return T&ResourceInterface
     */
    protected function makeCreateRequest(array $body): ResourceInterface
    {
        return $this->makeResource($this->postRequest('', ['json' => $body]));
    }

    /**
     * @param mixed[] $body
     */
    protected function makeUpdateRequest(string $id, array $body): ResourceInterface
    {
        return $this->makeResource($this->putRequest('/{id}', ['id' => $id, 'json' => $body]));
    }

    protected function makeDeleteRequest(string $id): void
    {
        $this->deleteRequest('/{id}', ['id' => $id]);
    }

    protected function makeGetRequest(string $id): ResourceInterface
    {
        return $this->makeResource($this->getRequest('/{id}', ['id' => $id]));
    }

    /**
     * @param mixed[] $query
     * @return ListResource<T>
     */
    protected function makeListRequest(array $query = []): ListResource
    {
        return $this->makeListResource($this->getRequest('', ['query' => $query]));
    }

    /**
     * @param mixed[] $options
     */
    protected function getRequest(string $function = '', array $options = []): ResponseInterface
    {
        return $this->request(self::METHOD_GET, $function, $options);
    }

    /**
     * @param mixed[] $options
     */
    protected function postRequest(string $function = '', array $options = []): ResponseInterface
    {
        return $this->request(self::METHOD_POST, $function, $options);
    }

    /**
     * @param mixed[] $options
     */
    protected function putRequest(string $function = '', array $options = []): ResponseInterface
    {
        return $this->request(self::METHOD_PUT, $function, $options);
    }

    /**
     * @param mixed[] $options
     */
    protected function patchRequest(string $function = '', array $options = []): ResponseInterface
    {
        return $this->request(self::METHOD_PATCH, $function, $options);
    }

    /**
     * @param mixed[] $options
     */
    protected function deleteRequest(string $function = '', array $options = []): ResponseInterface
    {
        return $this->request(self::METHOD_DELETE, $function, $options);
    }

    /**
     * @return mixed[]
     */
    protected function parseResponse(ResponseInterface $response): array
    {
        try {
            $result = DigiSignClient::parseResponse($response);
        } catch (RuntimeException $e) {
            throw new ResponseException($response, $e->getMessage(), null, $e);
        }

        if ($result === null) {
            throw new EmptyResultException();
        }

        return $result;
    }

    /**
     * @return T
     */
    protected function makeResource(ResponseInterface $response): ResourceInterface
    {
        return $this->createResource($response, $this->getResourceClass());
    }

    /**
     * @param class-string<U> $resourceClass
     * @return U
     * @template U of ResourceInterface
     */
    protected function createResource(
        ResponseInterface $response,
        string $resourceClass = BaseResource::class
    ): ResourceInterface {
        $resource = new $resourceClass($this->parseResponse($response));
        $resource->setResponse($response);

        return $resource;
    }

    /**
     * @return ListResource<T>
     */
    protected function makeListResource(ResponseInterface $response): ListResource
    {
        return $this->createListResource($response, $this->getResourceClass());
    }

    /**
     * @param class-string<U> $resourceClass
     * @return ListResource<U>
     * @template U of ResourceInterface
     */
    protected function createListResource(
        ResponseInterface $response,
        string $resourceClass = BaseResource::class
    ): ListResource {
        $resource = new ListResource($this->parseResponse($response), $resourceClass);
        $resource->setResponse($response);

        return $resource;
    }

    /**
     * @param class-string<U> $resourceClass
     * @return Collection<U>
     * @template U of ResourceInterface
     */
    protected function createCollectionResource(
        ResponseInterface $response,
        string $resourceClass = BaseResource::class
    ): Collection {
        $collection = new Collection($this->parseResponse($response), $resourceClass);
        $collection->setResponse($response);

        return $collection;
    }

    /**
     * @param mixed[] $options
     * @return mixed[]
     */
    protected function prepareOptions(array $options): array
    {
        return array_merge($this->getResourceOptions(), $options);
    }

    protected function preparePath(string $path): string
    {
        return $this->getResourcePath() . $path;
    }
}
