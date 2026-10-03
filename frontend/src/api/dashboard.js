import { http } from './http'

export const getDashboard = (params = {}) =>
  http.get('/dashboard', { params }).then((r) => r.data.data)
