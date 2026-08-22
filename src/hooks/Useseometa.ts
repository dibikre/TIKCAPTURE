import { useEffect } from 'react'

interface SeoMeta {
  title: string
  description: string
  canonical: string
  ogTitle?: string
  ogDescription?: string
  ogUrl?: string
  ogImage?: string
  robots?: string
}

function setMeta(name: string, content: string, property = false) {
  const attr = property ? 'property' : 'name'
  let el = document.head.querySelector<HTMLMetaElement>(`meta[${attr}="${name}"]`)
  if (!el) {
    el = document.createElement('meta')
    el.setAttribute(attr, name)
    document.head.appendChild(el)
  }
  el.setAttribute('content', content)
}

function setLink(rel: string, href: string) {
  let el = document.head.querySelector<HTMLLinkElement>(`link[rel="${rel}"]`)
  if (!el) {
    el = document.createElement('link')
    el.setAttribute('rel', rel)
    document.head.appendChild(el)
  }
  el.setAttribute('href', href)
}

function applyMeta(meta: SeoMeta) {
  document.title = meta.title
  setMeta('description', meta.description)
  setLink('canonical', meta.canonical)
  setMeta('og:title',       meta.ogTitle       ?? meta.title,       true)
  setMeta('og:description', meta.ogDescription ?? meta.description, true)
  setMeta('og:url',         meta.ogUrl         ?? meta.canonical,   true)
  setMeta('og:type',        'website',                              true)
  setMeta('og:site_name',   'TikCapture',                           true)
  if (meta.ogImage) setMeta('og:image', meta.ogImage, true)
  setMeta('robots', meta.robots ?? 'index, follow')
  setMeta('twitter:card',        'summary_large_image')
  setMeta('twitter:title',       meta.ogTitle       ?? meta.title)
  setMeta('twitter:description', meta.ogDescription ?? meta.description)
  if (meta.ogImage) setMeta('twitter:image', meta.ogImage)
}

// Détecte si react-snap est en train de prerendre
const isPrerendering = navigator.userAgent === 'ReactSnap'

export function useSeoMeta(meta: SeoMeta) {
  if (isPrerendering) {
    applyMeta(meta)
  }

  useEffect(() => {
    applyMeta(meta)
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [
    meta.title,
    meta.description,
    meta.canonical,
    meta.ogTitle,
    meta.ogDescription,
    meta.ogUrl,
    meta.ogImage,
    meta.robots,
  ])
}