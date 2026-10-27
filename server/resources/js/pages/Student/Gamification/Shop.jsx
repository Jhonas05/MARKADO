import { Link, usePage } from '@inertiajs/react'
import Icon from '@prototype/components/Icon.jsx'
import CharacterCard from '../../../Components/CharacterCard.jsx'
import StudentQuizLayout from '../../../Layouts/StudentQuizLayout.jsx'

export default function Shop({ economy }) {
  const { errors } = usePage().props
  const ownedCount = economy.characters.filter((character) => character.owned).length

  return (
    <StudentQuizLayout active="shop" title="Character shop">
      <main className="gamification-page" id="main-content">
        <header className="gamification-hero gamification-hero--shop">
          <div className="gamification-hero__copy"><p className="section-kicker">Character shop</p><h1>Meet your next study companion</h1><p>Collect original MARKADO characters with coins earned for participation. Your grades and speed never affect rewards.</p></div>
          <div className="wallet-card" aria-label={`${economy.balance} MARKADO Coins`}><span className="wallet-card__icon"><Icon name="medal" size={24} /></span><span><small>Available balance</small><strong>{economy.balance}</strong><small>MARKADO Coins</small></span></div>
        </header>
        {errors?.purchase ? <div className="gamification-alert" role="alert">{errors.purchase}</div> : null}
        <section className="reward-loop" aria-labelledby="reward-loop-title">
          <div className="reward-loop__heading"><p className="section-kicker">How it works</p><h2 id="reward-loop-title">A small reward for showing up</h2></div>
          <ol>
            <li><span><Icon name="checkCircle" size={19} /></span><div><strong>Complete</strong><small>Submit an eligible quiz</small></div></li>
            <li><span><Icon name="medal" size={19} /></span><div><strong>Earn</strong><small>Receive {economy.completionReward} coins once</small></div></li>
            <li><span><Icon name="spark" size={19} /></span><div><strong>Collect</strong><small>Unlock a cosmetic companion</small></div></li>
          </ol>
        </section>
        <div className="character-shop-note" role="note"><Icon name="shield" size={19} /><p>Server-checked demo coins only—no real money, trading, rankings, or quiz advantage.</p><Link href="/student/characters/collection">View collection <Icon name="arrowRight" size={15} /></Link></div>
        <section className="character-section" aria-labelledby="shop-title">
          <div className="character-section__heading"><div><p className="section-kicker">Series one</p><h2 id="shop-title">MARKADO originals</h2></div><span>{ownedCount} of {economy.characters.length} collected</span></div>
          <div className="character-grid">{economy.characters.map((character) => <CharacterCard balance={economy.balance} canAfford={economy.balance >= character.price} character={character} key={character.id} mode="shop" />)}</div>
        </section>
      </main>
    </StudentQuizLayout>
  )
}
