import { router } from '@inertiajs/react'
import Icon from '@prototype/components/Icon.jsx'

export default function CharacterCard({ character, mode, canAfford = false }) {
  const action = mode === 'shop' ? 'purchase' : 'equip'
  const disabled = mode === 'shop' ? character.owned || !canAfford : !character.owned || character.equipped
  const label = mode === 'shop'
    ? character.owned ? 'Owned' : canAfford ? `Unlock for ${character.price}` : `Need ${character.price} coins`
    : character.equipped ? 'Equipped' : character.owned ? 'Equip character' : 'Locked'

  return (
    <article className={`character-card${character.equipped ? ' character-card--equipped' : ''}`}>
      <div className="character-card__art">
        <img src={character.artworkPath} alt={`${character.name}, an original MARKADO character`} />
        {character.equipped ? <span><Icon name="check" size={14} /> Equipped</span> : null}
      </div>
      <div className="character-card__body">
        <div className="character-card__heading"><h2>{character.name}</h2><span className="coin-price"><Icon name="medal" size={16} /> {character.price}</span></div>
        <p>{character.description}</p>
        <button className="button button--primary character-card__action" type="button" disabled={disabled} onClick={() => router.post(`/student/characters/${character.id}/${action}`, {}, { preserveScroll: true })}>{label}</button>
      </div>
    </article>
  )
}
