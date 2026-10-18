<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown inside the import transaction to roll back a --dry-run.
 */
final class DryRunFinished extends RuntimeException {}
