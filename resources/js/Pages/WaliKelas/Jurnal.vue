<template>
  <Head title="Jurnal Harian Kelas" />

  <AppLayout
    title="Jurnal Harian KBM Kelas"
    :subtitle="kelas ? `Pantau pembelajaran & kehadiran guru kelas ${kelas.nama_kelas}` : 'Pantau KBM Kelas perwalian Anda'"
    :navigation="navigation"
  >
    <!-- DATE/SEMESTER FILTER PANEL -->
    <div class="rounded-2xl border border-white/8 p-5 mb-6 space-y-4" style="background: var(--card)">
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-bold text-white flex items-center gap-2">
          <span>📅</span> Filter Rentang Semester & Tahun
        </h3>
        <span class="text-xs font-semibold text-slate-500" v-if="kelas">
          Kelas Perwalian: <span class="text-indigo-400 font-bold">{{ kelas.nama_kelas }}</span>
        </span>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 max-w-3xl">
        <!-- Tahun Akademik -->
        <div class="space-y-1.5">
          <label class="block text-[10px] uppercase font-bold text-slate-500 tracking-wider">Tahun Akademik</label>
          <div class="relative">
            <select 
              v-model="filters.year" 
              @change="applyFilters"
              class="w-full bg-black/20 border border-white/10 rounded-lg pl-3 pr-8 py-2.5 text-sm font-medium text-white appearance-none focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition-all"
            >
              <option v-for="y in availableYears" :key="y" :value="y">Tahun {{ y }}</option>
            </select>
            <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-slate-500 text-xs">▼</div>
          </div>
        </div>

        <!-- Semester -->
        <div class="space-y-1.5">
          <label class="block text-[10px] uppercase font-bold text-slate-500 tracking-wider">Semester</label>
          <div class="relative">
            <select 
              v-model="filters.semester" 
              @change="applyFilters"
              class="w-full bg-black/20 border border-white/10 rounded-lg pl-3 pr-8 py-2.5 text-sm font-medium text-white appearance-none focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition-all"
            >
              <option value="GANJIL">Ganjil (Juli - Desember)</option>
              <option value="GENAP">Genap (Januari - Juni)</option>
            </select>
            <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-slate-500 text-xs">▼</div>
          </div>
        </div>

        <!-- Subject filter -->
        <div class="space-y-1.5">
          <label class="block text-[10px] uppercase font-bold text-slate-500 tracking-wider">Mata Pelajaran</label>
          <div class="relative">
            <select 
              v-model="filters.id_mapel" 
              @change="applyFilters"
              class="w-full bg-black/20 border border-white/10 rounded-lg pl-3 pr-8 py-2.5 text-sm font-medium text-white appearance-none focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition-all"
            >
              <option :value="null">Semua Mapel</option>
              <option v-for="sub in subjects" :key="sub.id_mapel" :value="sub.id_mapel">
                {{ sub.nama_mapel }}
              </option>
            </select>
            <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-slate-500 text-xs">▼</div>
          </div>
        </div>
      </div>
    </div>

    <!-- TABS -->
    <div class="flex items-center gap-4 border-b border-white/10 mb-6">
      <button 
        @click="activeTab = 'timeline'"
        class="px-4 py-3 text-sm font-bold uppercase tracking-wider border-b-2 transition-colors flex items-center gap-2"
        :class="activeTab === 'timeline' ? 'border-indigo-500 text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-300'"
      >
        <span>📅</span> Timeline KBM Kelas
      </button>
      <button 
        @click="activeTab = 'rekap'"
        class="px-4 py-3 text-sm font-bold uppercase tracking-wider border-b-2 transition-colors flex items-center gap-2"
        :class="activeTab === 'rekap' ? 'border-indigo-500 text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-300'"
      >
        <span>👨‍🏫</span> Ketidakhadiran Guru di Kelas
      </button>
    </div>

    <!-- TAB 1: TIMELINE KBM KELAS -->
    <div v-if="activeTab === 'timeline'" class="space-y-5">
      <div 
        v-for="session in sessions" 
        :key="session.id"
        class="rounded-2xl border border-white/8 overflow-hidden transition-all duration-200 hover:border-white/15"
        style="background: var(--card)"
      >
        <div class="p-5 flex flex-col md:flex-row md:items-center justify-between gap-5">
          
          <!-- LEFT: Mapel, Jam & Status Sesi -->
          <div class="space-y-2 md:max-w-xs flex-1">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="text-xs font-bold px-2 py-0.5 rounded bg-indigo-500/10 text-indigo-400">
                📅 {{ formatDate(session.tanggal) }}
              </span>
              <span class="text-xs font-bold px-2 py-0.5 rounded bg-white/5 text-slate-300">
                Jam ke-{{ session.jam_ke }}
              </span>
              <span :class="statusSesiBadge(session.status_sesi)">
                {{ session.status_sesi }}
              </span>
            </div>
            <h3 class="text-lg font-black text-white leading-tight">
              {{ session.mapel }}
            </h3>
            <div class="text-xs text-slate-500">
              Guru: <span class="font-semibold text-slate-300">{{ session.guru }}</span>
              <span v-if="session.status_guru" :class="['ml-2 px-1.5 py-0.5 rounded text-[10px] font-bold', statusGuruBadge(session.status_guru)]">
                {{ session.status_guru }}
              </span>
            </div>
          </div>

          <!-- MIDDLE: Ringkasan Materi (Jurnal) -->
          <div class="flex-1 md:max-w-md bg-white/2 rounded-xl p-3 border border-white/4">
            <span class="block text-[9px] uppercase font-bold text-slate-500 tracking-wider mb-1">Materi Log / Jurnal</span>
            <p class="text-xs text-slate-300 italic line-clamp-3">
              "{{ session.materi_log || 'Tidak ada catatan materi pelajaran.' }}"
            </p>
          </div>

          <!-- RIGHT: Absensi Statistik & Button -->
          <div class="flex items-center gap-6 justify-between md:justify-end">
            <!-- Stats -->
            <div class="text-right space-y-1" v-if="session.status_sesi !== 'PENDING' && session.status_sesi !== 'KOSONG'">
              <div class="text-xs text-slate-400">Kehadiran Siswa:</div>
              <div class="text-sm font-bold text-white">
                <span class="text-green-400">{{ session.stats.hadir }}</span> / {{ session.stats.total }} Hadir
              </div>
              <div class="flex gap-1.5 text-[9px] font-mono text-slate-500 justify-end">
                <span v-if="session.stats.sakit > 0" class="text-amber-500">S:{{ session.stats.sakit }}</span>
                <span v-if="session.stats.izin > 0" class="text-blue-500">I:{{ session.stats.izin }}</span>
                <span v-if="session.stats.alpa > 0" class="text-red-500">A:{{ session.stats.alpa }}</span>
              </div>
            </div>
            <div class="text-right" v-else-if="session.status_sesi === 'KOSONG'">
              <span class="text-xs text-red-400 font-semibold italic">Kelas Kosong (Guru ALPA)</span>
            </div>
            <div class="text-right" v-else>
              <span class="text-xs text-slate-600 font-semibold italic">Belum Absen (Pending)</span>
            </div>

            <!-- Detail Button -->
            <button 
              v-if="session.status_sesi !== 'PENDING' && session.status_sesi !== 'KOSONG'"
              @click="showDetails(session)"
              class="px-4 py-2.5 rounded-xl border border-white/10 text-xs font-bold text-white hover:bg-white/5 transition-colors flex items-center gap-1.5"
            >
              📋 Lihat Presensi
            </button>
          </div>

        </div>
      </div>

      <!-- Empty state -->
      <div v-if="sessions.length === 0" class="rounded-2xl border border-white/8 p-12 text-center text-slate-500" style="background: var(--card)">
        <div class="text-4xl mb-3">📓</div>
        <div class="font-bold text-white">Tidak Ada Catatan Jurnal</div>
        <div class="text-xs mt-1 text-slate-600">Tidak ada sesi KBM tatap muka yang terjadwal/aktif pada filter semester ini.</div>
      </div>
    </div>

    <!-- TAB 2: REKAP KETIDAKHADIRAN GURU DI KELAS -->
    <div v-if="activeTab === 'rekap'" class="bg-[#1E293B] border border-white/10 rounded-2xl overflow-hidden shadow-2xl">
      <table class="w-full">
        <thead>
          <tr class="bg-black/20 border-b border-white/10">
            <th class="text-left px-5 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest w-12">No</th>
            <th class="text-left px-5 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest">Nama Guru Pengajar</th>
            <th class="text-center px-5 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest">Total Sesi di Kelas</th>
            <th class="text-center px-5 py-4 text-xs font-bold text-green-400 uppercase tracking-widest">Hadir</th>
            <th class="text-center px-5 py-4 text-xs font-bold text-red-400 uppercase tracking-widest">Alpa (Tidak Masuk)</th>
            <th class="text-center px-5 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest">Kehadiran</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="teacherStats.length === 0">
            <td colspan="6" class="px-5 py-10 text-center text-slate-500">
              Tidak ada data pengajar untuk kelas ini dalam periode terpilih.
            </td>
          </tr>
          <tr v-for="(row, idx) in teacherStats" :key="row.id_guru" class="border-b border-white/5 hover:bg-white/5 transition-colors group">
            <td class="px-5 py-4 text-sm text-slate-500">{{ idx + 1 }}</td>
            <td class="px-5 py-4 text-sm font-semibold text-white group-hover:text-indigo-400 transition-colors">
              {{ row.nama_guru }}
            </td>
            <td class="px-5 py-4 text-sm text-slate-400 text-center">{{ row.total_sesi }}</td>
            <td class="px-5 py-4 text-sm font-bold text-green-400 text-center">{{ row.hadir }}</td>
            <td class="px-5 py-4 text-sm font-bold text-red-400 text-center">{{ row.alpa }}</td>
            <td class="px-5 py-4">
              <div class="flex items-center justify-center gap-3">
                <div class="w-24 h-2 rounded-full bg-black/40 border border-white/5 overflow-hidden">
                  <div class="h-full" :class="getScoreColor(row.persentase_kehadiran)" :style="`width: ${row.persentase_kehadiran}%`"></div>
                </div>
                <span class="text-xs font-black" :class="getTextColor(row.persentase_kehadiran)">{{ row.persentase_kehadiran }}%</span>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- MODAL: DETAIL PRESENSI SISWA -->
    <transition name="fade">
      <div v-if="selectedSession" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="closeDetails"></div>
        
        <!-- Modal Content -->
        <div class="relative w-full max-w-2xl rounded-2xl border border-white/12 shadow-2xl p-6 overflow-hidden max-h-[85vh] flex flex-col" style="background: var(--card)">
          
          <!-- Header -->
          <div class="flex items-start justify-between border-b border-white/8 pb-4 mb-4">
            <div>
              <h3 class="text-base font-bold text-white flex items-center gap-2">
                <span>📋</span> Detail Kehadiran Kelas {{ kelas?.nama_kelas }}
              </h3>
              <p class="text-xs text-slate-500 mt-1">
                {{ selectedSession.mapel }} — Jam ke-{{ selectedSession.jam_ke }} ({{ formatDate(selectedSession.tanggal) }})
              </p>
            </div>
            <button @click="closeDetails" class="text-slate-500 hover:text-white transition-colors text-lg font-bold">
              ✕
            </button>
          </div>

          <!-- Quick statistics grid -->
          <div class="grid grid-cols-4 gap-3 mb-4">
            <div class="bg-green-500/10 border border-green-500/20 rounded-xl p-3 text-center">
              <div class="text-base font-bold text-green-400">{{ selectedSession.stats.hadir }}</div>
              <div class="text-[9px] text-slate-500 font-bold uppercase tracking-wider mt-0.5">Hadir</div>
            </div>
            <div class="bg-blue-500/10 border border-blue-500/20 rounded-xl p-3 text-center">
              <div class="text-base font-bold text-blue-400">{{ selectedSession.stats.izin }}</div>
              <div class="text-[9px] text-slate-500 font-bold uppercase tracking-wider mt-0.5">Izin</div>
            </div>
            <div class="bg-amber-500/10 border border-amber-500/20 rounded-xl p-3 text-center">
              <div class="text-base font-bold text-amber-400">{{ selectedSession.stats.sakit }}</div>
              <div class="text-[9px] text-slate-500 font-bold uppercase tracking-wider mt-0.5">Sakit</div>
            </div>
            <div class="bg-red-500/10 border border-red-500/20 rounded-xl p-3 text-center">
              <div class="text-base font-bold text-red-400">{{ selectedSession.stats.alpa }}</div>
              <div class="text-[9px] text-slate-500 font-bold uppercase tracking-wider mt-0.5">Alpa</div>
            </div>
          </div>

          <!-- Students List -->
          <div class="overflow-y-auto flex-1 space-y-2 pr-1">
            <div 
              v-for="student in selectedSession.details" 
              :key="student.nis"
              class="flex items-center justify-between p-3 rounded-xl bg-white/3 border border-white/5"
            >
              <div class="min-w-0">
                <div class="text-xs font-semibold text-white truncate">{{ student.nama }}</div>
                <div class="text-[10px] text-slate-600">{{ student.nis }}</div>
              </div>
              <div class="flex items-center gap-3">
                <span v-if="student.status === 'HADIR' && student.waktu" class="text-[9px] text-slate-500 font-mono">
                  Absen: {{ student.waktu }} ({{ student.metode === 'SCAN_QR' ? 'QR' : 'Manual' }})
                </span>
                <span :class="['px-2.5 py-1 rounded-lg text-[10px] font-bold', attendanceBadge(student.status)]">
                  {{ student.status }}
                </span>
              </div>
            </div>
          </div>

        </div>
      </div>
    </transition>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  sessions: Array,
  kelas: Object,
  subjects: Array,
  teacherStats: Array,
  filters: Object
});

const currentYear = new Date().getFullYear();
const availableYears = [2025, 2026, 2027];

const filters = ref({
  year: props.filters.year || currentYear,
  semester: props.filters.semester || 'GANJIL',
  id_mapel: props.filters.id_mapel || null
});

const activeTab = ref('timeline');
const selectedSession = ref(null);

const applyFilters = () => {
  router.get('/walikelas/jurnal', filters.value, {
    preserveState: true,
    preserveScroll: true
  });
};

const showDetails = (session) => {
  selectedSession.value = session;
};

const closeDetails = () => {
  selectedSession.value = null;
};

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
  const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
  return `${days[d.getDay()]}, ${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
};

const statusSesiBadge = (s) => {
  return {
    'PENDING': 'bg-yellow-500/10 text-yellow-400 border border-yellow-500/20 text-[10px] px-2 py-0.5 rounded',
    'AKTIF': 'bg-green-500/10 text-green-400 border border-green-500/20 text-[10px] px-2 py-0.5 rounded animate-pulse',
    'SELESAI': 'bg-blue-500/10 text-blue-400 border border-blue-500/20 text-[10px] px-2 py-0.5 rounded',
    'KOSONG': 'bg-red-500/10 text-red-400 border border-red-500/20 text-[10px] px-2 py-0.5 rounded',
  }[s] || 'bg-slate-500/10 text-slate-400 text-[10px] px-2 py-0.5 rounded';
};

const statusGuruBadge = (s) => {
  return {
    'HADIR': 'text-green-400 bg-green-500/10 border border-green-500/25',
    'TERLAMBAT': 'text-yellow-400 bg-yellow-500/10 border border-yellow-500/25',
    'ALPA': 'text-red-400 bg-red-500/10 border border-red-500/25',
  }[s] || 'text-slate-400 bg-slate-500/10';
};

const attendanceBadge = (s) => {
  return {
    'HADIR': 'bg-green-500/10 text-green-400 border border-green-500/20',
    'IZIN': 'bg-blue-500/10 text-blue-400 border border-blue-500/20',
    'SAKIT': 'bg-amber-500/10 text-amber-400 border border-amber-500/20',
    'ALPA': 'bg-red-500/10 text-red-400 border border-red-500/20',
  }[s] || 'bg-slate-500/10 text-slate-400';
};

const getScoreColor = (pct) => {
  if (pct >= 90) return 'bg-emerald-400';
  if (pct >= 75) return 'bg-yellow-400';
  return 'bg-red-500';
};

const getTextColor = (pct) => {
  if (pct >= 90) return 'text-emerald-400';
  if (pct >= 75) return 'text-yellow-400';
  return 'text-red-400';
};

// Wali Kelas Navigation
const navigation = [
  {
    label: 'Perwalian',
    items: [
      { href: '/walikelas/dashboard', icon: '🏠', label: 'Dashboard Kelas' },
      { href: '/walikelas/jurnal', icon: '📓', label: 'Jurnal KBM Kelas' },
      { href: '/walikelas/p5-assessment', icon: '🌱', label: 'Asesmen P5' },
    ],
  },
];
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
