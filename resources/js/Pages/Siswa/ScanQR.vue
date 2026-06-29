<template>
  <Head title="Scan QR Code" />

  <AppLayout
    title="Scan Kehadiran"
    subtitle="Arahkan kamera ke layar Guru untuk memulai kelas"
    :navigation="navigation"
  >
    <div class="max-w-lg mx-auto w-full">
      <div class="rounded-2xl border border-white/10 p-6 flex flex-col items-center shadow-xl relative overflow-hidden" style="background: var(--card)">
        
        <!-- Status Notification -->
        <div v-if="successMessage" class="absolute top-0 left-0 right-0 bg-green-500/20 text-green-400 p-4 text-center text-sm font-bold border-b border-green-500/30 z-10">
          {{ successMessage }}
        </div>
        <div v-if="errorMessage" class="absolute top-0 left-0 right-0 bg-red-500/20 text-red-400 p-4 text-center text-sm font-bold border-b border-red-500/30 z-10">
          {{ errorMessage }}
        </div>

        <h2 class="text-xl font-black text-white mb-2 mt-8">Scanner KBM</h2>
        <p class="text-sm text-slate-400 text-center mb-6">Pastikan QR Code di layar guru masuk ke dalam kotak pemindai.</p>

        <!-- Scanner Box -->
        <div class="w-full aspect-square rounded-2xl overflow-hidden border-4 border-indigo-500/30 relative bg-black">
          
          <!-- Kamera stream — selalu dirender agar inisialisasi di background -->
          <qrcode-stream 
            v-if="!isProcessing && !successMessage"
            @detect="onDetect" 
            @camera-on="onCameraOn"
            @error="onError"
            :track="paintOutline"
            style="position:absolute;inset:0;width:100%;height:100%;"
          ></qrcode-stream>

          <!-- Loading overlay di atas stream -->
          <div v-if="isLoading && !errorMessage"
               class="absolute inset-0 bg-black/70 flex flex-col items-center justify-center z-10">
            <span class="w-8 h-8 border-4 border-indigo-400 border-t-transparent rounded-full animate-spin mb-3"></span>
            <span class="text-indigo-400 text-sm font-bold animate-pulse">Menyiapkan Kamera...</span>
          </div>

          <!-- Processing Overlay -->
          <div v-if="isProcessing" class="absolute inset-0 bg-indigo-900/80 backdrop-blur-sm flex flex-col items-center justify-center z-10">
            <span class="w-10 h-10 border-4 border-indigo-400 border-t-transparent rounded-full animate-spin mb-4"></span>
            <span class="text-white font-bold text-sm">Memvalidasi QR...</span>
          </div>
          
        </div>

        <button @click="resetScanner" class="mt-8 px-6 py-2.5 rounded-xl text-sm font-bold text-indigo-400 bg-indigo-500/10 hover:bg-indigo-500/20 transition-colors w-full border border-indigo-500/20">
          Reset / Ulangi Scan
        </button>

      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { QrcodeStream } from 'vue-qrcode-reader';
import axios from 'axios';

const navigation = [
  {
    label: 'Akademik',
    items: [
      { href: '/siswa/dashboard', icon: '🏠', label: 'Beranda' },
      { href: '/siswa/scan-qr', icon: '📷', label: 'Scan QR Presensi', badge: 'Live' },
      { href: '/siswa/materi', icon: '📁', label: 'Materi Belajar' },
      { href: '/siswa/jadwal', icon: '📅', label: 'Jadwal Kelas' },
    ],
  },
  {
    label: 'Laporan',
    items: [
      { href: '/siswa/nilai', icon: '📊', label: 'Nilai & Capaian' },
      { href: '/siswa/kuesioner', icon: '📝', label: 'Evaluasi Pembelajaran' },
    ],
  },
];

const isLoading = ref(true);
const isProcessing = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

const resetScanner = () => {
  errorMessage.value = '';
  successMessage.value = '';
  isProcessing.value = false;
};

const onCameraOn = () => {
  isLoading.value = false;
};

const onError = (error) => {
  isLoading.value = false;
  errorMessage.value = 'ERROR: Kamera tidak dapat diakses. Pastikan browser mengizinkan kamera atau akses via HTTPS.';
  if (error.name === 'NotAllowedError') {
    errorMessage.value = 'Akses kamera ditolak oleh pengguna.';
  } else if (error.name === 'NotFoundError') {
    errorMessage.value = 'Tidak ada perangkat kamera ditemukan.';
  } else {
    errorMessage.value = 'Gagal mengakses kamera: ' + error.message;
  }
};

const onDetect = async (detectedCodes) => {
  if (isProcessing.value || detectedCodes.length === 0) return;
  
  isProcessing.value = true;
  errorMessage.value = '';
  
  const payload = detectedCodes[0].rawValue;
  
  try {
    const response = await axios.post('/siswa/scan-qr', { payload });
    successMessage.value = response.data.message;
    
    // Redirect ke dashboard setelah sukses 3 detik
    setTimeout(() => {
      router.visit('/siswa/dashboard');
    }, 3000);
    
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Gagal memvalidasi QR Code.';
    setTimeout(() => {
      isProcessing.value = false;
    }, 2000);
  }
};

const paintOutline = (detectedCodes, ctx) => {
  for (const detectedCode of detectedCodes) {
    const [firstPoint, ...otherPoints] = detectedCode.cornerPoints;
    
    ctx.strokeStyle = '#818cf8'; // indigo-400
    ctx.lineWidth = 4;

    ctx.beginPath();
    ctx.moveTo(firstPoint.x, firstPoint.y);
    for (const { x, y } of otherPoints) {
      ctx.lineTo(x, y);
    }
    ctx.lineTo(firstPoint.x, firstPoint.y);
    ctx.closePath();
    ctx.stroke();
  }
};
</script>
