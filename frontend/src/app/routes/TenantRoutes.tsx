import { Routes, Route, Navigate } from "react-router-dom";
import TenantLayout from "@/app/layout/TenantLayout";
import TenantDashboardPage from "@/features/tenant/dashboard/TenantDashboardPage";
import TenantInvoicesPage from "@/features/tenant/invoices/TenantInvoicesPage";
import TenantInvoiceDetailPage from "@/features/tenant/invoices/TenantInvoiceDetailPage";
import TenantUploadSlipPage from "@/features/tenant/payments/TenantUploadSlipPage";
import TenantRepairsPage from "@/features/tenant/repairs/TenantRepairsPage";
import TenantProfilePage from "@/features/tenant/profile/TenantProfilePage";
import TenantAnnouncementsPage from "@/features/tenant/announcements/TenantAnnouncementsPage";
import TenantParcelsPage from "@/features/tenant/parcels/TenantParcelsPage";
import TenantLoginPage from "@/features/auth/pages/TenantLoginPage";
import ProtectedRoute from "../guards/ProtectedRoute";
import RoleRoute from "../guards/RoleRoute";

export default function TenantRoutes() {
  return (
    <Routes>
      <Route path="login" element={<TenantLoginPage />} />

      <Route
        element={
          <ProtectedRoute>
            <RoleRoute role="tenant">
              <TenantLayout />
            </RoleRoute>
          </ProtectedRoute>
        }
      >
        <Route index element={<Navigate to="dashboard" replace />} />
        <Route path="dashboard" element={<TenantDashboardPage />} />
        <Route path="invoices" element={<TenantInvoicesPage />} />
        <Route path="invoices/:id" element={<TenantInvoiceDetailPage />} />
        <Route path="payments/upload" element={<TenantUploadSlipPage />} />
        <Route path="repairs" element={<TenantRepairsPage />} />
        <Route path="profile" element={<TenantProfilePage />} />
        <Route path="announcements" element={<TenantAnnouncementsPage />} />
        <Route path="parcels" element={<TenantParcelsPage />} />
      </Route>

      <Route path="*" element={<Navigate to="login" replace />} />
    </Routes>
  );
}