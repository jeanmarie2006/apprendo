export const CATS = {
  'Développement web': ['#4f46e5', '#7c3aed', '💻'], Design: ['#db2777', '#f43f5e', '🎨'], 'Marketing digital': ['#ea580c', '#f59e0b', '📣'],
  Entrepreneuriat: ['#059669', '#10b981', '🚀'], Bureautique: ['#0369a1', '#0ea5e9', '📊'], Langues: ['#7c2d12', '#c2410c', '🗣️'], Comptabilité: ['#334155', '#64748b', '🧮'],
}
export const cat = (c) => CATS[c] || ['#5b21b6', '#8b5cf6', '📘']
export const NIVEAUX = { debutant: 'Débutant', intermediaire: 'Intermédiaire', avance: 'Avancé' }
export const fmt = (n) => (n > 0 ? new Intl.NumberFormat('fr-FR').format(n).replace(/[  ]/g, ' ') + ' FCFA' : 'Gratuit')
export const duree = (min) => (min >= 60 ? `${Math.floor(min / 60)} h ${String(min % 60).padStart(2, '0')}` : `${min} min`)

/** Adresse d'intégration d'une vidéo YouTube ou Vimeo (null si l'adresse n'est pas reconnue). */
export function embed(url) {
  const yt = /(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([A-Za-z0-9_-]{6,})/.exec(url || '')
  if (yt) return `https://www.youtube-nocookie.com/embed/${yt[1]}?rel=0`
  const vm = /vimeo\.com\/(?:video\/)?(\d+)/.exec(url || '')
  return vm ? `https://player.vimeo.com/video/${vm[1]}` : null
}
