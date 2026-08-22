import { StrictMode } from 'react'
import { hydrateRoot, createRoot } from 'react-dom/client'
import { RouterProvider } from 'react-router-dom'
import { router } from './routers.tsx'
import './index.css'

const rootElement = document.getElementById('root')!
const app = (
  <StrictMode>
    <RouterProvider router={router} />
  </StrictMode>
)

if (rootElement.hasChildNodes()) {
  hydrateRoot(rootElement, app)
} else {
  createRoot(rootElement).render(app)
}