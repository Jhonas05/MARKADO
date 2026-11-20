import assert from 'node:assert/strict'
import { randomUUID } from 'node:crypto'
import { createRequire } from 'node:module'
import process from 'node:process'

function loopbackOrigin(value, fallback) {
  const url = new URL(value || fallback)
  assert.equal(url.hostname, '127.0.0.1', 'Use an isolated loopback service')
  assert.equal(url.protocol, 'http:')
  assert.equal(url.username + url.password, '')
  return url.origin
}

const app = loopbackOrigin(process.env.MARKADO_SIGNUP_QA_URL, 'http://127.0.0.1:8037')
const mailpit = loopbackOrigin(process.env.MARKADO_SIGNUP_QA_MAILPIT, 'http://127.0.0.1:8025')
const require = createRequire(import.meta.url)
const { chromium } = require(process.env.MARKADO_PLAYWRIGHT_MODULE || 'playwright-core')
const browser = await chromium.launch({ executablePath: process.env.MARKADO_CHROME, headless: true })

const identity = randomUUID()
const email = `signup-${identity}@students.example.edu`
const firstPassword = `First-Synthetic-${randomUUID()}`
const ownerPassword = `Owner-Synthetic-${randomUUID()}`
const signupLink = 'Create Student Account'

async function signup(page, from, number, password) {
  await page.goto(app + from)
  await page.getByRole('link', { name: signupLink, exact: true }).click()
  await page.getByRole('heading', { name: 'Create your Student account' }).waitFor()
  assert.equal(new URL(page.url()).origin, app)
  await page.getByLabel('Official student number').fill(number)
  await page.getByLabel('Institutional email address').fill(email)
  await page.getByLabel('Password', { exact: true }).fill(password)
  await page.getByLabel('Confirm password', { exact: true }).fill(password)
  await page.getByRole('button', { name: 'Create account', exact: true }).click()
  await page.waitForURL(app + '/register/verification')
}

async function inboxCode() {
  for (let attempt = 0; attempt < 20; attempt += 1) {
    const response = await fetch(mailpit + '/api/v1/messages')
    assert.equal(response.ok, true)
    const inbox = await response.json()
    const mail = inbox.messages.find((message) => message.To?.some((recipient) => recipient.Address === email))
    if (mail) {
      const contents = await (await fetch(mailpit + '/api/v1/message/' + mail.ID)).json()
      const code = contents.Text.match(/one-time verification code:\s*([A-Za-z0-9_-]{43})/i)?.[1]
      if (code) return code
    }
    await new Promise((resolve) => setTimeout(resolve, 250))
  }
  throw new Error('Verification did not reach the synthetic mailbox')
}

async function login(page, password) {
  await page.goto(app + '/login')
  await page.getByLabel('Email address').fill(email)
  await page.getByLabel('Password', { exact: true }).fill(password)
  await page.getByRole('button', { name: 'Sign in', exact: true }).click()
}

try {
  const first = await browser.newPage({ viewport: { width: 1440, height: 900 } })
  await signup(first, '/', 'SYN-FIRST-' + identity, firstPassword)
  const code = await inboxCode()
  await first.goto(app + '/student/dashboard')
  await first.waitForURL(app + '/login')

  const owner = await browser.newPage({ viewport: { width: 390, height: 844 } })
  await signup(owner, '/login', 'SYN-OWNER-' + identity, ownerPassword)
  await owner.getByRole('heading', { name: 'Check your school email' }).waitFor()
  await owner.getByLabel('Verification code').fill(code)
  await owner.getByRole('button', { name: 'Verify email and activate' }).click()
  await owner.waitForURL(app + '/login')
  await owner.getByRole('status').filter({ hasText: 'Your Student account is active' }).waitFor()
  await login(owner, ownerPassword)
  await owner.waitForURL(app + '/student/dashboard')
  await owner.getByRole('heading', { name: 'School verification required' }).waitFor()
  await owner.reload()
  await owner.getByRole('heading', { name: 'Your quizzes start here' }).waitFor()
  await owner.close()

  // A fresh session confirms the account persisted independently of signup cookies.
  const repeat = await browser.newPage({ viewport: { width: 768, height: 900 } })
  await login(repeat, ownerPassword)
  await repeat.waitForURL(app + '/student/dashboard')
  await repeat.getByRole('heading', { name: 'School verification required' }).waitFor()
  await login(first, firstPassword)
  await first.getByRole('alert').filter({ hasText: 'These credentials do not match' }).waitFor()

  for (const width of [1440, 768, 390]) {
    const page = await browser.newPage({ viewport: { width, height: 900 } })
    for (const [path, heading] of [
      ['/', 'Focus on the work. Trust the assessment.'],
      ['/login', 'Sign in to your workspace'],
      ['/register', 'Create your Student account'],
      ['/forgot-password', 'Recovery is currently unavailable'],
    ]) {
      await page.goto(app + path)
      await page.getByRole('heading', { name: heading }).waitFor()
      assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false, `Overflow at ${path} / ${width}px`)
      if (path === '/forgot-password') assert.equal(await page.locator('input').count(), 0)
    }
    await page.close()
  }
  process.stdout.write('Chrome signup, mailbox ownership, fresh login, refresh and responsive checks passed.\n')
} finally {
  await browser.close()
}
