import { useEffect, useMemo, useState } from "react";
import { useNavigate, useParams } from "react-router-dom";
import { tenantInvoicesApi, type TenantInvoiceDetail } from "./services/tenantInvoicesApi";

function money(n: any) {
  return Number(n ?? 0).toLocaleString();
}

// ✅ แสดงข้อความสถานะภาษาไทย
function displayStatus(inv: any) {
  if (inv.payment_status === "waiting") return "กำลังดำเนินการ";
  if (inv.status === "paid") return "ชำระแล้ว";
  if (inv.status === "unpaid" || inv.status === "partial") return "ยังไม่ชำระ";
  return inv.status ?? "-";
}

// ✅ สี badge
function statusPill(inv: any) {
  if (inv.payment_status === "waiting") return "bg-amber-100 text-amber-800";
  if (inv.status === "paid") return "bg-emerald-100 text-emerald-700";
  if (inv.status === "unpaid" || inv.status === "partial") return "bg-rose-100 text-rose-700";
  return "bg-gray-100 text-gray-700";
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

    const res = await tenantInvoicesApi.openPdfBlob(data.id);
    const blob = new Blob([res.data], { type: "application/pdf" });
    const url = window.URL.createObjectURL(blob);

    // เปิดแท็บใหม่เพื่อดู PDF
    window.open(url, "_blank", "noopener,noreferrer");

    // กัน memory leak (หน่วงนิดให้ browser เปิดก่อน)
    setTimeout(() => window.URL.revokeObjectURL(url), 60_000);
  };


  if (loading) return <div className="p-4 text-gray-500">กำลังโหลด...</div>;
  if (!data) return <div className="p-4 text-gray-500">ไม่พบข้อมูล</div>;

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between gap-3 flex-wrap">
        <button onClick={() => nav(-1)} className="px-3 py-2 rounded-xl border bg-white">
          ← กลับ
        </button>

        <div className="flex gap-2 flex-wrap">
          <button onClick={openPdf} className="px-4 py-2 rounded-xl border bg-white">
            👁️ เปิดดู PDF
          </button>

          {(data.status === "unpaid" || data.status === "partial") && (
            <button
              onClick={() => nav(`/tenant/payments/upload?invoice_id=${data.id}`)}
              className="px-4 py-2 rounded-xl bg-indigo-600 text-white font-medium"
            >
              💳 อัปโหลดสลิปชำระเงิน
            </button>
          )}
        </div>
      </div>

      {/* Summary */}
      <div className="bg-white border rounded-2xl shadow-sm p-5">
        <div className="flex items-start justify-between gap-3">
          <div>
            <div className="text-lg font-semibold">
              {data.invoice_no}
            </div>
            <div className="text-sm text-gray-500">
              งวด {data.period_month}/{data.period_year} • {data.type}
              {data.due_date ? ` • กำหนดชำระ ${data.due_date}` : ""}
            </div>
            {data.room && (
              <div className="text-sm text-gray-500 mt-1">
                ห้อง:{" "}
                {data.room.room_no ??
                  data.room.name ??
                  data.room.code ??
                  data.room.id}
              </div>
            )}
          </div>

          <div className="text-right">
            <div className="text-xl font-semibold">
              {money(data.total)} บาท
            </div>

            {/* ✅ แสดงสถานะภาษาไทย */}
            <div
              className={`text-xs mt-2 px-2 py-1 rounded-full inline-block ${statusPill(
                data
              )}`}
            >
              {displayStatus(data)}
            </div>
          </div>
        </div>
      </div>

      {/* Items */}
      <div className="bg-white border rounded-2xl shadow-sm p-5">
        <div className="font-semibold mb-3">รายการ</div>

        {data.items?.length ? (
          <div className="divide-y">
            {data.items.map((it) => {
              const lineTotal =
                Number(it.qty ?? 0) *
                Number(it.unit_price ?? 0);
              return (
                <div
                  key={it.id}
                  className="py-3 flex items-center justify-between gap-3"
                >
                  <div>
                    <div className="font-medium">
                      {it.description}
                    </div>
                    <div className="text-sm text-gray-500">
                      {it.qty} × {money(it.unit_price)} บาท
                    </div>
                  </div>
                  <div className="font-semibold">
                    {money(lineTotal)} บาท
                  </div>
                </div>
              );
            })}
          </div>
        ) : (
          <div className="text-sm text-gray-500">
            ไม่มีรายการ
          </div>
        )}

        {/* Totals */}
        <div className="mt-4 border-t pt-4 space-y-1 text-sm">
          <div className="flex justify-between">
            <span className="text-gray-600">
              รวมย่อย (คำนวณจากรายการ)
            </span>
            <span className="font-medium">
              {money(subtotal)} บาท
            </span>
          </div>
          <div className="flex justify-between">
            <span className="text-gray-600">
              ยอดสุทธิ (จากระบบ)
            </span>
            <span className="font-semibold">
              {money(data.total)} บาท
            </span>
          </div>
        </div>
      </div>
    </div>
  );
}