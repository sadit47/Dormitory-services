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

export type InvoiceType = "rent" | "utility" | "repair" | "cleaning";
export type InvoiceStatus = "unpaid" | "paid" | "void" | "draft" | string;

export type InvoiceItemPayload = {
  description: string;
  qty: number;
  unit_price: number;
};

export type InvoiceCreatePayload = {
  tenant_id: number;
  room_id?: number | null;
  type: InvoiceType;
  period_month: number; // 1-12
  period_year: number; // 2000-2100
  due_date?: string | null; // YYYY-MM-DD
  discount?: number | string | null;
  items: InvoiceItemPayload[];
};

export type InvoiceUpdatePayload = {
  due_date?: string | null;
  discount?: number | string | null;
  items: InvoiceItemPayload[];
};

export const adminInvoicesApi = {
  list: async (q = "", type = "", status = "", page = 1, per_page = 10) =>
    unwrap(await api.get("/admin/invoices", { params: { q, type, status, page, per_page } })),

  meta: async () => unwrap(await api.get("/admin/invoices/meta")),

  show: async (id: number) => unwrap(await api.get(`/admin/invoices/${id}`)),

  create: async (payload: InvoiceCreatePayload) => unwrap(await api.post("/admin/invoices", payload)),

  update: async (id: number, payload: InvoiceUpdatePayload) =>
    unwrap(await api.put(`/admin/invoices/${id}`, payload)),

  remove: async (id: number) => unwrap(await api.delete(`/admin/invoices/${id}`)),

  // ✅ ต้องโหลดเป็น blob ผ่าน axios (Bearer token จะถูกแนบไปด้วย)
  downloadPdf: async (id: number) => api.get(`/admin/invoices/${id}/pdf`, { responseType: "blob" }),
};