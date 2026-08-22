import { Home, Download, Video, HelpCircle, Users, BookOpen } from 'lucide-react'
import React from 'react'

export interface NavItem {
  label: string
  to: string
  icon: React.ReactNode
}

export const navItems: NavItem[] = [
  { label: 'Accueil',             to: '/',                    icon: <Home       className="w-4 h-4" /> },
  { label: 'Télécharger Vidéos', to: '/tiktok-video',        icon: <Download   className="w-4 h-4" /> },
  { label: 'Comment Enregistrer', to: '/comment-enregistrer', icon: <Video      className="w-4 h-4" /> },
  { label: 'Comment Télécharger', to: '/comment-telecharger', icon: <HelpCircle className="w-4 h-4" /> },
  { label: 'Créateurs',           to: '/createurs',           icon: <Users      className="w-4 h-4" /> },
  { label: 'Blog',                to: '/blog',                icon: <BookOpen   className="w-4 h-4" /> },
]
