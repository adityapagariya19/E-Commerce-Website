<?php

namespace App\Support;

use RuntimeException;

/** An error whose message is safe to show to the customer / admin. */
class StoreException extends RuntimeException
{
}
