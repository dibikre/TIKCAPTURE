export function normalizeTikTokUrl(input: string): string | null {
  const raw = input.trim()
  if (!raw) return null

  let url = raw

  if (/^https?:\/\/(vm|vt|t)\.tiktok\.com/i.test(url)) {
    return url
  }

  if (url.startsWith('/@')) {
    url = 'https://www.tiktok.com' + url
  }

  if (url.startsWith('@')) return null

  if (/^(www\.)?tiktok\.com/i.test(url)) {
    url = 'https://' + url
  }

  try {
    const parsed = new URL(url)
    if (!parsed.hostname.includes('tiktok.com')) return null

    const cleanUrl = parsed.origin + parsed.pathname

    if (!/\/video\/\d+/.test(cleanUrl)) return null

    return cleanUrl
  } catch {
    return null
  }
}

export function formatNumber(n: number): string {
  if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M'
  if (n >= 1_000)     return (n / 1_000).toFixed(1) + 'K'
  return String(n)
}

export function formatDuration(s: number): string {
  const m = Math.floor(s / 60)
  const sec = s % 60
  return `${m}:${sec.toString().padStart(2, '0')}`
}
