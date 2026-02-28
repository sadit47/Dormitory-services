import { useEffect, useState } from "react";
import { NavLink, Outlet, useLocation, useNavigate } from "react-router-dom";
import { authApi } from "../../features/auth/services/authApi";

type NavItem = { label: string; to: string };

const NAV: NavItem[] = [
  { label: "Dashboard", to: "/admin/dashboard" },
  { label: "ผู้เช่า", to: "/admin/tenants" },
  { label: "ห้องพัก", to: "/admin/rooms" },
  { label: "ใบแจ้งหนี้", to: "/admin/invoices" },
  { label: "แจ้งซ่อม", to: "/admin/repairs" },
  { label: "ตรวจสอบชำระเงิน", to: "/admin/payments" },
  { label: "โปรไฟล์", to: "/admin/profile" },
];

function cx(...c: (string | false | null | undefined)[]) {
  return c.filter(Boolean).join(" ");
}

function Sidebar({
  locationPathname,
  onNavigateDone,
  onLogout,
}: {
  locationPathname: string;
  onNavigateDone?: () => void;
  onLogout: () => Promise<void> | void;
}) {
  return (
    <div className="h-full rounded-2xl bg-blue-600 p-4 shadow-lg">
      {/* Brand */}
      <div className="mb-6">
        <div className="text-xl font-black tracking-tight text-white">Admin</div>
        <div className="mt-0.5 text-xs font-semibold text-blue-100">
          ระบบจัดการหอพัก
        </div>
      </div>

      {/* Menu */}
      <nav className="space-y-1">
        {NAV.map((item) => (
          <NavLink
            key={item.to}
            to={item.to}
            onClick={() => onNavigateDone?.()}
            className={({ isActive }) =>
              cx(
                "flex items-center gap-3 rounded-xl px-3 py-2",
                "text-sm font-extrabold transition",
                "focus:outline-none focus:ring-4 focus:ring-white/30",
                isActive
                  ? "bg-white text-blue-700"
                  : "text-blue-100 hover:bg-white/10 hover:text-white"
              )
            }
          >
            <span
              className={cx(
                "h-2.5 w-2.5 rounded-full",
                locationPathname === item.to ? "bg-blue-600" : "bg-blue-200"
              )}
            />
            {item.label}
          </NavLink>
        ))}
      </nav>

      {/* Current page */}
      <div className="mt-6 rounded-xl bg-white/10 p-3">
        <div className="text-xs font-bold text-blue-100">คุณอยู่หน้า</div>
        <div className="mt-1 text-sm font-black text-white">
          {locationPathname}
        </div>
      </div>

      {/* Logout */}
      <div className="mt-6">
        <button
          className={cx(
            "w-full rounded-xl bg-white px-4 py-2",
            "text-sm font-extrabold text-blue-700 shadow-sm",
            "hover:bg-blue-50 focus:outline-none focus:ring-4 focus:ring-white/40"
          )}
          onClick={onLogout}
        >
          Logout
        </button>
      </div>
    </div>
  );
}

export default function AdminLayout() {
  const location = useLocation();
  const navigate = useNavigate();

  // mobile drawer open state
  const [open, setOpen] = useState(false);

  // ปิด drawer ทุกครั้งเมื่อ route เปลี่ยน (กันค้าง)
  useEffect(() => {
    setOpen(false);
  }, [location.pathname]);

  // กัน scroll หน้าเว็บตอน drawer เปิด (มือถือ)
  useEffect(() => {
    if (open) document.body.style.overflow = "hidden";
    else document.body.style.overflow = "";
    return () => {
      document.body.style.overflow = "";
    };
  }, [open]);

  const handleLogout = async () => {
    try {
      await authApi.logout();
    } catch (e) {
      console.error(e);
    } finally {
      localStorage.removeItem("token");
      localStorage.removeItem("auth_token");
      sessionStorage.removeItem("token");
      navigate("/admin/login", { replace: true });
    }
  };

  return (
    <div className="min-h-screen bg-slate-50">
      <div className="mx-auto max-w-container px-4 py-4">
        {/* Topbar (mobile only) */}
        <div className="mb-4 flex items-center justify-between lg:hidden">
          <button
            type="button"
            onClick={() => setOpen(true)}
            className={cx(
              "inline-flex items-center justify-center rounded-xl",
              "border border-slate-200 bg-white px-3 py-2",
              "text-sm font-extrabold text-slate-900 shadow-sm",
              "hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-blue-100"
            )}
            aria-label="Open menu"
          >
            {/* hamburger icon */}
            <span className="mr-2 inline-block h-4 w-4">
              <svg viewBox="0 0 24 24" fill="none" className="h-4 w-4">
                <path
                  d="M4 6h16M4 12h16M4 18h16"
                  stroke="currentColor"
                  strokeWidth="2"
                  strokeLinecap="round"
                />
              </svg>
            </span>
            เมนู
          </button>

          <div className="text-right">
            <div className="text-xs font-extrabold text-slate-500">Admin</div>
            <div className="text-sm font-black text-slate-900">
              {location.pathname}
            </div>
          </div>
        </div>

        <div className="grid grid-cols-1 gap-4 lg:grid-cols-[280px_1fr]">
          {/* Desktop Sidebar */}
          <aside className="hidden lg:block">
            <Sidebar
              locationPathname={location.pathname}
              onLogout={handleLogout}
            />
          </aside>

          {/* Content */}
          <main className="min-w-0">
            <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
              <Outlet />
            </div>
          </main>
        </div>
      </div>

      {/* Mobile Drawer */}
      {open && (
        <>
          {/* overlay */}
          <div
            className="fixed inset-0 z-40 bg-slate-900/40 backdrop-blur-[2px] lg:hidden"
            onClick={() => setOpen(false)}
          />

          {/* drawer panel */}
          <div className="fixed inset-y-0 left-0 z-50 w-[320px] max-w-[85vw] p-3 lg:hidden">
            <div className="relative h-full">
              {/* close button */}
              <button
                type="button"
                onClick={() => setOpen(false)}
                className={cx(
                  "absolute right-2 top-2 z-10",
                  "rounded-xl bg-white/10 px-3 py-2 text-sm font-extrabold text-white",
                  "hover:bg-white/20 focus:outline-none focus:ring-4 focus:ring-white/30"
                )}
                aria-label="Close menu"
              >
                ปิด
              </button>

              <Sidebar
                locationPathname={location.pathname}
                onNavigateDone={() => setOpen(false)}
                onLogout={handleLogout}
              />
            </div>
          </div>
        </>
      )}
    </div>
  );
}
