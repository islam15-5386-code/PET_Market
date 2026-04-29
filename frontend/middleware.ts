import { NextRequest, NextResponse } from 'next/server'
import { TOKEN_KEY } from '@/lib/api'

// Routes that require authentication
const PROTECTED_ROUTES = ['/profile', '/orders', '/cart', '/checkout']

// Routes that require admin role — checked via API in the page itself,
// but the middleware ensures a token exists at minimum
const ADMIN_ROUTES = ['/admin']

// Routes only for guests (redirect logged-in users away)
const GUEST_ONLY_ROUTES = [
  '/login',
  '/register',
  '/forgot-password',
  '/reset-password',
]

export function middleware(request: NextRequest) {
  const { pathname } = request.nextUrl
  const token = request.cookies.get(TOKEN_KEY)?.value

  const isAuthenticated = !!token

  // Redirect logged-in users away from guest-only pages
  if (isAuthenticated && GUEST_ONLY_ROUTES.some((r) => pathname.startsWith(r))) {
    return NextResponse.redirect(new URL('/', request.url))
  }

  // Redirect unauthenticated users away from protected pages
  const isProtected =
    PROTECTED_ROUTES.some((r) => pathname.startsWith(r)) ||
    ADMIN_ROUTES.some((r) => pathname.startsWith(r))

  if (!isAuthenticated && isProtected) {
    const loginUrl = new URL('/login', request.url)
    loginUrl.searchParams.set('redirect', pathname)
    return NextResponse.redirect(loginUrl)
  }

  return NextResponse.next()
}

export const config = {
  matcher: [
    /*
     * Match all request paths except:
     * - _next/static (static files)
     * - _next/image  (image optimisation)
     * - favicon.ico
     * - public folder assets
     */
    '/((?!_next/static|_next/image|favicon.ico|.*\\.(?:svg|png|jpg|jpeg|gif|webp)$).*)',
  ],
}
