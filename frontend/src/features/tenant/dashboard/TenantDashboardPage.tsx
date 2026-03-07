import { useEffect, useMemo, useState } from "react";
import { useNavigate } from "react-router-dom";
import {
  ResponsiveContainer,
  LineChart,
  Line,
  XAxis,
  YAxis,
  Tooltip,
  CartesianGrid,
} from "recharts";
import {
  tenantDashboardApi,
  type TenantDashboardSummary,
} from "./services/tenantDashboardApi";

function getRoomLabel(room: any) {
  if (!room) return "-";
  return (
    room.room_no ??
    room.room_number ??
    room.number ??
    room.code ??
    room.name ??
    room.title ??
    room.label ??
    (room.id ? `Room #${room.id}` : "-")
  );
}

function displayStatus(inv: any) {
  if (inv?.payment_status === "waiting") return "กำลังดำเนินการ";
  if (inv?.status === "paid") return "ชำระแล้ว";
  if (inv?.status === "unpaid" || inv?.status === "partial") return "ยังไม่ชำระ";
  return inv?.status ?? "-";
}

function statusPill(inv: any) {
  if (inv?.payment_status === "waiting") {
    return "bg-amber-50 text-amber-700 ring-1 ring-amber-200";
  }
  if (inv?.status === "paid") {
    return "bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200";
  }
  if (inv?.status === "unpaid" || inv?.status === "partial") {
    return "bg-rose-50 text-rose-700 ring-1 ring-rose-200";
  }
  return "bg-slate-100 text-slate-600 ring-1 ring-slate-200";
}

function formatMoney(n: any) {
  return Number(n ?? 0).toLocaleString();
}

export default function TenantDashboardPage() {
  const navigate = useNavigate();
  const [data, setData] = useState<TenantDashboardSummary | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    setLoading(true);
    tenantDashboardApi
      .summary()
      .then(setData)
      .catch((err) => {
        console.error("TenantDashboard summary error:", err);
        setData(null);
      })
      .finally(() => setLoading(false));
  }, []);

  const unpaidCount = data?.summary?.unpaid_invoices ?? 0;

  const payNow = () => {
    const first = (data as any)?.recent_unpaid?.[0];
    if (first?.id) {
      navigate(`/tenant/payments/upload?invoice_id=${first.id}`);
      return;
    }
    navigate("/tenant/invoices");
  };

  const chart = useMemo(() => {
    if (!data) return { rows: [], mode: "none" as const };

    const paidHistory = (data as any).paid_history;
    if (Array.isArray(paidHistory) && paidHistory.length) {
      return {
        mode: "monthly" as const,
        rows: paidHistory.map((x: any) => ({
          label: String(x.label ?? ""),
          amount: Number(x.amount ?? 0),
        })),
      };
    }

    const paid = (data.latest_invoices ?? []).filter((x: any) => x.status === "paid");
    return {
      mode: "latest" as const,
      rows: paid.map((x: any) => ({
        label: String(x.invoice_no ?? `${x.period_month}/${x.period_year}`),
        amount: Number(x.total ?? 0),
      })),
    };
  }, [data]);

  if (loading) {
    return (
      <div className="flex h-56 items-center justify-center">
        <div className="rounded-2xl bg-white/90 px-5 py-3 text-slate-500 shadow-[0_10px_30px_rgba(15,23,42,0.06)]">
          กำลังโหลดข้อมูล...
        </div>
      </div>
    );
  }

  if (!data) {
    return (
      <div className="rounded-[28px] border border-white/70 bg-white/90 p-6 shadow-[0_12px_40px_rgba(15,23,42,0.06)]">
        <div className="font-semibold text-slate-800">ไม่พบข้อมูล</div>
        <div className="mt-1 text-sm text-slate-500">
          กรุณาลองออกจากระบบแล้วเข้าสู่ระบบใหม่
        </div>
      </div>
    );
  }

  const { summary, user, current_room } = data;

  return (
    <div className="space-y-6">
      {/* Welcome Header */}
      <section className="relative overflow-hidden rounded-[28px] border border-white/40 bg-linear-to-r from-indigo-500 via-blue-500 to-cyan-500 p-6 text-white shadow-[0_18px_50px_rgba(59,130,246,0.22)]">
        <div className="relative z-10">
          <div className="text-sm font-medium text-white/85">ยินดีต้อนรับ</div>

          <div className="mt-2 flex flex-wrap items-center gap-2">
            <div className="text-2xl font-bold tracking-tight">
              {user?.name ?? "Tenant"}
            </div>

            {unpaidCount > 0 && (
              <span className="rounded-full bg-rose-500/90 px-3 py-1 text-xs font-medium text-white shadow-md">
                🔔 ค้างชำระ {unpaidCount} รายการ
              </span>
            )}
          </div>

          <div className="mt-3 inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-sm backdrop-blur-sm ring-1 ring-white/20">
            ห้องพัก: <span className="ml-1 font-semibold">{getRoomLabel(current_room)}</span>
          </div>

          <div className="mt-5 flex flex-wrap gap-3">
            <button
              onClick={payNow}
              className="rounded-2xl bg-white px-4 py-2.5 font-medium text-slate-800 shadow-[0_8px_24px_rgba(255,255,255,0.28)] transition hover:-translate-y-0.5 hover:shadow-[0_14px_30px_rgba(255,255,255,0.32)]"
            >
              💳 ชำระเงินทันที
            </button>

            <button
              onClick={() => navigate("/tenant/invoices")}
              className="rounded-2xl border border-white/25 bg-white/10 px-4 py-2.5 font-medium text-white backdrop-blur-sm transition hover:bg-white/20"
            >
              ดูใบแจ้งหนี้
            </button>
          </div>
        </div>

        <div className="absolute -right-16 -top-14 h-52 w-52 rounded-full bg-white/10 blur-sm" />
        <div className="absolute -bottom-20 -right-4 h-64 w-64 rounded-full bg-cyan-300/20 blur-md" />
        <div className="absolute left-1/3 top-0 h-32 w-32 rounded-full bg-white/10 blur-2xl" />
      </section>

      {/* KPI */}
      <section className="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <KpiCard
          title="ยอดค้างชำระ"
          value={`${formatMoney(summary.total_due)} บาท`}
          accent="from-amber-300 via-orange-300 to-amber-400"
          icon="💰"
        />
        <KpiCard
          title="บิลค้าง"
          value={`${summary.unpaid_invoices ?? 0} รายการ`}
          accent="from-rose-300 via-pink-300 to-fuchsia-300"
          icon="🧾"
        />
        <KpiCard
          title="แจ้งซ่อมค้าง"
          value={`${summary.repair_open ?? 0} รายการ`}
          accent="from-emerald-300 via-teal-300 to-cyan-300"
          icon="🛠️"
        />
      </section>

      {/* Chart */}
      <section className="rounded-[28px] border border-white/70 bg-white/85 p-5 shadow-[0_12px_36px_rgba(15,23,42,0.05)] backdrop-blur-sm">
        <div className="mb-4 flex items-center justify-between">
          <div>
            <div className="text-base font-semibold text-slate-800">ยอดชำระย้อนหลัง</div>
            <div className="mt-1 text-xs text-slate-500">
              {chart.mode === "monthly"
                ? "สรุปรายเดือน"
                : chart.mode === "latest"
                ? "จากรายการชำระล่าสุด"
                : "-"}
            </div>
          </div>

          <div className="rounded-full bg-sky-50 px-3 py-1 text-xs font-medium text-sky-700 ring-1 ring-sky-100">
            📈 Payment Trend
          </div>
        </div>

        {chart.rows.length ? (
          <div className="h-64 rounded-2xl bg-linear-to-b from-slate-50 to-white p-3">
            <ResponsiveContainer width="100%" height="100%">
              <LineChart data={chart.rows}>
                <CartesianGrid strokeDasharray="3 3" stroke="#e2e8f0" />
                <XAxis dataKey="label" tick={{ fontSize: 12, fill: "#64748b" }} />
                <YAxis tick={{ fontSize: 12, fill: "#64748b" }} />
                <Tooltip
                  contentStyle={{
                    borderRadius: "16px",
                    border: "1px solid #e2e8f0",
                    boxShadow: "0 12px 30px rgba(15,23,42,0.08)",
                  }}
                  formatter={(value: any) => [`${formatMoney(value)} บาท`, "ยอดชำระ"]}
                  labelFormatter={(label: any) => `ช่วง: ${label}`}
                />
                <Line
                  type="monotone"
                  dataKey="amount"
                  stroke="#4f8df7"
                  strokeWidth={3}
                  dot={{ r: 3, fill: "#4f8df7" }}
                  activeDot={{ r: 5 }}
                />
              </LineChart>
            </ResponsiveContainer>
          </div>
        ) : (
          <div className="rounded-2xl bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
            ยังไม่มีข้อมูลยอดชำระ
          </div>
        )}
      </section>

      {/* Latest Invoices */}
      <section className="rounded-[28px] border border-white/70 bg-white/85 p-5 shadow-[0_12px_36px_rgba(15,23,42,0.05)] backdrop-blur-sm">
        <div className="mb-4 flex items-center justify-between">
          <div className="text-base font-semibold text-slate-800">ใบแจ้งหนี้ล่าสุด</div>
          <button
            onClick={() => navigate("/tenant/invoices")}
            className="text-sm font-medium text-indigo-600 transition hover:text-indigo-700"
          >
            ดูทั้งหมด
          </button>
        </div>

        {data.latest_invoices?.length ? (
          <div className="space-y-3">
            {data.latest_invoices.map((inv: any) => (
              <div
                key={inv.id}
                className="group rounded-2xl border border-slate-200/80 bg-linear-to-r from-white to-slate-50/70 p-4 shadow-[0_8px_22px_rgba(15,23,42,0.04)] transition hover:-translate-y-0.5 hover:shadow-[0_14px_28px_rgba(15,23,42,0.07)]"
              >
                <div className="flex items-center justify-between gap-4">
                  <div className="min-w-0">
                    <div className="font-semibold text-slate-800">{inv.invoice_no}</div>
                    <div className="mt-1 text-sm text-slate-500">
                      งวด {inv.period_month}/{inv.period_year}
                    </div>
                    {inv.due_date && (
                      <div className="mt-1 text-xs text-slate-400">
                        กำหนดชำระ: {inv.due_date}
                      </div>
                    )}
                  </div>

                  <div className="text-right">
                    <div className="text-lg font-bold text-slate-800">
                      {formatMoney(inv.total)} บาท
                    </div>
                    <div
                      className={`mt-2 inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${statusPill(inv)}`}
                    >
                      {displayStatus(inv)}
                    </div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        ) : (
          <div className="rounded-2xl bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
            ยังไม่มีใบแจ้งหนี้
          </div>
        )}
      </section>
    </div>
  );
}

function KpiCard({
  title,
  value,
  accent,
  icon,
}: {
  title: string;
  value: string;
  accent: string;
  icon: string;
}) {
  return (
    <div className="relative overflow-hidden rounded-3xl border border-white/80 bg-white/90 p-5 shadow-[0_12px_32px_rgba(15,23,42,0.05)] backdrop-blur-sm transition hover:-translate-y-0.5 hover:shadow-[0_16px_36px_rgba(15,23,42,0.08)]">
      <div className="relative z-10 flex items-start justify-between">
        <div>
          <div className="text-sm font-medium text-slate-500">{title}</div>
          <div className="mt-2 text-2xl font-bold tracking-tight text-slate-800">
            {value}
          </div>
        </div>

        <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-50 text-2xl shadow-inner">
          {icon}
        </div>
      </div>

      <div
        className={`absolute -bottom-14 -right-10 h-36 w-36 rounded-full bg-linear-to-br ${accent} opacity-25 blur-sm`}
      />
      <div className="absolute inset-x-0 top-0 h-1 bg-linear-to-r from-transparent via-slate-200/60 to-transparent" />
    </div>
  );
}