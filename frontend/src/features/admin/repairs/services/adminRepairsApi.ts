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

export type RepairStatus = "submitted" | "in_progress" | "done" | "rejected";

export const adminRepairsApi = {
  list: async (q = "", status = "", page = 1, per_page = 10) =>
    unwrap(await api.get("/admin/repairs", { params: { q, status, page, per_page } })),

  // ✅ รับ status เป็น RepairStatus ตรง ๆ
  updateStatus: async (id: number, status: RepairStatus) =>
    unwrap(await api.patch(`/admin/repairs/${id}/status`, { status })),

  remove: async (id: number) => unwrap(await api.delete(`/admin/repairs/${id}`)),

  // ✅ สำคัญ: โหลดไฟล์แบบ blob (จะส่ง Bearer ได้)
  fileBlob: async (fileId: number) =>
    api.get(`/files/${fileId}`, { responseType: "blob" }),
};