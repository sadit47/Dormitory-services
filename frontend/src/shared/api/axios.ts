import axios from "axios";
import { storage } from "@/shared/utils/storage";

const baseURL = import.meta.env.VITE_API_BASE_URL || "http://localhost:8088/api/v1";

export const api = axios.create({
  baseURL,
  withCredentials: false,
  headers: {
    Accept: "application/json", //  กัน Laravel redirect แล้วส่ง HTML
  },
});

api.interceptors.request.use((config) => {
  const token = storage.getToken();
  if (token) {
    config.headers = config.headers ?? {};
    config.headers.Authorization = `Bearer ${token}`;
  }

  //  API ตอบ JSON
  config.headers = config.headers ?? {};
  config.headers.Accept = "application/json";

  return config;
});

api.interceptors.response.use(
  (res) => res,
  (err) => {
    // debug ให้เห็นจริงว่า backend ส่งอะไรกลับมา
    const status = err?.response?.status;
    const data = err?.response?.data;

    // ถ้า backend ส่ง HTML จะเห็นเป็น string ยาวๆ
    console.error("API ERROR", {
      baseURL,
      url: err?.config?.url,
      status,
      data,
    });

    return Promise.reject(err);
  }
);
