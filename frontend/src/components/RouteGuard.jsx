import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { useAppStore } from '../shared/store';

function normalizeRole(role) {
  return String(role || '').trim().toLowerCase();
}

export function RouteGuard({ requireAdmin = false, allowedRoles = [] }) {
  const location = useLocation();
  const { isAuthenticated, user } = useAppStore();
  const normalizedRole = normalizeRole(user?.role);

  if (!isAuthenticated) {
    return <Navigate to="/login" replace state={{ from: location.pathname }} />;
  }

  if (requireAdmin && normalizedRole !== 'admin') {
    return <Navigate to="/dashboard" replace />;
  }

  if (allowedRoles.length > 0 && !allowedRoles.map(normalizeRole).includes(normalizedRole)) {
    return <Navigate to="/dashboard" replace />;
  }

  return <Outlet />;
}
