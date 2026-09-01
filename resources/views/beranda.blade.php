@extends('layouts.app')

@section('title', __('app.name'))

@section('content')
    <h1 class="text-2xl font-semibold sm:text-3xl">{{ __('app.home.heading') }}</h1>

    <p class="mt-3 text-base leading-relaxed text-slate-700">{{ __('app.home.body') }}</p>

    <p class="mt-6 inline-block rounded border border-emerald-300 bg-emerald-50 px-3 py-2
              text-sm font-medium text-emerald-900">
        {{ __('app.home.status') }}
    </p>
@endsection
