import { useEffect, useMemo, useState } from "react";
import {
  tenantProfileApi,
  type TenantProfilePayload,
  type TenantProfileResponse,
} from "./services/tenantProfileApi";

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

export default function TenantProfilePage() {
  const [data, setData] = useState<TenantProfileResponse | null>(null);
  const [loading, setLoading] = useState(true);

  const [saving, setSaving] = useState(false);
  const [err, setErr] = useState("");
  const [okMsg, setOkMsg] = useState("");

  const [form, setForm] = useState<TenantProfilePayload>({
    citizen_id: "",
    address: "",
    emergency_contact: "",
  });

  const startDate = data?.tenant?.start_date ?? null;
  const endDate = data?.tenant?.end_date ?? null;

  const canSave = useMemo(() => true, []);

  const load = async () => {
    setLoading(true);
    setErr("");
    setOkMsg("");

    try {
      const res = await tenantProfileApi.show();
      setData(res);

      setForm({
        citizen_id: res?.tenant?.citizen_id ?? "",
        address: res?.tenant?.address ?? "",
        emergency_contact: res?.tenant?.emergency_contact ?? "",
      });
    } catch (e: any) {
      console.error("TENANT PROFILE LOAD ERROR", e);
      setData(null);
      setErr(e?.response?.data?.message ?? "โหลดข้อมูลไม่สำเร็จ");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    load();
  }, []);

  const save = async () => {
    setSaving(true);
    setErr("");
    setOkMsg("");

    try {
      const payload: TenantProfilePayload = {
        citizen_id: form.citizen_id ? String(form.citizen_id) : null,
        address: form.address ? String(form.address) : null,
        emergency_contact: form.emergency_contact
          ? String(form.emergency_contact)
          : null,
      };

      const res = await tenantProfileApi.update(payload);

      setData((prev) =>
        prev
          ? {
              ...prev,
              tenant: { ...(prev.tenant as any), ...(res?.tenant ?? {}) },
              current_room: res?.current_room ?? prev.current_room,
            }
          : prev
      );

      setOkMsg("บันทึกข้อมูลเรียบร้อยแล้ว");
    } catch (e: any) {
      console.error("TENANT PROFILE SAVE ERROR", e);
      setErr(e?.response?.data?.message ?? "บันทึกไม่สำเร็จ");
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <div className="rounded-[28px] border border-white/70 bg-white/90 p-6 shadow-[0_14px_36px_rgba(15,23,42,0.06)]">
        <div className="rounded-2xl bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
          กำลังโหลด...
        </div>
      </div>
    );
  }

  if (!data) {
    return (
      <div className="rounded-[28px] border border-white/70 bg-white/90 p-6 shadow-[0_14px_36px_rgba(15,23,42,0.06)]">
        <div className="font-semibold text-slate-800">ไม่พบข้อมูล</div>
        <div className="mt-1 text-sm text-slate-500">
          {err || "กรุณาลองออกจากระบบแล้วเข้าสู่ระบบใหม่"}
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-5">
      {/* Header */}
      <section className="rounded-[28px] border border-white/70 bg-white/85 p-5 shadow-[0_14px_36px_rgba(15,23,42,0.06)] backdrop-blur-sm">
        <div>
          <div className="text-2xl font-bold tracking-tight text-slate-800">
            โปรไฟล์
          </div>
          <div className="mt-1 text-sm text-slate-500">
            จัดการข้อมูลติดต่อ และดูรายละเอียดสัญญาเช่า
          </div>
        </div>
      </section>

      {err && (
        <div className="rounded-2xl border border-rose-100 bg-rose-50 px-4 py-3 text-sm text-rose-700 shadow-[0_8px_18px_rgba(244,63,94,0.06)]">
          {err}
        </div>
      )}

      {okMsg && (
        <div className="rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-[0_8px_18px_rgba(16,185,129,0.06)]">
          {okMsg}
        </div>
      )}

      {/* Contract / Room card */}
      <section className="relative overflow-hidden rounded-[28px] border border-white/40 bg-linear-to-r from-indigo-500 via-blue-500 to-cyan-500 p-6 text-white shadow-[0_18px_50px_rgba(59,130,246,0.22)]">
        <div className="relative z-10 flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
          <div>
            <div className="text-sm font-medium text-white/85">ห้องพักปัจจุบัน</div>
            <div className="mt-2 text-3xl font-bold tracking-tight">
              {getRoomLabel(data.current_room)}
            </div>
            <div className="mt-2 text-sm text-white/80">
              ข้อมูลห้องที่เช่าอยู่ในปัจจุบัน
            </div>
          </div>

          <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:min-w-105">
            <InfoGlassCard
              label="วันเริ่มเช่า"
              value={formatDateTH(startDate)}
              hint="กำหนดจากแอดมิน"
            />
            <InfoGlassCard
              label="วันสิ้นสุดสัญญา"
              value={formatDateTH(endDate)}
              hint="กำหนดจากแอดมิน"
            />
          </div>
        </div>

        <div className="absolute -right-16 -top-14 h-52 w-52 rounded-full bg-white/10 blur-sm" />
        <div className="absolute -bottom-20 -right-4 h-64 w-64 rounded-full bg-cyan-300/20 blur-md" />
        <div className="absolute left-1/3 top-0 h-32 w-32 rounded-full bg-white/10 blur-2xl" />
      </section>

      {/* User card */}
      <section className="rounded-[28px] border border-white/70 bg-white/85 p-5 shadow-[0_14px_36px_rgba(15,23,42,0.06)] backdrop-blur-sm">
        <div className="mb-4">
          <div className="text-lg font-semibold text-slate-800">ข้อมูลผู้ใช้</div>
          <div className="mt-1 text-sm text-slate-500">
            ข้อมูลพื้นฐานของบัญชีผู้ใช้งาน
          </div>
        </div>

        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <ReadOnlyCard label="ชื่อ" value={data.user?.name ?? "-"} />
          <ReadOnlyCard label="อีเมล" value={data.user?.email ?? "-"} />
          <ReadOnlyCard label="เบอร์โทร" value={data.user?.phone ?? "-"} />
        </div>
      </section>

      {/* Editable tenant info */}
      <section className="rounded-[28px] border border-white/70 bg-white/85 p-5 shadow-[0_14px_36px_rgba(15,23,42,0.06)] backdrop-blur-sm">
        <div className="mb-5 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
          <div>
            <div className="text-lg font-semibold text-slate-800">ข้อมูลผู้เช่า</div>
            <div className="mt-1 text-sm text-slate-500">
              แก้ไขข้อมูลติดต่อได้ วันเริ่มเช่าและวันสิ้นสุดสัญญาจะแก้ไม่ได้
            </div>
          </div>

          <button
            onClick={save}
            disabled={saving || !canSave}
            className="rounded-2xl bg-linear-to-r from-indigo-600 to-blue-600 px-5 py-3 font-medium text-white shadow-[0_12px_26px_rgba(59,130,246,0.22)] transition hover:-translate-y-0.5 hover:shadow-[0_16px_30px_rgba(59,130,246,0.28)] disabled:cursor-not-allowed disabled:opacity-50"
          >
            {saving ? "กำลังบันทึก..." : "บันทึกข้อมูล"}
          </button>
        </div>

        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <FieldCard label="เลขบัตรประชาชน">
            <input
              value={(form.citizen_id as any) ?? ""}
              onChange={(e) =>
                setForm((x) => ({ ...x, citizen_id: e.target.value }))
              }
              className="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-800 outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
              placeholder="เช่น 1234567890123"
            />
          </FieldCard>

          <FieldCard label="ติดต่อฉุกเฉิน">
            <input
              value={(form.emergency_contact as any) ?? ""}
              onChange={(e) =>
                setForm((x) => ({ ...x, emergency_contact: e.target.value }))
              }
              className="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-800 outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
              placeholder="ชื่อ / เบอร์ / ความสัมพันธ์"
            />
          </FieldCard>

          <div className="sm:col-span-2">
            <FieldCard label="ที่อยู่">
              <textarea
                value={(form.address as any) ?? ""}
                onChange={(e) =>
                  setForm((x) => ({ ...x, address: e.target.value }))
                }
                className="min-h-30 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-800 outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
                placeholder="ที่อยู่สำหรับติดต่อ"
              />
            </FieldCard>
          </div>
        </div>
      </section>
    </div>
  );
}

function InfoGlassCard({
  label,
  value,
  hint,
}: {
  label: string;
  value: string;
  hint?: string;
}) {
  return (
    <div className="rounded-3xl border border-white/30 bg-white/90 p-4 text-slate-800 shadow-[0_10px_24px_rgba(15,23,42,0.08)] backdrop-blur-sm">
      <div className="text-xs font-medium text-slate-500">{label}</div>
      <div className="mt-2 text-2xl font-bold tracking-tight text-slate-900">
        {value}
      </div>
      {hint && <div className="mt-1 text-[11px] text-slate-500">{hint}</div>}
    </div>
  );
}

function ReadOnlyCard({
  label,
  value,
}: {
  label: string;
  value: string;
}) {
  return (
    <div className="rounded-2xl border border-slate-200/80 bg-linear-to-r from-white to-slate-50/70 p-4 shadow-[0_8px_20px_rgba(15,23,42,0.04)]">
      <div className="text-xs font-medium text-slate-500">{label}</div>
      <div className="mt-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 font-medium text-slate-800">
        {value}
      </div>
    </div>
  );
}

function FieldCard({
  label,
  children,
}: {
  label: string;
  children: React.ReactNode;
}) {
  return (
    <div className="rounded-2xl border border-slate-200/80 bg-linear-to-r from-white to-slate-50/70 p-4 shadow-[0_8px_20px_rgba(15,23,42,0.04)]">
      <div className="mb-2 text-sm font-medium text-slate-700">{label}</div>
      {children}
    </div>
  );
}