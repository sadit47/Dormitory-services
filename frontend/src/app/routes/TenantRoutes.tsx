import { Routes, Route, Navigate } from "react-router-dom";
import TenantLayout from "@/app/layout/TenantLayout";
import TenantDashboardPage from "@/features/tenant/dashboard/TenantDashboardPage";
import TenantInvoicesPage from "@/features/tenant/invoices/TenantInvoicesPage";
import TenantInvoiceDetailPage from "@/features/tenant/invoices/TenantInvoiceDetailPage";
import TenantUploadSlipPage from "@/features/tenant/payments/TenantUploadSlipPage";
import TenantRepairsPage from "@/features/tenant/repairs/TenantRepairsPage";
import TenantProfilePage from "@/features/tenant/profile/TenantProfilePage";

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
      </Route>

      {/* เข้า /tenant แล้วเด้งไป dashboard */}
      <Route path="" element={<Navigate to="dashboard" replace />} />
    </Routes>
  );
}