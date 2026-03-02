import { Link, Route, Routes, useNavigate } from 'react-router-dom';
import { HomePage } from './pages/HomePage';
import { CategoriesPage } from './pages/CategoriesPage';
import { ItemDetailsPage } from './pages/ItemDetailsPage';
import { UserProfilePage } from './pages/UserProfilePage';
import { DashboardPage } from './pages/DashboardPage';
import { AdminPage } from './pages/AdminPage';
import { LoginPage } from './pages/LoginPage';
import { RegisterPage } from './pages/RegisterPage';
import { ResetPasswordPage } from './pages/ResetPasswordPage';
import { RouteGuard } from './components/RouteGuard';
import { logout } from './api/auth';
import { useAppStore } from './shared/store';

export function App() {
  const navigate = useNavigate();
  const { isAuthenticated, user, clearSession } = useAppStore();
  const isAdmin = String(user?.role || '').trim().toLowerCase() === 'admin';

  const handleLogout = async () => {
    try {
      await logout();
    } catch {
      // ignore logout request errors and clear local session anyway
    } finally {
      clearSession();
      navigate('/login', { replace: true });
    }
  };

  return (
    <>
      <header>
        <nav>
          <Link to="/">Головна</Link> | <Link to="/categories">Категорії</Link> | <Link to="/dashboard">Dashboard</Link> |{' '}
          {isAdmin ? <Link to="/admin">Admin</Link> : <span aria-hidden="true">Admin</span>}
        </nav>
        {isAuthenticated ? (
          <div>
            <span>Ви увійшли як {user?.email || 'користувач'}.</span> <button onClick={handleLogout}>Вийти</button>
          </div>
        ) : (
          <div>
            <Link to="/login">Вхід</Link> | <Link to="/register">Реєстрація</Link>
          </div>
        )}
      </header>

      <Routes>
        <Route path="/" element={<HomePage />} />
        <Route path="/categories" element={<CategoriesPage />} />
        <Route path="/item/:id" element={<ItemDetailsPage />} />
        <Route path="/user/:id" element={<UserProfilePage />} />
        <Route path="/login" element={<LoginPage />} />
        <Route path="/register" element={<RegisterPage />} />
        <Route path="/reset-password" element={<ResetPasswordPage />} />

        <Route element={<RouteGuard />}>
          <Route path="/dashboard" element={<DashboardPage />} />
        </Route>

        <Route element={<RouteGuard requireAdmin allowedRoles={['admin']} />}>
          <Route path="/admin" element={<AdminPage />} />
        </Route>
      </Routes>
    </>
  );
}
