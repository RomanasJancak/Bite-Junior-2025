<?php

namespace App\Controller\Api;

use App\Entity\IpAddress;
use App\Repository\IpAddressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/ip-addresses', name: 'api_ip_address_')]
class IpAddressController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(IpAddressRepository $ipRepository): JsonResponse
    {
        $ips = $ipRepository->findAll();

        $data = array_map(fn(IpAddress $ip) => [
            'id' => $ip->getId(),
            'address' => $ip->getAddress(),
            'created_at' => $ip->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updated_at' => $ip->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ], $ips);

        return $this->json($data);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);

        if (empty($payload['address'])) {
            return $this->json(['error' => 'Missing "address"'], 400);
        }

        $ip = new IpAddress();
        $ip->setAddress($payload['address']);
        $ip->setCreatedAt(new \DateTimeImmutable());

        $em->persist($ip);
        $em->flush();

        return $this->json([
            'id' => $ip->getId(),
            'address' => $ip->getAddress(),
            'createdAt' => $ip->getCreatedAt()?->format('Y-m-d H:i:s'),
        ], 201);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(IpAddress $ip): JsonResponse
    {
        return $this->json([
            'id' => $ip->getId(),
            'address' => $ip->getAddress(),
            'createdAt' => $ip->getCreatedAt()?->format('Y-m-d H:i:s'),
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(IpAddress $ip, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($ip);
        $em->flush();

        return $this->json(null, 204);
    }
}
