import { useEffect, useMemo, useState } from "react";
import {
  tenantRepairsApi,
  type Paginated,
  type RepairListItem,
  type RepairPriority,
} from "./services/tenantRepairsApi";

function pillPriority(p: RepairPriority) {
  if (p === "high") return "bg-rose-50 text-rose-700 ring-1 ring-rose-200";
  if (p === "medium") return "bg-amber-50 text-amber-800 ring-1 ring-amber-200";
  return "bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200";
}

function thPriority(p: RepairPriority) {
  if (p === "high") return "เร่งด่วน";
  if (p === "medium") return "ปานกลาง";
  return "ไม่เร่งด่วน";
}

function priorityHint(p: RepairPriority) {
  if (p === "high") return "แนะนำเมื่อมีความเสี่ยง หรืออุปกรณ์ใช้งานไม่ได้";
  if (p === "medium") return "ปัญหาทั่วไปที่รบกวนการใช้งาน";
  return "ปัญหาเล็กน้อย สามารถนัดซ่อมภายหลังได้";
}

function pillStatus(status: string) {
  if (status === "submitted") return "bg-slate-100 text-slate-700 ring-1 ring-slate-200";
  if (status === "pending") return "bg-amber-50 text-amber-800 ring-1 ring-amber-200";
  if (status === "in_progress") return "bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200";
  if (status === "done") return "bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200";
  return "bg-gray-100 text-gray-700 ring-1 ring-gray-200";
}

function thStatus(status: string) {
  if (status === "submitted") return "ส่งคำขอแล้ว";
  if (status === "pending") return "รอดำเนินการ";
  if (status === "in_progress") return "กำลังซ่อม";
  if (status === "done") return "เสร็จสิ้น";
  return status;
}

function formatDate(date: any) {
  if (!date) return "-";
  return String(date).slice(0, 10);
}

type RepairStatusFilter = "all" | "submitted" | "pending" | "in_progress" | "done";

export default function TenantRepairsPage() {
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(10);
  const [statusFilter, setStatusFilter] = useState<RepairStatusFilter>("all");

  const [res, setRes] = useState<Paginated<RepairListItem> | null>(null);
  const [loading, setLoading] = useState(true);

  const [title, setTitle] = useState("");
  const [priority, setPriority] = useState<RepairPriority>("medium");
  const [description, setDescription] = useState("");
  const [images, setImages] = useState<File[]>([]);
  const [saving, setSaving] = useState(false);
  const [err, setErr] = useState<string | null>(null);
  const [okMsg, setOkMsg] = useState<string | null>(null);

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

  const filteredRows = useMemo(() => {
    if (statusFilter === "all") return rows;
    return rows.filter((x) => x.status === statusFilter);
  }, [rows, statusFilter]);

  const counts = useMemo(() => {
    const data = res?.data ?? [];
    return {
      all: data.length,
      submitted: data.filter((x) => x.status === "submitted").length,
      pending: data.filter((x) => x.status === "pending").length,
      inProgress: data.filter((x) => x.status === "in_progress").length,
      done: data.filter((x) => x.status === "done").length,
    };
  }, [res]);

  const imagePreviews = useMemo(() => {
    return images.map((file) => ({
      file,
      url: URL.createObjectURL(file),
    }));
  }, [images]);

  useEffect(() => {
    return () => {
      imagePreviews.forEach((x) => URL.revokeObjectURL(x.url));
    };
  }, [imagePreviews]);

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    setErr(null);
    setOkMsg(null);

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

      setTitle("");
      setPriority("medium");
      setDescription("");
      setImages([]);
      setOkMsg("ส่งคำขอแจ้งซ่อมเรียบร้อยแล้ว");

      setPage(1);
      load();
    } catch (ex: any) {
      const msg = ex?.response?.data?.message || "บันทึกไม่สำเร็จ";
      setErr(String(msg));
    } finally {
      setSaving(false);
    }
  };

  const resetForm = () => {
    setTitle("");
    setPriority("medium");
    setDescription("");
    setImages([]);
    setErr(null);
    setOkMsg(null);
  };

  const removeImageAt = (idx: number) => {
    setImages((prev) => prev.filter((_, i) => i !== idx));
  };

  return (
    <div className="space-y-5 pb-20 sm:pb-0">
      {/* Header / Create form */}
      <section className="rounded-[28px] border border-white/70 bg-white/85 p-5 shadow-[0_14px_36px_rgba(15,23,42,0.06)] backdrop-blur-sm">
        <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
          <div>
            <div className="text-2xl font-bold tracking-tight text-slate-800">
              แจ้งซ่อม
            </div>
            <div className="mt-1 text-sm text-slate-500">
              กรอกข้อมูลปัญหา แนบรูปประกอบ และติดตามสถานะคำขอได้
            </div>
          </div>

          <div className={`inline-flex rounded-full px-3 py-1.5 text-xs font-medium ${pillPriority(priority)}`}>
            {thPriority(priority)}
          </div>
        </div>

        <div className="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-5">
          <MiniStat label="ทั้งหมด" value={`${counts.all}`} />
          <MiniStat label="ส่งแล้ว" value={`${counts.submitted}`} />
          <MiniStat label="รอดำเนินการ" value={`${counts.pending}`} />
          <MiniStat label="กำลังซ่อม" value={`${counts.inProgress}`} />
          <MiniStat label="เสร็จสิ้น" value={`${counts.done}`} />
        </div>

        {err && (
          <div className="mt-4 rounded-2xl border border-rose-100 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {err}
          </div>
        )}

        {okMsg && (
          <div className="mt-4 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {okMsg}
          </div>
        )}

        <form onSubmit={submit} className="mt-5 space-y-4">
          <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div className="rounded-2xl border border-slate-200/80 bg-linear-to-r from-white to-slate-50/70 p-4 shadow-[0_8px_20px_rgba(15,23,42,0.04)]">
              <label className="text-sm font-medium text-slate-700">หัวข้อปัญหา *</label>
              <input
                value={title}
                onChange={(e) => setTitle(e.target.value)}
                className="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-800 outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
                placeholder="เช่น ก๊อกน้ำรั่ว / แอร์ไม่เย็น"
              />
            </div>

            <div className="rounded-2xl border border-slate-200/80 bg-linear-to-r from-white to-slate-50/70 p-4 shadow-[0_8px_20px_rgba(15,23,42,0.04)]">
              <label className="text-sm font-medium text-slate-700">ระดับความเร่งด่วน *</label>
              <select
                value={priority}
                onChange={(e) => setPriority(e.target.value as RepairPriority)}
                className="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-800 outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
              >
                <option value="low">ไม่เร่งด่วน</option>
                <option value="medium">ปานกลาง</option>
                <option value="high">เร่งด่วน</option>
              </select>
              <div className="mt-2 text-xs text-slate-500">{priorityHint(priority)}</div>
            </div>
          </div>

          <div className="rounded-2xl border border-slate-200/80 bg-linear-to-r from-white to-slate-50/70 p-4 shadow-[0_8px_20px_rgba(15,23,42,0.04)]">
            <label className="text-sm font-medium text-slate-700">รายละเอียด</label>
            <textarea
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              className="mt-2 min-h-37.5 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-800 outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
              placeholder="อธิบายอาการ ตำแหน่ง จุดที่พบปัญหา หรือช่วงเวลาที่พบปัญหา"
            />
          </div>

          <div className="rounded-3xl border border-dashed border-slate-300 bg-slate-50/80 p-4 sm:p-5">
            <div className="text-sm font-medium text-slate-700">แนบรูปประกอบ</div>
            <div className="mt-1 text-sm text-slate-500">
              รองรับไฟล์ jpg, jpeg, png, webp ขนาดไม่เกิน 5MB ต่อไฟล์
            </div>

            <div className="mt-4">
              <label className="inline-flex cursor-pointer items-center rounded-2xl bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-[0_8px_18px_rgba(15,23,42,0.05)] ring-1 ring-slate-200 transition hover:bg-slate-50">
                🖼️ เลือกรูปภาพ
                <input
                  type="file"
                  multiple
                  accept="image/png,image/jpeg,image/jpg,image/webp"
                  onChange={(e) => setImages(Array.from(e.target.files ?? []))}
                  className="hidden"
                />
              </label>
            </div>

            {!images.length ? (
              <div className="mt-4 rounded-2xl border border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-400">
                ยังไม่ได้เลือกรูปภาพ
              </div>
            ) : (
              <div className="mt-4 rounded-3xl border border-slate-200 bg-white p-4 shadow-[0_8px_18px_rgba(15,23,42,0.04)]">
                <div className="flex items-center justify-between gap-3">
                  <div className="text-sm font-medium text-slate-700">ตัวอย่างรูปที่เลือก</div>
                  <div className="text-xs text-slate-500">{images.length} รูป</div>
                </div>

                <div className="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                  {imagePreviews.map((item, idx) => (
                    <div
                      key={`${item.file.name}-${idx}`}
                      className="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50"
                    >
                      <div className="aspect-square overflow-hidden bg-white">
                        <img
                          src={item.url}
                          alt={item.file.name}
                          className="h-full w-full object-cover"
                        />
                      </div>

                      <div className="p-3">
                        <div className="line-clamp-1 text-sm font-medium text-slate-700">
                          {item.file.name}
                        </div>
                        <div className="mt-1 text-xs text-slate-500">
                          {Math.round(item.file.size / 1024)} KB
                        </div>

                        <button
                          type="button"
                          onClick={() => removeImageAt(idx)}
                          className="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-600 transition hover:bg-slate-50"
                        >
                          ลบรูปนี้
                        </button>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>

          <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div className="text-sm text-slate-500">
              กรุณาตรวจสอบหัวข้อปัญหา ระดับความเร่งด่วน และรูปประกอบก่อนส่งคำขอ
            </div>

            <div className="flex gap-2">
              <button
                type="submit"
                disabled={saving}
                className="rounded-2xl bg-linear-to-r from-indigo-600 to-blue-600 px-5 py-3 font-medium text-white shadow-[0_12px_26px_rgba(59,130,246,0.22)] transition hover:-translate-y-0.5 hover:shadow-[0_16px_30px_rgba(59,130,246,0.28)] disabled:cursor-not-allowed disabled:opacity-50"
              >
                {saving ? "กำลังส่งคำขอ..." : "ส่งคำขอแจ้งซ่อม"}
              </button>

              <button
                type="button"
                onClick={resetForm}
                className="rounded-2xl border border-slate-200 bg-white px-5 py-3 font-medium text-slate-700 shadow-[0_8px_18px_rgba(15,23,42,0.04)] transition hover:bg-slate-50"
              >
                ล้างฟอร์ม
              </button>
            </div>
          </div>
        </form>
      </section>

      {/* List */}
      <section className="overflow-hidden rounded-[28px] border border-white/70 bg-white/85 shadow-[0_14px_36px_rgba(15,23,42,0.06)] backdrop-blur-sm">
        <div className="flex flex-wrap items-end justify-between gap-3 p-5">
          <div>
            <div className="text-lg font-semibold text-slate-800">รายการแจ้งซ่อมของฉัน</div>
            <div className="mt-1 text-sm text-slate-500">ติดตามสถานะคำขอแจ้งซ่อมล่าสุด</div>
          </div>

          <div className="flex flex-wrap gap-2">
            <select
              value={statusFilter}
              onChange={(e) => {
                setPage(1);
                setStatusFilter(e.target.value as RepairStatusFilter);
              }}
              className="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 shadow-[0_8px_20px_rgba(15,23,42,0.04)] outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
            >
              <option value="all">ทุกสถานะ</option>
              <option value="submitted">ส่งคำขอแล้ว</option>
              <option value="pending">รอดำเนินการ</option>
              <option value="in_progress">กำลังซ่อม</option>
              <option value="done">เสร็จสิ้น</option>
            </select>

            <select
              value={perPage}
              onChange={(e) => {
                setPage(1);
                setPerPage(Number(e.target.value));
              }}
              className="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 shadow-[0_8px_20px_rgba(15,23,42,0.04)] outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
            >
              <option value={10}>10/หน้า</option>
              <option value={20}>20/หน้า</option>
              <option value={50}>50/หน้า</option>
            </select>
          </div>
        </div>

        {loading ? (
          <div className="p-6">
            <div className="rounded-2xl bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
              กำลังโหลด...
            </div>
          </div>
        ) : filteredRows.length ? (
          <div className="p-3 sm:p-4">
            <div className="mb-3 flex flex-wrap gap-2">
              <FilterChip
                active={statusFilter === "all"}
                label={`ทั้งหมด ${counts.all}`}
                onClick={() => {
                  setPage(1);
                  setStatusFilter("all");
                }}
              />
              <FilterChip
                active={statusFilter === "submitted"}
                label={`ส่งแล้ว ${counts.submitted}`}
                onClick={() => {
                  setPage(1);
                  setStatusFilter("submitted");
                }}
              />
              <FilterChip
                active={statusFilter === "pending"}
                label={`รอดำเนินการ ${counts.pending}`}
                onClick={() => {
                  setPage(1);
                  setStatusFilter("pending");
                }}
              />
              <FilterChip
                active={statusFilter === "in_progress"}
                label={`กำลังซ่อม ${counts.inProgress}`}
                onClick={() => {
                  setPage(1);
                  setStatusFilter("in_progress");
                }}
              />
              <FilterChip
                active={statusFilter === "done"}
                label={`เสร็จสิ้น ${counts.done}`}
                onClick={() => {
                  setPage(1);
                  setStatusFilter("done");
                }}
              />
            </div>

            <div className="space-y-3">
              {filteredRows.map((r) => (
                <div
                  key={r.id}
                  className="rounded-3xl border border-slate-200/80 bg-linear-to-r from-white to-slate-50/70 p-4 shadow-[0_10px_24px_rgba(15,23,42,0.04)] transition hover:-translate-y-0.5 hover:shadow-[0_16px_30px_rgba(15,23,42,0.08)]"
                >
                  <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div className="min-w-0">
                      <div className="flex flex-wrap items-center gap-2">
                        <div className="text-lg font-semibold tracking-tight text-slate-800">
                          {r.title}
                        </div>
                        <span className={`rounded-full px-2.5 py-1 text-xs font-medium ${pillPriority(r.priority)}`}>
                          {thPriority(r.priority)}
                        </span>
                      </div>

                      {r.description ? (
                        <div className="mt-2 line-clamp-2 text-sm text-slate-600">
                          {r.description}
                        </div>
                      ) : (
                        <div className="mt-2 text-sm text-slate-400">ไม่มีรายละเอียด</div>
                      )}

                      <div className="mt-3 text-xs text-slate-500">
                        แจ้งเมื่อ {formatDate(r.requested_at)}
                      </div>
                    </div>

                    <div className="shrink-0 text-left sm:text-right">
                      <div className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${pillStatus(r.status)}`}>
                        {thStatus(r.status)}
                      </div>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        ) : (
          <div className="p-6">
            <div className="rounded-2xl bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
              ไม่พบรายการแจ้งซ่อมในสถานะนี้
            </div>
          </div>
        )}
      </section>

      {/* Pagination */}
      {res && res.last_page > 1 && (
        <section className="rounded-3xl border border-white/70 bg-white/85 p-4 shadow-[0_12px_30px_rgba(15,23,42,0.05)] backdrop-blur-sm">
          <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div className="text-sm text-slate-500">
              หน้า {res.current_page} / {res.last_page} • ทั้งหมด {res.total} รายการ
            </div>

            <div className="flex gap-2">
              <button
                disabled={page <= 1}
                onClick={() => setPage((p) => Math.max(1, p - 1))}
                className="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-[0_6px_16px_rgba(15,23,42,0.04)] transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
              >
                ก่อนหน้า
              </button>

              <button
                disabled={page >= res.last_page}
                onClick={() => setPage((p) => Math.min(res.last_page, p + 1))}
                className="rounded-2xl bg-slate-900 px-4 py-2 text-sm font-medium text-white shadow-[0_8px_18px_rgba(15,23,42,0.14)] transition hover:-translate-y-0.5 hover:shadow-[0_12px_24px_rgba(15,23,42,0.18)] disabled:cursor-not-allowed disabled:opacity-50"
              >
                ถัดไป
              </button>
            </div>
          </div>
        </section>
      )}
    </div>
  );
}

function MiniStat({
  label,
  value,
}: {
  label: string;
  value: string;
}) {
  return (
    <div className="rounded-2xl border border-slate-200/80 bg-linear-to-r from-white to-slate-50/70 p-4 shadow-[0_8px_20px_rgba(15,23,42,0.04)]">
      <div className="text-sm text-slate-500">{label}</div>
      <div className="mt-2 text-2xl font-bold tracking-tight text-slate-800">
        {value}
      </div>
    </div>
  );
}

function FilterChip({
  label,
  active,
  onClick,
}: {
  label: string;
  active?: boolean;
  onClick?: () => void;
}) {
  return (
    <button
      onClick={onClick}
      className={[
        "rounded-full px-3 py-1.5 text-xs font-medium transition",
        active
          ? "bg-slate-900 text-white shadow-[0_8px_18px_rgba(15,23,42,0.14)]"
          : "bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50",
      ].join(" ")}
    >
      {label}
    </button>
  );
}