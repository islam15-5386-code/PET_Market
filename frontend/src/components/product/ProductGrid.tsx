import { ProductCard } from './ProductCard'
import { AlertTriangle } from 'lucide-react'
import type { Product } from '@/types'
import { EmptyState } from '@/components/ui/EmptyState'
import { LoadingSkeleton } from '@/components/ui/LoadingSkeleton'

interface ProductGridProps {
  products: Product[]
  loading: boolean
  error?: string | null
}

export function ProductGrid({ products, loading, error }: ProductGridProps) {
  if (loading) {
    return (
      <div className="grid grid-cols-2 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        {Array.from({ length: 8 }).map((_, i) => (
          <div key={i} className="surface-card overflow-hidden">
            <LoadingSkeleton className="aspect-square w-full rounded-none" />
            <div className="space-y-2 p-4">
              <LoadingSkeleton className="h-4 w-4/5" />
              <LoadingSkeleton className="h-3 w-2/3" />
              <LoadingSkeleton className="h-8 w-full" />
            </div>
          </div>
        ))}
      </div>
    )
  }

  if (error) {
    return <EmptyState title="Unable to load products" description={error} icon={<AlertTriangle className="h-7 w-7" />} />
  }

  if (products.length === 0) {
    return <EmptyState title="No products found" description="Try adjusting filters, budget range, or AI query." />
  }

  return (
    <div className="grid grid-cols-2 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
      {products.map((product) => (
        <ProductCard key={product.id} product={product} />
      ))}
    </div>
  )
}
