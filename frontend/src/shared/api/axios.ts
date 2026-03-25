import axios, { AxiosError } from "axios";
import { storage } from "@/shared/utils/storage";

const baseURL = import.meta.env.VITE_API_BASE_URL;

if (!baseURL) {
  throw new Error("Missing VITE_API_BASE_URL");
}

export const api = axios.create({
  baseURL,
  withCredentials: false,
  headers: {
    Accept: "application/json",
  },
});

let isRedirectingToLogin = false;

api.interceptors.request.use((config) => {
  const token = storage.getToken();

  config.headers = config.headers ?? {};
  config.headers.Accept = "application/json";

  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }

  return config;
});

api.interceptors.response.use(
  (res) => res,
  async (err: AxiosError<any>) => {
    const status = err?.response?.status;

    console.error("API ERROR", {
      baseURL,
      url: err?.config?.url,
      method: err?.config?.method,
      status,
      data: err?.response?.data,
    });

    if (status === 429) {
      alert("ทำรายการถี่เกินไป กรุณารอสักครู่แล้วลองใหม่อีกครั้ง");
      return Promise.reject(err);
    }

    if (status === 401) {
      storage.clearAuth();

      if (!isRedirectingToLogin && window.location.pathname !== "/login") {
        isRedirectingToLogin = true;
        window.location.href = "/login";
      }

      return Promise.reject(err);
    }

    if (status === 403) {
      alert("คุณไม่มีสิทธิ์เข้าถึงข้อมูลนี้");
      return Promise.reject(err);
    }

    if (status === 422) {
      const errors = err?.response?.data?.errors;
      if (errors) {
        const firstError = Object.values(errors)[0] as string[] | undefined;
        if (firstError?.[0]) {
          alert(firstError[0]);
        }
      }
      return Promise.reject(err);
    }

    if (status && status >= 500) {
      alert("ระบบขัดข้อง กรุณาลองใหม่อีกครั้ง");
      return Promise.reject(err);
    }

    return Promise.reject(err);
  }
);

export default api;