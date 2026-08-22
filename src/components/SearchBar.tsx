import { useState, useEffect } from 'react'
import { Search, Loader2, Sparkles } from 'lucide-react'
import { clsx, type ClassValue } from 'clsx'
import { twMerge } from 'tailwind-merge'
import { BASE_URL } from '../lib/constants'

function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}

// ─── Types ────────────────────────────────────────────────────────────────────

interface SearchBarProps {
  onSearch:           (username: string) => void
  isLoading?:         boolean
  className?:         string
  initialValue?:      string
}

// ─── Plateforme detection (TikTok only) ────────────────────────────────────────

type Platform = 'tiktok' | null

interface PlatformInfo {
  id: Platform
  label: string
  logo: string       
  color: string      
  placeholder: string
}

const PLATFORMS: Record<Exclude<Platform, null>, PlatformInfo> = {
  tiktok: {
    id:          'tiktok',
    label:       'TikTok',
    logo:        `${BASE_URL}/plateformes/tiktok.png`,
    color:       '#FF0050',
    placeholder: '@username ou URL TikTok...',
  },
}

function detectPlatform(_value: string): Platform {
  return 'tiktok'
}

function getPlatformInfo(platform: Platform): PlatformInfo {
  return PLATFORMS[platform ?? 'tiktok']
}

// ─── Component ────────────────────────────────────────────────────────────────

export function SearchBar({ onSearch, isLoading = false, className, initialValue = '' }: SearchBarProps) {
  const [input, setInput] = useState('')
  
  useEffect(() => {
    if (initialValue) {
      setInput(initialValue)
    }
  }, [initialValue])

  const [isFocused, setIsFocused] = useState(false)

  const detectedPlatform = detectPlatform(input)
  const platformInfo     = getPlatformInfo(detectedPlatform)

  const extractUsername = (value: string): string => {
    const trimmed = value.trim()
    const match = trimmed.match(/tiktok\.com\/@([^/?]+)/)
    if (match) return match[1]
    if (trimmed.startsWith('@')) return trimmed.substring(1)
    return trimmed
  }

  const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    const trimmed = input.trim()
    if (!trimmed) return
    onSearch(extractUsername(trimmed))
  }

  const accentColor = detectedPlatform ? platformInfo.color : '#FF0050'

  return (
    <div className={cn('w-full max-w-2xl mx-auto', className)}>

      {/* Badge */}
      <div className="flex justify-center mb-4 md:mb-6 px-2">
        <div className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full glass text-xs md:text-sm text-muted-foreground max-w-full">
          <Sparkles className="w-3.5 h-3.5 md:w-4 md:h-4 text-[#FF0050] shrink-0" />
          <span className="truncate">Enregistrement de live 100% gratuit</span>
        </div>
      </div>

      {/* Search Form */}
      <form
        onSubmit={handleSubmit}
        className={cn(
          'relative group transition-all duration-500',
          isFocused && 'scale-[1.01]'
        )}
      >
        <div
          className={cn(
            'relative flex items-center gap-1 p-1.5 md:p-2 rounded-2xl md:rounded-3xl',
            'glass transition-all duration-300',
          )}
          style={isFocused ? {
            outline: `2px solid ${accentColor}80`,
            boxShadow: `0 0 40px ${accentColor}33`,
          } : undefined}
        >
          <div className="pl-2 md:pl-3 shrink-0 flex items-center gap-1.5">
             <img src={platformInfo.logo} alt={platformInfo.label} className="w-5 h-5 md:w-6 md:h-6 object-contain" />
          </div>

          <input
            type="text"
            value={input}
            onChange={(e) => setInput(e.target.value)}
            onFocus={() => setIsFocused(true)}
            onBlur={() => setIsFocused(false)}
            placeholder={platformInfo.placeholder}
            className={cn(
              'flex-1 min-w-0 bg-transparent border-none outline-none',
              'text-foreground placeholder:text-muted-foreground',
              'py-3 px-1 md:py-4 md:px-2 text-sm md:text-lg font-sans'
            )}
            disabled={isLoading}
          />

          <button
              type="submit"
              disabled={isLoading || !input.trim()}
              className={cn(
                'flex items-center justify-center gap-1.5 shrink-0',
                'px-3 py-2.5 md:px-6 md:py-3 rounded-xl md:rounded-2xl',
                'text-white font-semibold text-sm md:text-base transition-all duration-300',
                'hover:scale-105 active:scale-95',
                'disabled:opacity-50 disabled:cursor-not-allowed'
              )}
              style={{
                background: `linear-gradient(to right, ${accentColor}, ${accentColor}cc)`,
              }}
            >
            {isLoading ? (
              <Loader2 className="w-4 h-4 animate-spin" />
            ) : (
              <Search className="w-4 h-4" />
            )}
            <span className="hidden sm:inline">{isLoading ? 'Recherche...' : 'Rechercher'}</span>
          </button>
        </div>

        <div
          className={cn(
            'absolute -inset-1 rounded-3xl blur-xl transition-opacity duration-500 -z-10',
            isFocused ? 'opacity-20' : 'opacity-0'
          )}
          style={{ background: `linear-gradient(to right, ${accentColor}, #00F2EA)` }}
        />
      </form>
    </div>
  )
}
