import { useForm } from '@inertiajs/react'

export default function SchoolVerification() {
  const send = useForm({})
  const proof = useForm({ verification_token: '', password: '', password_confirmation: '' })

  function verify(event) {
    event.preventDefault()
    proof.post('/student/school-verification/confirm', { preserveScroll: true })
  }

  return (
    <section aria-labelledby="school-proof-title">
      <h3 id="school-proof-title">Verify your school identity</h3>
      <p>Use the mailbox on your school record to confirm your identity. Your professor can then assign you a quiz.</p>
      <button type="button" className="button button--secondary" disabled={send.processing}
        onClick={() => send.post('/student/school-verification/send', { preserveScroll: true })}>
        Send school verification code
      </button>
      <form className="auth-form" onSubmit={verify}>
        {[
          ['verification_token', 'School verification code', 'text', 'one-time-code'],
          ['password', 'New school account password', 'password', 'new-password'],
          ['password_confirmation', 'Confirm new password', 'password', 'new-password'],
        ].map(([name, label, type, autoComplete]) => (
          <div className="auth-field" key={name}>
            <label htmlFor={'school-' + name}>{label}</label>
            <input id={'school-' + name} type={type} autoComplete={autoComplete} required value={proof.data[name]}
              onChange={(event) => proof.setData(name, event.target.value)} aria-invalid={Boolean(proof.errors[name])}
              aria-describedby={proof.errors[name] ? 'school-error-' + name : undefined} />
            {proof.errors[name] && <span role="alert" id={'school-error-' + name}>{proof.errors[name]}</span>}
          </div>
        ))}
        <small>Choose a different password with at least 12 characters, uppercase and lowercase letters, and a number.</small>
        <button type="submit" className="button button--primary" disabled={proof.processing}>Verify school identity</button>
      </form>
    </section>
  )
}
