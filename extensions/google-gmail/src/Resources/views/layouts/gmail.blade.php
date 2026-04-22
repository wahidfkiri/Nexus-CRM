@extends('layouts.global')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/google-gmail/css/google-gmail.css') }}">
@endpush

@section('breadcrumb')
@yield('ggm_breadcrumb')
@endsection

@section('content')
@yield('ggm_content')
@endsection

@push('scripts')
<script src="{{ asset('vendor/google-gmail/js/google-gmail.js') }}"></script>
@endpush
