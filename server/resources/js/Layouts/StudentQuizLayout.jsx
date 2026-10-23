import { usePage } from '@inertiajs/react'
import StudentShell from './StudentShell.jsx'

const navigation = [
  { href: '/student/dashboard', icon: 'home', label: 'Workspace', key: 'workspace' },
  { href: '/student/quizzes/join', icon: 'book', label: 'Join a quiz', key: 'join' },
  { icon: 'medal', label: 'Results', key: 'results', disabled: true },
  { href: '/student/dashboard#account', icon: 'user', label: 'Account', key: 'account' },
]

export default function StudentQuizLayout({ active = 'join', children, title }) {
  const { auth } = usePage().props
  const user = auth.user
  const initials = user.name
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0])
    .join('')
    .toUpperCase()

  return (
    <StudentShell
      active={active}
      authenticated
      homeHref="/student/dashboard"
      navigation={navigation}
      profile={{ detail: user.roleLabel || 'Student', initials, name: user.name }}
      title={title}
    >
      {children}
    </StudentShell>
  )
}
