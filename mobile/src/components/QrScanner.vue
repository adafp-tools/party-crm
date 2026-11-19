<template>
  <div class="qr-wrapper">
    <video ref="videoEl" autoplay playsinline muted />
    <div class="qr-overlay"><div class="qr-frame" /></div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import jsQR from 'jsqr'

const emit = defineEmits(['decoded', 'error'])
const videoEl = ref(null)
let stream = null
let raf = null
let canvas, ctx

onMounted(async () => {
  try {
    stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
    videoEl.value.srcObject = stream
    canvas = document.createElement('canvas')
    ctx = canvas.getContext('2d', { willReadFrequently: true })
    scan()
  } catch (err) {
    emit('error', err)
  }
})

onUnmounted(() => {
  stop()
})

function scan() {
  const video = videoEl.value
  if (!video || video.readyState !== 4) {
    raf = requestAnimationFrame(scan)
    return
  }
  canvas.width = video.videoWidth
  canvas.height = video.videoHeight
  ctx.drawImage(video, 0, 0)
  const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height)
  const code = jsQR(imageData.data, imageData.width, imageData.height)
  if (code?.data) {
    emit('decoded', code.data)
    stop()
    return
  }
  raf = requestAnimationFrame(scan)
}

function stop() {
  cancelAnimationFrame(raf)
  stream?.getTracks().forEach(t => t.stop())
}
</script>
