import { useEffect, useState } from 'react'

export default function useAuthoritativeCountdown(serverNow, deadlineAt) {
  const initialRemaining = deadlineAt
    ? Math.max(0, Date.parse(deadlineAt) - Date.parse(serverNow))
    : null
  const [elapsed, setElapsed] = useState(0)

  useEffect(() => {
    if (initialRemaining === null) return undefined

    const timer = window.setInterval(() => setElapsed((value) => value + 1000), 1000)
    return () => window.clearInterval(timer)
  }, [initialRemaining])

  return initialRemaining === null ? null : Math.max(0, initialRemaining - elapsed)
}
