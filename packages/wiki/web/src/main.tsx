import '@fontsource-variable/inter'
import './index.css'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { lazy, StrictMode, Suspense } from 'react'
import { createRoot } from 'react-dom/client'
import { BrowserRouter, Route, Routes } from 'react-router-dom'
import { AppShell } from '@/components/app-shell'
import { TooltipProvider } from '@/components/ui/tooltip'
import { ThemeProvider } from '@/lib/theme'
import { ListingPage } from '@/pages/listing'
import { NotFound } from '@/pages/not-found'
import { OverviewPage } from '@/pages/overview'
import { SubjectPage } from '@/pages/subject'

const AtlasPage = lazy(() => import('@/pages/atlas').then((m) => ({ default: m.AtlasPage })))
const queryClient = new QueryClient()

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <ThemeProvider>
      <QueryClientProvider client={queryClient}>
        <TooltipProvider delayDuration={200}>
          <BrowserRouter>
            <Routes>
              <Route element={<AppShell />}>
                <Route index element={<OverviewPage />} />
                <Route path="atlas" element={<Suspense fallback={null}><AtlasPage /></Suspense>} />
                <Route path=":kind" element={<ListingPage />} />
                <Route path=":kind/:id" element={<SubjectPage />} />
                <Route path="*" element={<NotFound />} />
              </Route>
            </Routes>
          </BrowserRouter>
        </TooltipProvider>
      </QueryClientProvider>
    </ThemeProvider>
  </StrictMode>,
)
