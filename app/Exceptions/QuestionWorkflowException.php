<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Carries a message meant to be shown to a teacher, in Indonesian.
 * Anything the user should not see must not be thrown as this type.
 */
class QuestionWorkflowException extends RuntimeException {}
