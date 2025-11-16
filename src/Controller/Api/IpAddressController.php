<?php

namespace App\Controller\Api;

use App\Entity\IpAddress;
use App\Repository\IpAddressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
//--------------
use App\Service\IpRetrievalExternalApi;
use App\Service\IpAddressService;


#[Route('/api/ip-addresses', name: 'api_ip_address_')]
class IpAddressController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(IpAddressRepository $ipRepository, IpAddressService $ipAddressService): JsonResponse
    {
      try {
        //$ips = $ipRepository->findAll();
        $ips = $ipAddressService->getAllFresh();

        $data = array_map(fn(IpAddress $ip) => [
            'id' => $ip->getId(),
            'address' => $ip->getAddress(),
            'created_at' => $ip->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updated_at' => $ip->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ], $ips);

        return $this->json($data);
      } catch (\Throwable $e) {
        if ($this->getParameter('kernel.environment') === 'dev') {
          return $this->json([
              'error' => 'Internal server error',
              'message' => $e->getMessage(),
              'file' => $e->getFile(),
              'line' => $e->getLine(),
          ], 500);
        }
      }
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
    
    #[Route('/find/{address}', name: 'find', methods: ['GET'])]
    public function find(
      string $address, 
      EntityManagerInterface $em,
      IpRetrievalExternalApi $ApiService,
      IpAddressService $ipAddressService): JsonResponse
    {
        $found = $em->getRepository(IpAddress::class)->findOneBy(['ip' => $address]);
        if(!$found){
          $found = $ipAddressService->getOneFresh($address);
        }
        if($found->isTooOld()){
          $found = $ipAddressService->getOneFresh($address);
          $ip = new IpAddress();
          $ip->setAddress($found['ip']);
          $em->persist($ip);
          $em->flush();
          $found = $ip;
        }
        if ($found->isBlacklisted()){
          return $this->json(['error' => 'IP address is blacklisted'], 403);
        }
        return $this->json([
          'id' => $found->getId(),
          'address' => $found->getIp(),
        ]);
    }
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id,EntityManagerInterface $em): JsonResponse
    {
      try{
        $ip = $em->getRepository(IpAddress::class)->find($id);

        if (!$ip) {
            return $this->json(['error' => 'IP address not found'], 404);
        }
        return $this->json([
            'id' => $ip->getId(),
            'address' => $ip->getAddress(),
            'createdAt' => $ip->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updatedAt' => $ip->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ]);
       } catch (\Throwable $e) {
        if ($this->getParameter('kernel.environment') === 'dev') {
          return $this->json([
              'error' => 'Internal server error',
              'message' => $e->getMessage(),
              'file' => $e->getFile(),
              'line' => $e->getLine(),
          ], 500);
        }
      }
    }
    
    #[Route('/blacklist_add/{id}', name: 'ban', methods: ['PATCH'])]
    public function ban(IpAddress $ip, EntityManagerInterface $em): JsonResponse
    {
        $blacklistItem = new \App\Entity\BlackList();
        $blacklistItem->setAddedAt(new \DateTimeImmutable());
        $blacklistItem->setIpAddress($ip);
        $em->persist($blacklistItem);
        $em->flush();
        $em->persist($ip);
        $em->flush();

        return $this->json([
            'id' => $ip->getId(),
            'address' => $ip->getAddress(),
            'createdAt' => $ip->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updatedAt' => $ip->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ]);
    }
    #[Route('/blacklist_remove/{id}', name: 'unban', methods: ['PATCH'])]
    public function unban(IpAddress $ip, EntityManagerInterface $em): JsonResponse
    {
        $blacklistItem = $em->getRepository(\App\Entity\BlackList::class)->findOneBy(['ipAddress' => $ip]);
        if ($blacklistItem) {
            $em->remove($blacklistItem);
            $em->flush();
        }

        return $this->json([
            'id' => $ip->getId(),
            'address' => $ip->getAddress(),
            'createdAt' => $ip->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updatedAt' => $ip->getUpdatedAt()?->format('Y-m-d H:i:s'),
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
