export const adminProfile = {
  name: 'Liza Mendoza',
  shortName: 'Admin Mendoza',
  initials: 'LM',
  scope: 'Academic operations · Prototype',
}

export const adminMetrics = [
  { label: 'Total accounts', value: '164', detail: 'Synthetic directory', icon: 'users', tone: 'primary' },
  { label: 'Students', value: '148', detail: 'Across 6 sections', icon: 'user', tone: 'primary' },
  { label: 'Instructors', value: '14', detail: '2 pending review', icon: 'book', tone: 'warning' },
  { label: 'Published quizzes', value: '18', detail: '26 total assessments', icon: 'checkCircle', tone: 'success' },
]

export const adminUsers = [
  {
    id: 'SYN-STU-0142',
    name: 'Mia Santos',
    role: 'Student',
    status: 'Active',
    context: 'BSBIO 1-A',
    lastActive: '22 Oct 2026 · 4:18 PM PHT',
    supportNote: 'No open support cases',
  },
  {
    id: 'SYN-STU-0187',
    name: 'Gabriel Aquino',
    role: 'Student',
    status: 'Active',
    context: 'BSBIO 1-A',
    lastActive: '22 Oct 2026 · 3:52 PM PHT',
    supportNote: 'Recovery request resolved in mock log',
  },
  {
    id: 'SYN-STU-0204',
    name: 'Carla Cruz',
    role: 'Student',
    status: 'Review needed',
    context: 'BSBIO 1-B',
    lastActive: '21 Oct 2026 · 6:40 PM PHT',
    supportNote: 'Synthetic duplicate-registration review',
  },
  {
    id: 'SYN-STU-0221',
    name: 'Noel Garcia',
    role: 'Student',
    status: 'Inactive',
    context: 'BSES 2-A',
    lastActive: '03 Oct 2026 · 9:05 AM PHT',
    supportNote: 'No action recommended without approved policy',
  },
  {
    id: 'SYN-INS-0007',
    name: 'Prof. Elena Reyes',
    role: 'Instructor',
    status: 'Active',
    context: 'Science faculty',
    lastActive: '22 Oct 2026 · 4:21 PM PHT',
    supportNote: 'Owns 4 synthetic quizzes',
  },
  {
    id: 'SYN-INS-0011',
    name: 'Dr. Paolo Santos',
    role: 'Instructor',
    status: 'Pending review',
    context: 'Research faculty',
    lastActive: '22 Oct 2026 · 10:14 AM PHT',
    supportNote: 'Role-verification policy remains unapproved',
  },
  {
    id: 'SYN-INS-0014',
    name: 'Prof. Ana Villanueva',
    role: 'Instructor',
    status: 'Active',
    context: 'Environmental Science',
    lastActive: '20 Oct 2026 · 2:36 PM PHT',
    supportNote: 'Owns 2 synthetic quizzes',
  },
  {
    id: 'SYN-ADM-0002',
    name: 'Admin Liza Mendoza',
    role: 'Administrator',
    status: 'Active',
    context: 'Academic operations',
    lastActive: '22 Oct 2026 · 4:24 PM PHT',
    supportNote: 'Prototype actor · permissions not production-approved',
  },
]

export const adminAssessments = [
  {
    id: 'ASM-SYN-104',
    title: 'Ecosystems checkpoint',
    course: 'BIO 104',
    section: 'BSBIO 1-A',
    instructor: 'Prof. Elena Reyes',
    status: 'Published',
    schedule: '22 Oct 2026 · 4:00–5:00 PM PHT',
    participation: '27 of 32 submitted',
    percent: 84,
  },
  {
    id: 'ASM-SYN-118',
    title: 'Cell division review',
    course: 'BIO 118',
    section: 'BSBIO 1-B',
    instructor: 'Prof. Elena Reyes',
    status: 'Scheduled',
    schedule: '24 Oct 2026 · 9:00–10:00 AM PHT',
    participation: '0 of 29 submitted',
    percent: 0,
  },
  {
    id: 'ASM-SYN-205',
    title: 'Watershed field concepts',
    course: 'ENV 205',
    section: 'BSES 2-A',
    instructor: 'Prof. Ana Villanueva',
    status: 'Draft',
    schedule: 'Not scheduled',
    participation: 'No attempts available',
    percent: 0,
  },
  {
    id: 'ASM-SYN-221',
    title: 'Research variables',
    course: 'RES 221',
    section: 'BSBIO 2-A',
    instructor: 'Dr. Paolo Santos',
    status: 'Ready for review',
    schedule: '28 Oct 2026 · 1:00–2:00 PM PHT',
    participation: 'Not open',
    percent: 0,
  },
  {
    id: 'ASM-SYN-090',
    title: 'Scientific measurement',
    course: 'SCI 090',
    section: 'STEM 12-C',
    instructor: 'Prof. Marco Lim',
    status: 'Closed',
    schedule: '18 Oct 2026 · 10:00–11:00 AM PHT',
    participation: '35 of 36 submitted',
    percent: 97,
  },
]

export const adminActivity = [
  {
    id: 'EVT-SYN-081',
    title: 'Instructor verification requested',
    actor: 'System-generated prototype event',
    time: '12 minutes ago',
    icon: 'shield',
    tone: 'warning',
  },
  {
    id: 'EVT-SYN-080',
    title: 'Assessment published by owner',
    actor: 'Prof. Elena Reyes · Ecosystems checkpoint',
    time: '34 minutes ago',
    icon: 'checkCircle',
    tone: 'success',
  },
  {
    id: 'EVT-SYN-079',
    title: 'Mock aggregate report previewed',
    actor: 'Admin Liza Mendoza · no personal grades included',
    time: 'Yesterday',
    icon: 'barChart',
    tone: 'primary',
  },
]

export const submissionDistribution = [
  { label: 'Submitted', value: 78, count: 96, tone: 'primary' },
  { label: 'In progress', value: 15, count: 18, tone: 'highlight' },
  { label: 'Not started', value: 7, count: 9, tone: 'muted' },
]

export const weeklyActivity = [
  { label: 'Mon', value: 38 },
  { label: 'Tue', value: 61 },
  { label: 'Wed', value: 48 },
  { label: 'Thu', value: 82 },
  { label: 'Fri', value: 67 },
  { label: 'Sat', value: 24 },
  { label: 'Sun', value: 15 },
]

export const adminHelpTopics = [
  {
    title: 'Understand the administrator scope',
    summary: 'Why account and assessment access is intentionally limited.',
    steps: [
      'Use administrative views only for an approved operational purpose.',
      'Treat every management action as provisional until decision D-15 is approved.',
      'Do not use a generic administrator screen to retrieve answer keys or private grades.',
    ],
  },
  {
    title: 'Review an account support case',
    summary: 'Inspect minimum necessary synthetic account context.',
    steps: [
      'Search by synthetic name or account reference.',
      'Review status and the support note without impersonating the user.',
      'Preview the proposed action and record a reason in the future production workflow.',
    ],
  },
  {
    title: 'Review assessment operations',
    summary: 'See ownership, schedule, state, and participation at a glance.',
    steps: [
      'Filter by lifecycle state or search by title, course, or instructor.',
      'Open the assessment preview for operational context.',
      'Contact the owning instructor when an action exceeds the approved admin scope.',
    ],
  },
  {
    title: 'Use aggregate reports safely',
    summary: 'Preview trends without exposing private learner results.',
    steps: [
      'Choose a date range that matches the documented reporting purpose.',
      'Use aggregate counts instead of individual grades whenever possible.',
      'Treat mock exports as portable sensitive artifacts in production planning.',
    ],
  },
]
