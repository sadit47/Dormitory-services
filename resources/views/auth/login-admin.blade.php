<x-guest-layout>
  <div class="w-full max-w-md mx-auto">
    <div class="mb-6">
      <a href="{{ route('login') }}" class="text-sm text-slate-500 hover:underline">← กลับไปเลือกประเภท</a>
      <div class="text-2xl font-bold text-slate-900 mt-3">Admin Login</div>
      <div class="text-slate-500">เข้าสู่ระบบสำหรับผู้ดูแล</div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border p-6">
      <x-auth-session-status class="mb-4" :status="session('status')" />
      <x-input-error :messages="$errors->get('email')" class="mb-4" />

      <form method="POST" action="{{ route('login') }}">
        @csrf
        <input type="hidden" name="role" value="admin">

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

        <div class="mt-4 flex items-center justify-between">
          <label class="inline-flex items-center">
            <input type="checkbox" class="rounded border-gray-300" name="remember">
            <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
          </label>
        </div>

        <div class="mt-6">
          <x-primary-button class="w-full justify-center">
            LOG IN (ADMIN)
          </x-primary-button>
        </div>
      </form>
    </div>
  </div>
</x-guest-layout>
