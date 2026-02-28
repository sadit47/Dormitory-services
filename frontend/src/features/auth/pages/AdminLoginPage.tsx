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
    return String(email).trim().length > 0 && String(password).trim().length > 0 && !loading;
  }, [email, password, loading]);

  const onSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!canSubmit) return;

    setErr(null);
    setLoading(true);

    try {
      // ✅ authApi.login() คืน { token, user }
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
    <div className="min-h-screen bg-gray-50 flex items-center justify-center p-4">
      <div className="w-full max-w-md">
        {/* Header */}
        <div className="mb-4">
          <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-50 text-indigo-700 text-xs font-semibold border border-indigo-100">
            🔐 Admin
          </div>
          <div className="mt-2 text-2xl font-semibold text-gray-900">เข้าสู่ระบบผู้ดูแล</div>
          <div className="text-sm text-gray-500 mt-1">จัดการห้อง ผู้เช่า ใบแจ้งหนี้ และรายการแจ้งซ่อม</div>
        </div>

        {/* Card */}
        <div className="bg-white border rounded-2xl shadow-sm p-5">
          {err && (
            <div className="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-700 text-sm font-medium">
              {err}
            </div>
          )}

          <form onSubmit={onSubmit} className="space-y-3">
            <div>
              <label className="text-sm font-medium text-gray-700">อีเมล</label>
              <input
                className="mt-1 w-full rounded-xl border px-3 py-2 bg-white outline-none focus:ring-2 focus:ring-indigo-200"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder="admin@email.com"
                autoComplete="username"
              />
            </div>

            <div>
              <label className="text-sm font-medium text-gray-700">รหัสผ่าน</label>
              <input
                className="mt-1 w-full rounded-xl border px-3 py-2 bg-white outline-none focus:ring-2 focus:ring-indigo-200"
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
              className="w-full mt-2 rounded-xl bg-gray-900 text-white font-semibold py-2.5 disabled:opacity-50"
            >
              {loading ? "กำลังเข้าสู่ระบบ..." : "Sign in"}
            </button>
          </form>

          <div className="mt-4 text-sm text-gray-600">
            เข้าหน้า Tenant?{" "}
            <Link to="/tenant/login" className="font-semibold text-indigo-600 hover:underline">
              Tenant Login
            </Link>
          </div>
        </div>

        <div className="mt-4 text-xs text-gray-400 text-center">© Dorm Service • Admin Portal</div>
      </div>
    </div>
  );
}