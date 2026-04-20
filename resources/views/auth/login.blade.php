@extends('layouts.auth')

@section('content')
    <div class="min-h-screen flex items-center justify-center bg-center bg-cover relative px-4"
        style="background-image: url('{{ asset('images/bg_login.jpg') }}');">

        <div class="absolute inset-0 bg-black/50"></div>

        <!-- Card -->
        <div class="relative w-full max-w-md rounded-2xl border border-white/15 bg-black/30 backdrop-blur-md shadow-2xl">
            <div class="p-6 sm:p-8">
                <!-- Logo + Title -->
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-white/80 p-2 flex items-center justify-center">
                        <img src="{{ asset('images/logo.png') }}" alt="Logo" class="w-full h-full object-contain">
                    </div>

                    <h1 class="mt-4 text-xl sm:text-2xl font-semibold text-white">Masuk</h1>
                    <p class="mt-2 text-sm text-white/80">Silakan login untuk melanjutkan</p>
                </div>

                <!-- Session Status -->
                <div class="mt-6">
                    <x-auth-session-status class="mb-4 text-white" :status="session('status')" />
                </div>

                <form method="POST" action="{{ route('login') }}" class="mt-2 space-y-5">
                    @csrf

                    <!-- Username -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-white/90">Username</label>
                        <input id="email" name="email" type="text" value="{{ old('email') }}" required autofocus
                            autocomplete="username" placeholder="Masukkan Username"
                            class="mt-2 w-full rounded-xl bg-white/10 border border-white/15 px-4 py-3 text-white placeholder-white/40
                                   focus:outline-none focus:ring-2 focus:ring-white/20 focus:border-white/30">
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-white/90">Password</label>

                        <div class="relative mt-2">
                            <input id="password" name="password" type="password" required autocomplete="current-password"
                                placeholder="••••••••"
                                class="w-full rounded-xl bg-white/10 border border-white/15 px-4 py-3 pr-12 text-white placeholder-white/40
                                       focus:outline-none focus:ring-2 focus:ring-white/20 focus:border-white/30">

                            
                            <button type="button" id="togglePassword"
                                class="absolute inset-y-0 right-3 flex items-center text-black/60 hover:text-black transition"
                                aria-label="Toggle password visibility">
                                
                                <svg id="iconEye" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>

                                
                                <svg id="iconEyeOff" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M10.58 10.58A3 3 0 0012 15a3 3 0 002.42-4.42" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9.88 5.08A9.954 9.954 0 0112 5c4.477 0 8.268 2.943 9.542 7a10.08 10.08 0 01-4.11 5.27" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M6.23 6.23A10.08 10.08 0 002.458 12C3.732 16.057 7.523 19 12 19c1.01 0 1.99-.15 2.91-.43" />
                                </svg>
                            </button>
                        </div>

                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <!-- Remember -->
                    <div class="flex items-center gap-2">
                        <input id="remember_me" name="remember" type="checkbox"
                            class="h-4 w-4 rounded border-white/30 bg-white/10 text-purple-600 focus:ring-purple-500">
                        <label for="remember_me" class="text-sm text-white/80">Remember me</label>
                    </div>

                    <!-- Button -->
                    <button type="submit"
                        class="w-full rounded-xl py-3 font-semibold text-white bg-purple-600 hover:bg-purple-700 transition">
                        Log in
                    </button>

                    <p class="pt-2 text-center text-xs text-white/60">
                        © {{ date('Y') }} — Sistem Presensi SMK Al Hafidz
                    </p>
                </form>
            </div>
        </div>
    </div>

    <script>
        const btn = document.getElementById('togglePassword');
        const input = document.getElementById('password');

        btn?.addEventListener('click', () => {
            input.type = input.type === 'password' ? 'text' : 'password';
        });
    </script>
@endsection
