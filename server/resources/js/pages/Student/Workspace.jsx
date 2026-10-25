import { Link } from '@inertiajs/react'
import Icon from '@prototype/components/Icon.jsx'
import StudentQuizLayout from '../../Layouts/StudentQuizLayout.jsx'

export default function Workspace({ assessmentStartAvailable, eligibility, quizJoinUrl }) {
  const verified = eligibility.hasSchoolRecord
    && eligibility.isActive
    && eligibility.verificationStatus === 'verified'
    && eligibility.mailboxVerified
    && eligibility.identityMatched

  return (
    <StudentQuizLayout active="workspace" title="Student dashboard">
      <main className="student-account-page" id="main-content">
        <section className="student-account-hero">
          <div>
            <p className="section-kicker">Student dashboard</p>
            <h1>Your quizzes start here</h1>
            <p>Enter a quiz code, review the instructions, and check your access before you begin.</p>
          </div>
          <Link className="button button--primary" href={quizJoinUrl}>
            Join with a code <Icon name="arrowRight" size={17} />
          </Link>
        </section>

        <div className="student-account-grid">
          <section className="panel student-account-card" id="account" aria-labelledby="identity-heading">
            <p className="section-kicker">Account access</p>
            <h2 id="identity-heading">{verified ? 'School identity verified' : 'School verification required'}</h2>
            <dl>
              <div><dt>Student number</dt><dd>{eligibility.studentNumber || 'Not assigned'}</dd></div>
              <div><dt>Verification</dt><dd>{eligibility.verificationLabel}</dd></div>
              <div><dt>Institutional mailbox</dt><dd>{eligibility.mailboxVerified ? 'Verified' : 'Verification required'}</dd></div>
              <div><dt>Roster identity match</dt><dd>{eligibility.identityMatched ? 'Matched' : 'Not confirmed'}</dd></div>
              <div><dt>Record status</dt><dd>{eligibility.isActive ? 'Active' : 'Unavailable'}</dd></div>
            </dl>
          </section>
          <section className="panel student-account-card" aria-labelledby="attempt-heading">
            <p className="section-kicker">Quiz access</p>
            <h2 id="attempt-heading">{assessmentStartAvailable ? 'Ready to check a quiz' : 'Quiz start is currently unavailable'}</h2>
            <p>{assessmentStartAvailable
              ? 'Use the code from your professor to review the quiz schedule and instructions before starting.'
              : 'You can still enter a code and review an assigned quiz. Your school has not enabled quiz starts yet.'}</p>
            <div className="student-account-callout" role="note"><Icon name="shield" size={18} /> A quiz code never replaces school verification or assignment.</div>
          </section>
        </div>
      </main>
    </StudentQuizLayout>
  )
}
