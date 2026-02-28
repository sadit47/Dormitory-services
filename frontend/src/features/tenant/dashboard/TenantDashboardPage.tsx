import { useEffect, useMemo, useState } from "react";
import { NavLink, useNavigate } from "react-router-dom";
import { tenantDashboardApi, type TenantDashboardSummary } from "./services/tenantDashboardApi";

import { ResponsiveContainer, LineChart, Line, XAxis, YAxis, Tooltip, CartesianGrid } from "recharts";

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

// ✅ แสดงสถานะภาษาไทย (เหมือนหน้า list/detail)
function displayStatus(inv: any) {
  if (inv?.payment_status === "waiting") return "กำลังดำเนินการ";
  if (inv?.status === "paid") return "ชำระแล้ว";
  if (inv?.status === "unpaid" || inv?.status === "partial") return "ยังไม่ชำระ";
  return inv?.status ?? "-";
}

// ✅ สี badge (เหมือนหน้า list/detail)
function statusPill(inv: any) {
  if (inv?.payment_status === "waiting") return "bg-amber-100 text-amber-800";
  if (inv?.status === "paid") return "bg-emerald-100 text-emerald-700";
  if (inv?.status === "unpaid" || inv?.status === "partial") return "bg-rose-100 text-rose-700";
  return "bg-gray-100 text-gray-700";
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
      <div className="flex items-center justify-center h-40">
        <div className="animate-pulse text-gray-500">กำลังโหลดข้อมูล...</div>
      </div>
    );
  }

  if (!data) {
    return (
      <div className="bg-white border rounded-2xl p-5">
        <div className="font-semibold text-gray-800">ไม่พบข้อมูล</div>
        <div className="text-sm text-gray-500 mt-1">กรุณาลองออกจากระบบแล้วเข้าสู่ระบบใหม่</div>
      </div>
    );
  }

  const { summary, user, current_room } = data;

  return (
    <div className="space-y-6 pb-20 sm:pb-0">
      {/* Welcome / Header */}
      <div className="relative overflow-hidden rounded-2xl p-6 text-white shadow-lg bg-linear-to-r from-indigo-600 via-blue-600 to-sky-600">
        <div className="text-sm opacity-90">ยินดีต้อนรับ</div>

        <div className="mt-1 flex items-center gap-2 flex-wrap">
          <div className="text-2xl font-semibold">{user?.name ?? "Tenant"}</div>

          {unpaidCount > 0 && (
            <span className="text-xs bg-rose-500/90 px-2 py-1 rounded-full">
              🔔 ค้างชำระ {unpaidCount} รายการ
            </span>
          )}
        </div>

        <div className="mt-3 text-sm opacity-95">
          ห้องพัก: <span className="font-semibold">{getRoomLabel(current_room)}</span>
        </div>

        <div className="mt-4 flex flex-wrap gap-2">
          <button
            onClick={payNow}
            className="px-4 py-2 rounded-xl bg-white text-gray-900 font-medium shadow hover:opacity-95"
          >
            💳 ชำระเงินทันที
          </button>

          <button
            onClick={() => navigate("/tenant/invoices")}
            className="px-4 py-2 rounded-xl bg-white/15 border border-white/25 text-white hover:bg-white/20"
          >
            ดูใบแจ้งหนี้
          </button>
        </div>

        <div className="absolute -right-16 -top-16 w-52 h-52 rounded-full bg-white/10" />
        <div className="absolute -right-6 -bottom-20 w-60 h-60 rounded-full bg-white/10" />
      </div>

      {/* KPI */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <KpiCard title="ยอดค้างชำระ" value={`${formatMoney(summary.total_due)} บาท`} accent="from-amber-400 to-orange-500" icon="💰" />
        <KpiCard title="บิลค้าง" value={`${summary.unpaid_invoices ?? 0} รายการ`} accent="from-rose-400 to-pink-500" icon="🧾" />
        <KpiCard title="แจ้งซ่อมค้าง" value={`${summary.repair_open ?? 0} รายการ`} accent="from-emerald-400 to-teal-500" icon="🛠️" />
      </div>

      {/* Chart (Recharts) */}
      <section className="bg-white rounded-2xl shadow-sm border p-5">
        <div className="flex items-center justify-between mb-3">
          <div className="font-semibold text-gray-800">📊 ยอดชำระย้อนหลัง</div>
          <div className="text-xs text-gray-500">
            {chart.mode === "monthly" ? "รายเดือน" : chart.mode === "latest" ? "จากรายการชำระล่าสุด" : "-"}
          </div>
        </div>

        {chart.rows.length ? (
          <div className="h-56">
            <ResponsiveContainer width="100%" height="100%">
              <LineChart data={chart.rows}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="label" tick={{ fontSize: 12 }} />
                <YAxis tick={{ fontSize: 12 }} />
                <Tooltip formatter={(value: any) => [`${formatMoney(value)} บาท`, "ยอดชำระ"]} labelFormatter={(label: any) => `ช่วง: ${label}`} />
                <Line type="monotone" dataKey="amount" strokeWidth={2} dot={false} />
              </LineChart>
            </ResponsiveContainer>
          </div>
        ) : (
          <div className="text-sm text-gray-500">ยังไม่มีข้อมูลยอดชำระ</div>
        )}
      </section>

      {/* Latest invoices */}
      <section className="bg-white rounded-2xl shadow-sm border p-5">
        <div className="font-semibold text-gray-800 mb-4">ใบแจ้งหนี้ล่าสุด</div>

        {data.latest_invoices?.length ? (
          <div className="space-y-3">
            {data.latest_invoices.map((inv: any) => (
              <div
                key={inv.id}
                className="flex items-center justify-between border rounded-xl p-4 hover:bg-gray-50 transition"
              >
                <div>
                  <div className="font-medium">{inv.invoice_no}</div>
                  <div className="text-sm text-gray-500">งวด {inv.period_month}/{inv.period_year}</div>
                  {inv.due_date && <div className="text-xs text-gray-500 mt-1">กำหนดชำระ: {inv.due_date}</div>}
                </div>

                <div className="text-right">
                  <div className="font-semibold">{formatMoney(inv.total)} บาท</div>
                  <div className={`text-xs mt-1 px-2 py-1 rounded-full inline-block ${statusPill(inv)}`}>
                    {displayStatus(inv)}
                  </div>
                </div>
              </div>
            ))}
          </div>
        ) : (
          <div className="text-sm text-gray-500">ยังไม่มีใบแจ้งหนี้</div>
        )}
      </section>

      {/* Bottom Nav (mobile) */}
      <BottomNav />
    </div>
  );
}

function KpiCard({ title, value, accent, icon }: { title: string; value: string; accent: string; icon: string; }) {
  return (
    <div className="bg-white rounded-2xl shadow-sm border p-5 relative overflow-hidden">
      <div className="flex items-start justify-between">
        <div>
          <div className="text-sm text-gray-500">{title}</div>
          <div className="text-2xl font-semibold mt-2">{value}</div>
        </div>
        <div className="text-2xl">{icon}</div>
      </div>

      <div className={`absolute -bottom-10 -right-10 w-40 h-40 rounded-full opacity-20 bg-linear-to-r ${accent}`} />
    </div>
  );
}

function BottomNav() {
  const items = [
    { to: "/tenant/dashboard", label: "Dashboard", icon: "🏠" },
    { to: "/tenant/invoices", label: "ใบแจ้งหนี้", icon: "🧾" },
    { to: "/tenant/payments/upload", label: "ชำระเงิน", icon: "💳" },
    { to: "/tenant/profile", label: "โปรไฟล์", icon: "👤" },
  ];

  return (
    <div className="sm:hidden fixed bottom-0 left-0 right-0 bg-white border-t">
      <div className="max-w-6xl mx-auto grid grid-cols-4">
        {items.map((x) => (
          <NavLink
            key={x.to}
            to={x.to}
            className={({ isActive }) =>
              `py-2 flex flex-col items-center gap-1 text-xs ${
                isActive ? "text-indigo-600 font-semibold" : "text-gray-600"
              }`
            }
          >
            <div className="text-lg">{x.icon}</div>
            <div>{x.label}</div>
          </NavLink>
        ))}
      </div>
    </div>
  );
}