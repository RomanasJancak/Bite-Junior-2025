<?php


namespace App\Controller\Api;

use OpenApi\Annotations as OA;

use App\Entity\IpAddress;
use App\Entity\BlackList;
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
    /**
     * @OA\Get(
     *      path="/api/ip/find/{address}",
     *      summary="Find IP information",
     *      description="Retrieve details for one or multiple IP addresses (comma-separated).",
     *      tags={"IP"},
     *      @OA\Parameter(
     *          name="address",
     *          in="path",
     *          required=true,
     *          description="Comma-separated list of IPs to search",
     *          @OA\Schema(type="string", example="8.8.8.8,1.1.1.1")
     *      ),
     *      @OA\Response(
     *         response=200,
     *         description="Successfully retrieved IP information",
     *         @OA\JsonContent(
     *           type="object",
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="ip_data", type="array", @OA\Items(type="object"))
     *         )
     *      ),
     *    @OA\Response(response=400, description="Too many IPs "),
     *    @OA\Response(response=500, description="Internal server error")
     * )
     */
    #[Route('/find/{address}', name: 'find', methods: ['GET'])]
    public function find(string $address, EntityManagerInterface $em,IpAddressService $ipAddressService): JsonResponse
    {
      try{
        $ips = explode(',', $address);
        if(count($ips) > (int)$_ENV['IP_MAX_FIND_QUNANTITY']){
          return $this->json(['error' => 'Cannot find more than ' . $_ENV['IP_MAX_FIND_QUNANTITY'] . ' IP addresses at once'], 400);
        }
        $message = [];

        foreach($ips as $address){
          if(filter_var($address, FILTER_VALIDATE_IP) === false) {
            $message[] = "IP address '$address' is not valid.";
            continue;
          }
          $found = $em->getRepository(IpAddress::class)->findOneBy(['ip' => $address]);
          if(!$found){
            $found = $ipAddressService->getOneFresh($address);
            $em->persist($found);
            $em->flush();
            $message[] = $found->getJsonData();
          }else{
            if($found->isBlacklisted()){
              $message[] = "IP address '$address' is blacklisted.";
            }else if($found->isTooOld()){
              $found = $ipAddressService->getOneFresh($address);
              $em->persist($found);
              $em->flush();
              $message[] = $found->getJsonData();
            }else{
              $message[] = $found->getJsonData();
            }
          }          
        }
        return $this->json([
          'success' => true,
          'ip_data' => $message,
        ]); 
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
    /**
     * @OA\Patch(
     *    path="/api/ip/blacklist/{address}",
     *    summary="Add one or more IP addresses to blacklist",
     *    tags={"IP"},
     *    @OA\Parameter(
     *      name="address",
     *      in="path",
     *      required=true,
     *      description="Comma-separated list of IPs to blacklist",
     *      @OA\Schema(type="string", example="8.8.8.8,1.1.1.1")
     *    ),
     *    @OA\Response(response=200, description="IPs blacklisted successfully"),
     *    @OA\Response(response=400, description="Too many IPs or invalid IP"),
     *    @OA\Response(response=500, description="Internal server error")
     * )
     */
    #[Route('/blacklist/{address}', name: 'ban', methods: ['PATCH'])]
    public function ban(string $address, EntityManagerInterface $em): JsonResponse
    {
      try {
        $ips = explode(',', $address);
        if(count($ips) > (int)$_ENV['IP_MAX_BAN_QUANTITY']){
          return $this->json(['error' => 'Cannot blacklist more than ' . $_ENV['IP_MAX_BAN_QUANTITY'] . ' IP addresses at once'], 400);
        }
        $message = [];
        foreach($ips as $ip){
          if(filter_var($ip, FILTER_VALIDATE_IP) === false) {
            $message[] = "IP address '$ip' is not valid.";
            continue;
          }
          $ip = $em->getRepository(IpAddress::class)->findOneBy(['ip' => $ip]);
          if(!$ip){
            $message[] = "IP address '$ip' not found.";
            continue;
          }
          if($ip->isBlacklisted()){
            $message[] = "IP address '$ip' is already blacklisted.";
            continue;
          }
          $blacklistItem = new BlackList();
          $blacklistItem->setAddedAt(new \DateTimeImmutable());
          $blacklistItem->setIpAddress($ip);
          $em->persist($blacklistItem);
          $em->flush();
          $message[] = "IP address '$ip' added to blacklist successfully.";
        }
        return $this->json([
            'success' => true,
            'messages' => $message,
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
    /**
     * @OA\delete(
     *     path="/api/ip/blacklist/{ip}",
     *     summary="Remove one or more IPs from the blacklist",
     *     tags={"IP"},
     *     @OA\Parameter(
     *         name="ip",
     *         in="path",
     *         required=true,
     *         description="Comma-separated list of IPs to unban",
     *         @OA\Schema(type="string", example="8.8.8.8,1.1.1.1")
     *     ),
     *     @OA\Response(response=200, description="IPs removed from blacklist successfully"),
     *     @OA\Response(response=400, description="Invalid IP or too many requests"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    #[Route('/blacklist/{ip}', name: 'unban', methods: ['DELETE'])]
    public function unban(string $ip, EntityManagerInterface $em): JsonResponse
    {
      try {
        $ips = explode(',', $ip);
        if(count($ips) > (int)$_ENV['IP_MAX_UNBAN_QUANTITY']){
          return $this->json(['error' => 'Cannot unban more than ' . $_ENV['IP_MAX_UNBAN_QUANTITY'] . ' IP addresses at once'], 400);
        }
        $message = [];
        foreach($ips as $ip){
          if(filter_var($ip, FILTER_VALIDATE_IP) === false) {
            $message[] = "IP address '$ip' is not valid.";
            continue;
          }
          $ipEntity = $em->getRepository(IpAddress::class)->findOneBy(['ip' => $ip]);
          if(!$ipEntity){
            $message[] = "IP address '$ip' not found.";
            continue;
          }
          $blacklistItem = $em->getRepository(BlackList::class)->findOneBy(['ipAddress' => $ipEntity]);
          if($blacklistItem){
            $em->remove($blacklistItem);
            $em->flush();
            $message[] = "IP address '$ip' removed from blacklist successfully.";
          }else{
            $message[] = "IP address '$ip' is not blacklisted.";
          }
        }
        return $this->json([
            'success' => true,
            'messages' => $message,
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
    /**
     * @OA\Delete(
     *     path="/api/ip/{ips}",
     *     summary="Delete one or multiple IP records",
     *     tags={"IP"},
     *     @OA\Parameter(
     *         name="ips",
     *         in="path",
     *         required=true,
     *         description="Comma-separated list of IPs to delete",
     *         @OA\Schema(type="string", example="8.8.8.8,1.1.1.1")
     *     ),
     *     @OA\Response(response=200, description="IP records deleted successfully"),
     *     @OA\Response(response=400, description="Invalid IP or too many deletions"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    #[Route('/{ips}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $ips, EntityManagerInterface $em): JsonResponse
    {
      try {
        $ips = explode(',', $ips);
        if(count($ips) > (int)$_ENV['IP_MAX_DELETE_QUANTITY']){
          return $this->json(['error' => 'Cannot delete more than 10 IP addresses at once'], 400);
        }
        $message = [];
        foreach($ips as $ip){
          if(filter_var($ip, FILTER_VALIDATE_IP) === false) {
            $message[] = "IP address '$ip' is not valid.";
            continue;
          }
          $ip = $em->getRepository(IpAddress::class)->findOneBy(['ip' => $ip]);
          if ($ip) {
            $message[] = "Deleted IP address: " . $ip->getIp();
            $em->remove($ip); // blacklist ir taip dings / cascade
            $em->flush();
          }else{
            $message[] = "IP address '$ip' not found.";
          }
        }
        $em->flush();
        return $this->json([
          'success' => true,
          'message' => $message,
        ], 200);
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
