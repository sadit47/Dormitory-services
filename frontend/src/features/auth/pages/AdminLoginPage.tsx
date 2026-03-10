import { useMemo, useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { authApi } from "../services/authApi";
import { storage } from "../../../shared/utils/storage";

export default function AdminLoginPage() {
  const nav = useNavigate();

  const [email, setEmail] = useState("admin@dorm.test");
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
      const res = await authApi.login(email, password, "admin-web");
      const role = res.user.role;

      if (role !== "admin") {
        storage.clearToken();
        storage.clearRole();
        setErr("บัญชีนี้ไม่ใช่ผู้ดูแลระบบ (Admin)");
        return;
      }

      storage.setToken(res.token);
      storage.setRole(role);
      nav("/admin/dashboard", { replace: true });
    } catch (e: any) {
      setErr(e?.response?.data?.message ?? e?.message ?? "Login failed");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="relative min-h-screen overflow-hidden bg-linear-to-br from-slate-100 via-indigo-50 to-cyan-50">
      {/* background glow */}
      <div className="absolute inset-0 overflow-hidden">
        <div className="absolute -left-20 top-10 h-72 w-72 rounded-full bg-indigo-300/25 blur-3xl" />
        <div className="absolute right-0 top-0 h-80 w-80 rounded-full bg-sky-300/20 blur-3xl" />
        <div className="absolute bottom-0 left-1/3 h-72 w-72 rounded-full bg-cyan-300/20 blur-3xl" />
        <div className="absolute bottom-10 right-10 h-64 w-64 rounded-full bg-violet-300/15 blur-3xl" />
      </div>

      <div className="relative flex min-h-screen items-center justify-center p-4">
        <div className="w-full max-w-md">
          {/* Header */}
          <div className="mb-5 text-center">
            <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-linear-to-r from-indigo-600 via-blue-600 to-cyan-500 text-3xl text-white shadow-[0_18px_40px_rgba(59,130,246,0.28)]">
              🔐
            </div>

            <div className="mt-4 inline-flex items-center gap-2 rounded-full border border-indigo-100 bg-white/80 px-3 py-1 text-xs font-semibold text-indigo-700 shadow-[0_8px_18px_rgba(15,23,42,0.04)] backdrop-blur-sm">
              Admin Portal
            </div>

            <div className="mt-3 text-3xl font-bold tracking-tight text-slate-800">
              เข้าสู่ระบบผู้ดูแล
            </div>
            <div className="mt-2 text-sm leading-6 text-slate-500">
              จัดการห้อง ผู้เช่า ใบแจ้งหนี้ แจ้งซ่อม และข้อมูลสำคัญของระบบหอพัก
            </div>
          </div>

          {/* Card */}
          <div className="rounded-4xl border border-white/70 bg-white/85 p-6 shadow-[0_20px_50px_rgba(15,23,42,0.10)] backdrop-blur-xl">
            {err && (
              <div className="mb-4 rounded-2xl border border-rose-100 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 shadow-[0_8px_18px_rgba(244,63,94,0.05)]">
                {err}
              </div>
            )}

            <form onSubmit={onSubmit} className="space-y-4">
              <div>
                <label className="text-sm font-medium text-slate-700">อีเมล</label>
                <input
                  className="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-800 outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder="admin@email.com"
                  autoComplete="username"
                />
              </div>

              <div>
                <label className="text-sm font-medium text-slate-700">รหัสผ่าน</label>
                <input
                  className="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-800 outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
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
                className="mt-2 w-full rounded-2xl bg-linear-to-r from-slate-900 via-indigo-700 to-blue-600 py-3 font-semibold text-white shadow-[0_14px_28px_rgba(59,130,246,0.20)] transition hover:-translate-y-0.5 hover:shadow-[0_18px_34px_rgba(59,130,246,0.26)] disabled:cursor-not-allowed disabled:opacity-50"
              >
                {loading ? "กำลังเข้าสู่ระบบ..." : "เข้าสู่ระบบ"}
              </button>
            </form>

            <div className="mt-5 rounded-2xl border border-slate-200/80 bg-slate-50/80 px-4 py-3 text-sm text-slate-600">
              เข้าหน้า Tenant?{" "}
              <Link
                to="/tenant/login"
                className="font-semibold text-indigo-600 transition hover:text-indigo-700 hover:underline"
              >
                Tenant Login
              </Link>
            </div>
          </div>

          <div className="mt-5 text-center text-xs text-slate-400">
            © Dorm Service • Admin Portal
          </div>
        </div>
      </div>
    </div>
  );
}