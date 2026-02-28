import { api } from "@/shared/api/axios";

function unwrap(res: any) {
  const root = res?.data ?? res;

  // apiResponse wrapper: { ok, message, data: ... }
  if (root && typeof root === "object" && "ok" in root && "data" in root) {
    return (root as any).data;
  }

  // sometimes API returns raw data
  return root;
}

export type AdminProfile = {
  id: number;
  name: string;
  email: string;
  phone: string | null;
};

export type AdminProfileUpdatePayload = {
  name: string;
  phone?: string | null;
};

export const adminProfileApi = {
  show: async (): Promise<AdminProfile> => unwrap(await api.get("/admin/profile")),
  update: async (payload: AdminProfileUpdatePayload) =>
    unwrap(await api.put("/admin/profile", payload)),
};