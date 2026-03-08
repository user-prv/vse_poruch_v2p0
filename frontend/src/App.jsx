import { Link, NavLink, Route, Routes, useNavigate } from 'react-router-dom';
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
    <div className="app-shell">
      <header className="topbar">
        <Link to="/" className="brand">
          Поруч
        </Link>

        <nav className="topnav" aria-label="Головна навігація">
          <NavLink to="/" end className={({ isActive }) => `topnav-link ${isActive ? 'active' : ''}`}>
            Головна
          </NavLink>
          <NavLink to="/categories" className={({ isActive }) => `topnav-link ${isActive ? 'active' : ''}`}>
            Категорії
          </NavLink>
          <NavLink to="/register" className={({ isActive }) => `topnav-link ${isActive ? 'active' : ''}`}>
            Інфо
          </NavLink>
          <NavLink to="/dashboard" className={({ isActive }) => `topnav-link ${isActive ? 'active' : ''}`}>
            Кабінет
          </NavLink>
        </nav>
      </header>

      {isAuthenticated ? (
        <div className="session-banner">
          <span>Ви увійшли як {user?.email || 'користувач'}.</span>
          <button type="button" onClick={handleLogout}>
            Вийти
          </button>
          {isAdmin ? <Link to="/admin">Admin</Link> : null}
        </div>
      ) : (
        <div className="session-banner">
          <Link to="/login">Вхід</Link>
          <span aria-hidden="true">•</span>
          <Link to="/register">Реєстрація</Link>
        </div>
      )}

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
    </div>
  );
}
