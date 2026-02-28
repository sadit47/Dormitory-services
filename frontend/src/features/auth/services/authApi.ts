import { api } from "../../../shared/api/axios";

export type Role = "admin" | "tenant";

type User = { id: number; name: string; email: string; role: Role };

type LoginResponse = {
  ok?: boolean;
  message?: string;
  data?: { token: string; user: User };
  token?: string;
  user?: User;
};

type MeResponse = {
  ok?: boolean;
  message?: string;
  data?: { user: User };
  user?: User;
};

// ✅ normalize ให้ frontend ใช้งานง่าย ไม่ error
function unwrapLogin(root: LoginResponse): { token: string; user: User } {
  // แบบ apiResponse: { ok, data: { token, user } }
  if (root?.data?.token && root?.data?.user) return root.data;

  // แบบไม่ wrap: { token, user }
  if (root?.token && root?.user) return { token: root.token, user: root.user };

  throw new Error("Invalid login response shape");
}

function unwrapMe(root: MeResponse): { user: User } {
  if (root?.data?.user) return { user: root.data.user };
  if (root?.user) return { user: root.user };
  throw new Error("Invalid me response shape");
}

export const authApi = {
  // ✅ เปลี่ยน param เป็น client (ตามที่หน้า login ส่ง "admin-web"/"tenant-web")
  // และยังรองรับ role ด้วย (optional) เผื่อ backend ใช้
  login: async (
    email: string,
    password: string,
    client: string,
    role?: Role,
    device_name = "react"
  ) => {
    const res = await api.post<LoginResponse>("/auth/login", {
      email,
      password,
      client,       // ✅ ใช้แก้เส้นแดง "admin-web"/"tenant-web"
      role,         // ✅ เผื่อ backend ต้องการ "admin"/"tenant"
      device_name,
    });

    return unwrapLogin(res.data);
  },

  me: async () => {
    const res = await api.get<MeResponse>("/auth/me");
    return unwrapMe(res.data);
  },

  logout: async () => {
    const res = await api.post("/auth/logout");
    return res.data;
  },
};