import { Video, ArrowUpRight } from 'lucide-react'
import { Link, useNavigate } from 'react-router-dom'

// ─── Types ────────────────────────────────────────────────────────────────────

interface FooterLinkInternal {
  label: string
  to: string
}

interface FooterLinkExternal {
  label: string
  href: string
}

type FooterLink = FooterLinkInternal | FooterLinkExternal

interface FooterColumn {
  title: string
  links: FooterLink[]
}

// ─── Data ─────────────────────────────────────────────────────────────────────

const footerLinks: FooterColumn[] = [
  {
    title: 'Ressources',
    links: [
      { label: 'FAQ',             to: '/faq' },
      { label: 'Blog',            to: '/blog' },
      { label: 'Tutoriels vidéo', to: '/tutoriels-video' },
    ],
  },
  {
    title: 'Support',
    links: [
      { label: 'Contact',     to: '/contact' },
      { label: 'Suggestions', to: '/suggestion' },
    ],
  },
  {
    title: 'Légal',
    links: [
      { label: 'CGU & Confidentialité', to: '/cgu' },
      { label: 'Conditions de vente',   to: '/cgv' },
      { label: 'Mentions légales',      to: '/mentions-legales' },
    ],
  },
]

const socialLinks: { label: string; href: string; icon: React.ReactNode }[] = [
  {
    label: 'Twitter / X',
    href: 'javascript:void(0)',
    icon: (
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.746l7.73-8.835L1.254 2.25H8.08l4.254 5.622 5.91-5.622Zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
      </svg>
    ),
  },
  {
    label: 'Instagram',
    href: 'javascript:void(0)',
    icon: (
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <rect x="2" y="2" width="20" height="20" rx="5" ry="5" />
        <circle cx="12" cy="12" r="4" />
        <circle cx="17.5" cy="6.5" r="1" fill="currentColor" />
      </svg>
    ),
  },
  {
    label: 'GitHub',
    href: 'javascript:void(0)',
    icon: (
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22" />
      </svg>
    ),
  },
]

// ─── Utils ────────────────────────────────────────────────────────────────────

function isInternal(link: FooterLink): link is FooterLinkInternal {
  return 'to' in link
}

// ─── Component ────────────────────────────────────────────────────────────────

export function Footer() {
  const navigate = useNavigate()

  return (
    <footer className="relative z-10 mt-auto overflow-hidden">

      <div className="pointer-events-none absolute inset-x-0 top-0 h-px bg-linear-to-r from-transparent via-[#FF0050]/50 to-transparent" />
      <div className="pointer-events-none absolute -top-32 left-1/2 -translate-x-1/2 w-150 h-64 bg-[#FF0050]/5 rounded-full blur-[80px]" />

      <div className="relative container mx-auto px-6 sm:px-10 pt-16 pb-8 max-w-6xl">

        <div className="grid grid-cols-12 gap-y-12 gap-x-8 mb-16">

          {/* Brand */}
          <div className="col-span-12 lg:col-span-5 flex flex-col gap-6">

            <button
              onClick={() => navigate('/')}
              className="group w-fit flex items-center gap-3"
            >
              <div className="relative w-10 h-10 rounded-2xl bg-linear-to-br from-[#FF0050] to-[#ff6b9d] flex items-center justify-center shadow-lg shadow-[#FF0050]/25 transition-transform duration-300 group-hover:scale-110">
                <Video className="w-5 h-5 text-white" />
                <div className="absolute inset-0 rounded-2xl bg-white/10" />
              </div>
              <span className="text-xl font-bold tracking-tight gradient-text" style={{ fontVariantLigatures: 'none' }}>
                TikCapture
              </span>
            </button>

            <p className="text-sm leading-relaxed text-foreground max-w-xs">
              Enregistrez vos lives TikTok préférés en haute qualité —{' '}
              <span className="text-foreground font-medium">gratuitement, sans installation.</span>
            </p>

            <div className="flex flex-wrap gap-2">
              {[
                { dot: 'bg-emerald-400', label: 'Sécurisé SSL' },
                { dot: 'bg-[#FF0050]',  label: '100 % Gratuit' },
              ].map(({ dot, label }) => (
                <span
                  key={label}
                  className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium border border-white/8 bg-white/4 text-foreground backdrop-blur-sm"
                >
                  <span className={`w-1.5 h-1.5 rounded-full ${dot}`} />
                  {label}
                </span>
              ))}
            </div>

            <div className="flex gap-3 mt-auto">
              {socialLinks.map(({ label, href, icon }) => (
                <a
                  key={label}
                  href={href}
                  aria-label={label}
                  className="w-8 h-8 flex items-center justify-center rounded-lg text-foreground border border-white/8 bg-white/3 transition-all duration-200 hover:text-[#FF0050] hover:border-[#FF0050]/30 hover:bg-[#FF0050]/5"
                >
                  {icon}
                </a>
              ))}
            </div>
          </div>

          <div className="hidden lg:block lg:col-span-1" />

          {/* Link columns */}
          {footerLinks.map((col) => (
            <div key={col.title} className="col-span-4 lg:col-span-2">
              <p className="text-[10px] font-semibold uppercase tracking-[0.15em] text-foreground/70 mb-5">
                {col.title}
              </p>
              <ul className="space-y-3.5">
                {col.links.map((link) => (
                  <li key={link.label}>
                    {isInternal(link) ? (
                      <Link
                        to={link.to}
                        className="group inline-flex items-center gap-1 text-sm text-foreground transition-colors duration-200 hover:text-foreground"
                      >
                        <span>{link.label}</span>
                        <ArrowUpRight className="w-3 h-3 opacity-0 -translate-y-0.5 translate-x-0.5 transition-all duration-200 group-hover:opacity-60 group-hover:translate-y-0 group-hover:translate-x-0" />
                      </Link>
                    ) : (
                      <a
                        href={link.href}
                        className="group inline-flex items-center gap-1 text-sm text-foreground transition-colors duration-200 hover:text-foreground"
                      >
                        <span>{link.label}</span>
                        <ArrowUpRight className="w-3 h-3 opacity-0 -translate-y-0.5 translate-x-0.5 transition-all duration-200 group-hover:opacity-60 group-hover:translate-y-0 group-hover:translate-x-0" />
                      </a>
                    )}
                  </li>
                ))}
              </ul>
            </div>
          ))}
        </div>

        {/* Bottom bar */}
        <div className="relative pt-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-foreground/60">
          <div className="absolute inset-x-0 top-0 h-px bg-linear-to-r from-transparent via-white/[0.07] to-transparent" />
          <p>
            © 2026{' '}
            <span className="text-foreground/70 font-medium">TikCapture</span>
            {' '}· Tous droits réservés.
          </p>
          <p className="text-center sm:text-right text-foreground/80">
            Respectez les droits d'auteur et les CGU de TikTok.
          </p>
        </div>

      </div>
    </footer>
  )
}