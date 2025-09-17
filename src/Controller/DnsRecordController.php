<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * DNS Record Controller
 *
 * Author: Oğuz Tokatlı
 */
final class DnsRecordController extends AbstractController
{
    #[Route('/', name: 'app_dns_record')]
    public function index(): Response
    {
        return $this->render('dns_record/index.html.twig', [
            'controller_name' => 'DnsRecordController',
        ]);
    }

    #[Route('/api/dns-records', name: 'api_dns_records', methods: ['GET'])]
    public function apiDnsRecords(Request $request): Response
    {
        $domain = $request->query->get('domain');
        if (!$domain) {
            return $this->json([
                'success' => false,
                'error' => 'Domain parameter is required'
            ], Response::HTTP_BAD_REQUEST);
        }

        $dnsRecords = dns_get_record($domain, DNS_A + DNS_AAAA + DNS_MX);
        if (empty($dnsRecords)) {
            return $this->json([
                'success' => false,
                'error' => 'Domain doesn\'t exist or has no DNS records'
            ], Response::HTTP_BAD_REQUEST);
        }

        usort($dnsRecords, function ($a, $b) {
            if ($a['type'] === 'MX' && $b['type'] === 'MX') {
                return $a['pri'] <=> $b['pri'];
            }
            return 0;
        });

        $normalizedRecords = array_map(function ($record) {
            if ($record['type'] === 'A' || $record['type'] === 'AAAA') {
                return [
                    'type'  => $record['type'],
                    'name'  => $record['host'],
                    'value' => $record['ip'] ?? 'Unknown',
                    'ttl'   => $record['ttl'],
                ];
            } elseif ($record['type'] === 'MX') {
                return [
                    'type'  => $record['type'],
                    'name'  => $record['host'],
                    'value' => $record['target'] ?? 'Unknown',
                    'ttl'   => $record['ttl'],
                ];
            }
            return $record;
        }, $dnsRecords);

        return new JsonResponse([
            'success' => true,
            'records'    => $normalizedRecords
        ], Response::HTTP_OK);
    }
}
