import axios from 'axios'

const api = axios.create({
  baseURL: '/api/v1',
  headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
})

api.interceptors.request.use(config => {
  const token = localStorage.getItem('crm_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

export function setToken(token) {
  localStorage.setItem('crm_token', token)
}

export function clearToken() {
  localStorage.removeItem('crm_token')
}

export function hasToken() {
  return !!localStorage.getItem('crm_token')
}

export async function login(email, password, deviceName, platform, totpCode = null) {
  const { data } = await api.post('/auth/login', {
    email,
    password,
    device_name: deviceName,
    platform,
    totp_code: totpCode,
  })
  return data
}

export async function logout() {
  await api.post('/auth/logout')
  clearToken()
  localStorage.removeItem('crm_device_id')
}

export async function getMe() {
  const { data } = await api.get('/me')
  return data.data
}

export async function syncPull(since = null, deviceName = null, platform = null) {
  const body = {}
  if (since) body.since = since
  if (deviceName) body.device_name = deviceName
  if (platform) body.platform = platform
  const { data } = await api.post('/sync/pull', body)
  return data
}

export async function syncPush(operations) {
  const { data } = await api.post('/sync/push', { operations })
  return data
}

export async function heartbeat() {
  const { data } = await api.post('/devices/heartbeat')
  return data
}

export async function uploadPhoto(memberId, file) {
  const form = new FormData()
  form.append('member_id', memberId)
  form.append('photo', file)
  const { data } = await api.post('/photos', form, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
  return data
}

export default api
