@php
  $u = auth()->user();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="api-token" content="{{ session('api_token') }}">

  <title>{{ ($title ?? 'Dashboard') . ' - ' . config('app.name', 'Dorm Service') }}</title>

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

  <!-- ✅ Vite (Tailwind/JS) -->
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased bg-slate-200/60 text-slate-900">
  <div class="min-h-screen">
    <div class="flex">

      {{-- Sidebar --}}
      <aside class="hidden md:flex md:w-64 flex-col bg-indigo-700 text-white min-h-screen">
        <div class="px-6 py-6 font-bold text-lg flex items-center gap-2">
          <div class="w-10 h-10 rounded-xl bg-white/15 grid place-items-center">🏠</div>
          Dorm Service
        </div>

        <nav class="px-3 space-y-1">
          <a href="{{ route('tenant.dashboard') }}"
             class="block px-4 py-3 rounded-xl hover:bg-white/10 {{ request()->routeIs('tenant.dashboard') ? 'bg-white/15' : '' }}">
            📊 Dashboard
          </a>

          <a href="{{ route('tenant.invoices.index') }}"
             class="block px-4 py-3 rounded-xl hover:bg-white/10 {{ request()->routeIs('tenant.invoices.*') ? 'bg-white/15' : '' }}">
            🧾 ใบแจ้งหนี้/ใบเสร็จ
          </a>

          <a href="{{ route('tenant.repairs.index') }}"
             class="block px-4 py-3 rounded-xl hover:bg-white/10 {{ request()->routeIs('tenant.repairs.*') ? 'bg-white/15' : '' }}">
            🛠 แจ้งซ่อม
          </a>

          {{-- ถ้า route tenant.profile.edit ยังไม่มี ให้คอมเมนต์บรรทัดนี้ก่อน --}}
          <a href="{{ route('tenant.profile.edit') }}"
             class="block px-4 py-3 rounded-xl hover:bg-white/10 {{ request()->routeIs('tenant.profile.*') ? 'bg-white/15' : '' }}">
            👤 ข้อมูลส่วนตัว
          </a>
        </nav>

        <div class="mt-auto p-4">
          <div class="text-sm opacity-90 mb-3">
            {{ $u->name }}<br>
            <span class="opacity-80">{{ $u->email }}</span>
          </div>

          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="w-full bg-white text-indigo-700 rounded-xl py-2 font-semibold hover:bg-slate-100">
              ออกจากระบบ
            </button>
          </form>
        </div>
      </aside>

      {{-- Main --}}
      <main class="flex-1 min-w-0">
        {{-- topbar --}}
        <div class="bg-white border-b">
          <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
            <div class="font-semibold text-slate-800">
              {{ $title ?? 'Dashboard' }}
            </div>

            <div class="md:hidden">
              <a href="{{ route('tenant.dashboard') }}" class="text-indigo-700 font-semibold">Home</a>
            </div>
          </div>
        </div>

        {{-- content --}}
        <div class="max-w-6xl mx-auto px-4 py-6">
          @if(session('success'))
            <div class="mb-4 bg-emerald-50 text-emerald-700 px-4 py-3 rounded-xl border border-emerald-100">
              {{ session('success') }}
            </div>
          @endif

          {{ $slot }}
        </div>
      </main>

    </div>
  </div>
</body>
</html>
