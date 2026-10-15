export interface RufflePlayer extends HTMLElement {
  load: (options: { url: string; parameters: Record<string, string> }) => Promise<void>
  playAnim?: (anim: string) => void
}

declare global {
  interface Window {
    RufflePlayer?: { config?: Record<string, unknown>; newest: () => { createPlayer: () => RufflePlayer } }
  }
}

let ruffle: Promise<void> | undefined

export function loadRuffle(): Promise<void> {
  ruffle ??= new Promise((resolve, reject) => {
    window.RufflePlayer = window.RufflePlayer ?? ({} as Window['RufflePlayer'])
    window.RufflePlayer!.config = {
      publicPath: '/ruffle/',
      backgroundColor: '#00000000',
      wmode: 'transparent',
      autoplay: 'on',
      unmuteOverlay: 'hidden',
      splashScreen: false,
      contextMenu: 'off',
      allowScriptAccess: true,
    }
    const script = document.createElement('script')
    script.src = '/ruffle/ruffle.js'
    script.onload = () => resolve()
    script.onerror = () => reject(new Error('Ruffle failed to load'))
    document.head.append(script)
  })
  return ruffle
}
