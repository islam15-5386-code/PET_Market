import Link from 'next/link'
import Image from 'next/image'

interface CategoryCardProps {
  slug: string
  name: string
  icon: string | null
  imageUrl?: string | null
  productsCount?: number
}

export function CategoryCard({ slug, name, icon, imageUrl, productsCount }: CategoryCardProps) {
  return (
    <Link
      href={`/products?category=${slug}`}
      className="flex flex-col items-center gap-3 p-5 bg-white rounded-2xl border border-gray-200 hover:border-orange-300 hover:shadow-md transition-all group"
    >
      <div className="relative h-16 w-16 overflow-hidden rounded-2xl bg-orange-50">
        {imageUrl ? (
          <Image
            src={imageUrl}
            alt={name}
            fill
            sizes="64px"
            className="object-cover group-hover:scale-105 transition-transform duration-200"
          />
        ) : (
          <span className="grid h-full place-items-center text-3xl">{icon ?? '??'}</span>
        )}
      </div>
      <div className="text-center">
        <p className="text-sm font-semibold text-gray-800 group-hover:text-orange-600 transition-colors">
          {name}
        </p>
        {productsCount != null && (
          <p className="text-xs text-gray-400 mt-0.5">{productsCount} items</p>
        )}
      </div>
    </Link>
  )
}
