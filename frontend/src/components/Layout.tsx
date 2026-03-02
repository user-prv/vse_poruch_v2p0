import { Link, Outlet } from 'react-router-dom'

export function Layout() {
  return (
    <div style={{ fontFamily: 'sans-serif', padding: '16px' }}>
      <h1>VsePoruch v2</h1>
      <nav style={{ display: 'flex', gap: '12px' }}>
        <Link to="/">Home</Link>
        <Link to="/users">Users</Link>
      </nav>
      <hr />
      <Outlet />
    </div>
  )
}
