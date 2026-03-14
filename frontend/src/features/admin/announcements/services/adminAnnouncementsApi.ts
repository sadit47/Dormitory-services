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

export type AnnouncementTargetPayload = {
  target_type: "all" | "room" | "tenant";
  target_id?: number | null;
};

export type AnnouncementPayload = {
  title: string;
  content: string;
  type: "general" | "urgent" | "maintenance";
  status?: "draft" | "published" | "expired" | string;
  is_pinned?: boolean;
  starts_at?: string | null;
  ends_at?: string | null;
  targets?: AnnouncementTargetPayload[];
  images?: File[];
};

function toMysqlDateTime(v?: string | null) {
  if (!v) return "";
  return v.replace("T", " ") + ":00";
}

function toFormData(payload: AnnouncementPayload) {
  const fd = new FormData();

  fd.append("title", payload.title);
  fd.append("content", payload.content);
  fd.append("type", payload.type);
  fd.append("status", payload.status ?? "draft");
  fd.append("is_pinned", payload.is_pinned ? "1" : "0");

  if (payload.starts_at) {
    fd.append("starts_at", toMysqlDateTime(payload.starts_at));
  }

  if (payload.ends_at) {
    fd.append("ends_at", toMysqlDateTime(payload.ends_at));
  }

  (payload.targets ?? []).forEach((t, i) => {
    fd.append(`targets[${i}][target_type]`, t.target_type);
    if (t.target_id != null) {
      fd.append(`targets[${i}][target_id]`, String(t.target_id));
    }
  });

  (payload.images ?? []).forEach((file) => {
    fd.append("images[]", file);
  });

  return fd;
}

export const adminAnnouncementsApi = {
  list: async (status = "", type = "", q = "", page = 1, per_page = 10) =>
    unwrap(await api.get("/admin/announcements", { params: { status, type, q, page, per_page } })),

  show: async (id: number) =>
    unwrap(await api.get(`/admin/announcements/${id}`)),

  create: async (payload: AnnouncementPayload) =>
    unwrap(await api.post("/admin/announcements", toFormData(payload))),

  update: async (id: number, payload: AnnouncementPayload) =>
    unwrap(await api.post(`/admin/announcements/${id}`, toFormData(payload))),

  publish: async (id: number) =>
    unwrap(await api.post(`/admin/announcements/${id}/publish`)),

  expire: async (id: number) =>
    unwrap(await api.post(`/admin/announcements/${id}/expire`)),

  remove: async (id: number) =>
    unwrap(await api.delete(`/admin/announcements/${id}`)),
};