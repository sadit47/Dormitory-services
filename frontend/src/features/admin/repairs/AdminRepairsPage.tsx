import { useEffect, useMemo, useRef, useState } from "react";
import { adminRepairsApi } from "./services/adminRepairsApi";
import type { RepairStatus } from "./services/adminRepairsApi";

/* ===================== Types ===================== */
type RepairFile = {
  id?: number;
  name?: string | null;
  url?: string | null; // ถ้า backend ส่ง url มา (กรณี public)
  path?: string | null;
  disk?: string | null;
  mime?: string | null;
};

type RepairRow = {
  id: number;
  title?: string | null;
  description?: string | null;
  priority?: string | null; // low/medium/high
  status?: RepairStatus;

  requested_at?: string | null;
  completed_at?: string | null;

  tenant?: { user?: { name?: string; email?: string; phone?: string } | null } | null;
  room?: { code?: string } | null;

  files?: RepairFile[];
};

type Paged<T> = {
  current_page?: number;
  data?: T[];
  total?: number;
  per_page?: number;
  last_page?: number;
};

function isPaged<T>(x: any): x is Paged<T> {
  return x && typeof x === "object" && Array.isArray(x.data);
}

/* ===================== Helpers ===================== */
function statusLabel(s?: string) {
  if (!s) return "-";
  if (s === "submitted") return "ส่งเรื่อง";
  if (s === "in_progress") return "กำลังดำเนินการ";
  if (s === "done") return "เสร็จสิ้น";
  if (s === "rejected") return "ปฏิเสธ";
  return s;
}

function statusBadgeClass(s?: string) {
  if (s === "done") return "bg-emerald-50 text-emerald-700 ring-emerald-200";
  if (s === "in_progress") return "bg-sky-50 text-sky-700 ring-sky-200";
  if (s === "submitted") return "bg-amber-50 text-amber-800 ring-amber-200";
  if (s === "rejected") return "bg-rose-50 text-rose-700 ring-rose-200";
  return "bg-slate-100 text-slate-700 ring-slate-200";
}

function priorityLabel(p?: string | null) {
  if (!p) return "-";
  if (p === "low") return "ต่ำ";
  if (p === "medium") return "กลาง";
  if (p === "high") return "สูง";
  return p;
}

function fmtDate(v?: string | null) {
  if (!v) return "-";
  const d = new Date(v);
  if (Number.isNaN(d.getTime())) return String(v);
  return d.toLocaleString("th-TH");
}

/* ===================== Modal ===================== */
function Modal({
  open,
  title,
  children,
  onClose,
}: {
  open: boolean;
  title: string;
  children: React.ReactNode;
  onClose: () => void;
}) {
  if (!open) return null;
  return (
    <div className="fixed inset-0 z-35">
      <div className="absolute inset-0 bg-slate-900/40" onClick={onClose} />
      <div className="absolute inset-0 flex items-center justify-center p-4">
        <div className="w-full max-w-4xl rounded-2xl bg-white shadow-xl ring-1 ring-slate-200">
          <div className="flex items-center justify-between border-b border-slate-200 p-4">
            <div className="text-lg font-black text-slate-900">{title}</div>
            <button
              onClick={onClose}
              className="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-sm font-extrabold text-slate-700 hover:bg-slate-50"
              type="button"
            >
              ปิด
            </button>
          </div>
          <div className="p-4">{children}</div>
        </div>
      </div>
    </div>
  );
}

/* ===================== Page ===================== */
export default function AdminRepairsPage() {
  const [rows, setRows] = useState<RepairRow[]>([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(false);

  // filters
  const [q, setQ] = useState("");
  const [status, setStatus] = useState<string>("");

  // paging
  const [page, setPage] = useState(1);
  const perPage = 10;
  const totalPages = useMemo(() => Math.max(1, Math.ceil(total / perPage)), [total, perPage]);

  // modal view
  const [open, setOpen] = useState(false);
  const [viewing, setViewing] = useState<RepairRow | null>(null);

  // ✅ cache สำหรับรูป (fileId -> objectURL)
  const imgUrlCacheRef = useRef<Map<number, string>>(new Map());

  const revokeAllCachedUrls = () => {
    const m = imgUrlCacheRef.current;
    for (const [, url] of m) URL.revokeObjectURL(url);
    m.clear();
  };

  useEffect(() => {
    return () => {
      // cleanup เมื่อออกจากหน้า
      revokeAllCachedUrls();
    };
  }, []);

  const loadList = async (targetPage = page) => {
    setLoading(true);
    try {
      const res = await adminRepairsApi.list(q, status, targetPage, perPage);

      if (Array.isArray(res)) {
        setRows(res as any);
        setTotal(res.length);
      } else if (isPaged<RepairRow>(res)) {
        setRows(res.data ?? []);
        setTotal(Number(res.total ?? (res.data?.length ?? 0)));
      } else {
        setRows([]);
        setTotal(0);
      }
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

  const onSearch = () => {
    setPage(1);
    loadList(1);
  };

  const openDetail = (r: RepairRow) => {
    setViewing(r);
    setOpen(true);
  };

  // ✅ แก้ type: next เป็น RepairStatus (ไม่ใช่ string union ซ้ำ)
  const changeStatus = async (r: RepairRow, next: RepairStatus) => {
    try {
      await adminRepairsApi.updateStatus(r.id, next);

      const completedAt = next === "done" ? new Date().toISOString() : null;

      // update row locally
      setRows((prev) =>
        prev.map((x) =>
          x.id === r.id
            ? { ...x, status: next, completed_at: next === "done" ? completedAt : x.completed_at }
            : x
        )
      );

      setViewing((v) =>
        v && v.id === r.id
          ? { ...v, status: next, completed_at: next === "done" ? completedAt : v.completed_at }
          : v
      );
    } catch (e: any) {
      console.error(e);
      alert(e?.response?.data?.message ?? "อัปเดตสถานะไม่สำเร็จ");
    }
  };

  const removeRepair = async (r: RepairRow) => {
    const ok = window.confirm(`ยืนยันลบรายการแจ้งซ่อม #${r.id} ?`);
    if (!ok) return;

    try {
      await adminRepairsApi.remove(r.id);

      setOpen(false);
      setViewing(null);

      // ล้างรูป cache (กันค้าง)
      revokeAllCachedUrls();

      await loadList(page);
    } catch (e: any) {
      console.error(e);
      alert(e?.response?.data?.message ?? "ลบไม่สำเร็จ");
    }
  };

  /**
   * ✅ ได้ src สำหรับ <img> แบบแน่นอน:
   * - ถ้า backend ส่ง f.url ที่เปิดได้ (public) ใช้เลย
   * - ถ้าไม่ → โหลดไฟล์ด้วย Bearer เป็น blob แล้วทำ ObjectURL
   */
  const getImageSrc = async (f: RepairFile): Promise<string> => {
    const id = Number(f?.id ?? 0);
    if (!id) return "";

    // 1) ถ้ามี url จาก backend แล้ว
    if (f.url) return f.url;

    // 2) cache
    const cached = imgUrlCacheRef.current.get(id);
    if (cached) return cached;

    // 3) โหลด blob ด้วย Bearer
    const res = await adminRepairsApi.fileBlob(id);
    const url = URL.createObjectURL(res.data);
    imgUrlCacheRef.current.set(id, url);
    return url;
  };

  /* ===================== Image Tile ===================== */
  function ImageTile({ f}: { f: RepairFile; idx: number }) {
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
      // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [f?.id, f?.url]);

    

    // ลิงก์เปิดแท็บใหม่: ถ้าเป็น objectURL ก็เปิดได้, ถ้าเป็น url ก็เปิดได้
    const href = src || "#";

    return (
      <a
        href={href}
        target="_blank"
        rel="noreferrer"
        className="group block overflow-hidden rounded-2xl border border-slate-200 bg-slate-50"
      >
        <div className="aspect-square w-full overflow-hidden bg-white">
          {src ? (
            <img
              src={src}
              
              className="h-full w-full object-cover transition group-hover:scale-[1.03]"
            />
          ) : (
            <div className="flex h-full items-center justify-center px-3 text-center text-xs font-semibold text-slate-500">
              {err ? err : "กำลังโหลดรูป..."}
            </div>
          )}
        </div>
        
      </a>
    );
  }

  return (
    <div className="space-y-4">
      {/* Header */}
      <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <div className="text-xs font-extrabold tracking-wide text-slate-500">Admin</div>
          <div className="text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">แจ้งซ่อม</div>
          <div className="mt-1 text-sm text-slate-500">ค้นหา • กรองสถานะ • ดูรายละเอียด • อัปเดตสถานะ • ลบ</div>
        </div>

        <div className="flex flex-wrap gap-2">
          <button
            onClick={() => loadList(page)}
            className="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-900 shadow-sm hover:bg-slate-50"
            type="button"
          >
            Refresh
          </button>
        </div>
      </div>

      {/* Filters */}
      <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div className="grid gap-3 lg:grid-cols-[360px_240px_140px] lg:items-center">
          <input
            value={q}
            onChange={(e) => setQ(e.target.value)}
            placeholder="ค้นหา: หัวข้อ / ชื่อผู้เช่า / อีเมล / ห้อง"
            className="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-900 outline-none placeholder:text-slate-400 focus:ring-4 focus:ring-indigo-100"
            onKeyDown={(e) => e.key === "Enter" && onSearch()}
          />

          <select
            value={status}
            onChange={(e) => setStatus(e.target.value)}
            className="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-900 outline-none focus:ring-4 focus:ring-indigo-100"
          >
            <option value="">ทุกสถานะ</option>
            <option value="submitted">ส่งเรื่อง</option>
            <option value="in_progress">กำลังดำเนินการ</option>
            <option value="done">เสร็จสิ้น</option>
            <option value="rejected">ปฏิเสธ</option>
          </select>

          <button
            onClick={onSearch}
            className="h-11 rounded-xl bg-indigo-600 px-4 text-sm font-extrabold text-white shadow-sm hover:bg-indigo-700"
            type="button"
          >
            Search
          </button>
        </div>

        <div className="mt-4 text-right text-sm font-semibold text-slate-500">
          รวมทั้งหมด <span className="font-black text-slate-900">{total}</span> รายการ
        </div>
      </div>

      {/* Table */}
      <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        {loading && <div className="p-3 text-sm text-slate-500">Loading...</div>}
        {!loading && rows.length === 0 && <div className="p-3 text-sm text-slate-500">ไม่พบข้อมูลแจ้งซ่อม</div>}

        {!loading && rows.length > 0 && (
          <div className="overflow-x-auto">
            <table className="min-w-full w-full border-collapse">
              <thead>
                <tr className="text-left text-xs font-extrabold text-slate-600">
                  <th className="py-3">หัวข้อ</th>
                  <th className="py-3">ผู้เช่า</th>
                  <th className="py-3">ห้อง</th>
                  <th className="py-3">ความสำคัญ</th>
                  <th className="py-3">วันแจ้ง</th>
                  <th className="py-3">สถานะ</th>
                  <th className="py-3 text-right">จัดการ</th>
                </tr>
              </thead>

              <tbody>
                {rows.map((r) => {
                  const tenantName = r.tenant?.user?.name ?? "-";
                  const roomCode = r.room?.code ?? "-";
                  return (
                    <tr key={r.id} className="border-t border-slate-100 text-sm font-semibold text-slate-900">
                      
                      <td className="py-4">
                        <div className="font-black">{r.title ?? "-"}</div>
                        <div className="text-xs font-semibold text-slate-500 line-clamp-1">{r.description ?? ""}</div>
                      </td>
                      <td className="py-4">{tenantName}</td>
                      <td className="py-4 font-black">{roomCode}</td>
                      <td className="py-4">{priorityLabel(r.priority)}</td>
                      <td className="py-4">{fmtDate(r.requested_at)}</td>
                      <td className="py-4">
                        <span
                          className={[
                            "inline-flex rounded-full px-3 py-1 text-xs font-extrabold ring-1",
                            statusBadgeClass(r.status),
                          ].join(" ")}
                        >
                          {statusLabel(r.status)}
                        </span>
                      </td>
                      <td className="py-4">
                        <div className="flex justify-end gap-2">
                          <button
                            onClick={() => openDetail(r)}
                            className="rounded-xl px-3 py-1.5 text-xs font-extrabold ring-1 border border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100"
                            type="button"
                          >
                            รายละเอียด
                          </button>

                          <button
                            onClick={() => removeRepair(r)}
                            className="rounded-xl px-3 py-1.5 text-xs font-extrabold ring-1 border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100"
                            type="button"
                          >
                            ลบ
                          </button>
                        </div>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        )}

        {/* Pagination */}
        <div className="mt-4 flex items-center justify-between gap-3">
          <div className="text-sm font-semibold text-slate-500">
            หน้า <span className="font-black text-slate-900">{page}</span> /{" "}
            <span className="font-black text-slate-900">{totalPages}</span>
          </div>

          <div className="flex gap-2">
            <button
              disabled={page <= 1}
              onClick={() => setPage((p) => Math.max(1, p - 1))}
              className={[
                "h-10 rounded-xl border px-4 text-sm font-extrabold",
                page <= 1
                  ? "cursor-not-allowed border-slate-200 bg-slate-50 text-slate-400"
                  : "border-slate-200 bg-white text-slate-900 hover:bg-slate-50",
              ].join(" ")}
              type="button"
            >
              ก่อนหน้า
            </button>

            <button
              disabled={page >= totalPages}
              onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
              className={[
                "h-10 rounded-xl border px-4 text-sm font-extrabold",
                page >= totalPages
                  ? "cursor-not-allowed border-slate-200 bg-slate-50 text-slate-400"
                  : "border-slate-200 bg-white text-slate-900 hover:bg-slate-50",
              ].join(" ")}
              type="button"
            >
              ถัดไป
            </button>
          </div>
        </div>
      </div>

      {/* Detail Modal */}
      <Modal
        open={open}
        title={`รายละเอียดแจ้งซ่อม #${viewing?.id ?? ""}`}
        onClose={() => {
          setOpen(false);
          // จะให้ไม่ล้าง cache ก็ได้ แต่ถ้าล้างจะประหยัด memory
          // revokeAllCachedUrls();
        }}
      >
        {!viewing ? (
          <div className="text-sm text-slate-500">ไม่พบข้อมูล</div>
        ) : (
          <div className="space-y-4">
            <div className="grid gap-3 sm:grid-cols-2">
              <div className="rounded-2xl border border-slate-200 bg-white p-4">
                <div className="text-xs font-extrabold text-slate-500">หัวข้อ</div>
                <div className="mt-1 text-lg font-black text-slate-900">{viewing.title ?? "-"}</div>
                <div className="mt-2 text-sm font-semibold text-slate-700 whitespace-pre-wrap">
                  {viewing.description ?? "-"}
                </div>
              </div>

              <div className="rounded-2xl border border-slate-200 bg-white p-4">
                <div className="text-xs font-extrabold text-slate-500">ข้อมูล</div>
                <div className="mt-2 text-sm font-semibold text-slate-700">
                  ผู้เช่า: <span className="font-black">{viewing.tenant?.user?.name ?? "-"}</span>
                </div>
                <div className="text-sm font-semibold text-slate-700">อีเมล: {viewing.tenant?.user?.email ?? "-"}</div>
                <div className="text-sm font-semibold text-slate-700">โทร: {viewing.tenant?.user?.phone ?? "-"}</div>
                <div className="mt-2 text-sm font-semibold text-slate-700">
                  ห้อง: <span className="font-black">{viewing.room?.code ?? "-"}</span>
                </div>
                <div className="text-sm font-semibold text-slate-700">ความสำคัญ: {priorityLabel(viewing.priority)}</div>
                <div className="text-sm font-semibold text-slate-700">วันแจ้ง: {fmtDate(viewing.requested_at)}</div>
                <div className="text-sm font-semibold text-slate-700">วันเสร็จ: {fmtDate(viewing.completed_at)}</div>

                <div className="mt-3">
                  <div className="text-xs font-extrabold text-slate-500">สถานะ</div>
                  <div className="mt-2 flex flex-wrap items-center gap-2">
                    <span
                      className={[
                        "inline-flex rounded-full px-3 py-1 text-xs font-extrabold ring-1",
                        statusBadgeClass(viewing.status),
                      ].join(" ")}
                    >
                      {statusLabel(viewing.status)}
                    </span>

                    <div className="ml-auto flex flex-wrap gap-2">
                      <button
                        onClick={() => changeStatus(viewing, "submitted")}
                        className="rounded-xl border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-extrabold text-amber-800 hover:bg-amber-100"
                        type="button"
                      >
                        ส่งเรื่อง
                      </button>
                      <button
                        onClick={() => changeStatus(viewing, "in_progress")}
                        className="rounded-xl border border-sky-200 bg-sky-50 px-3 py-1.5 text-xs font-extrabold text-sky-700 hover:bg-sky-100"
                        type="button"
                      >
                        ดำเนินการ
                      </button>
                      <button
                        onClick={() => changeStatus(viewing, "done")}
                        className="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-extrabold text-emerald-700 hover:bg-emerald-100"
                        type="button"
                      >
                        เสร็จสิ้น
                      </button>
                      <button
                        onClick={() => changeStatus(viewing, "rejected")}
                        className="rounded-xl border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-extrabold text-rose-700 hover:bg-rose-100"
                        type="button"
                      >
                        ปฏิเสธ
                      </button>
                    </div>
                  </div>
                </div>

                <div className="mt-4 flex justify-end">
                  <button
                    onClick={() => removeRepair(viewing)}
                    className="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-extrabold text-rose-700 hover:bg-rose-100"
                    type="button"
                  >
                    ลบรายการนี้
                  </button>
                </div>
              </div>
            </div>

            {/* Images */}
            <div className="rounded-2xl border border-slate-200 bg-white p-4">
              <div className="text-sm font-black text-slate-900">รูปแนบ</div>

              {(viewing.files ?? []).length === 0 ? (
                <div className="mt-2 text-sm text-slate-500">ไม่มีรูปแนบ</div>
              ) : (
                <div className="mt-3 grid gap-3 sm:grid-cols-3 lg:grid-cols-4">
                  {(viewing.files ?? []).map((f, idx) => (
                    <ImageTile key={f.id ?? idx} f={f} idx={idx} />
                  ))}
                </div>
              )}
            </div>
          </div>
        )}
      </Modal>
    </div>
  );
}