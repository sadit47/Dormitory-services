import { useEffect, useMemo, useRef, useState } from "react";
import { adminPaymentsReviewApi } from "./services/adminPaymentsReviewApi";
import type { PendingPaymentRow, PaymentStatus } from "./services/adminPaymentsReviewApi";

/* ===================== Helpers ===================== */
function money(v: any) {
  const n = Number(v ?? 0);
  if (Number.isNaN(n)) return "-";
  return n.toLocaleString("th-TH", { minimumFractionDigits: 0, maximumFractionDigits: 2 });
}

function fmtDate(v?: string | null) {
  if (!v) return "-";
  const d = new Date(v);
  if (Number.isNaN(d.getTime())) return String(v);
  return d.toLocaleString("th-TH");
}

function statusLabel(s?: PaymentStatus) {
  if (!s) return "-";
  if (s === "waiting") return "รอตรวจสอบ";
  if (s === "approved") return "อนุมัติแล้ว";
  if (s === "rejected") return "ปฏิเสธ";
  return String(s);
}

function badgeClass(s?: PaymentStatus) {
  if (s === "approved") return "bg-emerald-50 text-emerald-700 ring-emerald-200";
  if (s === "waiting") return "bg-amber-50 text-amber-800 ring-amber-200";
  if (s === "rejected") return "bg-rose-50 text-rose-700 ring-rose-200";
  return "bg-slate-100 text-slate-700 ring-slate-200";
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
        <div className="w-full max-w-5xl rounded-2xl bg-white shadow-xl ring-1 ring-slate-200">
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
          <div className="p-4">{children}</div>
        </div>
      </div>
    </div>
  );
}

/* ===================== Page ===================== */
export default function AdminPaymentReviewPage() {
  const [rows, setRows] = useState<PendingPaymentRow[]>([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(false);

  // paging
  const [page, setPage] = useState(1);
  const perPage = 20;
  const totalPages = useMemo(() => Math.max(1, Math.ceil(total / perPage)), [total, perPage]);

  // modal
  const [open, setOpen] = useState(false);
  const [viewing, setViewing] = useState<PendingPaymentRow | null>(null);

  // slip cache (fileId -> objectURL)
  const slipUrlCacheRef = useRef<Map<number, string>>(new Map());

  const revokeAllSlipUrls = () => {
    const m = slipUrlCacheRef.current;
    for (const [, url] of m) URL.revokeObjectURL(url);
    m.clear();
  };

  useEffect(() => {
    return () => {
      revokeAllSlipUrls();
    };
  }, []);

  const loadList = async (targetPage = page) => {
    setLoading(true);
    try {
      const res = await adminPaymentsReviewApi.pending(targetPage, perPage);
      setRows(res?.data ?? []);
      setTotal(Number(res?.total ?? (res?.data?.length ?? 0)));
    } catch (e) {
      console.error(e);
      setRows([]);
      setTotal(0);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadList(page);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [page]);

  const openDetail = (r: PendingPaymentRow) => {
    setViewing(r);
    setOpen(true);
  };

  const getSlipSrc = async (r: PendingPaymentRow): Promise<string> => {
    const fileId = Number(r?.slip?.file_id ?? 0);
    if (!fileId) return "";

    // 1) cache ก่อน
    const cached = slipUrlCacheRef.current.get(fileId);
    if (cached) return cached;

    // 2) โหลด blob ด้วย bearer
    const res = await adminPaymentsReviewApi.fileBlob(fileId);
    const url = URL.createObjectURL(res.data);
    slipUrlCacheRef.current.set(fileId, url);
    return url;
  };

  function SlipPreview({ r }: { r: PendingPaymentRow }) {
    const [src, setSrc] = useState("");
    const [err, setErr] = useState("");

    useEffect(() => {
      let alive = true;
      setSrc("");
      setErr("");

      (async () => {
        try {
          const s = await getSlipSrc(r);
          if (!alive) return;
          setSrc(s);
        } catch (e: any) {
          console.error("load slip failed", e);
          if (!alive) return;
          setErr(e?.response?.data?.message ?? "โหลดสลิปไม่สำเร็จ");
        }
      })();

      return () => {
        alive = false;
      };
      // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [r?.slip?.file_id]);

    if (!r?.slip?.file_id) {
      return <div className="text-sm text-slate-500">ไม่มีสลิปแนบ</div>;
    }

    return (
      <div className="rounded-2xl border border-slate-200 bg-slate-50 p-3">
        <div className="text-xs font-extrabold text-slate-500">สลิป</div>
        <div className="mt-2 overflow-hidden rounded-2xl border border-slate-200 bg-white">
          <div className="aspect-4/5 w-full bg-white">
            {src ? (
              <a href={src} target="_blank" rel="noreferrer" className="block h-full w-full">
                <img src={src} alt={r?.slip?.original_name ?? "slip"} className="h-full w-full object-contain" />
              </a>
            ) : (
              <div className="flex h-full items-center justify-center px-4 text-center text-sm font-semibold text-slate-600">
                {err ? err : "กำลังโหลดสลิป..."}
              </div>
            )}
          </div>
        </div>

        <div className="mt-2 text-xs font-semibold text-slate-700 line-clamp-1">
          {r?.slip?.original_name ?? `file-${r?.slip?.file_id}`}
        </div>
      </div>
    );
  }

  const approve = async (r: PendingPaymentRow) => {
    const ok = window.confirm(`อนุมัติการชำระเงิน #${r.id} (${r.invoice?.invoice_no ?? "-"}) ?`);
    if (!ok) return;

    try {
      await adminPaymentsReviewApi.approve(r.id);
      // รีเฟรชรายการ pending
      await loadList(page);
      setOpen(false);
      setViewing(null);
    } catch (e: any) {
      console.error(e);
      alert(e?.response?.data?.message ?? "อนุมัติไม่สำเร็จ");
    }
  };

  const reject = async (r: PendingPaymentRow) => {
    const ok = window.confirm(`ปฏิเสธการชำระเงิน #${r.id} (${r.invoice?.invoice_no ?? "-"}) ?`);
    if (!ok) return;

    try {
      await adminPaymentsReviewApi.reject(r.id);
      await loadList(page);
      setOpen(false);
      setViewing(null);
    } catch (e: any) {
      console.error(e);
      alert(e?.response?.data?.message ?? "ปฏิเสธไม่สำเร็จ");
    }
  };

  return (
    <div className="space-y-4">
      {/* Header */}
      <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <div className="text-xs font-extrabold tracking-wide text-slate-500">Admin</div>
          <div className="text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">ตรวจสอบการชำระเงิน</div>
          <div className="mt-1 text-sm text-slate-500">รายการสถานะ “รอตรวจสอบ” • ดูสลิป • อนุมัติ/ปฏิเสธ</div>
        </div>

        <div className="flex flex-wrap gap-2">
          <button
            onClick={() => loadList(page)}
            className="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-900 shadow-sm hover:bg-slate-50"
            type="button"
          >
            Refresh
          </button>
        </div>
      </div>

      {/* Table */}
      <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        {loading && <div className="p-3 text-sm text-slate-500">Loading...</div>}
        {!loading && rows.length === 0 && <div className="p-3 text-sm text-slate-500">ไม่มีรายการรอตรวจสอบ</div>}

        {!loading && rows.length > 0 && (
          <div className="overflow-x-auto">
            <table className="min-w-full w-full border-collapse">
              <thead>
                <tr className="text-left text-xs font-extrabold text-slate-600">
                  <th className="py-3">#</th>
                  <th className="py-3">เลขใบแจ้งหนี้</th>
                  <th className="py-3">ผู้เช่า</th>
                  <th className="py-3 text-center">ยอดชำระ</th>
                  <th className="py-3 text-center">เวลาชำระ</th>
                  <th className="py-3 text-center">สถานะ</th>
                  <th className="py-3 text-center">จัดการ</th>
                </tr>
              </thead>

              <tbody>
                {rows.map((r) => {
                  const invoiceNo = r.invoice?.invoice_no ?? "-";
                  const tenantName = r.invoice?.tenant?.user?.name ?? "-";
                  const paidAt = r.paid_at ?? r.created_at ?? null;

                  return (
                    <tr key={r.id} className="border-t border-slate-100 text-sm font-semibold text-slate-900">
                      <td className="py-4 font-black">{r.id}</td>
                      <td className="py-4 font-black">{invoiceNo}</td>
                      <td className="py-4">{tenantName}</td>
                      <td className="py-4 text-right font-black">฿ {money(r.amount)}</td>
                      <td className="py-4 text-center font-black">{fmtDate(paidAt)}</td>
                      <td className="py-4 ">
                        <span className={["inline-flex rounded-full px-3 py-1 text-xs font-extrabold ring-1", badgeClass(r.status)].join(" ")}>
                          {statusLabel(r.status)}
                        </span>
                      </td>
                      <td className="py-4">
                        <div className="flex justify-end gap-2">
                          <button
                            onClick={() => openDetail(r)}
                            className="rounded-xl px-3 py-1.5 text-xs font-extrabold ring-1 border border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100"
                            type="button"
                          >
                            ตรวจสอบ
                          </button>

                          <button
                            onClick={() => approve(r)}
                            className="rounded-xl px-3 py-1.5 text-xs font-extrabold ring-1 border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100"
                            type="button"
                          >
                            อนุมัติ
                          </button>

                          <button
                            onClick={() => reject(r)}
                            className="rounded-xl px-3 py-1.5 text-xs font-extrabold ring-1 border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100"
                            type="button"
                          >
                            ปฏิเสธ
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
                page <= 1 ? "cursor-not-allowed border-slate-200 bg-slate-50 text-slate-400" : "border-slate-200 bg-white text-slate-900 hover:bg-slate-50",
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
                page >= totalPages ? "cursor-not-allowed border-slate-200 bg-slate-50 text-slate-400" : "border-slate-200 bg-white text-slate-900 hover:bg-slate-50",
              ].join(" ")}
              type="button"
            >
              ถัดไป
            </button>
          </div>
        </div>
      </div>

      {/* Modal */}
      <Modal
        open={open}
        title={`ตรวจสอบการชำระเงิน #${viewing?.id ?? ""}`}
        onClose={() => {
          setOpen(false);
          setViewing(null);
          // จะไม่ล้าง cache ก็ได้ แต่ถ้าล้างจะประหยัด memory
          // revokeAllSlipUrls();
        }}
      >
        {!viewing ? (
          <div className="text-sm text-slate-500">ไม่พบข้อมูล</div>
        ) : (
          <div className="grid gap-4 lg:grid-cols-[1fr_360px]">
            {/* Left: Info */}
            <div className="space-y-3">
              <div className="rounded-2xl border border-slate-200 bg-white p-4">
                <div className="text-xs font-extrabold text-slate-500">ข้อมูลการชำระเงิน</div>

                <div className="mt-2 grid gap-2 sm:grid-cols-2 text-sm font-semibold text-slate-700">
                  <div>
                    ใบแจ้งหนี้: <span className="font-black text-slate-900">{viewing.invoice?.invoice_no ?? "-"}</span>
                  </div>
                  <div>
                    ยอดชำระ: <span className="font-black text-slate-900">฿ {money(viewing.amount)}</span>
                  </div>
                  <div>
                    ผู้เช่า: <span className="font-black text-slate-900">{viewing.invoice?.tenant?.user?.name ?? "-"}</span>
                  </div>
                  <div>อีเมล: {viewing.invoice?.tenant?.user?.email ?? "-"}</div>
                  <div>เวลาชำระ: {fmtDate(viewing.paid_at ?? viewing.created_at ?? null)}</div>
                  <div>
                    สถานะ:{" "}
                    <span className={["inline-flex rounded-full px-3 py-1 text-xs font-extrabold ring-1", badgeClass(viewing.status)].join(" ")}>
                      {statusLabel(viewing.status)}
                    </span>
                  </div>
                </div>
              </div>

              <div className="flex flex-wrap justify-end gap-2">
                <button
                  onClick={() => approve(viewing)}
                  className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-extrabold text-emerald-700 hover:bg-emerald-100"
                  type="button"
                >
                  อนุมัติ
                </button>
                <button
                  onClick={() => reject(viewing)}
                  className="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-extrabold text-rose-700 hover:bg-rose-100"
                  type="button"
                >
                  ปฏิเสธ
                </button>
              </div>
            </div>

            {/* Right: Slip */}
            <SlipPreview r={viewing} />
          </div>
        )}
      </Modal>
    </div>
  );
}