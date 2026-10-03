import { http } from './http'

export const listCategories = (params = {}) =>
  http.get('/categories', { params }).then((r) => r.data.data)
export const createCategory = (payload) =>
  http.post('/categories', payload).then((r) => r.data.data)
export const updateCategory = (id, payload) =>
  http.put(`/categories/${id}`, payload).then((r) => r.data.data)
export const deleteCategory = (id) => http.delete(`/categories/${id}`)
