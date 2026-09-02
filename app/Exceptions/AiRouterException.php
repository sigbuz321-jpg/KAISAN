<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A generation attempt that failed in a way the teacher should hear about.
 *
 * Like QuestionWorkflowException, the message is shown as-is in the panel, so
 * it stays in Indonesian and never carries a URL, a key, or a router payload.
 *
 * `retryable` decides whether the queue should try again. Every retry is
 * another charge on the client's account, so only genuinely transient faults
 * are marked true: a reply we cannot parse will not parse any better the third
 * time, and a missing API key will not appear on its own.
 */
class AiRouterException extends RuntimeException
{
    private function __construct(string $message, public readonly bool $retryable)
    {
        parent::__construct($message);
    }

    public static function unreachable(): self
    {
        return new self(
            'Layanan AI sedang tidak bisa dihubungi. Silakan coba lagi beberapa saat lagi.',
            retryable: true,
        );
    }

    public static function unreadableResponse(): self
    {
        return new self(
            'Gagal membuat soal. Silakan coba lagi atau ubah topiknya.',
            retryable: false,
        );
    }

    public static function notConfigured(): self
    {
        return new self(
            'Layanan AI belum dikonfigurasi. Hubungi admin.',
            retryable: false,
        );
    }
}
