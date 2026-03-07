import { useEffect, useMemo, useState } from "react";
import { useNavigate } from "react-router-dom";
import {
  tenantInvoicesApi,
  type TenantInvoiceListItem,
  type Paginated,
} from "./services/tenantInvoicesApi";

function money(n: any) {
  return Number(n ?? 0).toLocaleString();
}

function displayStatus(inv: TenantInvoiceListItem) {
  if (inv.payment_status === "waiting") return "กำลังดำเนินการ";
  if (inv.status === "paid") return "ชำระแล้ว";
  if (inv.status === "unpaid" || inv.status === "partial") return "ยังไม่ชำระ";
  return inv.status ?? "-";
}

function statusPill(inv: TenantInvoiceListItem) {
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

export default function TenantInvoicesPage() {
  const nav = useNavigate();
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(10);
  const [status, setStatus] = useState<string>("all");
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

    if (status === "waiting") {
      return data.filter((x) => x.payment_status === "waiting");
    }

    if (status === "paid") {
      return data.filter((x) => x.status === "paid");
    }

    if (status === "unpaid") {
      return data.filter(
        (x) =>
          (x.status === "unpaid" || x.status === "partial") &&
          x.payment_status !== "waiting"
      );
    }

    return data;
  }, [res, status]);

  const counts = useMemo(() => {
    const data = res?.data ?? [];
    return {
      all: data.length,
      unpaid: data.filter(
        (x) =>
          (x.status === "unpaid" || x.status === "partial") &&
          x.payment_status !== "waiting"
      ).length,
      waiting: data.filter((x) => x.payment_status === "waiting").length,
      paid: data.filter((x) => x.status === "paid").length,
    };
  }, [res]);

  return (
    <div className="space-y-5">
      {/* Header */}
      <section className="rounded-[28px] border border-white/70 bg-white/85 p-5 shadow-[0_14px_36px_rgba(15,23,42,0.06)] backdrop-blur-sm">
        <div className="flex flex-wrap items-start justify-between gap-4">
          <div>
            <div className="text-2xl font-bold tracking-tight text-slate-800">
              ใบแจ้งหนี้
            </div>
            <div className="mt-1 text-sm text-slate-500">
              ดูรายการใบแจ้งหนี้ เลือกเปิดดูรายละเอียด และตรวจสอบสถานะการชำระเงิน
            </div>
          </div>

          <div className="flex flex-wrap items-center gap-2">
            <select
              value={status}
              onChange={(e) => {
                setPage(1);
                setStatus(e.target.value);
              }}
              className="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 shadow-[0_8px_20px_rgba(15,23,42,0.04)] outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
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
              className="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 shadow-[0_8px_20px_rgba(15,23,42,0.04)] outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
            >
              <option value={10}>10/หน้า</option>
              <option value={20}>20/หน้า</option>
              <option value={50}>50/หน้า</option>
            </select>
          </div>
        </div>

        {/* Quick stats */}
        <div className="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
          <MiniStat
            label="ทั้งหมด"
            value={`${counts.all}`}
            active={status === "all"}
            onClick={() => {
              setPage(1);
              setStatus("all");
            }}
          />
          <MiniStat
            label="ยังไม่ชำระ"
            value={`${counts.unpaid}`}
            active={status === "unpaid"}
            onClick={() => {
              setPage(1);
              setStatus("unpaid");
            }}
          />
          <MiniStat
            label="กำลังดำเนินการ"
            value={`${counts.waiting}`}
            active={status === "waiting"}
            onClick={() => {
              setPage(1);
              setStatus("waiting");
            }}
          />
          <MiniStat
            label="ชำระแล้ว"
            value={`${counts.paid}`}
            active={status === "paid"}
            onClick={() => {
              setPage(1);
              setStatus("paid");
            }}
          />
        </div>
      </section>

      {/* List */}
      <section className="overflow-hidden rounded-[28px] border border-white/70 bg-white/85 shadow-[0_14px_36px_rgba(15,23,42,0.06)] backdrop-blur-sm">
        {loading ? (
          <div className="p-6">
            <div className="rounded-2xl bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
              กำลังโหลด...
            </div>
          </div>
        ) : rows.length ? (
          <div className="p-3 sm:p-4">
            <div className="space-y-3">
              {rows.map((inv) => (
                <button
                  key={inv.id}
                  onClick={() => nav(`/tenant/invoices/${inv.id}`)}
                  className="group w-full rounded-3xl border border-slate-200/80 bg-linear-to-r from-white to-slate-50/70 p-4 text-left shadow-[0_10px_24px_rgba(15,23,42,0.04)] transition-all duration-200 hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-[0_16px_30px_rgba(15,23,42,0.08)]"
                >
                  <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div className="min-w-0">
                      <div className="flex flex-wrap items-center gap-2">
                        <div className="text-lg font-semibold tracking-tight text-slate-800">
                          {inv.invoice_no}
                        </div>
                        <span className="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-medium text-slate-600">
                          งวด {inv.period_month}/{inv.period_year}
                        </span>
                      </div>

                      <div className="mt-2 text-sm text-slate-500">
                        {inv.due_date
                          ? `กำหนดชำระ ${inv.due_date}`
                          : "ไม่มีวันกำหนดชำระ"}
                      </div>
                    </div>

                    <div className="shrink-0 text-left sm:text-right">
                      <div className="text-2xl font-bold tracking-tight text-slate-800">
                        {money(inv.total)}{" "}
                        <span className="text-lg font-semibold text-slate-700">บาท</span>
                      </div>
                      <div
                        className={`mt-2 inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${statusPill(
                          inv
                        )}`}
                      >
                        {displayStatus(inv)}
                      </div>
                    </div>
                  </div>

                  <div className="mt-4 flex items-center justify-between border-t border-slate-100 pt-3 text-sm">
                    <span className="text-slate-400">กดเพื่อดูรายละเอียดใบแจ้งหนี้</span>
                    <span className="font-medium text-indigo-600 transition group-hover:translate-x-0.5">
                      ดูรายละเอียด →
                    </span>
                  </div>
                </button>
              ))}
            </div>
          </div>
        ) : (
          <div className="p-6">
            <div className="rounded-2xl bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
              ไม่พบใบแจ้งหนี้
            </div>
          </div>
        )}
      </section>

      {/* Pagination */}
      {res && res.last_page > 1 && (
        <section className="rounded-3xl border border-white/70 bg-white/85 p-4 shadow-[0_12px_30px_rgba(15,23,42,0.05)] backdrop-blur-sm">
          <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div className="text-sm text-slate-500">
              หน้า {res.current_page} / {res.last_page} • ทั้งหมด {res.total} รายการ
            </div>

            <div className="flex gap-2">
              <button
                disabled={page <= 1}
                onClick={() => setPage((p) => Math.max(1, p - 1))}
                className="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-[0_6px_16px_rgba(15,23,42,0.04)] transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
              >
                ก่อนหน้า
              </button>

              <button
                disabled={page >= res.last_page}
                onClick={() => setPage((p) => Math.min(res.last_page, p + 1))}
                className="rounded-2xl bg-slate-900 px-4 py-2 text-sm font-medium text-white shadow-[0_8px_18px_rgba(15,23,42,0.14)] transition hover:-translate-y-0.5 hover:shadow-[0_12px_24px_rgba(15,23,42,0.18)] disabled:cursor-not-allowed disabled:opacity-50"
              >
                ถัดไป
              </button>
            </div>
          </div>
        </section>
      )}
    </div>
  );
}

function MiniStat({
  label,
  value,
  active,
  onClick,
}: {
  label: string;
  value: string;
  active?: boolean;
  onClick?: () => void;
}) {
  return (
    <button
      onClick={onClick}
      className={[
        "rounded-2xl border p-4 text-left transition-all",
        active
          ? "border-indigo-200 bg-indigo-50/80 shadow-[0_10px_22px_rgba(99,102,241,0.10)]"
          : "border-slate-200/80 bg-linear-to-r from-white to-slate-50/70 shadow-[0_8px_20px_rgba(15,23,42,0.04)] hover:border-slate-300 hover:shadow-[0_12px_24px_rgba(15,23,42,0.06)]",
      ].join(" ")}
    >
      <div className="text-sm text-slate-500">{label}</div>
      <div className="mt-2 text-2xl font-bold tracking-tight text-slate-800">
        {value}
      </div>
    </button>
  );
}