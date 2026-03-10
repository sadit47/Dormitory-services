import { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import {
  tenantDashboardApi,
  type TenantDashboardSummary,
} from "./services/tenantDashboardApi";
import { tenantAnnouncementsApi } from "../announcements/services/tenantAnnouncementsApi";
import { tenantParcelsApi } from "../parcels/services/tenantParcelsApi";

type DashboardAnnouncement = {
  id: number;
  title: string;
  content: string;
  type: "general" | "urgent" | "maintenance" | string;
  is_pinned?: boolean;
  starts_at?: string | null;
};

type DashboardParcel = {
  id: number;
  tracking_no?: string | null;
  courier?: string | null;
  sender_name?: string | null;
  status: "arrived" | "picked_up" | "cancelled" | string;
  received_at?: string | null;
  room?: { code?: string | null; room_no?: string | null } | null;
};

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

function formatMoney(n: any) {
  return Number(n ?? 0).toLocaleString();
}

function formatDateTH(v?: string | null) {
  if (!v) return "-";
  const d = new Date(v);
  if (Number.isNaN(d.getTime())) return String(v);
  return new Intl.DateTimeFormat("th-TH", {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
  }).format(d);
}

function announcementBadgeType(v?: string) {
  if (v === "general") return "bg-sky-50 text-sky-700 ring-1 ring-sky-200";
  if (v === "urgent") return "bg-rose-50 text-rose-700 ring-1 ring-rose-200";
  if (v === "maintenance") return "bg-amber-50 text-amber-800 ring-1 ring-amber-200";
  return "bg-slate-100 text-slate-700 ring-1 ring-slate-200";
}

function announcementTypeLabel(v?: string) {
  if (v === "general") return "ข่าวทั่วไป";
  if (v === "urgent") return "ข่าวด่วน";
  if (v === "maintenance") return "ซ่อมบำรุง";
  return v || "-";
}

function parcelStatusLabel(value?: string) {
  if (!value) return "-";
  if (value === "arrived") return "รอรับ";
  if (value === "picked_up") return "รับแล้ว";
  if (value === "cancelled") return "ยกเลิก";
  return value;
}

function parcelBadgeClass(status?: string) {
  if (status === "arrived") return "bg-amber-50 text-amber-800 ring-1 ring-amber-200";
  if (status === "picked_up") return "bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200";
  if (status === "cancelled") return "bg-rose-50 text-rose-700 ring-1 ring-rose-200";
  return "bg-slate-100 text-slate-700 ring-1 ring-slate-200";
}

function normalizePaged<T>(res: any): T[] {
  if (Array.isArray(res)) return res;
  if (res && Array.isArray(res.data)) return res.data;
  return [];
}

export default function TenantDashboardPage() {
  const navigate = useNavigate();

  const [data, setData] = useState<TenantDashboardSummary | null>(null);
  const [announcements, setAnnouncements] = useState<DashboardAnnouncement[]>([]);
  const [parcels, setParcels] = useState<DashboardParcel[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    setLoading(true);

    Promise.all([
      tenantDashboardApi.summary(),
      tenantAnnouncementsApi.list("", 1, 3),
      tenantParcelsApi.list("", 1, 3),
    ])
      .then(([summary, annRes, parcelRes]) => {
        setData(summary);
        setAnnouncements(normalizePaged<DashboardAnnouncement>(annRes).slice(0, 3));
        setParcels(normalizePaged<DashboardParcel>(parcelRes).slice(0, 3));
      })
      .catch((err) => {
        console.error("TenantDashboard load error:", err);
        setData(null);
        setAnnouncements([]);
        setParcels([]);
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

      {/* ข่าวสาร + พัสดุ */}
      <section className="grid grid-cols-1 gap-5 xl:grid-cols-2">
        {/* ข่าวสาร */}
        <section className="rounded-[28px] border border-white/70 bg-white/85 p-5 shadow-[0_12px_36px_rgba(15,23,42,0.05)] backdrop-blur-sm">
          <div className="mb-4 flex items-center justify-between">
            <div>
              <div className="text-base font-semibold text-slate-800">ข่าวสารล่าสุด</div>
              <div className="mt-1 text-xs text-slate-500">ประกาศล่าสุดจากหอพัก</div>
            </div>

            <button
              onClick={() => navigate("/tenant/announcements")}
              className="text-sm font-medium text-indigo-600 transition hover:text-indigo-700"
            >
              ดูทั้งหมด
            </button>
          </div>

          {announcements.length ? (
            <div className="space-y-3">
              {announcements.map((item) => (
                <div
                  key={item.id}
                  className="rounded-2xl border border-slate-200/80 bg-linear-to-r from-white to-slate-50/70 p-4 shadow-[0_8px_22px_rgba(15,23,42,0.04)] transition hover:-translate-y-0.5 hover:shadow-[0_14px_28px_rgba(15,23,42,0.07)]"
                >
                  <div className="flex flex-wrap items-center gap-2">
                    <div className="font-semibold text-slate-800">{item.title}</div>

                    <span
                      className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${announcementBadgeType(
                        item.type
                      )}`}
                    >
                      {announcementTypeLabel(item.type)}
                    </span>

                    {item.is_pinned ? (
                      <span className="inline-flex rounded-full bg-violet-50 px-2.5 py-1 text-xs font-medium text-violet-700 ring-1 ring-violet-200">
                        ปักหมุด
                      </span>
                    ) : null}
                  </div>

                  <div className="mt-2 line-clamp-2 text-sm text-slate-600">
                    {item.content}
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <div className="rounded-2xl bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
              ยังไม่มีข่าวสาร
            </div>
          )}
        </section>

        {/* พัสดุ */}
        <section className="rounded-[28px] border border-white/70 bg-white/85 p-5 shadow-[0_12px_36px_rgba(15,23,42,0.05)] backdrop-blur-sm">
          <div className="mb-4 flex items-center justify-between">
            <div>
              <div className="text-base font-semibold text-slate-800">พัสดุล่าสุด</div>
              <div className="mt-1 text-xs text-slate-500">ติดตามพัสดุที่เกี่ยวข้องกับห้องของคุณ</div>
            </div>

            <button
              onClick={() => navigate("/tenant/parcels")}
              className="text-sm font-medium text-indigo-600 transition hover:text-indigo-700"
            >
              ดูทั้งหมด
            </button>
          </div>

          {parcels.length ? (
            <div className="space-y-3">
              {parcels.map((item) => (
                <div
                  key={item.id}
                  className="rounded-2xl border border-slate-200/80 bg-linear-to-r from-white to-slate-50/70 p-4 shadow-[0_8px_22px_rgba(15,23,42,0.04)] transition hover:-translate-y-0.5 hover:shadow-[0_14px_28px_rgba(15,23,42,0.07)]"
                >
                  <div className="flex items-start justify-between gap-3">
                    <div className="min-w-0">
                      <div className="font-semibold text-slate-800">
                        {item.tracking_no || `Parcel #${item.id}`}
                      </div>
                      <div className="mt-1 text-sm text-slate-500">
                        ขนส่ง: {item.courier || "-"}
                      </div>
                      <div className="mt-1 text-sm text-slate-500">
                        ผู้ส่ง: {item.sender_name || "-"}
                      </div>
                      <div className="mt-2 text-xs text-slate-400">
                        รับเข้า: {formatDateTH(item.received_at)}
                      </div>
                    </div>

                    <span
                      className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${parcelBadgeClass(
                        item.status
                      )}`}
                    >
                      {parcelStatusLabel(item.status)}
                    </span>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <div className="rounded-2xl bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
              ยังไม่มีพัสดุ
            </div>
          )}
        </section>
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