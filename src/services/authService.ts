import { AUTH_API } from '../lib/constants'
import { AuthUser } from '../stores/authStore'

export interface AuthResponse {
  status: 'success' | 'error'
  message?: string
  data?: {
    user: AuthUser
    token?: string
  }
}

/**
 * Service pour gérer les interactions avec l'API d'authentification.
 * Respecte le principe de Single Responsibility (SRP).
 */
export const authService = {
  /**
   * Vérifie la validité du token et récupère les infos utilisateur.
   */
  async verifyToken(token: string): Promise<AuthResponse> {
    const res = await fetch(`${AUTH_API}?action=verify`, {
      headers: { Authorization: `Bearer ${token}` },
    })
    
    if (!res.ok) {
      throw new Error(`Auth verification failed with status: ${res.status}`)
    }
    
    return await res.json()
  },

  /**
   * Ici, on pourrait ajouter d'autres méthodes comme login, register, etc.
   * Cela permet de centraliser la logique de fetch et de gestion des erreurs.
   */
}
