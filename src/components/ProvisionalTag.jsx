import Icon from './Icon.jsx'

export default function ProvisionalTag({ decision = 'D-15', children }) {
  return (
    <span className="provisional-tag">
      <Icon name="alertCircle" size={15} />
      <span>{children}</span>
      <strong>{decision}</strong>
    </span>
  )
}
