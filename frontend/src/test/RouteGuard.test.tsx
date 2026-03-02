import { render, screen } from '@testing-library/react'
import { MemoryRouter } from 'react-router-dom'
import { RouteGuard } from '../router/RouteGuard'

describe('RouteGuard', () => {
  it('renders children when role is allowed', () => {
    render(
      <MemoryRouter>
        <RouteGuard allowedRoles={['admin']}>
          <div>Secret page</div>
        </RouteGuard>
      </MemoryRouter>
    )

    expect(screen.getByText('Secret page')).toBeTruthy()
  })
})
