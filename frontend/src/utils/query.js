/** Drops empty values so route queries and API params stay clean. */
export function cleanQuery(query) {
  return Object.fromEntries(
    Object.entries(query).filter(
      ([, value]) => value !== undefined && value !== null && value !== '',
    ),
  )
}

/** First value of a route query param as a string ('' when absent). */
export function queryString(value) {
  const first = Array.isArray(value) ? value[0] : value
  return first == null ? '' : String(first)
}
