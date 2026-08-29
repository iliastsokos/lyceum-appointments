<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when an uploaded file passes extension/MIME validation but
 * PhpSpreadsheet still can't parse it (corrupted, wrong internal format,
 * or not actually a spreadsheet). Never let the underlying parser exception
 * reach the user as a raw 500 error.
 */
class UnreadableSpreadsheetException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This file could not be read. Please make sure it is a valid .xlsx file and try again.');
    }
}
