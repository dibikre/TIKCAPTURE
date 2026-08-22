import { useLiveStore } from '../stores/liveStore'
import { Check, Film } from 'lucide-react'

const qualityLabels: Record<string, { name: string; desc: string }> = {
  origin: { name: 'Original', desc: 'Qualité maximale' },
  hd: { name: 'HD', desc: 'Haute définition' },
  sd: { name: 'SD', desc: 'Définition standard' },
  ld: { name: 'LD', desc: 'Basse définition' },
}

export function QualitySelector() {
  const { searchResult, selectedQuality, setSelectedQuality } = useLiveStore()
  
  if (!searchResult) return null
  
  const qualities = Object.entries(searchResult.streams)
    .sort(([a], [b]) => {
      const order = ['origin', 'hd', 'sd', 'ld']
      return order.indexOf(a) - order.indexOf(b)
    })
  
  return (
    <div>
      <label className="block text-sm text-muted-foreground mb-3">
        Qualité d'enregistrement
      </label>
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
        {qualities.map(([key, stream], index) => {
          const isSelected = selectedQuality === key
          const label = qualityLabels[key] || { name: key, desc: '' }
          
          return (
            <label
              key={key}
              className={`
                relative flex items-center gap-4 p-4 rounded-2xl cursor-pointer
                transition-all duration-300
                ${isSelected 
                  ? 'bg-[#FF0050]/10 border-2 border-[#FF0050]' 
                  : 'bg-white/5 border-2 border-transparent hover:bg-white/10'
                }
              `}
            >
              <input
                type="radio"
                name="quality"
                value={key}
                checked={isSelected}
                onChange={() => setSelectedQuality(key)}
                className="sr-only"
              />
              
              <div className={`
                w-12 h-12 rounded-xl flex items-center justify-center shrink-0
                ${isSelected ? 'bg-[#FF0050] text-white' : 'bg-white/10 text-muted-foreground'}
              `}>
                <Film className="w-6 h-6" />
              </div>
              
              <div className="flex-1 min-w-0">
                <div className="flex items-center gap-2">
                  <span className="font-semibold">{stream.qualityName || label.name}</span>
                  {index === 0 && (
                    <span className="px-2 py-0.5 rounded-full bg-[#FF0050]/20 text-[#FF0050] text-xs font-medium">
                      Recommandé
                    </span>
                  )}
                </div>
                <p className="text-sm text-muted-foreground">
                  {stream.resolution || label.desc}
                </p>
                {stream.bitrate && (
                  <p className="text-xs text-muted-foreground/60">
                    {stream.bitrate} • {stream.type}
                  </p>
                )}
              </div>
              
              {isSelected && (
                <div className="w-6 h-6 rounded-full bg-[#FF0050] flex items-center justify-center shrink-0">
                  <Check className="w-4 h-4 text-white" />
                </div>
              )}
            </label>
          )
        })}
      </div>
    </div>
  )
}