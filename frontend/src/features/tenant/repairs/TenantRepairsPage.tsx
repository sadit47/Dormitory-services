import { useEffect, useMemo, useState } from "react";
import { tenantRepairsApi, type Paginated, type RepairListItem, type RepairPriority } from "./services/tenantRepairsApi";

function pillPriority(p: RepairPriority) {
  if (p === "high") return "bg-rose-100 text-rose-700";
  if (p === "medium") return "bg-amber-100 text-amber-800";
  return "bg-emerald-100 text-emerald-700";
}
function thPriority(p: RepairPriority) {
  if (p === "high") return "เร่งด่วน";
  if (p === "medium") return "ปานกลาง";
  return "ไม่เร่งด่วน";
}
function pillStatus(status: string) {
  if (status === "submitted") return "bg-slate-100 text-slate-700";
  if (status === "pending") return "bg-amber-100 text-amber-800";
  if (status === "in_progress") return "bg-indigo-100 text-indigo-700";
  if (status === "done") return "bg-emerald-100 text-emerald-700";
  return "bg-gray-100 text-gray-700";
}
function thStatus(status: string) {
  if (status === "submitted") return "ส่งคำขอแล้ว";
  if (status === "pending") return "รอดำเนินการ";
  if (status === "in_progress") return "กำลังซ่อม";
  if (status === "done") return "เสร็จสิ้น";
  return status;
}

export default function TenantRepairsPage() {
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(10);

  const [res, setRes] = useState<Paginated<RepairListItem> | null>(null);
  const [loading, setLoading] = useState(true);

  // form state
  const [title, setTitle] = useState("");
  const [priority, setPriority] = useState<RepairPriority>("medium");
  const [description, setDescription] = useState("");
  const [images, setImages] = useState<File[]>([]);
  const [saving, setSaving] = useState(false);
  const [err, setErr] = useState<string | null>(null);

  const load = () => {
    setLoading(true);
    tenantRepairsApi
      .list(page, perPage)
      .then(setRes)
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    load();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [page, perPage]);

  const rows = useMemo(() => res?.data ?? [], [res]);

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    setErr(null);

    if (!title.trim()) {
      setErr("กรุณากรอกหัวข้อปัญหา");
      return;
    }

    try {
      setSaving(true);
      await tenantRepairsApi.create({
        title: title.trim(),
        priority,
        description: description.trim() || undefined,
        images,
      });

      // reset
      setTitle("");
      setPriority("medium");
      setDescription("");
      setImages([]);

      // reload list
      setPage(1);
      load();
    } catch (ex: any) {
      const msg = ex?.response?.data?.message || "บันทึกไม่สำเร็จ";
      setErr(String(msg));
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="space-y-6 pb-20 sm:pb-0">
      {/* Create form */}
      <section className="bg-white border rounded-2xl shadow-sm p-5">
        <div className="flex items-center justify-between gap-3 flex-wrap">
          <div>
            <div className="text-xl font-semibold text-gray-900">แจ้งซ่อม</div>
            <div className="text-sm text-gray-500">กรอกข้อมูลปัญหา และแนบรูปประกอบได้</div>
          </div>
          <span className={`text-xs px-2 py-1 rounded-full ${pillPriority(priority)}`}>
            {thPriority(priority)}
          </span>
        </div>

        {err && (
          <div className="mt-4 text-sm bg-rose-50 text-rose-700 border border-rose-200 rounded-xl p-3">
            {err}
          </div>
        )}

        <form onSubmit={submit} className="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
          <div className="space-y-2">
            <label className="text-sm text-gray-700">หัวข้อปัญหา *</label>
            <input
              value={title}
              onChange={(e) => setTitle(e.target.value)}
              className="w-full border rounded-xl px-3 py-2 bg-white"
              placeholder="เช่น ก๊อกน้ำรั่ว / แอร์ไม่เย็น"
            />
          </div>

          <div className="space-y-2">
            <label className="text-sm text-gray-700">ระดับความเร่งด่วน *</label>
            <select
              value={priority}
              onChange={(e) => setPriority(e.target.value as RepairPriority)}
              className="w-full border rounded-xl px-3 py-2 bg-white"
            >
              <option value="low">ไม่เร่งด่วน</option>
              <option value="medium">ปานกลาง</option>
              <option value="high">เร่งด่วน</option>
            </select>
            <div className="text-xs text-gray-500">
              {priority === "high"
                ? "แนะนำเมื่อมีความเสี่ยง/ใช้งานไม่ได้"
                : priority === "medium"
                ? "ปัญหาทั่วไป รบกวนการใช้งาน"
                : "ปัญหาเล็กน้อย นัดซ่อมภายหลังได้"}
            </div>
          </div>

          <div className="md:col-span-2 space-y-2">
            <label className="text-sm text-gray-700">รายละเอียด</label>
            <textarea
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              className="w-full border rounded-xl px-3 py-2 bg-white min-h-27.5"
              placeholder="อธิบายอาการ/ตำแหน่ง/ช่วงเวลาที่พบปัญหา"
            />
          </div>

          <div className="md:col-span-2 space-y-2">
            <label className="text-sm text-gray-700">แนบรูป (jpg/png/webp) สูงสุด 5MB/ไฟล์</label>
            <input
              type="file"
              multiple
              accept="image/png,image/jpeg,image/jpg,image/webp"
              onChange={(e) => setImages(Array.from(e.target.files ?? []))}
              className="w-full"
            />
            {!!images.length && (
              <div className="text-xs text-gray-600">
                เลือกแล้ว: {images.map((f) => f.name).join(", ")}
              </div>
            )}
          </div>

          <div className="md:col-span-2 flex gap-2">
            <button
              type="submit"
              disabled={saving}
              className="px-4 py-2 rounded-xl bg-indigo-600 text-white font-medium disabled:opacity-50"
            >
              {saving ? "กำลังส่งคำขอ..." : "ส่งคำขอแจ้งซ่อม"}
            </button>
            <button
              type="button"
              onClick={() => {
                setTitle("");
                setPriority("medium");
                setDescription("");
                setImages([]);
                setErr(null);
              }}
              className="px-4 py-2 rounded-xl border bg-white"
            >
              ล้างฟอร์ม
            </button>
          </div>
        </form>
      </section>

      {/* List */}
      <section className="bg-white border rounded-2xl shadow-sm overflow-hidden">
        <div className="p-5 flex items-end justify-between gap-3 flex-wrap">
          <div>
            <div className="font-semibold text-gray-900">รายการแจ้งซ่อมของฉัน</div>
            <div className="text-sm text-gray-500">ติดตามสถานะคำขอแจ้งซ่อม</div>
          </div>

          <select
            value={perPage}
            onChange={(e) => {
              setPage(1);
              setPerPage(Number(e.target.value));
            }}
            className="border rounded-xl px-3 py-2 text-sm bg-white"
          >
            <option value={10}>10/หน้า</option>
            <option value={20}>20/หน้า</option>
            <option value={50}>50/หน้า</option>
          </select>
        </div>

        {loading ? (
          <div className="p-6 text-gray-500">กำลังโหลด...</div>
        ) : rows.length ? (
          <div className="divide-y">
            {rows.map((r) => (
              <div key={r.id} className="p-4 flex items-start justify-between gap-4">
                <div className="min-w-0">
                  <div className="font-medium text-gray-900 truncate">{r.title}</div>
                  {r.description ? (
                    <div className="text-sm text-gray-600 mt-1 line-clamp-2">{r.description}</div>
                  ) : (
                    <div className="text-sm text-gray-400 mt-1">ไม่มีรายละเอียด</div>
                  )}
                  <div className="text-xs text-gray-500 mt-2">
                    {r.requested_at ? `แจ้งเมื่อ ${String(r.requested_at).slice(0, 10)}` : ""}
                  </div>
                </div>

                <div className="shrink-0 text-right space-y-2">
                  <div className={`text-xs px-2 py-1 rounded-full inline-block ${pillPriority(r.priority)}`}>
                    {thPriority(r.priority)}
                  </div>
                  <div className={`text-xs px-2 py-1 rounded-full inline-block ${pillStatus(r.status)}`}>
                    {thStatus(r.status)}
                  </div>
                </div>
              </div>
            ))}
          </div>
        ) : (
          <div className="p-6 text-gray-500">ยังไม่มีรายการแจ้งซ่อม</div>
        )}
      </section>

      {/* Pagination */}
      {res && res.last_page > 1 && (
        <div className="flex items-center justify-between">
          <div className="text-sm text-gray-500">
            หน้า {res.current_page} / {res.last_page} • ทั้งหมด {res.total} รายการ
          </div>

          <div className="flex gap-2">
            <button
              disabled={page <= 1}
              onClick={() => setPage((p) => Math.max(1, p - 1))}
              className="px-3 py-2 rounded-xl border bg-white disabled:opacity-50"
            >
              ก่อนหน้า
            </button>
            <button
              disabled={page >= res.last_page}
              onClick={() => setPage((p) => Math.min(res.last_page, p + 1))}
              className="px-3 py-2 rounded-xl border bg-white disabled:opacity-50"
            >
              ถัดไป
            </button>
          </div>
        </div>
      )}
    </div>
  );
}