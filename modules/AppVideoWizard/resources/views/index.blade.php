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
            <li>Livewire Class Exists: {{ class_exists(\Livewire\Livewire::class) ? 'YES' : 'NO' }}</li>
            <li>Component Class Exists: {{ class_exists(\Modules\AppVideoWizard\Livewire\VideoWizard::class) ? 'YES' : 'NO' }}</li>
        </ul>
    </div>

    {{-- Attempt to render Livewire component with error handling --}}
    @php
        $livewireError = null;
        try {
            // Check if component is registered
            $componentName = 'appvideowizard::video-wizard';

            // Try to get the component class
            $livewireManager = app('livewire');

            echo '<div class="alert alert-success mb-4"><p>Attempting to render Livewire component...</p></div>';

        } catch (\Throwable $e) {
            $livewireError = $e->getMessage() . "\n\nFile: " . $e->getFile() . "\nLine: " . $e->getLine() . "\n\nTrace:\n" . $e->getTraceAsString();
        }
    @endphp

    @if($livewireError)
        <div class="alert alert-error mb-4">
            <h4>Pre-render Error:</h4>
            <pre style="white-space: pre-wrap; font-size: 12px;">{{ $livewireError }}</pre>
        </div>
    @else
        {{-- Now try to actually render the component --}}
        @php
            try {
                echo \Livewire\Livewire::mount('appvideowizard::video-wizard', ['project' => $project])->html();
            } catch (\Throwable $e) {
                echo '<div class="alert alert-error mb-4">';
                echo '<h4>Livewire Mount Error:</h4>';
                echo '<pre style="white-space: pre-wrap; font-size: 12px;">';
                echo htmlspecialchars($e->getMessage() . "\n\nFile: " . $e->getFile() . "\nLine: " . $e->getLine());
                echo "\n\nTrace:\n" . htmlspecialchars($e->getTraceAsString());
                echo '</pre></div>';
            }
        @endphp
    @endif

    {{-- Fallback link --}}
    <div class="mt-4">
        <a href="{{ route('app.video-wizard.projects') }}" class="btn btn-ghost">
            Back to Projects
        </a>
    </div>
</div>
@endsection
