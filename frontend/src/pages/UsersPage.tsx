import { useEffect, useState } from 'react'
import { apiGet } from '../services/api/client'
import { usePending } from '../hooks/usePending'

type User = { id: number; email: string; role: string }

export function UsersPage() {
  const [users, setUsers] = useState<User[]>([])
  const [error, setError] = useState<string>('')
  const { pending, run } = usePending()

  useEffect(() => {
    run(async () => {
      const payload = await apiGet<{ data: User[] }>('/users')
      setUsers(payload.data)
    }).catch((err: Error) => setError(err.message))
  }, [])

  if (pending) return <p>Loading users...</p>
  if (error) return <p>Failed to load users: {error}</p>

  return (
    <ul>
      {users.map((user) => (
        <li key={user.id}>
          {user.email} ({user.role})
        </li>
      ))}
    </ul>
  )
}
