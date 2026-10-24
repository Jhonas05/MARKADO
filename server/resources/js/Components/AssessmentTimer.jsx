import Icon from '@prototype/components/Icon.jsx'

function formatRemaining(milliseconds) {
  const seconds = Math.max(0, Math.ceil(milliseconds / 1000))
  const minutes = Math.floor(seconds / 60)
  const remainder = seconds % 60
  return `${String(minutes).padStart(2, '0')}:${String(remainder).padStart(2, '0')}`
}

export default function AssessmentTimer({ label, remaining }) {
  if (remaining === null) return null
  const urgent = remaining <= 60_000

  return (
    <div className={urgent ? 'assessment-timer assessment-timer--urgent' : 'assessment-timer'} role="timer" aria-live={urgent ? 'polite' : 'off'}>
      <Icon name="clock" size={18} />
      <span>{label}</span>
      <strong>{formatRemaining(remaining)}</strong>
    </div>
  )
}
