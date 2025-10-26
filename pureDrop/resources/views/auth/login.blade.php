@extends('layouts.app')

@section('title', 'Login Admin')

@section('content')
<div class="row justify-content-center">
  <div class="col-md-6 col-lg-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <h4 class="card-title mb-3">Admin Login</h4>

        @if($errors->any())
          <div class="alert alert-danger">
            <ul class="mb-0">
              @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <form method="POST" action="{{ route('login.attempt') }}">
          @csrf
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" class="form-control" required autofocus>
          </div>

          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>

          <div class="mb-3 form-check">
            <input type="checkbox" name="remember" id="remember" class="form-check-input">
            <label for="remember" class="form-check-label">Remember me</label>
          </div>

          <div class="d-grid">
            <button class="btn btn-primary">Login</button>
          </div>
        </form>
      </div>
    </div>

    <p class="text-muted small mt-2">Gunakan akun admin untuk akses dashboard.</p>
  </div>
</div>
@endsection
