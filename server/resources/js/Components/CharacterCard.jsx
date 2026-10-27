import { router } from '@inertiajs/react'
import { useState } from 'react'
import Icon from '@prototype/components/Icon.jsx'
import CharacterArtwork from './CharacterArtwork.jsx'

const characterDetails = {
  byte: { edition: 'Starter companion', accent: 'mint' },
  mochi: { edition: 'Focus companion', accent: 'lavender' },
  pixel: { edition: 'Signature companion', accent: 'indigo' },
}

export default function CharacterCard({ balance = 0, character, mode, canAfford = false }) {
  const [processing, setProcessing] = useState(false)
  const action = mode === 'shop' ? 'purchase' : 'equip'
  const disabled = mode === 'shop' ? character.owned || !canAfford : !character.owned || character.equipped
  const details = characterDetails[character.slug] ?? { edition: 'MARKADO companion', accent: 'mint' }
  const state = character.equipped
    ? 'equipped'
    : character.owned
      ? 'owned'
      : mode === 'shop' && canAfford
        ? 'available'
        : mode === 'shop'
          ? 'insufficient'
          : 'locked'
  const status = {
    equipped: { icon: 'check', label: 'Equipped' },
    owned: { icon: 'checkCircle', label: 'Owned' },
    available: { icon: 'spark', label: 'Ready to unlock' },
    insufficient: { icon: 'lock', label: 'More coins needed' },
    locked: { icon: 'lock', label: 'Not collected' },
  }[state]
  const buttonLabel = processing
    ? action === 'purchase' ? 'Unlocking…' : 'Equipping…'
    : mode === 'shop'
      ? character.owned ? 'In your collection' : canAfford ? character.price === 0 ? 'Unlock for free' : `Unlock for ${character.price} coins` : `Need ${character.price - balance} more coins`
      : character.equipped ? 'Currently equipped' : character.owned ? `Equip ${character.name}` : 'Visit Shop to unlock'
  const descriptionId = `character-${character.slug}-description`

  function submitAction() {
    if (disabled || processing) return
    setProcessing(true)
    router.post(`/student/characters/${character.id}/${action}`, {}, {
      preserveScroll: true,
      onFinish: () => setProcessing(false),
    })
  }

  return (
    <article className={`character-card character-card--${state} character-card--${details.accent}`} data-character-state={state}>
      <div className="character-card__art">
        <CharacterArtwork character={character} />
        <span className="character-card__status"><Icon name={status.icon} size={14} /> {status.label}</span>
      </div>
      <div className="character-card__body">
        <div className="character-card__identity">
          <p className="character-card__edition">{details.edition}</p>
          <div className="character-card__heading">
            <h2>{character.name}</h2>
            <span className="coin-price"><Icon name="medal" size={16} /> {character.price === 0 ? 'Free' : character.price}</span>
          </div>
        </div>
        <p id={descriptionId}>{character.description}</p>
        {state === 'insufficient' ? <p className="character-card__balance-note">Balance: {balance} coins</p> : null}
        <button
          aria-describedby={descriptionId}
          className={`button ${state === 'available' || (mode === 'collection' && character.owned && !character.equipped) ? 'button--primary' : 'button--secondary'} character-card__action`}
          type="button"
          disabled={disabled || processing}
          onClick={submitAction}
        >
          {buttonLabel}
        </button>
      </div>
    </article>
  )
}
