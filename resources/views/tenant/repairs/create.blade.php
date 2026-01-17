<x-tenant-layout>
  <x-slot name="title">แจ้งซ่อมใหม่</x-slot>

  <div class="card-strong p-6">
    <h1 class="text-xl font-bold mb-1">🛠 แจ้งซ่อมใหม่</h1>
    <p class="text-sm text-slate-500 mb-4">
      กรอกข้อมูลและกดส่ง ระบบจะบันทึกคำร้องของคุณ
    </p>

    {{-- ✅ error รวม --}}
    @if ($errors->any())
      <div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
        <div class="font-semibold mb-1">ส่งไม่สำเร็จ</div>
        <ul class="list-disc pl-5 space-y-1">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    {{-- ✅ POST + enctype --}}
    <form method="POST"
          action="{{ route('tenant.repairs.store') }}"
          enctype="multipart/form-data"
          class="space-y-4">
      @csrf

      {{-- หัวข้อ --}}
      <div>
        <label class="block text-sm font-semibold mb-1">หัวข้อ</label>
        <input
          name="title"
          value="{{ old('title') }}"
          required
          placeholder="เช่น แอร์ไม่เย็น / น้ำรั่ว"
          class="w-full rounded-xl border border-slate-300 px-4 py-2
                 focus:outline-none focus:ring-2 focus:ring-indigo-300"
        >
        @error('title')
          <div class="text-sm text-red-600 mt-1">{{ $message }}</div>
        @enderror
      </div>

      {{-- รายละเอียด --}}
      <div>
        <label class="block text-sm font-semibold mb-1">รายละเอียด</label>
        <textarea
          name="description"
          rows="4"
          placeholder="อธิบายอาการ/จุดที่เสีย"
          class="w-full rounded-xl border border-slate-300 px-4 py-2
                 focus:outline-none focus:ring-2 focus:ring-indigo-300"
        >{{ old('description') }}</textarea>
        @error('description')
          <div class="text-sm text-red-600 mt-1">{{ $message }}</div>
        @enderror
      </div>

      {{-- ✅ ความเร่งด่วน (สำคัญมาก ไม่งั้นส่งไม่ผ่าน) --}}
      <div>
        <label class="block text-sm font-semibold mb-1">ความเร่งด่วน</label>
        <select
          name="priority"
          required
          class="w-full rounded-xl border border-slate-300 px-4 py-2
                 focus:outline-none focus:ring-2 focus:ring-indigo-300"
        >
          <option value="low" @selected(old('priority')==='low')>ต่ำ</option>
          <option value="medium" @selected(old('priority','medium')==='medium')>กลาง</option>
          <option value="high" @selected(old('priority')==='high')>สูง</option>
        </select>
        @error('priority')
          <div class="text-sm text-red-600 mt-1">{{ $message }}</div>
        @enderror
      </div>

      {{-- ✅ แนบรูปหลายรูป --}}
      <div>
        <label class="block text-sm font-semibold mb-1">แนบรูป (ถ้ามี)</label>
        <input
          type="file"
          name="images[]"
          multiple
          accept="image/*"
          class="w-full rounded-xl border border-slate-300 px-4 py-2"
        >
        @error('images.*')
          <div class="text-sm text-red-600 mt-1">{{ $message }}</div>
        @enderror
      </div>

      {{-- ปุ่ม --}}
      <div class="flex items-center gap-2 pt-2">
        <button
          type="submit"
          class="px-5 py-2 rounded-xl bg-indigo-700 text-white
                 font-semibold hover:bg-indigo-600">
          ส่งแจ้งซ่อม
        </button>

        <a
          href="{{ route('tenant.repairs.index') }}"
          class="px-5 py-2 rounded-xl border bg-white
                 hover:bg-slate-50 font-semibold">
          ย้อนกลับ
        </a>
      </div>

    </form>
  </div>
</x-tenant-layout>
