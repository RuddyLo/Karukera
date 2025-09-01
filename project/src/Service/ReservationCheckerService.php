<?php
namespace App\Service;

use App\Entity\Reservation;
use App\Repository\ReservationRepository;

class ReservationCheckerService
{
    public function __construct(private ReservationRepository $reservationRepository) {}

    public function isAvailable(Reservation $newReservation): bool
    {
        $start = $newReservation->getStartDate();
        $end = $newReservation->getEndDate();
        $appartement = $newReservation->getApartment();

        $existingReservations = $this->reservationRepository->findReservationsBetweenDates(
            $appartement,
            $start,
            $end
        );

        return count($existingReservations) === 0;
    }
}