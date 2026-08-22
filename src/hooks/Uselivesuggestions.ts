import { useQuery } from '@tanstack/react-query'
import { BASE_URL } from '../lib/constants'

export interface LiveSuggestion {
  username: string
  nickname: string
  avatar: string
  cover: string
  viewers: number
  title: string
  followers: number
  verified: boolean
}

const API_URL = `${BASE_URL}/suggestion_search.php`

async function fetchLiveSuggestions(keyword: string): Promise<LiveSuggestion[]> {
  const response = await fetch(API_URL, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ keyword }),
  })

  if (!response.ok) throw new Error('Erreur réseau')

  const data = await response.json()
  if (!data.success) throw new Error(data.error || 'Erreur inconnue')

  return data.suggestions as LiveSuggestion[]
}

export function useLiveSuggestions(keyword: string, enabled: boolean) {
  return useQuery({
    queryKey: ['live-suggestions', keyword],
    queryFn: () => fetchLiveSuggestions(keyword),
    enabled: enabled && keyword.trim().length > 0,
    staleTime: 60_000,   // 1 min — les lives changent vite
    retry: 1,
  })
}