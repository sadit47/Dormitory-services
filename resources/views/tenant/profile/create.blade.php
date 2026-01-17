<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">กรอกข้อมูลผู้เช่า</h2>
  </x-slot>

  <div class="py-6">
    <div class="max-w-xl mx-auto sm:px-6 lg:px-8">

      @if(session('success'))
        <div class="bg-yellow-100 text-yellow-800 px-4 py-2 rounded-xl mb-4">
          {{ session('success') }}
        </div>
      @endif

      <div class="bg-white rounded-2xl shadow p-6">
        <form method="POST" action="{{ route('tenant.profile.store') }}" class="space-y-4">
          @csrf

          <div>
            <label class="text-sm text-gray-600">ชื่อ-สกุล</label>
            <input name="full_name" class="w-full border rounded-xl px-3 py-2" required value="{{ old('full_name') }}">
            @error('full_name') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
          </div>

          <div>
            <label class="text-sm text-gray-600">เบอร์โทร</label>
            <input name="phone" class="w-full border rounded-xl px-3 py-2" value="{{ old('phone') }}">
          </div>

          <div>
            <label class="text-sm text-gray-600">ที่อยู่</label>
            <textarea name="address" class="w-full border rounded-xl px-3 py-2" rows="3">{{ old('address') }}</textarea>
          </div>

          <button class="bg-gray-900 text-white rounded-xl px-4 py-2">
            บันทึก
          </button>
        </form>
      </div>

    </div>
  </div>
</x-app-layout>
