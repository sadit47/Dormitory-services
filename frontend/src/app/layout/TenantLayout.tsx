import { NavLink, Outlet, useNavigate } from "react-router-dom";

const nav = [
  { to: "/tenant/dashboard", label: "Dashboard", icon: "🏠" },
  { to: "/tenant/invoices", label: "ใบแจ้งหนี้", icon: "🧾" },
  { to: "/tenant/payments/upload", label: "อัปโหลดสลิป", icon: "💳" },
  { to: "/tenant/repairs", label: "แจ้งซ่อม", icon: "🛠️" },
  { to: "/tenant/profile", label: "โปรไฟล์", icon: "👤" },
];

export default function TenantLayout() {
  const navigate = useNavigate();

  const onLogout = () => {
    localStorage.removeItem("token");
    localStorage.removeItem("role");
    navigate("/tenant/login", { replace: true });
  };

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Topbar */}
      <header className="sticky top-0 z-50 bg-white/85 backdrop-blur border-b">
        <div className="max-w-6xl mx-auto px-3 md:px-6 py-3 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="w-9 h-9 rounded-xl bg-linear-to-r from-indigo-600 via-blue-600 to-sky-600 text-white flex items-center justify-center shadow-sm">
              🏢
            </div>
            <div>
              <div className="font-semibold text-gray-900 leading-tight">Tenant</div>
              <div className="text-xs text-gray-500 leading-tight">พื้นที่ผู้เช่า</div>
            </div>
          </div>

          <button
            onClick={onLogout}
            className="text-sm px-3 py-2 rounded-xl bg-gray-900 text-white hover:opacity-95 active:opacity-90"
          >
            ออกจากระบบ
          </button>
        </div>

        {/* Tabs (desktop + tablet) */}
        <nav className="hidden sm:block">
          <div className="max-w-6xl mx-auto px-3 md:px-6 pb-3">
            <div className="flex gap-2 flex-wrap">
              {nav.map((x) => (
                <NavLink
                  key={x.to}
                  to={x.to}
                  className={({ isActive }) =>
                    [
                      "text-sm px-3 py-2 rounded-full border whitespace-nowrap transition",
                      "hover:bg-gray-50",
                      isActive
                        ? "bg-gray-900 text-white border-gray-900"
                        : "bg-white text-gray-700 border-gray-200",
                    ].join(" ")
                  }
                >
                  <span className="mr-2">{x.icon}</span>
                  {x.label}
                </NavLink>
              ))}
            </div>
          </div>
        </nav>

        {/* Bottom Nav (mobile) */}
        <nav className="sm:hidden fixed bottom-0 left-0 right-0 bg-white/90 backdrop-blur border-t z-50">
          <div className="max-w-6xl mx-auto grid grid-cols-5">
            {nav.map((x) => (
              <NavLink
                key={x.to}
                to={x.to}
                className={({ isActive }) =>
                  [
                    "py-2 flex flex-col items-center gap-1 text-[11px] transition",
                    isActive ? "text-indigo-600 font-semibold" : "text-gray-600",
                  ].join(" ")
                }
              >
                <div className="text-lg leading-none">{x.icon}</div>
                <div className="leading-none">{x.label}</div>
              </NavLink>
            ))}
          </div>
        </nav>
      </header>

      {/* Content */}
      <main className="p-3 md:p-6 max-w-6xl mx-auto pb-20 sm:pb-6">
        <Outlet />
      </main>
    </div>
  );
}