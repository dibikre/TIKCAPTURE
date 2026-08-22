import { Eye, MessageCircle, Share2, HardDrive, Video, Hash, UserCheck } from 'lucide-react'
import { ApiResponse } from './types'
import { formatNumber } from './utils'
import { StatBadge, DownloadButton, RelatedCard } from './SubComponents'

export function ResultPanel({ data, onReset, onSearch }: { data: ApiResponse; onReset: () => void; onSearch: (url: string) => void }) {
  const { video, author, related } = data
  if (!video || !author) return null

  return (
    <div className="space-y-6 animate-fade-in-up">
      <div className="rounded-2xl glass border border-white/10 overflow-hidden">
        {/* Thumbnail + info */}
        <div className="flex flex-col sm:flex-row gap-0">
          <div className="relative sm:w-48 shrink-0 aspect-9/16 sm:aspect-auto bg-white/5">
            {video.dynamicCover || video.cover ? (
              <img src={video.dynamicCover || video.cover} alt="Miniature" className="w-full h-full object-cover" />
            ) : (
              <div className="w-full h-full flex items-center justify-center text-4xl">🎬</div>
            )}
            <div className="absolute inset-0 bg-linear-to-t from-black/60 to-transparent sm:hidden" />
          </div>

          <div className="flex-1 p-5 space-y-4">
            <div className="flex items-center gap-3">
              {author.avatar && (
                <img src={author.avatar} alt={author.nickname} className="w-12 h-12 rounded-full object-cover border-2 border-[#FF0050]/40 shrink-0" />
              )}
              <div className="min-w-0">
                <p className="font-bold text-foreground truncate">{author.nickname}</p>
                <p className="text-sm text-muted-foreground">@{author.username}</p>
              </div>
              <span className="ml-auto shrink-0 flex items-center gap-1 text-xs px-2.5 py-1 rounded-full bg-[#FF0050]/15 border border-[#FF0050]/30 text-[#FF0050]">
                <Video className="w-3 h-3" /> Video TikTok
              </span>
            </div>

            {author.signature && (
              <p className="text-sm text-foreground leading-relaxed line-clamp-2">{author.signature}</p>
            )}

            <div className="flex gap-4 text-sm">
              {[
                { label: 'Videos',    value: formatNumber(author.videoCount) },
                { label: 'Suivis',    value: formatNumber(author.following) },
                { label: 'Followers', value: formatNumber(author.followers) },
              ].map((s) => (
                <div key={s.label} className="text-center">
                  <p className="font-bold text-foreground">{s.value}</p>
                  <p className="text-xs text-muted-foreground">{s.label}</p>
                </div>
              ))}
            </div>

            {video.desc && (
              <p className="text-sm text-foreground/80 leading-relaxed line-clamp-3">{video.desc}</p>
            )}

            {video.hashtags.length > 0 && (
              <div className="flex flex-wrap gap-1.5">
                {video.hashtags.slice(0, 6).map((tag) => (
                  <span key={tag} className="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-[#00F2EA]/10 border border-[#00F2EA]/20 text-[#00F2EA]">
                    <Hash className="w-2.5 h-2.5" />{tag}
                  </span>
                ))}
              </div>
            )}
          </div>
        </div>

        {/* Stats */}
        <div className="px-5 py-4 border-t border-white/8 grid grid-cols-2 sm:grid-cols-4 gap-3">
          <StatBadge icon={<Eye className="w-4 h-4" />}           value={formatNumber(video.stats.plays)}    label="Vues" />
          <StatBadge icon={<MessageCircle className="w-4 h-4" />} value={formatNumber(video.stats.comments)} label="Commentaires" />
          <StatBadge icon={<Share2 className="w-4 h-4" />}        value={formatNumber(video.stats.shares)}   label="Partages" />
          <StatBadge icon={<HardDrive className="w-4 h-4" />}     value={video.sizeFormatted}                label="Taille" />
        </div>

        {(video.width > 0 && video.height > 0) && (
          <div className="px-5 pb-4">
            <span className="text-xs text-muted-foreground">
              Resolution : <span className="text-foreground">{video.width}x{video.height}</span>
              {video.createTime && <> · Publie le <span className="text-foreground">{video.createTime}</span></>}
            </span>
          </div>
        )}

        {/* Download buttons */}
        <div className="px-5 pb-5 flex flex-col sm:flex-row gap-3">
          {video.urlNoWatermark && <DownloadButton href={video.urlNoWatermark} label="Sans filigrane (HD)" accent />}
          {video.urlWatermark   && <DownloadButton href={video.urlWatermark}   label="Avec filigrane" />}
          <button
            onClick={onReset}
            className="flex items-center justify-center gap-2 px-6 py-3 rounded-xl glass border border-white/15 text-sm text-muted-foreground hover:text-foreground hover:border-white/30 transition-all"
          >
            Nouvelle recherche
          </button>
        </div>

        <div className="mx-5 mb-5 flex items-start gap-3 px-4 py-3 rounded-lg bg-[#00F2EA]/5 border border-[#00F2EA]/20">
          <span className="text-[#00F2EA] text-xs mt-0.5 shrink-0">💡</span>
          <p className="text-sm text-[#00F2EA]/80">
            Si la video s'ouvre au lieu de se telecharger, faites <strong>Ctrl+S</strong> pour la sauvegarder.
            Sur mobile, appuyez longuement sur la video.
          </p>
        </div>
      </div>

      {related && related.length > 0 && (
        <div className="space-y-4">
          <h2 className="text-lg font-bold text-foreground flex items-center gap-2">
            <UserCheck className="w-5 h-5 text-[#FF0050]" />
            Videos recommandees
          </h2>
          <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
            {related.map((v) => <RelatedCard key={v.id} video={v} onSearch={onSearch} />)}
          </div>
        </div>
      )}
    </div>
  )
}
