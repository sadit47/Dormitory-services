import { useEffect, useMemo, useState } from "react";
import { adminInvoicesApi } from "./services/adminInvoicesApi";
import type {
  InvoiceCreatePayload,
  InvoiceUpdatePayload,
  InvoiceType,
} from "./services/adminInvoicesApi";

/* ===================== Types ===================== */
type InvoiceRow = {
  id: number;
  invoice_no?: string;

  tenant_id?: number;
  room_id?: number | null;

  type?: InvoiceType | string;
  status?: "unpaid" | "paid" | "void" | "draft" | string;

  period_month?: number;
  period_year?: number;

  due_date?: string | null;

  subtotal?: number | string | null;
  discount?: number | string | null;
  total?: number | string | null;

  tenant?: {
    id?: number;
    user?: { name?: string; email?: string; phone?: string } | null;
  } | null;

  room?: { id?: number; code?: string } | null;

  items?: { description?: string; qty?: number; unit_price?: number }[];
};

type Paged<T> = {
  current_page?: number;
  data?: T[];
  total?: number;
  per_page?: number;
  last_page?: number;
};

type InvoicesMeta = {
  tenants?: { id: number; user?: { name?: string; email?: string } | null }[];
  rooms?: { id: number; code?: string; floor?: number; status?: string }[];
};

type ItemForm = {
  description: string;
  qty: number;
  unit_price: number;
};

type UtilityForm = {
  water_units: number;
  water_unit_price: number;
  electric_units: number;
  electric_unit_price: number;
};

function isPaged<T>(x: any): x is Paged<T> {
  return x && typeof x === "object" && Array.isArray(x.data);
}

/* ===================== Helpers ===================== */
function money(v: any) {
  const n = Number(v ?? 0);
  if (Number.isNaN(n)) return "-";
  return n.toLocaleString("th-TH", {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  });
}

function typeLabel(t?: string) {
  if (!t) return "-";
  if (t === "rent") return "ค่าเช่า";
  if (t === "utility") return "ค่าน้ำ/ไฟ";
  if (t === "repair") return "ซ่อมแซม";
  if (t === "cleaning") return "ทำความสะอาด";
  return t;
}

function statusLabel(s?: string) {
  if (!s) return "-";
  if (s === "unpaid") return "ค้างชำระ";
  if (s === "paid") return "ชำระแล้ว";
  if (s === "void") return "ยกเลิก";
  if (s === "draft") return "ร่าง";
  return s;
}

function badgeClass(status?: string) {
  if (status === "paid") return "bg-emerald-50 text-emerald-700 ring-emerald-200";
  if (status === "unpaid") return "bg-amber-50 text-amber-800 ring-amber-200";
  if (status === "void") return "bg-rose-50 text-rose-700 ring-rose-200";
  if (status === "draft") return "bg-slate-100 text-slate-700 ring-slate-200";
  return "bg-slate-100 text-slate-700 ring-slate-200";
}

function calcSubtotal(items: ItemForm[]) {
  return items.reduce(
    (sum, it) => sum + Number(it.qty || 0) * Number(it.unit_price || 0),
    0
  );
}

function buildUtilityItems(utility: UtilityForm): ItemForm[] {
  const items: ItemForm[] = [];

  if (
    Number(utility.water_units) > 0 ||
    Number(utility.water_unit_price) > 0
  ) {
    items.push({
      description: "ค่าน้ำ",
      qty: Number(utility.water_units || 0),
      unit_price: Number(utility.water_unit_price || 0),
    });
  }

  if (
    Number(utility.electric_units) > 0 ||
    Number(utility.electric_unit_price) > 0
  ) {
    items.push({
      description: "ค่าไฟฟ้า",
      qty: Number(utility.electric_units || 0),
      unit_price: Number(utility.electric_unit_price || 0),
    });
  }

  return items.length
    ? items
    : [
        { description: "ค่าน้ำ", qty: 0, unit_price: 0 },
        { description: "ค่าไฟฟ้า", qty: 0, unit_price: 0 },
      ];
}

function extractUtilityForm(items: ItemForm[]): UtilityForm {
  const waterItem = items.find((x) => String(x.description).includes("น้ำ"));
  const electricItem = items.find(
    (x) =>
      String(x.description).includes("ไฟ") ||
      String(x.description).toLowerCase().includes("electric")
  );

  return {
    water_units: Number(waterItem?.qty ?? 0),
    water_unit_price: Number(waterItem?.unit_price ?? 0),
    electric_units: Number(electricItem?.qty ?? 0),
    electric_unit_price: Number(electricItem?.unit_price ?? 0),
  };
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
          <div className="max-h-[80vh] overflow-y-auto p-4">{children}</div>
        </div>
      </div>
    </div>
  );
}

/* ===================== Page ===================== */
export default function AdminInvoicesPage() {
  const [rows, setRows] = useState<InvoiceRow[]>([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(false);

  const [meta, setMeta] = useState<InvoicesMeta | null>(null);

  // filters
  const [q, setQ] = useState("");
  const [type, setType] = useState<string>("");
  const [status, setStatus] = useState<string>("");

  // paging
  const [page, setPage] = useState(1);
  const perPage = 10;
  const totalPages = useMemo(
    () => Math.max(1, Math.ceil(total / perPage)),
    [total]
  );

  // modal
  const [open, setOpen] = useState(false);
  const [mode, setMode] = useState<"create" | "edit">("create");
  const [editingId, setEditingId] = useState<number | null>(null);
  const [saving, setSaving] = useState(false);
  const [formErr, setFormErr] = useState("");

  const [form, setForm] = useState<InvoiceCreatePayload>({
    tenant_id: 0,
    room_id: null,
    type: "rent",
    period_month: new Date().getMonth() + 1,
    period_year: new Date().getFullYear(),
    due_date: null,
    discount: 0,
    items: [{ description: "ค่าเช่า", qty: 1, unit_price: 0 }],
  });

  const [utilityForm, setUtilityForm] = useState<UtilityForm>({
    water_units: 0,
    water_unit_price: 0,
    electric_units: 0,
    electric_unit_price: 0,
  });

  const subtotal = useMemo(
    () => calcSubtotal(form.items as ItemForm[]),
    [form.items]
  );

  const totalPrice = useMemo(
    () => Math.max(0, subtotal - Number(form.discount ?? 0)),
    [subtotal, form.discount]
  );

  /* ---------- PDF ---------- */
  const openPdf = async (id: number) => {
    try {
      const res = await adminInvoicesApi.downloadPdf(id);
      const blob = new Blob([res.data], { type: "application/pdf" });
      const url = URL.createObjectURL(blob);

      window.open(url, "_blank", "noopener,noreferrer");
      setTimeout(() => URL.revokeObjectURL(url), 60_000);
    } catch (e: any) {
      console.error(e);
      alert(e?.response?.data?.message ?? "เปิด PDF ไม่สำเร็จ");
    }
  };

  /* ---------- loaders ---------- */
  const loadMeta = async () => {
    try {
      const m = await adminInvoicesApi.meta();
      setMeta(m ?? null);
    } catch (e) {
      console.error(e);
      setMeta(null);
    }
  };

  const loadList = async (targetPage = page) => {
    setLoading(true);
    try {
      const res = await adminInvoicesApi.list(q, type, status, targetPage, perPage);

      if (Array.isArray(res)) {
        setRows(res as any);
        setTotal(res.length);
      } else if (isPaged<InvoiceRow>(res)) {
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

  /* ---------- actions ---------- */
  const openCreate = () => {
    setMode("create");
    setEditingId(null);
    setFormErr("");

    const now = new Date();
    const firstTenant = meta?.tenants?.[0]?.id ?? 0;

    setUtilityForm({
      water_units: 0,
      water_unit_price: 0,
      electric_units: 0,
      electric_unit_price: 0,
    });

    setForm({
      tenant_id: firstTenant,
      room_id: null,
      type: "rent",
      period_month: now.getMonth() + 1,
      period_year: now.getFullYear(),
      due_date: null,
      discount: 0,
      items: [{ description: "ค่าเช่า", qty: 1, unit_price: 0 }],
    });

    setOpen(true);
  };

  const openEdit = async (r: InvoiceRow) => {
    setMode("edit");
    setEditingId(r.id);
    setFormErr("");
    setOpen(true);

    try {
      const data = await adminInvoicesApi.show(r.id);

      const itemsRaw = Array.isArray((data as any)?.items)
        ? (data as any).items
        : [];

      const items: ItemForm[] =
        itemsRaw.length > 0
          ? itemsRaw.map((it: any) => ({
              description: String(it?.description ?? ""),
              qty: Number(it?.qty ?? 1),
              unit_price: Number(it?.unit_price ?? 0),
            }))
          : [{ description: "ค่าเช่า", qty: 1, unit_price: 0 }];

      const nextType = ((data as any)?.type ?? r.type ?? "rent") as InvoiceType;

      setForm({
        tenant_id: Number((data as any)?.tenant_id ?? r.tenant_id ?? 0),
        room_id: (data as any)?.room_id
          ? Number((data as any).room_id)
          : r.room_id ?? null,
        type: nextType,
        period_month: Number(
          (data as any)?.period_month ??
            r.period_month ??
            new Date().getMonth() + 1
        ),
        period_year: Number(
          (data as any)?.period_year ??
            r.period_year ??
            new Date().getFullYear()
        ),
        due_date: ((data as any)?.due_date ?? r.due_date ?? null) as any,
        discount: Number((data as any)?.discount ?? r.discount ?? 0),
        items,
      });

      if (nextType === "utility") {
        setUtilityForm(extractUtilityForm(items));
      } else {
        setUtilityForm({
          water_units: 0,
          water_unit_price: 0,
          electric_units: 0,
          electric_unit_price: 0,
        });
      }
    } catch (e) {
      console.error(e);
      setFormErr("โหลดข้อมูลใบแจ้งหนี้ไม่สำเร็จ");
    }
  };

  const validate = () => {
    if (!form.tenant_id) return "กรุณาเลือกผู้เช่า";
    if (!form.type) return "กรุณาเลือกประเภท";
    if (!form.period_month || form.period_month < 1 || form.period_month > 12)
      return "เดือนรอบบิลไม่ถูกต้อง";
    if (!form.period_year || form.period_year < 2000 || form.period_year > 2100)
      return "ปีรอบบิลไม่ถูกต้อง";
    if (!form.items || form.items.length < 1) return "ต้องมีรายการอย่างน้อย 1 รายการ";

    if (form.type === "utility") {
      if (
        Number(utilityForm.water_units) < 0 ||
        Number(utilityForm.water_unit_price) < 0 ||
        Number(utilityForm.electric_units) < 0 ||
        Number(utilityForm.electric_unit_price) < 0
      ) {
        return "ค่าหน่วยน้ำ/ไฟต้องไม่ติดลบ";
      }

      const hasWater =
        Number(utilityForm.water_units) > 0 &&
        Number(utilityForm.water_unit_price) >= 0;

      const hasElectric =
        Number(utilityForm.electric_units) > 0 &&
        Number(utilityForm.electric_unit_price) >= 0;

      if (!hasWater && !hasElectric) {
        return "กรุณากรอกข้อมูลค่าน้ำหรือค่าไฟอย่างน้อย 1 รายการ";
      }
    }

    for (const it of form.items as any[]) {
      if (!String(it.description || "").trim()) return "กรอกรายละเอียดรายการให้ครบ";
      if (Number(it.qty ?? 0) <= 0) return "จำนวนต้องมากกว่า 0";
      if (Number(it.unit_price ?? 0) < 0) return "ราคา/หน่วยต้องไม่ติดลบ";
    }

    return "";
  };

  const save = async () => {
    const msg = validate();
    if (msg) return setFormErr(msg);

    setSaving(true);
    setFormErr("");

    try {
      if (mode === "create") {
        const payload: InvoiceCreatePayload = {
          tenant_id: Number(form.tenant_id),
          room_id: form.room_id ? Number(form.room_id) : null,
          type: form.type,
          period_month: Number(form.period_month),
          period_year: Number(form.period_year),
          due_date: form.due_date ? String(form.due_date).slice(0, 10) : null,
          discount: form.discount ?? 0,
          items: form.items.map((it) => ({
            description: String(it.description || "").trim(),
            qty: Number(it.qty),
            unit_price: Number(it.unit_price),
          })),
        };

        await adminInvoicesApi.create(payload);
      } else if (editingId != null) {
        const payload: InvoiceUpdatePayload = {
          due_date: form.due_date ? String(form.due_date).slice(0, 10) : null,
          discount: form.discount ?? 0,
          items: form.items.map((it) => ({
            description: String(it.description || "").trim(),
            qty: Number(it.qty),
            unit_price: Number(it.unit_price),
          })),
        };

        await adminInvoicesApi.update(editingId, payload);
      }

      setOpen(false);
      await loadList(page);
    } catch (e: any) {
      console.error(e);
      setFormErr(e?.response?.data?.message ?? "บันทึกไม่สำเร็จ");
    } finally {
      setSaving(false);
    }
  };

  const removeInvoice = async (r: InvoiceRow) => {
    const ok = window.confirm(
      `ยืนยันลบใบแจ้งหนี้ ${r.invoice_no ?? `#${r.id}`} ?`
    );
    if (!ok) return;

    try {
      await adminInvoicesApi.remove(r.id);
      await loadList(page);
    } catch (e: any) {
      console.error(e);
      alert(e?.response?.data?.message ?? "ลบไม่สำเร็จ");
    }
  };

  return (
    <div className="space-y-4">
      {/* Header */}
      <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <div className="text-xs font-extrabold tracking-wide text-slate-500">
            Admin
          </div>
          <div className="text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">
            ใบแจ้งหนี้
          </div>
          <div className="mt-1 text-sm text-slate-500">
            ค้นหา • กรองประเภท/สถานะ • สร้าง/แก้ไขรายการบิล
          </div>
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
            className="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-indigo-700"
            type="button"
          >
            + สร้างใบแจ้งหนี้
          </button>
        </div>
      </div>

      {/* Filters */}
      <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div className="grid gap-3 lg:grid-cols-[360px_220px_220px_140px] lg:items-center">
          <input
            value={q}
            onChange={(e) => setQ(e.target.value)}
            placeholder="ค้นหาเลขใบแจ้งหนี้ / ชื่อ / อีเมล"
            className="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-900 outline-none placeholder:text-slate-400 focus:ring-4 focus:ring-indigo-100"
            onKeyDown={(e) => e.key === "Enter" && onSearch()}
          />

          <select
            value={type}
            onChange={(e) => setType(e.target.value)}
            className="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-900 outline-none focus:ring-4 focus:ring-indigo-100"
          >
            <option value="">ทุกประเภท</option>
            <option value="rent">ค่าเช่า</option>
            <option value="utility">ค่าน้ำ/ไฟ</option>
            <option value="repair">ซ่อมแซม</option>
            <option value="cleaning">ทำความสะอาด</option>
          </select>

          <select
            value={status}
            onChange={(e) => setStatus(e.target.value)}
            className="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-900 outline-none focus:ring-4 focus:ring-indigo-100"
          >
            <option value="">ทุกสถานะ</option>
            <option value="unpaid">ค้างชำระ</option>
            <option value="paid">ชำระแล้ว</option>
            <option value="void">ยกเลิก</option>
            <option value="draft">ร่าง</option>
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
          รวมทั้งหมด <span className="font-black text-slate-900">{total}</span> ใบ
        </div>
      </div>

      {/* Table */}
      <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        {loading && <div className="p-3 text-sm text-slate-500">Loading...</div>}
        {!loading && rows.length === 0 && (
          <div className="p-3 text-sm text-slate-500">ไม่พบข้อมูลใบแจ้งหนี้</div>
        )}

        {!loading && rows.length > 0 && (
          <div className="overflow-x-auto">
            <table className="min-w-full w-full border-collapse">
              <thead>
                <tr className="text-left text-xs font-extrabold text-slate-600">
                  <th className="py-3">เลขใบแจ้งหนี้</th>
                  <th className="py-3">ผู้เช่า</th>
                  <th className="py-3">ห้อง</th>
                  <th className="py-3">ประเภท</th>
                  <th className="py-3 text-right">ยอดรวม</th>
                  <th className="py-3 text-center">สถานะ</th>
                  <th className="py-3 text-right">จัดการ</th>
                </tr>
              </thead>

              <tbody>
                {rows.map((r) => {
                  const tenantName = r.tenant?.user?.name ?? "-";
                  const roomCode = r.room?.code ?? "-";
                  const isPaid = r.status === "paid";

                  return (
                    <tr
                      key={r.id}
                      className="border-t border-slate-100 text-sm font-semibold text-slate-900"
                    >
                      <td className="py-4 font-black">
                        {r.invoice_no ?? `#${r.id}`}
                      </td>
                      <td className="py-4">{tenantName}</td>
                      <td className="py-4 font-black">{roomCode}</td>
                      <td className="py-4">{typeLabel(r.type as any)}</td>
                      <td className="py-4 text-right font-black">
                        ฿ {money(r.total)}
                      </td>
                      <td className="py-4 text-center">
                        <span
                          className={[
                            "inline-flex rounded-full px-3 py-1 text-xs font-extrabold ring-1",
                            badgeClass(r.status),
                          ].join(" ")}
                        >
                          {statusLabel(r.status)}
                        </span>
                      </td>

                      <td className="py-4">
                        <div className="flex justify-end gap-2">
                          <button
                            onClick={() => openPdf(r.id)}
                            className="rounded-xl px-3 py-1.5 text-xs font-extrabold ring-1 border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100"
                            type="button"
                          >
                            ดู PDF
                          </button>

                          <button
                            onClick={() => openEdit(r)}
                            disabled={isPaid}
                            className={[
                              "rounded-xl px-3 py-1.5 text-xs font-extrabold ring-1",
                              isPaid
                                ? "cursor-not-allowed bg-slate-50 text-slate-400 ring-slate-200"
                                : "border border-sky-200 bg-sky-50 text-sky-700 hover:bg-sky-100",
                            ].join(" ")}
                            type="button"
                          >
                            แก้ไข
                          </button>

                          <button
                            onClick={() => removeInvoice(r)}
                            disabled={isPaid}
                            className={[
                              "rounded-xl px-3 py-1.5 text-xs font-extrabold ring-1",
                              isPaid
                                ? "cursor-not-allowed bg-slate-50 text-slate-400 ring-slate-200"
                                : "border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100",
                            ].join(" ")}
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

      {/* Modal Create/Edit */}
      <Modal
        open={open}
        title={
          mode === "create"
            ? "สร้างใบแจ้งหนี้"
            : `แก้ไขใบแจ้งหนี้ #${editingId ?? ""}`
        }
        onClose={() => setOpen(false)}
      >
        {formErr && (
          <div className="mb-3 rounded-xl bg-rose-50 p-3 text-sm font-semibold text-rose-700 ring-1 ring-rose-200">
            {formErr}
          </div>
        )}

        {/* ฟอร์มส่วนบน */}
        <div className="grid gap-3 sm:grid-cols-2">
          <div>
            <div className="mb-1 text-xs font-extrabold text-slate-600">
              ผู้เช่า
            </div>
            <select
              value={form.tenant_id}
              onChange={(e) =>
                setForm((x) => ({ ...x, tenant_id: Number(e.target.value) }))
              }
              className="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold outline-none focus:ring-4 focus:ring-indigo-100"
              disabled={mode === "edit"}
            >
              <option value={0}>เลือกผู้เช่า</option>
              {(meta?.tenants ?? []).map((t) => (
                <option key={t.id} value={t.id}>
                  {t.user?.name ?? `Tenant #${t.id}`}{" "}
                  {t.user?.email ? `(${t.user.email})` : ""}
                </option>
              ))}
            </select>
          </div>

          <div>
            <div className="mb-1 text-xs font-extrabold text-slate-600">ห้อง</div>
            <select
              value={form.room_id ?? ""}
              onChange={(e) =>
                setForm((x) => ({
                  ...x,
                  room_id: e.target.value ? Number(e.target.value) : null,
                }))
              }
              className="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold outline-none focus:ring-4 focus:ring-indigo-100"
              disabled={mode === "edit"}
            >
              <option value="">ไม่ระบุ</option>
              {(meta?.rooms ?? []).map((r) => (
                <option key={r.id} value={r.id}>
                  {r.code ?? `Room #${r.id}`}
                </option>
              ))}
            </select>
          </div>

          <div>
            <div className="mb-1 text-xs font-extrabold text-slate-600">
              ประเภท
            </div>
            <select
              value={form.type}
              onChange={(e) => {
                const nextType = e.target.value as InvoiceType;

                if (nextType === "utility") {
                  const utilityItems = buildUtilityItems(utilityForm);
                  setForm((x) => ({
                    ...x,
                    type: nextType,
                    items: utilityItems,
                  }));
                } else {
                  setForm((x) => {
                    const nextItems =
                      nextType === "rent"
                        ? [{ description: "ค่าเช่า", qty: 1, unit_price: 0 }]
                        : nextType === "repair"
                        ? [{ description: "ค่าซ่อมแซม", qty: 1, unit_price: 0 }]
                        : nextType === "cleaning"
                        ? [{ description: "ค่าทำความสะอาด", qty: 1, unit_price: 0 }]
                        : x.items.length
                        ? x.items
                        : [{ description: "", qty: 1, unit_price: 0 }];

                    return {
                      ...x,
                      type: nextType,
                      items: nextItems,
                    };
                  });
                }
              }}
              className="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold outline-none focus:ring-4 focus:ring-indigo-100"
              disabled={mode === "edit"}
            >
              <option value="rent">ค่าเช่า</option>
              <option value="utility">ค่าน้ำ/ไฟ</option>
              <option value="repair">ซ่อมแซม</option>
              <option value="cleaning">ทำความสะอาด</option>
            </select>

            {mode === "edit" && (
              <div className="mt-1 text-xs font-semibold text-slate-500">
                * แก้ไขไม่ได้ (backend update รองรับเฉพาะ due_date/discount/items)
              </div>
            )}
          </div>

          <div>
            <div className="mb-1 text-xs font-extrabold text-slate-600">
              วันครบกำหนด
            </div>
            <input
              type="date"
              value={(form.due_date ? String(form.due_date) : "").slice(0, 10)}
              onChange={(e) =>
                setForm((x) => ({ ...x, due_date: e.target.value }))
              }
              className="h-11 w-full rounded-xl border border-slate-200 px-3 text-sm font-semibold outline-none focus:ring-4 focus:ring-indigo-100"
            />
          </div>

          <div>
            <div className="mb-1 text-xs font-extrabold text-slate-600">
              เดือนรอบบิล
            </div>
            <input
              type="number"
              min={1}
              max={12}
              value={form.period_month}
              onChange={(e) =>
                setForm((x) => ({
                  ...x,
                  period_month: Number(e.target.value),
                }))
              }
              className="h-11 w-full rounded-xl border border-slate-200 px-3 text-sm font-semibold outline-none focus:ring-4 focus:ring-indigo-100"
              disabled={mode === "edit"}
            />
          </div>

          <div>
            <div className="mb-1 text-xs font-extrabold text-slate-600">
              ปีรอบบิล
            </div>
            <input
              type="number"
              min={2000}
              max={2100}
              value={form.period_year}
              onChange={(e) =>
                setForm((x) => ({
                  ...x,
                  period_year: Number(e.target.value),
                }))
              }
              className="h-11 w-full rounded-xl border border-slate-200 px-3 text-sm font-semibold outline-none focus:ring-4 focus:ring-indigo-100"
              disabled={mode === "edit"}
            />
          </div>

          <div className="sm:col-span-2">
            <div className="mb-1 text-xs font-extrabold text-slate-600">
              ส่วนลด
            </div>
            <input
              type="number"
              min={0}
              value={Number(form.discount ?? 0)}
              onChange={(e) =>
                setForm((x) => ({
                  ...x,
                  discount: Number(e.target.value),
                }))
              }
              className="h-11 w-full rounded-xl border border-slate-200 px-3 text-sm font-semibold outline-none focus:ring-4 focus:ring-indigo-100"
            />
          </div>
        </div>

        {/* Utility form */}
        {form.type === "utility" && (
          <div className="mt-4 rounded-2xl border border-sky-200 bg-sky-50/70 p-4">
            <div className="mb-3 text-sm font-black text-slate-900">
              ข้อมูลค่าน้ำ / ค่าไฟ
            </div>

            <div className="grid gap-3 sm:grid-cols-2">
              <div>
                <div className="mb-1 text-xs font-extrabold text-slate-600">
                  จำนวนหน่วยน้ำ
                </div>
                <input
                  type="number"
                  min={0}
                  step="1"
                  value={utilityForm.water_units}
                  onChange={(e) => {
                    const v = Number(e.target.value);
                    const next = { ...utilityForm, water_units: v };
                    setUtilityForm(next);
                    setForm((x) => ({ ...x, items: buildUtilityItems(next) }));
                  }}
                  className="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold outline-none focus:ring-4 focus:ring-sky-100"
                  placeholder="เช่น 12"
                />
              </div>

              <div>
                <div className="mb-1 text-xs font-extrabold text-slate-600">
                  ราคาต่อหน่วยน้ำ
                </div>
                <input
                  type="number"
                  min={0}
                  step="1"
                  value={utilityForm.water_unit_price}
                  onChange={(e) => {
                    const v = Number(e.target.value);
                    const next = { ...utilityForm, water_unit_price: v };
                    setUtilityForm(next);
                    setForm((x) => ({ ...x, items: buildUtilityItems(next) }));
                  }}
                  className="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold outline-none focus:ring-4 focus:ring-sky-100"
                  placeholder="เช่น 18"
                />
              </div>

              <div>
                <div className="mb-1 text-xs font-extrabold text-slate-600">
                  จำนวนหน่วยไฟฟ้า
                </div>
                <input
                  type="number"
                  min={0}
                  step="1"
                  value={utilityForm.electric_units}
                  onChange={(e) => {
                    const v = Number(e.target.value);
                    const next = { ...utilityForm, electric_units: v };
                    setUtilityForm(next);
                    setForm((x) => ({ ...x, items: buildUtilityItems(next) }));
                  }}
                  className="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold outline-none focus:ring-4 focus:ring-sky-100"
                  placeholder="เช่น 85"
                />
              </div>

              <div>
                <div className="mb-1 text-xs font-extrabold text-slate-600">
                  ราคาต่อหน่วยไฟฟ้า
                </div>
                <input
                  type="number"
                  min={0}
                  step="1"
                  value={utilityForm.electric_unit_price}
                  onChange={(e) => {
                    const v = Number(e.target.value);
                    const next = { ...utilityForm, electric_unit_price: v };
                    setUtilityForm(next);
                    setForm((x) => ({ ...x, items: buildUtilityItems(next) }));
                  }}
                  className="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold outline-none focus:ring-4 focus:ring-sky-100"
                  placeholder="เช่น 7"
                />
              </div>
            </div>

            <div className="mt-3 rounded-xl bg-white px-3 py-2 text-xs font-semibold text-slate-600 ring-1 ring-sky-100">
              ระบบจะสร้างรายการ “ค่าน้ำ” และ “ค่าไฟฟ้า” ให้อัตโนมัติจากข้อมูลด้านบน
            </div>
          </div>
        )}

        {/* Items */}
        <div className="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-3">
          <div className="flex items-center justify-between">
            <div className="text-sm font-black text-slate-900">รายการ</div>

            {form.type !== "utility" && (
              <button
                type="button"
                onClick={() =>
                  setForm((x) => ({
                    ...x,
                    items: [...x.items, { description: "", qty: 1, unit_price: 0 }],
                  }))
                }
                className="rounded-xl bg-indigo-100 px-3 py-1.5 text-xs font-extrabold text-indigo-700 hover:bg-indigo-200"
              >
                + เพิ่มรายการ
              </button>
            )}
          </div>

          <div className="mt-3 space-y-3">
            {(form.items as ItemForm[]).map((it, idx) => (
              <div
                key={idx}
                className={`grid gap-2 items-end ${
                  form.type === "utility"
                    ? "sm:grid-cols-[1fr_120px_160px]"
                    : "sm:grid-cols-[1fr_120px_160px_80px]"
                }`}
              >
                <div>
                  <div className="mb-1 text-xs font-extrabold text-slate-600">
                    รายละเอียด
                  </div>
                  <input
                    value={it.description}
                    disabled={form.type === "utility"}
                    onChange={(e) => {
                      const v = e.target.value;
                      setForm((x) => {
                        const items = [...(x.items as ItemForm[])];
                        items[idx] = { ...items[idx], description: v };
                        return { ...x, items };
                      });
                    }}
                    className={[
                      "h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold outline-none focus:ring-4 focus:ring-indigo-100",
                      form.type === "utility"
                        ? "cursor-not-allowed bg-slate-100 text-slate-500"
                        : "",
                    ].join(" ")}
                  />
                </div>

                <div>
                  <div className="mb-1 text-xs font-extrabold text-slate-600">
                    จำนวน
                  </div>
                  <input
                    type="number"
                    min={0.01}
                    step="1"
                    value={it.qty}
                    onChange={(e) => {
                      const v = Number(e.target.value);
                      setForm((x) => {
                        const items = [...(x.items as ItemForm[])];
                        items[idx] = { ...items[idx], qty: v };
                        return { ...x, items };
                      });

                      if (form.type === "utility") {
                        if (String(it.description).includes("น้ำ")) {
                          setUtilityForm((u) => ({ ...u, water_units: v }));
                        } else if (String(it.description).includes("ไฟ")) {
                          setUtilityForm((u) => ({ ...u, electric_units: v }));
                        }
                      }
                    }}
                    className="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold outline-none focus:ring-4 focus:ring-indigo-100"
                  />
                </div>

                <div>
                  <div className="mb-1 text-xs font-extrabold text-slate-600">
                    ราคา/หน่วย
                  </div>
                  <input
                    type="number"
                    min={0}
                    step="1"
                    value={it.unit_price}
                    onChange={(e) => {
                      const v = Number(e.target.value);
                      setForm((x) => {
                        const items = [...(x.items as ItemForm[])];
                        items[idx] = { ...items[idx], unit_price: v };
                        return { ...x, items };
                      });

                      if (form.type === "utility") {
                        if (String(it.description).includes("น้ำ")) {
                          setUtilityForm((u) => ({ ...u, water_unit_price: v }));
                        } else if (String(it.description).includes("ไฟ")) {
                          setUtilityForm((u) => ({ ...u, electric_unit_price: v }));
                        }
                      }
                    }}
                    className="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold outline-none focus:ring-4 focus:ring-indigo-100"
                  />
                </div>

                {form.type !== "utility" && (
                  <button
                    type="button"
                    disabled={(form.items as ItemForm[]).length <= 1}
                    onClick={() =>
                      setForm((x) => ({
                        ...x,
                        items: (x.items as ItemForm[]).filter((_, i) => i !== idx),
                      }))
                    }
                    className={[
                      "h-11 rounded-xl px-3 text-xs font-extrabold",
                      (form.items as ItemForm[]).length <= 1
                        ? "cursor-not-allowed border border-slate-200 bg-slate-100 text-slate-400"
                        : "border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100",
                    ].join(" ")}
                  >
                    ลบ
                  </button>
                )}
              </div>
            ))}
          </div>

          <div className="mt-3 flex flex-col gap-1 text-sm font-semibold sm:flex-row sm:items-center sm:justify-between">
            <div className="text-slate-600">
              Subtotal:{" "}
              <span className="font-black text-slate-900">฿ {money(subtotal)}</span>
            </div>
            <div className="text-slate-600">
              Total:{" "}
              <span className="font-black text-indigo-700">
                ฿ {money(totalPrice)}
              </span>
            </div>
          </div>
        </div>

        <div className="mt-4 flex items-center justify-end gap-2">
          <button
            onClick={() => setOpen(false)}
            className="h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm font-extrabold text-slate-900 hover:bg-slate-50"
            disabled={saving}
            type="button"
          >
            ยกเลิก
          </button>
          <button
            onClick={save}
            className="h-11 rounded-xl bg-indigo-600 px-4 text-sm font-extrabold text-white shadow-sm hover:bg-indigo-700 disabled:opacity-60"
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