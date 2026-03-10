import { useEffect, useMemo, useRef, useState } from "react";
import { tenantParcelsApi } from "./services/tenantParcelsApi";

type ParcelRow = {
  id: number;
  tracking_no?: string | null;
  courier?: string | null;
  sender_name?: string | null;
  note?: string | null;
  status: "arrived" | "picked_up" | "cancelled" | string;
  received_at?: string | null;
  picked_up_at?: string | null;
  room?: { code?: string | null; room_no?: string | null } | null;
  files?: {
    id?: number;
    name?: string | null;
    original_name?: string | null;
    url?: string | null;
    path?: string | null;
    disk?: string | null;
    mime?: string | null;
  }[];
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

function statusLabel(value?: string) {
  if (!value) return "-";
  if (value === "arrived") return "รอรับ";
  if (value === "picked_up") return "รับแล้ว";
  if (value === "cancelled") return "ยกเลิก";
  return value;
}

function badgeClass(status?: string) {
  if (status === "arrived") {
    return "bg-amber-50 text-amber-800 ring-1 ring-amber-200";
  }
  if (status === "picked_up") {
    return "bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200";
  }
  if (status === "cancelled") {
    return "bg-rose-50 text-rose-700 ring-1 ring-rose-200";
  }
  return "bg-slate-100 text-slate-700 ring-1 ring-slate-200";
}

export default function TenantParcelsPage() {
  const [rows, setRows] = useState<ParcelRow[]>([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(false);
  const [status, setStatus] = useState("");

  const [page, setPage] = useState(1);
  const perPage = 12;
  const totalPages = useMemo(() => Math.max(1, Math.ceil(total / perPage)), [total]);

  const imgUrlCacheRef = useRef<Map<number, string>>(new Map());

  const normalize = (res: any) => {
    if (Array.isArray(res)) return { data: res as ParcelRow[], total: res.length };
    if (isPaged<ParcelRow>(res)) {
      return { data: res.data ?? [], total: res.total ?? (res.data?.length ?? 0) };
    }
    if (res && typeof res === "object" && Array.isArray(res.data)) {
      return { data: res.data as ParcelRow[], total: res.total ?? res.data.length };
    }
    return { data: [] as ParcelRow[], total: 0 };
  };

  const loadList = async (targetPage = page) => {
    setLoading(true);
    try {
      const res = await tenantParcelsApi.list(status, targetPage, perPage);
      const norm = normalize(res);
      setRows(norm.data);
      setTotal(norm.total);
    } catch (e) {
      console.error(e);
      setRows([]);
      setTotal(0);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadList(page);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [page]);

  const summary = useMemo(() => {
    const arrived = rows.filter((r) => r.status === "arrived").length;
    const picked = rows.filter((r) => r.status === "picked_up").length;
    const cancelled = rows.filter((r) => r.status === "cancelled").length;
    return { arrived, picked, cancelled };
  }, [rows]);

  const revokeAllCachedUrls = () => {
    const m = imgUrlCacheRef.current;
    for (const [, url] of m) URL.revokeObjectURL(url);
    m.clear();
  };

  useEffect(() => {
    return () => {
      revokeAllCachedUrls();
    };
  }, []);

  const getImageSrc = async (f: any): Promise<string> => {
    const id = Number(f?.id ?? 0);
    if (!id) return "";

    if (f.url) return f.url;

    const cached = imgUrlCacheRef.current.get(id);
    if (cached) return cached;

    const res = await tenantParcelsApi.fileBlob(id);
    const url = URL.createObjectURL(res.data);
    imgUrlCacheRef.current.set(id, url);
    return url;
  };

  function ImageTile({ f, idx }: { f: any; idx: number }) {
    const [src, setSrc] = useState<string>("");
    const [err, setErr] = useState<string>("");

    useEffect(() => {
      let alive = true;
      setErr("");
      setSrc("");

      (async () => {
        try {
          const s = await getImageSrc(f);
          if (!alive) return;
          setSrc(s);
        } catch (e: any) {
          console.error("load image failed", e);
          if (!alive) return;
          setErr(e?.response?.data?.message ?? "โหลดรูปไม่สำเร็จ");
        }
      })();

      return () => {
        alive = false;
      };
    }, [f?.id, f?.url]);

    const title = f?.name ?? f?.original_name ?? f?.path ?? `file-${idx}`;
    const href = src || "#";

    return (
      <a
        href={href}
        target="_blank"
        rel="noreferrer"
        className="group block overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 shadow-[0_8px_18px_rgba(15,23,42,0.04)]"
      >
        <div className="aspect-square w-full overflow-hidden bg-white">
          {src ? (
            <img
              src={src}
              alt={title}
              className="h-full w-full object-cover transition duration-200 group-hover:scale-[1.03]"
            />
          ) : (
            <div className="flex h-full items-center justify-center px-3 text-center text-xs font-semibold text-slate-500">
              {err ? err : "กำลังโหลดรูป..."}
            </div>
          )}
        </div>
        <div className="line-clamp-1 p-2 text-xs font-semibold text-slate-700">{title}</div>
      </a>
    );
  }

  return (
    <div className="space-y-5">
      {/* Header */}
      <section className="rounded-[28px] border border-white/70 bg-white/85 p-5 shadow-[0_14px_36px_rgba(15,23,42,0.06)] backdrop-blur-sm">
        <div>
          <div className="text-2xl font-bold tracking-tight text-slate-800">พัสดุของฉัน</div>
          <div className="mt-1 text-sm text-slate-500">
            ตรวจสอบพัสดุที่มาถึง สถานะการรับของ และรูปประกอบพัสดุ
          </div>
        </div>

        <div className="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
          <MiniStat label="ทั้งหมด" value={`${rows.length}`} />
          <MiniStat label="รอรับ" value={`${summary.arrived}`} tone="amber" />
          <MiniStat label="รับแล้ว" value={`${summary.picked}`} tone="emerald" />
          <MiniStat label="ยกเลิก" value={`${summary.cancelled}`} tone="rose" />
        </div>
      </section>

      {/* Filter */}
      <section className="rounded-[28px] border border-white/70 bg-white/85 p-5 shadow-[0_14px_36px_rgba(15,23,42,0.06)] backdrop-blur-sm">
        <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
          <div>
            <div className="text-lg font-semibold text-slate-800">ตัวกรองรายการพัสดุ</div>
            <div className="mt-1 text-sm text-slate-500">
              เลือกดูเฉพาะสถานะที่ต้องการ และรีเฟรชข้อมูลล่าสุดได้
            </div>
          </div>

          <div className="flex flex-col gap-3 sm:flex-row">
            <select
              value={status}
              onChange={(e) => setStatus(e.target.value)}
              className="h-12 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
            >
              <option value="">ทุกสถานะ</option>
              <option value="arrived">รอรับ</option>
              <option value="picked_up">รับแล้ว</option>
              <option value="cancelled">ยกเลิก</option>
            </select>

            <button
              onClick={() => {
                setPage(1);
                loadList(1);
              }}
              className="h-12 rounded-2xl bg-linear-to-r from-indigo-600 to-blue-600 px-5 text-sm font-medium text-white shadow-[0_12px_26px_rgba(59,130,246,0.22)] transition hover:-translate-y-0.5 hover:shadow-[0_16px_30px_rgba(59,130,246,0.28)]"
            >
              รีเฟรชข้อมูล
            </button>
          </div>
        </div>

        <div className="mt-4 flex flex-wrap gap-2">
          <span className="rounded-full bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-800 ring-1 ring-amber-200">
            รอรับ {summary.arrived}
          </span>
          <span className="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200">
            รับแล้ว {summary.picked}
          </span>
          <span className="rounded-full bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-700 ring-1 ring-rose-200">
            ยกเลิก {summary.cancelled}
          </span>
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
            ยังไม่มีพัสดุ
          </div>
        )}

        {!loading && rows.length > 0 && (
          <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            {rows.map((r) => (
              <div
                key={r.id}
                className="rounded-3xl border border-slate-200/80 bg-linear-to-r from-white to-slate-50/70 p-4 shadow-[0_10px_24px_rgba(15,23,42,0.04)] transition hover:-translate-y-0.5 hover:shadow-[0_16px_30px_rgba(15,23,42,0.08)]"
              >
                <div className="flex items-start justify-between gap-3">
                  <div className="min-w-0">
                    <div className="truncate text-lg font-bold tracking-tight text-slate-800">
                      {r.tracking_no || `Parcel #${r.id}`}
                    </div>
                    <div className="mt-1 text-sm text-slate-500">
                      ห้อง {r.room?.code ?? r.room?.room_no ?? "-"}
                    </div>
                  </div>

                  <span
                    className={`inline-flex shrink-0 rounded-full px-3 py-1 text-xs font-medium ${badgeClass(
                      r.status
                    )}`}
                  >
                    {statusLabel(r.status)}
                  </span>
                </div>

                <div className="mt-4 space-y-2 rounded-2xl bg-slate-50 p-4 text-sm text-slate-700">
                  <InfoRow label="ขนส่ง" value={r.courier || "-"} />
                  <InfoRow label="ผู้ส่ง" value={r.sender_name || "-"} />
                  <InfoRow label="วันที่รับเข้า" value={formatDateTH(r.received_at)} />
                  <InfoRow label="วันที่รับแล้ว" value={formatDateTH(r.picked_up_at)} />
                  <InfoRow label="หมายเหตุ" value={r.note || "-"} />
                </div>

                <div className="mt-4">
                  <div className="text-sm font-semibold text-slate-700">รูปพัสดุ</div>

                  {(r.files ?? []).length === 0 ? (
                    <div className="mt-3 rounded-2xl bg-slate-50 px-4 py-6 text-center text-sm text-slate-400">
                      ไม่มีรูปพัสดุ
                    </div>
                  ) : (
                    <div className="mt-3 grid gap-3 sm:grid-cols-2">
                      {(r.files ?? []).map((f, idx) => (
                        <ImageTile key={f.id ?? idx} f={f} idx={idx} />
                      ))}
                    </div>
                  )}
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
  tone?: "slate" | "amber" | "emerald" | "rose";
}) {
  const toneClass =
    tone === "amber"
      ? "from-amber-50 to-orange-50 border-amber-100"
      : tone === "emerald"
      ? "from-emerald-50 to-teal-50 border-emerald-100"
      : tone === "rose"
      ? "from-rose-50 to-pink-50 border-rose-100"
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

function InfoRow({ label, value }: { label: string; value: string }) {
  return (
    <div className="flex items-start justify-between gap-3">
      <span className="text-slate-500">{label}</span>
      <span className="text-right font-medium text-slate-700">{value}</span>
    </div>
  );
}