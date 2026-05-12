<?php

namespace App\Service;

use App\Entity\Apartment;
use App\Entity\Reservation;
use App\Repository\MinimumStayPeriodRepository;
use App\Repository\ReservationRepository;

class ReservationValidationService
{
    public function __construct(
        private ReservationRepository $reservationRepository,
        private MinimumStayPeriodRepository $minimumStayPeriodRepository,
    ) {
    }

    /**
     * Check if reservation is available (no conflicts)
     */
    public function isAvailable(Reservation $reservation): bool
    {
        $existingReservations = $this->reservationRepository->createQueryBuilder('r')
            ->where('r.apartment = :apartment')
            ->andWhere('r.startDate < :endDate')
            ->andWhere('r.endDate > :startDate')
            ->andWhere('r.status NOT IN (:canceledStatus)')
            ->setParameter('apartment', $reservation->getApartment())
            ->setParameter('startDate', $reservation->getStartDate())
            ->setParameter('endDate', $reservation->getEndDate())
            ->setParameter('canceledStatus', ['canceled'])
            ->getQuery()
            ->getResult();

        return \count($existingReservations) === 0;
    }

    /**
     * Check if reservation meets minimum stay requirement
     */
    public function meetsMinimumStay(Reservation $reservation): bool
    {
        $startDate = $reservation->getStartDate();
        $endDate = $reservation->getEndDate();
        $apartment = $reservation->getApartment();

        // Calculate number of days
        $days = (int)$startDate->diff($endDate)->format('%a');

        // Find minimum stay period for this date range
        $minimumStayPeriod = $this->minimumStayPeriodRepository
            ->createQueryBuilder('m')
            ->where('m.apartment = :apartment')
            ->andWhere('m.startDate <= :startDate')
            ->andWhere('m.endDate >= :startDate')
            ->orWhere('m.startDate <= :endDate')
            ->andWhere('m.endDate >= :endDate')
            ->orWhere('m.startDate >= :startDate AND m.endDate <= :endDate')
            ->setParameter('apartment', $apartment)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getResult();

        if (empty($minimumStayPeriod)) {
            return true; // No minimum stay requirement
        }

        // Check if the number of days meets the maximum minimum requirement
        $maxMinimumDays = max(array_map(fn($p) => $p->getMinimumDays(), $minimumStayPeriod));

        return $days >= $maxMinimumDays;
    }

    /**
     * Get all errors for a reservation
     */
    public function validate(Reservation $reservation): array
    {
        $errors = [];

        if (!$this->isAvailable($reservation)) {
            $errors[] = 'L\'appartement est déjà réservé sur cette période.';
        }

        if (!$this->meetsMinimumStay($reservation)) {
            $errors[] = 'La durée de la réservation ne respecte pas la durée minimale requise.';
        }

        return $errors;
    }
}
