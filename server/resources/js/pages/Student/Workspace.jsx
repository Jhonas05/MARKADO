import { Link } from '@inertiajs/react'
import Icon from '@prototype/components/Icon.jsx'
import StudentQuizLayout from '../../Layouts/StudentQuizLayout.jsx'

export default function Workspace({ eligibility, quizJoinUrl }) {
  const verified = eligibility.hasSchoolRecord
    && eligibility.isActive
    && eligibility.verificationStatus === 'verified'

  return (
    <StudentQuizLayout active="workspace" title="Student dashboard">
      <main className="student-account-page" id="main-content">
        <section className="student-account-hero">
          <div>
            <p className="section-kicker">Student dashboard</p>
            <h1>Your assessment workspace</h1>
            <p>Review your institution-managed access status before opening a quiz.</p>
          </div>
          <Link className="button button--primary" href={quizJoinUrl}>
            Join with a code <Icon name="arrowRight" size={17} />
          </Link>
        </section>

        <div className="student-account-grid">
          <section className="panel student-account-card" id="account" aria-labelledby="identity-heading">
            <p className="section-kicker">School identity</p>
            <h2 id="identity-heading">{verified ? 'Verified for eligibility checks' : 'Verification required'}</h2>
            <dl>
              <div><dt>Student number</dt><dd>{eligibility.studentNumber || 'Not assigned'}</dd></div>
              <div><dt>Verification</dt><dd>{eligibility.verificationLabel}</dd></div>
              <div><dt>Record status</dt><dd>{eligibility.isActive ? 'Active' : 'Unavailable'}</dd></div>
            </dl>
          </section>
          <section className="panel student-account-card" aria-labelledby="attempt-heading">
            <p className="section-kicker">Assessment engine</p>
            <h2 id="attempt-heading">Attempts remain gated</h2>
            <p>Secure join preflight is available. Starting attempts, saving answers, timers, and scoring remain disabled on this route.</p>
            <div className="student-account-callout" role="note"><Icon name="shield" size={18} /> Eligibility is rechecked by Laravel for every protected quiz.</div>
          </section>
        </div>
      </main>
    </StudentQuizLayout>
  )
}
