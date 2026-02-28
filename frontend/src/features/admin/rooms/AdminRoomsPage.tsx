import { useEffect, useMemo, useState } from "react";
import { adminRoomsApi } from "./services/adminRoomsApi";
import type { RoomPayload } from "./services/adminRoomsApi";

type RoomRow = {
  id: number;
  code: string;
  floor: number;
  price_monthly?: string | number;
  status: "vacant" | "occupied" | "maintenance" | string;
  room_type?: { id: number; name: string } | null;
  room_type_id?: number | null;
  active_assignment?: {
    end_date?: string | null;
    tenant?: { user?: { name?: string; email?: string; phone?: string } | null } | null;
  } | null;
};

type Paged<T> = {
  current_page?: number;
  data?: T[];
  total?: number;
  per_page?: number;
  last_page?: number;
};

type RoomsMeta = {
  room_types?: { id: number; name: string }[];
  statuses?: { value: string; label: string }[];
};

function isPaged<T>(x: any): x is Paged<T> {
  return x && typeof x === "object" && "data" in x && Array.isArray((x as any).data);
}

function formatMoney(v: string | number | undefined) {
  const n = Number(v ?? 0);
  if (Number.isNaN(n)) return "-";
  return n.toLocaleString("th-TH");
}

function formatDateTH(v?: string | null) {
  if (!v) return "-";
  const d = new Date(v);
  if (Number.isNaN(d.getTime())) return String(v);
  return new Intl.DateTimeFormat("th-TH", { year: "numeric", month: "2-digit", day: "2-digit" }).format(d);
}

function statusLabel(value?: string, meta?: RoomsMeta | null) {
  if (!value) return "-";
  const fromMeta = meta?.statuses?.find((s) => s.value === value)?.label;
  if (fromMeta) return fromMeta;
  if (value === "vacant") return "ว่าง";
  if (value === "occupied") return "มีผู้เช่า";
  if (value === "maintenance") return "ซ่อมบำรุง";
  return value;
}

function badgeClass(status?: string) {
  if (status === "vacant") return "bg-emerald-50 text-emerald-700 ring-emerald-200";
  if (status === "occupied") return "bg-sky-50 text-sky-700 ring-sky-200";
  if (status === "maintenance") return "bg-amber-50 text-amber-800 ring-amber-200";
  return "bg-slate-100 text-slate-700 ring-slate-200";
}

function cardAccentRing(status?: string) {
  if (status === "vacant") return "hover:ring-emerald-200";
  if (status === "occupied") return "hover:ring-sky-200";
  if (status === "maintenance") return "hover:ring-amber-200";
  return "hover:ring-slate-200";
}

function InfoRow({ label, value }: { label: string; value: any }) {
  return (
    <div className="flex items-start justify-between gap-3">
      <div className="text-xs font-semibold text-slate-500">{label}</div>
      <div className="text-right text-xs font-semibold text-slate-900 break-all">{value}</div>
    </div>
  );
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
        <div className="w-full max-w-xl rounded-2xl bg-white shadow-xl ring-1 ring-slate-200">
          <div className="flex items-center justify-between border-b border-slate-200 p-4">
            <div className="text-lg font-black text-slate-900">{title}</div>
            <button
              onClick={onClose}
              className="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-sm font-extrabold text-slate-700 hover:bg-slate-50"
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

export default function AdminRoomsPage() {
  const [rows, setRows] = useState<RoomRow[]>([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(false);

  const [meta, setMeta] = useState<RoomsMeta | null>(null);

  // filters
  const [status, setStatus] = useState<string>("");
  const [roomTypeId, setRoomTypeId] = useState<string>("");
  const [search, setSearch] = useState<string>("");

  const [page, setPage] = useState(1);
  const perPage = 12;
  const totalPages = useMemo(() => Math.max(1, Math.ceil(total / perPage)), [total, perPage]);

  // ===== Add/Edit Modal State =====
  const [roomModalOpen, setRoomModalOpen] = useState(false);
  const [mode, setMode] = useState<"create" | "edit">("create");
  const [editingId, setEditingId] = useState<number | null>(null);
  const [saving, setSaving] = useState(false);
  const [formErr, setFormErr] = useState<string>("");

  const [form, setForm] = useState<RoomPayload>({
    code: "",
    floor: 1,
    room_type_id: null,
    price_monthly: "",
    status: "vacant",
  });

  const openCreate = () => {
    setMode("create");
    setEditingId(null);
    setFormErr("");
    setForm({
      code: "",
      floor: 1,
      room_type_id: meta?.room_types?.[0]?.id ?? null,
      price_monthly: "",
      status: "vacant",
    });
    setRoomModalOpen(true);
  };

  const openEdit = (r: RoomRow) => {
    setMode("edit");
    setEditingId(r.id);
    setFormErr("");
    setForm({
      code: r.code ?? "",
      floor: Number(r.floor ?? 1),
      room_type_id: r.room_type?.id ?? r.room_type_id ?? null,
      price_monthly: r.price_monthly ?? "",
      status: r.status ?? "vacant",
    });
    setRoomModalOpen(true);
  };

  const validate = () => {
    const code = (form.code ?? "").trim();
    if (!code) return "กรุณากรอกเลขห้อง (เช่น A101)";
    if (!Number.isFinite(Number(form.floor)) || Number(form.floor) <= 0) return "ชั้นต้องเป็นตัวเลขมากกว่า 0";
    const p = form.price_monthly === "" || form.price_monthly == null ? null : Number(form.price_monthly);
    if (p != null && Number.isNaN(p)) return "ราคา/เดือน ต้องเป็นตัวเลข";
    return "";
  };

  const saveRoom = async () => {
    const msg = validate();
    if (msg) {
      setFormErr(msg);
      return;
    }

    setSaving(true);
    setFormErr("");
    try {
      const payload: RoomPayload = {
        ...form,
        code: form.code.trim(),
        floor: Number(form.floor),
        room_type_id: form.room_type_id ? Number(form.room_type_id) : null,
        price_monthly:
          form.price_monthly === "" || form.price_monthly == null ? null : Number(form.price_monthly),
        status: form.status,
      };

      if (mode === "create") {
        await adminRoomsApi.create(payload);
      } else if (mode === "edit" && editingId != null) {
        await adminRoomsApi.update(editingId, payload);
      }

      setRoomModalOpen(false);
      await loadList(page);
    } catch (e: any) {
      console.error(e);
      setFormErr(e?.response?.data?.message ?? "บันทึกไม่สำเร็จ");
    } finally {
      setSaving(false);
    }
  };

    const deleteRoom = async (r: RoomRow) => {
    const ok = window.confirm(`ยืนยันลบห้อง ${r.code} ?\nการลบอาจกระทบข้อมูลผู้เช่า/บิล ถ้ามีการผูกอยู่`);
    if (!ok) return;

    try {
      await adminRoomsApi.remove(r.id);
      await loadList(page);
    } catch (e: any) {
      console.error(e);
      alert(e?.response?.data?.message ?? "ลบไม่สำเร็จ");
    }
  };


  // ===== data loaders =====
  const loadMeta = async () => {
    try {
      const m = await adminRoomsApi.meta();
      setMeta(m?.data ?? m);
    } catch {
      setMeta(null);
    }
  };

  const normalize = (res: any) => {
    if (Array.isArray(res)) return { data: res as RoomRow[], total: res.length };
    if (isPaged<RoomRow>(res)) return { data: res.data ?? [], total: res.total ?? (res.data?.length ?? 0) };
    if (res && typeof res === "object" && Array.isArray(res.data))
      return { data: res.data as RoomRow[], total: res.total ?? res.data.length };
    return { data: [] as RoomRow[], total: 0 };
  };

  const loadList = async (targetPage = page) => {
    setLoading(true);
    try {
      const res = await adminRoomsApi.list(status, search, targetPage, perPage);
      const norm = normalize(res);

      let dataRows = norm.data;
      if (roomTypeId) {
        const idNum = Number(roomTypeId);
        dataRows = dataRows.filter((r) => (r.room_type?.id ?? r.room_type_id) === idNum);
      }

      setRows(dataRows);
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
    loadMeta();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  useEffect(() => {
    loadList(page);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [page]);

  const onSearch = () => {
    setPage(1);
    loadList(1);
  };

  const summary = useMemo(() => {
    const vacant = rows.filter((r) => r.status === "vacant").length;
    const occupied = rows.filter((r) => r.status === "occupied").length;
    const maintenance = rows.filter((r) => r.status === "maintenance").length;
    return { vacant, occupied, maintenance };
  }, [rows]);

  return (
    <div className="space-y-4">
      {/* Header */}
      <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <div className="text-xs font-extrabold tracking-wide text-slate-500">Admin</div>
          <div className="text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">ห้องพัก</div>
          <div className="mt-1 text-sm text-slate-500">แสดงสถานะห้อง • ผู้เช่าปัจจุบัน • วันพักถึง</div>
        </div>

        <div className="flex flex-wrap gap-2">
          <button
            onClick={() => loadList(page)}
            className="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-900 shadow-sm hover:bg-slate-50"
          >
            Refresh
          </button>
          <button
            onClick={openCreate}
            className="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-blue-700"
          >
            + เพิ่มห้องพัก
          </button>
        </div>
      </div>

      {/* Filters */}
      <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div className="grid gap-3 lg:grid-cols-[260px_260px_1fr_140px] lg:items-center">
          <select
            value={status}
            onChange={(e) => setStatus(e.target.value)}
            className="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-900 outline-none focus:ring-4 focus:ring-blue-100"
          >
            <option value="">ทุกสถานะ</option>
            {(meta?.statuses ?? [
              { value: "vacant", label: "ว่าง" },
              { value: "occupied", label: "มีผู้เช่า" },
              { value: "maintenance", label: "ซ่อมบำรุง" },
            ]).map((s) => (
              <option key={s.value} value={s.value}>
                {s.label}
              </option>
            ))}
          </select>

          <select
            value={roomTypeId}
            onChange={(e) => setRoomTypeId(e.target.value)}
            className="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-900 outline-none focus:ring-4 focus:ring-blue-100"
          >
            <option value="">ทุกประเภทห้อง</option>
            {(meta?.room_types ?? []).map((t) => (
              <option key={t.id} value={String(t.id)}>
                {t.name}
              </option>
            ))}
          </select>

          <input
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="ค้นหาเลขห้อง เช่น A101"
            className="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-900 outline-none placeholder:text-slate-400 focus:ring-4 focus:ring-blue-100"
            onKeyDown={(e) => e.key === "Enter" && onSearch()}
          />

          <button
            onClick={onSearch}
            className="h-11 rounded-xl bg-blue-600 px-4 text-sm font-extrabold text-white shadow-sm hover:bg-blue-700"
          >
            Search
          </button>
        </div>

        <div className="mt-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
          <div className="flex flex-wrap gap-2 text-xs font-semibold text-slate-600">
            <span className="rounded-full bg-emerald-50 px-3 py-1 text-emerald-700 ring-1 ring-emerald-200">
              ว่าง {summary.vacant}
            </span>
            <span className="rounded-full bg-sky-50 px-3 py-1 text-sky-700 ring-1 ring-sky-200">
              มีผู้เช่า {summary.occupied}
            </span>
            <span className="rounded-full bg-amber-50 px-3 py-1 text-amber-800 ring-1 ring-amber-200">
              ซ่อมบำรุง {summary.maintenance}
            </span>
          </div>

          <div className="text-right text-sm font-semibold text-slate-500">
            รวมทั้งหมด <span className="font-black text-slate-900">{total}</span> ห้อง
          </div>
        </div>
      </div>

      {/* Cards */}
      <div className="rounded-2xl border border-slate-500 bg-white p-4 shadow-sm">
        {loading && <div className="p-3 text-sm text-slate-500">Loading...</div>}
        {!loading && rows.length === 0 && <div className="p-3 text-sm text-slate-500">ไม่พบข้อมูล</div>}

        {!loading && rows.length > 0 && (
          <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            {rows.map((r) => {
              const aa =
                (r as any).active_assignment ??
                (r as any).activeAssignment ??
                (r as any).current_assignment ??
                (r as any).currentAssignment ??
                null;

              const tenant =
                aa?.tenant ??
                (r as any).tenant ??
                null;

              const user =
                tenant?.user ??
                tenant?.user_profile ??
                null;

              const startDate =
                aa?.start_date ??
                tenant?.start_date ??
                (r as any).start_date ??
                null;

              const endDate =
                aa?.end_date ??
                tenant?.end_date ??
                (r as any).end_date ??
                null;

              const tenantName = user?.name ?? "-";
              const tenantPhone = user?.phone ?? "-";
              const tenantEmail = user?.email ?? "-";


              return (
                <div
                  key={r.id}
                  className={[
                    "rounded-2xl border border-slate-500 bg-white p-4 shadow-sm",
                    "ring-1 ring-transparent transition",
                    "hover:-translate-y-0.5 hover:shadow-md hover:ring-4",
                    cardAccentRing(r.status),
                  ].join(" ")}
                >
                  <div className="flex items-start justify-between gap-3">
                    <div>
                      <div className="text-xl font-black tracking-tight text-slate-900">{r.code}</div>
                      <div className="mt-0.5 text-sm font-semibold text-slate-500">
                        ชั้น {r.floor} • {r.room_type?.name ?? "ไม่ระบุประเภท"}
                      </div>
                    </div>

                    <div className="flex items-center gap-2">
                      <span
                        className={[
                          "inline-flex items-center rounded-full px-3 py-1 text-xs font-extrabold ring-1",
                          badgeClass(r.status),
                        ].join(" ")}
                      >
                        {statusLabel(r.status, meta)}
                      </span>

                      <button
                        onClick={() => openEdit(r)}
                        className="rounded-xl border border-slate-200 bg-blue-400 px-3 py-1.5 text-xs font-extrabold text-slate-900 hover:bg-slate-50"
                      >
                        แก้ไข
                      </button>

                      <button
                        onClick={() => deleteRoom(r)}
                        className="rounded-xl border border-rose-300 bg-rose-50 px-3 py-1.5 text-xs font-extrabold text-rose-700 hover:bg-rose-100"
                      >
                        ลบ
                      </button>
                    </div>
                  </div>

                  <div className="mt-4 space-y-3">
                    <div className="flex items-end justify-between">
                      <div className="text-xs font-semibold text-slate-500">ราคา/เดือน</div>
                      <div className="text-lg font-black text-blue-600">฿ {formatMoney(r.price_monthly)}</div>
                    </div>

                    <div className="rounded-xl bg-slate-50 p-3">
                      <div className="text-xs font-extrabold text-slate-600">ข้อมูลผู้เช่า</div>
                      <div className="mt-2 space-y-2">
                        <InfoRow label="ผู้เช่า" value={r.status === "occupied" ? tenantName : "-"} />
                        <InfoRow label="ติดต่อ" value={r.status === "occupied" ? `${tenantPhone} • ${tenantEmail}` : "-"} />
                        <InfoRow label="เริ่มพัก" value={formatDateTH(startDate)} />
                        <InfoRow label="พักถึง" value={formatDateTH(endDate)} />
                      </div>
                    </div>
                  </div>
                </div>
              );
            })}
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
            >
              ถัดไป
            </button>
          </div>
        </div>
      </div>

      {/* ===== Add/Edit Modal ===== */}
      <Modal
        open={roomModalOpen}
        title={mode === "create" ? "เพิ่มห้องพัก" : "แก้ไขห้องพัก"}
        onClose={() => setRoomModalOpen(false)}
      >
        {formErr && (
          <div className="mb-3 rounded-xl bg-rose-50 p-3 text-sm font-semibold text-rose-700 ring-1 ring-rose-200">
            {formErr}
          </div>
        )}

        <div className="grid gap-3 sm:grid-cols-2">
          <div className="sm:col-span-1">
            <div className="mb-1 text-xs font-extrabold text-slate-600">เลขห้อง</div>
            <input
              value={form.code}
              onChange={(e) => setForm((x) => ({ ...x, code: e.target.value }))}
              className="h-11 w-full rounded-xl border border-slate-200 px-3 text-sm font-semibold outline-none focus:ring-4 focus:ring-blue-100"
              placeholder="เช่น A101"
            />
          </div>

          <div className="sm:col-span-1">
            <div className="mb-1 text-xs font-extrabold text-slate-600">ชั้น</div>
            <input
              type="number"
              value={form.floor}
              onChange={(e) => setForm((x) => ({ ...x, floor: Number(e.target.value) }))}
              className="h-11 w-full rounded-xl border border-slate-200 px-3 text-sm font-semibold outline-none focus:ring-4 focus:ring-blue-100"
              min={1}
            />
          </div>

          <div className="sm:col-span-1">
            <div className="mb-1 text-xs font-extrabold text-slate-600">ประเภทห้อง</div>
            <select
              value={form.room_type_id ?? ""}
              onChange={(e) => setForm((x) => ({ ...x, room_type_id: e.target.value ? Number(e.target.value) : null }))}
              className="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold outline-none focus:ring-4 focus:ring-blue-100"
            >
              <option value="">ไม่ระบุ</option>
              {(meta?.room_types ?? []).map((t) => (
                <option key={t.id} value={t.id}>
                  {t.name}
                </option>
              ))}
            </select>
          </div>

          <div className="sm:col-span-1">
            <div className="mb-1 text-xs font-extrabold text-slate-600">ราคา/เดือน</div>
            <input
              value={form.price_monthly ?? ""}
              onChange={(e) => setForm((x) => ({ ...x, price_monthly: e.target.value }))}
              className="h-11 w-full rounded-xl border border-slate-200 px-3 text-sm font-semibold outline-none focus:ring-4 focus:ring-blue-100"
              placeholder="เช่น 4500"
              inputMode="numeric"
            />
          </div>

          <div className="sm:col-span-2">
            <div className="mb-1 text-xs font-extrabold text-slate-600">สถานะ</div>
            <select
              value={form.status}
              onChange={(e) => setForm((x) => ({ ...x, status: e.target.value }))}
              className="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold outline-none focus:ring-4 focus:ring-blue-100"
            >
              {(meta?.statuses ?? [
                { value: "vacant", label: "ว่าง" },
                { value: "occupied", label: "มีผู้เช่า" },
                { value: "maintenance", label: "ซ่อมบำรุง" },
              ]).map((s) => (
                <option key={s.value} value={s.value}>
                  {s.label}
                </option>
              ))}
            </select>

            <div className="mt-2 text-xs font-semibold text-slate-500">
              * แนะนำ: ถ้าห้องมีผู้เช่าอยู่ อย่าเปลี่ยนเป็น “ว่าง” ถ้ายังไม่ย้ายผู้เช่าออก
            </div>
          </div>
        </div>

        <div className="mt-4 flex items-center justify-end gap-2">
          <button
            onClick={() => setRoomModalOpen(false)}
            className="h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm font-extrabold text-slate-900 hover:bg-slate-50"
            disabled={saving}
          >
            ยกเลิก
          </button>
          <button
            onClick={saveRoom}
            className="h-11 rounded-xl bg-blue-600 px-4 text-sm font-extrabold text-white shadow-sm hover:bg-blue-700 disabled:opacity-60"
            disabled={saving}
          >
            {saving ? "กำลังบันทึก..." : "บันทึก"}
          </button>
        </div>
      </Modal>
    </div>
  );
}
