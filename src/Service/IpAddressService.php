<?php
namespace App\Service;

use App\Repository\IpAddressRepository;
use App\Service\IpRetrievalExternalApi as ApiClient;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\IpAddress;

class IpAddressService
{
  public function __construct(
    private IpAddressRepository $repo,
    private ApiClient $apiClient,
    private EntityManagerInterface $em
  ) 
  {

  }
  public function getIpAddressData(string $ip): array
  {
    $ipAddress = $this->repo->findOneByAddress($ip);

    if ($ipAddress && !$ipAddress->isTooOld()) {
      return [
        'id' => $ipAddress->getId(),
        'address' => $ipAddress->getIp(),
      ];
    }

    $data = $this->apiClient->fetchData($ip);

    if (!$ipAddress) {
      $ipAddress = new IpAddress();
      $ipAddress->setIp($data['ip']);
      $this->em->persist($ipAddress);
    } else {
      $ipAddress->setIp($data['ip']);
    }

    $this->em->flush();

    return [
      'id' => $ipAddress->getId(),
      'address' => $ipAddress->getIp(),
    ];
  }
  public function getAllFresh(): array
  {
    $ips = $this->repo->findAll();
    foreach ($ips as $key => $ip) {
      if ($ip->isTooOld()) {
        $this->em->remove($ip);
        $data = $this->apiClient->fetchData($ip->getAddress());

        $ip->setIp($data['ip']);
        $ip->setUpdatedAt(new \DateTime());

        $this->em->persist($ip);
      }
    }

    return $ips;
  }
}