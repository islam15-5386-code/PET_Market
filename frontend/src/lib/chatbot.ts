import api from './api'
import type { ApiResponse } from '@/types'

export interface ChatbotProduct {
  id: number
  name: string
  price: number
  image: string | null
  rating: number
  slug: string
}

export interface ChatbotMessageResponse {
  session_id: string
  reply: string
  intent: string
  pet_type: string | null
  category: string | null
  age_group: string | null
  safety_level: string
  vet_warning: string | null
  recommended_products: ChatbotProduct[]
}

export async function sendChatbotMessage(payload: { message: string; session_id?: string }) {
  const { data } = await api.post<ApiResponse<ChatbotMessageResponse>>('/chatbot/message', payload)
  return data.data!
}
