import { useEffect, useMemo, useState } from "react";
import { useNavigate } from "react-router-dom";
import { tenantInvoicesApi, type TenantInvoiceListItem, type Paginated } from "./services/tenantInvoicesApi";

function money(n: any) {
  return Number(n ?? 0).toLocaleString();
}

// ✅ แสดงข้อความสถานะเป็นภาษาไทย ตาม requirement
function displayStatus(inv: TenantInvoiceListItem) {
  // ถ้ามีการส่งสลิปแล้ว (รอตรวจ)
  if (inv.payment_status === "waiting") return "กำลังดำเนินการ";

  // สถานะของใบแจ้งหนี้
  if (inv.status === "paid") return "ชำระแล้ว";
  if (inv.status === "unpaid" || inv.status === "partial") return "ยังไม่ชำระ";

  return inv.status ?? "-";
}

// ✅ สี badge ตามสถานะ
function statusPill(inv: TenantInvoiceListItem) {
  if (inv.payment_status === "waiting") return "bg-amber-100 text-amber-800";
  if (inv.status === "paid") return "bg-emerald-100 text-emerald-700";
  if (inv.status === "unpaid" || inv.status === "partial") return "bg-rose-100 text-rose-700";
  return "bg-gray-100 text-gray-700";
}

export default function TenantInvoicesPage() {
  const nav = useNavigate();
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(10);

  // ✅ filter แบบไทย
  const [status, setStatus] = useState<string>("all"); // all | unpaid | waiting | paid

  const [res, setRes] = useState<Paginated<TenantInvoiceListItem> | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    setLoading(true);
    tenantInvoicesApi
      .list(page, perPage)
      .then(setRes)
      .finally(() => setLoading(false));
  }, [page, perPage]);

  const rows = useMemo(() => {
    const data = res?.data ?? [];
    if (status === "all") return data;

    // ✅ กำลังดำเนินการ = มี payment waiting
    if (status === "waiting") return data.filter((x) => x.payment_status === "waiting");

    // ✅ ชำระแล้ว = invoice paid
    if (status === "paid") return data.filter((x) => x.status === "paid");

    // ✅ ยังไม่ชำระ = unpaid/partial และไม่อยู่ใน waiting
    if (status === "unpaid") {
      return data.filter(
        (x) => (x.status === "unpaid" || x.status === "partial") && x.payment_status !== "waiting"
      );
    }

    return data;
  }, [res, status]);

  return (
    <div className="space-y-4">
      {/* Header */}
      <div className="flex items-end justify-between gap-3 flex-wrap">
        <div>
          <div className="text-xl font-semibold text-gray-900">ใบแจ้งหนี้</div>
          <div className="text-sm text-gray-500">ดูรายการใบแจ้งหนี้ และเปิดดูรายละเอียด</div>
        </div>

        <div className="flex gap-2 items-center">
          <select
            value={status}
            onChange={(e) => {
              setPage(1);
              setStatus(e.target.value);
            }}
            className="border rounded-xl px-3 py-2 text-sm bg-white"
          >
            <option value="all">ทั้งหมด</option>
            <option value="unpaid">ยังไม่ชำระ</option>
            <option value="waiting">กำลังดำเนินการ</option>
            <option value="paid">ชำระแล้ว</option>
          </select>

          <select
            value={perPage}
            onChange={(e) => {
              setPage(1);
              setPerPage(Number(e.target.value));
            }}
            className="border rounded-xl px-3 py-2 text-sm bg-white"
          >
            <option value={10}>10/หน้า</option>
            <option value={20}>20/หน้า</option>
            <option value={50}>50/หน้า</option>
          </select>
        </div>
      </div>

      {/* List */}
      <div className="bg-white border rounded-2xl shadow-sm overflow-hidden">
        {loading ? (
          <div className="p-6 text-gray-500">กำลังโหลด...</div>
        ) : rows.length ? (
          <div className="divide-y">
            {rows.map((inv) => (
              <button
                key={inv.id}
                onClick={() => nav(`/tenant/invoices/${inv.id}`)}
                className="w-full text-left p-4 hover:bg-gray-50 transition flex items-center justify-between gap-4"
              >
                <div>
                  <div className="font-medium text-gray-900">{inv.invoice_no}</div>
                  <div className="text-sm text-gray-500">
                    งวด {inv.period_month}/{inv.period_year} 
                    {inv.due_date ? ` • กำหนดชำระ ${inv.due_date}` : ""}
                  </div>
                </div>

                <div className="text-right shrink-0">
                  <div className="font-semibold">{money(inv.total)} บาท</div>
                  <div className={`text-xs mt-1 px-2 py-1 rounded-full inline-block ${statusPill(inv)}`}>
                    {displayStatus(inv)}
                  </div>
                </div>
              </button>
            ))}
          </div>
        ) : (
          <div className="p-6 text-gray-500">ไม่พบใบแจ้งหนี้</div>
        )}
      </div>

      {/* Pagination */}
      {res && res.last_page > 1 && (
        <div className="flex items-center justify-between">
          <div className="text-sm text-gray-500">
            หน้า {res.current_page} / {res.last_page} • ทั้งหมด {res.total} รายการ
          </div>

          <div className="flex gap-2">
            <button
              disabled={page <= 1}
              onClick={() => setPage((p) => Math.max(1, p - 1))}
              className="px-3 py-2 rounded-xl border bg-white disabled:opacity-50"
            >
              ก่อนหน้า
            </button>
            <button
              disabled={page >= res.last_page}
              onClick={() => setPage((p) => Math.min(res.last_page, p + 1))}
              className="px-3 py-2 rounded-xl border bg-white disabled:opacity-50"
            >
              ถัดไป
            </button>
          </div>
        </div>
      )}
    </div>
  );
}