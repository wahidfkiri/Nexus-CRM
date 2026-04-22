@extends('layouts.global')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/notion-workspace/css/notion-workspace.css') }}">
<link href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css" rel="stylesheet">
@endpush

@section('breadcrumb')
@yield('notion_breadcrumb')
@endsection

@section('content')
@yield('notion_content')
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>
<script src="{{ asset('vendor/notion-workspace/js/notion-workspace.js') }}"></script>
@endpush
