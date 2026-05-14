export function formatBDT(value: number | string | null | undefined): string {
  const amount = Number(value ?? 0)
  if (!Number.isFinite(amount)) return '৳0'

  return `৳${amount.toLocaleString('en-BD', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  })}`
}

