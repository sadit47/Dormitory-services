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

export default function TenantRoutes() {
  return (
    <Routes>
      <Route element={<TenantLayout />}>
        <Route path="dashboard" element={<TenantDashboardPage />} />
        <Route path="invoices" element={<TenantInvoicesPage />} />
        <Route path="invoices/:id" element={<TenantInvoiceDetailPage />} />
        <Route path="payments/upload" element={<TenantUploadSlipPage />} />
        <Route path="repairs" element={<TenantRepairsPage />} />
        <Route path="profile" element={<TenantProfilePage />} />
        <Route path="announcements" element={<TenantAnnouncementsPage />} />
        <Route path="parcels" element={<TenantParcelsPage />} />
      </Route>

      {/* เข้า /tenant แล้วเด้งไป dashboard */}
      <Route path="" element={<Navigate to="dashboard" replace />} />
    </Routes>
  );
}