export const BASE_URL = import.meta.env.VITE_API_BASE_URL || (import.meta.env.DEV ? 'http://127.0.0.1:8000' : '')
export const AUTH_API = `${BASE_URL}/segment_page/api/authentification.php`
