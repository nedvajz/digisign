<?php

declare(strict_types=1);

namespace DigitalCz\DigiSign\Endpoint;

use DigitalCz\DigiSign\Endpoint\Traits\CRUDEndpointTrait;
use DigitalCz\DigiSign\Resource\Envelope;
use DigitalCz\DigiSign\Resource\EnvelopeDocument;
use DigitalCz\DigiSign\Resource\EnvelopeTag;
use DigitalCz\DigiSign\Resource\ListResource;
use DigitalCz\DigiSign\Stream\FileResponse;

/**
 * @extends ResourceEndpoint<EnvelopeDocument>
 * @method EnvelopeDocument get(string $id)
 * @method EnvelopeDocument create(array $body)
 * @method EnvelopeDocument update(string $id, array $body)
 */
final class EnvelopeDocumentsEndpoint extends ResourceEndpoint
{
    /** @use CRUDEndpointTrait<EnvelopeDocument> */
    use CRUDEndpointTrait;

    /**
     * @param Envelope|string $envelope
     */
    public function __construct(EnvelopesEndpoint $parent, $envelope)
    {
        parent::__construct($parent, '/{envelope}/documents', EnvelopeDocument::class, ['envelope' => $envelope]);
    }

    /**
     * @param mixed[] $body
     */
    public function positions(array $body): void
    {
        $this->putRequest('/positions', ['json' => $body]);
    }

    public function merge(): EnvelopeDocument
    {
        $response = $this->postRequest("/merge");

        return $this->makeResource($response);
    }

    /**
     * @param mixed[] $query
     */
    public function download(string $id, array $query = []): FileResponse
    {
        return $this->stream(self::METHOD_GET, '/{id}/download', ['id' => $id, 'query' => $query]);
    }

    /**
     * @param mixed[] $query
     * @return ListResource<EnvelopeTag>
     */
    public function tags(string $id, array $query = []): ListResource
    {
        return $this->createListResource(
            $this->getRequest('/{id}/tags', ['id' => $id, 'query' => $query]),
            EnvelopeTag::class
        );
    }
}
