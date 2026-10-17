<?php

namespace App\Exceptions;

use RuntimeException;

/** A webhook request whose signature or payload could not be verified. */
class InvalidWebhookException extends RuntimeException {}
