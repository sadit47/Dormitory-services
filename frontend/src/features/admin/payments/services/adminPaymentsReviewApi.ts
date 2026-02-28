import { api } from "@/shared/api/axios";

function unwrap(res: any) {
  const root = res?.data ?? res;

  // apiResponse wrapper: { ok, message, data: ... }
  if (root && typeof root === "object" && "ok" in root && "data" in root) {
    return (root as any).data;
  }

  // paginate object directly: { current_page, data, total, ... }
  if (root && typeof root === "object" && "current_page" in root && "data" in root) {
    return root;
  }

  // nested paginate: { data: { current_page, ... } }
  if (root && typeof root === "object" && "data" in root && (root as any).data?.current_page) {
    return (root as any).data;
  }

  return root;
}

export type PaymentStatus = "waiting" | "approved" | "rejected" | string;

export type PendingPaymentRow = {
  id: number;
  amount?: number | string | null;
  status?: PaymentStatus;
  paid_at?: string | null;
  created_at?: string | null;

  invoice?: {
    id?: number;
    invoice_no?: string | null;
    tenant?: {
      id?: number;
      user?: { id?: number; name?: string | null; email?: string | null } | null;
    } | null;
  } | null;

  slip?: {
    file_id?: number;
    original_name?: string | null;
    mime?: string | null;
    url?: string | null; // backend ส่ง route('files.show', id)
  } | null;
};

type Paged<T> = {
  current_page?: number;
  data?: T[];
  total?: number;
  per_page?: number;
  last_page?: number;
};

export const adminPaymentsReviewApi = {
  pending: async (page = 1, per_page = 20) =>
    unwrap(await api.get("/admin/payments/pending", { params: { page, per_page } })) as Paged<PendingPaymentRow>,

  approve: async (paymentId: number) =>
    unwrap(await api.post(`/admin/payments/${paymentId}/approve`)),

  reject: async (paymentId: number) =>
    unwrap(await api.post(`/admin/payments/${paymentId}/reject`)),

  // ✅ โหลดไฟล์สลิปแบบ bearer (blob)
  fileBlob: async (fileId: number) =>
    api.get(`/files/${fileId}`, { responseType: "blob" }),
};