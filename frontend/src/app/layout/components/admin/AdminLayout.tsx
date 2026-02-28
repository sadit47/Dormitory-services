import { NavLink, Outlet } from "react-router-dom";

type NavItem = {
  label: string;
  to: string;
};

const NAV: NavItem[] = [
  { label: "Dashboard", to: "/admin" },
  { label: "ผู้เช่า", to: "/admin/tenants" },
  { label: "ห้องพัก", to: "/admin/rooms" },
  { label: "ใบแจ้งหนี้", to: "/admin/invoices" },
  { label: "ตรวจสอบการชำระเงิน", to: "/admin/payments" },
  { label: "โปรไฟล์", to: "/admin/profile" },
];

function cx(...c: (string | false | null | undefined)[]) {
  return c.filter(Boolean).join(" ");
}

export default function AdminLayout() {
  return (
    <div className="min-h-screen bg-slate-50">
      <div className="mx-auto max-w-350 px-4 py-4">
        <div className="grid grid-cols-1 gap-4 lg:grid-cols-[280px_1fr]">
          {/* Sidebar */}
          <aside className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div className="mb-4">
              <div className="text-xl font-black tracking-tight text-slate-900">Admin</div>
              <div className="text-xs font-semibold text-slate-500">
                dorm-service • management
              </div>
            </div>

            <nav className="space-y-1">
              {NAV.map((item) => (
                <NavLink
                  key={item.to}
                  to={item.to}
                  end={item.to === "/admin"}
                  className={({ isActive }) =>
                    cx(
                      "flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-extrabold transition",
                      "focus:outline-none focus:ring-4 focus:ring-blue-100",
                      isActive
                        ? "bg-blue-50 text-blue-700 ring-1 ring-blue-200"
                        : "text-slate-700 hover:bg-slate-50 hover:text-slate-900"
                    )
                  }
                >
                  <span
                    className={cx(
                      "h-2.5 w-2.5 rounded-full",
                      item.to === "/admin"
                        ? "bg-indigo-500"
                        : item.to.includes("tenants")
                        ? "bg-emerald-500"
                        : "bg-sky-500"
                    )}
                  />
                  {item.label}
                </NavLink>
              ))}
            </nav>

            <div className="mt-5 border-t border-slate-200 pt-4">
              <button
                className={cx(
                  "w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-extrabold text-slate-900 shadow-sm",
                  "hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-blue-100"
                )}
                onClick={() => {
                  // TODO: hook logout
                  // e.g. authApi.logout(); navigate("/login");
                  alert("TODO: logout");
                }}
              >
                Logout
              </button>
            </div>
          </aside>

          {/* Content */}
          <main className="min-w-0 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <Outlet />
          </main>
        </div>
      </div>
    </div>
  );
}
