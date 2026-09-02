@php
    /** @var \App\Models\AiGenerationJob $job */
    $reasons = $job->meta['reasons'] ?? [];
@endphp

<div class="space-y-4 text-sm">
    <dl class="grid grid-cols-2 gap-x-4 gap-y-2">
        <dt class="text-gray-500 dark:text-gray-400">Status</dt>
        <dd class="font-medium">{{ $job->status->label() }}</dd>

        <dt class="text-gray-500 dark:text-gray-400">Mata pelajaran</dt>
        <dd class="font-medium">{{ $job->subject->name }}</dd>

        <dt class="text-gray-500 dark:text-gray-400">Bab</dt>
        <dd class="font-medium">{{ $job->topic?->name ?? 'Semua bab' }}</dd>

        <dt class="text-gray-500 dark:text-gray-400">Tingkat kesulitan</dt>
        <dd class="font-medium">{{ $job->difficulty->label() }}</dd>

        <dt class="text-gray-500 dark:text-gray-400">Diminta</dt>
        <dd class="font-medium">{{ $job->count }} soal</dd>

        @if ($job->status === \App\Enums\AiJobStatus::Done)
            <dt class="text-gray-500 dark:text-gray-400">Tersimpan</dt>
            <dd class="font-medium">{{ $job->savedCount() }} soal</dd>

            <dt class="text-gray-500 dark:text-gray-400">Dilewati</dt>
            <dd class="font-medium">
                {{ $job->rejectedCount() }} tidak lolos pemeriksaan,
                {{ $job->duplicateCount() }} sudah ada
            </dd>

            <dt class="text-gray-500 dark:text-gray-400">Pemakaian</dt>
            <dd class="font-medium">{{ number_format($job->totalTokens()) }} token</dd>

            <dt class="text-gray-500 dark:text-gray-400">Perkiraan biaya</dt>
            <dd class="font-medium">{{ $job->estimated_cost }}</dd>
        @endif
    </dl>

    @if ($job->error)
        <div class="rounded-lg bg-danger-50 p-3 text-danger-700 dark:bg-danger-500/10 dark:text-danger-400">
            {{ $job->error }}
        </div>
    @endif

    @if (filled($reasons))
        <div>
            <p class="mb-2 font-medium">Soal yang dilewati</p>
            <ul class="list-inside list-disc space-y-1 text-gray-600 dark:text-gray-400">
                @foreach ($reasons as $reason)
                    <li>{{ $reason }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
