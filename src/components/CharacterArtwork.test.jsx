import { fireEvent, render, screen } from '@testing-library/react'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import CharacterArtwork from '../../server/resources/js/Components/CharacterArtwork.jsx'
import CharacterCard from '../../server/resources/js/Components/CharacterCard.jsx'

const { post } = vi.hoisted(() => ({ post: vi.fn() }))

vi.mock('@inertiajs/react', () => ({
  router: { post },
}))

const byte = {
  id: 'byte-id',
  slug: 'byte',
  name: 'Byte',
  description: 'A bright starter companion.',
  price: 0,
  owned: false,
  equipped: false,
  artworkPath: 'https://untrusted.example/ignored.png',
}

const mochi = {
  id: 'mochi-id',
  slug: 'mochi',
  name: 'Mochi',
  description: 'A calm focus companion.',
  price: 10,
  owned: true,
  equipped: false,
}

describe('CharacterArtwork', () => {
  beforeEach(() => post.mockReset())

  it('renders the trusted v2 PNG with an accessible label', () => {
    render(<CharacterArtwork character={byte} />)

    const image = screen.getByRole('img', { name: 'Byte, an original MARKADO character' })
    expect(image).toHaveAttribute('src', '/characters/byte-v2.png')
    expect(image).toHaveAttribute('data-artwork-state', 'primary')
  })

  it('switches once from a failed PNG to the matching local SVG', () => {
    render(<CharacterArtwork character={byte} />)

    fireEvent.error(screen.getByRole('img'))

    expect(screen.getByRole('img')).toHaveAttribute('src', '/characters/byte.svg')
    expect(screen.getByRole('img')).toHaveAttribute('data-artwork-state', 'fallback')
  })

  it('shows a visible accessible placeholder when both local images fail', () => {
    render(<CharacterArtwork character={byte} />)

    fireEvent.error(screen.getByRole('img'))
    fireEvent.error(screen.getByRole('img'))

    expect(screen.queryByRole('img', { name: 'Byte, an original MARKADO character' })).not.toBeInTheDocument()
    expect(screen.getByRole('img', { name: 'Byte, an original MARKADO character; artwork unavailable' })).toHaveAttribute('data-artwork-state', 'placeholder')
    expect(screen.getByText('Byte')).toBeVisible()
    expect(screen.getByText('Artwork unavailable')).toBeVisible()
  })

  it('uses a placeholder immediately for an unknown slug without retrying arbitrary URLs', () => {
    render(<CharacterArtwork character={{ ...byte, slug: 'unknown', name: 'Unknown' }} />)

    expect(screen.queryByRole('img', { name: 'Unknown, an original MARKADO character' })).not.toBeInTheDocument()
    expect(screen.getByRole('img', { name: /Unknown.*artwork unavailable/ })).toHaveAttribute('data-artwork-state', 'placeholder')
    expect(document.querySelector('img')).not.toBeInTheDocument()
  })

  it('resets failed artwork when the character changes', () => {
    const { rerender } = render(<CharacterArtwork character={byte} />)
    fireEvent.error(screen.getByRole('img'))
    expect(screen.getByRole('img')).toHaveAttribute('src', '/characters/byte.svg')

    rerender(<CharacterArtwork character={mochi} />)

    expect(screen.getByRole('img', { name: 'Mochi, an original MARKADO character' })).toHaveAttribute('src', '/characters/mochi-v2.png')
  })

  it('keeps decorative artwork out of the accessibility tree', () => {
    const { container } = render(<CharacterArtwork character={byte} decorative />)

    expect(screen.queryByRole('img')).not.toBeInTheDocument()
    expect(container.querySelector('img')).toHaveAttribute('alt', '')
    expect(container.querySelector('img')).toHaveAttribute('aria-hidden', 'true')
  })
})

describe('CharacterCard artwork failure isolation', () => {
  beforeEach(() => post.mockReset())

  it('keeps the Shop purchase action usable after both artwork sources fail', () => {
    render(<CharacterCard balance={10} canAfford character={{ ...mochi, owned: false }} mode="shop" />)
    fireEvent.error(screen.getByRole('img'))
    fireEvent.error(screen.getByRole('img'))
    fireEvent.click(screen.getByRole('button', { name: 'Unlock for 10 coins' }))

    expect(post).toHaveBeenCalledWith('/student/characters/mochi-id/purchase', {}, expect.objectContaining({ preserveScroll: true }))
  })

  it('keeps the Collection equip action usable after both artwork sources fail', () => {
    render(<CharacterCard character={mochi} mode="collection" />)
    fireEvent.error(screen.getByRole('img'))
    fireEvent.error(screen.getByRole('img'))
    fireEvent.click(screen.getByRole('button', { name: 'Equip Mochi' }))

    expect(post).toHaveBeenCalledWith('/student/characters/mochi-id/equip', {}, expect.objectContaining({ preserveScroll: true }))
  })

  it('preserves ownership and trusted catalog pricing controls', () => {
    const { rerender } = render(<CharacterCard balance={0} canAfford={false} character={{ ...mochi, owned: false }} mode="shop" />)
    expect(screen.getByRole('button', { name: 'Need 10 more coins' })).toBeDisabled()

    rerender(<CharacterCard balance={100} canAfford character={{ ...mochi, owned: true }} mode="shop" />)
    expect(screen.getByRole('button', { name: 'In your collection' })).toBeDisabled()
    expect(post).not.toHaveBeenCalled()
  })
})
