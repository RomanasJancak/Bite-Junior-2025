<?php
namespace App\Service;

use App\Repository\IpAddressRepository;
use App\Service\IpRetrievalExternalApi as ApiClient;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\IpAddress;

use App\Exception\IpBlackListedException;

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
    $ips = array_filter($ips, fn(IpAddress $ip) => !$ip->isBlacklisted());
    foreach ($ips as $key => $ip) {
      if ($ip->isTooOld()) {
        $this->em->remove($ip);
        $data = $this->apiClient->fetchData($ip->getAddress());

        $ip->setIp($data['ip']);
        $ip->setJsonData($data);
        $this->em->persist($ip);
      }
    }
    $this->em->flush();
    return $ips;
  }
  public function getBlacklistedIps(): array
  {
    $ips = $this->repo->findAll();
    $blacklistedIps = array_filter($ips, fn(IpAddress $ip) => $ip->isBlacklisted());
    return $blacklistedIps;
  }
  public function getBlackListedIpsFromArray(array $ips): array
  {
    $blacklistedIps = [];
    foreach ($ips as $ip) {
      $ipAddress = $this->repo->findOneBy(['ip' => $ip]);
      if ($ipAddress && $ipAddress->isBlacklisted()) {
        $blacklistedIps[] = $ipAddress;
      }
    }
    return $blacklistedIps;
  }
  public function refreshIp(IpAddress $ipAddress): IpAddress
  {
    $data = $this->apiClient->fetchData($ipAddress->getAddress());
    $ipAddress->setIp($data['ip']);
    $ipAddress->setJsonData($data);
    $this->em->persist($ipAddress);
    $this->em->flush();

    return $ipAddress;
  }
  public function refreshIps(array $ipAddresses): array
  {
    foreach ($ipAddresses as $ipAddress) {
      $this->refreshIp($ipAddress);
    }
    return $ipAddresses;
  }
  public function getOneFresh(string $ip): IpAddress
  {
    $ipAddress = $this->repo
    //->findOneByAddress($ip);
    ->findOneBy(['ip' => $ip]);
    if($ipAddress && $ipAddress->isBlacklisted()){
      throw new \IpBlacklistedException($ip);
    }
    if ($ipAddress && !$ipAddress->isTooOld()) {
      return $ipAddress;
    }

    $data = $this->apiClient->fetchData($ip);
    $ipAddress->setIp($data['ip']);
    $ipAddress->setJsonData($data);
    $this->em->persist($ipAddress);
    $this->em->flush();

    return $ipAddress;
  }
}