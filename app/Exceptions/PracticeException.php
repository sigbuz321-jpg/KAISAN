<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Something went wrong during practice, phrased for the student reading it.
 *
 * Practice is meant to be low-stakes, so these say what to do next rather than
 * what went wrong internally.
 */
class PracticeException extends RuntimeException
{
    public static function sessionClosed(): self
    {
        return new self('Sesi latihan ini sudah ditutup. Mulai latihan baru untuk melanjutkan.');
    }

    public static function wrongSubject(): self
    {
        return new self('Soal itu bukan dari mata pelajaran yang sedang kamu latih.');
    }

    public static function notPublished(): self
    {
        return new self('Soal itu sedang tidak tersedia untuk latihan.');
    }

    public static function noQuestions(): self
    {
        return new self('Belum ada soal untuk mata pelajaran ini. Beri tahu gurumu, ya.');
    }
}
