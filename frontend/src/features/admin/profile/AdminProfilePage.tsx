import { useEffect, useMemo, useState } from "react";
import { adminProfileApi } from "./services/adminProfileApi";
import type { AdminProfile } from "./services/adminProfileApi";

function isEmail(v: string) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
}

export default function AdminProfilePage() {
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [err, setErr] = useState("");
  const [okMsg, setOkMsg] = useState("");

  const [profile, setProfile] = useState<AdminProfile | null>(null);

  const [name, setName] = useState("");
  const [phone, setPhone] = useState<string>("");

  const email = useMemo(() => profile?.email ?? "", [profile]);

  const load = async () => {
    setLoading(true);
    setErr("");
    setOkMsg("");

    try {
      const p = await adminProfileApi.show();
      setProfile(p);
      setName(p?.name ?? "");
      setPhone(p?.phone ?? "");
    } catch (e: any) {
      console.error(e);
      setErr(e?.response?.data?.message ?? "โหลดโปรไฟล์ไม่สำเร็จ");
      setProfile(null);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    load();
  }, []);

  const validate = () => {
    if (!name.trim()) return "กรุณากรอกชื่อ";
    if (name.trim().length > 255) return "ชื่อต้องไม่เกิน 255 ตัวอักษร";
    if (phone && phone.length > 50) return "เบอร์โทรต้องไม่เกิน 50 ตัวอักษร";
    if (email && !isEmail(email)) return "อีเมลในระบบดูไม่ถูกต้อง (ตรวจสอบข้อมูลผู้ใช้)";
    return "";
  };

  const save = async () => {
    const msg = validate();
    if (msg) {
      setErr(msg);
      setOkMsg("");
      return;
    }

    setSaving(true);
    setErr("");
    setOkMsg("");

    try {
      await adminProfileApi.update({
        name: name.trim(),
        phone: phone.trim() ? phone.trim() : null,
      });

      setOkMsg("บันทึกโปรไฟล์สำเร็จ");
      await load();
    } catch (e: any) {
      console.error(e);
      setErr(e?.response?.data?.message ?? "บันทึกไม่สำเร็จ");
      setOkMsg("");
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="space-y-5">
      {/* Header */}
      <section className="rounded-[28px] border border-white/70 bg-white/85 p-5 shadow-[0_14px_36px_rgba(15,23,42,0.06)] backdrop-blur-sm">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <div className="text-xs font-semibold tracking-wide text-slate-500">
              Admin
            </div>
            <div className="mt-1 text-3xl font-bold tracking-tight text-slate-800">
              โปรไฟล์
            </div>
            <div className="mt-1 text-sm text-slate-500">
              แก้ไขชื่อและเบอร์โทรของผู้ดูแลระบบ
            </div>
          </div>

          <div className="flex flex-wrap gap-2">
            <button
              onClick={load}
              type="button"
              disabled={loading || saving}
              className="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-[0_8px_18px_rgba(15,23,42,0.05)] transition hover:bg-slate-50 disabled:opacity-60"
            >
              รีเฟรช
            </button>

            <button
              onClick={save}
              type="button"
              disabled={loading || saving}
              className="inline-flex items-center justify-center rounded-2xl bg-linear-to-r from-indigo-600 to-blue-600 px-5 py-2.5 text-sm font-medium text-white shadow-[0_12px_26px_rgba(59,130,246,0.22)] transition hover:-translate-y-0.5 hover:shadow-[0_16px_30px_rgba(59,130,246,0.28)] disabled:opacity-60"
            >
              {saving ? "กำลังบันทึก..." : "บันทึก"}
            </button>
          </div>
        </div>
      </section>

      {/* Alerts */}
      {err && (
        <div className="rounded-2xl border border-rose-100 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 shadow-[0_8px_18px_rgba(244,63,94,0.05)]">
          {err}
        </div>
      )}

      {okMsg && (
        <div className="rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 shadow-[0_8px_18px_rgba(16,185,129,0.05)]">
          {okMsg}
        </div>
      )}

      {/* Content */}
      <section className="rounded-[28px] border border-white/70 bg-white/85 p-5 shadow-[0_14px_36px_rgba(15,23,42,0.06)] backdrop-blur-sm">
        {loading && (
          <div className="rounded-2xl bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
            กำลังโหลด...
          </div>
        )}

        {!loading && !profile && (
          <div className="rounded-2xl bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
            ไม่พบข้อมูลโปรไฟล์
          </div>
        )}

        {!loading && profile && (
          <div className="space-y-4">
            {/* Readonly cards */}
            <div className="grid gap-4 sm:grid-cols-2">
              <InfoCard
                label="อีเมล (แก้ไขไม่ได้)"
                value={profile.email ?? "-"}
              />
              <InfoCard
                label="รหัสของผู้ใช้"
                value={`#${profile.id}`}
              />
            </div>

            {/* Editable form */}
            <div className="rounded-3xl border border-slate-200/80 bg-linear-to-r from-white to-slate-50/70 p-5 shadow-[0_10px_24px_rgba(15,23,42,0.04)]">
              <div className="mb-4">
                <div className="text-lg font-semibold text-slate-800">
                  ข้อมูลที่แก้ไขได้
                </div>
                <div className="mt-1 text-sm text-slate-500">
                  สามารถเปลี่ยนชื่อและเบอร์โทรได้ตามต้องการ
                </div>
              </div>

              <div className="grid gap-4 sm:grid-cols-2">
                <FieldCard label="ชื่อ">
                  <input
                    value={name}
                    onChange={(e) => setName(e.target.value)}
                    className="h-12 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
                    placeholder="ชื่อผู้ดูแลระบบ"
                  />
                </FieldCard>

                <FieldCard label="เบอร์โทร">
                  <input
                    value={phone}
                    onChange={(e) => setPhone(e.target.value)}
                    className="h-12 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
                    placeholder="เช่น 0812345678"
                  />
                </FieldCard>
              </div>
            </div>
          </div>
        )}
      </section>
    </div>
  );
}

function InfoCard({
  label,
  value,
}: {
  label: string;
  value: string;
}) {
  return (
    <div className="rounded-3xl border border-slate-200/80 bg-linear-to-r from-white to-slate-50/70 p-5 shadow-[0_10px_24px_rgba(15,23,42,0.04)]">
      <div className="text-sm font-medium text-slate-500">{label}</div>
      <div className="mt-3 text-3xl font-bold tracking-tight text-slate-800">
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
    <div>
      <div className="mb-2 text-sm font-medium text-slate-700">{label}</div>
      {children}
    </div>
  );
}