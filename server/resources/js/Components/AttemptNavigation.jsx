export default function AttemptNavigation({ items }) {
  return (
    <ol className="attempt-navigation" aria-label="Question progress">
      {items.map((item) => (
        <li
          className={[
            item.current ? 'attempt-navigation__item--current' : '',
            item.answered ? 'attempt-navigation__item--answered' : '',
            item.status === 'locked' ? 'attempt-navigation__item--locked' : '',
          ].filter(Boolean).join(' ')}
          aria-current={item.current ? 'step' : undefined}
          key={item.position}
        >
          <span>{item.position}</span>
          <small>{item.current ? 'Current' : item.answered ? 'Saved' : item.status}</small>
        </li>
      ))}
    </ol>
  )
}
