// routers.tsx
import { createBrowserRouter } from 'react-router-dom'
import { lazy, Suspense, type ComponentType } from 'react'
import App from './App'

// Helper pour convertir export nommé → export default
function lazyNamed<T extends ComponentType<unknown>>(importFn: () => Promise<{ [key: string]: T }>, name: string) {
  return lazy(async () => {
    const module = await importFn()
    return { default: module[name] as T }
  })
}

// Lazy load avec export nommés
const Home = lazyNamed(() => import('./components/Home'), 'Home')
const HowToRecord = lazyNamed(() => import('./components/Howtorecord'), 'HowToRecord')
const HowToDownload = lazyNamed(() => import('./components/Howtodownload'), 'HowToDownload')
const TikTokVideo = lazyNamed(() => import('./components/Tiktokvideo'), 'TikTokVideo')
const Blog = lazyNamed(() => import('./components/Blog'), 'Blog')
const FAQ = lazyNamed(() => import('./components/Faq'), 'FAQ')
const Tutorials = lazyNamed(() => import('./components/Tutorials'), 'Tutorials')
const Contact = lazyNamed(() => import('./components/Contact'), 'Contact')
const Suggestions = lazyNamed(() => import('./components/Suggestions'), 'Suggestions')
const CGU = lazyNamed(() => import('./components/Cgu'), 'CGU')
const CGV = lazyNamed(() => import('./components/Cgv'), 'CGV')
const MentionsLegales = lazyNamed(() => import('./components/Mentionslegales'), 'MentionsLegales')
const NotFound = lazyNamed(() => import('./components/NotFound'), 'NotFound')
const Actors = lazyNamed(() => import('./components/Actors'), 'Actors')
const ActorDetails = lazyNamed(() => import('./components/ActorDetails'), 'ActorDetails')
const ActorVideoDetails = lazyNamed(() => import('./components/ActorVideoDetails'), 'ActorVideoDetails')
const Login = lazyNamed(() => import('./components/Login'), 'Login')
const Register = lazyNamed(() => import('./components/Register'), 'Register')
const VerifyAccount = lazyNamed(() => import('./components/VerifyAccount'), 'VerifyAccount')
const ForgotPassword = lazyNamed(() => import('./components/ForgotPassword'), 'ForgotPassword')
const ResetPassword = lazyNamed(() => import('./components/ResetPassword'), 'ResetPassword')
const Dashboard = lazyNamed(() => import('./components/Dashboard'), 'Dashboard')

// Wrapper Suspense
const withSuspense = (Component: ComponentType) => (
  <Suspense fallback={
    <div className="min-h-screen flex items-center justify-center">
      <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-[#FF0050]" />
    </div>
  }>
    <Component />
  </Suspense>
)

export const router = createBrowserRouter([
  {
    path: '/',
    element: <App />,
    children: [
      { index: true, element: withSuspense(Home) },
      { path: 'comment-enregistrer', element: withSuspense(HowToRecord) },
      { path: 'comment-telecharger', element: withSuspense(HowToDownload) },
      { path: 'tiktok-video', element: withSuspense(TikTokVideo) },
      { path: 'blog', element: withSuspense(Blog) },
      { path: 'blog/:slug', element: withSuspense(Blog) },
      { path: 'faq', element: withSuspense(FAQ) },
      { path: 'tutoriels-video', element: withSuspense(Tutorials) },
      { path: 'contact', element: withSuspense(Contact) },
      { path: 'suggestion', element: withSuspense(Suggestions) },
      { path: 'createurs', element: withSuspense(Actors) },
      { path: 'createurs/:actorId', element: withSuspense(ActorDetails) },
      { path: 'createurs/:actorId/videos/:videoId', element: withSuspense(ActorVideoDetails) },
      { path: 'connexion', element: withSuspense(Login) },
      { path: 'inscription', element: withSuspense(Register) },
      { path: 'verifier-compte', element: withSuspense(VerifyAccount) },
      { path: 'mot-de-passe-oublie', element: withSuspense(ForgotPassword) },
      { path: 'reset-password', element: withSuspense(ResetPassword) },
      { path: 'dashboard', element: withSuspense(Dashboard) },
      { path: 'cgu', element: withSuspense(CGU) },
      { path: 'cgv', element: withSuspense(CGV) },
      { path: 'mentions-legales', element: withSuspense(MentionsLegales) },
      { path: '*', element: withSuspense(NotFound) },
    ],
  },
])