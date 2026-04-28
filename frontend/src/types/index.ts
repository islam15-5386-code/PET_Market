// ─────────────────────────────────────────────────────────────────────────────
// Global TypeScript Types — Pet Marketplace
// ─────────────────────────────────────────────────────────────────────────────

// ── User ──────────────────────────────────────────────────────────────────────

export type UserRole = 'user' | 'admin'

export interface User {
  id: number
  name: string
  email: string
  role: UserRole
  phone: string | null
  avatar_url: string | null
  address: string | null
  city: string | null
  postal_code: string | null
  is_active: boolean
  created_at: string
}

// ── Auth ──────────────────────────────────────────────────────────────────────

export interface TokenPayload {
  access_token: string
  token_type: string
  expires_in: number
}

export interface AuthResponse {
  success: boolean
  message: string
  data: {
    user: User
    token: TokenPayload
  }
}

export interface RegisterInput {
  name: string
  email: string
  password: string
  password_confirmation: string
}

export interface LoginInput {
  email: string
  password: string
}

// ── API Response wrappers ─────────────────────────────────────────────────────

export interface ApiResponse<T = unknown> {
  success: boolean
  message?: string
  data?: T
  errors?: Record<string, string[]>
}

export interface PaginatedMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
  from?: number | null
  to?: number | null
}

export interface PaginatedResponse<T> {
  data: T[]
  meta: PaginatedMeta
}

// ── Category ──────────────────────────────────────────────────────────────────

export interface Category {
  id: number
  name: string
  slug: string
  icon: string | null
  description: string | null
  products_count?: number
}

// ── Product ───────────────────────────────────────────────────────────────────

export interface Product {
  id: number
  name: string
  slug: string
  price: string
  stock_quantity: number
  is_available: boolean
  primary_image: string | null
  location: string | null
  category: Category
  created_at: string
}

export interface ProductDetail extends Product {
  description: string | null
  images: string[]
  updated_at: string
}

export interface ProductFilters {
  search?: string
  category_id?: number | null
  min_price?: number | null
  max_price?: number | null
  location?: string
  sort?: 'newest' | 'oldest' | 'price_asc' | 'price_desc'
  per_page?: number
  page?: number
}

// ── Cart ──────────────────────────────────────────────────────────────────────

export interface CartItem {
  id: number
  product_id: number
  product_name: string
  product_slug: string
  primary_image: string | null
  unit_price: string
  quantity: number
  subtotal: string
  stock_quantity: number
  is_available: boolean
}

export interface CartSummary {
  items_count: number
  total_quantity: number
  subtotal: string
  shipping_fee: string
  total: string
}

export interface Cart {
  items: CartItem[]
  summary: CartSummary
}

// ── Order ─────────────────────────────────────────────────────────────────────

export type OrderStatus =
  | 'pending'
  | 'confirmed'
  | 'processing'
  | 'shipped'
  | 'delivered'
  | 'cancelled'

export interface OrderItem {
  id: number
  product_id: number | null
  product_name: string
  product_image: string | null
  quantity: number
  unit_price: string
  total_price: string
}

export interface ShippingInfo {
  name: string
  phone: string
  address: string
  city: string
  postal_code: string | null
}

export interface Order {
  id: number
  order_number: string
  status: OrderStatus
  subtotal: string
  shipping_fee: string
  total_amount: string
  items_count?: number
  items?: OrderItem[]
  shipping: ShippingInfo
  notes: string | null
  created_at: string
}

export interface CheckoutInput {
  shipping_name: string
  shipping_phone: string
  shipping_address: string
  shipping_city: string
  shipping_postal_code?: string
  notes?: string
}

// ── Auth context ──────────────────────────────────────────────────────────────

export type AuthState =
  | { status: 'idle' }
  | { status: 'loading' }
  | { status: 'authenticated'; user: User }
  | { status: 'unauthenticated' }

export interface AuthContextValue {
  state: AuthState
  user: User | null
  isAuthenticated: boolean
  isAdmin: boolean
  isLoading: boolean
  login: (input: LoginInput) => Promise<User>
  register: (input: RegisterInput) => Promise<void>
  logout: () => Promise<void>
  refreshUser: () => Promise<void>
}
