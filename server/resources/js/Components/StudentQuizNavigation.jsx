import { Link } from '@inertiajs/react'
import Icon from '@prototype/components/Icon.jsx'

const items = [
  { href: '/workspace', icon: 'home', label: 'Workspace', key: 'workspace' },
  { href: '/student/quizzes/join', icon: 'book', label: 'Join a quiz', key: 'join' },
]

export default function StudentQuizNavigation({ active }) {
  return (
    <nav className="student-preflight-nav" aria-label="Student quiz navigation">
      {items.map((item) => (
        <Link
          className={item.key === active ? 'nav-link nav-link--active' : 'nav-link'}
          href={item.href}
          aria-current={item.key === active ? 'page' : undefined}
          key={item.key}
        >
          <Icon name={item.icon} size={18} />
          <span>{item.label}</span>
        </Link>
      ))}
      <span className="nav-link foundation-nav-placeholder" aria-disabled="true">
        <Icon name="medal" size={18} />
        <span>Results</span>
      </span>
    </nav>
  )
}
