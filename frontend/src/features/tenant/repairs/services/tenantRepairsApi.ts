import { api } from "@/shared/api/axios";

function unwrap(res: any) {
  const root = res?.data ?? res;
  if (root && typeof root === "object" && "ok" in root && "data" in root) return (root as any).data;
  if (root && typeof root === "object" && "current_page" in root && "data" in root) return root;
  if (root && typeof root === "object" && "data" in root && (root as any).data?.current_page) return (root as any).data;
  return root;
}

export type RepairPriority = "low" | "medium" | "high";
export type RepairStatus = "submitted" | "pending" | "in_progress" | "completed" | string;

export type RepairListItem = {
  id: number;
  title: string;
  description?: string | null;
  priority: RepairPriority;
  status: RepairStatus;
  requested_at?: string | null;
  created_at?: string;
  room_id?: number | null;
};

export type Paginated<T> = {
  current_page: number;
  data: T[];
  total: number;
  per_page: number;
  last_page: number;
};

export type RepairCreatePayload = {
  title: string;
  description?: string;
  priority: RepairPriority;
  images?: File[];
};

export const tenantRepairsApi = {
  list: async (page = 1, per_page = 10): Promise<Paginated<RepairListItem>> =>
    unwrap(await api.get("tenant/repairs", { params: { page, per_page } })), // ✅ ไม่มี / นำหน้า

  create: async (payload: RepairCreatePayload) => {
    const fd = new FormData();
    fd.append("title", payload.title);
    fd.append("priority", payload.priority);
    if (payload.description) fd.append("description", payload.description);

    (payload.images ?? []).forEach((f) => fd.append("images[]", f));

    return unwrap(
      await api.post("tenant/repairs", fd, {
        headers: { "Content-Type": "multipart/form-data" },
      })
    );
  },
};