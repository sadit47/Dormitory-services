import { useEffect, useMemo, useState } from "react";
import { tenantAnnouncementsApi } from "./services/tenantAnnouncementsApi";

type AnnouncementRow = {
  id: number;
  title: string;
  content: string;
  type: "general" | "urgent" | "maintenance" | string;
  status: "draft" | "published" | "expired" | string;
  is_pinned?: boolean;
  starts_at?: string | null;
  ends_at?: string | null;
};

type Paged<T> = {
  current_page?: number;
  data?: T[];
  total?: number;
  per_page?: number;
  last_page?: number;
};

function isPaged<T>(x: any): x is Paged<T> {
  return x && typeof x === "object" && "data" in x && Array.isArray((x as any).data);
}

function badgeType(v?: string) {
  if (v === "general") return "bg-sky-50 text-sky-700 ring-1 ring-sky-200";
  if (v === "urgent") return "bg-rose-50 text-rose-700 ring-1 ring-rose-200";
  if (v === "maintenance") return "bg-amber-50 text-amber-800 ring-1 ring-amber-200";
  return "bg-slate-100 text-slate-700 ring-1 ring-slate-200";
}

function typeLabel(v?: string) {
  if (v === "general") return "ข่าวทั่วไป";
  if (v === "urgent") return "ข่าวด่วน";
  if (v === "maintenance") return "ซ่อมบำรุง";
  return v || "-";
}

export default function TenantAnnouncementsPage() {
  const [urgentRows, setUrgentRows] = useState<AnnouncementRow[]>([]);
  const [rows, setRows] = useState<AnnouncementRow[]>([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(false);
  const [type, setType] = useState("");

  const [page, setPage] = useState(1);
  const perPage = 12;
  const totalPages = useMemo(() => Math.max(1, Math.ceil(total / perPage)), [total]);

  const [okMsg, setOkMsg] = useState("");
  const [errMsg, setErrMsg] = useState("");

  const normalize = (res: any) => {
    if (Array.isArray(res)) return { data: res as AnnouncementRow[], total: res.length };
    if (isPaged<AnnouncementRow>(res)) {
      return { data: res.data ?? [], total: res.total ?? (res.data?.length ?? 0) };
    }
    if (res && typeof res === "object" && Array.isArray(res.data)) {
      return { data: res.data as AnnouncementRow[], total: res.total ?? res.data.length };
    }
    return { data: [] as AnnouncementRow[], total: 0 };
  };

  const loadUrgent = async () => {
    try {
      const res = await tenantAnnouncementsApi.urgentActive();
      const list = Array.isArray(res) ? res : res?.data ?? [];
      setUrgentRows(list);
    } catch (e) {
      console.error(e);
      setUrgentRows([]);
    }
  };

  const loadList = async (targetPage = page) => {
    setLoading(true);
    setErrMsg("");
    try {
      const res = await tenantAnnouncementsApi.list(type, targetPage, perPage);
      const norm = normalize(res);
      setRows(norm.data);
      setTotal(norm.total);
    } catch (e) {
      console.error(e);
      setRows([]);
      setTotal(0);
      setErrMsg("โหลดรายการประกาศไม่สำเร็จ");
    } finally {
      setLoading(false);
    }
  };

  const markRead = async (id: number) => {
    setOkMsg("");
    setErrMsg("");

    try {
      await tenantAnnouncementsApi.read(id);
      setOkMsg("บันทึกว่าอ่านแล้วเรียบร้อย");
    } catch (e: any) {
      console.error(e);
      setErrMsg(e?.response?.data?.message ?? "บันทึกไม่สำเร็จ");
    }
  };

  useEffect(() => {
    loadUrgent();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  useEffect(() => {
    loadList(page);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [page, type]);

  const counts = useMemo(() => {
    return {
      all: rows.length,
      urgent: rows.filter((x) => x.type === "urgent").length,
      general: rows.filter((x) => x.type === "general").length,
      maintenance: rows.filter((x) => x.type === "maintenance").length,
    };
  }, [rows]);

  return (
    <div className="space-y-5">
      {/* Header */}
      <section className="rounded-[28px] border border-white/70 bg-white/85 p-5 shadow-[0_14px_36px_rgba(15,23,42,0.06)] backdrop-blur-sm">
        <div>
          <div className="text-xs font-semibold tracking-wide text-slate-500">Tenant</div>
          <div className="mt-1 text-2xl font-bold tracking-tight text-slate-800 sm:text-3xl">
            ข่าวสารหอพัก
          </div>
          <div className="mt-1 text-sm text-slate-500">
            ตรวจสอบประกาศล่าสุด ข่าวด่วน และงานซ่อมบำรุง
          </div>
        </div>

        <div className="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
          <MiniStat label="ทั้งหมด" value={`${counts.all}`} />
          <MiniStat label="ข่าวด่วน" value={`${counts.urgent}`} tone="rose" />
          <MiniStat label="ข่าวทั่วไป" value={`${counts.general}`} tone="sky" />
          <MiniStat label="ซ่อมบำรุง" value={`${counts.maintenance}`} tone="amber" />
        </div>
      </section>

      {errMsg && (
        <div className="rounded-2xl border border-rose-100 bg-rose-50 px-4 py-3 text-sm text-rose-700 shadow-[0_8px_18px_rgba(244,63,94,0.06)]">
          {errMsg}
        </div>
      )}

      {okMsg && (
        <div className="rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-[0_8px_18px_rgba(16,185,129,0.06)]">
          {okMsg}
        </div>
      )}

      {/* Urgent announcements */}
      {urgentRows.length > 0 && (
        <section className="rounded-[28px] border border-rose-200 bg-rose-50/85 p-5 shadow-[0_14px_36px_rgba(244,63,94,0.06)] backdrop-blur-sm">
          <div className="mb-4 flex items-center gap-2">
            <span className="inline-flex rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-700 ring-1 ring-rose-200">
              ประกาศด่วน
            </span>
            <span className="text-sm text-rose-700/80">
              โปรดตรวจสอบข้อมูลสำคัญล่าสุด
            </span>
          </div>

          <div className="space-y-3">
            {urgentRows.map((r) => (
              <div
                key={r.id}
                className="rounded-3xl border border-rose-200/80 bg-white/90 p-4 shadow-[0_10px_24px_rgba(244,63,94,0.05)]"
              >
                <div className="flex flex-wrap items-center gap-2">
                  <span className="inline-flex rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700 ring-1 ring-rose-200">
                    ด่วน
                  </span>
                  <div className="text-lg font-bold tracking-tight text-slate-800">
                    {r.title}
                  </div>
                  {r.is_pinned ? (
                    <span className="inline-flex rounded-full bg-violet-50 px-3 py-1 text-xs font-semibold text-violet-700 ring-1 ring-violet-200">
                      ปักหมุด
                    </span>
                  ) : null}
                </div>

                <div className="mt-3 whitespace-pre-wrap text-sm leading-6 text-slate-700">
                  {r.content}
                </div>
              </div>
            ))}
          </div>
        </section>
      )}

      {/* Filter */}
      <section className="rounded-[28px] border border-white/70 bg-white/85 p-5 shadow-[0_14px_36px_rgba(15,23,42,0.06)] backdrop-blur-sm">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
          <div className="w-full sm:max-w-xs">
            <label className="mb-2 block text-sm font-medium text-slate-700">
              ประเภทประกาศ
            </label>
            <select
              value={type}
              onChange={(e) => {
                setPage(1);
                setType(e.target.value);
              }}
              className="h-12 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
            >
              <option value="">ทุกประเภท</option>
              <option value="general">ข่าวทั่วไป</option>
              <option value="urgent">ข่าวด่วน</option>
              <option value="maintenance">ซ่อมบำรุง</option>
            </select>
          </div>

          <button
            onClick={() => {
              setPage(1);
              loadList(1);
              loadUrgent();
            }}
            className="h-10 rounded-2xl bg-linear-to-r from-indigo-600 to-blue-600 px-4 text-sm font-medium text-white shadow-[0_10px_20px_rgba(59,130,246,0.18)] transition hover:-translate-y-0.5 hover:shadow-[0_14px_24px_rgba(59,130,246,0.24)]"
          >
            รีเฟรชข้อมูล
          </button>
        </div>
      </section>

      {/* List */}
      <section className="rounded-[28px] border border-white/70 bg-white/85 p-4 shadow-[0_14px_36px_rgba(15,23,42,0.06)] backdrop-blur-sm sm:p-5">
        {loading && (
          <div className="rounded-2xl bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
            กำลังโหลด...
          </div>
        )}

        {!loading && rows.length === 0 && (
          <div className="rounded-2xl bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
            ยังไม่มีประกาศ
          </div>
        )}

        {!loading && rows.length > 0 && (
          <div className="space-y-4">
            {rows.map((r) => (
              <div
                key={r.id}
                className="rounded-3xl border border-slate-200/80 bg-linear-to-r from-white to-slate-50/70 p-4 shadow-[0_10px_24px_rgba(15,23,42,0.04)] transition hover:-translate-y-0.5 hover:shadow-[0_16px_30px_rgba(15,23,42,0.08)]"
              >
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                  <div className="min-w-0">
                    <div className="flex flex-wrap items-center gap-2">
                      <div className="text-lg font-bold tracking-tight text-slate-800">
                        {r.title}
                      </div>

                      <span
                        className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold ${badgeType(
                          r.type
                        )}`}
                      >
                        {typeLabel(r.type)}
                      </span>

                      {r.is_pinned ? (
                        <span className="inline-flex rounded-full bg-violet-50 px-3 py-1 text-xs font-semibold text-violet-700 ring-1 ring-violet-200">
                          ปักหมุด
                        </span>
                      ) : null}
                    </div>

                    <div className="mt-3 whitespace-pre-wrap text-sm leading-6 text-slate-700">
                      {r.content}
                    </div>
                  </div>

                  <button
                    onClick={() => markRead(r.id)}
                    className="shrink-0 rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white shadow-[0_8px_18px_rgba(15,23,42,0.14)] transition hover:-translate-y-0.5 hover:bg-slate-800 hover:shadow-[0_12px_24px_rgba(15,23,42,0.18)]"
                  >
                    อ่านแล้ว
                  </button>
                </div>
              </div>
            ))}
          </div>
        )}

        {/* Pagination */}
        <div className="mt-5 rounded-3xl border border-slate-200/70 bg-white p-4 shadow-[0_8px_20px_rgba(15,23,42,0.04)]">
          <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div className="text-sm text-slate-500">
              หน้า <span className="font-bold text-slate-800">{page}</span> /{" "}
              <span className="font-bold text-slate-800">{totalPages}</span>
            </div>

            <div className="flex gap-2">
              <button
                disabled={page <= 1}
                onClick={() => setPage((p) => Math.max(1, p - 1))}
                className={[
                  "rounded-2xl px-4 py-2 text-sm font-medium transition",
                  page <= 1
                    ? "cursor-not-allowed border border-slate-200 bg-slate-50 text-slate-400"
                    : "border border-slate-200 bg-white text-slate-700 shadow-[0_6px_16px_rgba(15,23,42,0.04)] hover:bg-slate-50",
                ].join(" ")}
              >
                ก่อนหน้า
              </button>

              <button
                disabled={page >= totalPages}
                onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
                className={[
                  "rounded-2xl px-4 py-2 text-sm font-medium transition",
                  page >= totalPages
                    ? "cursor-not-allowed bg-slate-200 text-slate-400"
                    : "bg-slate-900 text-white shadow-[0_8px_18px_rgba(15,23,42,0.14)] hover:-translate-y-0.5 hover:shadow-[0_12px_24px_rgba(15,23,42,0.18)]",
                ].join(" ")}
              >
                ถัดไป
              </button>
            </div>
          </div>
        </div>
      </section>
    </div>
  );
}

function MiniStat({
  label,
  value,
  tone = "slate",
}: {
  label: string;
  value: string;
  tone?: "slate" | "sky" | "rose" | "amber";
}) {
  const toneClass =
    tone === "sky"
      ? "from-sky-50 to-cyan-50 border-sky-100"
      : tone === "rose"
      ? "from-rose-50 to-pink-50 border-rose-100"
      : tone === "amber"
      ? "from-amber-50 to-orange-50 border-amber-100"
      : "from-white to-slate-50 border-slate-200";

  return (
    <div
      className={`rounded-2xl border bg-linear-to-r ${toneClass} p-4 shadow-[0_8px_20px_rgba(15,23,42,0.04)]`}
    >
      <div className="text-sm text-slate-500">{label}</div>
      <div className="mt-2 text-2xl font-bold tracking-tight text-slate-800">
        {value}
      </div>
    </div>
  );
}