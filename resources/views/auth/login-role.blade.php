@php
  $isAdmin = ($role ?? '') === 'admin';
@endphp

<x-guest-layout>
  <div class="max-w-md mx-auto">
    <div class="text-center">
      <div class="text-2xl font-bold text-slate-900">
        {{ $isAdmin ? 'Admin Login' : 'Tenant Login' }}
      </div>
      <div class="text-sm text-slate-500 mt-1">
        กรุณาเข้าสู่ระบบเพื่อใช้งานระบบหอพัก
      </div>
    </div>

    <div class="mt-6 bg-white rounded-2xl shadow-sm border p-6">
      <x-auth-session-status class="mb-4" :status="session('status')" />

      <x-input-error :messages="$errors->get('email')" class="mb-3" />

      <form method="POST" action="{{ route('login.store') }}">
        @csrf

        {{-- ส่ง role ไปกับฟอร์ม --}}
        <input type="hidden" name="role" value="{{ $isAdmin ? 'admin' : 'tenant' }}">

        <div>
          <x-input-label for="email" :value="__('Email')" />
          <x-text-input id="email" class="block mt-1 w-full" type="email" name="email"
                        :value="old('email')" required autofocus autocomplete="username" />
        </div>

        <div class="mt-4">
          <x-input-label for="password" :value="__('Password')" />
          <x-text-input id="password" class="block mt-1 w-full" type="password" name="password"
                        required autocomplete="current-password" />
        </div>

        <div class="block mt-4">
          <label for="remember_me" class="inline-flex items-center">
            <input id="remember_me" type="checkbox"
                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                   name="remember">
            <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
          </label>
        </div>

        <div class="flex items-center justify-between mt-6">
          <a href="{{ route('login') }}" class="text-sm text-slate-500 hover:underline">
            ← กลับไปเลือกประเภท
          </a>
        </div>

        <button type="submit"
                class="mt-4 w-full rounded-2xl font-semibold py-3 text-white
                       {{ $isAdmin ? 'bg-slate-900 hover:bg-slate-800' : 'bg-indigo-600 hover:bg-indigo-500' }}
                       transition">
          LOG IN
        </button>
      </form>
    </div>
  </div>
</x-guest-layout>
