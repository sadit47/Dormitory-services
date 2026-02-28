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
    <div className="space-y-4">
      {/* Header */}
      <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <div className="text-xs font-extrabold tracking-wide text-slate-500">Admin</div>
          <div className="text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">โปรไฟล์</div>
          <div className="mt-1 text-sm text-slate-500">แก้ไขชื่อและเบอร์โทรของผู้ดูแลระบบ</div>
        </div>

        {/* ✅ ทำให้เหมือนหน้าอื่น */}
        <div className="flex flex-wrap gap-2">
          <button
            onClick={() => load()}
            className="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-900 shadow-sm hover:bg-slate-50 disabled:opacity-60"
            type="button"
            disabled={loading || saving}
          >
            Refresh
          </button>

          <button
            onClick={save}
            className="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-indigo-700 disabled:opacity-60"
            type="button"
            disabled={loading || saving}
          >
            {saving ? "กำลังบันทึก..." : "บันทึก"}
          </button>
        </div>
      </div>

      {/* Alerts */}
      {err && (
        <div className="rounded-2xl bg-rose-50 p-4 text-sm font-semibold text-rose-700 ring-1 ring-rose-200">
          {err}
        </div>
      )}
      {okMsg && (
        <div className="rounded-2xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-700 ring-1 ring-emerald-200">
          {okMsg}
        </div>
      )}

      {/* Content */}
      <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        {loading && <div className="text-sm text-slate-500">Loading...</div>}

        {!loading && !profile && <div className="text-sm text-slate-500">ไม่พบข้อมูลโปรไฟล์</div>}

        {!loading && profile && (
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="rounded-2xl border border-slate-500 bg-white p-4">
              <div className="text-xs font-extrabold text-slate-500">อีเมล (แก้ไม่ได้)</div>
              <div className="mt-1 text-base font-black text-slate-900">{profile.email ?? "-"}</div>
              <div className="mt-2 text-xs font-semibold text-slate-500">
                * API ฝั่ง backend อนุญาตแก้เฉพาะ name/phone
              </div>
            </div>

            <div className="rounded-2xl border border-slate-500 bg-white p-4">
              <div className="text-xs font-extrabold text-slate-500">รหัสผู้ใช้</div>
              <div className="mt-1 text-base font-black text-slate-900">#{profile.id}</div>
            </div>

            <div className="sm:col-span-2 rounded-2xl border border-slate-200 bg-slate-50 p-4">
              <div className="grid gap-3 sm:grid-cols-2">
                <div>
                  <div className="mb-1 text-xs font-extrabold text-slate-600">ชื่อ</div>
                  <input
                    value={name}
                    onChange={(e) => setName(e.target.value)}
                    className="h-11 w-full rounded-xl border border-slate-500 bg-white px-3 text-sm font-semibold text-slate-900 outline-none placeholder:text-slate-400 focus:ring-4 focus:ring-indigo-100"
                    placeholder="ชื่อผู้ดูแลระบบ"
                  />
                </div>

                <div>
                  <div className="mb-1 text-xs font-extrabold text-slate-600">เบอร์โทร</div>
                  <input
                    value={phone}
                    onChange={(e) => setPhone(e.target.value)}
                    className="h-11 w-full rounded-xl border border-slate-500 bg-white px-3 text-sm font-semibold text-slate-900 outline-none placeholder:text-slate-400 focus:ring-4 focus:ring-indigo-100"
                    placeholder="เช่น 0812345678"
                  />
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}