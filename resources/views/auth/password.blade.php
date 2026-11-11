@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Ubah Kata Sandi</h2>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('password.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label>Kata Sandi Lama</label>
            <input type="password" name="current_password"
                class="w-full border border-gray-300 rounded px-3 py-2 mt-1"
            @error('current_password')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label>Kata Sandi Baru</label>
            <input type="password" name="password"
                class="w-full border border-gray-300 rounded px-3 py-2 mt-1"
            @error('password')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label>Konfirmasi Kata Sandi Baru</label>
            <input type="password" name="password_confirmation"
                class="w-full border border-gray-300 rounded px-3 py-2 mt-1"
        </div>

        <button type="submit" class="btn btn-primary">
            Simpan Kata Sandi
        </button>
    </form>
</div>
@endsection