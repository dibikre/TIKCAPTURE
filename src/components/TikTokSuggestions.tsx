import { memo } from 'react'
import { Sparkles } from 'lucide-react'

interface SuggestedCreator {
  username: string
  label?: string
}

const SUGGESTIONS: SuggestedCreator[] = [
  { username: 'khaby.lame' },
  { username: 'bellapoarch' },
  { username: 'charlidamelio' },
  { username: 'addisonre' },
  { username: 'mrbeast' },
  { username: 'zachking' },
  { username: 'kimberly.loaiza' },
  { username: 'cznburak' },
  { username: 'therock' },
  { username: 'domelipa' },
]

interface TikTokSuggestionsProps {
  onSearch: (username: string) => void
}

export const TikTokSuggestions = memo(({ onSearch }: TikTokSuggestionsProps) => {
  return (
    <div className="max-w-4xl mx-auto mt-6 mb-10 animate-fade-in px-4">
      <div className="flex items-center gap-2 mb-3 text-muted-foreground">
        <Sparkles className="w-3.5 h-3.5 text-[#00F2EA]" />
        <span className="text-xs font-medium uppercase tracking-wider">Suggestions</span>
      </div>
      <div className="flex flex-wrap gap-2">
        {SUGGESTIONS.map((creator) => (
          <button
            key={creator.username}
            onClick={() => onSearch(creator.username)}
            className="px-3 py-1.5 rounded-lg bg-white/5 border border-white/10 hover:border-[#FF0050]/40 hover:bg-[#FF0050]/5 transition-all text-xs font-medium text-foreground hover:text-[#FF0050] active:scale-95"
          >
            @{creator.username}
          </button>
        ))}
      </div>
    </div>
  )
})

TikTokSuggestions.displayName = 'TikTokSuggestions'
