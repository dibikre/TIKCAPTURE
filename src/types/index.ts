export interface TikTokUser {
  uniqueId: string
  nickname: string
  avatar: string
  verified: boolean
  bio: string
}

export interface TikTokStats {
  followers: number
  following: number
}

export interface TikTokLive {
  isLive: boolean
  status: string
  title: string
  startTime: string
  viewers: number
  thumbnail?: string
  streamThumbnail?: string
}

export interface StreamQuality {
  qualityName: string
  resolution: string
  bitrate: string
  type: 'HLS' | 'FLV' | 'CMAF' | 'DASH'
  url: string
  hls?: string
  flv?: string
  cmaf?: string
  dash?: string
}

export interface SearchResult {
  success: boolean
  user: TikTokUser
  stats: TikTokStats
  live: TikTokLive
  streams: Record<string, StreamQuality>
}

export interface RecordingState {
  isRecording: boolean
  progress: number
  duration: number
  size: string
  speed: string
  startTime: number | null
}