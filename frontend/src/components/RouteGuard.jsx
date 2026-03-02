import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { useAppStore } from '../shared/store';

export function RouteGuard({ requireAdmin = false }) {
  const location = useLocation();
  const { isAuthenticated, user } = useAppStore();

  if (!isAuthenticated) {
    return <Navigate to="/login" replace state={{ from: location.pathname }} />;
  }

  if (requireAdmin && user?.role !== 'admin') {
    return <Navigate to="/dashboard" replace />;
  }

  return <Outlet />;
}
