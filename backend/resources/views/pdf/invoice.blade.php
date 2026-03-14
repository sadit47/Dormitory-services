{{-- resources/views/pdf/invoice.blade.php --}}
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <style>
    @page {
    margin: 14mm 12mm 14mm 12mm;
    }

    @font-face{
      font-family:"PDFThai";
      src:url("data:font/truetype;charset=utf-8;base64,{{ $fontRegularB64 }}") format("truetype");
      font-weight: normal;
      font-style: normal;
    }

    @font-face{
      font-family:"PDFThai";
      src:url("data:font/truetype;charset=utf-8;base64,{{ $fontBoldB64 }}") format("truetype");
      font-weight: bold;
      font-style: normal;
    }

    body{
      font-family:"PDFThai", DejaVu Sans, sans-serif;
      font-size:15px;
      line-height:1.5;
      color:#0f172a;
    }

    table, td, th, div, span, p{
      font-family:"PDFThai", DejaVu Sans, sans-serif;
      letter-spacing: 0;
      word-spacing: 0;
    }

    b, strong, .fw-bold, .title, .section-title, .info-value, .grand-row td {
    font-family:"PDFThai", DejaVu Sans, sans-serif;
    font-weight: bold;
     }

    .page{ width:100%; }
    .muted{ color:#64748b; }
    .text-right{ text-align:right; }
    .text-center{ text-align:center; }

    .title{
      font-size:22px;
      font-weight:bold;
      color:#0f172a;
      margin:0 0 2px 0;
    }

    .subtitle{
      font-size:12px;
      color:#64748b;
      margin:0;
    }

    .section{
      border:1px solid #dbe2ea;
      border-radius:10px;
      background:#ffffff;
      padding:8px 10px;
    }

    .section-soft{
      border:1px solid #dbe2ea;
      border-radius:10px;
      background:#f8fafc;
      padding:8px 10px;
    }

    .section-title{
      font-size:16px;
      font-weight:bold;
      color:#0f172a;
      margin-bottom:4px;
    }

    .header-wrap{
      width:100%;
      border-collapse:collapse;
      margin-bottom:8px;
    }

    .header-wrap td{
      vertical-align:top;
    }

    .hero-box{
      border:1px solid #c7d2fe;
      border-radius:10px;
      background:linear-gradient(135deg, #eef2ff 0%, #eff6ff 55%, #ecfeff 100%);
      padding:10px 12px;
    }

    .invoice-meta{
      margin-top:5px;
      color:#334155;
    }

    .invoice-meta div{
      margin-bottom:1px;
      font-size:15px;
    }

    .info-grid{
      width:100%;
      border-collapse:separate;
      border-spacing:0 5px;
    }

    .info-grid td{
      vertical-align:top;
    }

    .info-label{
      font-size:12px;
      color:#64748b;
      margin-bottom:1px;
    }

    .info-value{
      font-size:16px;
      font-weight:bold;
      color:#0f172a;
    }

    .badge{
      display:inline-block;
      padding:2px 10px;
      border-radius:999px;
      font-size:12px;
      font-weight:bold;
      border:1px solid #c7d2fe;
      background:#eef2ff;
      color:#4338ca;
    }

    .badge-paid{
      background:#ecfdf5;
      border-color:#bbf7d0;
      color:#047857;
    }

    .badge-unpaid{
      background:#fff7ed;
      border-color:#fed7aa;
      color:#c2410c;
    }

    .badge-void{
      background:#fef2f2;
      border-color:#fecaca;
      color:#b91c1c;
    }

    table.items{
      width:100%;
      border-collapse:separate;
      border-spacing:0;
      margin-top:8px;
      border:1px solid #e2e8f0;
      border-radius:10px;
      overflow:hidden;
    }

    table.items thead th{
      background:#f8fafc;
      color:#334155;
      font-weight:bold;
      font-size:14px;
      padding:6px 8px;
      border-bottom:1px solid #e2e8f0;
      text-align:left;
    }

    table.items tbody td{
      padding:6px 8px;
      border-bottom:1px solid #eef2f7;
      font-size:14px;
    }

    table.items tbody tr:last-child td{
      border-bottom:none;
    }

    table.items .right{
      text-align:right;
    }

    .summary-wrap{
      width:100%;
      margin-top:8px;
      border-collapse:collapse;
    }

    .summary-box{
      border:1px solid #dbe2ea;
      border-radius:10px;
      background:#ffffff;
      padding:8px 10px;
    }

    .summary-table{
      width:100%;
      border-collapse:collapse;
    }

    .summary-table td{
      padding:3px 0;
      font-size:14px;
    }

    .summary-label{
      color:#64748b;
    }

    .summary-value{
      text-align:right;
      font-weight:bold;
      color:#0f172a;
    }

    .grand-row td{
      border-top:1px solid #e2e8f0;
      padding-top:6px;
      font-size:17px;
      font-weight:bold;
      color:#111827;
    }

    .qr-box{
      text-align:center;
    }

    .qr-frame{
      border:1px solid #dbe2ea;
      border-radius:10px;
      background:#ffffff;
      padding:6px;
      display:inline-block;
    }

    .qr-caption{
      margin-top:4px;
      font-size:12px;
      color:#64748b;
    }

    .pay-amount{
      margin-top:5px;
      font-size:18px;
      font-weight:bold;
      color:#0f172a;
    }

    .note-box{
      margin-top:6px;
      border:1px dashed #cbd5e1;
      border-radius:8px;
      background:#f8fafc;
      padding:6px 8px;
      color:#475569;
      font-size:12px;
      line-height:1.35;
    }

    .compact-line div{
      margin-bottom:1px;
      font-size:14px;
    }
  </style>
</head>
<body>
  <div class="page">
    {{-- Header --}}
    <table class="header-wrap" cellpadding="0" cellspacing="0">
      <tr>
        <td style="width:57%; padding-right:6px;">
          <div class="hero-box">
            <div class="title">ใบแจ้งหนี้</div>
            <div class="subtitle">เอกสารสำหรับแจ้งยอดชำระของผู้เช่า</div>

            <div class="invoice-meta" style="margin-top:6px;">
              <div>เลขที่ใบแจ้งหนี้: <b>{{ $invoice->invoice_no }}</b></div>
              <div>งวดบิล: <b>{{ sprintf('%02d', $invoice->period_month) }}/{{ $invoice->period_year }}</b></div>
              <div>
                ประเภท:
                <b>
                  @if($invoice->type === 'rent')
                    ค่าเช่า
                  @elseif($invoice->type === 'utility')
                    ค่าน้ำ / ค่าไฟ
                  @elseif($invoice->type === 'repair')
                    ซ่อมแซม
                  @elseif($invoice->type === 'cleaning')
                    ทำความสะอาด
                  @else
                    {{ strtoupper($invoice->type) }}
                  @endif
                </b>
              </div>
            </div>
          </div>
        </td>

        <td style="width:43%;">
          <div class="section">
            <table class="info-grid" cellpadding="0" cellspacing="0">
              <tr>
                <td style="width:50%;">
                  <div class="info-label">วันออกเอกสาร</div>
                  <div class="info-value">{{ $invoice->created_at?->format('d/m/Y') }}</div>
                </td>
                <td style="width:50%;">
                  <div class="info-label text-right">กำหนดชำระ</div>
                  <div class="info-value text-right">
                    {{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') : '-' }}
                  </div>
                </td>
              </tr>

              <tr>
                <td>
                  <div class="info-label">ห้อง</div>
                  <div class="info-value">{{ $invoice->room?->code ?? '-' }}</div>
                </td>
                
                
                </td>
              </tr>
            </table>
          </div>
        </td>
      </tr>
    </table>

    {{-- Tenant + QR --}}
    <table class="header-wrap" cellpadding="0" cellspacing="0">
      <tr>
        <td style="width:57%; padding-right:6px;">
          <div class="section-soft">
            <div class="section-title">ข้อมูลผู้เช่า</div>

            <div class="compact-line">
              <div>ชื่อผู้เช่า: <b>{{ $invoice->tenant?->user?->name ?? '-' }}</b></div>
              <div>อีเมล: <b>{{ $invoice->tenant?->user?->email ?? '-' }}</b></div>
              <div>เบอร์โทร: <b>{{ $invoice->tenant?->user?->phone ?? '-' }}</b></div>
            </div>

            <div class="note-box">
              * กรุณาสแกน QR เพื่อชำระเงิน และอัปโหลดสลิปยืนยันการชำระในระบบ
            </div>
          </div>
        </td>

        <td style="width:43%;">
          <div class="section qr-box">
            <div class="section-title" style="margin-bottom:6px;">QR ชำระเงิน</div>

            @if(!empty($qrDataUri))
              <div class="qr-frame">
                <img src="{{ $qrDataUri }}" alt="QR Payment" style="width:140px; height:auto;">
              </div>
            @else
              <div class="muted">ไม่พบไฟล์ QR (public/qr-code.jpg)</div>
            @endif

            <div class="qr-caption">กรุณาตรวจสอบยอดก่อนชำระเงิน</div>
            <div class="pay-amount">ยอดชำระ {{ number_format($invoice->total, 2) }} บาท</div>
          </div>
        </td>
      </tr>
    </table>

    {{-- Items --}}
    <table class="items" cellpadding="0" cellspacing="0">
      <thead>
        <tr>
          <th>รายละเอียด</th>
          <th class="right" style="width:75px;">จำนวน</th>
          <th class="right" style="width:95px;">ราคา/หน่วย</th>
          <th class="right" style="width:95px;">รวม</th>
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
    <table class="summary-wrap" cellpadding="0" cellspacing="0">
      <tr>
        <td style="width:58%;"></td>
        <td style="width:42%;">
          <div class="summary-box">
            <table class="summary-table" cellpadding="0" cellspacing="0">
              <tr>
                <td class="summary-label">ยอดรวมก่อนหักส่วนลด</td>
                <td class="summary-value">{{ number_format($invoice->subtotal, 2) }}</td>
              </tr>
              <tr>
                <td class="summary-label">ส่วนลด</td>
                <td class="summary-value">{{ number_format($invoice->discount, 2) }}</td>
              </tr>
              <tr class="grand-row">
                <td>ยอดสุทธิ</td>
                <td class="text-right">{{ number_format($invoice->total, 2) }} บาท</td>
              </tr>
            </table>
          </div>
        </td>
      </tr>
    </table>
  </div>
</body>
</html>