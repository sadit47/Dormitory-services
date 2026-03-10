import React from "react";
import { Navigate, useLocation } from "react-router-dom";
import { storage } from "../../shared/utils/storage";

type Props = {
  role: "admin" | "tenant";
  children: React.ReactNode;
};

export default function RoleRoute({ role, children }: Props) {
  const loc = useLocation();
  const currentRole = storage.getRole();

  if (currentRole !== role) {
    if (currentRole === "admin") {
      return <Navigate to="/admin/dashboard" replace state={{ from: loc }} />;
    }

    if (currentRole === "tenant") {
      return <Navigate to="/tenant/dashboard" replace state={{ from: loc }} />;
    }

    return <Navigate to={role === "admin" ? "/admin/login" : "/tenant/login"} replace />;
  }

  return <>{children}</>;
}