<template>
  <Head title="Dashboard Guru" />

  <AppLayout
    title="Dashboard"
    :subtitle="`Selamat ${greeting}, ${$page.props.auth?.user?.name}`"
    :navigation="navigation"
  >
    <template #topbar-actions>
      <span class="text-xs text-slate-500 font-mono">
        🕐 {{ currentTime }}
      </span>
    </template>

    <div class="grid grid-cols-3 gap-6">

      <!-- LEFT: Jadwal Hari Ini (2/3) -->
      <div class="col-span-2 space-y-4">

        <!-- Header -->
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-base font-bold">Jadwal Hari Ini</h2>
            <p class="text-xs text-slate-500">{{ todayLabel }}</p>
          </div>
          <div class="flex items-center gap-1.5 bg-indigo-500/10 border border-indigo-500/20 px-3 py-1.5 rounded-full text-xs font-semibold text-indigo-400">
            {{ jadwalList.length }} sesi mengajar
          </div>
        </div>

        <!-- LAYAR QR CODE (Tampil jika ada sesi PENDING yang aktif) -->
        <div v-if="activePendingSession" class="rounded-2xl border-2 border-indigo-500 bg-indigo-900/20 p-8 flex flex-col items-center justify-center text-center space-y-4 shadow-[0_0_40px_rgba(79,70,229,0.2)]">
          <h3 class="text-xl font-bold text-white">KELAS SEGERA DIMULAI</h3>
          <p class="text-sm text-indigo-200 max-w-md">Silakan tampilkan QR Code ini ke layar proyektor atau hadapkan ke perwakilan kelas untuk di-scan menggunakan HP mereka.</p>
          
          <div class="p-4 bg-white rounded-xl shadow-xl inline-block mt-4">
            <QrcodeVue :value="qrPayload" :size="250" level="H" render-as="svg" />
          </div>

          <div class="mt-4 flex items-center justify-center gap-2 text-xs font-mono text-indigo-300">
            <span class="w-2 h-2 rounded-full bg-indigo-400 animate-pulse"></span>
            Menunggu scan siswa... (Auto-refresh 10s)
          </div>
        </div>

        <!-- Session Cards -->
        <div v-for="sesi in jadwalList" :key="sesi.id"
             :class="[
               'relative rounded-2xl border p-5 transition-all duration-200',
               sesi.isActive
                 ? 'border-indigo-500/50 bg-indigo-500/10 shadow-lg shadow-indigo-500/10'
                 : sesi.status === 'SELESAI'
                   ? 'border-white/5 bg-white/2 opacity-60'
                   : 'border-white/8 hover:border-white/15'
             ]"
             style="background: var(--card)">

          <!-- Active glow indicator -->
          <div v-if="sesi.isActive"
               class="absolute top-4 right-4 flex items-center gap-2 text-xs font-semibold text-indigo-400">
            <span class="w-2 h-2 rounded-full bg-indigo-400 animate-pulse"></span>
            SLOT AKTIF SEKARANG
          </div>

          <div class="flex items-start gap-4">
            <!-- Time block -->
            <div class="flex-shrink-0 text-center w-16">
              <div class="text-lg font-black text-white font-mono">{{ sesi.jamMulai }}</div>
              <div class="text-[10px] text-slate-600 mt-0.5">JP {{ sesi.jamKe }}</div>
              <div class="text-xs text-slate-600 font-mono">{{ sesi.jamSelesai }}</div>
            </div>

            <!-- Divider -->
            <div class="w-px self-stretch bg-white/8"></div>

            <!-- Session info -->
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-1">
                <h3 class="font-bold text-sm">{{ sesi.mapel }}</h3>
                <span :class="statusBadge(sesi.statusGuru)">{{ sesi.statusGuru }}</span>
              </div>
              <div class="flex items-center gap-3 text-xs text-slate-500">
                <span>🏫 {{ sesi.kelas }}</span>
                <span>📍 Ruang {{ sesi.ruang }}</span>
                <span>⏱ {{ sesi.durasiJP }} JP ({{ sesi.durasiMenit }} menit)</span>
              </div>

              <!-- Student counter (if active) -->
              <div v-if="sesi.status === 'AKTIF' || sesi.status === 'PENDING_AKTIF'"
                   class="mt-3 flex items-center gap-3">
                <div class="text-xs text-slate-400">
                  Siswa hadir: <span class="font-bold text-white">{{ sesi.siswaHadir }}/{{ sesi.siswaTotal }}</span>
                </div>
                <div class="flex-1 h-1.5 rounded-full bg-white/7">
                  <div class="h-1.5 rounded-full bg-green-500 transition-all"
                       :style="`width:${(sesi.siswaHadir/sesi.siswaTotal)*100}%`"></div>
                </div>
              </div>
            </div>

            <!-- Action button -->
            <div class="flex-shrink-0">

              <!-- MULAI SESI — hanya jika slot aktif dan belum dimulai -->
              <button
                v-if="sesi.isActive && sesi.status === 'BELUM_MULAI'"
                @click="mulaiSesi(sesi)"
                class="relative overflow-hidden flex items-center gap-2 px-5 py-3 rounded-xl text-sm font-bold text-white transition-all hover:-translate-y-0.5"
                style="background: linear-gradient(135deg, #4F46E5, #7C3AED); box-shadow: 0 0 20px rgba(79,70,229,0.5), 0 0 40px rgba(79,70,229,0.2);">
                <span class="relative z-10">▶ Mulai Sesi KBM</span>
                <!-- Shimmer -->
                <div class="absolute inset-0 -skew-x-12 bg-gradient-to-r from-transparent via-white/20 to-transparent translate-x-full animate-[shimmer_2s_infinite]"></div>
              </button>

              <!-- LIHAT ABSENSI — jika sudah aktif -->
              <a
                v-else-if="sesi.status === 'AKTIF' || sesi.status === 'PENDING_AKTIF'"
                :href="`/guru/sesi-kbm/${sesi.id}`"
                class="flex items-center gap-2 px-5 py-3 rounded-xl text-sm font-bold text-white bg-green-600 hover:bg-green-500 transition-colors">
                📋 Lihat Absensi
              </a>

              <!-- SELESAI -->
              <div v-else-if="sesi.statusSesi === 'SELESAI'"
                   class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 bg-white/4 border border-white/8">
                ✓ Selesai
              </div>

              <!-- PENDING / BELUM AKTIF -->
              <div v-else
                   class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 bg-white/4 border border-white/8">
                ⏳ Menunggu Jam
              </div>

            </div>
          </div>
        </div>

        <!-- Empty state -->
        <div v-if="jadwalList.length === 0"
             class="rounded-2xl border border-white/8 p-10 text-center text-slate-500">
          <div class="text-4xl mb-3">📅</div>
          <div class="font-semibold">Tidak ada jadwal mengajar hari ini</div>
          <div class="text-sm mt-1">Nikmati hari Anda! 🎉</div>
        </div>

      </div>

      <!-- RIGHT: Stats sidebar (1/3) -->
      <div class="space-y-4">

        <!-- Mini stat cards -->
        <div class="rounded-2xl border border-white/8 p-5 space-y-4" style="background: var(--card)">
          <div class="text-sm font-bold">Kehadiran Bulan Ini</div>
          <div class="grid grid-cols-3 gap-3">
            <div class="text-center">
              <div class="text-2xl font-black text-green-400">{{ myStats.hadir }}</div>
              <div class="text-[10px] text-slate-600 mt-0.5">Hadir</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-black text-yellow-400">{{ myStats.terlambat }}</div>
              <div class="text-[10px] text-slate-600 mt-0.5">Terlambat</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-black text-red-400">{{ myStats.alpa }}</div>
              <div class="text-[10px] text-slate-600 mt-0.5">Alpa</div>
            </div>
          </div>
          <div class="h-1.5 rounded-full bg-white/7 overflow-hidden">
            <div class="h-full flex">
              <div class="bg-green-500 transition-all" :style="`width:${myStats.pctHadir}%`"></div>
              <div class="bg-yellow-500 transition-all" :style="`width:${myStats.pctTerlambat}%`"></div>
              <div class="bg-red-500 transition-all" :style="`width:${myStats.pctAlpa}%`"></div>
            </div>
          </div>
          <div class="text-xs text-slate-500 text-center">{{ myStats.pctHadir }}% tingkat kehadiran</div>
        </div>

        <!-- Mapel yang diampu -->
        <div class="rounded-2xl border border-white/8 p-5" style="background: var(--card)">
          <div class="text-sm font-bold mb-3">Mapel Diampu</div>
          <div class="space-y-2">
            <div v-for="mapel in mapelDiampu" :key="mapel.nama"
                 class="flex items-center justify-between py-2 border-b border-white/5 last:border-0">
              <div>
                <div class="text-xs font-semibold">{{ mapel.nama }}</div>
                <div class="text-[10px] text-slate-600">{{ mapel.kelas }}</div>
              </div>
              <div class="text-[10px] font-semibold text-indigo-400">{{ mapel.jp }} JP/mgg</div>
            </div>
          </div>
        </div>

        <!-- Quick links -->
        <div class="rounded-2xl border border-white/8 p-5" style="background: var(--card)">
          <div class="text-sm font-bold mb-3">Menu Cepat</div>
          <div class="space-y-1.5">
            <a v-for="link in quickLinks" :key="link.label"
               :href="link.href"
               class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-white/5 transition-colors cursor-pointer text-slate-400 hover:text-white">
              <span class="text-base">{{ link.icon }}</span>
              <span class="text-xs font-medium">{{ link.label }}</span>
              <span class="ml-auto text-slate-700">→</span>
            </a>
          </div>
        </div>

      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import QrcodeVue from 'qrcode.vue';

const props = defineProps({ 
  sessions: Array,
  jadwal: Array,
  hariIni: String
});

// Live clock
const now = ref(new Date());
let clockInterval;
onMounted(() => { clockInterval = setInterval(() => now.value = new Date(), 1000); });
onUnmounted(() => clearInterval(clockInterval));

const currentTime = computed(() =>
  now.value.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
);

const greeting = computed(() => {
  const h = now.value.getHours();
  if (h < 11) return 'Pagi';
  if (h < 15) return 'Siang';
  if (h < 18) return 'Sore';
  return 'Malam';
});

const todayLabel = computed(() =>
  now.value.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
);

const navigation = [
  {
    label: 'KBM (Kegiatan Belajar Mengajar)',
    items: [
      { href: '/guru/dashboard', icon: '📊', label: 'Dashboard' },
      { href: '/guru/riwayat-jurnal', icon: '📜', label: 'Riwayat Jurnal Mengajar' },
    ],
  },
  {
    label: 'Evaluasi & Penilaian',
    items: [
      { href: '/guru/pemetaan-materi', icon: '🗺️', label: 'Pemetaan Materi' },
      { href: '/guru/bank-soal', icon: '📝', label: 'Bank Soal & Ujian Live' },
      { href: '/guru/nilai-sumatif', icon: '📊', label: 'Nilai Sumatif' },
      { href: '/guru/nilai-akhir', icon: '📋', label: 'Nilai Akhir' },
      { href: '/guru/rapor-preview', icon: '📑', label: 'Rapor Preview' },
    ],
  },
];

// Data jadwal dari database
const jadwalList = computed(() => {
  if (!props.jadwal) return [];
  return props.jadwal.map((j) => {
    return {
      ...j,
      jamMulai: j.jam,
      jamSelesai: '',
      durasiJP: 1,
      durasiMenit: 45,
      siswaHadir: 0, // Belum ditarik dari DB
      siswaTotal: 36,
    };
  });
});

// Logika QR Code Generator & Polling
const activePendingSession = computed(() => {
  return props.jadwal?.find(j => j.status_sesi === 'PENDING' && j.is_active);
});

const qrPayload = ref('');

let qrRefreshInterval = null;
let statusPollingInterval = null;

const generateQrPayload = (session) => {
  if (!session || !session.payload) return '';
  const data = JSON.parse(session.payload);
  data.timestamp = Date.now();
  return JSON.stringify(data);
};

watch(activePendingSession, (newSession) => {
  if (newSession) {
    qrPayload.value = generateQrPayload(newSession);
    
    if (!qrRefreshInterval) {
      qrRefreshInterval = setInterval(() => {
        qrPayload.value = generateQrPayload(newSession);
      }, 10000);
    }
    
    if (!statusPollingInterval) {
      statusPollingInterval = setInterval(async () => {
        try {
          const res = await fetch(`/guru/kbm-status/${newSession.id}`);
          const data = await res.json();
          if (data.status_sesi === 'AKTIF') {
            router.reload({ only: ['jadwal'] });
          }
        } catch (e) {
          console.error('Polling error', e);
        }
      }, 3000);
    }
  } else {
    if (qrRefreshInterval) { clearInterval(qrRefreshInterval); qrRefreshInterval = null; }
    if (statusPollingInterval) { clearInterval(statusPollingInterval); statusPollingInterval = null; }
  }
}, { immediate: true });

onUnmounted(() => {
  if (qrRefreshInterval) clearInterval(qrRefreshInterval);
  if (statusPollingInterval) clearInterval(statusPollingInterval);
});

const myStats = ref({ hadir: 24, terlambat: 3, alpa: 1, pctHadir: 86, pctTerlambat: 11, pctAlpa: 3 });

const mapelDiampu = ref([
  { nama: 'Dasar-Dasar Jaringan', kelas: 'XII TKJ 1 & 2', jp: 4 },
  { nama: 'Administrasi Sistem Jaringan', kelas: 'XII TKJ 2', jp: 4 },
  { nama: 'Keamanan Jaringan', kelas: 'XI TKJ 1', jp: 2 },
]);

const quickLinks = [
  { href: '/guru/pemetaan-materi', icon: '🗺️', label: 'Pemetaan CP & TP' },
  { href: '/guru/nilai-sumatif', icon: '📊', label: 'Input Nilai Siswa' },
  { href: '/guru/rapor-preview', icon: '📄', label: 'Preview Rapor' },
  { href: '/guru/asesmen-formatif', icon: '📝', label: 'Catatan Formatif' },
];

const statusBadge = (s) => ({
  'HADIR':     'badge-hadir',
  'AKTIF':     'badge-aktif',
  'TERLAMBAT': 'badge-terlambat',
  'PENDING':   'badge-pending',
  'ALPA':      'badge-alpa',
}[s] || 'badge-pending');

const mulaiSesi = (sesi) => {
  router.post(`/guru/sesi-kbm/${sesi.id}/mulai`, {}, {
    onSuccess: () => router.visit(`/guru/sesi-kbm/${sesi.id}`),
  });
};
</script>

<style scoped>
@keyframes shimmer {
  0% { transform: skewX(-12deg) translateX(-200%); }
  100% { transform: skewX(-12deg) translateX(200%); }
}
</style>
