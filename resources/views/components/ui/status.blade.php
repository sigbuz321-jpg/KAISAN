@if (session('status'))
    <x-ui.banner tone="success" class="mt-6">{{ session('status') }}</x-ui.banner>
@endif
