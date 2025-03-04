<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Event;
use App\Entity\Reservation;
use Symfony\Component\HttpFoundation\JsonResponse;

final class ReservationController extends AbstractController
{
    #[Route('/reservation', name: 'app_reservation')]
    public function index(): Response
    {
        return $this->render('reservation/index.html.twig', [
            'controller_name' => 'ReservationController',
        ]);
    }

    #[Route('/backreservation', name: 'back_showreservation')]
    public function Showreservation(EntityManagerInterface $entityManager): Response
    {
        $reservations = $entityManager
            ->getRepository(Reservation::class)
            ->findAll();
            
        // Calculate summary statistics
        $totalAmount = 0;
        $totalSeats = 0;
        $pendingCount = 0;
        
        foreach ($reservations as $reservation) {
            $totalAmount += $reservation->getTotalAmount();
            $totalSeats += $reservation->getSeats();
            // Assuming you'll add a status field later
            // if ($reservation->getStatus() === 'pending') {
            //     $pendingCount++;
            // }
        }
            
        return $this->render('backOffice/events/reservation.html.twig', [
            'reservations' => $reservations,
            'totalAmount' => $totalAmount,
            'totalSeats' => $totalSeats,
            'pendingCount' => $pendingCount
        ]);
    }

    #[Route('/reservation/add/{idevent}', name: 'add_reservation', methods: ['POST'])]
    public function addReservation(Request $request, int $idevent, EntityManagerInterface $entityManager): Response
    {
        $event = $entityManager->getRepository(Event::class)->find($idevent);
        
        if (!$event) {
            $this->addFlash('error', 'Event not found.');
            return $this->redirectToRoute('events_list');
        }

        // Get the number of seats requested
        $seats = (int) $request->request->get('seats');
        
        // Validate seats
        if ($seats <= 0) {
            $this->addFlash('error', 'Please select a valid number of seats.');
            return $this->redirectToRoute('events_list');
        }

        // Check if enough seats are available
        if ($seats > $event->getNombreBillets()) {
            $this->addFlash('error', 'Not enough seats available.');
            return $this->redirectToRoute('events_list');
        }

        try {
            // Create new reservation
            $reservation = new Reservation();
            $reservation->setEvent($event);
            $reservation->setSeats($seats);
            $reservation->setDate(new \DateTime());
            $reservation->setTotalAmount($seats * $event->getSeatprice());
            
            // Set user if logged in
            if ($this->getUser()) {
                $reservation->setUser($this->getUser());
            }
            
            // Update available seats in event
            $event->setNombreBillets($event->getNombreBillets() - $seats);
            
            // Persist changes
            $entityManager->persist($reservation);
            $entityManager->persist($event);
            $entityManager->flush();
            
            $this->addFlash('success', 'Reservation created successfully! You reserved ' . $seats . ' seats.');
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'An error occurred while processing your reservation.');
            return $this->redirectToRoute('events_list');
        }
        
        return $this->redirectToRoute('events_list');
    }

    #[Route('/reservation/delete/{id}', name: 'delete_reservation')]
    public function deleteReservation(int $id, EntityManagerInterface $entityManager): Response
    {
        $reservation = $entityManager->getRepository(Reservation::class)->find($id);
        
        if (!$reservation) {
            throw $this->createNotFoundException('Reservation not found');
        }
        
        $entityManager->remove($reservation);
        $entityManager->flush();
        
        $this->addFlash('success', 'Reservation cancelled successfully!');
        
        return $this->redirectToRoute('back_showreservation');
    }

    #[Route('/reservation/edit/{id}', name: 'edit_reservation', methods: ['POST'])]
    public function editReservation(Request $request, int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $reservation = $entityManager->getRepository(Reservation::class)->find($id);
        
        if (!$reservation) {
            return new JsonResponse(['success' => false, 'message' => 'Reservation not found'], 404);
        }
        
        $seats = (int) $request->request->get('seats');
        
        if ($seats <= 0) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid number of seats'], 400);
        }
        
        try {
            // Update reservation with new seats and recalculate total
            $reservation->setSeats($seats);
            $newTotal = $seats * $reservation->getEvent()->getSeatprice();
            $reservation->setTotalAmount($newTotal);
            
            $entityManager->flush();
            
            return new JsonResponse([
                'success' => true,
                'newTotal' => $newTotal,
                'seats' => $seats
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Error updating reservation'], 500);
        }
    }
}
