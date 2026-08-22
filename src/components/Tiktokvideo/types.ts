export interface VideoStats { plays: number; comments: number; shares: number }

export interface VideoData {
  id: string; desc: string; cover: string; dynamicCover: string
  createTime: string; width: number; height: number
  size: number; sizeFormatted: string
  urlNoWatermark: string; urlWatermark: string
  hashtags: string[]; stats: VideoStats
}

export interface AuthorData {
  username: string; nickname: string; signature: string; avatar: string
  videoCount: number; followers: number; following: number
}

export interface RelatedVideo {
  id: string; desc: string; cover: string; dynamic_cover: string
  author_nickname: string; author_avatar: string; author_uniqueid: string
  play_count: number; digg_count: number; comment_count: number
  duration: number; video_url: string
}

export interface ApiResponse {
  success: boolean; error?: string
  maintenance?: boolean
  video?: VideoData; author?: AuthorData; related?: RelatedVideo[]
}
