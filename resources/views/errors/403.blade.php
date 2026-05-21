@extends('layouts.app')

@section('title', 'Access Denied')

@section('content')
<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="p-8">
        @include('partials._empty_state', [
            'icon' => 'ph-lock',
            'title' => 'Access Denied',
            'message' => $message ?? 'You do not have permission to view this module.'
        ])
    </div>
</div>
@endsection
