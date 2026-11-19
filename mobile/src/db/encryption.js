/**
 * AES-GCM encryption with PBKDF2 PIN-derived key.
 * Key is held in memory only while the app is unlocked.
 */

const PBKDF2_ITERATIONS = 310_000
const SALT_KEY = 'crm_enc_salt'
const PIN_ATTEMPTS_KEY = 'crm_pin_attempts'
const MAX_PIN_ATTEMPTS = 5

let _key = null // CryptoKey — held in memory only

function getSalt() {
  let salt = localStorage.getItem(SALT_KEY)
  if (!salt) {
    const bytes = crypto.getRandomValues(new Uint8Array(16))
    salt = btoa(String.fromCharCode(...bytes))
    localStorage.setItem(SALT_KEY, salt)
  }
  return Uint8Array.from(atob(salt), c => c.charCodeAt(0))
}

export async function deriveKey(pin) {
  const enc = new TextEncoder()
  const keyMaterial = await crypto.subtle.importKey(
    'raw',
    enc.encode(String(pin)),
    'PBKDF2',
    false,
    ['deriveKey'],
  )
  return crypto.subtle.deriveKey(
    { name: 'PBKDF2', salt: getSalt(), iterations: PBKDF2_ITERATIONS, hash: 'SHA-256' },
    keyMaterial,
    { name: 'AES-GCM', length: 256 },
    false,
    ['encrypt', 'decrypt'],
  )
}

export async function encrypt(data) {
  if (!_key) throw new Error('App is locked')
  const iv = crypto.getRandomValues(new Uint8Array(12))
  const enc = new TextEncoder()
  const ciphertext = await crypto.subtle.encrypt(
    { name: 'AES-GCM', iv },
    _key,
    enc.encode(JSON.stringify(data)),
  )
  return {
    iv: Array.from(iv),
    ct: Array.from(new Uint8Array(ciphertext)),
  }
}

export async function decrypt(blob) {
  if (!_key) throw new Error('App is locked')
  const plaintext = await crypto.subtle.decrypt(
    { name: 'AES-GCM', iv: new Uint8Array(blob.iv) },
    _key,
    new Uint8Array(blob.ct),
  )
  return JSON.parse(new TextDecoder().decode(plaintext))
}

export async function unlock(pin) {
  const attempts = parseInt(localStorage.getItem(PIN_ATTEMPTS_KEY) || '0', 10)
  if (attempts >= MAX_PIN_ATTEMPTS) {
    throw new Error('TOO_MANY_ATTEMPTS')
  }

  const candidateKey = await deriveKey(pin)

  // Verify PIN against a stored test blob if one exists
  const testBlob = localStorage.getItem('crm_pin_test')
  if (testBlob) {
    try {
      const parsed = JSON.parse(testBlob)
      const tempKey = _key
      _key = candidateKey
      const result = await decrypt(parsed)
      if (result !== 'ok') throw new Error('bad')
      _key = tempKey
    } catch {
      _key = null
      localStorage.setItem(PIN_ATTEMPTS_KEY, String(attempts + 1))
      const remaining = MAX_PIN_ATTEMPTS - attempts - 1
      if (remaining <= 0) {
        await wipeLocalData()
        throw new Error('TOO_MANY_ATTEMPTS')
      }
      throw new Error(`WRONG_PIN:${remaining}`)
    }
  }

  _key = candidateKey
  localStorage.setItem(PIN_ATTEMPTS_KEY, '0')

  // Write test blob on first unlock
  if (!testBlob) {
    const blob = await encrypt('ok')
    localStorage.setItem('crm_pin_test', JSON.stringify(blob))
  }
}

export function lock() {
  _key = null
}

export function isUnlocked() {
  return _key !== null
}

export function hasPinSet() {
  return localStorage.getItem('crm_pin_test') !== null
}

export async function setupPin(pin) {
  _key = await deriveKey(pin)
  localStorage.setItem(PIN_ATTEMPTS_KEY, '0')
  const blob = await encrypt('ok')
  localStorage.setItem('crm_pin_test', JSON.stringify(blob))
}

export async function wipeLocalData() {
  lock()
  localStorage.removeItem(SALT_KEY)
  localStorage.removeItem('crm_pin_test')
  localStorage.removeItem(PIN_ATTEMPTS_KEY)
  localStorage.removeItem('crm_token')
  localStorage.removeItem('crm_device_id')
  localStorage.removeItem('crm_last_sync')
  localStorage.removeItem('crm_stale_timer')

  const { db } = await import('./schema.js')
  await db.delete()

  // Clear service worker caches
  if ('caches' in window) {
    const names = await caches.keys()
    await Promise.all(names.map(n => caches.delete(n)))
  }
}
