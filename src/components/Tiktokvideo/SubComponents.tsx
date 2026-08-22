import React, { useState } from 'react'
import { Download, Loader2, Play } from 'lucide-react'
import { cn } from '../../lib/utils'
import { BASE_URL } from '../../lib/constants'
import { RelatedVideo } from './types'
import { formatDuration, formatNumber } from './utils'

export function StatBadge({ icon, value, label }: { icon: React.ReactNode; value: string; label: string }) {
  return (
    <div className="flex flex-col items-center gap-1 px-4 py-3 rounded-xl glass border border-white/10">
      <div className="text-[#FF0050]">{icon}</div>
      <span className="text-base font-bold text-foreground tabular-nums">{value}</span>
      <span className="text-xs text-muted-foreground">{label}</span>
    </div>
  )
}

const DOWNLOAD_PROXY = `${BASE_URL}/segment_page/api/download-proxy.php`

export function DownloadButton({ href, label, accent = false }: { href: string; label: string; accent?: boolean }) {
  const [downloading, setDownloading] = useState(false)

  const handleDownload = () => {
    if (!href) return
    setDownloading(true)
    const proxyUrl = `${DOWNLOAD_PROXY}?url=${encodeURIComponent(href)}`
    const a = document.createElement('a')
    a.href = proxyUrl
    a.download = `tiktok-${Date.now()}.mp4`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    setTimeout(() => setDownloading(false), 3000)
  }

  return (
    <button
      onClick={handleDownload}
      disabled={downloading}
      className={cn(
        'flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-semibold text-sm transition-all duration-200 hover:scale-105 active:scale-95 disabled:opacity-60 disabled:cursor-wait',
        accent
          ? 'bg-[#FF0050] hover:bg-[#e0004a] text-white shadow-lg shadow-[#FF0050]/25'
          : 'glass border border-white/15 text-foreground hover:border-white/30'
      )}
    >
      {downloading
        ? <Loader2 className="w-4 h-4 animate-spin shrink-0" />
        : <Download className="w-4 h-4 shrink-0" />
      }
      {downloading ? 'Telechargement...' : label}
    </button>
  )
}

export function RelatedCard({ video, onSearch }: { video: RelatedVideo; onSearch: (url: string) => void }) {
  return (
    <button
      onClick={() => onSearch(video.video_url)}
      className="group text-left rounded-xl glass border border-white/10 overflow-hidden hover:border-white/20 transition-all duration-200"
    >
      <div className="relative aspect-9/16 bg-white/5 overflow-hidden">
        <img
          src={video.dynamic_cover || video.cover}
          alt={video.desc}
          className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
        />
        <div className="absolute bottom-1.5 right-1.5 bg-black/70 text-white text-xs px-1.5 py-0.5 rounded">
          {formatDuration(video.duration)}
        </div>
        <div className="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors flex items-center justify-center">
          <Play className="w-8 h-8 text-white opacity-0 group-hover:opacity-100 transition-opacity" />
        </div>
      </div>
      <div className="p-3 space-y-1.5">
        <div className="flex items-center gap-2">
          <img src={video.author_avatar} alt={video.author_nickname} className="w-5 h-5 rounded-full object-cover" />
          <span className="text-xs font-medium text-foreground truncate">{video.author_nickname}</span>
        </div>
        <p className="text-xs text-muted-foreground line-clamp-2 leading-snug">{video.desc}</p>
        <div className="flex gap-3 text-xs text-muted-foreground pt-0.5">
          <span>👁 {formatNumber(video.play_count)}</span>
          <span>❤️ {formatNumber(video.digg_count)}</span>
        </div>
      </div>
    </button>
  )
}
