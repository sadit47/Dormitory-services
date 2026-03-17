import { api } from "@/shared/api/axios";

function unwrap(res: any) {
  const root = res?.data ?? res;

  if (root && typeof root === "object" && "ok" in root && "data" in root) return (root as any).data;
  if (root && typeof root === "object" && "current_page" in root && "data" in root) return root;
  if (root && typeof root === "object" && "data" in root && (root as any).data?.current_page) return (root as any).data;

  return root;
}

export type PaymentStatus = "waiting" | "approved" | "rejected" | string | null;

export type TenantDashboardInvoice = {
  id: number;
  invoice_no: string;
  type: string;
  period_month: number;
  period_year: number;
  total: number | string;
  due_date?: string | null;
  status: string;
  receipt_no?: string | null;
  created_at?: string;
  payment_status?: PaymentStatus;
  room?: any;
};

export type TenantDashboardSummary = {
  user: { id: number; name: string; email: string; role: string };
  tenant: any | null;
  current_room: any | null;

  summary: {
    total_due: number;
    unpaid_invoices: number;
    repair_open: number;
  };

  latest_invoices: TenantDashboardInvoice[];
  recent_unpaid: TenantDashboardInvoice[];

  paid_history?: Array<{ label: string; amount: number }>;
  debug?: any;
};

export const tenantDashboardApi = {
  summary: async (): Promise<TenantDashboardSummary> =>
    unwrap(await api.get("/tenant/dashboard/summary")), // ✅ ไม่มี / นำหน้า
};