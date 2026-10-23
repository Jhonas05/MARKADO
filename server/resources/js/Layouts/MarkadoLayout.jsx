import StudentShell from './StudentShell.jsx'

const navigation = [
  { href: '/', icon: 'home', label: 'Overview', key: 'overview' },
  { href: '/login', icon: 'book', label: 'Join a quiz', key: 'join' },
  { icon: 'medal', label: 'Results', key: 'results', disabled: true },
  { href: '#foundation-guide', icon: 'help', label: 'Help', key: 'help' },
]

export default function MarkadoLayout({ children, student, title }) {
  return (
    <StudentShell
      active="overview"
      homeHref="/"
      navigation={navigation}
      profile={{
        detail: student.studentNumber,
        initials: student.initials,
        name: student.name,
      }}
      title={title}
    >
      {children}
    </StudentShell>
  )
}
