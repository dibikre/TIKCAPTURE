import { useState, useEffect } from 'react'
import { useLiveStore } from '../stores/liveStore'
import { Clock, Infinity as InfinityIcon, ChevronDown } from 'lucide-react'
import { cn } from '../lib/utils'

const presets = [
  { value: 0, label: 'Continu', icon: InfinityIcon },
  { value: 60, label: '1m', icon: Clock },
  { value: 300, label: '5m', icon: Clock },
  { value: 1800, label: '30m', icon: Clock },
  { value: 3600, label: '1h', icon: Clock },
]

type TimeUnit = 's' | 'm' | 'h'

export function DurationPicker() {
  const { recordingDuration, setRecordingDuration } = useLiveStore()
  const [customValue, setCustomValue] = useState('')
  const [unit, setUnit] = useState<TimeUnit>('m')
  const [isUnitMenuOpen, setIsUnitMenuOpen] = useState(false)
  
  const handlePresetClick = (value: number) => {
    setRecordingDuration(value)
    setCustomValue('')
  }
  
  const updateDuration = (val: string, u: TimeUnit) => {
    const num = parseFloat(val)
    if (!isNaN(num) && num >= 0) {
      let multiplier = 1
      if (u === 'm') multiplier = 60
      if (u === 'h') multiplier = 3600
      setRecordingDuration(Math.round(num * multiplier))
    } else if (val === '') {
      setRecordingDuration(0)
    }
  }

  const handleCustomChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const value = e.target.value
    setCustomValue(value)
    updateDuration(value, unit)
  }

  const handleUnitChange = (newUnit: TimeUnit) => {
    setUnit(newUnit)
    updateDuration(customValue, newUnit)
    setIsUnitMenuOpen(false)
  }
  
  const unitLabels: Record<TimeUnit, string> = {
    s: 'Secondes',
    m: 'Minutes',
    h: 'Heures'
  }

  const unitShortLabels: Record<TimeUnit, string> = {
    s: 'sec',
    m: 'min',
    h: 'h'
  }

  // Fermer le menu si on clique ailleurs
  useEffect(() => {
    if (!isUnitMenuOpen) return
    const handler = () => setIsUnitMenuOpen(false)
    document.addEventListener('click', handler)
    return () => document.removeEventListener('click', handler)
  }, [isUnitMenuOpen])
  
  return (
    <div className="animate-fade-in-up">
      <label className="block text-sm font-medium text-muted-foreground mb-4">
        Durée d'enregistrement
      </label>
      
      <div className="flex flex-wrap gap-2 mb-5">
        {presets.map((preset) => {
          const Icon = preset.icon
          const isSelected = recordingDuration === preset.value && !customValue
          
          return (
            <button
              key={preset.value}
              onClick={() => handlePresetClick(preset.value)}
              className={cn(
                "flex items-center gap-2 px-4 py-2.5 rounded-xl font-medium transition-all duration-300",
                isSelected
                  ? 'bg-[#FF0050] text-white shadow-lg shadow-[#FF0050]/30 scale-105'
                  : 'bg-white/5 text-muted-foreground hover:bg-white/10 hover:text-foreground'
              )}
            >
              {Icon && <Icon className="w-4 h-4" />}
              {preset.label}
            </button>
          )
        })}
      </div>
      
      <div className="flex flex-col sm:flex-row sm:items-center gap-4">
        <div className="flex items-center gap-3">
          <div className="h-px w-8 bg-border" />
          <span className="text-xs font-bold text-muted-foreground uppercase tracking-widest">Ou durée personnalisée</span>
          <div className="h-px flex-1 sm:hidden bg-border" />
        </div>

        <div className="flex items-center gap-2">
          <div className="relative group flex-1 sm:flex-initial">
            <input
              type="number"
              value={customValue}
              onChange={handleCustomChange}
              placeholder="0"
              min="0"
              className="w-full sm:w-32 px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-foreground placeholder:text-muted-foreground focus:outline-none focus:border-[#FF0050] focus:ring-1 focus:ring-[#FF0050]/50 transition-all"
            />
          </div>

          <div className="relative">
            <button
              type="button"
              onClick={(e) => { e.stopPropagation(); setIsUnitMenuOpen(!isUnitMenuOpen) }}
              className="flex items-center justify-between gap-3 px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-foreground hover:bg-white/10 transition-all min-w-[110px]"
            >
              <span className="text-sm font-medium">{unitLabels[unit]}</span>
              <ChevronDown className={cn("w-4 h-4 text-muted-foreground transition-transform duration-300", isUnitMenuOpen && "rotate-180")} />
            </button>

            {isUnitMenuOpen && (
              <div className="absolute left-0 right-0 bottom-full mb-2 bg-[#1a1a1a] border border-white/10 rounded-xl overflow-hidden shadow-2xl z-20 animate-fade-in-up">
                {(Object.keys(unitLabels) as TimeUnit[]).map((u) => (
                  <button
                    key={u}
                    onClick={() => handleUnitChange(u)}
                    className={cn(
                      "w-full text-left px-4 py-2.5 text-sm transition-colors",
                      unit === u ? 'bg-[#FF0050] text-white' : 'text-muted-foreground hover:bg-white/5 hover:text-foreground'
                    )}
                  >
                    {unitLabels[u]}
                  </button>
                ))}
              </div>
            )}
          </div>
        </div>
        
        {customValue && (
          <p className="text-xs text-[#FF0050] font-medium animate-pulse">
            Arrêt auto à : {customValue} {unitShortLabels[unit]}
          </p>
        )}
      </div>
    </div>
  )
}
