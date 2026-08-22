import { useCallback } from 'react'
import { useLiveStore } from '../stores/liveStore'
import { formatBytes } from '../lib/utils'
import { BASE_URL } from '../lib/constants'

// ─── Helpers HLS ──────────────────────────────────────────────────────────────

/**
 * Résout une URL de segment par rapport à la base URL du manifeste.
 * Logique identique au PHP handleProxy() + downloadLoop() JS :
 *
 *   if starts 'http'  → absolue, on garde
 *   if starts '//'    → on préfixe https:
 *   if starts '/'     → relatif à la racine du domaine
 *   else              → relatif au dossier du manifeste (baseUrl)
 *
 * NOTE : quand le PHP proxy reécrit le manifeste, les segments deviennent
 * "hls-proxy.php?action=proxy&url=..." — ce sont des URLs relatives
 * qui tombent dans le cas `else` et sont résolues correctement par baseUrl.
 */
function resolveSegmentUrl(line: string, manifestUrl: string): string {
  const trimmed = line.trim()
  if (trimmed.startsWith('http://') || trimmed.startsWith('https://')) return trimmed
  if (trimmed.startsWith('//')) return 'https:' + trimmed
  if (trimmed.startsWith('/')) {
    try {
      const u = new URL(manifestUrl)
      return u.protocol + '//' + u.host + trimmed
    } catch { /* fallback baseUrl */ }
  }
  // Relatif au dossier du manifeste — cas typique des segments réécris par le proxy PHP
  const lastSlash = manifestUrl.lastIndexOf('/')
  const baseUrl   = lastSlash > 0 ? manifestUrl.substring(0, lastSlash + 1) : manifestUrl
  return baseUrl + trimmed
}

/**
 * Parse le manifeste M3U8 et retourne les URLs de segments dans l'ordre.
 * Logique identique au downloadLoop() du PHP JS :
 *
 *   for (const line of lines) {
 *     if (!trimmed || trimmed.startsWith('#')) continue
 *     → c'est un segment
 *   }
 *
 * On suit exactement cette règle : toute ligne non-vide non-commentaire = segment.
 * Le PHP proxy a déjà réécrit les URLs, pas besoin de re-proxifier.
 */
function parseM3U8Segments(m3u8Text: string, manifestUrl: string): string[] {
  const segments: string[] = []

  for (const line of m3u8Text.split('\n')) {
    const trimmed = line.trim()
    if (!trimmed || trimmed.startsWith('#')) continue
    segments.push(resolveSegmentUrl(trimmed, manifestUrl))
  }

  return segments
}

// ─── Fetch avec retry ─────────────────────────────────────────────────────────

/**
 * Fetch avec retry exponentiel — comme le PHP qui fait jusqu'à 5 tentatives
 * sur ytdlp.online avec sleep(2) entre chaque.
 * Ici on fait 3 tentatives côté client avec backoff 1s → 2s → 4s.
 */
async function fetchWithRetry(
  url:    string,
  signal: AbortSignal,
  retries = 3,
  delay   = 1000,
): Promise<Response> {
  let lastError: unknown

  for (let attempt = 0; attempt < retries; attempt++) {
    if (signal.aborted) throw new DOMException('Aborted', 'AbortError')

    try {
      const res = await fetch(url, { signal, cache: 'no-store' })
      if (res.ok) return res
      // 4xx → pas de retry (erreur définitive)
      if (res.status >= 400 && res.status < 500) throw new Error(`HTTP ${res.status}`)
      lastError = new Error(`HTTP ${res.status}`)
    } catch (e) {
      if ((e as Error).name === 'AbortError') throw e
      lastError = e
    }

    if (attempt < retries - 1) {
      await new Promise<void>(resolve => {
        const t = setTimeout(resolve, delay * Math.pow(2, attempt))
        signal.addEventListener('abort', () => { clearTimeout(t); resolve() }, { once: true })
      })
    }
  }

  throw lastError
}

/**
 * Télécharge un segment et retourne son contenu.
 * Retourne null en cas d'erreur (segment ignoré, comme le PHP qui log et continue).
 */
async function fetchSegment(url: string, signal: AbortSignal): Promise<Uint8Array<ArrayBuffer> | null> {
  try {
    const res = await fetchWithRetry(url, signal, 3, 500)
    return new Uint8Array(await res.arrayBuffer())
  } catch (e) {
    if ((e as Error).name === 'AbortError') throw e
    console.warn('[useRecording] Segment ignoré:', url, (e as Error).message)
    return null
  }
}

// ─── Enregistrement HLS ───────────────────────────────────────────────────────

/**
 * Polling du manifeste + téléchargement des segments.
 * Reproduit exactement la logique downloadLoop() du PHP JS :
 *
 *   while (isRecording) {
 *     fetch(playlistUrl)
 *     parse lines → nouveaux segments seulement (segmentQueue)
 *     download chaque nouveau segment
 *     if (#EXT-X-ENDLIST && aucun nouveau) → stop
 *     await 2000ms
 *   }
 */
async function recordHls(
  manifestUrl:       string,
  signal:            AbortSignal,
  recordingDuration: number,
  onProgress:        (size: number, elapsed: number) => void,
): Promise<Blob> {
  const startTime    = Date.now()
  const endTime      = recordingDuration > 0 ? startTime + recordingDuration * 1000 : Infinity
  const seenSegments = new Set<string>()
  const allChunks:   Uint8Array<ArrayBuffer>[] = []
  let   totalBytes   = 0

  while (!signal.aborted) {
    if (Date.now() >= endTime) break

    try {
      const res  = await fetchWithRetry(manifestUrl, signal, 3, 1000)
      const text = await res.text()
      const segments    = parseM3U8Segments(text, manifestUrl)
      const isEndList   = text.includes('#EXT-X-ENDLIST')
      const newSegments = segments.filter(s => !seenSegments.has(s))

      for (const segUrl of newSegments) {
        if (signal.aborted) break
        seenSegments.add(segUrl)
        const data = await fetchSegment(segUrl, signal)
        if (data) {
          allChunks.push(data)
          totalBytes += data.length
          onProgress(totalBytes, (Date.now() - startTime) / 1000)
        }
      }

      // Fin de playlist (VOD ou live terminé) — identique au PHP JS
      if (newSegments.length === 0 && isEndList) break

    } catch (e) {
      if ((e as Error).name === 'AbortError') break
      console.error('[useRecording] Erreur manifeste:', (e as Error).message)
      // Attendre 5s avant retry sur erreur réseau (comme le PHP : await 5000)
      await new Promise<void>(resolve => {
        const t = setTimeout(resolve, 5000)
        signal.addEventListener('abort', () => { clearTimeout(t); resolve() }, { once: true })
      })
      continue
    }

    // Polling toutes les 2s — identique au PHP JS : await new Promise(r => setTimeout(r, 2000))
    await new Promise<void>(resolve => {
      const t = setTimeout(resolve, 2000)
      signal.addEventListener('abort', () => { clearTimeout(t); resolve() }, { once: true })
    })
  }

  return new Blob(allChunks, { type: 'video/mp2t' })
}

// ─── Enregistrement FLV (TikTok) ─────────────────────────────────────────────

async function recordFlv(
  streamUrl:         string,
  signal:            AbortSignal,
  recordingDuration: number,
  onProgress:        (size: number, elapsed: number) => void,
): Promise<Blob> {
  const response = await fetch(streamUrl, { signal, method: 'GET' })
  if (!response.ok) throw new Error(`HTTP error: ${response.status}`)

  const reader = response.body?.getReader()
  if (!reader) throw new Error('No reader available')

  const chunks:      BlobPart[] = []
  let receivedLength = 0
  const startTime    = Date.now()

  try {
    while (!signal.aborted) {
      const { done, value } = await reader.read()
      if (done) break
      chunks.push(value)
      receivedLength += value.length
      onProgress(receivedLength, (Date.now() - startTime) / 1000)
    }
  } catch (e) {
    if ((e as Error).name !== 'AbortError') throw e
  } finally {
    reader.releaseLock()
  }

  return new Blob(chunks, { type: 'video/x-flv' })
}

// ─── Hook principal ───────────────────────────────────────────────────────────

export function useRecording() {
  const {
    getSelectedStream,
    recordingDuration,
    startRecording:         startStoreRecording,
    stopRecording:          stopStoreRecording,
    updateRecordingProgress,
    setAbortController,
  } = useLiveStore()

  const startRecording = useCallback(async () => {
    const stream = getSelectedStream()
    if (!stream) return

    // Priorité FLV (TikTok) > HLS — inchangé
    const flvUrl = stream.flv || (stream.type === 'FLV'  ? stream.url : null)
    const hlsUrl = stream.hls || (stream.type === 'HLS'  ? stream.url : null)
                              || (stream.type === 'CMAF' ? stream.url : null)
                              || (stream.type === 'DASH' ? stream.url : null)

    const useHls    = !flvUrl && !!hlsUrl
    const streamUrl = flvUrl || hlsUrl

    if (!streamUrl) {
      console.error('[useRecording] Aucune URL de stream disponible')
      return
    }

    // Sauvegarde dans l'historique des enregistrements
    try {
      const { searchResult, selectedQuality } = useLiveStore.getState()
      if (searchResult) {
        fetch(`${BASE_URL}/segment_page/api/recordings.php`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            uniqueId:  searchResult.user.uniqueId,
            nickname:  searchResult.user.nickname,
            avatar:    searchResult.user.avatar,
            title:     searchResult.live.title,
            viewers:   searchResult.live.viewers,
            startTime: searchResult.live.startTime,
            quality:   selectedQuality || 'Auto'
          })
        }).catch(() => {})
      }
    } catch { /* ignore */ }

    startStoreRecording()
    const controller = new AbortController()
    setAbortController(controller)

    // Gestion de l'arrêt automatique par durée
    let durationTimer: ReturnType<typeof setTimeout> | null = null
    if (recordingDuration > 0) {
      durationTimer = setTimeout(() => {
        console.log(`[useRecording] Arrêt automatique après ${recordingDuration}s`)
        controller.abort()
      }, recordingDuration * 1000)
    }

    const onProgress = (size: number, elapsed: number) => {
      const progress = recordingDuration > 0
        ? Math.min((elapsed / recordingDuration) * 100, 100)
        : 0
      updateRecordingProgress({
        progress,
        size:     formatBytes(size),
        speed:    elapsed > 0 ? `${formatBytes(size / elapsed)}/s` : '0 KB/s',
        duration: elapsed,
      })
    }

    try {
      const blob = useHls
        ? await recordHls(streamUrl, controller.signal, recordingDuration, onProgress)
        : await recordFlv(streamUrl, controller.signal, recordingDuration, onProgress)

      if (blob.size > 0) {
        const { searchResult, selectedQuality } = useLiveStore.getState()
        const username  = searchResult?.user.uniqueId || 'unknown'
        const timestamp = new Date().toISOString().replace(/:/g, '-').split('.')[0]
        const ext       = useHls ? 'ts' : 'flv'

        const url = URL.createObjectURL(blob)
        const a   = document.createElement('a')
        a.href     = url
        a.download = `TikCapture_${username}_${selectedQuality}_${timestamp}.${ext}`
        document.body.appendChild(a)
        a.click()
        document.body.removeChild(a)
        setTimeout(() => URL.revokeObjectURL(url), 1000)
      }

    } catch (error) {
      if ((error as Error).name !== 'AbortError') {
        console.error('[useRecording] Erreur enregistrement:', error)
      }
    } finally {
      if (durationTimer) clearTimeout(durationTimer)
      stopStoreRecording()
    }
  }, [
    getSelectedStream,
    recordingDuration,
    startStoreRecording,
    stopStoreRecording,
    updateRecordingProgress,
    setAbortController,
  ])

  const stopRecording = useCallback(() => {
    const { abortController } = useLiveStore.getState()
    if (abortController) abortController.abort()
    stopStoreRecording()
  }, [stopStoreRecording])

  return { startRecording, stopRecording }
}