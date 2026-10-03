import axios from 'axios'

export const http = axios.create({
  baseURL: '/api',
  withCredentials: true,
  withXSRFToken: true,
  headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
})

export function fetchCsrfCookie() {
  return axios.get('/sanctum/csrf-cookie', { withCredentials: true })
}

let unauthorizedHandler = () => {}

/** Registered once in main.js so this module stays free of router/store imports. */
export function onUnauthorized(handler) {
  unauthorizedHandler = handler
}

const WRITE_METHODS = ['post', 'put', 'patch', 'delete']

function hasXsrfCookie() {
  return document.cookie.split('; ').some((cookie) => cookie.startsWith('XSRF-TOKEN='))
}

// Prime the CSRF cookie before the first write.
http.interceptors.request.use(async (config) => {
  if (WRITE_METHODS.includes(config.method) && !hasXsrfCookie()) {
    await fetchCsrfCookie()
  }
  return config
})

http.interceptors.response.use(
  (response) => response,
  async (error) => {
    const { response, config } = error

    // 419 → refresh the CSRF cookie and retry once.
    if (response?.status === 419 && config && !config._retried) {
      config._retried = true
      await fetchCsrfCookie()
      return http(config)
    }

    // 401 (except on /user, which is how we probe for a session) → session expired.
    if (response?.status === 401 && config?.url !== '/user') {
      unauthorizedHandler()
    }

    return Promise.reject(error)
  },
)

/** Human-readable message for a failed request (network, 5xx, or the API's own message). */
export function errorMessage(error, fallback = 'Something went wrong. Please try again.') {
  if (!error?.response) return "Can't reach the server. Check your connection and try again."
  if (error.response.status >= 500) return 'Something went wrong on our side. Please try again.'
  return error.response.data?.message || fallback
}
