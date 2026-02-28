import { NavLink, useNavigate } from "react-router-dom";
import { authApi } from "../../../features/auth/services/authApi";
import { storage } from "../../../shared/utils/storage";

const linkStyle = ({ isActive }: any) => ({
  display: "block",
  padding: "10px 12px",
  textDecoration: "none",
  borderRadius: 10,
  background: isActive ? "#eef2ff" : "transparent",
  color: "#111827",
  fontWeight: 600,
});

export default function Sidebar() {
  const nav = useNavigate();

  const logout = async () => {
    try { await authApi.logout(); } catch {}
    storage.clearToken();
    storage.clearRole();
    nav("/admin/login");
  };

  return (
    <aside style={{ width: 260, padding: 16, background: "#fff", borderRight: "1px solid #e5e7eb" }}>
      <div style={{ fontSize: 18, fontWeight: 800, marginBottom: 14 }}>Admin</div>

      <nav style={{ display: "grid", gap: 8 }}>
        <NavLink to="/admin/dashboard" style={linkStyle}>Dashboard</NavLink>
        <NavLink to="/admin/tenants" style={linkStyle}>ผู้เช่า</NavLink>
        <NavLink to="/admin/rooms" style={linkStyle}>ห้องพัก</NavLink>
        <NavLink to="/admin/invoices" style={linkStyle}>ใบแจ้งหนี้</NavLink>
        <NavLink to="/admin/repairs" style={linkStyle}>แจ้งซ่อม</NavLink>
        <NavLink to="/admin/payments" style={linkStyle}>ตรวจสอบการชำระเงิน</NavLink>
        <NavLink to="/admin/profile" style={linkStyle}>โปรไฟล์</NavLink>
      </nav>

      <div style={{ marginTop: 18 }}>
        <button
          onClick={logout}
          style={{
            width: "100%",
            padding: "10px 12px",
            borderRadius: 10,
            border: "1px solid #e5e7eb",
            background: "#fff",
            cursor: "pointer",
            fontWeight: 700,
          }}
        >
          Logout
        </button>
      </div>
    </aside>
  );
}
