import { useState } from 'react'
import Icon from '@prototype/components/Icon.jsx'

const trustedArtwork = Object.freeze({
  byte: Object.freeze({ primary: '/characters/byte-v2.png', fallback: '/characters/byte.svg' }),
  mochi: Object.freeze({ primary: '/characters/mochi-v2.png', fallback: '/characters/mochi.svg' }),
  pixel: Object.freeze({ primary: '/characters/pixel-v2.png', fallback: '/characters/pixel.svg' }),
})

export default function CharacterArtwork({ character, className = '', decorative = false }) {
  const slug = character?.slug?.toLowerCase() || ''
  const name = character?.name || 'MARKADO character'
  const sources = trustedArtwork[slug]
  const characterKey = character?.id || name
  const sourceKey = sources ? `${characterKey}:${slug}:${sources.primary}:${sources.fallback}` : `${characterKey}:unknown:${slug}`
  const [artworkState, setArtworkState] = useState({
    sourceKey,
    stage: sources ? 'primary' : 'placeholder',
  })
  const stage = artworkState.sourceKey === sourceKey
    ? artworkState.stage
    : sources ? 'primary' : 'placeholder'
  const classes = ['character-artwork', className].filter(Boolean).join(' ')
  const accessibleName = `${name}, an original MARKADO character`

  if (!sources || stage === 'placeholder') {
    return (
      <span
        aria-hidden={decorative ? 'true' : undefined}
        aria-label={decorative ? undefined : `${accessibleName}; artwork unavailable`}
        className={`${classes} character-artwork--placeholder`}
        data-artwork-state="placeholder"
        role={decorative ? undefined : 'img'}
      >
        <Icon name="spark" size={30} />
        <span className="character-artwork__placeholder-name">{name}</span>
        <span className="character-artwork__placeholder-note">Artwork unavailable</span>
      </span>
    )
  }

  const source = stage === 'primary' ? sources.primary : sources.fallback

  return (
    <img
      alt={decorative ? '' : accessibleName}
      aria-hidden={decorative ? 'true' : undefined}
      className={classes}
      data-artwork-state={stage}
      onError={() => setArtworkState({ sourceKey, stage: stage === 'primary' ? 'fallback' : 'placeholder' })}
      src={source}
    />
  )
}
