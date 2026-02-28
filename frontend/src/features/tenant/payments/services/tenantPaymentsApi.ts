import { api } from "@/shared/api/axios";

function unwrap(res: any) {
  const root = res?.data ?? res;
  if (root && typeof root === "object" && "ok" in root && "data" in root) return (root as any).data;
  return root;
}

export type PaymentCreateResult = {
  id: number;
  invoice_id: number;
  amount: number | string;
  status: "waiting" | "approved" | "rejected" | string;
};

export const tenantPaymentsApi = {
  uploadSlip: async (invoiceId: number, form: FormData): Promise<PaymentCreateResult> => {
    // สำคัญ: ต้องเป็น multipart/form-data
    return unwrap(
      await api.post(`/tenant/payments/${invoiceId}`, form, {
        headers: { "Content-Type": "multipart/form-data" },
      })
    );
  },
};