import React, { useEffect, useState } from "react";
import { Navigate, useLocation } from "react-router-dom";
import { authApi } from "../../features/auth/services/authApi";
import { storage } from "../../shared/utils/storage";

type Props = { children: React.ReactNode };

export default function ProtectedRoute({ children }: Props) {
  const [loading, setLoading] = useState(true);
  const [ok, setOk] = useState(false);
  const loc = useLocation();

  useEffect(() => {
    const token = storage.getToken();
    if (!token) {
      setOk(false);
      setLoading(false);
      return;
    }

    authApi
      .me()
      .then(() => setOk(true))
      .catch(() => {
        storage.clearToken();
        storage.clearRole();
        setOk(false);
      })
      .finally(() => setLoading(false));
  }, []);

  if (loading) {
  return (
    <div className="flex min-h-screen items-center justify-center bg-slate-50">
      <div className="rounded-2xl bg-white px-5 py-3 text-slate-500 shadow-[0_10px_30px_rgba(15,23,42,0.06)]">
        กำลังตรวจสอบสิทธิ์...
      </div>
    </div>
  );
}

  if (!ok) {
    const isAdmin = loc.pathname.startsWith("/admin");
    return <Navigate to={isAdmin ? "/admin/login" : "/tenant/login"} replace />;
  }

  return <>{children}</>;
}
