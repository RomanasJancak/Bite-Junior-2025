<?php

namespace App\Controller;

use App\Entity\IpAddress;
use App\Form\IpAddressType;
use App\Repository\IpAddressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ip/address')]
final class IpAddressController extends AbstractController
{
    #[Route(name: 'app_ip_address_index', methods: ['GET'])]
    public function index(IpAddressRepository $ipAddressRepository): Response
    {
        return $this->render('ip_address/index.html.twig', [
            'ip_addresses' => $ipAddressRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_ip_address_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $ipAddress = new IpAddress();
        $form = $this->createForm(IpAddressType::class, $ipAddress);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($ipAddress);
            $entityManager->flush();

            return $this->redirectToRoute('app_ip_address_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('ip_address/new.html.twig', [
            'ip_address' => $ipAddress,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_ip_address_show', methods: ['GET'])]
    public function show(IpAddress $ipAddress): Response
    {
        return $this->render('ip_address/show.html.twig', [
            'ip_address' => $ipAddress,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_ip_address_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, IpAddress $ipAddress, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(IpAddressType::class, $ipAddress);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_ip_address_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('ip_address/edit.html.twig', [
            'ip_address' => $ipAddress,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_ip_address_delete', methods: ['POST'])]
    public function delete(Request $request, IpAddress $ipAddress, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$ipAddress->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($ipAddress);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_ip_address_index', [], Response::HTTP_SEE_OTHER);
    }
}
