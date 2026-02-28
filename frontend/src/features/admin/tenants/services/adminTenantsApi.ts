import { api } from "@/shared/api/axios";

export type Paged<T> = {
  data: T[];
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
};

type ApiResponse<T> = { ok: boolean; message: string; data: T };

function unwrap<T>(res: any): T {
  const root = res?.data ?? res;
  if (root && typeof root === "object" && "ok" in root && "data" in root) return (root as ApiResponse<T>).data;
  return root as T;
}

export type TenantPayload = {
  name: string;
  email: string;
  phone?: string | null;

  citizen_id?: string | null;
  address?: string | null;

  // assign room (optional)
  room_id?: number | null;

  start_date?: string | null;

  // end_date optional
  end_date?: string | null; // "YYYY-MM-DD"

  // password options (optional)
  password?: string | null;
  password_confirmation?: string | null;
};

export const adminTenantsApi = {
  meta: async () => unwrap<any>(await api.get("/admin/tenants/meta")),

  list: async (q = "", page = 1, per_page = 10) =>
    unwrap<Paged<any>>(await api.get("/admin/tenants", { params: { q, page, per_page } })),

  create: async (payload: TenantPayload) =>
    unwrap<any>(await api.post("/admin/tenants", payload)),

  update: async (id: number, payload: TenantPayload) =>
    unwrap<any>(await api.put(`/admin/tenants/${id}`, payload)),

  remove: async (id: number) => unwrap<any>(await api.delete(`/admin/tenants/${id}`)),
};
