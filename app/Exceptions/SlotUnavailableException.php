<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown by BookingService when a slot can no longer be booked — either
 * because it was already booked by someone else (the normal race outcome)
 * or because a concurrent transaction still holds its row lock and the
 * database gave up waiting. Both cases must show the user the exact same
 * friendly message (spec §10) and must never produce a false confirmation.
 */
class SlotUnavailableException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Δυστυχώς, αυτό το ραντεβού μόλις κλείστηκε από άλλον χρήστη. Παρακαλούμε επιλέξτε άλλη διαθέσιμη ώρα.');
    }
}
