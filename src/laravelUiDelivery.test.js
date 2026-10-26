import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import process from 'node:process'
import { describe, expect, it } from 'vitest'

const read = (path) => readFileSync(resolve(process.cwd(), path), 'utf8')

const studentJourney = [
  'server/resources/js/pages/Student/Workspace.jsx',
  'server/resources/js/pages/Student/Quizzes/Join.jsx',
  'server/resources/js/pages/Student/Quizzes/Preflight.jsx',
  'server/resources/js/pages/Student/Quizzes/Assessment.jsx',
  'server/resources/js/Layouts/StudentShell.jsx',
].map(read).join('\n')

const professorJourney = [
  'server/resources/js/pages/Professor/Quizzes/Index.jsx',
  'server/resources/js/pages/Professor/Quizzes/Create.jsx',
  'server/resources/js/pages/Professor/Quizzes/Edit.jsx',
  'server/resources/js/pages/Professor/Quizzes/Results.jsx',
  'server/resources/js/Layouts/ProfessorLayout.jsx',
  'server/resources/js/Components/ProfessorUi.jsx',
].map(read).join('\n')

const authJourney = [
  'server/resources/js/pages/Welcome.jsx',
  'server/resources/js/pages/Auth/Login.jsx',
  'server/resources/js/pages/Auth/Register.jsx',
  'server/resources/js/pages/Auth/PolicyGate.jsx',
].map(read).join('\n')

describe('Laravel/Inertia production UI delivery', () => {
  it('keeps role logout discoverable across desktop and mobile shells', () => {
    const studentShell = read('server/resources/js/Layouts/StudentShell.jsx')
    const professorShell = read('server/resources/js/Layouts/ProfessorLayout.jsx')
    const routes = read('server/routes/web.php')

    expect(professorShell).toContain('prof-sidebar__signout')
    expect(professorShell).toContain('method="delete"')
    expect(studentShell).toContain('student-mobile-signout')
    expect(studentShell).toContain('method="delete"')
    expect(routes).toContain("Route::delete('/logout'")
    expect(routes).not.toContain("Route::get('/logout'")
  })

  it('uses fluid responsive reward layouts and explicit pre-paint theme classes', () => {
    const styles = read('server/resources/css/foundation.css')
    const blade = read('server/resources/views/app.blade.php')

    expect(styles).toContain('minmax(min(100%, 260px), 1fr)')
    expect(styles).toContain('@media (max-width: 390px)')
    expect(styles).toContain('.prof-main { max-width: none; margin-inline: auto; }')
    expect(blade).toContain("root.classList.toggle('light', !dark)")
  })

  it('keeps the synthetic foundation dashboard off public routes', () => {
    const routes = read('server/routes/web.php')

    expect(routes).not.toContain("Route::get('/foundation'")
    expect(routes).not.toContain('StudentDashboardController')
  })

  it('uses user-facing copy instead of phase and implementation language', () => {
    const routedCopy = [studentJourney, professorJourney, authJourney].join('\n')

    expect(routedCopy).not.toMatch(/Phase 5C|database-backed|D16 approved|D22 school-managed|PostgreSQL|development workflow|server-controlled activation gate/i)
    expect(routedCopy).not.toMatch(/Student UI prototype|Professor UI prototype|Administrator UI prototype/i)
  })

  it('does not embed fabricated student records or academic metrics in live journeys', () => {
    const routedCopy = [studentJourney, professorJourney].join('\n')

    expect(routedCopy).not.toMatch(/Mika Santos|Lorenzo Ruiz|Maria Cruz|87%|92%|1st place/i)
  })

  it('preserves real quiz actions without exposing cross-role shortcuts', () => {
    expect(professorJourney).toContain("form.post('/professor/quizzes')")
    expect(professorJourney).toContain("router.post(`/professor/quizzes/${quiz.id}/publish`")
    expect(professorJourney).toContain('questionForm.post(quiz.authoring.questionsPath')
    expect(professorJourney).toContain('quiz.resultsPath ?')
    expect(professorJourney).not.toContain('Student area')
  })

  it('keeps MARKADO branding and theme controls across role entry points', () => {
    for (const journey of [studentJourney, professorJourney, authJourney]) {
      expect(journey).toContain('MarkadoLogo')
      expect(journey).toContain('ThemeToggle')
    }
  })
})
