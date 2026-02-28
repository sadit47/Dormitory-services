import { Routes, Route, Navigate } from "react-router-dom";
import AdminLoginPage from "@/features/auth/pages/AdminLoginPage";
import TenantLoginPage from "@/features/auth/pages/TenantLoginPage";
import TenantRoutes from "@/app/routes/TenantRoutes";
import AdminRoutes from "@/app/routes/AdminRoutes";

export default function App() {
  return (
    <Routes>
      {/* Auth */}
      <Route path="/admin/login" element={<AdminLoginPage />} />
      <Route path="/tenant/login" element={<TenantLoginPage />} />

      {/* Admin */}
      <Route path="/admin/*" element={<AdminRoutes />} />

      {/* Tenant */}
      <Route path="/tenant/*" element={<TenantRoutes />} />

      {/* fallback */}
      <Route path="*" element={<Navigate to="/admin/login" replace />} />
    </Routes>
  );
}