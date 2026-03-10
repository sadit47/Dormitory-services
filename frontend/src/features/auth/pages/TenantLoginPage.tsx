import { useMemo, useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { authApi } from "../services/authApi";
import { storage } from "../../../shared/utils/storage";

export default function TenantLoginPage() {
  const nav = useNavigate();

  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [err, setErr] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  const canSubmit = useMemo(() => {
    return (
      String(email).trim().length > 0 &&
      String(password).trim().length > 0 &&
      !loading
    );
  }, [email, password, loading]);

  const onSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!canSubmit) return;

    setErr(null);
    setLoading(true);

    try {
      const res = await authApi.login(email, password, "tenant-web");

      const role = res.user.role;

      if (role !== "tenant") {
        storage.clearToken();
        storage.clearRole();
        setErr("บัญชีนี้ไม่ใช่ผู้เช่า (Tenant)");
        return;
      }

      storage.setToken(res.token);
      storage.setRole(role);
      nav("/tenant", { replace: true });
    } catch (e: any) {
      setErr(e?.response?.data?.message ?? e?.message ?? "Login failed");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="relative min-h-screen overflow-hidden bg-linear-to-br from-emerald-50 via-cyan-50 to-blue-100">

      {/* glow background */}
      <div className="absolute inset-0">
        <div className="absolute -left-20 top-10 h-72 w-72 rounded-full bg-emerald-300/30 blur-3xl" />
        <div className="absolute right-0 top-0 h-80 w-80 rounded-full bg-cyan-300/25 blur-3xl" />
        <div className="absolute bottom-10 left-1/3 h-72 w-72 rounded-full bg-blue-300/25 blur-3xl" />
      </div>

      <div className="relative flex min-h-screen items-center justify-center p-4">
        <div className="w-full max-w-md">

          {/* Header */}
          <div className="mb-6 text-center">

            <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-linear-to-r from-emerald-500 via-teal-500 to-cyan-500 text-white text-3xl shadow-[0_18px_40px_rgba(16,185,129,0.35)]">
              🏠
            </div>

            <div className="mt-4 inline-flex items-center gap-2 rounded-full border border-emerald-100 bg-white/80 px-3 py-1 text-xs font-semibold text-emerald-700 shadow-sm backdrop-blur-sm">
              Tenant Portal
            </div>

            <div className="mt-3 text-3xl font-bold tracking-tight text-slate-800">
              เข้าสู่ระบบผู้เช่า
            </div>

            <div className="mt-2 text-sm text-slate-500">
              ดูใบแจ้งหนี้ อัปโหลดสลิป และแจ้งซ่อม
            </div>
          </div>

          {/* Card */}
          <div className="rounded-4xl border border-white/70 bg-white/85 p-6 shadow-[0_20px_50px_rgba(15,23,42,0.12)] backdrop-blur-xl">

            {err && (
              <div className="mb-4 rounded-2xl border border-rose-100 bg-rose-50 px-4 py-3 text-sm text-rose-700 shadow-sm">
                {err}
              </div>
            )}

            <form onSubmit={onSubmit} className="space-y-4">

              <div>
                <label className="text-sm font-medium text-slate-700">
                  อีเมล
                </label>

                <input
                  className="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-800 outline-none transition focus:border-emerald-300 focus:ring-4 focus:ring-emerald-100"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder="tenant@email.com"
                  autoComplete="username"
                />
              </div>

              <div>
                <label className="text-sm font-medium text-slate-700">
                  รหัสผ่าน
                </label>

                <input
                  className="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-800 outline-none transition focus:border-emerald-300 focus:ring-4 focus:ring-emerald-100"
                  type="password"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder="••••••••"
                  autoComplete="current-password"
                />
              </div>

              <button
                type="submit"
                disabled={!canSubmit}
                className="mt-2 w-full rounded-2xl bg-linear-to-r from-emerald-500 via-teal-500 to-cyan-500 py-3 font-semibold text-white shadow-[0_14px_30px_rgba(16,185,129,0.35)] transition hover:-translate-y-0.5 hover:shadow-[0_20px_40px_rgba(16,185,129,0.4)] disabled:opacity-50"
              >
                {loading ? "กำลังเข้าสู่ระบบ..." : "เข้าสู่ระบบ"}
              </button>

            </form>

            {/* switch login */}
            <div className="mt-5 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
              เข้าหน้า Admin?{" "}
              <Link
                to="/admin/login"
                className="font-semibold text-indigo-600 hover:text-indigo-700 hover:underline"
              >
                Admin Login
              </Link>
            </div>
          </div>

          <div className="mt-5 text-center text-xs text-slate-400">
            © Dorm Service • Tenant Portal
          </div>

        </div>
      </div>
    </div>
  );
}