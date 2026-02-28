import React from "react";
import { Navigate, useLocation } from "react-router-dom";
import { storage } from "../../shared/utils/storage";

type Props = { role: "admin" | "tenant"; children: React.ReactNode };

export default function RoleRoute({ role, children }: Props) {
  const loc = useLocation();
  const current = storage.getRole();

  if (current !== role) {
    return (
      <Navigate
        to={role === "admin" ? "/admin/login" : "/tenant/login"}
        replace
        state={{ from: loc.pathname }}
      />
    );
  }

  return <>{children}</>;
}
