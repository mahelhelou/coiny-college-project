import { http } from './http'

export const fetchUser = () => http.get('/user').then((r) => r.data.data)
export const login = (credentials) => http.post('/login', credentials).then((r) => r.data.data)
export const register = (payload) => http.post('/register', payload).then((r) => r.data.data)
export const logout = () => http.post('/logout')
export const updateProfile = (payload) => http.put('/profile', payload).then((r) => r.data.data)
export const updatePassword = (payload) => http.put('/profile/password', payload)
