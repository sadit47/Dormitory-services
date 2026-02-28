import { api } from "@/shared/api/axios";

export const adminDashboardApi = {
  summary: async () => {
    const res = await api.get("/admin/dashboard/summary");
    return res.data;
  },
};
