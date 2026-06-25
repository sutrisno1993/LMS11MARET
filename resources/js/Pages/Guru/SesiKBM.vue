<template>
  <Head :title="`Sesi KBM — ${session?.mapel}`" />

  <AppLayout
    :title="`Sesi KBM Aktif`"
    :subtitle="`${session?.mapel} — ${session?.kelas}`"
    :navigation="navigation"
  >
    <template #topbar-actions>
      <span class="text-xs font-mono" :class="sessionStatus === 'AKTIF' ? 'text-green-400' : sessionStatus === 'SELESAI' ? 'text-blue-400' : sessionStatus === 'SCANNING' ? 'text-indigo-400' : 'text-yellow-400'">
        {{ sessionStatus === 'AKTIF' ? '🟢 Sesi Aktif' : sessionStatus === 'SELESAI' ? '🔵 Sesi Selesai' : sessionStatus === 'SCANNING' ? '⚡ Menunggu Scan QR' : '🟡 Menunggu Start' }}
      </span>
    </template>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

      <!-- ===================== PANEL KIRI: QR ENGINE / JURNAL ===================== -->
      <div class="space-y-5">

        <!-- Status Card -->
        <div :class="[
          'rounded-2xl border p-5 transition-all duration-500',
          sessionStatus === 'AKTIF' ? 'border-green-500/30 bg-green-500/8' :
          sessionStatus === 'SELESAI' ? 'border-blue-500/30 bg-blue-500/8' :
          sessionStatus === 'SCANNING' ? 'border-indigo-500/30 bg-indigo-500/8' :
          'border-yellow-500/30 bg-yellow-500/8'
        ]">
          <div class="flex items-center gap-3">
            <div :class="[
              'w-12 h-12 rounded-xl flex items-center justify-center text-2xl flex-shrink-0',
              sessionStatus === 'AKTIF' ? 'bg-green-500/20' :
              sessionStatus === 'SELESAI' ? 'bg-blue-500/20' :
              sessionStatus === 'SCANNING' ? 'bg-indigo-500/20' :
              'bg-yellow-500/20'
            ]">
              {{ sessionStatus === 'AKTIF' ? '✅' : sessionStatus === 'SELESAI' ? '✓' : sessionStatus === 'SCANNING' ? '⚡' : '⏳' }}
            </div>
            <div>
              <div class="font-bold" :class="sessionStatus === 'AKTIF' ? 'text-green-400' : sessionStatus === 'SELESAI' ? 'text-blue-400' : sessionStatus === 'SCANNING' ? 'text-indigo-400' : 'text-yellow-400'">
                {{ sessionStatus === 'AKTIF' ? 'KELAS AKTIF' : sessionStatus === 'SELESAI' ? 'SESI SELESAI' : sessionStatus === 'SCANNING' ? 'MENUNGGU PRESENSI SISWA' : 'PENDING' }}
              </div>
              <div class="text-xs text-slate-500 mt-0.5">
                {{ sessionStatus === 'AKTIF' ? 'Pembelajaran sedang berlangsung. Absensi default hadir.' : 
                   sessionStatus === 'SELESAI' ? 'Pertemuan kelas ini sudah selesai dideklarasikan.' :
                   sessionStatus === 'SCANNING' ? 'Minta salah satu perwakilan siswa untuk scan QR Code agar sesi aktif.' :
                   'Tekan tombol Mulai Sesi KBM di Dashboard untuk memulai kelas.' }}
              </div>
            </div>
          </div>
        </div>

        <!-- QR Code Container (hanya jika SCANNING) -->
        <div v-if="sessionStatus === 'SCANNING'" class="rounded-2xl border border-white/8 p-7 text-center" style="background: var(--card)">
          <div class="text-sm font-bold mb-1">QR Code Absensi</div>
          <div class="text-xs text-slate-500 mb-5">Gunakan QR ini untuk mengaktifkan sesi kelas</div>

          <div class="relative inline-block">
            <!-- SVG Countdown ring -->
            <svg class="absolute inset-0 w-full h-full -rotate-90" viewBox="0 0 200 200"
                 style="width:220px;height:220px;top:-10px;left:-10px;">
              <circle cx="100" cy="100" r="90" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="4"/>
              <circle
                cx="100" cy="100" r="90"
                fill="none"
                stroke="#4F46E5"
                stroke-width="4"
                stroke-linecap="round"
                :stroke-dasharray="565"
                :stroke-dashoffset="565 - (565 * countdownPct / 100)"
                style="transition: stroke-dashoffset 1s linear;"
              />
            </svg>

            <!-- QR Code box -->
            <div class="relative w-48 h-48 mx-auto rounded-2xl bg-white flex items-center justify-center shadow-2xl shadow-indigo-500/20"
                 style="width:200px;height:200px;">
              <QrcodeVue :value="qrPayload" :size="170" level="H" render-as="svg" />
            </div>
          </div>

          <!-- Countdown display -->
          <div class="mt-6">
            <div class="text-3xl font-black font-mono tabular-nums text-indigo-400">
              {{ countdownDisplay }}
            </div>
            <div class="text-xs text-slate-500 mt-1">Token kedaluwarsa dalam</div>
          </div>
        </div>

        <!-- Jurnal Pembelajaran (Hanya tampil jika AKTIF) -->
        <div v-if="sessionStatus === 'AKTIF'" class="rounded-2xl border border-white/8 p-5 space-y-4" style="background: var(--card)">
          <div>
            <h3 class="text-sm font-bold text-white">Jurnal Pembelajaran Harian</h3>
            <p class="text-[10px] text-slate-500 mt-0.5">Catat ringkasan materi pembelajaran pertemuan tatap muka hari ini.</p>
          </div>
          <div class="space-y-1.5">
            <label class="text-[10px] text-slate-500 uppercase tracking-widest font-semibold">Ringkasan Materi / Catatan KBM</label>
            <textarea
              v-model="materiLog"
              rows="4"
              placeholder="Contoh: Belajar dasar routing statik dan konfigurasi IP Address di router Cisco..."
              class="w-full bg-white/5 border border-white/8 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-600 outline-none focus:border-indigo-500/50 resize-none transition-colors"
            ></textarea>
          </div>
        </div>

        <!-- Session Info -->
        <div class="rounded-2xl border border-white/8 p-5" style="background: var(--card)">
          <div class="text-sm font-bold mb-4">Informasi Sesi</div>
          <div class="grid grid-cols-2 gap-3">
            <div v-for="info in sessionInfo" :key="info.label"
                 class="bg-white/4 rounded-xl p-3">
              <div class="text-[10px] text-slate-600 uppercase tracking-wide">{{ info.label }}</div>
              <div class="text-sm font-semibold mt-1" :class="info.color || 'text-white'">{{ info.value }}</div>
            </div>
          </div>
        </div>

        <!-- ===================== MODUL UJIAN LIVE ===================== -->
        <div v-if="sessionStatus === 'AKTIF'" class="rounded-2xl border border-indigo-500/30 p-5 bg-indigo-500/5 shadow-[0_0_20px_rgba(79,70,229,0.1)]">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-xl bg-indigo-500/20 text-indigo-400 flex items-center justify-center text-xl">🚀</div>
            <div>
              <h3 class="font-bold text-white text-sm">Luncurkan Ujian Live</h3>
              <p class="text-[10px] text-indigo-300">Pilih paket soal untuk dikirim ke HP siswa saat ini juga.</p>
            </div>
          </div>

          <form @submit.prevent="launchExam" v-if="questionBanks && questionBanks.length > 0">
            <select v-model="selectedBankId" required class="w-full bg-black/50 border border-indigo-500/30 rounded-xl px-3 py-2.5 text-xs text-white mb-3 outline-none focus:border-indigo-400">
              <option value="" disabled>Pilih Paket Soal...</option>
              <option v-for="bank in questionBanks" :key="bank.id_bank" :value="bank.id_bank">{{ bank.judul }}</option>
            </select>
            <button type="submit" class="w-full py-2.5 rounded-xl font-bold text-xs text-white transition-all bg-indigo-600 hover:bg-indigo-500 shadow-lg shadow-indigo-500/20">
              Mulai Ujian Sekarang
            </button>
          </form>

          <div v-else class="text-xs text-slate-400 bg-white/5 p-3 rounded-xl border border-white/10 text-center">
            Anda belum memiliki bank soal untuk mapel ini. 
            <a href="/guru/bank-soal/create" class="text-indigo-400 hover:underline block mt-1">Buat Paket Soal</a>
          </div>
        </div>

      </div>

      <!-- ===================== PANEL KANAN: ABSENSI SISWA ===================== -->
      <div class="space-y-5">

        <!-- Attendance summary -->
        <div class="grid grid-cols-4 gap-3">
          <div v-for="s in attendanceSummary" :key="s.label"
               class="rounded-xl border border-white/8 p-3.5 text-center"
               style="background: var(--card)">
            <div class="text-xl font-black" :style="`color:${s.color}`">{{ s.count }}</div>
            <div class="text-[10px] text-slate-600 mt-0.5">{{ s.label }}</div>
          </div>
        </div>

        <!-- Student list -->
        <div class="rounded-2xl border border-white/8 overflow-hidden" style="background: var(--card)">
          <div class="flex items-center justify-between px-5 py-4 border-b border-white/8">
            <div>
              <div class="font-bold text-sm">Daftar Kehadiran Siswa</div>
              <div class="text-xs text-slate-500">{{ session?.kelas }} — {{ students.length }} siswa</div>
            </div>
            <!-- Manual absensi hanya aktif setelah sesi AKTIF -->
            <div v-if="sessionStatus === 'PENDING' || sessionStatus === 'SCANNING'"
                 class="text-[10px] text-yellow-400 bg-yellow-500/10 border border-yellow-500/20 px-2.5 py-1.5 rounded-lg">
              🔒 Tunggu scan pertama
            </div>
            <div v-else-if="sessionStatus === 'SELESAI'"
                 class="text-[10px] text-slate-400 bg-white/5 border border-white/10 px-2.5 py-1.5 rounded-lg">
              🔒 Sesi Selesai (Read-only)
            </div>
            <div v-else class="text-[10px] text-green-400 bg-green-500/10 border border-green-500/20 px-2.5 py-1.5 rounded-lg font-semibold">
              ✓ Klik Status untuk Mengubah
            </div>
          </div>

          <!-- Filter tabs -->
          <div class="flex border-b border-white/8">
            <button v-for="tab in ['Semua', 'Hadir', 'Izin', 'Sakit', 'Alpa']" :key="tab"
                    @click="activeTab = tab"
                    :class="[
                      'flex-1 py-2.5 text-xs font-semibold transition-colors',
                      activeTab === tab
                        ? 'text-indigo-400 border-b-2 border-indigo-500'
                        : 'text-slate-600 hover:text-slate-400'
                    ]">
              {{ tab }}
            </button>
          </div>

          <!-- Search -->
          <div class="px-4 py-3 border-b border-white/5">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="🔍 Cari nama siswa..."
              class="w-full bg-white/5 border border-white/8 rounded-lg px-3 py-2 text-xs text-white placeholder-slate-600 outline-none focus:border-indigo-500/50"
            />
          </div>

          <!-- Students -->
          <div class="overflow-y-auto" style="max-height: 400px;">
            <div v-for="siswa in filteredStudents" :key="siswa.id"
                 class="flex items-center justify-between px-4 py-3.5 border-b border-white/4 hover:bg-white/2 transition-colors">

              <div class="flex items-center gap-3 min-w-0">
                <!-- Avatar -->
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold flex-shrink-0"
                     :class="[
                       siswa.status === 'HADIR' ? 'bg-green-500/10 text-green-400' :
                       siswa.status === 'IZIN' ? 'bg-blue-500/10 text-blue-400' :
                       siswa.status === 'SAKIT' ? 'bg-amber-500/10 text-amber-400' :
                       'bg-red-500/10 text-red-400'
                     ]">
                  {{ siswa.nama.split(' ').map(w=>w[0]).slice(0,2).join('') }}
                </div>

                <!-- Name + NIS -->
                <div class="min-w-0">
                  <div class="text-xs font-semibold truncate">{{ siswa.nama }}</div>
                  <div class="text-[10px] text-slate-600">{{ siswa.nis }}</div>
                </div>
              </div>

              <!-- Clickable Badge Action -->
              <div>
                <button
                  @click="toggleStatus(siswa)"
                  :disabled="sessionStatus !== 'AKTIF'"
                  class="px-3 py-1.5 rounded-xl font-bold text-[11px] transition-all duration-150 flex items-center gap-1.5 select-none"
                  :class="[
                    siswa.status === 'HADIR' ? 'bg-green-500/15 text-green-400 hover:bg-green-500/25' :
                    siswa.status === 'IZIN' ? 'bg-blue-500/15 text-blue-400 hover:bg-blue-500/25' :
                    siswa.status === 'SAKIT' ? 'bg-amber-500/15 text-amber-400 hover:bg-amber-500/25' :
                    'bg-red-500/15 text-red-400 hover:bg-red-500/25',
                    sessionStatus === 'AKTIF' ? 'cursor-pointer hover:scale-[1.03] active:scale-[0.97]' : 'cursor-default'
                  ]"
                >
                  <span>{{ statusLabel(siswa.status) }}</span>
                  <span v-if="siswa.status === 'HADIR' && siswa.metode" class="text-[9px] opacity-75 font-normal">
                    ({{ siswa.metode === 'SCAN_QR' ? '📱 QR' : '✋ Manual' }})
                  </span>
                </button>
              </div>

            </div>

            <!-- Empty state -->
            <div v-if="filteredStudents.length === 0" class="py-10 text-center text-xs text-slate-500">
              Tidak ada siswa dengan filter ini
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div v-if="sessionStatus === 'AKTIF'" class="space-y-3">
          <button
            @click="saveAttendance"
            :disabled="isSaving"
            class="w-full py-3.5 rounded-xl font-bold text-sm text-white transition-all hover:-translate-y-0.5 flex items-center justify-center gap-2"
            style="background: linear-gradient(135deg, #22C55E, #16A34A); box-shadow: 0 8px 20px rgba(34,197,94,0.3);">
            {{ isSaving ? '⏳ Menyimpan...' : '💾 Simpan Presensi & Jurnal' }}
          </button>
          
          <button
            @click="akhiriKbm"
            class="w-full py-3.5 rounded-xl font-bold text-sm text-red-400 border border-red-500/30 hover:bg-red-500/10 transition-colors flex items-center justify-center gap-2">
            🛑 Akhiri Sesi KBM (Selesai)
          </button>
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
  sessionId: [String, Number], 
  session: Object, 
  students: Array,
  questionBanks: Array
});

// ---- Session state ----
const sessionStatus = ref(props.session?.status_sesi || 'PENDING'); 
const isRefreshing = ref(false);
const isSaving = ref(false);
const materiLog = ref(props.session?.materi_log || '');

const selectedBankId = ref('');
const launchExam = () => {
  if (!selectedBankId.value) return;
  router.post(`/guru/live-exam/launch`, {
    id_bank: selectedBankId.value,
    id_kbm_session: props.sessionId
  });
};

// ---- QR Code Payload ----
const qrPayload = ref('');
const generateQrPayload = () => {
  return JSON.stringify({
    id_kbm_session: props.sessionId,
    timestamp: Date.now()
  });
};

// ---- QR Countdown (900 detik = 15 menit) ----
const countdownSecs = ref(900);
let countdownTimer;

const countdownDisplay = computed(() => {
  const m = Math.floor(countdownSecs.value / 60);
  const s = countdownSecs.value % 60;
  return `${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
});
const countdownPct = computed(() => (countdownSecs.value / 900) * 100);

let statusPollingInterval = null;

onMounted(() => {
  if (sessionStatus.value === 'SCANNING') {
    qrPayload.value = generateQrPayload();
    countdownTimer = setInterval(() => {
      if (countdownSecs.value > 0) {
        countdownSecs.value--;
      } else {
        countdownSecs.value = 900;
        qrPayload.value = generateQrPayload();
      }
    }, 1000);

    // Polling untuk mendeteksi scan QR dari perwakilan kelas
    statusPollingInterval = setInterval(async () => {
      try {
        const res = await fetch(`/guru/kbm-status/${props.sessionId}`);
        const data = await res.json();
        if (data.status_sesi === 'AKTIF') {
          clearInterval(statusPollingInterval);
          router.reload({
            onSuccess: () => {
              sessionStatus.value = 'AKTIF';
            }
          });
        }
      } catch (e) {
        console.error('Polling error:', e);
      }
    }, 3000);
  }
});

onUnmounted(() => {
  if (countdownTimer) clearInterval(countdownTimer);
  if (statusPollingInterval) clearInterval(statusPollingInterval);
});

// ---- Session info ----
const sessionInfo = computed(() => [
  { label: 'Mata Pelajaran', value: props.session?.subject?.nama_mapel || '-' },
  { label: 'Kelas', value: props.session?.clas?.nama_kelas || '-' },
  { label: 'Jam Pelajaran', value: `Jam ke-${props.session?.jam_ke || '-'}` },
  { label: 'Status Guru', value: props.session?.status_guru || 'PENDING', color: props.session?.status_guru === 'HADIR' ? 'text-green-400' : props.session?.status_guru === 'TERLAMBAT' ? 'text-yellow-400' : 'text-red-400' },
  { label: 'Tanggal', value: props.session?.tanggal ? new Date(props.session.tanggal).toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) : '-' },
  { label: 'Waktu Aktif', value: props.session?.waktu_scan_masuk ? new Date(props.session.waktu_scan_masuk).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : '-' },
]);

// ---- Students ----
const activeTab = ref('Semua');
const searchQuery = ref('');

const students = ref(props.students?.map(s => ({
  ...s,
  status: s.status || 'HADIR',
  metode: s.metode,
  waktu: s.waktu,
})) || []);

// Sinkronisasi data ketika props berubah (misal setelah scan/reload)
watch(() => props.students, (newVal) => {
  if (newVal) {
    students.value = newVal.map(s => ({
      ...s,
      status: s.status || 'HADIR',
      metode: s.metode,
      waktu: s.waktu,
    }));
  }
}, { deep: true });

watch(() => props.session, (newVal) => {
  if (newVal) {
    sessionStatus.value = newVal.status_sesi || 'PENDING';
    materiLog.value = newVal.materi_log || '';
  }
}, { deep: true });

const filteredStudents = computed(() => {
  let list = students.value;
  if (activeTab.value === 'Hadir') list = list.filter(s => s.status === 'HADIR');
  if (activeTab.value === 'Izin') list = list.filter(s => s.status === 'IZIN');
  if (activeTab.value === 'Sakit') list = list.filter(s => s.status === 'SAKIT');
  if (activeTab.value === 'Alpa') list = list.filter(s => s.status === 'ALPA');
  
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    list = list.filter(s => s.nama.toLowerCase().includes(q) || s.nis.includes(q));
  }
  return list;
});

const attendanceSummary = computed(() => [
  { label: 'Hadir', count: students.value.filter(s => s.status === 'HADIR').length, color: '#22C55E' },
  { label: 'Izin', count: students.value.filter(s => s.status === 'IZIN').length, color: '#3B82F6' },
  { label: 'Sakit', count: students.value.filter(s => s.status === 'SAKIT').length, color: '#F59E0B' },
  { label: 'Alpa', count: students.value.filter(s => s.status === 'ALPA').length, color: '#EF4444' },
]);

const statusLabel = (s) => {
  return {
    'HADIR': 'Hadir',
    'IZIN': 'Izin',
    'SAKIT': 'Sakit',
    'ALPA': 'Alpa'
  }[s] || 'Hadir';
};

const toggleStatus = (siswa) => {
  if (sessionStatus.value !== 'AKTIF') return;
  const statusCycle = {
    'HADIR': 'IZIN',
    'IZIN': 'SAKIT',
    'SAKIT': 'ALPA',
    'ALPA': 'HADIR'
  };
  siswa.status = statusCycle[siswa.status] || 'HADIR';
  
  if (siswa.status === 'HADIR') {
    siswa.metode = 'MANUAL_GURU';
    siswa.waktu = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
  } else {
    siswa.metode = null;
    siswa.waktu = null;
  }
};

const saveAttendance = () => {
  isSaving.value = true;
  router.post(`/guru/sesi-kbm/${props.sessionId}/presensi`, {
    students: students.value,
    materi_log: materiLog.value
  }, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      isSaving.value = false;
    }
  });
};

const akhiriKbm = () => {
  if (confirm('Apakah Anda yakin ingin menyelesaikan sesi KBM ini? Data presensi dan jurnal yang belum disimpan otomatis tersimpan.')) {
    // Simpan dahulu untuk memastikan data terbaru terkirim
    router.post(`/guru/sesi-kbm/${props.sessionId}/presensi`, {
      students: students.value,
      materi_log: materiLog.value
    }, {
      onSuccess: () => {
        router.post(`/guru/sesi-kbm/${props.sessionId}/selesai`);
      }
    });
  }
};

const navigation = [
  {
    label: 'KBM (Kegiatan Belajar Mengajar)',
    items: [
      { href: '/guru/dashboard', icon: '📊', label: 'Dashboard' },
      { href: '/guru/jadwal', icon: '📅', label: 'Jadwal Mengajar' },
      { href: '/guru/riwayat-jurnal', icon: '📜', label: 'Riwayat Jurnal Mengajar' },
      { href: '/guru/materi', icon: '📁', label: 'Materi Pembelajaran' },
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
</script>

<style scoped>
@keyframes shimmer {
  0% { transform: skewX(-12deg) translateX(-200%); }
  100% { transform: skewX(-12deg) translateX(200%); }
}
</style>
