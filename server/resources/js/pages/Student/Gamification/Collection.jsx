import { Link, usePage } from '@inertiajs/react'
import Icon from '@prototype/components/Icon.jsx'
import CharacterCard from '../../../Components/CharacterCard.jsx'
import CharacterArtwork from '../../../Components/CharacterArtwork.jsx'
import StudentQuizLayout from '../../../Layouts/StudentQuizLayout.jsx'

export default function Collection({ economy }) {
  const { errors } = usePage().props
  const owned = economy.characters.filter((character) => character.owned)
  const locked = economy.characters.filter((character) => !character.owned)
  const collectionProgress = economy.characters.length ? Math.round((owned.length / economy.characters.length) * 100) : 0

  return (
    <StudentQuizLayout active="collection" title="Character collection">
      <main className="gamification-page" id="main-content">
        <header className="gamification-hero gamification-hero--collection">
          <div className="gamification-hero__copy"><p className="section-kicker">Character collection</p><h1>Your companion shelf</h1><p>Pick an owned character to join your dashboard. Companions are cosmetic and never change quiz scores, timers, or access.</p></div>
          <div className="wallet-card" aria-label={`${economy.balance} MARKADO Coins`}><span className="wallet-card__icon"><Icon name="medal" size={24} /></span><span><small>Available balance</small><strong>{economy.balance}</strong><small>MARKADO Coins</small></span></div>
        </header>
        {errors?.equip ? <div className="gamification-alert" role="alert">{errors.equip}</div> : null}
        <section className="equipped-card panel" aria-labelledby="equipped-title">
          <div className="equipped-card__art" aria-hidden="true">{economy.equippedCharacter ? <CharacterArtwork character={economy.equippedCharacter} decorative /> : <Icon name="spark" size={34} />}</div>
          <div className="equipped-card__copy"><span className="character-state-pill"><Icon name="check" size={14} /> Equipped companion</span><h2 id="equipped-title">{economy.equippedCharacter?.name || 'Choose a companion'}</h2><p>{economy.equippedCharacter?.description || 'Equip one of your owned characters below.'}</p></div>
          <Link className="button button--secondary" href="/student/characters/shop">Open Shop <Icon name="arrowRight" size={16} /></Link>
        </section>
        <section className="collection-progress" aria-labelledby="collection-progress-title">
          <div><p className="section-kicker">Collection progress</p><h2 id="collection-progress-title">{owned.length} of {economy.characters.length} companions collected</h2></div>
          <div className="collection-progress__track" role="progressbar" aria-label="Character collection progress" aria-valuemin="0" aria-valuemax={economy.characters.length} aria-valuenow={owned.length}><span style={{ width: `${collectionProgress}%` }} /></div>
        </section>
        <section className="character-section" aria-labelledby="owned-title">
          <div className="character-section__heading"><div><p className="section-kicker">Ready to equip</p><h2 id="owned-title">Your characters</h2></div><span>{owned.length} collected</span></div>
          <div className="character-grid">{owned.map((character) => <CharacterCard character={character} key={character.id} mode="collection" />)}</div>
        </section>
        {locked.length ? <section className="character-section" aria-labelledby="locked-title"><div className="character-section__heading"><div><p className="section-kicker">Discover next</p><h2 id="locked-title">Still to collect</h2></div><Link href="/student/characters/shop">Browse Shop <Icon name="arrowRight" size={15} /></Link></div><div className="character-grid character-grid--locked">{locked.map((character) => <CharacterCard character={character} key={character.id} mode="collection" />)}</div></section> : null}
      </main>
    </StudentQuizLayout>
  )
}
