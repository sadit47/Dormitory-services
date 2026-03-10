import { useEffect, useMemo, useRef, useState } from "react";
import { api } from "@/shared/api/axios";
import { adminParcelsApi } from "./services/adminParcelsApi";
import type { ParcelPayload } from "./services/adminParcelsApi";


type ParcelRow = {
  id: number;
  tenant_id: number;
  room_id?: number | null;
  tracking_no?: string | null;
  courier?: string | null;
  sender_name?: string | null;
  note?: string | null;
  status: "arrived" | "picked_up" | "cancelled" | string;
  received_at?: string | null;
  picked_up_at?: string | null;
  tenant?: {
    id: number;
    user?: { name?: string; email?: string; phone?: string } | null;
  } | null;
  room?: {
    id: number;
    code?: string | null;
    room_no?: string | null;
  } | null;
  receivedBy?: { id: number; name?: string } | null;
  pickedUpBy?: { id: number; name?: string } | null;
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

type TenantOption = {
  id: number;
  label: string;
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
  if (status === "arrived") return "bg-amber-50 text-amber-800 ring-amber-200";
  if (status === "picked_up") return "bg-emerald-50 text-emerald-700 ring-emerald-200";
  if (status === "cancelled") return "bg-rose-50 text-rose-700 ring-rose-200";
  return "bg-slate-100 text-slate-700 ring-slate-200";
}

function cardAccentRing(status?: string) {
  if (status === "arrived") return "hover:ring-amber-200";
  if (status === "picked_up") return "hover:ring-emerald-200";
  if (status === "cancelled") return "hover:ring-rose-200";
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
        <div className="w-full max-w-2xl rounded-2xl bg-white shadow-xl ring-1 ring-slate-200">
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

export default function AdminParcelsPage() {
  const [rows, setRows] = useState<ParcelRow[]>([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(false);

  const [status, setStatus] = useState("");
  const [search, setSearch] = useState("");

  const [tenantOptions, setTenantOptions] = useState<TenantOption[]>([]);

  const [page, setPage] = useState(1);
  const perPage = 12;
  const totalPages = useMemo(() => Math.max(1, Math.ceil(total / perPage)), [total]);

  const [parcelModalOpen, setParcelModalOpen] = useState(false);
  const [mode, setMode] = useState<"create" | "edit">("create");
  const [editingId, setEditingId] = useState<number | null>(null);
  const [saving, setSaving] = useState(false);
  const [formErr, setFormErr] = useState("");

  const imgUrlCacheRef = useRef<Map<number, string>>(new Map());

  const [form, setForm] = useState<ParcelPayload>({
    tenant_id: 0,
    tracking_no: "",
    courier: "",
    sender_name: "",
    note: "",
    images: [],
  });

  const openCreate = () => {
    setMode("create");
    setEditingId(null);
    setFormErr("");
    setForm({
      tenant_id: 0,
      tracking_no: "",
      courier: "",
      sender_name: "",
      note: "",
      images: [],
    });
    setParcelModalOpen(true);
  };

  const openEdit = (r: ParcelRow) => {
    setMode("edit");
    setEditingId(r.id);
    setFormErr("");
    setForm({
      tenant_id: r.tenant_id,
      tracking_no: r.tracking_no ?? "",
      courier: r.courier ?? "",
      sender_name: r.sender_name ?? "",
      note: r.note ?? "",
      images: [],
    });
    setParcelModalOpen(true);
  };

  const validate = () => {
    if (!Number(form.tenant_id)) return "กรุณาเลือกผู้เช่า";
    return "";
  };

  const normalize = (res: any) => {
    if (Array.isArray(res)) return { data: res as ParcelRow[], total: res.length };
    if (isPaged<ParcelRow>(res)) return { data: res.data ?? [], total: res.total ?? (res.data?.length ?? 0) };
    if (res && typeof res === "object" && Array.isArray(res.data)) {
      return { data: res.data as ParcelRow[], total: res.total ?? res.data.length };
    }
    return { data: [] as ParcelRow[], total: 0 };
  };

  const loadTenantOptions = async () => {
    try {
      const res = await api.get("/admin/tenants", { params: { page: 1, per_page: 200 } });
      const root = res?.data?.data ?? res?.data ?? {};
      const list = Array.isArray(root?.data) ? root.data : Array.isArray(root) ? root : [];

      setTenantOptions(
        list.map((t: any) => ({
          id: t.id,
          label: `${t?.user?.name ?? "ผู้เช่า"}${t?.current_room?.code ? ` • ${t.current_room.code}` : ""}`,
        }))
      );
    } catch (e) {
      console.error(e);
      setTenantOptions([]);
    }
  };

  const loadList = async (targetPage = page) => {
    setLoading(true);
    try {
      const res = await adminParcelsApi.list(status, search, targetPage, perPage);
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

  const saveParcel = async () => {
    const msg = validate();
    if (msg) {
      setFormErr(msg);
      return;
    }

    setSaving(true);
    setFormErr("");
    try {
      const payload: ParcelPayload = {
        tenant_id: Number(form.tenant_id),
        tracking_no: form.tracking_no?.trim() || null,
        courier: form.courier?.trim() || null,
        sender_name: form.sender_name?.trim() || null,
        note: form.note?.trim() || null,
        images: form.images ?? [],
      };

      if (mode === "create") {
        await adminParcelsApi.create(payload);
      } else if (mode === "edit" && editingId != null) {
        await adminParcelsApi.update(editingId, payload);
      }

      setParcelModalOpen(false);
      await loadList(page);
    } catch (e: any) {
      console.error(e);
      setFormErr(e?.response?.data?.message ?? "บันทึกไม่สำเร็จ");
    } finally {
      setSaving(false);
    }
  };

  const pickupParcel = async (r: ParcelRow) => {
    const ok = window.confirm(`ยืนยันบันทึกว่าพัสดุ ${r.tracking_no || `#${r.id}`} รับแล้ว ?`);
    if (!ok) return;
    try {
      await adminParcelsApi.pickup(r.id);
      await loadList(page);
    } catch (e: any) {
      console.error(e);
      alert(e?.response?.data?.message ?? "อัปเดตไม่สำเร็จ");
    }
  };

  const deleteParcel = async (r: ParcelRow) => {
    const ok = window.confirm(`ยืนยันลบพัสดุ ${r.tracking_no || `#${r.id}`} ?`);
    if (!ok) return;
    try {
      await adminParcelsApi.remove(r.id);
      await loadList(page);
    } catch (e: any) {
      console.error(e);
      alert(e?.response?.data?.message ?? "ลบไม่สำเร็จ");
    }
  };

  useEffect(() => {
    loadTenantOptions();
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
    const arrived = rows.filter((r) => r.status === "arrived").length;
    const picked_up = rows.filter((r) => r.status === "picked_up").length;
    const cancelled = rows.filter((r) => r.status === "cancelled").length;
    return { arrived, picked_up, cancelled };
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

    const res = await adminParcelsApi.fileBlob(id);
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
      className="group block overflow-hidden rounded-2xl border border-slate-200 bg-slate-50"
    >
      <div className="aspect-square w-full overflow-hidden bg-white">
        {src ? (
          <img
            src={src}
            alt={title}
            className="h-full w-full object-cover transition group-hover:scale-[1.03]"
          />
        ) : (
          <div className="flex h-full items-center justify-center px-3 text-center text-xs font-semibold text-slate-500">
            {err ? err : "กำลังโหลดรูป..."}
          </div>
        )}
      </div>
      <div className="p-2 text-xs font-semibold text-slate-700 line-clamp-1">{title}</div>
    </a>
  );
}

  return (
    <div className="space-y-4">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <div className="text-xs font-extrabold tracking-wide text-slate-500">Admin</div>
          <div className="text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">พัสดุ</div>
          <div className="mt-1 text-sm text-slate-500">บันทึกพัสดุ • แนบรูป • ติดตามสถานะการรับของ</div>
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
            + เพิ่มพัสดุ
          </button>
        </div>
      </div>

      <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div className="grid gap-3 lg:grid-cols-[240px_1fr_140px] lg:items-center">
          <select
            value={status}
            onChange={(e) => setStatus(e.target.value)}
            className="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-900 outline-none focus:ring-4 focus:ring-blue-100"
          >
            <option value="">ทุกสถานะ</option>
            <option value="arrived">รอรับ</option>
            <option value="picked_up">รับแล้ว</option>
            <option value="cancelled">ยกเลิก</option>
          </select>

          <input
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="ค้นหาเลขพัสดุ / ผู้ส่ง / ขนส่ง / ชื่อผู้เช่า"
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
            <span className="rounded-full bg-amber-50 px-3 py-1 text-amber-800 ring-1 ring-amber-200">
              รอรับ {summary.arrived}
            </span>
            <span className="rounded-full bg-emerald-50 px-3 py-1 text-emerald-700 ring-1 ring-emerald-200">
              รับแล้ว {summary.picked_up}
            </span>
            <span className="rounded-full bg-rose-50 px-3 py-1 text-rose-700 ring-1 ring-rose-200">
              ยกเลิก {summary.cancelled}
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
          <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            {rows.map((r) => {
              const tenantName = r.tenant?.user?.name ?? "-";
              const tenantPhone = r.tenant?.user?.phone ?? "-";
              const tenantEmail = r.tenant?.user?.email ?? "-";
              const roomCode = r.room?.code ?? r.room?.room_no ?? "-";

              return (
                <div
                  key={r.id}
                  className={[
                    "rounded-2xl border border-slate-300 bg-white p-4 shadow-sm",
                    "ring-1 ring-transparent transition",
                    "hover:-translate-y-0.5 hover:shadow-md hover:ring-4",
                    cardAccentRing(r.status),
                  ].join(" ")}
                >
                  <div className="flex items-start justify-between gap-3">
                    <div>
                      <div className="text-xl font-black tracking-tight text-slate-900">
                        {r.tracking_no || `Parcel #${r.id}`}
                      </div>
                      <div className="mt-0.5 text-sm font-semibold text-slate-500">
                        ห้อง {roomCode} • {r.courier || "ไม่ระบุขนส่ง"}
                      </div>
                    </div>

                    <div className="flex items-center gap-2">
                      <span
                        className={[
                          "inline-flex items-center rounded-full px-3 py-1 text-xs font-extrabold ring-1",
                          badgeClass(r.status),
                        ].join(" ")}
                      >
                        {statusLabel(r.status)}
                      </span>

                      <button
                        onClick={() => openEdit(r)}
                        className="rounded-xl border border-slate-200 bg-blue-400 px-3 py-1.5 text-xs font-extrabold text-slate-900 hover:bg-slate-50"
                      >
                        แก้ไข
                      </button>

                      <button
                        onClick={() => deleteParcel(r)}
                        className="rounded-xl border border-rose-300 bg-rose-50 px-3 py-1.5 text-xs font-extrabold text-rose-700 hover:bg-rose-100"
                      >
                        ลบ
                      </button>
                    </div>
                  </div>

                  <div className="mt-4 space-y-3">
                    <div className="rounded-xl bg-slate-50 p-3">
                      <div className="text-xs font-extrabold text-slate-600">ข้อมูลผู้เช่า</div>
                      <div className="mt-2 space-y-2">
                        <InfoRow label="ผู้เช่า" value={tenantName} />
                        <InfoRow label="ติดต่อ" value={`${tenantPhone} • ${tenantEmail}`} />
                        <InfoRow label="วันที่รับเข้า" value={formatDateTH(r.received_at)} />
                      </div>
                    </div>

                    <div className="rounded-xl bg-slate-50 p-3">
                      <div className="text-xs font-extrabold text-slate-600">รายละเอียดพัสดุ</div>
                      <div className="mt-2 space-y-2">
                        <InfoRow label="ผู้ส่ง" value={r.sender_name || "-"} />
                        <InfoRow label="หมายเหตุ" value={r.note || "-"} />
                        <InfoRow label="วันที่รับแล้ว" value={formatDateTH(r.picked_up_at)} />
                        <div className="pt-1">
                        <div className="text-xs font-extrabold text-slate-600">รูปพัสดุ</div>
                        {(r.files ?? []).length === 0 ? (
                            <div className="mt-2 text-xs font-semibold text-slate-500">ไม่มีรูปพัสดุ</div>
                        ) : (
                            <div className="mt-3 grid gap-3 sm:grid-cols-2">
                            {(r.files ?? []).map((f, idx) => (
                                <ImageTile key={f.id ?? idx} f={f} idx={idx} />
                            ))}
                            </div>
                        )}
                        </div>
                      </div>
                    </div>

                    {r.status !== "picked_up" && (
                      <button
                        onClick={() => pickupParcel(r)}
                        className="w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-extrabold text-white shadow-sm hover:bg-emerald-700"
                      >
                        บันทึกว่ารับพัสดุแล้ว
                      </button>
                    )}
                  </div>
                </div>
              );
            })}
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

      <Modal
        open={parcelModalOpen}
        title={mode === "create" ? "เพิ่มพัสดุ" : "แก้ไขพัสดุ"}
        onClose={() => setParcelModalOpen(false)}
      >
        {formErr && (
          <div className="mb-3 rounded-xl bg-rose-50 p-3 text-sm font-semibold text-rose-700 ring-1 ring-rose-200">
            {formErr}
          </div>
        )}

        <div className="grid gap-3 sm:grid-cols-2">
          <div className="sm:col-span-2">
            <div className="mb-1 text-xs font-extrabold text-slate-600">ผู้เช่า</div>
            <select
              value={form.tenant_id || ""}
              onChange={(e) => setForm((x) => ({ ...x, tenant_id: Number(e.target.value) }))}
              className="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold outline-none focus:ring-4 focus:ring-blue-100"
              disabled={mode === "edit"}
            >
              <option value="">เลือกผู้เช่า</option>
              {tenantOptions.map((t) => (
                <option key={t.id} value={t.id}>
                  {t.label}
                </option>
              ))}
            </select>
          </div>

          <div>
            <div className="mb-1 text-xs font-extrabold text-slate-600">เลขพัสดุ</div>
            <input
              value={form.tracking_no ?? ""}
              onChange={(e) => setForm((x) => ({ ...x, tracking_no: e.target.value }))}
              className="h-11 w-full rounded-xl border border-slate-200 px-3 text-sm font-semibold outline-none focus:ring-4 focus:ring-blue-100"
              placeholder="เช่น TH123456789"
            />
          </div>

          <div>
            <div className="mb-1 text-xs font-extrabold text-slate-600">ขนส่ง</div>
            <input
              value={form.courier ?? ""}
              onChange={(e) => setForm((x) => ({ ...x, courier: e.target.value }))}
              className="h-11 w-full rounded-xl border border-slate-200 px-3 text-sm font-semibold outline-none focus:ring-4 focus:ring-blue-100"
              placeholder="เช่น Flash / Kerry"
            />
          </div>

          <div>
            <div className="mb-1 text-xs font-extrabold text-slate-600">ผู้ส่ง</div>
            <input
              value={form.sender_name ?? ""}
              onChange={(e) => setForm((x) => ({ ...x, sender_name: e.target.value }))}
              className="h-11 w-full rounded-xl border border-slate-200 px-3 text-sm font-semibold outline-none focus:ring-4 focus:ring-blue-100"
              placeholder="เช่น Shopee / Lazada"
            />
          </div>

          <div>
            <div className="mb-1 text-xs font-extrabold text-slate-600">แนบรูป</div>
            <input
              type="file"
              multiple
              onChange={(e) => setForm((x) => ({ ...x, images: Array.from(e.target.files ?? []) }))}
              className="h-11 w-full rounded-xl border border-slate-200 px-3 pt-2 text-sm font-semibold outline-none focus:ring-4 focus:ring-blue-100"
            />
          </div>

          <div className="sm:col-span-2">
            <div className="mb-1 text-xs font-extrabold text-slate-600">หมายเหตุ</div>
            <textarea
              value={form.note ?? ""}
              onChange={(e) => setForm((x) => ({ ...x, note: e.target.value }))}
              className="min-h-24 w-full rounded-xl border border-slate-200 px-3 py-3 text-sm font-semibold outline-none focus:ring-4 focus:ring-blue-100"
              placeholder="รายละเอียดเพิ่มเติม"
            />
          </div>
        </div>

        <div className="mt-4 flex items-center justify-end gap-2">
          <button
            onClick={() => setParcelModalOpen(false)}
            className="h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm font-extrabold text-slate-900 hover:bg-slate-50"
            disabled={saving}
          >
            ยกเลิก
          </button>
          <button
            onClick={saveParcel}
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