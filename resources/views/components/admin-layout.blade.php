{{-- resources/views/components/admin-layout.blade.php --}}
@props(['title' => null])

<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $title ? $title.' • ' : '' }}Admin Panel</title>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="text-slate-800">
  <div class="min-h-screen bg-gradient-to-b from-slate-200 to-slate-100">
    {{-- Topbar --}}
    <header class="sticky top-0 z-20 bg-indigo-600 backdrop-blur border-b border-slate-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="h-9 w-9 rounded-xl bg-white/80 text-slate-800 grid place-items-center font-black">A</div>
          <div class="leading-tight">
            <div class="font-bold text-white">Admin Panel</div>
            <div class="text-xs text-white">{{ auth()->user()->email ?? '-' }}</div>
          </div>
        </div>

        

        <div class="flex items-center gap-2">
          {{-- ✅ actions slot from pages --}}
  @isset($actions)
    <div class="hidden sm:flex items-center gap-2 mr-2">
      {{ $actions }}
    </div>
  @endisset
          <a href="{{ route('admin.profile.edit') }}"
             class="hidden sm:inline-flex px-3 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-sm font-semibold">
            แก้ไขข้อมูลตัวเอง
          </a>

          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="px-3 py-2 rounded-xl bg-slate-900 text-white hover:bg-slate-800 text-sm font-semibold">
              ออกจากระบบ
            </button>
          </form>
        </div>
      </div>

      {{-- Nav --}}
      <nav class="border-t border-slate-200 bg-white/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
          <div class="flex flex-wrap gap-2">
            <a class="px-3 py-2 rounded-xl text-sm font-semibold {{ request()->routeIs('admin.dashboard') ? 'bg-indigo-600 text-white' : 'bg-slate-100 hover:bg-slate-200' }}"
               href="{{ route('admin.dashboard') }}">Dashboard</a>

            <a class="px-3 py-2 rounded-xl text-sm font-semibold {{ request()->routeIs('admin.tenants.*') ? 'bg-indigo-600 text-white' : 'bg-slate-100 hover:bg-slate-200' }}"
               href="{{ route('admin.tenants.index') }}">ผู้เช่า</a>

            <a class="px-3 py-2 rounded-xl text-sm font-semibold {{ request()->routeIs('admin.rooms.*') ? 'bg-indigo-600 text-white' : 'bg-slate-100 hover:bg-slate-200' }}"
               href="{{ route('admin.rooms.index') }}">ห้องพัก</a>

            <a class="px-3 py-2 rounded-xl text-sm font-semibold {{ request()->routeIs('admin.invoices.*') ? 'bg-indigo-600 text-white' : 'bg-slate-100 hover:bg-slate-200' }}"
               href="{{ route('admin.invoices.index') }}">ใบแจ้งหนี้</a>

            <a class="px-3 py-2 rounded-xl text-sm font-semibold {{ request()->routeIs('admin.payments.pending') ? 'bg-indigo-600 text-white' : 'bg-slate-100 hover:bg-slate-200' }}"
               href="{{ route('admin.payments.pending') }}">ชำระเงินรอตรวจสอบ</a>

            <a class="px-3 py-2 rounded-xl text-sm font-semibold {{ request()->routeIs('admin.repairs.*') ? 'bg-indigo-600 text-white' : 'bg-slate-100 hover:bg-slate-200' }}"
               href="{{ route('admin.repairs.index') }}">แจ้งซ่อม</a>

            <a class="px-3 py-2 rounded-xl text-sm font-semibold {{ request()->routeIs('admin.profile.*') ? 'bg-indigo-600 text-white' : 'bg-slate-100 hover:bg-slate-200' }}"
               href="{{ route('admin.profile.edit') }}">โปรไฟล์</a>
          </div>
        </div>
      </nav>
    </header>

    {{-- Content --}}
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 bg-transparent">
      @if ($title)
        <div class="mb-4">
          <h1 class="text-2xl font-bold">{{ $title }}</h1>
        </div>
      @endif

      @if(session('success'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800">
          {{ session('success') }}
        </div>
      @endif

      @if(session('error'))
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800">
          {{ session('error') }}
        </div>
      @endif

      {{ $slot }}
    </main>
  </div>
</body>
</html>
