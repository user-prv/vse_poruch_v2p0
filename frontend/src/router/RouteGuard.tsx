import { Navigate } from 'react-router-dom'

const currentUserRole = 'admin'

export function RouteGuard({ allowedRoles, children }: { allowedRoles: string[]; children: JSX.Element }) {
  if (!allowedRoles.includes(currentUserRole)) {
    return <Navigate to="/" replace />
  }
  return children
}
