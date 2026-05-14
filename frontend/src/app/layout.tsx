import type { Metadata } from 'next'
import { Inter } from 'next/font/google'
import './globals.css'
import { AuthProvider } from '@/context/AuthContext'
import { Navbar } from '@/components/layout/Navbar'
import { Footer } from '@/components/layout/Footer'
import { ChatbotWidget } from '@/components/layout/ChatbotWidget'

const inter = Inter({ subsets: ['latin'] })

export const metadata: Metadata = {
  title: {
    default: 'Pet Marketplace',
    template: '%s | Pet Marketplace',
  },
  description:
    "Bangladesh's trusted online marketplace for pet food, accessories and health products.",
}

export default function RootLayout({
  children,
}: {
  children: React.ReactNode
}) {
  return (
    <html lang="en">
      <body className={inter.className}>
        <AuthProvider>
          <div className="flex flex-col min-h-screen">
            <Navbar />
            <main className="flex-1">{children}</main>
            <Footer />
            <ChatbotWidget />
          </div>
        </AuthProvider>
      </body>
    </html>
  )
}
