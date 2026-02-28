<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $title }}</title>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-gradient-to-b from-slate-100 to-white text-slate-800">
  <div class="min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-4xl grid grid-cols-1 md:grid-cols-2 gap-6">

      {{-- Left: Brand/Info --}}
      <div class="rounded-3xl p-8 text-white shadow-lg
        {{ $role==='admin' ? 'bg-gradient-to-br from-indigo-600 to-violet-700' : 'bg-gradient-to-br from-emerald-600 to-teal-700' }}
      ">
        <div class="text-2xl font-extrabold tracking-tight">Dorm Service</div>
        <div class="mt-2 text-white/90">{{ $subtitle }}</div>

        <div class="mt-8 space-y-3 text-sm text-white/90">
          <div class="flex items-start gap-3">
            <div class="mt-1 h-2.5 w-2.5 rounded-full bg-white/90"></div>
            <div>ปลอดภัย: เข้าด้วยบัญชีของคุณเท่านั้น</div>
          </div>
          <div class="flex items-start gap-3">
            <div class="mt-1 h-2.5 w-2.5 rounded-full bg-white/90"></div>
            <div>ดู PDF ใบแจ้งหนี้/ใบเสร็จ และ QR ชำระเงินได้</div>
          </div>
          <div class="flex items-start gap-3">
            <div class="mt-1 h-2.5 w-2.5 rounded-full bg-white/90"></div>
            <div>แจ้งซ่อม และติดตามสถานะได้</div>
          </div>
        </div>

        <div class="mt-10 text-sm text-white/80">
          ไปหน้าอื่น:
          @if($role==='admin')
            <a href="{{ route('tenant.login') }}" class="underline font-semibold">เข้าสู่ระบบผู้เช่า</a>
          @else
            <a href="{{ route('admin.login') }}" class="underline font-semibold">เข้าสู่ระบบแอดมิน</a>
          @endif
        </div>
      </div>

      {{-- Right: Login form --}}
      <div class="rounded-3xl bg-white shadow-lg border border-slate-200 p-8">
        <div class="text-xl font-bold">{{ $title }}</div>
        <div class="text-sm text-slate-500 mt-1">กรอกอีเมลและรหัสผ่านเพื่อเข้าสู่ระบบ</div>

        {{-- errors --}}
        @if ($errors->any())
          <div class="mt-4 rounded-2xl bg-rose-50 border border-rose-200 px-4 py-3 text-rose-700 text-sm">
            <ul class="list-disc pl-5">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
          @csrf

          {{-- ส่ง role ไปให้ controller login ตรวจ --}}
          <input type="hidden" name="role" value="{{ $role }}">

          <div>
            <label class="text-sm font-semibold">Email</label>
            <input name="email" type="email" value="{{ old('email') }}"
              class="mt-1 w-full rounded-2xl border border-slate-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-slate-900/20"
              placeholder="name@example.com" required autofocus>
          </div>

          <div>
            <label class="text-sm font-semibold">Password</label>
            <input name="password" type="password"
              class="mt-1 w-full rounded-2xl border border-slate-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-slate-900/20"
              placeholder="••••••••" required>
          </div>

          <div class="flex items-center justify-between">
            <label class="inline-flex items-center gap-2 text-sm text-slate-600">
              <input type="checkbox" name="remember" class="rounded border-slate-300">
              Remember me
            </label>

            @if (Route::has('password.request'))
              <a class="text-sm font-semibold underline text-slate-700" href="{{ route('password.request') }}">
                Forgot your password?
              </a>
            @endif
          </div>

          <button
            class="w-full rounded-2xl px-5 py-3 font-semibold text-white shadow
              {{ $role==='admin' ? 'bg-indigo-700 hover:bg-indigo-600' : 'bg-emerald-700 hover:bg-emerald-600' }}
            ">
            LOG IN
          </button>

          <div class="text-xs text-slate-500 text-center">
            ระบบจะอนุญาตเข้าเฉพาะ role ที่เลือกเท่านั้น
          </div>
        </form>
      </div>

    </div>
  </div>
</body>
</html>
