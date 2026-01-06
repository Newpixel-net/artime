@extends('layouts.app')

@section('title', __('Video Creator'))

@section('css')
{{-- Livewire 3 styles - use the framework's method --}}
{!! \Livewire\Livewire::styles() !!}
<style>
    [wire\:loading], [wire\:loading\.delay], [wire\:loading\.inline-block], [wire\:loading\.inline], [wire\:loading\.block], [wire\:loading\.flex], [wire\:loading\.table], [wire\:loading\.grid] {
        display: none;
    }
    [wire\:offline] {
        display: none;
    }
    [wire\:dirty]:not(textarea):not(input):not(select) {
        display: none;
    }
</style>
@endsection

@section('content')
<div class="container mx-auto px-4 py-6">
    @livewire('appvideowizard::video-wizard', ['project' => $project])
</div>
@endsection

@section('script')
{{-- Livewire 3 script - use the framework's method to get the actual script tags --}}
{!! \Livewire\Livewire::scripts() !!}

{{-- Debug: Check if Livewire loads --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            if (typeof Livewire === 'undefined') {
                console.error('Livewire JS not loaded!');
                // Show error to user
                var errorDiv = document.createElement('div');
                errorDiv.style.cssText = 'position:fixed;top:0;left:0;right:0;background:red;color:white;padding:10px;text-align:center;z-index:9999;';
                errorDiv.innerHTML = 'Error: Livewire JavaScript failed to load. Please refresh the page or contact support.';
                document.body.prepend(errorDiv);
            } else {
                console.log('Livewire loaded:', Livewire);
            }
        }, 1000);
    });
</script>
@endsection
