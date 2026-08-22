import type { Actor, ActorVideoPageData } from '../content/actors-content'
import { BASE_URL } from './constants'

declare global {
  interface Window {
    __SERVER_CONFIG__?: {
      ACTORS_API_BASE?: string
    }
  }
}

const API_BASE =
  window.__SERVER_CONFIG__?.ACTORS_API_BASE?.trim() ||
  `${BASE_URL}/api`

async function getJson<T>(url: string): Promise<T> {
  const res = await fetch(url, { cache: 'no-store' })
  if (!res.ok) {
    throw new Error(`Request failed with status ${res.status}`)
  }
  return res.json() as Promise<T>
}

export async function fetchActors(page = 1, limit = 24, search = ''): Promise<{
  data: Actor[]
  total: number
  page: number
  totalPages: number
}> {
  const params = new URLSearchParams({ page: String(page), limit: String(limit), search })
  const data = await getJson<{ status: string; data: Actor[]; total: number; totalPages: number }>(
    `${API_BASE}/actors.php?${params}`
  )
  return { data: data.data, total: data.total, page, totalPages: data.totalPages }
}

export async function fetchActorById(actorId: string): Promise<Actor> {
  const data = await getJson<{ status: string; data: Actor }>(
    `${API_BASE}/actor.php?id=${encodeURIComponent(actorId)}`
  )
  return data.data
}

export async function fetchActorVideoPage(actorId: string, videoId: string): Promise<ActorVideoPageData> {
  const data = await getJson<{ status: string; data: ActorVideoPageData }>(
    `${API_BASE}/actor-video.php?actor_id=${encodeURIComponent(actorId)}&video_id=${encodeURIComponent(videoId)}`
  )
  return data.data
}
