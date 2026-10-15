import { createHash } from 'node:crypto'
import { existsSync, readFileSync } from 'node:fs'
import path from 'node:path'
import tailwindcss from '@tailwindcss/vite'
import react from '@vitejs/plugin-react'
import { defineConfig } from 'vite'

const swfVersion = createHash('sha1')
  .update(
    ['item.swf', 'avatar.swf', 'monster.swf']
      .map((file) => path.resolve(__dirname, 'public/swfs', file))
      .filter((file) => existsSync(file))
      .map((file) => readFileSync(file).toString('base64'))
      .join(''),
  )
  .digest('hex')
  .slice(0, 10)

export default defineConfig({
  plugins: [react(), tailwindcss()],
  define: { __SWF_VERSION__: JSON.stringify(swfVersion) },
  resolve: { alias: { '@': path.resolve(__dirname, './src') } },
  server: { proxy: { '/api': 'http://127.0.0.1:8765', '/proxy': 'http://127.0.0.1:8765' } },
  build: { outDir: '../public/app', emptyOutDir: true },
})
