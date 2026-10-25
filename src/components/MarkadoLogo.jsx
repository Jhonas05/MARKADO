import symbolSource from '../../server/public/branding/markado-symbol.png'
import wordmarkSource from '../../server/public/branding/markado-logo-transparent.png'

const sources = {
  symbol: {
    height: 320,
    src: symbolSource,
    width: 320,
  },
  wordmark: {
    height: 272,
    src: wordmarkSource,
    width: 1561,
  },
}

export default function MarkadoLogo({
  className = '',
  decorative = false,
  label = 'MARKADO',
  size = 'medium',
  variant = 'wordmark',
}) {
  const source = sources[variant] ?? sources.wordmark
  const customSize = typeof size === 'number' ? { '--markado-logo-size': `${size}px` } : undefined
  const sizeClass = typeof size === 'string' ? ` markado-logo--${size}` : ''

  return (
    <span
      aria-hidden={decorative ? 'true' : undefined}
      className={`markado-logo markado-logo--${variant}${sizeClass}${className ? ` ${className}` : ''}`}
      style={customSize}
    >
      <span className="markado-logo__surface">
        <img
          alt={decorative ? '' : label}
          className="markado-logo__image"
          decoding="async"
          draggable="false"
          height={source.height}
          src={source.src}
          width={source.width}
        />
      </span>
    </span>
  )
}
