import { api } from "@/shared/api/axios";

function unwrap(res: any) {
  const root = res?.data ?? res;

  // apiResponse wrapper: { ok, message, data: ... }
  if (root && typeof root === "object" && "ok" in root && "data" in root) {
    return (root as any).data;
  }

  // paginate object directly: { current_page, data, total, ... }
  if (root && typeof root === "object" && "current_page" in root && "data" in root) {
    return root;
  }

  // nested paginate: { data: { current_page, ... } }
  if (root && typeof root === "object" && "data" in root && (root as any).data?.current_page) {
    return (root as any).data;
  }

  return root;
}

export type RoomPayload = {
  code: string;
  floor: number;
  room_type_id?: number | null;
  price_monthly?: number | string | null;
  status: "vacant" | "occupied" | "maintenance" | string;
};

export const adminRoomsApi = {
  list: async (status = "", search = "", page = 1, per_page = 10) =>
    unwrap(await api.get("/admin/rooms", { params: { status, search, page, per_page } })),

  meta: async () => unwrap(await api.get("/admin/rooms/meta")),

  create: async (payload: RoomPayload) =>
    unwrap(await api.post("/admin/rooms", payload)),

  update: async (id: number, payload: RoomPayload) =>
    unwrap(await api.put(`/admin/rooms/${id}`, payload)),

  remove: async (id: number) =>
  unwrap(await api.delete(`/admin/rooms/${id}`)),

};
