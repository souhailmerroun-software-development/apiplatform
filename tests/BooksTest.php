<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Book;
use Hautelook\AliceBundle\PhpUnit\RefreshDatabaseTrait;

class BooksTest extends ApiTestCase
{
    // This trait provided by AliceBundle will take care of refreshing the database content to a known state before each test
    use RefreshDatabaseTrait;

    public function testGetCollection(): void
    {
        // The client implements Symfony HttpClient's `HttpClientInterface`, and the response `ResponseInterface`
        $response = static::createClient()->request('GET', '/api/books');

        $this->assertResponseIsSuccessful();
        // Asserts that the returned content type is JSON-LD (the default)
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        // Asserts that the returned JSON is a superset of this one
        $this->assertJsonContains([
            '@context' => '/api/contexts/Book',
            '@id' => '/api/books',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 100,
            'hydra:view' => [
                '@id' => '/api/books?page=1',
                '@type' => 'hydra:PartialCollectionView',
                'hydra:first' => '/api/books?page=1',
                'hydra:last' => '/api/books?page=4',
                'hydra:next' => '/api/books?page=2',
            ],
        ]);

        // Because test fixtures are automatically loaded between each test, you can assert on them
        $this->assertCount(30, $response->toArray()['hydra:member']);

        // Asserts that the returned JSON is validated by the JSON Schema generated for this resource by API Platform
        // This generated JSON Schema is also used in the OpenAPI spec!
        $this->assertMatchesResourceCollectionJsonSchema(Book::class);
    }

    public function testCreateBook(): void
    {
        $response = static::createClient()->request('POST', '/api/books', ['json' => [
            'title' => 'The Handmaid\'s Tale',
            'description' => 'Brilliantly conceived and executed, this powerful evocation of twenty-first century America gives full rein to Margaret Atwood\'s devastating irony, wit and astute perception.'
        ]]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/Book',
            '@type' => 'Book',
            'title' => 'The Handmaid\'s Tale',
            'description' => 'Brilliantly conceived and executed, this powerful evocation of twenty-first century America gives full rein to Margaret Atwood\'s devastating irony, wit and astute perception.'
        ]);
        $this->assertMatchesRegularExpression('~^/api/books/\d+$~', $response->toArray()['@id']);
        $this->assertMatchesResourceItemJsonSchema(Book::class);
    }

    /*public function testUpdateBook(): void
    {
        $client = static::createClient();
        // findIriBy allows to retrieve the IRI of an item by searching for some of its properties.
        // ISBN 9786644879585 has been generated by Alice when loading test fixtures.
        // Because Alice use a seeded pseudo-random number generator, we're sure that this ISBN will always be generated.
        $iri = $this->findIriBy(Book::class, ['isbn' => '9781344037075']);

        $client->request('PUT', $iri, ['json' => [
            'title' => 'updated title',
        ]]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@id' => $iri,
            'isbn' => '9781344037075',
            'title' => 'updated title',
        ]);
    }*/

    /*public function testDeleteBook(): void
    {
        $client = static::createClient();
        $iri = $this->findIriBy(Book::class, ['isbn' => '9781344037075']);

        $client->request('DELETE', $iri);

        $this->assertResponseStatusCodeSame(204);
        $this->assertNull(
            // Through the container, you can access all your services from the tests, including the ORM, the mailer, remote API clients...
            static::getContainer()->get('doctrine')->getRepository(Book::class)->findOneBy(['isbn' => '9781344037075'])
        );
    }*/
}