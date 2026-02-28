import { Routes, Route, Navigate } from "react-router-dom";
import AdminLayout from "../layout/AdminLayout";
import AdminLoginPage from "../../features/auth/pages/AdminLoginPage";
import AdminDashboardPage from "../../features/admin/dashboard/AdminDashboardPage";
import AdminTenantsPage from "../../features/admin/tenants/AdminTenantsPage";
import AdminRoomsPage from "../../features/admin/rooms/AdminRoomsPage";
import AdminInvoicesPage from "../../features/admin/invoices/AdminInvoicesPage";
import AdminRepairsPage from "../../features/admin/repairs/AdminRepairsPage";
import AdminPaymentReviewPage from "../../features/admin/payments/AdminPaymentReviewPage";
import AdminProfilePage from "../../features/admin/profile/AdminProfilePage";
import ProtectedRoute from "../guards/ProtectedRoute";
import RoleRoute from "../guards/RoleRoute";

export default function AdminRoutes() {
  return (
    <Routes>
      <Route path="login" element={<AdminLoginPage />} />

      <Route
        element={
          <ProtectedRoute>
            <RoleRoute role="admin">
              <AdminLayout />
            </RoleRoute>
          </ProtectedRoute>
        }
      >
        <Route index element={<Navigate to="dashboard" replace />} />
        <Route path="dashboard" element={<AdminDashboardPage />} />
        <Route path="tenants" element={<AdminTenantsPage />} />
        <Route path="rooms" element={<AdminRoomsPage />} />
        <Route path="invoices" element={<AdminInvoicesPage />} />
        <Route path="repairs" element={<AdminRepairsPage />} />
        <Route path="payments" element={<AdminPaymentReviewPage />} />
        <Route path="profile" element={<AdminProfilePage />} />
      </Route>

      <Route path="*" element={<Navigate to="login" replace />} />
    </Routes>
  );
}
