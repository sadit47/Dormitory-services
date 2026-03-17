import { useEffect, useMemo, useState } from "react";
import { useNavigate, useParams } from "react-router-dom";
import {
  tenantInvoicesApi,
  type TenantInvoiceDetail,
} from "./services/tenantInvoicesApi";

function money(n: any) {
  return Number(n ?? 0).toLocaleString();
}

function displayStatus(inv: any) {
  if (inv.payment_status === "waiting") return "กำลังดำเนินการ";
  if (inv.status === "paid") return "ชำระแล้ว";
  if (inv.status === "unpaid" || inv.status === "partial") return "ยังไม่ชำระ";
  return inv.status ?? "-";
}

function statusPill(inv: any) {
  if (inv.payment_status === "waiting") {
    return "bg-amber-50 text-amber-700 ring-1 ring-amber-200";
  }
  if (inv.status === "paid") {
    return "bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200";
  }
  if (inv.status === "unpaid" || inv.status === "partial") {
    return "bg-rose-50 text-rose-700 ring-1 ring-rose-200";
  }
  return "bg-slate-100 text-slate-600 ring-1 ring-slate-200";
}

function getRoomLabel(room: any) {
  if (!room) return "-";
  return room.room_no ?? room.name ?? room.code ?? room.id ?? "-";
}

export default function TenantInvoiceDetailPage() {
  const nav = useNavigate();
  const { id } = useParams();
  const invoiceId = Number(id);

  const [data, setData] = useState<TenantInvoiceDetail | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (!invoiceId) return;

    setLoading(true);
    tenantInvoicesApi
      .show(invoiceId)
      .then(setData)
      .finally(() => setLoading(false));
  }, [invoiceId]);

  const subtotal = useMemo(() => {
    const items = data?.items ?? [];
    return items.reduce(
      (acc, x) => acc + Number(x.qty ?? 0) * Number(x.unit_price ?? 0),
      0
    );
  }, [data]);

  const openPdf = async () => {
    if (!data?.id) return;

    try {
      const res = await tenantInvoicesApi.openPdfBlob(data.id);
      const blob = new Blob([res.data], { type: "application/pdf" });

      const ua = navigator.userAgent || "";
      const isIOS = /iPad|iPhone|iPod/.test(ua);
      const isAndroid = /Android/.test(ua);
      const isMobile = isIOS || isAndroid;

      // iPhone/iPad: ใช้ FileReader จะเปิดง่ายกว่า blob url
      if (isIOS) {
        const reader = new FileReader();
        reader.onloadend = () => {
          const dataUrl = reader.result as string;
          window.location.href = dataUrl;
        };
        reader.readAsDataURL(blob);
        return;
      }

      const url = window.URL.createObjectURL(blob);

      if (isMobile) {
        // มือถือเปิดหน้าเดิม
        window.location.href = url;
      } else {
        // desktop เปิดแท็บใหม่
        window.open(url, "_blank", "noopener,noreferrer");
      }

      setTimeout(() => window.URL.revokeObjectURL(url), 60_000);
    } catch (error) {
      console.error("open pdf failed", error);
      alert("ไม่สามารถเปิด PDF ได้");
    }
    
  };
  const downloadPdf = async () => {
  if (!data?.id) return;

  try {
    const res = await tenantInvoicesApi.downloadPdfBlob(data.id);
    const blob = new Blob([res.data], { type: "application/pdf" });
    const url = window.URL.createObjectURL(blob);

    const a = document.createElement("a");
    a.href = url;
    a.download = `invoice-${data.invoice_no}.pdf`;
    document.body.appendChild(a);
    a.click();
    a.remove();

    setTimeout(() => window.URL.revokeObjectURL(url), 60_000);
  } catch (error) {
    console.error("download pdf failed", error);
    alert("ดาวน์โหลด PDF ไม่สำเร็จ");
  }
};


  if (loading) {
    return (
      <div className="rounded-[28px] border border-white/70 bg-white/90 p-6 shadow-[0_12px_36px_rgba(15,23,42,0.06)]">
        <div className="rounded-2xl bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
          กำลังโหลด...
        </div>
      </div>
    );
  }

  if (!data) {
    return (
      <div className="rounded-[28px] border border-white/70 bg-white/90 p-6 shadow-[0_12px_36px_rgba(15,23,42,0.06)]">
        <div className="rounded-2xl bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
          ไม่พบข้อมูล
        </div>
      </div>
    );
  }

  const canUploadSlip =
    (data.status === "unpaid" || data.status === "partial") &&
    data.payment_status !== "waiting";

  return (
    <div className="space-y-5">
      {/* Top actions */}
      <section className="rounded-[28px] border border-white/70 bg-white/85 p-5 shadow-[0_14px_36px_rgba(15,23,42,0.06)] backdrop-blur-sm">
        <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
          <div>
            <button
              onClick={() => nav(-1)}
              className="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-[0_6px_16px_rgba(15,23,42,0.04)] transition hover:bg-slate-50"
            >
              ← กลับ
            </button>

            <div className="mt-4">
              <div className="text-2xl font-bold tracking-tight text-slate-800">
                รายละเอียดใบแจ้งหนี้
              </div>
              <div className="mt-1 text-sm text-slate-500">
                ตรวจสอบข้อมูลรายการ ยอดรวม และสถานะการชำระเงิน
              </div>
            </div>
          </div>

          <div className="flex flex-wrap gap-2">
            <button
              onClick={openPdf}
              className="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-[0_8px_20px_rgba(15,23,42,0.04)] transition hover:bg-slate-50"
            >
              👁️ เปิดดู PDF
            </button>

            <button
              onClick={downloadPdf}
              className="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-[0_8px_20px_rgba(15,23,42,0.04)] transition hover:bg-slate-50"
            >
              ⬇️ ดาวน์โหลด PDF
            </button>

            {canUploadSlip && (
              <button
                onClick={() => nav(`/tenant/payments/upload?invoice_id=${data.id}`)}
                className="rounded-2xl bg-linear-to-r from-indigo-600 to-blue-600 px-4 py-2.5 text-sm font-medium text-white shadow-[0_12px_26px_rgba(59,130,246,0.22)] transition hover:-translate-y-0.5 hover:shadow-[0_16px_30px_rgba(59,130,246,0.28)]"
              >
                💳 อัปโหลดสลิปชำระเงิน
              </button>
            )}
          </div>
        </div>
      </section>

      {/* Summary hero */}
      <section className="relative overflow-hidden rounded-[28px] border border-white/40 bg-linear-to-r from-indigo-500 via-blue-500 to-cyan-500 p-6 text-white shadow-[0_18px_50px_rgba(59,130,246,0.22)]">
        <div className="relative z-10 flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
          <div>
            <div className="text-sm font-medium text-white/85">เลขที่ใบแจ้งหนี้</div>
            <div className="mt-2 text-2xl font-bold tracking-tight">
              {data.invoice_no}
            </div>

            <div className="mt-4 flex flex-wrap gap-2 text-sm">
              <span className="rounded-full bg-white/15 px-3 py-1 ring-1 ring-white/20 backdrop-blur-sm">
                งวด {data.period_month}/{data.period_year}
              </span>
              <span className="rounded-full bg-white/15 px-3 py-1 ring-1 ring-white/20 backdrop-blur-sm">
                ห้อง {getRoomLabel(data.room)}
              </span>
              {data.due_date && (
                <span className="rounded-full bg-white/15 px-3 py-1 ring-1 ring-white/20 backdrop-blur-sm">
                  กำหนดชำระ {data.due_date}
                </span>
              )}
            </div>
          </div>

          <div className="text-left lg:text-right">
            <div className="text-sm font-medium text-white/85">ยอดรวมทั้งหมด</div>
            <div className="mt-2 text-3xl font-bold tracking-tight">
              {money(data.total)} บาท
            </div>
            <div
              className={`mt-3 inline-flex rounded-full px-3 py-1.5 text-xs font-medium ${statusPill(
                data
              )}`}
            >
              {displayStatus(data)}
            </div>
          </div>
        </div>

        <div className="absolute -right-16 -top-14 h-52 w-52 rounded-full bg-white/10 blur-sm" />
        <div className="absolute -bottom-20 -right-4 h-64 w-64 rounded-full bg-cyan-300/20 blur-md" />
      </section>

      {/* Content grid */}
      <div className="grid grid-cols-1 gap-5 xl:grid-cols-3">
        {/* Items */}
        <section className="xl:col-span-2 rounded-[28px] border border-white/70 bg-white/85 p-5 shadow-[0_14px_36px_rgba(15,23,42,0.06)] backdrop-blur-sm">
          <div className="mb-4 flex items-center justify-between">
            <div>
              <div className="text-lg font-semibold text-slate-800">รายการค่าใช้จ่าย</div>
              <div className="mt-1 text-sm text-slate-500">
                รายการทั้งหมดภายในใบแจ้งหนี้นี้
              </div>
            </div>

            <div className="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">
              {data.items?.length ?? 0} รายการ
            </div>
          </div>

          {data.items?.length ? (
            <div className="space-y-3">
              {data.items.map((it) => {
                const lineTotal =
                  Number(it.qty ?? 0) * Number(it.unit_price ?? 0);

                return (
                  <div
                    key={it.id}
                    className="rounded-2xl border border-slate-200/80 bg-linear-to-r from-white to-slate-50/70 p-4 shadow-[0_8px_20px_rgba(15,23,42,0.04)]"
                  >
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                      <div className="min-w-0">
                        <div className="font-semibold text-slate-800">
                          {it.description}
                        </div>
                        <div className="mt-1 text-sm text-slate-500">
                          จำนวน {it.qty} × {money(it.unit_price)} บาท
                        </div>
                      </div>

                      <div className="text-left sm:text-right">
                        <div className="text-lg font-bold text-slate-800">
                          {money(lineTotal)} บาท
                        </div>
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>
          ) : (
            <div className="rounded-2xl bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
              ไม่มีรายการ
            </div>
          )}
        </section>

        {/* Summary / totals */}
        <section className="rounded-[28px] border border-white/70 bg-white/85 p-5 shadow-[0_14px_36px_rgba(15,23,42,0.06)] backdrop-blur-sm">
          <div className="text-lg font-semibold text-slate-800">สรุปยอด</div>
          <div className="mt-1 text-sm text-slate-500">ยอดรวมและข้อมูลการชำระเงิน</div>

          <div className="mt-5 space-y-3">
            <SummaryRow label="รวมย่อยจากรายการ" value={`${money(subtotal)} บาท`} />
            <SummaryRow label="ยอดสุทธิจากระบบ" value={`${money(data.total)} บาท`} strong />
            <SummaryRow label="สถานะ" value={displayStatus(data)} badge={statusPill(data)} />
          </div>

          <div className="mt-5 rounded-2xl bg-slate-50 p-4">
            <div className="text-sm text-slate-500">ข้อมูลเพิ่มเติม</div>

            <div className="mt-3 space-y-2 text-sm">
              <InfoRow label="เลขที่ใบแจ้งหนี้" value={data.invoice_no} />
              <InfoRow label="งวด" value={`${data.period_month}/${data.period_year}`} />
              <InfoRow label="ห้อง" value={String(getRoomLabel(data.room))} />
              <InfoRow label="กำหนดชำระ" value={String(data.due_date ?? "-")} />
            </div>
          </div>

          {canUploadSlip && (
            <button
              onClick={() => nav(`/tenant/payments/upload?invoice_id=${data.id}`)}
              className="mt-5 w-full rounded-2xl bg-linear-to-r from-indigo-600 to-blue-600 px-4 py-3 font-medium text-white shadow-[0_12px_26px_rgba(59,130,246,0.22)] transition hover:-translate-y-0.5 hover:shadow-[0_16px_30px_rgba(59,130,246,0.28)]"
            >
              💳 อัปโหลดสลิปชำระเงิน
            </button>
          )}
        </section>
      </div>
    </div>
  );
}

function SummaryRow({
  label,
  value,
  strong,
  badge,
}: {
  label: string;
  value: string;
  strong?: boolean;
  badge?: string;
}) {
  return (
    <div className="flex items-center justify-between gap-3 rounded-2xl border border-slate-200/70 bg-white p-3 shadow-[0_6px_16px_rgba(15,23,42,0.03)]">
      <span className="text-sm text-slate-500">{label}</span>

      {badge ? (
        <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${badge}`}>
          {value}
        </span>
      ) : (
        <span className={strong ? "font-bold text-slate-800" : "font-medium text-slate-700"}>
          {value}
        </span>
      )}
    </div>
  );
}

function InfoRow({ label, value }: { label: string; value: string }) {
  return (
    <div className="flex items-center justify-between gap-3">
      <span className="text-slate-500">{label}</span>
      <span className="font-medium text-slate-700">{value}</span>
    </div>
  );
}