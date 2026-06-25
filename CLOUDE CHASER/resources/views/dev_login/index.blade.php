@extends('layouts.app')

@section('content')
    <h1 class="mb-3">Temporary Login Switch</h1>
    <p class="text-muted">For development only. Choose one seeded user to enter the system.</p>

    <div class="row g-3">
        @foreach($users as $user)
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">{{ $user->name }}</h5>
                        <p class="mb-1"><strong>Role:</strong> {{ ucfirst($user->role) }}</p>
                        <p class="mb-1"><strong>Email:</strong> {{ $user->email }}</p>
                        <p class="mb-3"><strong>Department:</strong> {{ $user->department?->name ?? '-' }}</p>

                        <form method="POST" action="{{ route('dev-login.store') }}">
                            @csrf
                            <input type="hidden" name="user_id" value="{{ $user->id }}">
                            <button class="btn btn-primary" type="submit">
                                {{ $selectedUserId == $user->id ? 'Continue as this user' : 'Switch to this user' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection