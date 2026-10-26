import { Link, usePage } from '@inertiajs/react'
import Icon from '@prototype/components/Icon.jsx'
import CharacterCard from '../../../Components/CharacterCard.jsx'
import StudentQuizLayout from '../../../Layouts/StudentQuizLayout.jsx'

export default function Collection({ economy }) {
  const { errors } = usePage().props
  const owned = economy.characters.filter((character) => character.owned)
  const locked = economy.characters.filter((character) => !character.owned)

  return (
    <StudentQuizLayout active="collection" title="Character collection">
      <main className="gamification-page" id="main-content">
        <header className="gamification-hero">
          <div><p className="section-kicker">Character collection</p><h1>Your study companions</h1><p>Characters are cosmetic rewards. They never change quiz scores, timers, or access.</p></div>
          <div className="wallet-card" aria-label={`${economy.balance} MARKADO Coins`}><Icon name="medal" size={22} /><span><strong>{economy.balance}</strong> MARKADO Coins</span></div>
        </header>
        {errors?.equip ? <div className="gamification-alert" role="alert">{errors.equip}</div> : null}
        <section className="equipped-card panel" aria-labelledby="equipped-title">
          <div><p className="section-kicker">Currently equipped</p><h2 id="equipped-title">{economy.equippedCharacter?.name || 'Choose a companion'}</h2><p>{economy.equippedCharacter?.description || 'Equip one of your owned characters below.'}</p></div>
          {economy.equippedCharacter ? <img src={economy.equippedCharacter.artworkPath} alt="" /> : null}
          <Link className="button button--secondary" href="/student/characters/shop">Open character shop</Link>
        </section>
        <section className="character-section" aria-labelledby="owned-title">
          <div className="character-section__heading"><h2 id="owned-title">Owned characters</h2><span>{owned.length} collected</span></div>
          <div className="character-grid">{owned.map((character) => <CharacterCard character={character} key={character.id} mode="collection" />)}</div>
        </section>
        {locked.length ? <section className="character-section" aria-labelledby="locked-title"><div className="character-section__heading"><h2 id="locked-title">Still to collect</h2><span>Available in Shop</span></div><div className="character-grid character-grid--locked">{locked.map((character) => <CharacterCard character={character} key={character.id} mode="collection" />)}</div></section> : null}
      </main>
    </StudentQuizLayout>
  )
}
