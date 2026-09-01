@if (session('status'))
    <div class="mt-6 rounded border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
        {{ session('status') }}
    </div>
@endif
