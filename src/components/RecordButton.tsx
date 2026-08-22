import { useLiveStore } from '../stores/liveStore'
import { Radio, Square} from 'lucide-react'
import { useRecording } from '../hooks/useRecording'

export function RecordButton() {
  const { selectedQuality, recording } = useLiveStore()
  const { startRecording, stopRecording } = useRecording()
  
  const isRecording = recording.isRecording
  
  if (!selectedQuality) {
    return (
      <button
        disabled
        className="w-full py-4 rounded-2xl bg-muted/20 text-muted-foreground font-semibold cursor-not-allowed"
      >
        Sélectionnez une qualité pour commencer
      </button>
    )
  }
  
  return (
    <button
      onClick={isRecording ? stopRecording : startRecording}
      disabled={!selectedQuality}
      className={`
        w-full py-4 rounded-2xl font-semibold text-lg
        flex items-center justify-center gap-3
        transition-all duration-300
        ${isRecording
          ? 'bg-red-500 hover:bg-red-600 text-white animate-pulse'
          : 'bg-linear-to-r from-[#FF0050] to-[#ff6b9d] hover:shadow-lg hover:shadow-[#FF0050]/30 text-white hover:scale-[1.02]'
        }
        active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed
      `}
    >
      {isRecording ? (
        <>
          <Square className="w-5 h-5 fill-current" />
          <span>Arrêter l'enregistrement</span>
        </>
      ) : (
        <>
          <Radio className="w-5 h-5" />
          <span>Démarrer l'enregistrement</span>
        </>
      )}
    </button>
  )
}