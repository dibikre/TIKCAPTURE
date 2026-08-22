import { Moon, Sun, Monitor } from 'lucide-react'
import { useTheme } from './ThemeProvider'

export function ThemeToggle() {
  const { theme, setTheme } = useTheme()

  return (
    <div className="flex items-center gap-2 p-1 rounded-full glass">
      {/* Bouton Light */}
      <button
        onClick={() => setTheme('light')}
        className={`p-2 rounded-full transition-all duration-300 ${
          theme === 'light'
            ? 'bg-[#FFB800] text-white shadow-lg'
            : 'text-muted-foreground hover:text-foreground hover:bg-black/5 dark:hover:bg-white/10'
        }`}
        title="Mode clair"
      >
        <Sun className="w-4 h-4" />
      </button>

      {/* Bouton Dark */}
      <button
        onClick={() => setTheme('dark')}
        className={`p-2 rounded-full transition-all duration-300 ${
          theme === 'dark'
            ? 'bg-[#FF0050] text-white shadow-lg shadow-[#FF0050]/30'
            : 'text-muted-foreground hover:text-foreground hover:bg-black/5 dark:hover:bg-white/10'
        }`}
        title="Mode sombre"
      >
        <Moon className="w-4 h-4" />
      </button>

      {/* Bouton System */}
      <button
        onClick={() => setTheme('system')}
        className={`p-2 rounded-full transition-all duration-300 ${
          theme === 'system'
            ? 'bg-foreground text-background'
            : 'text-muted-foreground hover:text-foreground hover:bg-black/5 dark:hover:bg-white/10'
        }`}
        title="Mode système"
      >
        <Monitor className="w-4 h-4" />
      </button>
    </div>
  )
}

// Version simplifiée (juste un bouton qui toggle)
export function ThemeToggleSimple() {
  const { resolvedTheme, toggleTheme } = useTheme()

  return (
    <button
      onClick={toggleTheme}
      className="relative p-3 rounded-full glass hover:bg-black/5 dark:hover:bg-white/10 transition-all duration-300 group"
      title={resolvedTheme === 'dark' ? 'Passer en mode clair' : 'Passer en mode sombre'}
    >
      {/* Icône Sun (visible en dark mode) */}
      <Sun 
        className={`w-5 h-5 text-[#FFB800] transition-all duration-500 ${
          resolvedTheme === 'dark' ? 'rotate-0 scale-100' : 'rotate-90 scale-0'
        }`} 
      />
      
      {/* Icône Moon (visible en light mode) */}
      <Moon 
        className={`absolute inset-0 m-auto w-5 h-5 text-[#7000FF] transition-all duration-500 ${
          resolvedTheme === 'light' ? 'rotate-0 scale-100' : '-rotate-90 scale-0'
        }`} 
      />
      
      {/* Glow effect */}
      <div className={`absolute inset-0 rounded-full blur-md transition-opacity duration-300 ${
        resolvedTheme === 'dark' ? 'bg-[#FFB800]/20' : 'bg-[#7000FF]/20'
      } opacity-0 group-hover:opacity-100`} />
    </button>
  )
}

// Version ultra-compacte pour header
export function ThemeToggleMini() {
  const { resolvedTheme, toggleTheme } = useTheme()

  return (
    <button
      onClick={toggleTheme}
      className="relative w-10 h-10 rounded-xl glass flex items-center justify-center overflow-hidden group hover:bg-black/5 dark:hover:bg-white/10 transition-all duration-300"
      title={resolvedTheme === 'dark' ? 'Mode clair' : 'Mode sombre'}
    >
      <div className="relative w-5 h-5">
        <Sun 
          className={`absolute inset-0 w-5 h-5 text-[#FFB800] transition-all duration-500 ${
            resolvedTheme === 'dark' ? 'rotate-0 scale-100 opacity-100' : 'rotate-90 scale-0 opacity-0'
          }`} 
        />
        <Moon 
          className={`absolute inset-0 w-5 h-5 text-[#7000FF] transition-all duration-500 ${
            resolvedTheme === 'light' ? 'rotate-0 scale-100 opacity-100' : '-rotate-90 scale-0 opacity-0'
          }`} 
        />
      </div>
      <div className={`absolute inset-0 rounded-xl transition-opacity duration-300 ${
        resolvedTheme === 'dark' ? 'bg-[#FFB800]/10' : 'bg-[#7000FF]/10'
      } opacity-0 group-hover:opacity-100`} />
    </button>
  )
}