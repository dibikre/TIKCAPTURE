import { useLiveStore } from '../stores/liveStore'

export function RecordingProgress() {
  const { recording } = useLiveStore()

  if (!recording.isRecording) return null

  return (
    <div className="space-y-4 animate-fade-in">
      {/* Progress bar */}
      <div className="h-3 bg-white/10 rounded-full overflow-hidden">
        <div
          className="h-full bg-linear-to-r from-[#FF0050] to-[#ff6b9d] transition-all duration-300 rounded-full"
          style={{ width: `${recording.progress}%` }}
        />
      </div>

      {/* Stats */}
      <div className="flex justify-between text-sm font-mono">
        <span className="text-muted-foreground">
          {recording.duration > 0 ? `${recording.progress.toFixed(0)}%` : 'En cours...'}
        </span>
        <span className="text-foreground">{recording.size}</span>
        <span className="text-muted-foreground">{recording.speed}</span>
      </div>
    </div>
  )
}