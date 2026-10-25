import { Head, Link } from '@inertiajs/react'
import MarkadoLogo from '@prototype/components/MarkadoLogo.jsx'
import ThemeToggle from '@prototype/components/ThemeToggle.jsx'

const messages = {
  403: ['Access restricted', 'You do not have permission to view this MARKADO page.'],
  404: ['Page not found', 'The requested MARKADO page does not exist or has moved.'],
  500: ['Something went wrong', 'The server could not complete this request. Try again later.'],
  503: ['Service unavailable', 'MARKADO is temporarily unavailable. Try again shortly.'],
}

export default function ErrorPage({ status = 500 }) {
  const [title, description] = messages[status] ?? messages[500]

  return (
    <main className="foundation-error" id="main-content">
      <Head title={`${status} ${title}`} />
      <ThemeToggle compact />
      <MarkadoLogo size={30} />
      <p className="section-kicker">Error {status}</p>
      <h1>{title}</h1>
      <p>{description}</p>
      <Link className="button button--primary" href="/">
        Return to overview
      </Link>
    </main>
  )
}
