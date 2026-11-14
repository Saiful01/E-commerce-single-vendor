<?php

namespace App\Exceptions;

use RuntimeException;

/** A problem the admin (or customer) can fix, with a message that is safe to show. */
class OrderException extends RuntimeException
{
}
