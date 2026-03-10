import { useEffect, useMemo, useState } from "react";
import { adminAnnouncementsApi } from "./services/adminAnnouncementsApi";
import type { AnnouncementPayload } from "./services/adminAnnouncementsApi";

type AnnouncementRow = {
  id: number;
  title: string;
  content: string;
  type: "general" | "urgent" | "maintenance" | string;
  status: "draft" | "published" | "expired" | string;
  is_pinned?: boolean;
  starts_at?: string | null;
  ends_at?: string | null;
  creator?: { name?: string; email?: string } | null;
  reads?: { id: number; user?: { name?: string } | null; read_at?: string | null }[];
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
  if (!v) return "ทันที";
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

function formatEndDateTH(v?: string | null) {
  if (!v) return "ไม่กำหนด";
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

function toDateTimeLocalValue(v?: string | null) {
  if (!v) return "";
  const d = new Date(v);
  if (!Number.isNaN(d.getTime())) {
    const pad = (n: number) => String(n).padStart(2, "0");
    const yyyy = d.getFullYear();
    const mm = pad(d.getMonth() + 1);
    const dd = pad(d.getDate());
    const hh = pad(d.getHours());
    const mi = pad(d.getMinutes());
    return `${yyyy}-${mm}-${dd}T${hh}:${mi}`;
  }

  const s = String(v).replace(" ", "T");
  return s.length >= 16 ? s.slice(0, 16) : s;
}

function typeLabel(v?: string) {
  if (v === "general") return "ข่าวทั่วไป";
  if (v === "urgent") return "ข่าวด่วน";
  if (v === "maintenance") return "ซ่อมบำรุง";
  return v || "-";
}

function statusLabel(v?: string) {
  if (v === "draft") return "ฉบับร่าง";
  if (v === "published") return "เผยแพร่แล้ว";
  if (v === "expired") return "หมดอายุ";
  return v || "-";
}

function typeBadge(v?: string) {
  if (v === "general") return "bg-sky-50 text-sky-700 ring-sky-200";
  if (v === "urgent") return "bg-rose-50 text-rose-700 ring-rose-200";
  if (v === "maintenance") return "bg-amber-50 text-amber-800 ring-amber-200";
  return "bg-slate-100 text-slate-700 ring-slate-200";
}

function statusBadge(v?: string) {
  if (v === "draft") return "bg-slate-100 text-slate-700 ring-slate-200";
  if (v === "published") return "bg-emerald-50 text-emerald-700 ring-emerald-200";
  if (v === "expired") return "bg-rose-50 text-rose-700 ring-rose-200";
  return "bg-slate-100 text-slate-700 ring-slate-200";
}

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
    <div className="fixed inset-0 z-50">
      <div className="absolute inset-0 bg-slate-900/40" onClick={onClose} />
      <div className="absolute inset-0 flex items-center justify-center p-4">
        <div className="w-full max-w-3xl rounded-2xl bg-white shadow-xl ring-1 ring-slate-200">
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

export default function AdminAnnouncementsPage() {
  const [rows, setRows] = useState<AnnouncementRow[]>([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(false);

  const [status, setStatus] = useState("");
  const [type, setType] = useState("");
  const [search, setSearch] = useState("");

  const [page, setPage] = useState(1);
  const perPage = 12;
  const totalPages = useMemo(() => Math.max(1, Math.ceil(total / perPage)), [total]);

  const [modalOpen, setModalOpen] = useState(false);
  const [mode, setMode] = useState<"create" | "edit">("create");
  const [editingId, setEditingId] = useState<number | null>(null);
  const [saving, setSaving] = useState(false);
  const [formErr, setFormErr] = useState("");

  const [form, setForm] = useState<AnnouncementPayload>({
    title: "",
    content: "",
    type: "general",
    status: "published",
    is_pinned: false,
    starts_at: "",
    ends_at: "",
    targets: [{ target_type: "all", target_id: null }],
    images: [],
  });

  const openCreate = () => {
    setMode("create");
    setEditingId(null);
    setFormErr("");
    setForm({
      title: "",
      content: "",
      type: "general",
      status: "published",
      is_pinned: false,
      starts_at: "",
      ends_at: "",
      targets: [{ target_type: "all", target_id: null }],
      images: [],
    });
    setModalOpen(true);
  };

  const openEdit = (r: AnnouncementRow) => {
    setMode("edit");
    setEditingId(r.id);
    setFormErr("");
    setForm({
      title: r.title ?? "",
      content: r.content ?? "",
      type: (r.type as any) ?? "general",
      status: (r.status as any) ?? "draft",
      is_pinned: !!r.is_pinned,
      starts_at: toDateTimeLocalValue(r.starts_at),
      ends_at: toDateTimeLocalValue(r.ends_at),
      targets: [{ target_type: "all", target_id: null }],
      images: [],
    });
    setModalOpen(true);
  };

  const validate = () => {
    if (!(form.title ?? "").trim()) return "กรุณากรอกหัวข้อประกาศ";
    if (!(form.content ?? "").trim()) return "กรุณากรอกรายละเอียดประกาศ";
    return "";
  };

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

  const loadList = async (targetPage = page) => {
    setLoading(true);
    try {
      const res = await adminAnnouncementsApi.list(status, type, search, targetPage, perPage);
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

  const saveAnnouncement = async () => {
    const msg = validate();
    if (msg) {
      setFormErr(msg);
      return;
    }

    setSaving(true);
    setFormErr("");
    try {
      const payload: AnnouncementPayload = {
        ...form,
        title: form.title.trim(),
        content: form.content.trim(),
        starts_at: form.starts_at || null,
        ends_at: form.ends_at || null,
        targets: [{ target_type: "all", target_id: null }],
        images: form.images ?? [],
      };

      if (mode === "create") {
        await adminAnnouncementsApi.create(payload);
      } else if (mode === "edit" && editingId != null) {
        await adminAnnouncementsApi.update(editingId, payload);
      }

      setModalOpen(false);
      await loadList(page);
    } catch (e: any) {
      console.error(e);
      setFormErr(e?.response?.data?.message ?? "บันทึกไม่สำเร็จ");
    } finally {
      setSaving(false);
    }
  };

  const publishAnnouncement = async (r: AnnouncementRow) => {
    try {
      await adminAnnouncementsApi.publish(r.id);
      await loadList(page);
    } catch (e: any) {
      console.error(e);
      alert(e?.response?.data?.message ?? "เผยแพร่ไม่สำเร็จ");
    }
  };

  const expireAnnouncement = async (r: AnnouncementRow) => {
    try {
      await adminAnnouncementsApi.expire(r.id);
      await loadList(page);
    } catch (e: any) {
      console.error(e);
      alert(e?.response?.data?.message ?? "ปิดประกาศไม่สำเร็จ");
    }
  };

  const deleteAnnouncement = async (r: AnnouncementRow) => {
    const ok = window.confirm(`ยืนยันลบประกาศ "${r.title}" ?`);
    if (!ok) return;
    try {
      await adminAnnouncementsApi.remove(r.id);
      await loadList(page);
    } catch (e: any) {
      console.error(e);
      alert(e?.response?.data?.message ?? "ลบไม่สำเร็จ");
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

  const summary = useMemo(() => {
    const draft = rows.filter((r) => r.status === "draft").length;
    const published = rows.filter((r) => r.status === "published").length;
    const expired = rows.filter((r) => r.status === "expired").length;
    return { draft, published, expired };
  }, [rows]);

  return (
    <div className="space-y-4">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <div className="text-xs font-extrabold tracking-wide text-slate-500">Admin</div>
          <div className="text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">ประกาศ</div>
          <div className="mt-1 text-sm text-slate-500">ข่าวประชาสัมพันธ์ • ข่าวด่วน • แจ้งซ่อมบำรุง</div>
        </div>

        <div className="flex flex-wrap gap-2">
          <button
            onClick={() => loadList(page)}
            className="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-900 shadow-sm hover:bg-slate-50"
            type="button"
          >
            Refresh
          </button>
          <button
            onClick={openCreate}
            className="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-blue-700"
            type="button"
          >
            + สร้างประกาศ
          </button>
        </div>
      </div>

      <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div className="grid gap-3 lg:grid-cols-[220px_220px_1fr_140px] lg:items-center">
          <select
            value={status}
            onChange={(e) => setStatus(e.target.value)}
            className="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold outline-none focus:ring-4 focus:ring-blue-100"
          >
            <option value="">ทุกสถานะ</option>
            <option value="draft">ฉบับร่าง</option>
            <option value="published">เผยแพร่แล้ว</option>
            <option value="expired">หมดอายุ</option>
          </select>

          <select
            value={type}
            onChange={(e) => setType(e.target.value)}
            className="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold outline-none focus:ring-4 focus:ring-blue-100"
          >
            <option value="">ทุกประเภท</option>
            <option value="general">ข่าวทั่วไป</option>
            <option value="urgent">ข่าวด่วน</option>
            <option value="maintenance">ซ่อมบำรุง</option>
          </select>

          <input
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="ค้นหาหัวข้อหรือรายละเอียดประกาศ"
            className="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold outline-none placeholder:text-slate-400 focus:ring-4 focus:ring-blue-100"
            onKeyDown={(e) => e.key === "Enter" && onSearch()}
          />

          <button
            onClick={onSearch}
            className="h-11 rounded-xl bg-blue-600 px-4 text-sm font-extrabold text-white shadow-sm hover:bg-blue-700"
            type="button"
          >
            Search
          </button>
        </div>

        <div className="mt-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
          <div className="flex flex-wrap gap-2 text-xs font-semibold text-slate-600">
            <span className="rounded-full bg-slate-100 px-3 py-1 text-slate-700 ring-1 ring-slate-200">
              ฉบับร่าง {summary.draft}
            </span>
            <span className="rounded-full bg-emerald-50 px-3 py-1 text-emerald-700 ring-1 ring-emerald-200">
              เผยแพร่แล้ว {summary.published}
            </span>
            <span className="rounded-full bg-rose-50 px-3 py-1 text-rose-700 ring-1 ring-rose-200">
              หมดอายุ {summary.expired}
            </span>
          </div>

          <div className="text-right text-sm font-semibold text-slate-500">
            รวมทั้งหมด <span className="font-black text-slate-900">{total}</span> รายการ
          </div>
        </div>
      </div>

      <div className="rounded-2xl border border-slate-500 bg-white p-4 shadow-sm">
        {loading && <div className="p-3 text-sm text-slate-500">Loading...</div>}
        {!loading && rows.length === 0 && <div className="p-3 text-sm text-slate-500">ไม่พบข้อมูล</div>}

        {!loading && rows.length > 0 && (
          <div className="grid gap-4 lg:grid-cols-2">
            {rows.map((r) => (
              <div key={r.id} className="rounded-2xl border border-slate-300 bg-white p-4 shadow-sm">
                <div className="flex items-start justify-between gap-3">
                  <div>
                    <div className="text-xl font-black tracking-tight text-slate-900">{r.title}</div>
                    <div className="mt-1 flex flex-wrap gap-2">
                      <span
                        className={`inline-flex rounded-full px-3 py-1 text-xs font-extrabold ring-1 ${typeBadge(
                          r.type
                        )}`}
                      >
                        {typeLabel(r.type)}
                      </span>
                      <span
                        className={`inline-flex rounded-full px-3 py-1 text-xs font-extrabold ring-1 ${statusBadge(
                          r.status
                        )}`}
                      >
                        {statusLabel(r.status)}
                      </span>
                      {r.is_pinned ? (
                        <span className="inline-flex rounded-full bg-violet-50 px-3 py-1 text-xs font-extrabold text-violet-700 ring-1 ring-violet-200">
                          ปักหมุด
                        </span>
                      ) : null}
                    </div>
                  </div>

                  <div className="flex flex-wrap gap-2">
                    <button
                      onClick={() => openEdit(r)}
                      className="rounded-xl border border-slate-200 bg-blue-400 px-3 py-1.5 text-xs font-extrabold text-slate-900 hover:bg-slate-50"
                      type="button"
                    >
                      แก้ไข
                    </button>
                    <button
                      onClick={() => deleteAnnouncement(r)}
                      className="rounded-xl border border-rose-300 bg-rose-50 px-3 py-1.5 text-xs font-extrabold text-rose-700 hover:bg-rose-100"
                      type="button"
                    >
                      ลบ
                    </button>
                  </div>
                </div>

                <div className="mt-4 whitespace-pre-wrap rounded-xl bg-slate-50 p-3 text-sm font-medium text-slate-700">
                  {r.content}
                </div>

                <div className="mt-4 grid gap-2 sm:grid-cols-2">
                  <div className="rounded-xl bg-slate-50 p-3 text-xs font-semibold text-slate-700">
                    เริ่มแสดง: {formatDateTH(r.starts_at)}
                  </div>
                  <div className="rounded-xl bg-slate-50 p-3 text-xs font-semibold text-slate-700">
                    สิ้นสุด: {formatEndDateTH(r.ends_at)}
                  </div>
                  <div className="rounded-xl bg-slate-50 p-3 text-xs font-semibold text-slate-700">
                    ผู้สร้าง: {r.creator?.name ?? "-"}
                  </div>
                </div>

                <div className="mt-4 flex flex-wrap gap-2">
                  {r.status !== "published" && (
                    <button
                      onClick={() => publishAnnouncement(r)}
                      className="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-extrabold text-white hover:bg-emerald-700"
                      type="button"
                    >
                      เผยแพร่
                    </button>
                  )}
                  {r.status === "published" && (
                    <button
                      onClick={() => expireAnnouncement(r)}
                      className="rounded-xl bg-amber-500 px-4 py-2 text-sm font-extrabold text-white hover:bg-amber-600"
                      type="button"
                    >
                      ปิดประกาศ
                    </button>
                  )}
                </div>
              </div>
            ))}
          </div>
        )}

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

      <Modal
        open={modalOpen}
        title={mode === "create" ? "สร้างประกาศ" : "แก้ไขประกาศ"}
        onClose={() => setModalOpen(false)}
      >
        {formErr && (
          <div className="mb-3 rounded-xl bg-rose-50 p-3 text-sm font-semibold text-rose-700 ring-1 ring-rose-200">
            {formErr}
          </div>
        )}

        <div className="grid gap-3 sm:grid-cols-2">
          <div className="sm:col-span-2">
            <div className="mb-1 text-xs font-extrabold text-slate-600">หัวข้อประกาศ</div>
            <input
              value={form.title}
              onChange={(e) => setForm((x) => ({ ...x, title: e.target.value }))}
              className="h-11 w-full rounded-xl border border-slate-200 px-3 text-sm font-semibold outline-none focus:ring-4 focus:ring-blue-100"
            />
          </div>

          <div>
            <div className="mb-1 text-xs font-extrabold text-slate-600">ประเภท</div>
            <select
              value={form.type}
              onChange={(e) => setForm((x) => ({ ...x, type: e.target.value as any }))}
              className="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold outline-none focus:ring-4 focus:ring-blue-100"
            >
              <option value="general">ข่าวทั่วไป</option>
              <option value="urgent">ข่าวด่วน</option>
              <option value="maintenance">ซ่อมบำรุง</option>
            </select>
          </div>

          <div>
            <div className="mb-1 text-xs font-extrabold text-slate-600">สถานะ</div>
            <select
              value={form.status}
              onChange={(e) => setForm((x) => ({ ...x, status: e.target.value as any }))}
              className="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold outline-none focus:ring-4 focus:ring-blue-100"
            >
              <option value="draft">ฉบับร่าง</option>
              <option value="published">เผยแพร่แล้ว</option>
            </select>
          </div>

          <div>
            <div className="mb-1 text-xs font-extrabold text-slate-600">เริ่มแสดง</div>
            <input
              type="datetime-local"
              value={form.starts_at ?? ""}
              onChange={(e) => setForm((x) => ({ ...x, starts_at: e.target.value }))}
              className="h-11 w-full rounded-xl border border-slate-200 px-3 text-sm font-semibold outline-none focus:ring-4 focus:ring-blue-100"
            />
          </div>

          <div>
            <div className="mb-1 text-xs font-extrabold text-slate-600">สิ้นสุด</div>
            <input
              type="datetime-local"
              value={form.ends_at ?? ""}
              onChange={(e) => setForm((x) => ({ ...x, ends_at: e.target.value }))}
              className="h-11 w-full rounded-xl border border-slate-200 px-3 text-sm font-semibold outline-none focus:ring-4 focus:ring-blue-100"
            />
          </div>

          <div className="sm:col-span-2">
            <label className="inline-flex items-center gap-2 text-sm font-bold text-slate-700">
              <input
                type="checkbox"
                checked={!!form.is_pinned}
                onChange={(e) => setForm((x) => ({ ...x, is_pinned: e.target.checked }))}
              />
              ปักหมุดประกาศ
            </label>
          </div>

          <div className="sm:col-span-2">
            <div className="mb-1 text-xs font-extrabold text-slate-600">รายละเอียด</div>
            <textarea
              value={form.content}
              onChange={(e) => setForm((x) => ({ ...x, content: e.target.value }))}
              className="min-h-35 w-full rounded-xl border border-slate-200 px-3 py-3 text-sm font-semibold outline-none focus:ring-4 focus:ring-blue-100"
            />
          </div>
        </div>

        <div className="mt-4 flex items-center justify-end gap-2">
          <button
            onClick={() => setModalOpen(false)}
            className="h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm font-extrabold text-slate-900 hover:bg-slate-50"
            disabled={saving}
            type="button"
          >
            ยกเลิก
          </button>
          <button
            onClick={saveAnnouncement}
            className="h-11 rounded-xl bg-blue-600 px-4 text-sm font-extrabold text-white shadow-sm hover:bg-blue-700 disabled:opacity-60"
            disabled={saving}
            type="button"
          >
            {saving ? "กำลังบันทึก..." : "บันทึก"}
          </button>
        </div>
      </Modal>
    </div>
  );
}