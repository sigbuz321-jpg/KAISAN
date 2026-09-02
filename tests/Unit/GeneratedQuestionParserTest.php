<?php

use App\Exceptions\AiRouterException;
use App\Services\AiRouter\GeneratedQuestionParser;

beforeEach(function () {
    $this->parser = new GeneratedQuestionParser;
});

it('reads a bare JSON array', function () {
    $parsed = $this->parser->parse('[{"stem":"Soal satu"},{"stem":"Soal dua"}]');

    expect($parsed)->toHaveCount(2)
        ->and($parsed[0]['stem'])->toBe('Soal satu');
});

it('reads an array wrapped in a code fence', function () {
    $parsed = $this->parser->parse("```json\n[{\"stem\":\"Soal satu\"}]\n```");

    expect($parsed)->toHaveCount(1);
});

it('reads an array buried in chatter before and after', function () {
    $parsed = $this->parser->parse('Tentu, ini soalnya: [{"stem":"Soal satu"}] Semoga membantu!');

    expect($parsed)->toHaveCount(1);
});

it('rejects a reply that is not JSON at all', function () {
    expect(fn () => $this->parser->parse('Maaf, saya tidak bisa membuat soal itu.'))
        ->toThrow(AiRouterException::class, 'Gagal membuat soal');
});

it('rejects an empty reply', function () {
    expect(fn () => $this->parser->parse('   '))->toThrow(AiRouterException::class);
});

it('rejects a JSON object where an array was demanded', function () {
    expect(fn () => $this->parser->parse('{"stem":"Soal satu"}'))->toThrow(AiRouterException::class);
});

it('does not ask the queue to retry an unreadable reply', function () {
    // Retrying costs the client another call and will not fix bad JSON.
    try {
        $this->parser->parse('bukan JSON');
    } catch (AiRouterException $e) {
        expect($e->retryable)->toBeFalse();
    }
});

it('drops non-object entries but keeps the rest', function () {
    $parsed = $this->parser->parse('[{"stem":"Soal satu"},"sampah",{"stem":"Soal dua"}]');

    expect($parsed)->toHaveCount(2);
});
