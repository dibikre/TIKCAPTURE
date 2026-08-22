export interface ActorVideo {
  id: string
  title: string
  thumbnail: string
  duration: string
  views: string
  description?: string
  videoUrl?: string
  transcript?: string
}

export interface VideoPlaybackTask {
  time: string
  label: string
}

export interface ActorVideoDetails {
  description: string
  videoUrl: string
  transcript: string
  playbackTasks: VideoPlaybackTask[]
  sprite: {
    imageUrl: string
    columns: 8
    rows: 8
  }
}

export interface Actor {
  id: string
  name: string
  profileName: string
  platform: string
  avatar: string
  coverImage: string
  description: string
  totalVideos: number
  videos: ActorVideo[]
}

export interface ActorVideoPageData {
  actor: Actor
  video: ActorVideo
  playbackTasks: VideoPlaybackTask[]
  sprite: {
    imageUrl: string
    columns: 8
    rows: 8
  }
  relatedVideos: Array<{ actorId: string; actorName: string; video: ActorVideo }>
  relatedCreators: Actor[]
}
