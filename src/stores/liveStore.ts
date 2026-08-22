import { create } from 'zustand'
import type { SearchResult, StreamQuality, RecordingState } from '../types'

interface LiveStore {
  // Search
  searchQuery: string
  setSearchQuery: (query: string) => void
  
  // Results
  searchResult: SearchResult | null
  setSearchResult: (result: SearchResult | null) => void
  
  // Selected quality
  selectedQuality: string | null
  setSelectedQuality: (quality: string) => void
  getSelectedStream: () => StreamQuality | null
  
  // Recording duration
  recordingDuration: number // 0 = continu, >0 = seconds
  setRecordingDuration: (duration: number) => void
  
  // Recording state
  recording: RecordingState
  startRecording: () => void
  stopRecording: () => void
  updateRecordingProgress: (progress: Partial<RecordingState>) => void
  
  // Abort controller for fetch cancellation
  abortController: AbortController | null
  setAbortController: (controller: AbortController | null) => void

  // Suggestion after recording
  showSuggestion: boolean
  setShowSuggestion: (show: boolean) => void
  
  // Reset
  reset: () => void
}

const initialRecordingState: RecordingState = {
  isRecording: false,
  progress: 0,
  duration: 0,
  size: '0 MB',
  speed: '0 KB/s',
  startTime: null,
}

export const useLiveStore = create<LiveStore>((set, get) => ({
  searchQuery: '',
  setSearchQuery: (query) => set({ searchQuery: query }),
  
  searchResult: null,
  setSearchResult: (result) => set({ searchResult: result }),
  
  selectedQuality: null,
  setSelectedQuality: (quality) => set({ selectedQuality: quality }),
  getSelectedStream: () => {
    const { searchResult, selectedQuality } = get()
    if (!searchResult || !selectedQuality) return null
    return searchResult.streams[selectedQuality] || null
  },
  
  recordingDuration: 0,
  setRecordingDuration: (duration) => set({ recordingDuration: duration }),
  
  recording: initialRecordingState,
  startRecording: () => set({
    recording: {
      ...initialRecordingState,
      isRecording: true,
      startTime: Date.now(),
    }
  }),
  stopRecording: () => set({
    recording: initialRecordingState,
    abortController: null,
    showSuggestion: true, // ← affiche la suggestion après l'arrêt
  }),
  updateRecordingProgress: (progress) => set((state) => ({
    recording: { ...state.recording, ...progress }
  })),
  
  abortController: null,
  setAbortController: (controller) => set({ abortController: controller }),

  showSuggestion: false,
  setShowSuggestion: (show) => set({ showSuggestion: show }),
  
  reset: () => set({
    searchResult: null,
    selectedQuality: null,
    recordingDuration: 0,
    recording: initialRecordingState,
    abortController: null,
    showSuggestion: false,
  }),
}))