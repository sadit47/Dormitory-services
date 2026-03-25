export const storage = {
  getToken: () => localStorage.getItem("token"),
  setToken: (t: string) => localStorage.setItem("token", t),
  clearToken: () => localStorage.removeItem("token"),

  getRole: () => (localStorage.getItem("role") as "admin" | "tenant" | null),
  setRole: (r: "admin" | "tenant") => localStorage.setItem("role", r),
  clearRole: () => localStorage.removeItem("role"),

  clearAuth: () => {
    localStorage.removeItem("token");
    localStorage.removeItem("role");
  },
};