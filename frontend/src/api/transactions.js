import { http } from './http'

/** Resolves to the full paginated body: { data, links, meta }. */
export const listTransactions = (params = {}) =>
  http.get('/transactions', { params }).then((r) => r.data)
export const getTransaction = (id) => http.get(`/transactions/${id}`).then((r) => r.data.data)
export const createTransaction = (payload) =>
  http.post('/transactions', payload).then((r) => r.data.data)
export const updateTransaction = (id, payload) =>
  http.put(`/transactions/${id}`, payload).then((r) => r.data.data)
export const deleteTransaction = (id) => http.delete(`/transactions/${id}`)
