@extends('layouts.app')

@section('title', __('Video Creator'))

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- Debug information --}}
    <div class="alert alert-info mb-4">
        <h4>Debug Info - View Successfully Loaded</h4>
        <ul>
            <li>Route reached: YES</li>
            <li>View loaded: YES</li>
            <li>Project: {{ $project ? 'ID: ' . $project->id : 'NULL (new project)' }}</li>
            <li>PHP Version: {{ phpversion() }}</li>
            <li>Laravel Version: {{ app()->version() }}</li>
            <li>Livewire Class Exists: {{ class_exists(\Livewire\Livewire::class) ? 'YES' : 'NO' }}</li>
        </ul>
    </div>

    <div class="alert alert-warning mb-4">
        <p><strong>If you see this message, the route and view are working correctly.</strong></p>
        <p>The 404 error must be happening BEFORE this view is rendered.</p>
    </div>

    {{-- Simple static content to test --}}
    <div class="card bg-base-200">
        <div class="card-body">
            <h2 class="card-title">Video Creator - Test Mode</h2>
            <p>This is a static test. If you see this, the basic view rendering works.</p>
            <a href="{{ route('app.video-wizard.projects') }}" class="btn btn-primary">
                Go to Projects
            </a>
        </div>
    </div>
</div>
@endsection
