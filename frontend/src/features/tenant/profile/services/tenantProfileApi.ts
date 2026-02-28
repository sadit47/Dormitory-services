import { api } from "@/shared/api/axios";

function unwrap(res: any) {
  const root = res?.data ?? res;
  if (root && typeof root === "object" && "ok" in root && "data" in root) return (root as any).data;
  return root;
}

export type TenantProfileRoom = {
  id: number;
  code?: string | null;
  room_no?: string | null;
  name?: string | null;
  floor?: number | null;
  status?: string | null;
};

export type TenantProfileUser = {
  id: number;
  name: string;
  email: string;
  phone?: string | null;
  role: string;
};

export type TenantProfileTenant = {
  id: number;
  citizen_id?: string | null;
  address?: string | null;
  emergency_contact?: string | null;
  start_date?: string | null;
  end_date?: string | null;
};

export type TenantProfileResponse = {
  user: { id: number; name: string; email: string; phone?: string | null; role: string };
  tenant: {
    id: number;
    citizen_id?: string | null;
    address?: string | null;
    emergency_contact?: string | null;
    start_date?: string | null;
    end_date?: string | null;
  };
  current_room: any | null;
};

export type TenantProfilePayload = {
  citizen_id?: string | null;
  address?: string | null;
  emergency_contact?: string | null;

  // ❗ จะส่งไป update ได้ แต่หน้าเราจะ “ไม่ให้แก้” วันที่ เพื่อยึดตามแอดมิน
  start_date?: string | null; // YYYY-MM-DD
  end_date?: string | null; // YYYY-MM-DD
};

export const tenantProfileApi = {
  show: async (): Promise<TenantProfileResponse> => unwrap(await api.get("/tenant/profile")),
  update: async (payload: TenantProfilePayload) => unwrap(await api.put("/tenant/profile", payload)),
};