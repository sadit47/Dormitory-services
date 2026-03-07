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
    <div className="min-h-screen bg-linear-to-b from-slate-100 via-slate-200/60 to-slate-200">
      {/* Topbar */}
      <header className="sticky top-0 z-50 border-b border-white/60 bg-white/75 backdrop-blur-xl shadow-[0_18px_40px_rgba(15,23,42,0.08)]">
        <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-3 md:px-6">
          <div className="flex items-center gap-3">
            <div className="flex h-10 w-10 items-center justify-center rounded-2xl bg-linear-to-r from-indigo-500 via-blue-500 to-cyan-500 text-white shadow-[0_8px_20px_rgba(59,130,246,0.28)]">
              🏢
            </div>

            <div>
              <div className="font-semibold tracking-tight text-slate-800">Tenant</div>
              <div className="text-xs text-slate-500">พื้นที่ผู้เช่า</div>
            </div>
          </div>

          <button
            onClick={onLogout}
            className="rounded-2xl bg-slate-900 px-4 py-2 text-sm font-medium text-white shadow-[0_8px_20px_rgba(15,23,42,0.14)] transition hover:-translate-y-0.5 hover:shadow-[0_12px_26px_rgba(15,23,42,0.18)]"
          >
            ออกจากระบบ
          </button>
        </div>

        {/* Tabs desktop / tablet */}
        <nav className="hidden sm:block">
          <div className="mx-auto max-w-6xl px-4 pb-3 md:px-6">
            <div className="flex flex-wrap gap-2">
              {nav.map((x) => (
                <NavLink
                  key={x.to}
                  to={x.to}
                  className={({ isActive }) =>
                    [
                      "whitespace-nowrap rounded-full px-4 py-2 text-sm transition-all duration-200",
                      "shadow-[0_6px_18px_rgba(15,23,42,0.05)]",
                      isActive
                        ? "bg-slate-900 text-white"
                        : "bg-white/85 text-slate-600 hover:bg-white hover:text-slate-900",
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

        {/* Bottom Nav mobile */}
        <nav className="fixed bottom-3 left-3 right-3 z-50 sm:hidden">
          <div className="grid grid-cols-5 rounded-3xl border border-white/70 bg-white/88 p-2 backdrop-blur-xl shadow-[0_18px_40px_rgba(15,23,42,0.12)]">
            {nav.map((x) => (
              <NavLink
                key={x.to}
                to={x.to}
                className={({ isActive }) =>
                  [
                    "flex flex-col items-center gap-1 rounded-2xl py-2 text-[11px] transition-all",
                    isActive
                      ? "bg-indigo-50 text-indigo-600 shadow-[0_6px_14px_rgba(99,102,241,0.15)] font-semibold"
                      : "text-slate-500 hover:bg-slate-50",
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
      <main className="mx-auto max-w-6xl px-4 py-4 pb-24 md:px-6 md:py-6 sm:pb-6">
        <div className="rounded-[28px]">
          <Outlet />
        </div>
      </main>
    </div>
  );
}