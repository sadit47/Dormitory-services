import axios from "axios";
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

api.interceptors.request.use((config) => {
  const token = storage.getToken();

  if (token) {
    config.headers = config.headers ?? {};
    config.headers.Authorization = `Bearer ${token}`;
  }

  config.headers = config.headers ?? {};
  config.headers.Accept = "application/json";

  return config;
});

api.interceptors.response.use(
  (res) => res,
  (err) => {
    console.error("API ERROR", {
      baseURL,
      url: err?.config?.url,
      status: err?.response?.status,
      data: err?.response?.data,
    });

    return Promise.reject(err);
  }
);