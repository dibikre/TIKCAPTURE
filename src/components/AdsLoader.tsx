import { useEffect } from 'react'

const ADS_KEY = 'ads_time'
const MAX_MS  = 30 * 60 * 1000 // 30 minutes en ms

function getTodayKey() {
  return new Date().toISOString().slice(0, 10) // "2026-03-06"
}

function getAdsData(): { date: string; elapsed: number } {
  try {
    const raw = localStorage.getItem(ADS_KEY)
    if (raw) return JSON.parse(raw)
  } catch {
    // ignore
  }
  return { date: getTodayKey(), elapsed: 0 }
}

function saveElapsed(ms: number) {
  const today = getTodayKey()
  const data   = getAdsData()
  // Reset si nouveau jour
  const elapsed = data.date === today ? data.elapsed + ms : ms
  localStorage.setItem(ADS_KEY, JSON.stringify({ date: today, elapsed }))
}

function shouldShowAds(): boolean {
  const today = getTodayKey()
  const data  = getAdsData()
  if (data.date !== today) return true        // nouveau jour → reset
  return data.elapsed < MAX_MS               // encore du temps restant
}

export function AdsLoader() {
  useEffect(() => {
    if (!shouldShowAds()) return  // quota atteint pour aujourd'hui

    let startTime: number | null = null
    let scriptEl: HTMLScriptElement | null = null
    let intervalId: ReturnType<typeof setInterval> | null = null

    const loadAds = () => {
      startTime = Date.now()

      // Yandex Metrika - Chargement ultra-tardif pour ne pas impacter le LCP/FID
      const loadYandex = () => {
        // @ts-expect-error ym search
        if (window.ym) return // Déjà chargé

        const ymScript = document.createElement('script')
        ymScript.src   = 'https://mc.yandex.ru/metrika/tag.js'
        ymScript.async = true
        ymScript.defer = true
        ymScript.onload = () => {
          // @ts-expect-error ym global
          window.ym?.(104601917, 'init', {
            ssr: true, 
            webvisor: false, // Désactivé par défaut car très lourd, possiblement la cause des lenteurs
            clickmap: true,
            ecommerce: 'dataLayer', 
            accurateTrackBounce: true, 
            trackLinks: true,
          })
        }
        document.body.appendChild(ymScript)
      }

      // On attend que le navigateur soit vraiment tranquille
      if ('requestIdleCallback' in window) {
        window.requestIdleCallback(() => {
          setTimeout(loadYandex, 8000) // 8 secondes après idle
        }, { timeout: 10000 })
      } else {
        setTimeout(loadYandex, 10000)
      }

      // Réseau pub - chargé après un délai
      setTimeout(() => {
        scriptEl = document.createElement('script')
        scriptEl.src   = 'https://pl27796301.effectivegatecpm.com/71/f1/d0/71f1d0c4917e9479d7b27fab8cebe4be.js'
        scriptEl.async = true
        document.body.appendChild(scriptEl)
      }, 5000)

      // Sauvegarde le temps écoulé toutes les 10s
      intervalId = setInterval(() => {
        if (startTime) saveElapsed(10_000)

        // Vérifie si quota atteint → retire les pubs
        if (!shouldShowAds()) {
          stopAds()
        }
      }, 10_000)
    }

    const stopAds = () => {
      if (intervalId)  clearInterval(intervalId)
      if (scriptEl)    scriptEl.remove()
      // Sauvegarde le temps restant depuis le dernier tick
      if (startTime) {
        const elapsed = Date.now() - startTime
        saveElapsed(elapsed % 10_000) // le reste non encore sauvegardé
        startTime = null
      }
    }

    const load = () => setTimeout(loadAds, 3000)

    if (document.readyState === 'complete') {
      load()
    } else {
      window.addEventListener('load', load, { once: true })
    }

    // Sauvegarde quand l'utilisateur quitte la page
    const handleUnload = () => {
      if (startTime) {
        saveElapsed(Date.now() - startTime)
      }
    }
    window.addEventListener('beforeunload', handleUnload)

    return () => {
      stopAds()
      window.removeEventListener('beforeunload', handleUnload)
      window.removeEventListener('load', load)
    }
  }, [])

  return null
}