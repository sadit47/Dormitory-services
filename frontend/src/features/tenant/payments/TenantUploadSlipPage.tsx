import { useEffect, useMemo, useState } from "react";
import { useNavigate, useSearchParams } from "react-router-dom";
import { tenantInvoicesApi, type TenantInvoiceDetail } from "@/features/tenant/invoices/services/tenantInvoicesApi";
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

  // preview url สำหรับรูป
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
        // auto fill amount = total
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
    if (!f) return setFile(null);

    // validate ฝั่งหน้า (กันพลาดก่อนถึง backend)
    const maxBytes = 5 * 1024 * 1024; // 5MB
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
      // พาไปหน้าใบแจ้งหนี้หลังสำเร็จ
      setTimeout(() => nav("/tenant/invoices"), 700);
    } catch (e: any) {
      // รองรับ laravel validation error
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

  if (loading) return <div className="p-4 text-gray-500">กำลังโหลด...</div>;

  return (
    <div className="space-y-4">
      {/* Top actions */}
      <div className="flex items-center justify-between gap-3">
        <button onClick={() => nav(-1)} className="px-3 py-2 rounded-xl border bg-white">
          ← กลับ
        </button>
      </div>

      {/* Invoice summary */}
      <div className="bg-white border rounded-2xl shadow-sm p-5">
        <div className="text-lg font-semibold text-gray-900">อัปโหลดสลิปชำระเงิน</div>

        {invoice ? (
          <div className="mt-3 text-sm text-gray-600 space-y-1">
            <div>
              ใบแจ้งหนี้: <span className="font-medium text-gray-900">{invoice.invoice_no}</span>
            </div>
            <div>
              งวด: <span className="font-medium text-gray-900">{invoice.period_month}/{invoice.period_year}</span>
            </div>
            <div>
              ยอดที่ต้องชำระ: <span className="font-semibold text-gray-900">{money(invoice.total)} บาท</span>
            </div>
            {invoice.due_date && <div>กำหนดชำระ: <span className="font-medium text-gray-900">{invoice.due_date}</span></div>}
          </div>
        ) : (
          <div className="mt-2 text-sm text-gray-500">
            {err ?? "ไม่พบข้อมูลใบแจ้งหนี้"}
          </div>
        )}
      </div>

      {/* Form */}
      <div className="bg-white border rounded-2xl shadow-sm p-5 space-y-4">
        {err && (
          <div className="p-3 rounded-xl bg-rose-50 text-rose-700 text-sm border border-rose-100">
            {err}
          </div>
        )}
        {okMsg && (
          <div className="p-3 rounded-xl bg-emerald-50 text-emerald-700 text-sm border border-emerald-100">
            {okMsg}
          </div>
        )}

        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label className="text-sm text-gray-600">จำนวนเงิน</label>
            <input
              value={amount}
              onChange={(e) => setAmount(e.target.value)}
              inputMode="decimal"
              className="mt-1 w-full border rounded-xl px-3 py-2 bg-white"
              placeholder="เช่น 3500"
            />
            <div className="text-xs text-gray-500 mt-1">
              * ระบบจะตรวจสอบยอดและสถานะกับเจ้าหน้าที่อีกครั้ง
            </div>
          </div>

          <div>
            <label className="text-sm text-gray-600">วันที่ชำระ</label>
            <input
              type="date"
              value={paidAt}
              onChange={(e) => setPaidAt(e.target.value)}
              className="mt-1 w-full border rounded-xl px-3 py-2 bg-white"
            />
          </div>
        </div>

        <div>
          <label className="text-sm text-gray-600">แนบสลิป (jpg/png/webp/pdf, ไม่เกิน 5MB)</label>
          <input
            type="file"
            accept=".jpg,.jpeg,.png,.webp,.pdf"
            onChange={(e) => onPickFile(e.target.files?.[0] ?? null)}
            className="mt-1 w-full"
          />

          {file && (
            <div className="mt-3 border rounded-xl p-3 bg-gray-50">
              <div className="text-sm text-gray-700">
                ไฟล์: <span className="font-medium">{file.name}</span> ({Math.round(file.size / 1024)} KB)
              </div>

              {previewUrl ? (
                <img
                  src={previewUrl}
                  alt="slip preview"
                  className="mt-3 w-full max-w-md rounded-xl border"
                />
              ) : (
                <div className="mt-2 text-sm text-gray-500">ไฟล์ PDF จะไม่แสดง preview</div>
              )}
            </div>
          )}
        </div>

        <button
          onClick={submit}
          disabled={submitting || !invoice}
          className="w-full sm:w-auto px-5 py-3 rounded-xl bg-indigo-600 text-white font-medium disabled:opacity-50"
        >
          {submitting ? "กำลังอัปโหลด..." : "ยืนยันอัปโหลดสลิป"}
        </button>
      </div>
    </div>
  );
}