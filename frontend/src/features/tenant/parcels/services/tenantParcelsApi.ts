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

export const tenantParcelsApi = {
  list: async (status = "", page = 1, per_page = 10) =>
    unwrap(await api.get("/tenant/parcels", { params: { status, page, per_page } })),

  show: async (id: number) =>
    unwrap(await api.get(`/tenant/parcels/${id}`)),

  fileBlob: async (fileId: number) =>
  api.get(`/files/${fileId}`, { responseType: "blob" }),
};