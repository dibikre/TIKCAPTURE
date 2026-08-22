import { BASE_URL } from '../lib/constants'

export function StructuredData() {
  const schema = {
    "@context": "https://schema.org",
    "@type": "SoftwareApplication",
    "name": "TikCapture",
    "operatingSystem": "All",
    "applicationCategory": "MultimediaApplication",
    "offers": {
      "@type": "Offer",
      "price": "0",
      "priceCurrency": "USD"
    },
    "description": "Enregistreur de lives TikTok gratuit et téléchargeur de vidéos TikTok sans filigrane.",
    "url": BASE_URL,
    "image": `${BASE_URL}/logo.png`,
    "screenshot": `${BASE_URL}/og-image.png`,
    "featureList": [
      "Enregistrement live TikTok en haute qualité",
      "Téléchargement vidéo TikTok sans watermark",
      "Qualité HD supportée",
      "Sans installation requise",
      "Gratuit à 100%"
    ],
    "author": {
      "@type": "Organization",
      "name": "TikCapture Team",
      "url": BASE_URL
    }
  }

  const websiteSchema = {
    "@context": "https://schema.org",
    "@type": "WebSite",
    "url": BASE_URL,
    "name": "TikCapture",
    "description": "Enregistrez et téléchargez des lives TikTok gratuitement.",
    "potentialAction": {
      "@type": "SearchAction",
      "target": {
        "@type": "EntryPoint",
        "urlTemplate": `${BASE_URL}/?u={search_term_string}`
      },
      "query-input": "required name=search_term_string"
    }
  }

  return (
    <>
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(schema) }}
      />
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(websiteSchema) }}
      />
    </>
  )
}

interface HowToStep {
  name: string
  text: string
  url?: string
}

export function HowToStructuredData({ 
  name, 
  description, 
  steps 
}: { 
  name: string
  description: string
  steps: HowToStep[] 
}) {
  const schema = {
    "@context": "https://schema.org",
    "@type": "HowTo",
    "name": name,
    "description": description,
    "step": steps.map((s, i) => ({
      "@type": "HowToStep",
      "position": i + 1,
      "name": s.name,
      "itemListElement": [{
        "@type": "HowToDirection",
        "text": s.text
      }],
      ...(s.url && { "url": s.url })
    }))
  }

  return (
    <script
      type="application/ld+json"
      dangerouslySetInnerHTML={{ __html: JSON.stringify(schema) }}
    />
  )
}

export function CreatorStructuredData({ actor }: { actor: { name: string, description?: string, avatar: string, id: string, platform: string } }) {
  const schema = {
    "@context": "https://schema.org",
    "@type": "Person",
    "name": actor.name,
    "description": actor.description || `Créateur de lives sur ${actor.platform}`,
    "image": actor.avatar,
    "url": `${BASE_URL}/createurs/${actor.id}`,
    "sameAs": [
      actor.platform === 'tiktok' ? `https://www.tiktok.com/@${actor.id}` : ''
    ].filter(Boolean)
  }

  return (
    <script
      type="application/ld+json"
      dangerouslySetInnerHTML={{ __html: JSON.stringify(schema) }}
    />
  )
}

export function VideoStructuredData({ 
  video, 
  actor 
}: { 
  video: { title: string, description?: string, thumbnail: string, duration: string, created_at?: string, slug?: string, id?: string },
  actor: { name: string, id: string }
}) {
  const videoSlug = video.slug || video.id || ''
  const uploadDate = video.created_at || new Date().toISOString().split('T')[0]

  const schema = {
    "@context": "https://schema.org",
    "@type": "VideoObject",
    "name": video.title,
    "description": video.description || `Live de ${actor.name} capturé sur TikCapture`,
    "thumbnailUrl": video.thumbnail,
    "uploadDate": uploadDate,
    "duration": video.duration, // Should ideally be ISO 8601 duration format like "PT1H30M"
    "author": {
      "@type": "Person",
      "name": actor.name,
      "url": `${BASE_URL}/createurs/${actor.id}`
    },
    "contentUrl": `${BASE_URL}/createurs/${actor.id}/videos/${videoSlug}`
  }

  return (
    <script
      type="application/ld+json"
      dangerouslySetInnerHTML={{ __html: JSON.stringify(schema) }}
    />
  )
}
