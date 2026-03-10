import { api } from "@/shared/api/axios";

function unwrap(res: any) {
  const root = res?.data ?? res;

  if (root && typeof root === "object" && "ok" in root && "data" in root) {
    return (root as any).data;
  }

  if (root && typeof root === "object" && "current_page" in root && "data" in root) {
    return root;
  }

  if (root && typeof root === "object" && "data" in root && (root as any).data?.current_page) {
    return (root as any).data;
  }

  return root;
}

export type ParcelPayload = {
  tenant_id: number;
  tracking_no?: string | null;
  courier?: string | null;
  sender_name?: string | null;
  note?: string | null;
  images?: File[];
};

export type ParcelUpdatePayload = {
  tracking_no?: string | null;
  courier?: string | null;
  sender_name?: string | null;
  note?: string | null;
  status?: "arrived" | "picked_up" | "cancelled" | string;
  images?: File[];
};

function toFormData(payload: Record<string, any>) {
  const fd = new FormData();

  Object.entries(payload).forEach(([key, value]) => {
    if (value === undefined || value === null || value === "") return;

    if (Array.isArray(value)) {
      value.forEach((item) => {
        fd.append(`${key}[]`, item);
      });
      return;
    }

    fd.append(key, String(value));
  });

  return fd;
}

export const adminParcelsApi = {
  list: async (status = "", q = "", page = 1, per_page = 10) =>
    unwrap(await api.get("/admin/parcels", { params: { status, q, page, per_page } })),

  show: async (id: number) =>
    unwrap(await api.get(`/admin/parcels/${id}`)),

  create: async (payload: ParcelPayload) =>
    unwrap(
      await api.post("/admin/parcels", toFormData(payload), {
        headers: { "Content-Type": "multipart/form-data" },
      })
    ),

  update: async (id: number, payload: ParcelUpdatePayload) => {
    const fd = toFormData(payload);
    fd.append("_method", "PUT");
    return unwrap(
      await api.post(`/admin/parcels/${id}`, fd, {
        headers: { "Content-Type": "multipart/form-data" },
      })
    );
  },

  pickup: async (id: number) =>
    unwrap(await api.post(`/admin/parcels/${id}/pickup`)),

  remove: async (id: number) =>
    unwrap(await api.delete(`/admin/parcels/${id}`)),

  fileBlob: async (fileId: number) =>
  api.get(`/files/${fileId}`, { responseType: "blob" }),
};