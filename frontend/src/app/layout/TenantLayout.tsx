import { NavLink, Outlet, useLocation, useNavigate } from "react-router-dom";
import { useEffect, useState } from "react";

const nav = [
  { to: "/tenant/dashboard", label: "Dashboard", icon: "🏠" },
  { to: "/tenant/parcels", label: "พัสดุ", icon: "📦" },
  { to: "/tenant/invoices", label: "ใบแจ้งหนี้", icon: "🧾" },
  { to: "/tenant/payments/upload", label: "อัปโหลดสลิป", icon: "💳" },
  { to: "/tenant/repairs", label: "แจ้งซ่อม", icon: "🛠️" },
  { to: "/tenant/announcements", label: "ข่าวสาร", icon: "📢" },
  { to: "/tenant/profile", label: "โปรไฟล์", icon: "👤" },
];

export default function TenantLayout() {
  const navigate = useNavigate();
  const location = useLocation();
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  const onLogout = () => {
    localStorage.removeItem("token");
    localStorage.removeItem("role");
    setMobileMenuOpen(false);
    navigate("/tenant/login", { replace: true });
  };

  useEffect(() => {
    setMobileMenuOpen(false);
  }, [location.pathname]);

  useEffect(() => {
    if (!mobileMenuOpen) return;

    const original = document.body.style.overflow;
    document.body.style.overflow = "hidden";

    return () => {
      document.body.style.overflow = original;
    };
  }, [mobileMenuOpen]);

  const activeItem =
    nav.find((x) => location.pathname.startsWith(x.to)) ?? nav[0];

  return (
    <div className="min-h-screen bg-linear-to-b from-slate-100 via-slate-200/60 to-slate-200">
      <header className="sticky top-0 z-50 border-b border-white/60 bg-white/70 backdrop-blur-xl shadow-[0_14px_34px_rgba(15,23,42,0.08)]">
        {/* Desktop / tablet topbar */}
        <div className="mx-auto hidden max-w-6xl items-center justify-between px-4 py-3 md:flex md:px-6">
          <div className="flex items-center gap-3">
            <div className="flex h-10 w-10 items-center justify-center rounded-2xl bg-linear-to-r from-indigo-500 via-blue-500 to-cyan-500 text-white shadow-[0_8px_20px_rgba(59,130,246,0.28)]">
              🏢
            </div>

            <div>
              <div className="font-semibold tracking-tight text-slate-800">
                ผู้เข้าพัก
              </div>
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

        {/* Mobile topbar */}
        <div className="flex items-center justify-between px-4 py-3 md:hidden">
          <div className="flex min-w-0 items-center gap-3">
            <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-linear-to-r from-indigo-500 via-blue-500 to-cyan-500 text-white shadow-[0_8px_20px_rgba(59,130,246,0.28)]">
              🏢
            </div>

            <div className="min-w-0">
              <div className="truncate text-base font-bold tracking-tight text-slate-800">
                ผู้เข้าพัก
              </div>
              <div className="truncate text-[11px] text-slate-500">
                {activeItem.label}
              </div>
            </div>
          </div>

          <button
            type="button"
            aria-label="เปิดเมนู"
            aria-expanded={mobileMenuOpen}
            onClick={() => setMobileMenuOpen(true)}
            className="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white text-slate-800 shadow-[0_8px_20px_rgba(15,23,42,0.08)] ring-1 ring-slate-200 transition active:scale-95"
          >
            ☰
          </button>
        </div>

        {/* Desktop / tablet nav */}
        <nav className="hidden md:block">
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
      </header>

      <main className="mx-auto max-w-6xl px-3 py-4 md:px-6 md:py-6">
        <div className="rounded-[28px]">
          <Outlet />
        </div>
      </main>

      {/* Mobile bottom sheet */}
      {mobileMenuOpen && (
        <div className="md:hidden">
          <button
            type="button"
            aria-label="ปิดเมนู"
            onClick={() => setMobileMenuOpen(false)}
            className="fixed inset-0 z-60 bg-slate-900/35 backdrop-blur-[2px]"
          />

          <div className="fixed inset-x-0 bottom-0 z-61 rounded-t-[28px] border-t border-white/60 bg-white/95 px-4 pb-5 pt-3 shadow-[0_-18px_50px_rgba(15,23,42,0.16)] backdrop-blur-xl">
            <div className="mx-auto mb-4 h-1.5 w-14 rounded-full bg-slate-300" />

            <div className="mb-4 flex items-center justify-between">
              <div>
                <div className="text-lg font-bold tracking-tight text-slate-800">
                  เมนูผู้เช่า
                </div>
                <div className="text-xs text-slate-500">
                  เลือกหน้าที่ต้องการใช้งาน
                </div>
              </div>

              <button
                type="button"
                onClick={() => setMobileMenuOpen(false)}
                className="flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-100 text-slate-700 transition active:scale-95"
              >
                ✕
              </button>
            </div>

            <div className="grid grid-cols-2 gap-3">
              {nav.map((x) => {
                const isActive = location.pathname.startsWith(x.to);

                return (
                  <NavLink
                    key={x.to}
                    to={x.to}
                    className={[
                      "rounded-[22px] border p-4 transition-all",
                      isActive
                        ? "border-indigo-200 bg-indigo-50 text-indigo-700 shadow-[0_10px_22px_rgba(99,102,241,0.10)]"
                        : "border-slate-200 bg-white text-slate-700 shadow-[0_8px_18px_rgba(15,23,42,0.05)]",
                    ].join(" ")}
                  >
                    <div className="text-2xl">{x.icon}</div>
                    <div className="mt-2 text-sm font-semibold">{x.label}</div>
                  </NavLink>
                );
              })}
            </div>

            <div className="mt-4">
              <button
                onClick={onLogout}
                className="flex w-full items-center justify-center gap-2 rounded-[22px] bg-slate-900 px-4 py-3.5 font-medium text-white shadow-[0_10px_24px_rgba(15,23,42,0.16)] transition active:scale-[0.99]"
              >
                <span>↪</span>
                <span>ออกจากระบบ</span>
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}