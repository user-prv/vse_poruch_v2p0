import { Route, Routes } from 'react-router-dom';
import { HomePage } from './pages/HomePage';
import { CategoriesPage } from './pages/CategoriesPage';
import { ItemDetailsPage } from './pages/ItemDetailsPage';
import { UserProfilePage } from './pages/UserProfilePage';
import { DashboardPage } from './pages/DashboardPage';
import { AdminPage } from './pages/AdminPage';

export function App() {
  return (
    <Routes>
      <Route path="/" element={<HomePage />} />
      <Route path="/categories" element={<CategoriesPage />} />
      <Route path="/item/:id" element={<ItemDetailsPage />} />
      <Route path="/user/:id" element={<UserProfilePage />} />
      <Route path="/dashboard" element={<DashboardPage />} />
      <Route path="/admin" element={<AdminPage />} />
    </Routes>
  );
}
