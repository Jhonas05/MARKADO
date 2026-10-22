import Icon from '@prototype/components/Icon.jsx'

export default function FoundationNotice({ framework, persistence }) {
  return (
    <div className="prototype-notice foundation-notice" role="note">
      <Icon name="spark" size={17} />
      <p>
        <strong>Laravel + Inertia foundation demo.</strong> Served by {framework} with
        synthetic props. {persistence}.
      </p>
    </div>
  )
}
