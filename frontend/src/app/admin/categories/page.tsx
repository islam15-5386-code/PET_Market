'use client'

import React, { useEffect, useState } from 'react'
import { Plus, Pencil, Trash2, Tag, X, Check } from 'lucide-react'
import { Spinner } from '@/components/ui/Spinner'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { Alert } from '@/components/ui/Alert'
import {
  fetchAdminCategories,
  createCategory,
  updateCategory,
  deleteCategory,
} from '@/lib/admin/categories'
import type { Category } from '@/types'

interface CategoryForm {
  name: string
  description: string
  icon: string
}

const EMPTY_FORM: CategoryForm = { name: '', description: '', icon: '' }

export default function AdminCategoriesPage() {
  const [categories, setCategories] = useState<Category[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [successMsg, setSuccessMsg] = useState('')

  // Create form state
  const [showCreate, setShowCreate] = useState(false)
  const [createForm, setCreateForm] = useState<CategoryForm>(EMPTY_FORM)
  const [creating, setCreating] = useState(false)
  const [createError, setCreateError] = useState('')

  // Edit state
  const [editingId, setEditingId] = useState<number | null>(null)
  const [editForm, setEditForm] = useState<CategoryForm>(EMPTY_FORM)
  const [saving, setSaving] = useState(false)

  // Delete state
  const [deletingId, setDeletingId] = useState<number | null>(null)
  const [confirmDeleteId, setConfirmDeleteId] = useState<number | null>(null)

  useEffect(() => {
    load()
  }, [])

  async function load() {
    setLoading(true)
    try {
      const data = await fetchAdminCategories()
      setCategories(data)
    } catch {
      setError('Failed to load categories.')
    } finally {
      setLoading(false)
    }
  }

  function flash(msg: string) {
    setSuccessMsg(msg)
    setTimeout(() => setSuccessMsg(''), 3000)
  }

  // ── Create ────────────────────────────────────────────────────────────────

  async function handleCreate(e: React.FormEvent) {
    e.preventDefault()
    if (!createForm.name.trim()) {
      setCreateError('Name is required.')
      return
    }
    setCreating(true)
    setCreateError('')
    try {
      const cat = await createCategory({
        name: createForm.name.trim(),
        description: createForm.description.trim() || undefined,
        icon: createForm.icon.trim() || undefined,
      })
      setCategories((prev) => [...prev, cat])
      setCreateForm(EMPTY_FORM)
      setShowCreate(false)
      flash('Category created successfully.')
    } catch (err: any) {
      const msg =
        err?.response?.data?.message ?? 'Failed to create category.'
      setCreateError(msg)
    } finally {
      setCreating(false)
    }
  }

  // ── Edit ──────────────────────────────────────────────────────────────────

  function startEdit(cat: Category) {
    setEditingId(cat.id)
    setEditForm({
      name: cat.name,
      description: (cat as any).description ?? '',
      icon: (cat as any).icon ?? '',
    })
  }

  async function handleSaveEdit(id: number) {
    setSaving(true)
    try {
      const updated = await updateCategory(id, {
        name: editForm.name.trim(),
        description: editForm.description.trim() || undefined,
        icon: editForm.icon.trim() || undefined,
      })
      setCategories((prev) => prev.map((c) => (c.id === id ? updated : c)))
      setEditingId(null)
      flash('Category updated successfully.')
    } catch (err: any) {
      setError(err?.response?.data?.message ?? 'Failed to update.')
    } finally {
      setSaving(false)
    }
  }

  // ── Delete ────────────────────────────────────────────────────────────────

  async function handleDelete(id: number) {
    setDeletingId(id)
    try {
      await deleteCategory(id)
      setCategories((prev) => prev.filter((c) => c.id !== id))
      setConfirmDeleteId(null)
      flash('Category deleted.')
    } catch (err: any) {
      setError(err?.response?.data?.message ?? 'Failed to delete category.')
    } finally {
      setDeletingId(null)
    }
  }

  return (
    <div className="flex flex-col gap-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold text-gray-900">Categories</h1>
        <Button onClick={() => setShowCreate((v) => !v)} size="sm">
          <Plus className="h-4 w-4" />
          New Category
        </Button>
      </div>

      {error && (
        <Alert variant="error" message={error} onDismiss={() => setError('')} />
      )}
      {successMsg && (
        <Alert variant="success" message={successMsg} />
      )}

      {/* ── Create Form ─────────────────────────────────────────────────── */}
      {showCreate && (
        <form
          onSubmit={handleCreate}
          className="bg-white rounded-2xl border border-gray-200 p-5 flex flex-col gap-4"
        >
          <h2 className="font-semibold text-gray-900">New Category</h2>
          {createError && (
            <Alert variant="error" message={createError} />
          )}
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <Input
              label="Name *"
              value={createForm.name}
              onChange={(e) =>
                setCreateForm((f) => ({ ...f, name: e.target.value }))
              }
              placeholder="e.g. Dog Food"
              required
            />
            <Input
              label="Icon (emoji)"
              value={createForm.icon}
              onChange={(e) =>
                setCreateForm((f) => ({ ...f, icon: e.target.value }))
              }
              placeholder="🐶"
            />
            <Input
              label="Description"
              value={createForm.description}
              onChange={(e) =>
                setCreateForm((f) => ({ ...f, description: e.target.value }))
              }
              placeholder="Optional short description"
            />
          </div>
          <div className="flex gap-2 justify-end">
            <Button
              type="button"
              variant="ghost"
              size="sm"
              onClick={() => {
                setShowCreate(false)
                setCreateForm(EMPTY_FORM)
                setCreateError('')
              }}
            >
              Cancel
            </Button>
            <Button type="submit" size="sm" loading={creating}>
              Create
            </Button>
          </div>
        </form>
      )}

      {/* ── Table ───────────────────────────────────────────────────────── */}
      <div className="bg-white rounded-2xl border border-gray-200 overflow-hidden">
        {loading ? (
          <div className="flex items-center justify-center py-20">
            <Spinner />
          </div>
        ) : categories.length === 0 ? (
          <div className="flex flex-col items-center justify-center py-20 text-gray-400">
            <Tag className="h-10 w-10 mb-3 opacity-30" />
            <p className="text-sm">No categories yet. Create one above.</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-gray-100 bg-gray-50 text-left">
                  <th className="px-4 py-3 font-medium text-gray-500">Icon</th>
                  <th className="px-4 py-3 font-medium text-gray-500">Name</th>
                  <th className="px-4 py-3 font-medium text-gray-500">Slug</th>
                  <th className="px-4 py-3 font-medium text-gray-500">Description</th>
                  <th className="px-4 py-3 font-medium text-gray-500 text-right">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-50">
                {categories.map((cat) =>
                  editingId === cat.id ? (
                    <tr key={cat.id} className="bg-orange-50">
                      <td className="px-4 py-3">
                        <input
                          value={editForm.icon}
                          onChange={(e) =>
                            setEditForm((f) => ({ ...f, icon: e.target.value }))
                          }
                          className="w-16 text-center border border-gray-300 rounded-lg px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400"
                          placeholder="🐾"
                        />
                      </td>
                      <td className="px-4 py-3">
                        <input
                          value={editForm.name}
                          onChange={(e) =>
                            setEditForm((f) => ({ ...f, name: e.target.value }))
                          }
                          className="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 w-full"
                          required
                        />
                      </td>
                      <td className="px-4 py-3 text-gray-400 text-xs">
                        {cat.slug}
                      </td>
                      <td className="px-4 py-3">
                        <input
                          value={editForm.description}
                          onChange={(e) =>
                            setEditForm((f) => ({
                              ...f,
                              description: e.target.value,
                            }))
                          }
                          className="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 w-full"
                          placeholder="Optional"
                        />
                      </td>
                      <td className="px-4 py-3">
                        <div className="flex items-center justify-end gap-2">
                          <button
                            onClick={() => setEditingId(null)}
                            className="p-1.5 rounded-lg hover:bg-gray-100 text-gray-400"
                            title="Cancel"
                          >
                            <X className="h-4 w-4" />
                          </button>
                          <button
                            onClick={() => handleSaveEdit(cat.id)}
                            disabled={saving}
                            className="p-1.5 rounded-lg bg-orange-500 text-white hover:bg-orange-600 disabled:opacity-60"
                            title="Save"
                          >
                            {saving ? (
                              <Spinner size="sm" />
                            ) : (
                              <Check className="h-4 w-4" />
                            )}
                          </button>
                        </div>
                      </td>
                    </tr>
                  ) : (
                    <tr
                      key={cat.id}
                      className="hover:bg-gray-50 transition-colors"
                    >
                      <td className="px-4 py-3 text-xl">
                        {(cat as any).icon ?? '🏷️'}
                      </td>
                      <td className="px-4 py-3 font-medium text-gray-900">
                        {cat.name}
                      </td>
                      <td className="px-4 py-3 text-gray-500 text-xs">
                        {cat.slug}
                      </td>
                      <td className="px-4 py-3 text-gray-500">
                        {(cat as any).description ?? (
                          <span className="text-gray-300 italic">—</span>
                        )}
                      </td>
                      <td className="px-4 py-3">
                        <div className="flex items-center justify-end gap-2">
                          {confirmDeleteId === cat.id ? (
                            <>
                              <span className="text-xs text-red-600 font-medium mr-1">
                                Delete?
                              </span>
                              <button
                                onClick={() => setConfirmDeleteId(null)}
                                className="text-xs px-2 py-1 rounded-lg border border-gray-300 hover:bg-gray-50"
                              >
                                No
                              </button>
                              <button
                                onClick={() => handleDelete(cat.id)}
                                disabled={deletingId === cat.id}
                                className="text-xs px-2 py-1 rounded-lg bg-red-600 text-white hover:bg-red-700 disabled:opacity-60"
                              >
                                {deletingId === cat.id ? 'Deleting…' : 'Yes'}
                              </button>
                            </>
                          ) : (
                            <>
                              <button
                                onClick={() => startEdit(cat)}
                                className="p-1.5 rounded-lg hover:bg-gray-100 text-gray-500 hover:text-gray-700"
                                title="Edit"
                              >
                                <Pencil className="h-4 w-4" />
                              </button>
                              <button
                                onClick={() => setConfirmDeleteId(cat.id)}
                                className="p-1.5 rounded-lg hover:bg-red-50 text-gray-400 hover:text-red-600"
                                title="Delete"
                              >
                                <Trash2 className="h-4 w-4" />
                              </button>
                            </>
                          )}
                        </div>
                      </td>
                    </tr>
                  ),
                )}
              </tbody>
            </table>
          </div>
        )}
      </div>

      <p className="text-xs text-gray-400">
        {categories.length} categor{categories.length === 1 ? 'y' : 'ies'} total
      </p>
    </div>
  )
}
