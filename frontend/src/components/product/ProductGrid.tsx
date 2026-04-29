import { ProductCard } from './ProductCard'
import { Spinner } from '@/components/ui/Spinner'
import { Package } from 'lucide-react'
import type { Product } from '@/types'

interface ProductGridProps {
  products: Product[]
  loading: boolean
  error?: string | null
}

export function ProductGrid({ products, loading, error }: ProductGridProps) {
  if (loading) {
    return (
      <div className="flex items-center justify-center py-24">
        <Spinner size="lg" />
      </div>
    )
  }

  if (error) {
    return (
      <div className="flex flex-col items-center justify-center py-24 text-center text-gray-500 gap-3">
        <Package className="h-12 w-12 text-gray-300" />
        <p className="font-medium">{error}</p>
      </div>
    )
  }

  if (products.length === 0) {
    return (
      <div className="flex flex-col items-center justify-center py-24 text-center gap-3">
        <Package className="h-12 w-12 text-gray-300" />
        <p className="font-semibold text-gray-700">No products found</p>
        <p className="text-sm text-gray-500">Try adjusting your filters or search term.</p>
      </div>
    )
  }

  return (
    <div className="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
      {products.map((product) => (
        <ProductCard key={product.id} product={product} />
      ))}
    </div>
  )
}
