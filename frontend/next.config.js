/** @type {import('next').NextConfig} */
const remotePatterns = [
  {
    protocol: 'http',
    hostname: 'localhost',
    port: '8000',
    pathname: '/storage/**',
  },
  {
    protocol: 'http',
    hostname: '127.0.0.1',
    port: '8000',
    pathname: '/storage/**',
  },
  {
    protocol: 'https',
    hostname: 'api.petmarketplace.com',
    pathname: '/storage/**',
  },
  {
    protocol: 'https',
    hostname: 'images.unsplash.com',
    pathname: '/**',
  },
  {
    protocol: 'https',
    hostname: 'images.pexels.com',
    pathname: '/**',
  },
  {
    protocol: 'https',
    hostname: 'placehold.co',
    pathname: '/**',
  },
]

const apiUrl = process.env.NEXT_PUBLIC_API_URL
if (apiUrl) {
  try {
    const parsed = new URL(apiUrl)
    remotePatterns.push({
      protocol: parsed.protocol.replace(':', ''),
      hostname: parsed.hostname,
      port: parsed.port || undefined,
      pathname: '/storage/**',
    })
  } catch {
    // ignore invalid URL in build env
  }
}

const nextConfig = {
  images: {
    remotePatterns,
  },

  async headers() {
    return [
      {
        source: '/(.*)',
        headers: [
          { key: 'X-Frame-Options',       value: 'SAMEORIGIN' },
          { key: 'X-Content-Type-Options', value: 'nosniff' },
          { key: 'Referrer-Policy',        value: 'strict-origin-when-cross-origin' },
        ],
      },
    ]
  },
}

module.exports = nextConfig
