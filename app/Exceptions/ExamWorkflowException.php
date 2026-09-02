<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Carries a message shown to a student or teacher, in Indonesian.
 *
 * The wording matters more here than anywhere else in the app: a student
 * meeting one of these is usually mid-exam and already anxious. It says what
 * happened in plain words, never a status code.
 */
class ExamWorkflowException extends RuntimeException
{
    public static function notOpen(): self
    {
        return new self('Ujian ini sedang tidak dibuka.');
    }

    public static function alreadySubmitted(): self
    {
        return new self('Anda sudah mengumpulkan ujian ini.');
    }

    public static function timeIsUp(): self
    {
        return new self('Waktu ujian sudah habis, jawaban terakhir tidak bisa disimpan.');
    }

    public static function questionNotInExam(): self
    {
        return new self('Soal itu bukan bagian dari ujian ini.');
    }

    public static function noQuestions(): self
    {
        return new self('Ujian belum punya soal, jadi belum bisa dijadwalkan.');
    }

    public static function questionsAreFrozen(): self
    {
        return new self('Ujian yang sudah berjalan tidak bisa diubah soalnya. Buat ujian baru bila perlu revisi.');
    }
}
