import { Scale } from 'lucide-react'
import { useSeoMeta } from '../hooks/Useseometa'
import { BASE_URL } from '../lib/constants'

function Section({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <section className="space-y-3">
      <h2 className="text-xl font-bold text-foreground">{title}</h2>
      <div className="text-sm text-foreground leading-relaxed space-y-2">{children}</div>
    </section>
  )
}

function Row({ label, value }: { label: string; value: string }) {
  return (
    <div className="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-4">
      <span className="text-xs font-semibold uppercase tracking-wide text-muted-foreground/60 sm:w-36 shrink-0">{label}</span>
      <span>{value}</span>
    </div>
  )
}

export function MentionsLegales() {
  useSeoMeta({
    title:         'Mentions légales – TikCapture',
    description:   'Mentions légales de TikCapture : éditeur du site, hébergement, propriété intellectuelle, données personnelles et droit applicable.',
    canonical:     `${BASE_URL}/mentions-legales`,
    ogTitle:       'Mentions légales – TikCapture',
    ogDescription: 'Informations légales relatives au site TikCapture : éditeur, hébergeur, propriété intellectuelle et politique de confidentialité.',
    ogImage:       `${BASE_URL}/og-image.png`,
  })

  return (
    <main className="relative z-10 pt-24 md:pt-32 pb-20">
      <div className="mx-auto px-4 sm:px-6 w-full max-w-3xl">

        <div className="text-center mb-12 animate-fade-in">
          <div className="inline-flex items-center gap-2 px-4 py-2 rounded-full glass border border-white/10 mb-6">
            <Scale className="w-4 h-4 text-[#FF0050]" />
            <span className="text-sm text-muted-foreground">Conformément à la loi n°2004-575</span>
          </div>
          <h1 className="text-4xl sm:text-5xl font-black mb-4 tracking-tight">
            Mentions <span className="gradient-text">légales</span>
          </h1>
        </div>

        <div className="rounded-2xl glass border border-white/10 p-6 sm:p-10 space-y-8 animate-fade-in-up">

          <Section title="1. Éditeur du site">
            <div className="space-y-2.5">
              <Row label="Site web" value="tikcapture.live" />
              <Row label="Statut"   value="Particulier" />
              <Row label="Pays"     value="France" />
              <Row label="Contact"  value="Via la page Contact du site" />
            </div>
          </Section>

          <div className="h-px bg-white/5" />

          <Section title="2. Hébergement">
            <div className="space-y-2.5">
              <Row label="Hébergeur" value="Hostinger International Ltd" />
              <Row label="Adresse"   value="61 Lordou Vironos Street, 6023 Larnaca, Chypre" />
              <Row label="Site web"  value="hostinger.com" />
            </div>
          </Section>

          <div className="h-px bg-white/5" />

          <Section title="3. Propriété intellectuelle">
            <p>L'ensemble des éléments constituant le site TikCapture (textes, graphismes, logiciels, code source) est la propriété exclusive de TikCapture, sauf mention contraire.</p>
            <p>Toute reproduction, représentation, modification ou adaptation de tout ou partie des éléments du site est interdite sans autorisation préalable écrite.</p>
            <p>TikCapture n'est pas affilié à TikTok ou ByteDance Ltd. TikTok™ est une marque déposée de ByteDance Ltd.</p>
          </Section>

          <div className="h-px bg-white/5" />

          <Section title="4. Données personnelles">
            <p>Conformément au Règlement Général sur la Protection des Données (RGPD) et à la loi Informatique et Libertés, vous disposez d'un droit d'accès, de rectification et de suppression des données vous concernant.</p>
            <p>TikCapture ne collecte aucune donnée personnelle identifiable dans le cadre de l'utilisation normale du service. Pour toute demande relative à vos données, contactez-nous via la page Contact.</p>
          </Section>

          <div className="h-px bg-white/5" />

          <Section title="5. Cookies">
            <p>Le site utilise des cookies techniques nécessaires à son bon fonctionnement et des cookies analytiques anonymisés. En poursuivant votre navigation, vous acceptez l'utilisation de ces cookies.</p>
          </Section>

          <div className="h-px bg-white/5" />

          <Section title="6. Responsabilité">
            <p>TikCapture s'efforce d'assurer l'exactitude et la mise à jour des informations diffusées sur ce site. Toutefois, TikCapture ne peut garantir l'exactitude, la précision ou l'exhaustivité des informations mises à disposition.</p>
            <p>TikCapture décline toute responsabilité pour tout dommage résultant d'une intrusion frauduleuse d'un tiers, ou de toute défaillance technique.</p>
          </Section>

          <div className="h-px bg-white/5" />

          <Section title="7. Droit applicable">
            <p>Les présentes mentions légales sont soumises au droit français. En cas de litige, les tribunaux français seront seuls compétents.</p>
          </Section>

        </div>
      </div>
    </main>
  )
}