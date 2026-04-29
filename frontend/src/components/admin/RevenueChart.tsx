'use client'

import type { RevenuePoint } from '@/lib/admin/dashboard'

interface RevenueChartProps {
  data: RevenuePoint[]
  grandTotal: string
}

export function RevenueChart({ data, grandTotal }: RevenueChartProps) {
  if (data.length === 0) {
    return (
      <div className="bg-white rounded-2xl border border-gray-200 p-6">
        <h2 className="text-base font-semibold text-gray-900 mb-2">Revenue (Last 30 Days)</h2>
        <div className="flex items-center justify-center h-40 text-gray-400 text-sm">
          No revenue data yet
        </div>
      </div>
    )
  }

  const W = 600
  const H = 160
  const PAD = { top: 16, right: 16, bottom: 32, left: 56 }
  const chartW = W - PAD.left - PAD.right
  const chartH = H - PAD.top - PAD.bottom

  const values = data.map((d) => Number(d.total))
  const maxVal = Math.max(...values, 1)

  // Scale helpers
  const x = (i: number) => PAD.left + (i / (data.length - 1)) * chartW
  const y = (v: number) => PAD.top + chartH - (v / maxVal) * chartH

  // Build SVG path
  const linePath = data
    .map((d, i) => `${i === 0 ? 'M' : 'L'} ${x(i).toFixed(1)} ${y(Number(d.total)).toFixed(1)}`)
    .join(' ')

  const areaPath =
    linePath +
    ` L ${x(data.length - 1).toFixed(1)} ${(PAD.top + chartH).toFixed(1)}` +
    ` L ${PAD.left.toFixed(1)} ${(PAD.top + chartH).toFixed(1)} Z`

  // Y-axis labels
  const yTicks = [0, 0.25, 0.5, 0.75, 1].map((t) => ({
    value: maxVal * t,
    y: y(maxVal * t),
  }))

  // X-axis — show first, middle, last date
  const xLabels = [0, Math.floor((data.length - 1) / 2), data.length - 1].map((i) => ({
    label: data[i]?.date?.slice(5) ?? '', // MM-DD
    x: x(i),
  }))

  function fmt(n: number) {
    if (n >= 1000) return `${(n / 1000).toFixed(1)}k`
    return String(Math.round(n))
  }

  return (
    <div className="bg-white rounded-2xl border border-gray-200 p-6">
      <div className="flex items-start justify-between mb-4">
        <h2 className="text-base font-semibold text-gray-900">Revenue (Last 30 Days)</h2>
        <div className="text-right">
          <p className="text-xs text-gray-400">Total</p>
          <p className="text-lg font-bold text-gray-900">৳{Number(grandTotal).toLocaleString()}</p>
        </div>
      </div>

      <svg
        viewBox={`0 0 ${W} ${H}`}
        className="w-full"
        aria-label="Revenue chart"
      >
        <defs>
          <linearGradient id="areaGrad" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stopColor="#f97316" stopOpacity="0.25" />
            <stop offset="100%" stopColor="#f97316" stopOpacity="0.02" />
          </linearGradient>
        </defs>

        {/* Grid lines */}
        {yTicks.map((tick, i) => (
          <line
            key={i}
            x1={PAD.left}
            x2={W - PAD.right}
            y1={tick.y}
            y2={tick.y}
            stroke="#f3f4f6"
            strokeWidth={1}
          />
        ))}

        {/* Area fill */}
        <path d={areaPath} fill="url(#areaGrad)" />

        {/* Line */}
        <path
          d={linePath}
          fill="none"
          stroke="#f97316"
          strokeWidth={2}
          strokeLinejoin="round"
          strokeLinecap="round"
        />

        {/* Data dots */}
        {data.map((d, i) => (
          <circle
            key={i}
            cx={x(i)}
            cy={y(Number(d.total))}
            r={3}
            fill="#f97316"
            opacity={Number(d.total) > 0 ? 1 : 0}
          />
        ))}

        {/* Y-axis labels */}
        {yTicks.map((tick, i) => (
          <text
            key={i}
            x={PAD.left - 6}
            y={tick.y + 4}
            textAnchor="end"
            fontSize={10}
            fill="#9ca3af"
          >
            {fmt(tick.value)}
          </text>
        ))}

        {/* X-axis labels */}
        {xLabels.map(({ label, x: lx }, i) => (
          <text
            key={i}
            x={lx}
            y={H - 4}
            textAnchor="middle"
            fontSize={10}
            fill="#9ca3af"
          >
            {label}
          </text>
        ))}
      </svg>
    </div>
  )
}
