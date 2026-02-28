import { useEffect, useMemo, useState } from "react";
import { adminTenantsApi } from "./services/adminTenantsApi";
import type { TenantPayload } from "./services/adminTenantsApi";

type TenantRow = {
  id: number;
  user?: { name?: string; email?: string; phone?: string };
  citizen_id?: string | null;
  address?: string | null;
  start_date?: string | null;
  end_date?: string | null;
  current_room?: { id?: number; code?: string } | null;
  currentRoom?: { id?: number; code?: string } | null;
};

type Pagination = {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
};

type ApiResponse = {
  ok: boolean;
  message?: string;
  data: any;
  meta?: { pagination?: Partial<Pagination> };
};

function isPaged<T>(
  x: any
): x is {
  data: T[];
  current_page?: number;
  per_page?: number;
  total?: number;
  last_page?: number;
} {
  return x && typeof x === "object" && Array.isArray(x.data);
}

function normalizeTenants(raw: any): { rows: TenantRow[]; pg: Pagination } {
  const defaultPg: Pagination = {
    current_page: 1,
    per_page: 10,
    total: 0,
    last_page: 1,
  };

  // A) array ตรงๆ
  if (Array.isArray(raw)) {
    const total = raw.length;
    return {
      rows: raw as TenantRow[],
      pg: { ...defaultPg, total, per_page: total || 10, last_page: 1 },
    };
  }

  // B) paged ตรงๆ {data,total,current_page,...}
  if (isPaged<TenantRow>(raw)) {
    const rows = (raw.data ?? []) as TenantRow[];
    const total = Number(raw.total ?? rows.length ?? 0);
    const per_page = Number(raw.per_page ?? 10);
    const current_page = Number(raw.current_page ?? 1);
    const last_page = Number(
      raw.last_page ?? Math.max(1, Math.ceil(total / per_page))
    );
    return { rows, pg: { total, per_page, current_page, last_page } };
  }

  // C/D) apiResponse wrapper
  if (raw && typeof raw === "object" && "ok" in raw && "data" in raw) {
    const r = raw as ApiResponse;
    const d = r.data;

    // D) data เป็น paged
    if (isPaged<TenantRow>(d)) {
      const rows = (d.data ?? []) as TenantRow[];
      const total = Number(d.total ?? rows.length ?? 0);
      const per_page = Number(d.per_page ?? r.meta?.pagination?.per_page ?? 10);
      const current_page = Number(
        d.current_page ?? r.meta?.pagination?.current_page ?? 1
      );
      const last_page = Number(
        d.last_page ??
          r.meta?.pagination?.last_page ??
          Math.max(1, Math.ceil(total / per_page))
      );
      return { rows, pg: { total, per_page, current_page, last_page } };
    }

    // C) data เป็น array + meta.pagination
    if (Array.isArray(d)) {
      const rows = d as TenantRow[];
      const pg = r.meta?.pagination ?? {};
      const total = Number(pg.total ?? rows.length ?? 0);
      const per_page = Number(pg.per_page ?? 10);
      const current_page = Number(pg.current_page ?? 1);
      const last_page = Number(
        pg.last_page ?? Math.max(1, Math.ceil(total / per_page))
      );
      return { rows, pg: { total, per_page, current_page, last_page } };
    }
  }

  return { rows: [], pg: defaultPg };
}

function formatDateTH(v?: string | null) {
  if (!v) return "-";
  const d = new Date(v);
  if (Number.isNaN(d.getTime())) return String(v);
  return new Intl.DateTimeFormat("th-TH", {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
  }).format(d);
}

/* ---------- Simple Modal (inline style) ---------- */
function Modal(props: {
  open: boolean;
  title: string;
  onClose: () => void;
  children: React.ReactNode;
}) {
  if (!props.open) return null;
  return (
    <div style={modalWrap()}>
      <div style={modalOverlay()} onClick={props.onClose} />
      <div style={modalCenter()}>
        <div style={modalCard()}>
          <div style={modalHeader()}>
            <div style={{ fontWeight: 900, fontSize: 18 }}>{props.title}</div>
            <button onClick={props.onClose} style={btnStyle()}>
              ปิด
            </button>
          </div>
          <div style={{ padding: 14 }}>{props.children}</div>
        </div>
      </div>
    </div>
  );
}

export default function AdminTenantsPage() {
  const [rows, setRows] = useState<TenantRow[]>([]);
  const [loading, setLoading] = useState(false);

  const [q, setQ] = useState("");
  const [page, setPage] = useState(1);

  // ✅ pagination จาก backend จริง
  const [pg, setPg] = useState<Pagination>({
    current_page: 1,
    per_page: 10,
    total: 0,
    last_page: 1,
  });

  const [meta, setMeta] = useState<{
    vacant_rooms?: { id: number; code: string }[];
  } | null>(null);

  const totalPages = useMemo(() => Math.max(1, pg.last_page || 1), [pg.last_page]);

  // ===== Modal: Add/Edit =====
  const [open, setOpen] = useState(false);
  const [mode, setMode] = useState<"create" | "edit">("create");
  const [editingId, setEditingId] = useState<number | null>(null);
  const [saving, setSaving] = useState(false);
  const [formErr, setFormErr] = useState("");
  const [pwMode, setPwMode] = useState<"none" | "set">("none");

  const [form, setForm] = useState<TenantPayload>({
    name: "",
    email: "",
    phone: "",
    citizen_id: "",
    address: "",
    room_id: null,
    start_date: "",
    end_date: "",
    password: "",
    password_confirmation: "",
  });

  const loadMeta = async () => {
    try {
      const res = await adminTenantsApi.meta();
      setMeta((res as any)?.data ?? res);
    } catch (e) {
      console.error("LOAD TENANTS META ERROR", e);
      setMeta(null);
    }
  };

  const loadList = async (opts?: { resetPage?: boolean }) => {
    setLoading(true);
    try {
      const targetPage = opts?.resetPage ? 1 : page;
      if (opts?.resetPage) setPage(1);

      const raw = await adminTenantsApi.list(q, targetPage, pg.per_page);
      const norm = normalizeTenants(raw);

      setRows(norm.rows);
      setPg(norm.pg);
    } catch (e) {
      console.error("LOAD TENANTS ERROR", e);
      setRows([]);
      setPg((p) => ({ ...p, total: 0, last_page: 1, current_page: 1 }));
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadMeta();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  useEffect(() => {
    loadList();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [page]);

  const onSearch = () => loadList({ resetPage: true });

  const openCreate = () => {
    setMode("create");
    setEditingId(null);
    setFormErr("");
    setPwMode("set");

    const today = new Date().toISOString().slice(0, 10);

    setForm({
      name: "",
      email: "",
      phone: "",
      citizen_id: "",
      address: "",
      room_id: meta?.vacant_rooms?.[0]?.id ?? null,
      start_date: today,
      end_date: "",
      password: "",
      password_confirmation: "",
    });

    setOpen(true);
  };

  const openEdit = (t: TenantRow) => {
    setMode("edit");
    setEditingId(t.id);
    setFormErr("");
    setPwMode("none");

    setForm({
      name: t.user?.name ?? "",
      email: t.user?.email ?? "",
      phone: t.user?.phone ?? "",
      citizen_id: t.citizen_id ?? "",
      address: t.address ?? "",
      room_id: t.current_room?.id ?? t.currentRoom?.id ?? null,
      start_date: (t.start_date ?? "").slice(0, 10),
      end_date: (t.end_date ?? "").slice(0, 10),
      password: "",
      password_confirmation: "",
    });

    setOpen(true);
  };

  const validate = () => {
    if (!String(form.name || "").trim()) return "กรุณากรอกชื่อ";
    if (!String(form.email || "").trim()) return "กรุณากรอกอีเมล";

    // start/end date rule (optional but useful)
    if (form.start_date && form.end_date) {
      const s = String(form.start_date).slice(0, 10);
      const e = String(form.end_date).slice(0, 10);
      if (e < s) return "พักถึงต้องไม่ก่อนวันเริ่มพัก";
    }

    if (pwMode === "set") {
      const p1 = String(form.password || "");
      const p2 = String(form.password_confirmation || "");
      if (p1.length < 8) return "รหัสผ่านต้องยาวอย่างน้อย 8 ตัวอักษร";
      if (p1 !== p2) return "รหัสผ่านไม่ตรงกัน";
    }
    return "";
  };

  const saveTenant = async () => {
    const msg = validate();
    if (msg) return setFormErr(msg);

    setSaving(true);
    setFormErr("");

    try {
      const payload: TenantPayload = {
        name: String(form.name || "").trim(),
        email: String(form.email || "").trim(),
        phone: form.phone ? String(form.phone) : null,
        citizen_id: form.citizen_id ? String(form.citizen_id) : null,
        address: form.address ? String(form.address) : null,
        room_id: form.room_id ? Number(form.room_id) : null,
        start_date: form.start_date ? String(form.start_date).slice(0, 10) : null,
        end_date: form.end_date ? String(form.end_date).slice(0, 10) : null,
      };

      if (pwMode === "set") {
        payload.password = String(form.password || "");
        payload.password_confirmation = String(form.password_confirmation || "");
      }

      if (mode === "create") {
        await adminTenantsApi.create(payload);
      } else if (editingId != null) {
        await adminTenantsApi.update(editingId, payload);
      }

      setOpen(false);
      await loadMeta();
      await loadList();
    } catch (e: any) {
      console.error(e);
      setFormErr(e?.response?.data?.message ?? "บันทึกไม่สำเร็จ");
    } finally {
      setSaving(false);
    }
  };

  const deleteTenant = async (id: number) => {
    if (!confirm("ลบผู้เช่ารายนี้?")) return;
    try {
      await adminTenantsApi.remove(id);
      await loadMeta();
      await loadList();
    } catch (e: any) {
      console.error(e);
      alert(e?.response?.data?.message ?? "ลบไม่สำเร็จ");
    }
  };

  return (
    <div style={{ display: "grid", gap: 14 }}>
      {/* Header */}
      <div style={{ display: "flex", justifyContent: "space-between", alignItems: "end" }}>
        <div>
          <div style={{ fontSize: 13, color: "#6b7280", fontWeight: 800 }}>Admin</div>
          <div style={{ fontSize: 28, fontWeight: 900 }}>ผู้เช่า</div>
          <div style={{ fontSize: 13, color: "#6b7280", marginTop: 4 }}>
            แสดงข้อมูลผู้เช่า + ห้องพักปัจจุบัน + วันเริ่มพัก/พักถึง (pagination จาก backend จริง)
          </div>
        </div>

        <div style={{ display: "flex", gap: 10 }}>
          <button onClick={() => loadList()} style={topBtnStyle()}>
            
            Refresh
          </button>
          <button onClick={openCreate} 
          className="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-blue-700">
            + เพิ่มผู้เช่า
          </button>
        </div>
      </div>

      {/* Search */}
      <div style={searchBoxStyle()}>
        <input
          value={q}
          onChange={(e) => setQ(e.target.value)}
          placeholder="ค้นหา: ชื่อ / อีเมล / เบอร์ / เลขบัตร"
          style={inputStyle()}
          onKeyDown={(e) => e.key === "Enter" && onSearch()}
        />
        <button onClick={onSearch} 
        className="h-11 rounded-xl bg-blue-600 px-4 text-sm font-extrabold text-white shadow-sm hover:bg-blue-700">

          Search
        </button>

        <div style={{ marginLeft: "auto", fontSize: 13, color: "#6b7280", fontWeight: 800 }}>
          รวมทั้งหมด <span style={{ color: "#111827", fontWeight: 900 }}>{pg.total}</span> รายการ
        </div>
      </div>

      {/* Table */}
      <div style={{ background: "#fff", border: "1px solid #e5e7eb", borderRadius: 16, padding: 14 }}>
        <div style={{ overflowX: "auto" }}>
          <table width="100%" cellPadding={10} style={{ borderCollapse: "collapse" }}>
            <thead>
              <tr style={{ textAlign: "left", borderBottom: "1px solid #e5e7eb" }}>
                <th>ชื่อ</th>
                <th>อีเมล</th>
                <th>เบอร์</th>
                <th>ห้องพัก</th>
                <th>เริ่มพัก</th>
                <th>พักถึง</th>
                <th style={{ width: 220 }}>จัดการ</th>
              </tr>
            </thead>

            <tbody>
              {loading && (
                <tr>
                  <td colSpan={7} style={{ padding: 14, color: "#6b7280" }}>
                    Loading...
                  </td>
                </tr>
              )}

              {!loading && rows.length === 0 && (
                <tr>
                  <td colSpan={7} style={{ padding: 14, color: "#6b7280" }}>
                    ไม่พบข้อมูล
                  </td>
                </tr>
              )}

              {!loading &&
                rows.map((t) => {
                  const roomCode = t.current_room?.code ?? t.currentRoom?.code ?? "-";
                  return (
                    <tr key={t.id} style={{ borderBottom: "1px solid #f3f4f6" }}>
                      <td style={{ fontWeight: 900 }}>{t.user?.name ?? "-"}</td>
                      <td>{t.user?.email ?? "-"}</td>
                      <td>{t.user?.phone ?? "-"}</td>
                      <td style={{ fontWeight: 800 }}>{roomCode}</td>
                      <td>{formatDateTH(t.start_date)}</td>
                      <td>{formatDateTH(t.end_date)}</td>
                      <td>
                        <div style={{ display: "flex", gap: 8, flexWrap: "wrap" }}>
                          <button
                            onClick={() => openEdit(t)}
                            style={btnStyle({ edit: true })}
                          >
                            แก้ไข
                          </button>

                          <button
                            onClick={() => deleteTenant(t.id)}
                            style={btnStyle({ danger: true })}
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

        {/* Pagination */}
        <div style={{ display: "flex", justifyContent: "space-between", marginTop: 12, alignItems: "center" }}>
          <div style={{ fontSize: 13, color: "#6b7280", fontWeight: 800 }}>
            หน้า {pg.current_page} / {totalPages} • {pg.per_page} รายการ/หน้า
          </div>

          <div style={{ display: "flex", gap: 10 }}>
            <button
              disabled={pg.current_page <= 1}
              onClick={() => setPage((p) => Math.max(1, p - 1))}
              style={pageBtnStyle(pg.current_page <= 1)}
            >
              ก่อนหน้า
            </button>
            <button
              disabled={pg.current_page >= totalPages}
              onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
              style={pageBtnStyle(pg.current_page >= totalPages)}
            >
              ถัดไป
            </button>
          </div>

          <div style={{ fontSize: 12, color: "#6b7280" }}>
            ห้องว่าง (meta): {meta?.vacant_rooms?.slice(0, 6).map((r) => r.code).join(", ") ?? "-"}
          </div>
        </div>
      </div>

      {/* ===== Modal Add/Edit ===== */}
      <Modal
        open={open}
        title={mode === "create" ? "เพิ่มผู้เช่า" : `แก้ไขผู้เช่า #${editingId ?? ""}`}
        onClose={() => setOpen(false)}
      >
        {formErr && (
          <div
            style={{
              background: "#fef2f2",
              border: "1px solid #fecaca",
              padding: 10,
              borderRadius: 12,
              color: "#b91c1c",
              fontWeight: 900,
              marginBottom: 10,
            }}
          >
            {formErr}
          </div>
        )}

        <div style={{ display: "grid", gap: 10, gridTemplateColumns: "1fr 1fr" }}>
          <div style={{ gridColumn: "span 1" }}>
            <div style={lbl()}>ชื่อ</div>
            <input
              value={form.name}
              onChange={(e) => setForm((x) => ({ ...x, name: e.target.value }))}
              style={inputStyle()}
            />
          </div>

          <div style={{ gridColumn: "span 1" }}>
            <div style={lbl()}>อีเมล</div>
            <input
              value={form.email}
              onChange={(e) => setForm((x) => ({ ...x, email: e.target.value }))}
              style={inputStyle()}
            />
          </div>

          <div style={{ gridColumn: "span 1" }}>
            <div style={lbl()}>เบอร์</div>
            <input
              value={(form.phone as any) ?? ""}
              onChange={(e) => setForm((x) => ({ ...x, phone: e.target.value }))}
              style={inputStyle()}
            />
          </div>

          <div style={{ gridColumn: "span 1" }}>
            <div style={lbl()}>เลขบัตรประชาชน</div>
            <input
              value={(form.citizen_id as any) ?? ""}
              onChange={(e) => setForm((x) => ({ ...x, citizen_id: e.target.value }))}
              style={inputStyle()}
            />
          </div>

          <div style={{ gridColumn: "span 2" }}>
            <div style={lbl()}>ที่อยู่</div>
            <input
              value={(form.address as any) ?? ""}
              onChange={(e) => setForm((x) => ({ ...x, address: e.target.value }))}
              style={inputStyle()}
            />
          </div>

          <div style={{ gridColumn: "span 1" }}>
            <div style={lbl()}>ห้อง (เลือกห้องว่าง)</div>
            <select
              value={(form.room_id as any) ?? ""}
              onChange={(e) =>
                setForm((x) => ({ ...x, room_id: e.target.value ? Number(e.target.value) : null }))
              }
              style={inputStyle()}
            >
              <option value="">ไม่ระบุ</option>
              {(meta?.vacant_rooms ?? []).map((r) => (
                <option key={r.id} value={r.id}>
                  {r.code}
                </option>
              ))}
            </select>
          </div>

          <div style={{ gridColumn: "span 1" }}>
            <div style={lbl()}>เริ่มพัก</div>
            <input
              type="date"
              value={(form.start_date ?? "").slice(0, 10)}
              onChange={(e) => setForm((x) => ({ ...x, start_date: e.target.value }))}
              style={inputStyle()}
            />
          </div>

          <div style={{ gridColumn: "span 1" }}>
            <div style={lbl()}>พักถึง</div>
            <input
              type="date"
              value={(form.end_date ?? "").slice(0, 10)}
              onChange={(e) => setForm((x) => ({ ...x, end_date: e.target.value }))}
              style={inputStyle()}
            />
          </div>
        </div>

        {/* Password section */}
        <div style={{ marginTop: 12, borderTop: "1px solid #e5e7eb", paddingTop: 12 }}>
          <div style={{ fontWeight: 900, marginBottom: 8 }}>Password</div>

          <div style={{ display: "flex", gap: 8, flexWrap: "wrap" }}>
            <button
              onClick={() => setPwMode("none")}
              style={btnStyle({ active: pwMode === "none" })}
              type="button"
            >
              ไม่เปลี่ยน
            </button>
            <button
              onClick={() => setPwMode("set")}
              style={btnStyle({ active: pwMode === "set" })}
              type="button"
            >
              ตั้งรหัสผ่านใหม่
            </button>
          </div>

          {pwMode === "set" && (
            <div style={{ display: "grid", gap: 10, gridTemplateColumns: "1fr 1fr", marginTop: 10 }}>
              <div>
                <div style={lbl()}>รหัสผ่านใหม่</div>
                <input
                  type="password"
                  value={form.password ?? ""}
                  onChange={(e) => setForm((x) => ({ ...x, password: e.target.value }))}
                  style={inputStyle()}
                />
              </div>
              <div>
                <div style={lbl()}>ยืนยันรหัสผ่าน</div>
                <input
                  type="password"
                  value={form.password_confirmation ?? ""}
                  onChange={(e) => setForm((x) => ({ ...x, password_confirmation: e.target.value }))}
                  style={inputStyle()}
                />
              </div>
            </div>
          )}
        </div>

        <div style={{ display: "flex", justifyContent: "end", gap: 10, marginTop: 14 }}>
          <button onClick={() => setOpen(false)} style={topBtnStyle()} disabled={saving}>
            ยกเลิก
          </button>
          <button onClick={saveTenant} style={topBtnStyle(true)} disabled={saving}>
            {saving ? "กำลังบันทึก..." : "บันทึก"}
          </button>
        </div>
      </Modal>
    </div>
  );
}

/* styles */
function topBtnStyle(primary?: boolean): React.CSSProperties {
  return {
    padding: "10px 14px",
    borderRadius: 12,
    border: primary ? "1px solid #c7d2fe" : "1px solid #e5e7eb",
    background: primary ? "#eef2ff" : "#fff",
    cursor: "pointer",
    fontWeight: 900,
  };
}

function searchBoxStyle(): React.CSSProperties {
  return {
    background: "#fff",
    border: "1px solid #e5e7eb",
    borderRadius: 16,
    padding: 14,
    display: "flex",
    gap: 10,
    alignItems: "center",
  };
}

function inputStyle(): React.CSSProperties {
  return {
    width: "100%",
    padding: "11px 12px",
    borderRadius: 12,
    border: "1.5px solid #cbd5e1",
    outline: "none",
    fontWeight: 800,
    background: "#fff",
    boxShadow: "0 1px 0 rgba(15,23,42,.04)",
  };
}

function btnStyle(opt?: { danger?: boolean; active?: boolean; edit?: boolean }) {
  const danger = opt?.danger;
  const active = opt?.active;
  const edit = opt?.edit;

  return {
    padding: "8px 14px",
    borderRadius: 999, // pill
    border: danger
      ? "1px solid #fecaca"
      : edit
      ? "1px solid #bfdbfe"
      : active
      ? "1px solid #93c5fd"
      : "1px solid #e5e7eb",

    background: danger
      ? "#fef2f2"   // 🔴 ลบ (แดงอ่อน)
      : edit
      ? "#eff6ff"   // 🔵 แก้ไข (ฟ้าอ่อน)
      : active
      ? "#eef2ff"
      : "#f9fafb",

    color: danger ? "#b91c1c" : edit ? "#1d4ed8" : "#111827",

    cursor: "pointer",
    fontWeight: 900,
    fontSize: 13,
    lineHeight: 1.2,
  } as React.CSSProperties;
}


function pageBtnStyle(disabled: boolean) {
  return {
    padding: "10px 14px",
    borderRadius: 12,
    border: "1px solid #e5e7eb",
    background: disabled ? "#f9fafb" : "#fff",
    cursor: disabled ? "not-allowed" : "pointer",
    fontWeight: 900,
    color: disabled ? "#9ca3af" : "#111827",
  } as React.CSSProperties;
}

function lbl(): React.CSSProperties {
  return { fontSize: 12, fontWeight: 900, color: "#374151", marginBottom: 6 };
}

/* modal styles */
function modalWrap(): React.CSSProperties {
  return { position: "fixed", inset: 0, zIndex: 9999 };
}
function modalOverlay(): React.CSSProperties {
  return { position: "absolute", inset: 0, background: "rgba(15,23,42,.45)" };
}
function modalCenter(): React.CSSProperties {
  return {
    position: "absolute",
    inset: 0,
    display: "flex",
    alignItems: "center",
    justifyContent: "center",
    padding: 14,
  };
}
function modalCard(): React.CSSProperties {
  return {
    width: "100%",
    maxWidth: 720,
    background: "#fff",
    borderRadius: 16,
    border: "1px solid #e5e7eb",
    boxShadow: "0 20px 40px rgba(0,0,0,.12)",
  };
}
function modalHeader(): React.CSSProperties {
  return {
    display: "flex",
    alignItems: "center",
    justifyContent: "space-between",
    padding: 14,
    borderBottom: "1px solid #e5e7eb",
  };
}
