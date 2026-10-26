import { usePage } from '@inertiajs/react'
import StudentShell from './StudentShell.jsx'

export default function StudentQuizLayout({ active = 'join', children, showRewards = true, title }) {
  const { auth, gamification } = usePage().props
  const user = auth.user
  const navigation = [
    { href: '/student/dashboard', icon: 'home', label: 'Workspace', key: 'workspace' },
    { href: '/student/quizzes/join', icon: 'book', label: 'Join quiz', key: 'join' },
    ...(gamification?.enabled && showRewards ? [
      { href: '/student/characters/collection', icon: 'trophy', label: 'Collection', key: 'collection' },
      { href: '/student/characters/shop', icon: 'spark', label: 'Shop', key: 'shop' },
    ] : []),
    { href: '/student/dashboard#account', icon: 'user', label: 'Account', key: 'account' },
  ]
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
      wallet={showRewards && gamification?.enabled ? gamification : null}
    >
      {children}
    </StudentShell>
  )
}
