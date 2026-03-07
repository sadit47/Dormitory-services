import { useEffect, useMemo, useState } from "react";
import { useNavigate, useSearchParams } from "react-router-dom";
import {
  tenantInvoicesApi,
  type TenantInvoiceDetail,
} from "@/features/tenant/invoices/services/tenantInvoicesApi";
import { tenantPaymentsApi } from "./services/tenantPaymentsApi";

function money(n: any) {
  return Number(n ?? 0).toLocaleString();
}

function todayISODate() {
  const d = new Date();
  const yyyy = d.getFullYear();
  const mm = String(d.getMonth() + 1).padStart(2, "0");
  const dd = String(d.getDate()).padStart(2, "0");
  return `${yyyy}-${mm}-${dd}`;
}

function getRoomLabel(room: any) {
  if (!room) return "-";
  return room.room_no ?? room.name ?? room.code ?? room.id ?? "-";
}

export default function TenantUploadSlipPage() {
  const nav = useNavigate();
  const [sp] = useSearchParams();
  const invoiceId = Number(sp.get("invoice_id") ?? 0);

  const [invoice, setInvoice] = useState<TenantInvoiceDetail | null>(null);
  const [loading, setLoading] = useState(true);

  const [amount, setAmount] = useState<string>("");
  const [paidAt, setPaidAt] = useState<string>(todayISODate());
  const [file, setFile] = useState<File | null>(null);

  const [submitting, setSubmitting] = useState(false);
  const [err, setErr] = useState<string | null>(null);
  const [okMsg, setOkMsg] = useState<string | null>(null);

  const previewUrl = useMemo(() => {
    if (!file) return null;
    if (file.type === "application/pdf") return null;
    return URL.createObjectURL(file);
  }, [file]);

  useEffect(() => {
    if (!invoiceId) {
      setLoading(false);
      setErr("ไม่พบ invoice_id กรุณาเข้าเมนูใบแจ้งหนี้แล้วกดชำระเงินอีกครั้ง");
      return;
    }

    setLoading(true);
    tenantInvoicesApi
      .show(invoiceId)
      .then((d) => {
        setInvoice(d);
        setAmount(String(d.total ?? ""));
      })
      .catch(() => setErr("โหลดข้อมูลใบแจ้งหนี้ไม่สำเร็จ"))
      .finally(() => setLoading(false));
  }, [invoiceId]);

  useEffect(() => {
    return () => {
      if (previewUrl) URL.revokeObjectURL(previewUrl);
    };
  }, [previewUrl]);

  const onPickFile = (f: File | null) => {
    setOkMsg(null);
    setErr(null);

    if (!f) {
      setFile(null);
      return;
    }

    const maxBytes = 5 * 1024 * 1024;
    const okTypes = ["image/jpeg", "image/png", "image/webp", "application/pdf"];

    if (!okTypes.includes(f.type)) {
      setErr("ไฟล์ต้องเป็น jpg, jpeg, png, webp หรือ pdf เท่านั้น");
      return;
    }

    if (f.size > maxBytes) {
      setErr("ไฟล์ใหญ่เกิน 5MB");
      return;
    }

    setFile(f);
  };

  const submit = async () => {
    setOkMsg(null);
    setErr(null);

    if (!invoiceId) return setErr("invoice_id ไม่ถูกต้อง");
    if (!amount || Number(amount) <= 0) return setErr("กรุณากรอกจำนวนเงินให้ถูกต้อง");
    if (!paidAt) return setErr("กรุณาเลือกวันที่ชำระ");
    if (!file) return setErr("กรุณาแนบสลิป");

    try {
      setSubmitting(true);

      const fd = new FormData();
      fd.append("amount", String(amount));
      fd.append("paid_at", paidAt);
      fd.append("slip", file);

      await tenantPaymentsApi.uploadSlip(invoiceId, fd);

      setOkMsg("อัปโหลดสลิปสำเร็จ (รอตรวจสอบ)");
      setTimeout(() => nav("/tenant/invoices"), 700);
    } catch (e: any) {
      const msg =
        e?.response?.data?.message ||
        e?.response?.data?.errors?.slip?.[0] ||
        e?.response?.data?.errors?.amount?.[0] ||
        "อัปโหลดไม่สำเร็จ กรุณาลองใหม่";
      setErr(msg);
    } finally {
      setSubmitting(false);
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

  return (
    <div className="space-y-5">
      {/* Top action */}
      <section className="flex items-center justify-between">
        <button
          onClick={() => nav(-1)}
          className="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-[0_8px_18px_rgba(15,23,42,0.04)] transition hover:bg-slate-50"
        >
          ← กลับ
        </button>
      </section>

      {/* Header / Invoice summary */}
      <section className="rounded-[28px] border border-white/70 bg-white/85 p-5 shadow-[0_14px_36px_rgba(15,23,42,0.06)] backdrop-blur-sm">
        <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
          <div>
            <div className="text-2xl font-bold tracking-tight text-slate-800">
              อัปโหลดสลิปชำระเงิน
            </div>
            <div className="mt-1 text-sm text-slate-500">
              แนบหลักฐานการโอนเพื่อส่งให้เจ้าหน้าที่ตรวจสอบรายการชำระเงิน
            </div>
          </div>

          {invoice && (
            <div className="rounded-2xl bg-slate-50 px-4 py-3 text-sm shadow-inner">
              <div className="text-slate-500">ยอดที่ต้องชำระ</div>
              <div className="mt-1 text-2xl font-bold tracking-tight text-slate-800">
                {money(invoice.total)} บาท
              </div>
            </div>
          )}
        </div>

        {invoice ? (
          <div className="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <InfoCard label="เลขที่ใบแจ้งหนี้" value={invoice.invoice_no} />
            <InfoCard
              label="งวด"
              value={`${invoice.period_month}/${invoice.period_year}`}
            />
            <InfoCard label="ห้อง" value={String(getRoomLabel(invoice.room))} />
            <InfoCard label="กำหนดชำระ" value={String(invoice.due_date ?? "-")} />
          </div>
        ) : (
          <div className="mt-4 rounded-2xl border border-rose-100 bg-rose-50 px-4 py-4 text-sm text-rose-700">
            {err ?? "ไม่พบข้อมูลใบแจ้งหนี้"}
          </div>
        )}
      </section>

      {/* Form */}
      <section className="rounded-[28px] border border-white/70 bg-white/85 p-5 shadow-[0_14px_36px_rgba(15,23,42,0.06)] backdrop-blur-sm">
        <div className="mb-4">
          <div className="text-lg font-semibold text-slate-800">ข้อมูลการชำระเงิน</div>
          <div className="mt-1 text-sm text-slate-500">
            กรอกจำนวนเงิน วันที่ชำระ และแนบสลิปการโอน
          </div>
        </div>

        {err && (
          <div className="mb-4 rounded-2xl border border-rose-100 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {err}
          </div>
        )}

        {okMsg && (
          <div className="mb-4 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {okMsg}
          </div>
        )}

        <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
          <div className="rounded-2xl border border-slate-200/80 bg-linear-to-r from-white to-slate-50/70 p-4 shadow-[0_8px_20px_rgba(15,23,42,0.04)]">
            <label className="text-sm font-medium text-slate-700">จำนวนเงิน</label>
            <input
              value={amount}
              onChange={(e) => setAmount(e.target.value)}
              inputMode="decimal"
              placeholder="เช่น 3500"
              className="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-800 outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
            />
            <div className="mt-2 text-xs text-slate-500">
              ระบบจะตรวจสอบยอดและสถานะการชำระกับเจ้าหน้าที่อีกครั้ง
            </div>
          </div>

          <div className="rounded-2xl border border-slate-200/80 bg-linear-to-r from-white to-slate-50/70 p-4 shadow-[0_8px_20px_rgba(15,23,42,0.04)]">
            <label className="text-sm font-medium text-slate-700">วันที่ชำระ</label>
            <input
              type="date"
              value={paidAt}
              onChange={(e) => setPaidAt(e.target.value)}
              className="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-800 outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
            />
            <div className="mt-2 text-xs text-slate-500">
              กรุณาเลือกวันที่ที่ทำรายการโอนเงินจริง
            </div>
          </div>
        </div>

        {/* Upload area */}
        <div className="mt-4 rounded-3xl border border-dashed border-slate-300 bg-slate-50/80 p-4 sm:p-5">
          <div className="text-sm font-medium text-slate-700">
            แนบสลิปการชำระเงิน
          </div>
          <div className="mt-1 text-sm text-slate-500">
            รองรับไฟล์ jpg, jpeg, png, webp และ pdf ขนาดไม่เกิน 5MB
          </div>

          <div className="mt-4">
            <label className="inline-flex cursor-pointer items-center rounded-2xl bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-[0_8px_18px_rgba(15,23,42,0.05)] ring-1 ring-slate-200 transition hover:bg-slate-50">
              📎 เลือกไฟล์สลิป
              <input
                type="file"
                accept=".jpg,.jpeg,.png,.webp,.pdf"
                onChange={(e) => onPickFile(e.target.files?.[0] ?? null)}
                className="hidden"
              />
            </label>
          </div>

          {!file ? (
            <div className="mt-4 rounded-2xl border border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-400">
              ยังไม่ได้เลือกไฟล์
            </div>
          ) : (
            <div className="mt-4 rounded-3xl border border-slate-200 bg-white p-4 shadow-[0_8px_18px_rgba(15,23,42,0.04)]">
              <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                  <div className="text-sm text-slate-500">ไฟล์ที่เลือก</div>
                  <div className="mt-1 font-medium text-slate-800">{file.name}</div>
                  <div className="mt-1 text-sm text-slate-500">
                    ขนาด {Math.round(file.size / 1024)} KB
                  </div>
                </div>

                <button
                  type="button"
                  onClick={() => setFile(null)}
                  className="rounded-2xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
                >
                  ลบไฟล์
                </button>
              </div>

              {previewUrl ? (
                <div className="mt-4">
                  <div className="mb-2 text-sm font-medium text-slate-700">ตัวอย่างสลิป</div>
                  <img
                    src={previewUrl}
                    alt="slip preview"
                    className="max-h-130 w-full rounded-2xl border border-slate-200 object-contain shadow-[0_10px_24px_rgba(15,23,42,0.06)]"
                  />
                </div>
              ) : (
                <div className="mt-4 rounded-2xl bg-slate-50 px-4 py-4 text-sm text-slate-500">
                  ไฟล์ PDF จะไม่แสดงตัวอย่างบนหน้านี้
                </div>
              )}
            </div>
          )}
        </div>

        {/* Submit */}
        <div className="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div className="text-sm text-slate-500">
            กรุณาตรวจสอบเลขที่ใบแจ้งหนี้ จำนวนเงิน และไฟล์สลิปก่อนยืนยัน
          </div>

          <button
            onClick={submit}
            disabled={submitting || !invoice}
            className="rounded-2xl bg-linear-to-r from-indigo-600 to-blue-600 px-6 py-3 font-medium text-white shadow-[0_12px_26px_rgba(59,130,246,0.22)] transition hover:-translate-y-0.5 hover:shadow-[0_16px_30px_rgba(59,130,246,0.28)] disabled:cursor-not-allowed disabled:opacity-50"
          >
            {submitting ? "กำลังอัปโหลด..." : "ยืนยันอัปโหลดสลิป"}
          </button>
        </div>
      </section>
    </div>
  );
}

function InfoCard({ label, value }: { label: string; value: string }) {
  return (
    <div className="rounded-2xl border border-slate-200/80 bg-linear-to-r from-white to-slate-50/70 p-4 shadow-[0_8px_20px_rgba(15,23,42,0.04)]">
      <div className="text-sm text-slate-500">{label}</div>
      <div className="mt-2 font-semibold text-slate-800">{value}</div>
    </div>
  );
}