import { useMutation } from '@tanstack/react-query'
import type { SearchResult } from '../types'
import { extractUsername, isValidUsernameFormat} from '../lib/utils'

const API_URL = 'https://tikcapture.live/api_proxy.php'

interface SearchResponse extends SearchResult {
  error?: string
  notFound?: boolean
  suggestions?: string[]
}

interface SearchParams {
  username: string
  originalInput: string
}

async function searchTikTokUser({ username, originalInput }: SearchParams): Promise<SearchResponse> {
  const response = await fetch(API_URL, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ 
      action: 'search', 
      username,
      original_input: originalInput // Pour debug/logging côté serveur
    }),
  })
  
  if (!response.ok) {
    throw new Error(`Erreur serveur: ${response.status}`)
  }
  
  const data = await response.json()
  
  // Gestion des erreurs spécifiques
  if (data.error) {
    // Utilisateur non trouvé
    if (data.error.includes('non trouvé') || data.error.includes('not found')) {
      return {
        ...data,
        notFound: true,
        suggestions: data.suggestions || [],
      }
    }
    throw new Error(data.error)
  }
  
  if (!data.success) {
    throw new Error('Réponse invalide du serveur')
  }

  // Utilisateur introuvable : TikTok retourne CurrentRoom vide (roomInfo: null)
  // ce qui donne un uniqueId vide dans la réponse formatée
  if (!data.user?.uniqueId) {
    return {
      ...data,
      notFound: true,
      suggestions: [],
    }
  }

  return data
}

/**
 * Hook principal avec gestion des URLs courtes et validation
 */
export function useTikTokSearch() {
  return useMutation({
    mutationFn: async (input: string) => {
      const extracted = extractUsername(input)
      
      // Cas 1: URL courte - nécessite résolution
      if (extracted.isShortUrl) {
        return searchTikTokUser({ 
          username: extracted.originalInput, 
          originalInput: input 
        })
      }
      
      // Cas 2: Username invalide
      if (!extracted.username) {
        throw new Error('Format invalide. Utilisez: @username, tiktok.com/@username, ou username')
      }
      
      // Cas 3: Format de username suspect
      if (!isValidUsernameFormat(extracted.username)) {
        throw new Error('Username invalide. Lettres, chiffres, underscores et points uniquement (2-24 caractères)')
      }
      
      return searchTikTokUser({ 
        username: extracted.username, 
        originalInput: input 
      })
    },
    retry: 0, // Pas de retry automatique pour les erreurs utilisateur
  })
}

/**
 * Résout une URL courte TikTok en utilisant une technique alternative
 * (puisque CORS bloque les redirects côté client)
 */
//async function _resolveShortUrl(shortUrl: string): Promise<string> {
//  // Méthode 1: Essayer avec l'API de résolution (si disponible sur votre backend)
//  try {
//    const response = await fetch(API_URL, {
//      method: 'POST',
//      headers: { 'Content-Type': 'application/json' },
//      body: JSON.stringify({ 
//        action: 'resolve_url', 
//        url: shortUrl 
//      }),
//    })
//    
//    if (response.ok) {
//      const data = await response.json()
//      if (data.resolved_url) {
//        return data.resolved_url
//      }
//    }
//  } catch {
//    // Fallback sur méthode 2
//  }
//  
//  // Méthode 2: Pattern matching sur les URLs courantes
//  // Certains short URLs contiennent le username encodé ou redirigent vers des patterns connus
//  
//  // Méthode 3: Demander à l'utilisateur de vérifier
//  // On essaie quand même avec l'URL complète, le backend PHP gère les redirects
//  return shortUrl
//}