{{-- resources/views/pdf/receipt.blade.php --}}
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

    .row{ width:100%; }
    .row td{ vertical-align: top; }
    .title{ font-size:22px; font-weight:bold; }
    .muted{ color:#6b7280; }
    .right{ text-align:right; }
    .box{ border:1px solid #e5e7eb; padding:10px 12px; border-radius:8px; }
    table{ width:100%; border-collapse:collapse; margin-top:10px; }
    th,td{ border:1px solid #e5e7eb; padding:7px 8px; }
    th{ background:#f9fafb; }
    .grand{ font-size:18px; font-weight:bold; }
    .badge{ display:inline-block; padding:2px 10px; border-radius:999px; background:#dcfce7; color:#166534; font-weight:bold; }
  </style>
</head>
<body>

  <table class="row" cellpadding="0" cellspacing="0">
    <tr>
      <td style="width:60%">
        <div class="title">ใบเสร็จรับเงิน (RECEIPT)</div>
        <div>เลขที่ใบเสร็จ: <b>{{ $invoice->receipt_no }}</b></div>
        <div>อ้างอิงใบแจ้งหนี้: <b>{{ $invoice->invoice_no }}</b></div>
        <div class="muted">งวด: <b>{{ sprintf('%02d', $invoice->period_month) }}/{{ $invoice->period_year }}</b></div>
      </td>

      <td style="width:40%" class="right">
        <div class="box">
          <div><b>ระบบหอพัก</b></div>
          <div>วันที่ออกใบเสร็จ: {{ optional($invoice->receipt_issued_at)->format('d/m/Y H:i') }}</div>
          <div style="margin-top:6px;">สถานะ: <span class="badge">PAID</span></div>
        </div>
      </td>
    </tr>
  </table>

  <hr style="border:none;border-top:1px solid #e5e7eb;margin:12px 0;">

  <table class="row" cellpadding="0" cellspacing="0">
    <tr>
      <td style="width:60%">
        <div class="box">
          <div><b>ผู้เช่า:</b> {{ $invoice->tenant->user->name ?? '-' }}</div>
          <div><b>อีเมล:</b> {{ $invoice->tenant->user->email ?? '-' }}</div>
          <div><b>โทร:</b> {{ $invoice->tenant->user->phone ?? '-' }}</div>
          @if($invoice->room)
            <div><b>ห้อง:</b> {{ $invoice->room->code }}</div>
          @endif
        </div>
      </td>

      <td style="width:40%" class="right">
        <div class="box">
          <div style="font-weight:bold; margin-bottom:6px;">QR ชำระเงิน</div>
          @if(!empty($qrDataUri))
            <img src="{{ $qrDataUri }}" alt="QR Payment" style="width:210px; height:auto;">
          @else
            <div class="muted">ไม่พบไฟล์ QR: public/qr-code.jpg</div>
          @endif
          <div style="margin-top:6px;">
            ยอดชำระ <b>{{ number_format($invoice->total,2) }}</b> บาท
          </div>
        </div>
      </td>
    </tr>
  </table>

  <table>
    <thead>
      <tr>
        <th>รายการ</th>
        <th class="right" style="width:120px;">รวม</th>
      </tr>
    </thead>
    <tbody>
      @foreach($invoice->items as $it)
        <tr>
          <td>{{ $it->description }}</td>
          <td class="right">{{ number_format($it->amount,2) }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <div style="margin-top: 12px;" class="right">
    <div>ยอดรวมสุทธิ: <span class="grand">{{ number_format($invoice->total,2) }}</span></div>
  </div>

</body>
</html>
