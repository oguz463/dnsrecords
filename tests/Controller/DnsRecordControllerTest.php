<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * DNS Record Controller
 *
 * Author: Oğuz Tokatlı
 */
final class DnsRecordControllerTest extends WebTestCase
{
    public function testApiDnsRecordsNoDomainParam()
    {
        $client = DnsRecordControllerTest::createClient();
        $client->request('GET', '/api/dns-records');

        $this->assertResponseStatusCodeSame(400);

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($response['success']);
        $this->assertEquals('Domain parameter is required', $response['error']);
    }

    public function testApiDnsRecordsInvalidDomain()
    {
        $client = static::createClient();
        // Using a non-existent domain to simulate empty dns_get_record
        $fakeDomain = 'nonexistentdomainforsure1234567890.com';
        $client->request('GET', '/api/dns-records?domain=' . urlencode($fakeDomain));

        $this->assertResponseStatusCodeSame(400);

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($response['success']);
        $this->assertStringContainsString('no DNS records', $response['error']);
    }

    public function testApiDnsRecordsValidDomain()
    {
        $client = static::createClient();
        $client->request('GET', '/api/dns-records?domain=example.com');

        $this->assertResponseIsSuccessful();
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertTrue($response['success']);
        $this->assertIsArray($response['records']);
        $this->assertNotEmpty($response['records']);

        // Check required keys in the first record
        $record = $response['records'][0];
        $this->assertArrayHasKey('type', $record);
        $this->assertArrayHasKey('name', $record);
        $this->assertArrayHasKey('value', $record);
        $this->assertArrayHasKey('ttl', $record);
    }
}
