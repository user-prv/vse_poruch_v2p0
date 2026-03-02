import { Route, Routes } from 'react-router-dom'
import { Layout } from '../components/Layout'
import { HomePage } from '../pages/HomePage'
import { UsersPage } from '../pages/UsersPage'
import { RouteGuard } from './RouteGuard'

export function AppRouter() {
  return (
    <Routes>
      <Route element={<Layout />}>
        <Route path="/" element={<HomePage />} />
        <Route
          path="/users"
          element={
            <RouteGuard allowedRoles={['admin', 'manager']}>
              <UsersPage />
            </RouteGuard>
          }
        />
      </Route>
    </Routes>
  )
}
