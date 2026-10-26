import { Link, usePage } from '@inertiajs/react'
import Icon from '@prototype/components/Icon.jsx'
import CharacterCard from '../../../Components/CharacterCard.jsx'
import StudentQuizLayout from '../../../Layouts/StudentQuizLayout.jsx'

export default function Shop({ economy }) {
  const { errors } = usePage().props
  return (
    <StudentQuizLayout active="shop" title="Character shop">
      <main className="gamification-page" id="main-content">
        <header className="gamification-hero">
          <div><p className="section-kicker">Character shop</p><h1>Unlock a new companion</h1><p>Complete an eligible quiz to earn {economy.completionReward} demo coins. Rewards are based on participation, never grades or speed.</p></div>
          <div className="wallet-card" aria-label={`${economy.balance} MARKADO Coins`}><Icon name="medal" size={22} /><span><strong>{economy.balance}</strong> MARKADO Coins</span></div>
        </header>
        {errors?.purchase ? <div className="gamification-alert" role="alert">{errors.purchase}</div> : null}
        <div className="character-shop-note" role="note"><Icon name="shield" size={19} /><p>Prices and balances are checked by the server. No real money, trading, rankings, or gameplay advantage is involved.</p><Link href="/student/characters/collection">View collection</Link></div>
        <section className="character-section" aria-labelledby="shop-title">
          <div className="character-section__heading"><h2 id="shop-title">MARKADO originals</h2><span>{economy.characters.length} characters</span></div>
          <div className="character-grid">{economy.characters.map((character) => <CharacterCard canAfford={economy.balance >= character.price} character={character} key={character.id} mode="shop" />)}</div>
        </section>
      </main>
    </StudentQuizLayout>
  )
}
