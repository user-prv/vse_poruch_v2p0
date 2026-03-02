import { useState } from 'react'

export function usePending() {
  const [pending, setPending] = useState(false)

  async function run<T>(callback: () => Promise<T>): Promise<T> {
    setPending(true)
    try {
      return await callback()
    } finally {
      setPending(false)
    }
  }

  return { pending, run }
}
