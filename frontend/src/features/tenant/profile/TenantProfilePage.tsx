import { useEffect, useMemo, useState } from "react";
import { tenantProfileApi, type TenantProfilePayload, type TenantProfileResponse } from "./services/tenantProfileApi";

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

  const [form, setForm] = useState<TenantProfilePayload>({
    citizen_id: "",
    address: "",
    emergency_contact: "",
  });

  const startDate = data?.tenant?.start_date ?? null;
  const endDate = data?.tenant?.end_date ?? null;

  const canSave = useMemo(() => {
    // บันทึกเฉพาะข้อมูลโปรไฟล์ (ไม่ยุ่งวันเริ่ม/สิ้นสุด)
    return true;
  }, []);

  const load = async () => {
    setLoading(true);
    setErr("");
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
    try {
      // ✅ ไม่ส่ง start_date/end_date ไปแก้ เพื่อให้ยึดตามแอดมิน
      const payload: TenantProfilePayload = {
        citizen_id: form.citizen_id ? String(form.citizen_id) : null,
        address: form.address ? String(form.address) : null,
        emergency_contact: form.emergency_contact ? String(form.emergency_contact) : null,
      };

      const res = await tenantProfileApi.update(payload);

      // backend คืน { tenant, current_room } => รวมกลับเข้าหน้า
      setData((prev) =>
        prev
          ? {
              ...prev,
              tenant: { ...(prev.tenant as any), ...(res?.tenant ?? {}) },
              current_room: res?.current_room ?? prev.current_room,
            }
          : prev
      );
    } catch (e: any) {
      console.error("TENANT PROFILE SAVE ERROR", e);
      setErr(e?.response?.data?.message ?? "บันทึกไม่สำเร็จ");
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return <div className="p-4 text-gray-500">กำลังโหลด...</div>;
  }

  if (!data) {
    return (
      <div className="bg-white border rounded-2xl p-5">
        <div className="font-semibold text-gray-800">ไม่พบข้อมูล</div>
        <div className="text-sm text-gray-500 mt-1">{err || "กรุณาลองออกจากระบบแล้วเข้าสู่ระบบใหม่"}</div>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      {/* Header */}
      <div>
        <div className="text-xl font-semibold text-gray-900">โปรไฟล์</div>
        <div className="text-sm text-gray-500">จัดการข้อมูลติดต่อ และดูรายละเอียดสัญญาเช่า</div>
      </div>

      {err && (
        <div className="bg-rose-50 border border-rose-200 text-rose-700 rounded-2xl p-4 text-sm font-medium">
          {err}
        </div>
      )}

      {/* Contract / Room card */}
      <div className="relative overflow-hidden rounded-2xl p-6 text-white shadow-lg bg-linear-to-r from-indigo-600 via-blue-600 to-sky-600">
        <div className="flex items-start justify-between gap-4 flex-wrap">
          <div>
            <div className="text-sm opacity-90">ห้องพักปัจจุบัน</div>
            <div className="text-lg font-semibold text-white mt-1">{getRoomLabel(data.current_room)}</div>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 w-full sm:w-auto">
            <div className="bg-gray-50 border rounded-2xl p-4">
              <div className="text-xs text-gray-500 font-medium">วันเริ่มเช่า</div>
              <div className="mt-1 font-semibold text-gray-900">{formatDateTH(startDate)}</div>
              <div className="text-[11px] text-gray-500 mt-1">กำหนดจากแอดมิน</div>
            </div>

            <div className="bg-gray-50 border rounded-2xl p-4">
              <div className="text-xs text-gray-500 font-medium">วันสิ้นสุดสัญญา</div>
              <div className="mt-1 font-semibold text-gray-900">{formatDateTH(endDate)}</div>
              <div className="text-[11px] text-gray-500 mt-1">กำหนดจากแอดมิน</div>
            </div>
          </div>
        </div>
      </div>

      {/* User card */}
      <div className="bg-white border rounded-2xl shadow-sm p-5">
        <div className="font-semibold text-gray-900 mb-4">ข้อมูลผู้ใช้</div>

        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <div className="text-xs text-gray-500 font-medium mb-1">ชื่อ</div>
            <div className="px-3 py-2 rounded-xl border bg-gray-50 text-gray-900">
              {data.user?.name ?? "-"}
            </div>
          </div>

          <div>
            <div className="text-xs text-gray-500 font-medium mb-1">อีเมล</div>
            <div className="px-3 py-2 rounded-xl border bg-gray-50 text-gray-900">
              {data.user?.email ?? "-"}
            </div>
          </div>

          <div>
            <div className="text-xs text-gray-500 font-medium mb-1">เบอร์โทร</div>
            <div className="px-3 py-2 rounded-xl border bg-gray-50 text-gray-900">
              {data.user?.phone ?? "-"}
            </div>
          </div>
        </div>
      </div>

      {/* Editable tenant info */}
      <div className="bg-white border rounded-2xl shadow-sm p-5">
        <div className="flex items-center justify-between gap-3 flex-wrap mb-4">
          <div>
            <div className="font-semibold text-gray-900">ข้อมูลผู้เช่า</div>
            <div className="text-xs text-gray-500 mt-1">แก้ไขข้อมูลติดต่อได้ (วันเริ่มเช่า/สิ้นสุดสัญญาแก้ไม่ได้)</div>
          </div>

          <button
            onClick={save}
            disabled={saving || !canSave}
            className="px-4 py-2 rounded-xl bg-indigo-600 text-white font-medium disabled:opacity-50"
          >
            {saving ? "กำลังบันทึก..." : "บันทึก"}
          </button>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <div className="text-xs text-gray-500 font-medium mb-1">เลขบัตรประชาชน</div>
            <input
              value={(form.citizen_id as any) ?? ""}
              onChange={(e) => setForm((x) => ({ ...x, citizen_id: e.target.value }))}
              className="w-full px-3 py-2 rounded-xl border bg-white"
              placeholder="เช่น 1234567890123"
            />
          </div>

          <div>
            <div className="text-xs text-gray-500 font-medium mb-1">ติดต่อฉุกเฉิน</div>
            <input
              value={(form.emergency_contact as any) ?? ""}
              onChange={(e) => setForm((x) => ({ ...x, emergency_contact: e.target.value }))}
              className="w-full px-3 py-2 rounded-xl border bg-white"
              placeholder="ชื่อ/เบอร์/ความสัมพันธ์"
            />
          </div>

          <div className="sm:col-span-2">
            <div className="text-xs text-gray-500 font-medium mb-1">ที่อยู่</div>
            <input
              value={(form.address as any) ?? ""}
              onChange={(e) => setForm((x) => ({ ...x, address: e.target.value }))}
              className="w-full px-3 py-2 rounded-xl border bg-white"
              placeholder="ที่อยู่สำหรับติดต่อ"
            />
          </div>
        </div>
      </div>
    </div>
  );
}