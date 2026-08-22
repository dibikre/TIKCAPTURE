import { useMutation } from '@tanstack/react-query'
import { useTikTokSearch } from './useTikTokSearch'
import type { SearchResult } from '../types'

// ─── Types ────────────────────────────────────────────────────────────────────

export type Platform = 'tiktok'

export type UniversalResult = SearchResult & {
  platform:  Platform
  notFound?: boolean
}

// ─── Détection de plateforme ──────────────────────────────────────────────────

export function detectPlatform(_input: string): Platform {
  return 'tiktok'
}

// ─── Hook universel ───────────────────────────────────────────────────────────

export function useUniversalSearch() {
  const tiktokMutation = useTikTokSearch()

  const universalMutation = useMutation({
    mutationFn: async (input: string): Promise<UniversalResult> => {
      const result = await tiktokMutation.mutateAsync(input)
      return { ...result, platform: 'tiktok' as Platform }
    },
    retry: 0,
  })

  return {
    mutateAsync: universalMutation.mutateAsync,
    isPending:   universalMutation.isPending || tiktokMutation.isPending,
    isError:     universalMutation.isError,
    error:       universalMutation.error,
    reset:       universalMutation.reset,
  }
}
