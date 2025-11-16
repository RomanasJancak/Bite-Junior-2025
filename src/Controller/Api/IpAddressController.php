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

use App\Exception\IpBlackListedException;

#[Route('/api/ip', name: 'api_ip_address_')]
class IpAddressController extends AbstractController
{
    //#[Route('', name: 'index', methods: ['GET'])]
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
            'data' => $ip->getJsonData(),
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

    //#[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, IpAddressService $ipAddressService): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);

        if (empty($payload['address'])) {
            return $this->json(['error' => 'Missing "address"'], 400);
        }
        $ip = $ipAddressService->getOneFresh($payload['address']);
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
      try{
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
          throw new \Exception('IP address is blacklisted', 403);
        }
        return $this->json([
          'id' => $found->getId(),
          'address' => $found->getIp(),
        ]);
      } catch (IpBlacklistedException $e) {
        return $this->json([
            'error' => $e->getMessage(),
        ], 403);
      } catch (\Throwable $e) {
        $statusCode = $e->getCode() === 403 ? 403 : 500;
        $errorMessage = $e->getCode() === 403 ? $e->getMessage() : 'Internal server error';
        if ($this->getParameter('kernel.environment') === 'dev') {
          return $this->json([
              'error' => $errorMessage,
              'message' => $e->getMessage(),
              'file' => $e->getFile(),
              'line' => $e->getLine(),
          ], $statusCode);
        } else {
          return $this->json([
              'error' => $errorMessage,
          ], $statusCode);
        }
      }
    }
    //#[Route('/{id}', name: 'show', methods: ['GET'])]
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
            'jsonData' => $ip->getJsonData(),
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
    
    #[Route('/blacklist_add/{ip}', name: 'ban', methods: ['PATCH'])]
    public function ban(string $ip, EntityManagerInterface $em): JsonResponse
    {
      try {
        $ip = $em->getRepository(IpAddress::class)->findOneBy(['ip' => $ip]);
        if (!$ip) {
            return $this->json(['error' => 'IP address not found'], 404);
        }
        if ($ip->isBlacklisted()) {
            return $this->json(['error' => 'IP address is already blacklisted'], 400);
        }
        $blacklistItem = new \App\Entity\BlackList();
        $blacklistItem->setAddedAt(new \DateTimeImmutable());
        $blacklistItem->setIpAddress($ip);
        $em->persist($blacklistItem);
        $em->flush();
        $em->persist($ip);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'IP address blacklisted successfully',
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
    #[Route('/blacklist_remove/{ip}', name: 'unban', methods: ['PATCH'])]
    public function unban(string $ip, EntityManagerInterface $em): JsonResponse
    {
        $ip = $em->getRepository(IpAddress::class)->findOneBy(['ip' => $ip]);
        if (!$ip) {
            return $this->json(['error' => 'IP address not found'], 404);
        }
        $blacklistItem = $em->getRepository(\App\Entity\BlackList::class)->findOneBy(['ipAddress' => $ip]);
        if ($blacklistItem) {
            $em->remove($blacklistItem);
            $em->flush();
        }else{
          return $this->json(['error' => 'IP address is not blacklisted'], 400);
        }

        return $this->json([
            'success' => true,
            'message' => 'IP address removed from blacklist successfully',
        ]);
    }
    #[Route('/{ips}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $ips, EntityManagerInterface $em): JsonResponse
    {
      try {
        // $ips = explode(',', $ips);
        // if(count($ips) > 10){
        //   return $this->json(['error' => 'Cannot delete more than 10 IP addresses at once'], 400);
        // }
        $ip = $em->getRepository(IpAddress::class)->find($ips);
        if (!$ip) {
            return $this->json(['error' => 'IP address not found'], 404);
        }
        $em->remove($ip); // blacklist will be removed due to cascade
        $em->flush();

        return $this->json([
          'success' => true,
          'message' => 'IP address deleted successfully',
        ], 204);
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
}
