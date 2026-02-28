{{-- resources/views/pdf/invoice.blade.php --}}
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <style>
    @font-face{
  font-family:"THSarabun";
  src:url("data:font/truetype;charset=utf-8;base64,{{ $sarabunB64 ?? '' }}") format("truetype");
  font-weight: normal;
  font-style: normal;
}
@font-face{
  font-family:"THSarabun";
  src:url("data:font/truetype;charset=utf-8;base64,{{ $sarabunBoldB64 ?? ($sarabunB64 ?? '') }}") format("truetype");
  font-weight: bold;
  font-style: normal;
}
body{ font-family:"THSarabun", DejaVu Sans, sans-serif; }


    .muted{ color:#6b7280; }
    .box{ border:1px solid #e5e7eb; padding:12px; border-radius:8px; }
    .row{ width:100%; }
    .row td{ vertical-align:top; }
    .title{ font-size:22px; font-weight:bold; }
    .h{ font-weight:bold; }
    .right{ text-align:right; }

    table.items{ width:100%; border-collapse:collapse; margin-top:10px; }
    table.items th, table.items td{ border-bottom:1px solid #e5e7eb; padding:6px 8px; }
    table.items th{ background:#f9fafb; text-align:left; }

    .badge{
      display:inline-block; padding:2px 10px; border-radius:999px;
      background:#eef2ff; color:#3730a3; font-weight:bold;
    }

    .totalLine td{ padding:4px 8px; }
    .grand{ font-size:18px; font-weight:bold; }
  </style>
</head>
<body>


  @if(empty($sarabunB64))
    <div style="color:#b91c1c; margin-bottom:8px;">
      ไม่พบฟอนต์ไทย: public/fonts/THSarabunNew.ttf
    </div>
  @endif

  {{-- Header --}}
  <table class="row" cellpadding="0" cellspacing="0">
    <tr>
      <td style="width:60%;">
        <div class="title">ใบแจ้งหนี้ (INVOICE)</div>
        <div class="muted">เลขที่: <b>{{ $invoice->invoice_no }}</b></div>
        <div class="muted">งวด: <b>{{ sprintf('%02d', $invoice->period_month) }}/{{ $invoice->period_year }}</b></div>
        <div class="muted">ประเภท: <b>{{ strtoupper($invoice->type) }}</b></div>
      </td>

      <td style="width:40%;" class="right">
        <div class="box">
          <div class="h">วันออกเอกสาร</div>
          <div>{{ $invoice->created_at?->format('d/m/Y') }}</div>

          <div style="height:6px;"></div>

          <div class="h">กำหนดชำระ</div>
          <div>{{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') : '-' }}</div>

          <div style="height:6px;"></div>

          <div class="h">ห้อง</div>
          <div>{{ $invoice->room?->code ?? '-' }}</div>

          <div style="height:6px;"></div>

          <div class="h">สถานะ</div>
          <div class="badge">{{ strtoupper($invoice->status) }}</div>
        </div>
      </td>
    </tr>
  </table>

  <hr style="border:none;border-top:1px solid #e5e7eb;margin:12px 0;">

  {{-- Tenant + QR --}}
  <table class="row" cellpadding="0" cellspacing="0">
    <tr>
      <td style="width:60%;">
        <div class="box">
          <div class="h">ข้อมูลผู้เช่า</div>
          <div>ชื่อ: <b>{{ $invoice->tenant?->user?->name ?? '-' }}</b></div>
          <div>อีเมล: {{ $invoice->tenant?->user?->email ?? '-' }}</div>
          <div>โทร: {{ $invoice->tenant?->user?->phone ?? '-' }}</div>
          <div class="muted" style="margin-top:6px;">
            * สแกน QR เพื่อชำระเงิน แล้วอัปโหลดสลิปในระบบ
          </div>
        </div>
      </td>

      <td style="width:40%;" class="right">
        <div class="box">
          <div class="h" style="margin-bottom:6px;">QR ชำระเงิน</div>

          @if(!empty($qrDataUri))
            <img src="{{ $qrDataUri }}" alt="QR Payment" style="width:220px; height:auto;">
          @else
            <div class="muted">ไม่พบไฟล์ QR (public/qr-code.jpg)</div>
          @endif

          <div style="margin-top:6px;">
            ยอดชำระ <span class="h">{{ number_format($invoice->total, 2) }}</span> บาท
          </div>
        </div>
      </td>
    </tr>
  </table>

  {{-- Items --}}
  <table class="items" cellpadding="0" cellspacing="0">
    <thead>
      <tr>
        <th>รายละเอียด</th>
        <th class="right" style="width:70px;">จำนวน</th>
        <th class="right" style="width:90px;">ราคา/หน่วย</th>
        <th class="right" style="width:90px;">รวม</th>
      </tr>
    </thead>
    <tbody>
      @foreach($invoice->items as $it)
        <tr>
          <td>{{ $it->description }}</td>
          <td class="right">{{ number_format($it->qty, 2) }}</td>
          <td class="right">{{ number_format($it->unit_price, 2) }}</td>
          <td class="right"><b>{{ number_format($it->amount, 2) }}</b></td>
        </tr>
      @endforeach
    </tbody>
  </table>

  {{-- Summary --}}
  <table style="width:100%; margin-top:10px;" cellpadding="0" cellspacing="0">
    <tr>
      <td style="width:60%;"></td>
      <td style="width:40%;">
        <table style="width:100%;" class="totalLine" cellpadding="0" cellspacing="0">
          <tr>
            <td class="right muted">Subtotal</td>
            <td class="right">{{ number_format($invoice->subtotal, 2) }}</td>
          </tr>
          <tr>
            <td class="right muted">Discount</td>
            <td class="right">{{ number_format($invoice->discount, 2) }}</td>
          </tr>
          <tr>
            <td class="right grand">Total</td>
            <td class="right grand">{{ number_format($invoice->total, 2) }}</td>
          </tr>
        </table>
      </td>
    </tr>
  </table>

</body>
</html>
