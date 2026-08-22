import { clsx, type ClassValue } from 'clsx'
import { twMerge } from 'tailwind-merge'

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}

export function formatNumber(num: number): string {
  if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M'
  if (num >= 1000) return (num / 1000).toFixed(1) + 'K'
  return num.toString()
}

export function formatTime(seconds: number): string {
  const hrs = Math.floor(seconds / 3600)
  const mins = Math.floor((seconds % 3600) / 60)
  const secs = Math.floor(seconds % 60)
  return `${hrs.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
}

export function formatBytes(bytes: number): string {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

export async function resolveTikTokUrl(shortUrl: string): Promise<string> {
  try {
    // Utiliser un service de résolution ou fetch avec redirect
    await fetch(shortUrl, {
      method: 'HEAD',
      redirect: 'follow',
      mode: 'no-cors', // Pour éviter les erreurs CORS
    })
    
    // Si on a une réponse, essayer de récupérer l'URL finale
    // Note: En mode no-cors, on ne peut pas lire la réponse, 
    // donc on utilise une approche alternative
    
    return shortUrl
  } catch {
    return shortUrl
  }
}

/**
 * Extrait le username depuis différents formats d'URL TikTok
 * Gère: @username, tiktok.com/@username, vt.tiktok.com/xxx
 */
export function extractUsername(input: string): { 
  username: string | null
  isShortUrl: boolean
  originalInput: string 
} {
  const trimmed = input.trim()
  
  // Détecter URL courte TikTok
  const isShortUrl = /vt\.tiktok\.com|vm\.tiktok\.com|t\.tiktok\.com/i.test(trimmed)
  
  if (isShortUrl) {
    return {
      username: null, // Nécessite résolution
      isShortUrl: true,
      originalInput: trimmed
    }
  }
  
  // Format: https://www.tiktok.com/@username/live ou /video/...
  const fullUrlMatch = trimmed.match(/tiktok\.com\/@([^/?\s]+)/i)
  if (fullUrlMatch) {
    return {
      username: fullUrlMatch[1],
      isShortUrl: false,
      originalInput: trimmed
    }
  }
  
  // Format: @username
  if (trimmed.startsWith('@')) {
    return {
      username: trimmed.substring(1),
      isShortUrl: false,
      originalInput: trimmed
    }
  }
  
  // Format simple: username (sans @)
  if (/^[a-zA-Z0-9_.]+$/.test(trimmed)) {
    return {
      username: trimmed,
      isShortUrl: false,
      originalInput: trimmed
    }
  }
  
  return {
    username: null,
    isShortUrl: false,
    originalInput: trimmed
  }
}

/**
 * Valide si un username semble valide (format TikTok)
 */
export function isValidUsernameFormat(username: string): boolean {
  // TikTok usernames: 2-24 chars, lettres, chiffres, underscores, points
  return /^[a-zA-Z0-9_.]{2,24}$/.test(username)
}