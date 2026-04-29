import Link from 'next/link'

interface CategoryCardProps {
  id: number
  name: string
  icon: string | null
  productsCount?: number
}

export function CategoryCard({ id, name, icon, productsCount }: CategoryCardProps) {
  return (
    <Link
      href={`/products?category_id=${id}`}
      className="flex flex-col items-center gap-3 p-5 bg-white rounded-2xl border border-gray-200 hover:border-orange-300 hover:shadow-md transition-all group"
    >
      <span className="text-4xl group-hover:scale-110 transition-transform duration-200">
        {icon ?? '🐾'}
      </span>
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
