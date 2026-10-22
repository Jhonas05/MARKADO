export default function BrandMark({ size = 40 }) {
  return (
    <svg
      aria-hidden="true"
      className="brand-mark"
      focusable="false"
      height={size}
      viewBox="0 0 40 40"
      width={size}
    >
      <rect width="40" height="40" rx="12" fill="var(--primary)" />
      <path
        d="m10.5 19.5 6.2 6.1L29.8 12.8"
        fill="none"
        stroke="var(--on-primary)"
        strokeLinecap="round"
        strokeLinejoin="round"
        strokeWidth="4"
      />
      <path
        d="M9.5 31.1c6.7-1.1 13.5-1.1 20.5-.1"
        fill="none"
        stroke="var(--highlight)"
        strokeLinecap="round"
        strokeWidth="3.5"
      />
    </svg>
  )
}
