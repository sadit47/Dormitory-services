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

export type InvoiceStatus = "unpaid" | "partial" | "paid" | "void" | "draft" | string;
export type PaymentStatus = "waiting" | "approved" | "rejected" | string | null;

export type TenantInvoiceListItem = {
  id: number;
  invoice_no: string;
  type: string;
  period_month: number;
  period_year: number;
  total: number | string;
  due_date?: string | null;
  status: InvoiceStatus;
  receipt_no?: string | null;
  created_at?: string;

  // ✅ เพิ่มจาก backend (ดูว่ามีส่งสลิปรออนุมัติไหม)
  payment_status?: PaymentStatus;
};

export type TenantInvoiceDetail = TenantInvoiceListItem & {
  room?: any;
  items?: Array<{
    id: number;
    description: string;
    qty: number;
    unit_price: number | string;
    total?: number | string;
  }>;
};

export type Paginated<T> = {
  current_page: number;
  data: T[];
  total: number;
  per_page: number;
  last_page: number;
};

export const tenantInvoicesApi = {
  list: async (page = 1, per_page = 10): Promise<Paginated<TenantInvoiceListItem>> =>
    unwrap(await api.get("/tenant/invoices", { params: { page, per_page } })),

  show: async (id: number): Promise<TenantInvoiceDetail> =>
    unwrap(await api.get(`/tenant/invoices/${id}`)),

  // ✅ เปิดดู PDF ผ่าน blob (แนบ auth ผ่าน axios ได้ชัวร์)
  openPdfBlob: async (id: number) =>
    api.get(`/tenant/invoices/${id}/pdf`, { responseType: "blob" }),
};