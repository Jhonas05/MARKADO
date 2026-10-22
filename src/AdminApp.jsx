import { useEffect, useMemo, useState } from 'react'
import BrandMark from './components/BrandMark.jsx'
import Icon from './components/Icon.jsx'
import ProvisionalTag from './components/ProvisionalTag.jsx'
import {
  adminActivity,
  adminAssessments,
  adminHelpTopics,
  adminMetrics,
  adminProfile,
  adminUsers,
  submissionDistribution,
  weeklyActivity,
} from './data/adminPrototype.js'
import './admin.css'

const screens = {
  dashboard: 'dashboard',
  users: 'users',
  assessments: 'assessments',
  reports: 'reports',
  help: 'help',
}

const navigation = [
  { id: screens.dashboard, label: 'Dashboard', icon: 'dashboard' },
  { id: screens.users, label: 'User management', icon: 'users' },
  { id: screens.assessments, label: 'Assessment oversight', icon: 'book' },
  { id: screens.reports, label: 'Reporting overview', icon: 'barChart' },
  { id: screens.help, label: 'Help & guide', icon: 'help' },
]

const screenLabels = Object.fromEntries(
  navigation.map((item) => [item.id, item.label]),
)

function statusTone(status) {
  if (['Active', 'Published'].includes(status)) return 'success'
  if (['Pending review', 'Review needed', 'Ready for review'].includes(status)) {
    return 'warning'
  }
  if (status === 'Scheduled') return 'info'
  if (status === 'Inactive' || status === 'Closed') return 'neutral'
  return 'neutral'
}

function StatusBadge({ children, tone = 'neutral' }) {
  return <span className={`admin-status admin-status--${tone}`}>{children}</span>
}

function PrototypeBanner() {
  return (
    <div className="admin-prototype-banner" role="note">
      <Icon name="spark" size={17} />
      <p>
        <strong>Administrator UI prototype.</strong> Accounts, assessments,
        reports, activity, and management actions use synthetic data and do not
        change real records.
      </p>
    </div>
  )
}

function ScreenHeader({ eyebrow, title, description, actions }) {
  return (
    <header className="admin-page-heading">
      <div>
        <p className="section-kicker">{eyebrow}</p>
        <h1>{title}</h1>
        <p>{description}</p>
      </div>
      {actions ? <div className="admin-page-heading__actions">{actions}</div> : null}
    </header>
  )
}

function AdminHeader({ currentScreen, onMenu, onProfessor, onStudent }) {
  return (
    <header className="admin-header">
      <div className="admin-header__brand">
        <button
          className="admin-mobile-menu"
          type="button"
          onClick={onMenu}
          aria-label="Open administrator navigation"
        >
          <Icon name="menu" />
        </button>
        <BrandMark />
        <span>
          <strong>MARKADO</strong>
          <small>Administrator workspace</small>
        </span>
      </div>
      <div className="admin-header__context">
        <small>Administrator prototype</small>
        <strong>{screenLabels[currentScreen]}</strong>
      </div>
      <div className="admin-header__actions">
        <button className="admin-view-switch" type="button" onClick={onStudent}>
          <Icon name="user" size={17} />
          <span>Student view</span>
        </button>
        <button className="admin-view-switch" type="button" onClick={onProfessor}>
          <Icon name="book" size={17} />
          <span>Professor view</span>
        </button>
        <div className="admin-profile" aria-label={`Signed in as ${adminProfile.name}`}>
          <span>{adminProfile.initials}</span>
          <div>
            <strong>{adminProfile.shortName}</strong>
            <small>{adminProfile.scope}</small>
          </div>
        </div>
      </div>
    </header>
  )
}

function AdminSidebar({ currentScreen, mobileOpen, onClose, onNavigate }) {
  return (
    <>
      {mobileOpen ? (
        <button
          className="admin-nav-backdrop"
          type="button"
          aria-label="Close administrator navigation"
          onClick={onClose}
        />
      ) : null}
      <aside
        className={`admin-sidebar${mobileOpen ? ' admin-sidebar--open' : ''}`}
        aria-label="Administrator navigation"
      >
        <div className="admin-sidebar__intro">
          <span>Operational scope</span>
          <strong>Academic oversight</strong>
          <small>Synthetic workspace · PHT</small>
        </div>
        <nav aria-label="Administrator navigation">
          <p>Administration</p>
          {navigation.map((item) => (
            <button
              className={
                currentScreen === item.id
                  ? 'admin-nav-link admin-nav-link--active'
                  : 'admin-nav-link'
              }
              type="button"
              key={item.id}
              onClick={() => onNavigate(item.id)}
              aria-current={currentScreen === item.id ? 'page' : undefined}
            >
              <Icon name={item.icon} size={18} />
              <span>{item.label}</span>
            </button>
          ))}
        </nav>
        <div className="admin-sidebar__policy">
          <Icon name="shield" size={19} />
          <div>
            <strong>Scoped access only</strong>
            <span>No impersonation, hidden answer keys, or private grade browsing.</span>
          </div>
        </div>
      </aside>
    </>
  )
}

function MetricCard({ metric }) {
  return (
    <article className="admin-metric">
      <span className={`admin-metric__icon admin-metric__icon--${metric.tone}`}>
        <Icon name={metric.icon} />
      </span>
      <div>
        <strong>{metric.value}</strong>
        <span>{metric.label}</span>
      </div>
      <small>{metric.detail}</small>
    </article>
  )
}

function AdminDashboard({ onNavigate }) {
  return (
    <>
      <PrototypeBanner />
      <ScreenHeader
        eyebrow="Administrator dashboard"
        title="Good afternoon, Admin Mendoza."
        description="Review account support, assessment operations, and privacy-conscious aggregate activity from one scoped workspace."
        actions={
          <button className="button button--primary" type="button" onClick={() => onNavigate(screens.users)}>
            Review accounts
            <Icon name="arrowRight" size={18} />
          </button>
        }
      />

      <section className="admin-metric-grid" aria-label="Synthetic administration summary">
        {adminMetrics.map((metric) => <MetricCard key={metric.label} metric={metric} />)}
      </section>

      <div className="admin-dashboard-grid">
        <section className="admin-panel admin-status-overview" aria-labelledby="status-overview-title">
          <div className="admin-panel-heading">
            <div>
              <p className="section-kicker">Assessment operations</p>
              <h2 id="status-overview-title">Status overview</h2>
            </div>
            <StatusBadge tone="info">26 total</StatusBadge>
          </div>
          <div className="admin-state-grid">
            <div><strong>7</strong><span>Draft</span></div>
            <div><strong>1</strong><span>Ready for review</span></div>
            <div><strong>18</strong><span>Published</span></div>
            <div><strong>4</strong><span>Closed this month</span></div>
          </div>
          <div className="admin-overview-bar" aria-label="18 of 26 assessments published">
            <span style={{ width: '69%' }} />
          </div>
          <div className="admin-card-actions">
            <button className="button button--primary" type="button" onClick={() => onNavigate(screens.assessments)}>
              Open assessment oversight
              <Icon name="arrowRight" size={18} />
            </button>
          </div>
        </section>

        <section className="admin-panel admin-quick-nav" aria-labelledby="quick-nav-title">
          <div className="admin-panel-heading">
            <div>
              <p className="section-kicker">Quick navigation</p>
              <h2 id="quick-nav-title">Administrative sections</h2>
            </div>
          </div>
          <div className="admin-quick-list">
            {navigation.slice(1).map((item) => (
              <button type="button" key={item.id} onClick={() => onNavigate(item.id)}>
                <span><Icon name={item.icon} size={19} /></span>
                <strong>{item.label}</strong>
                <Icon name="chevronRight" size={18} />
              </button>
            ))}
          </div>
        </section>

        <section className="admin-panel admin-activity" aria-labelledby="activity-title">
          <div className="admin-panel-heading">
            <div>
              <p className="section-kicker">Synthetic audit preview</p>
              <h2 id="activity-title">Recent administrative activity</h2>
            </div>
            <StatusBadge>Mock log</StatusBadge>
          </div>
          <div className="admin-activity-list">
            {adminActivity.map((item) => (
              <article key={item.id}>
                <span className={`admin-activity__icon admin-activity__icon--${item.tone}`}>
                  <Icon name={item.icon} size={18} />
                </span>
                <div>
                  <strong>{item.title}</strong>
                  <span>{item.actor}</span>
                </div>
                <time>{item.time}</time>
              </article>
            ))}
          </div>
        </section>
      </div>
    </>
  )
}

function AccountDetail({ user, onClose, onPreviewAction }) {
  if (!user) return null

  return (
    <div className="admin-drawer" role="presentation">
      <button className="admin-drawer__backdrop" type="button" onClick={onClose} aria-label="Close account details" />
      <section role="dialog" aria-modal="true" aria-labelledby="account-detail-title">
        <div className="admin-drawer__header">
          <div>
            <p className="section-kicker">Synthetic account</p>
            <h2 id="account-detail-title">{user.name}</h2>
          </div>
          <button className="icon-button" type="button" onClick={onClose} aria-label="Close account details" autoFocus>
            <Icon name="close" />
          </button>
        </div>
        <StatusBadge tone={statusTone(user.status)}>{user.status}</StatusBadge>
        <dl className="admin-detail-list">
          <div><dt>Account reference</dt><dd>{user.id}</dd></div>
          <div><dt>Role</dt><dd>{user.role}</dd></div>
          <div><dt>Authorized context</dt><dd>{user.context}</dd></div>
          <div><dt>Last active</dt><dd>{user.lastActive}</dd></div>
          <div><dt>Support note</dt><dd>{user.supportNote}</dd></div>
        </dl>
        <div className="admin-privacy-note">
          <Icon name="shield" size={18} />
          <p>No grades, answers, passwords, or unnecessary contact details are shown in this generic support view.</p>
        </div>
        <ProvisionalTag>Account-management authority is unapproved</ProvisionalTag>
        <button className="button button--primary button--full" type="button" onClick={() => onPreviewAction(user)}>
          Preview support action
          <Icon name="arrowRight" size={18} />
        </button>
      </section>
    </div>
  )
}

function UserManagement({ onToast }) {
  const [query, setQuery] = useState('')
  const [role, setRole] = useState('All roles')
  const [status, setStatus] = useState('All statuses')
  const [selectedUser, setSelectedUser] = useState(null)
  const [confirmUser, setConfirmUser] = useState(null)

  const filteredUsers = useMemo(() => {
    const normalized = query.trim().toLowerCase()
    return adminUsers.filter((user) => {
      const matchesQuery = !normalized || [user.name, user.id, user.context].some((value) => value.toLowerCase().includes(normalized))
      const matchesRole = role === 'All roles' || user.role === role
      const matchesStatus = status === 'All statuses' || user.status === status
      return matchesQuery && matchesRole && matchesStatus
    })
  }, [query, role, status])

  function completeMockAction() {
    onToast(`Mock support preview recorded for ${confirmUser.name}. No account was changed.`)
    setConfirmUser(null)
    setSelectedUser(null)
  }

  function previewMockAction(user) {
    setSelectedUser(null)
    setConfirmUser(user)
  }

  return (
    <>
      <PrototypeBanner />
      <ScreenHeader
        eyebrow="Account operations"
        title="User management"
        description="Search synthetic accounts and preview scoped support actions without exposing grades, answer keys, or unrestricted student records."
        actions={<ProvisionalTag>Permissions require approval</ProvisionalTag>}
      />

      <section className="admin-panel admin-directory" aria-labelledby="directory-title">
        <div className="admin-panel-heading">
          <div><p className="section-kicker">Synthetic directory</p><h2 id="directory-title">Accounts</h2></div>
          <span className="admin-result-count" aria-live="polite">{filteredUsers.length} shown</span>
        </div>
        <div className="admin-toolbar">
          <label className="admin-search">
            <span className="sr-only">Search accounts</span>
            <Icon name="search" size={19} />
            <input value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Search name, reference, or context" />
          </label>
          <label className="admin-filter">
            <span>Role</span>
            <select value={role} onChange={(event) => setRole(event.target.value)}>
              <option>All roles</option>
              <option>Student</option>
              <option>Instructor</option>
              <option>Administrator</option>
            </select>
          </label>
          <label className="admin-filter">
            <span>Status</span>
            <select value={status} onChange={(event) => setStatus(event.target.value)}>
              <option>All statuses</option>
              <option>Active</option>
              <option>Pending review</option>
              <option>Review needed</option>
              <option>Inactive</option>
            </select>
          </label>
        </div>

        {filteredUsers.length ? (
          <div className="admin-table-wrap">
            <table className="admin-table">
              <thead><tr><th>Account</th><th>Role</th><th>Context</th><th>Status</th><th>Last active</th><th><span className="sr-only">Actions</span></th></tr></thead>
              <tbody>
                {filteredUsers.map((user) => (
                  <tr key={user.id} data-testid="admin-user-row">
                    <td><button className="admin-name-button" type="button" onClick={() => setSelectedUser(user)}><strong>{user.name}</strong><small>{user.id}</small></button></td>
                    <td>{user.role}</td>
                    <td>{user.context}</td>
                    <td><StatusBadge tone={statusTone(user.status)}>{user.status}</StatusBadge></td>
                    <td>{user.lastActive}</td>
                    <td><button className="admin-row-action" type="button" onClick={() => setSelectedUser(user)} aria-label={`Inspect ${user.name}`}><Icon name="chevronRight" size={18} /></button></td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        ) : (
          <div className="admin-empty" role="status">
            <span><Icon name="search" size={24} /></span>
            <h3>No accounts match these filters</h3>
            <p>Clear the search or choose broader role and status filters.</p>
            <button className="button button--secondary" type="button" onClick={() => { setQuery(''); setRole('All roles'); setStatus('All statuses') }}>Clear filters</button>
          </div>
        )}
      </section>

      <AccountDetail user={selectedUser} onClose={() => setSelectedUser(null)} onPreviewAction={previewMockAction} />
      {confirmUser ? (
        <ConfirmationDialog
          title="Preview this support action?"
          description={`This records a local mock acknowledgement for ${confirmUser.name}. It will not approve, disable, edit, or impersonate the account.`}
          confirmLabel="Confirm mock preview"
          onCancel={() => setConfirmUser(null)}
          onConfirm={completeMockAction}
        />
      ) : null}
    </>
  )
}

function AssessmentDetail({ assessment, onClose }) {
  if (!assessment) return null
  return (
    <div className="admin-drawer" role="presentation">
      <button className="admin-drawer__backdrop" type="button" onClick={onClose} aria-label="Close assessment details" />
      <section role="dialog" aria-modal="true" aria-labelledby="assessment-detail-title">
        <div className="admin-drawer__header">
          <div><p className="section-kicker">{assessment.course} · {assessment.id}</p><h2 id="assessment-detail-title">{assessment.title}</h2></div>
          <button className="icon-button" type="button" onClick={onClose} aria-label="Close assessment details" autoFocus><Icon name="close" /></button>
        </div>
        <StatusBadge tone={statusTone(assessment.status)}>{assessment.status}</StatusBadge>
        <dl className="admin-detail-list">
          <div><dt>Owning instructor</dt><dd>{assessment.instructor}</dd></div>
          <div><dt>Section</dt><dd>{assessment.section}</dd></div>
          <div><dt>Schedule</dt><dd>{assessment.schedule}</dd></div>
          <div><dt>Participation summary</dt><dd>{assessment.participation}</dd></div>
        </dl>
        <div className="admin-participation" aria-label={`${assessment.percent} percent participation`}>
          <span style={{ width: `${assessment.percent}%` }} />
        </div>
        <div className="admin-privacy-note">
          <Icon name="shield" size={18} />
          <p>This operational preview omits learner grades, responses, and the assessment answer key.</p>
        </div>
        <ProvisionalTag>Editing and publishing authority is unapproved</ProvisionalTag>
      </section>
    </div>
  )
}

function AssessmentOversight() {
  const [query, setQuery] = useState('')
  const [status, setStatus] = useState('All statuses')
  const [selectedAssessment, setSelectedAssessment] = useState(null)

  const filteredAssessments = useMemo(() => {
    const normalized = query.trim().toLowerCase()
    return adminAssessments.filter((assessment) => {
      const matchesQuery = !normalized || [assessment.title, assessment.course, assessment.instructor, assessment.section].some((value) => value.toLowerCase().includes(normalized))
      return matchesQuery && (status === 'All statuses' || assessment.status === status)
    })
  }, [query, status])

  return (
    <>
      <PrototypeBanner />
      <ScreenHeader
        eyebrow="Operational visibility"
        title="Assessment oversight"
        description="Review synthetic ownership, lifecycle status, schedules, and participation without crossing instructor or learner privacy boundaries."
        actions={<ProvisionalTag>Authority is scope-dependent</ProvisionalTag>}
      />

      <section className="admin-panel admin-assessments" aria-labelledby="assessments-title">
        <div className="admin-panel-heading">
          <div><p className="section-kicker">Assessment registry</p><h2 id="assessments-title">Assessments</h2></div>
          <span className="admin-result-count" aria-live="polite">{filteredAssessments.length} shown</span>
        </div>
        <div className="admin-toolbar admin-toolbar--compact">
          <label className="admin-search">
            <span className="sr-only">Search assessments</span>
            <Icon name="search" size={19} />
            <input value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Search title, course, instructor, or section" />
          </label>
          <label className="admin-filter">
            <span>Status</span>
            <select value={status} onChange={(event) => setStatus(event.target.value)}>
              <option>All statuses</option>
              <option>Draft</option>
              <option>Ready for review</option>
              <option>Scheduled</option>
              <option>Published</option>
              <option>Closed</option>
            </select>
          </label>
        </div>

        {filteredAssessments.length ? (
          <div className="admin-assessment-list">
            {filteredAssessments.map((assessment) => (
              <article key={assessment.id}>
                <span className="admin-course-tile">{assessment.course.split(' ')[0]}</span>
                <div className="admin-assessment-copy">
                  <small>{assessment.course} · {assessment.section}</small>
                  <h3>{assessment.title}</h3>
                  <span>{assessment.instructor}</span>
                </div>
                <div className="admin-assessment-schedule">
                  <small>Availability</small>
                  <strong>{assessment.schedule}</strong>
                </div>
                <div className="admin-assessment-participation">
                  <small>Participation</small>
                  <strong>{assessment.participation}</strong>
                </div>
                <StatusBadge tone={statusTone(assessment.status)}>{assessment.status}</StatusBadge>
                <button className="button button--secondary" type="button" onClick={() => setSelectedAssessment(assessment)}>
                  View details
                </button>
              </article>
            ))}
          </div>
        ) : (
          <div className="admin-empty" role="status">
            <span><Icon name="book" size={24} /></span>
            <h3>No assessments match</h3>
            <p>Change the search or select another lifecycle status.</p>
            <button className="button button--secondary" type="button" onClick={() => { setQuery(''); setStatus('All statuses') }}>Clear filters</button>
          </div>
        )}
      </section>
      <AssessmentDetail assessment={selectedAssessment} onClose={() => setSelectedAssessment(null)} />
    </>
  )
}

function ReportingOverview({ onToast }) {
  const [dateFrom, setDateFrom] = useState('2026-10-01')
  const [dateTo, setDateTo] = useState('2026-10-22')
  const [appliedRange, setAppliedRange] = useState('1–22 Oct 2026 · PHT')
  const [exportOpen, setExportOpen] = useState(false)
  const invalidRange = dateFrom && dateTo && dateFrom > dateTo

  function applyRange(event) {
    event.preventDefault()
    if (invalidRange) return
    setAppliedRange(`${dateFrom} to ${dateTo} · PHT`)
    onToast('Synthetic reporting range updated. No private records were queried.')
  }

  function prepareMockExport() {
    setExportOpen(false)
    onToast('Mock aggregate report prepared locally. No file was generated.')
  }

  return (
    <>
      <PrototypeBanner />
      <ScreenHeader
        eyebrow="Aggregate information only"
        title="Reporting overview"
        description="Explore synthetic participation and activity summaries without exposing individual learner grades, responses, or answer keys."
        actions={
          <button className="button button--primary" type="button" onClick={() => setExportOpen(true)}>
            Preview mock export
            <Icon name="download" size={18} />
          </button>
        }
      />

      <form className="admin-panel admin-date-range" onSubmit={applyRange} noValidate>
        <div><p className="section-kicker">Reporting window</p><h2>Date range</h2><small>Applied: {appliedRange}</small></div>
        <label><span>From</span><input type="date" value={dateFrom} onChange={(event) => setDateFrom(event.target.value)} /></label>
        <label><span>To</span><input type="date" value={dateTo} onChange={(event) => setDateTo(event.target.value)} aria-invalid={invalidRange} aria-describedby={invalidRange ? 'date-error' : undefined} /></label>
        <button className="button button--secondary" type="submit" disabled={invalidRange}>Apply range</button>
        {invalidRange ? <p className="admin-form-error" id="date-error" role="alert">The end date must be on or after the start date.</p> : null}
      </form>

      <section className="admin-report-metrics" aria-label="Synthetic reporting metrics">
        <article><span>Active assessments</span><strong>12</strong><small>Within selected range</small></article>
        <article><span>Eligible participants</span><strong>123</strong><small>Aggregate count</small></article>
        <article><span>Submission rate</span><strong>78%</strong><small>96 synthetic submissions</small></article>
        <article><span>Open support cases</span><strong>3</strong><small>No grade details</small></article>
      </section>

      <div className="admin-report-grid">
        <section className="admin-panel" aria-labelledby="distribution-title">
          <div className="admin-panel-heading"><div><p className="section-kicker">Participation</p><h2 id="distribution-title">Submission status</h2></div><StatusBadge tone="info">123 eligible</StatusBadge></div>
          <div className="admin-distribution" role="img" aria-label="Submitted 78 percent, in progress 15 percent, not started 7 percent">
            {submissionDistribution.map((item) => (
              <div key={item.label}>
                <div><span>{item.label}</span><strong>{item.count}</strong></div>
                <span className="admin-chart-track"><span className={`admin-chart-fill admin-chart-fill--${item.tone}`} style={{ width: `${item.value}%` }} /></span>
                <small>{item.value}%</small>
              </div>
            ))}
          </div>
        </section>

        <section className="admin-panel" aria-labelledby="weekly-title">
          <div className="admin-panel-heading"><div><p className="section-kicker">Assessment activity</p><h2 id="weekly-title">Weekly submissions</h2></div><StatusBadge>Mock trend</StatusBadge></div>
          <div className="admin-weekly-chart" role="img" aria-label="Synthetic weekly submissions peaked on Thursday">
            {weeklyActivity.map((day) => (
              <div key={day.label}><span style={{ height: `${day.value}%` }} /><strong>{day.value}</strong><small>{day.label}</small></div>
            ))}
          </div>
        </section>

        <section className="admin-panel admin-report-boundary" aria-labelledby="report-boundary-title">
          <Icon name="shield" size={24} />
          <div>
            <p className="section-kicker">Privacy boundary</p>
            <h2 id="report-boundary-title">Designed for minimum necessary reporting</h2>
            <p>Generic administrator reports use aggregate counts. Individual grades, answer keys, and detailed responses require a separately approved purpose and server-enforced scope.</p>
          </div>
          <ProvisionalTag decision="D-15 / D-19">Reporting and retention policies are open</ProvisionalTag>
        </section>
      </div>

      {exportOpen ? (
        <ConfirmationDialog
          title="Prepare an aggregate report preview?"
          description="The mock preview includes only the selected date range, aggregate activity, and submission status distribution. It creates no PDF or Word file and includes no individual grades."
          confirmLabel="Prepare mock summary"
          onCancel={() => setExportOpen(false)}
          onConfirm={prepareMockExport}
        />
      ) : null}
    </>
  )
}

function AdminHelp({ onNavigate }) {
  const [query, setQuery] = useState('')
  const normalized = query.trim().toLowerCase()
  const topics = adminHelpTopics.filter((topic) => !normalized || `${topic.title} ${topic.summary} ${topic.steps.join(' ')}`.toLowerCase().includes(normalized))

  return (
    <>
      <section className="admin-help-hero">
        <p className="section-kicker">Administrator help & guide</p>
        <h1>How can we help?</h1>
        <p>Task-focused guidance for scoped account support, assessment oversight, reporting, and privacy-aware administration.</p>
        <label className="admin-help-search">
          <Icon name="search" size={20} />
          <span className="sr-only">Search administrator help</span>
          <input value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Search help topics" />
        </label>
      </section>
      <div className="admin-help-layout">
        <section className="admin-help-topics" aria-label="Administrator help topics">
          {topics.length ? topics.map((topic, index) => (
            <details key={topic.title} open={index === 0 && !normalized}>
              <summary><span>{String(index + 1).padStart(2, '0')}</span><span><strong>{topic.title}</strong><small>{topic.summary}</small></span><Icon name="chevronDown" size={18} /></summary>
              <ol>{topic.steps.map((step) => <li key={step}>{step}</li>)}</ol>
            </details>
          )) : (
            <div className="admin-empty" role="status"><span><Icon name="search" size={24} /></span><h3>No help topics match</h3><p>Try “account,” “assessment,” “report,” or “privacy.”</p><button className="button button--secondary" type="button" onClick={() => setQuery('')}>Clear search</button></div>
          )}
        </section>
        <aside className="admin-help-shortcuts" aria-label="Administrator help shortcuts">
          <h2>Open a workspace</h2>
          <button type="button" onClick={() => onNavigate(screens.users)}><Icon name="users" size={18} /><span>User management</span><Icon name="chevronRight" size={17} /></button>
          <button type="button" onClick={() => onNavigate(screens.assessments)}><Icon name="book" size={18} /><span>Assessment oversight</span><Icon name="chevronRight" size={17} /></button>
          <button type="button" onClick={() => onNavigate(screens.reports)}><Icon name="barChart" size={18} /><span>Reporting overview</span><Icon name="chevronRight" size={17} /></button>
          <div><strong>Privacy reminder</strong><p>Use the minimum necessary information and never treat this prototype as authorization to access educational records.</p></div>
        </aside>
      </div>
    </>
  )
}

function ConfirmationDialog({ title, description, confirmLabel, onCancel, onConfirm }) {
  return (
    <div className="admin-modal-layer" role="presentation">
      <button className="admin-modal-backdrop" type="button" onClick={onCancel} aria-label="Close confirmation" />
      <section role="dialog" aria-modal="true" aria-labelledby="admin-confirm-title">
        <span className="admin-modal-icon"><Icon name="shield" size={24} /></span>
        <p className="section-kicker">Prototype confirmation</p>
        <h2 id="admin-confirm-title">{title}</h2>
        <p>{description}</p>
        <ProvisionalTag>Action is simulated and unapproved</ProvisionalTag>
        <div className="admin-modal-actions">
          <button className="button button--secondary" type="button" onClick={onCancel}>Cancel</button>
          <button className="button button--primary" type="button" onClick={onConfirm} autoFocus>{confirmLabel}</button>
        </div>
      </section>
    </div>
  )
}

export default function AdminApp({ onProfessor, onStudent }) {
  const [screen, setScreen] = useState(screens.dashboard)
  const [mobileNavOpen, setMobileNavOpen] = useState(false)
  const [toast, setToast] = useState('')

  useEffect(() => {
    window.scrollTo(0, 0)
  }, [screen])

  useEffect(() => {
    if (!toast) return undefined
    const timer = window.setTimeout(() => setToast(''), 4200)
    return () => window.clearTimeout(timer)
  }, [toast])

  function navigate(nextScreen) {
    setScreen(nextScreen)
    setMobileNavOpen(false)
  }

  let content
  switch (screen) {
    case screens.users:
      content = <UserManagement onToast={setToast} />
      break
    case screens.assessments:
      content = <AssessmentOversight />
      break
    case screens.reports:
      content = <ReportingOverview onToast={setToast} />
      break
    case screens.help:
      content = <AdminHelp onNavigate={navigate} />
      break
    default:
      content = <AdminDashboard onNavigate={navigate} />
  }

  return (
    <div className="admin-app">
      <a className="skip-link" href="#main-content">Skip to main content</a>
      <AdminHeader currentScreen={screen} onMenu={() => setMobileNavOpen(true)} onProfessor={onProfessor} onStudent={onStudent} />
      <div className="admin-frame">
        <AdminSidebar currentScreen={screen} mobileOpen={mobileNavOpen} onClose={() => setMobileNavOpen(false)} onNavigate={navigate} />
        <main className="admin-main" id="main-content">
          {content}
          <footer className="admin-footer"><span>MARKADO Administrator prototype · Phase 3</span><span>Synthetic data · scoped views · mock actions</span></footer>
        </main>
      </div>
      {toast ? <div className="admin-toast" role="status"><Icon name="checkCircle" size={18} />{toast}</div> : null}
    </div>
  )
}
