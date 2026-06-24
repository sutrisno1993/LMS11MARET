<template>
  <Head title="Dashboard Admin Kurikulum" />

  <AppLayout
    title="Dashboard Kurikulum"
    :subtitle="`${tanggal_hari_ini} — Shift Pagi & Siang Aktif`"
    :navigation="navigation"
  >
    <template #topbar-actions>
      <button class="px-3 py-2 text-xs font-semibold rounded-lg border border-white/10 text-slate-400 hover:text-white hover:bg-white/5 transition-colors">
        📥 Ekspor
      </button>
      <button class="px-3 py-2 text-xs font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white transition-colors">
        + Konfigurasi
      </button>
    </template>

    <!-- STAT CARDS -->
    <div class="grid grid-cols-4 gap-4 mb-7">
      <div v-for="stat in dashboardStats" :key="stat.label"
           class="relative overflow-hidden rounded-2xl border border-white/8 p-5 hover:-translate-y-1 transition-transform cursor-default"
           :style="`background: var(--card);`">
        <div class="absolute top-0 right-0 w-24 h-24 rounded-full -translate-y-1/2 translate-x-1/2 opacity-10"
             :style="`background: ${stat.color}`"></div>
        <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">{{ stat.label }}</div>
        <div class="text-4xl font-black mt-2 mb-1 tracking-tighter" :style="`color: ${stat.color}`">{{ stat.value }}</div>
        <div class="text-xs text-slate-600">{{ stat.delta }}</div>
        <div class="absolute top-5 right-5 text-2xl opacity-60">{{ stat.icon }}</div>
      </div>
    </div>

    <!-- MAIN GRID -->
    <div class="grid grid-cols-3 gap-5 mb-5">

      <!-- MONITORING TABLE -->
      <div class="col-span-2 rounded-2xl border border-white/8 overflow-hidden" style="background: var(--card)">
        <div class="flex items-center justify-between px-5 py-4 border-b border-white/8">
          <div>
            <div class="font-bold text-sm">Monitoring KBM Real-Time</div>
            <div class="text-xs text-slate-500">Status sesi aktif hari ini: {{ tanggal_hari_ini }}</div>
          </div>
          <div class="flex items-center gap-3">
            <button @click="showCctvModal = true" class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 border border-indigo-500 px-4 py-1.5 rounded-lg text-xs font-bold text-white transition-all hover:scale-105 shadow-lg shadow-indigo-500/20">
              <span>🎥</span> CCTV Kelas
            </button>
            <div class="flex items-center gap-1.5 bg-green-500/10 border border-green-500/20 px-3 py-1.5 rounded-full text-xs font-semibold text-green-400">
              <span class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse"></span>
              Auto-refresh
            </div>
          </div>
        </div>
        <table class="w-full">
          <thead>
            <tr class="bg-white/2">
              <th class="text-left px-4 py-3 text-[10px] font-semibold text-slate-600 uppercase tracking-wider border-b border-white/8">Jam Ke-</th>
              <th class="text-left px-4 py-3 text-[10px] font-semibold text-slate-600 uppercase tracking-wider border-b border-white/8">Waktu</th>
              <th class="text-left px-4 py-3 text-[10px] font-semibold text-slate-600 uppercase tracking-wider border-b border-white/8">Kelas</th>
              <th class="text-left px-4 py-3 text-[10px] font-semibold text-slate-600 uppercase tracking-wider border-b border-white/8">Mapel</th>
              <th class="text-left px-4 py-3 text-[10px] font-semibold text-slate-600 uppercase tracking-wider border-b border-white/8">Guru</th>
              <th class="text-left px-4 py-3 text-[10px] font-semibold text-slate-600 uppercase tracking-wider border-b border-white/8">Status</th>
              <th class="text-left px-4 py-3 text-[10px] font-semibold text-slate-600 uppercase tracking-wider border-b border-white/8">Jam Scan</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="live_kbm.length === 0">
              <td colspan="7" class="px-4 py-8 text-center text-slate-500 text-sm">
                Belum ada sesi KBM yang digenerate untuk hari ini.
              </td>
            </tr>
            <tr v-for="row in live_kbm" :key="row.id"
                class="border-b border-white/4 hover:bg-white/2 transition-colors">
              <td class="px-4 py-3 text-sm font-mono text-slate-400">{{ row.jam_ke }}</td>
              <td class="px-4 py-3 text-sm font-mono text-indigo-400/80">{{ row.waktu }}</td>
              <td class="px-4 py-3 text-sm font-semibold">{{ row.kelas }}</td>
              <td class="px-4 py-3 text-sm text-slate-400">{{ row.mapel }}</td>
              <td class="px-4 py-3 text-sm">{{ row.guru }}</td>
              <td class="px-4 py-3">
                <span :class="statusBadge(row.status)">
                  <span class="w-1.5 h-1.5 rounded-full inline-block mr-1" :style="`background: currentColor`"></span>
                  {{ row.status }}
                </span>
              </td>
              <td class="px-4 py-3 text-sm font-mono text-slate-400">{{ row.scan_masuk }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- CHART CARD -->
      <div class="rounded-2xl border border-white/8 p-5" style="background: var(--card)">
        <div class="flex items-center justify-between mb-1">
          <div class="font-bold text-sm">Rekap Guru Minggu Ini</div>
          <button class="text-[11px] text-indigo-400 hover:text-indigo-300">Per Bulan →</button>
        </div>
        <div class="text-xs text-slate-500 mb-4">7 Hari Terakhir</div>

        <!-- Bar chart -->
        <div class="flex items-end gap-2 h-36">
          <div v-for="day in chartDays" :key="day.label" class="flex-1 flex flex-col items-center gap-1">
            <div class="w-full flex items-end gap-0.5 justify-center" style="height:112px">
              <div class="w-2 rounded-t" :style="`height:${day.hadir}%;background:#22C55E`"></div>
              <div class="w-2 rounded-t" :style="`height:${day.terlambat}%;background:#F59E0B`"></div>
              <div class="w-2 rounded-t" :style="`height:${day.alpa}%;background:#EF4444`"></div>
            </div>
            <div class="text-[10px] text-slate-600">{{ day.label }}</div>
          </div>
        </div>

        <div class="flex gap-4 mt-3">
          <div class="flex items-center gap-1.5 text-[11px] text-slate-400"><div class="w-2 h-2 rounded-sm bg-green-500"></div>Hadir</div>
          <div class="flex items-center gap-1.5 text-[11px] text-slate-400"><div class="w-2 h-2 rounded-sm bg-yellow-500"></div>Lambat</div>
          <div class="flex items-center gap-1.5 text-[11px] text-slate-400"><div class="w-2 h-2 rounded-sm bg-red-500"></div>Alpa</div>
        </div>

        <!-- Progress bars -->
        <div class="mt-5 border-t border-white/8 pt-4 space-y-3">
          <div v-for="p in progressBars" :key="p.label">
            <div class="flex justify-between text-xs mb-1">
              <span class="text-slate-400">{{ p.label }}</span>
              <span class="font-semibold" :style="`color:${p.color}`">{{ p.pct }}%</span>
            </div>
            <div class="h-1.5 rounded-full bg-white/7">
              <div class="h-1.5 rounded-full transition-all" :style="`width:${p.pct}%;background:${p.color}`"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- PERFORMA TABLE -->
    <div class="rounded-2xl border border-white/8 overflow-hidden" style="background: var(--card)">
      <div class="flex items-center justify-between px-5 py-4 border-b border-white/8">
        <div>
          <div class="font-bold text-sm">Performa Kehadiran Guru — Diurutkan Terendah</div>
          <div class="text-xs text-slate-500">Kumulatif Tahun Pelajaran 2026/2027</div>
        </div>
        <button class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-white/10 text-slate-400 hover:text-white hover:bg-white/5 transition-colors">
          📥 Export Excel
        </button>
      </div>
      <table class="w-full">
        <thead>
          <tr class="bg-white/2 border-b border-white/8">
            <th class="text-left px-4 py-3 text-[10px] font-semibold text-slate-600 uppercase tracking-wider">#</th>
            <th class="text-left px-4 py-3 text-[10px] font-semibold text-slate-600 uppercase tracking-wider">Nama Guru</th>
            <th class="text-left px-4 py-3 text-[10px] font-semibold text-slate-600 uppercase tracking-wider">Total Sesi</th>
            <th class="text-left px-4 py-3 text-[10px] font-semibold text-slate-600 uppercase tracking-wider">Hadir</th>
            <th class="text-left px-4 py-3 text-[10px] font-semibold text-slate-600 uppercase tracking-wider">Terlambat</th>
            <th class="text-left px-4 py-3 text-[10px] font-semibold text-slate-600 uppercase tracking-wider">Alpa</th>
            <th class="text-left px-4 py-3 text-[10px] font-semibold text-slate-600 uppercase tracking-wider">% Kehadiran</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(g, i) in guruPerforma" :key="g.nama"
              class="border-b border-white/4 hover:bg-white/2 transition-colors">
            <td class="px-4 py-3 text-sm text-slate-600">{{ i + 1 }}</td>
            <td class="px-4 py-3 text-sm font-semibold">{{ g.nama }}</td>
            <td class="px-4 py-3 text-sm text-slate-400">{{ g.total }}</td>
            <td class="px-4 py-3 text-sm text-green-400 font-semibold">{{ g.hadir }}</td>
            <td class="px-4 py-3 text-sm text-yellow-400 font-semibold">{{ g.terlambat }}</td>
            <td class="px-4 py-3 text-sm text-red-400 font-semibold">{{ g.alpa }}</td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-2">
                <div class="w-20 h-1.5 rounded-full bg-white/7">
                  <div class="h-1.5 rounded-full" :style="`width:${g.pct}%;background:${g.pct >= 90 ? '#22C55E' : g.pct >= 80 ? '#F59E0B' : '#EF4444'}`"></div>
                </div>
                <span class="text-xs font-bold" :style="`color:${g.pct >= 90 ? '#22C55E' : g.pct >= 80 ? '#F59E0B' : '#EF4444'}`">{{ g.pct }}%</span>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- CCTV MODAL -->
    <div v-if="showCctvModal" class="fixed inset-0 z-[100] bg-[#0B0F1A]/90 backdrop-blur-lg flex flex-col">
      <!-- Header -->
      <div class="flex items-center justify-between px-8 py-5 border-b border-white/10 bg-[#111827]">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-red-500/20 text-red-500 flex items-center justify-center text-2xl border border-red-500/30">
            <span class="animate-pulse">🎥</span>
          </div>
          <div>
            <h2 class="text-xl font-black text-white uppercase tracking-wider">CCTV Kelas Virtual</h2>
            <p class="text-sm text-slate-400">Pemantauan Real-Time Kondisi Kelas</p>
          </div>
        </div>
        <button @click="showCctvModal = false" class="w-10 h-10 rounded-full bg-white/5 hover:bg-white/10 flex items-center justify-center text-xl border border-white/10 transition-colors">
          ✕
        </button>
      </div>
      
      <!-- Grid -->
      <div class="flex-1 overflow-y-auto p-8">
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-5">
          <div v-for="cls in currentClassesStatus" :key="cls.id_kelas"
               @click="selectedClassDetail = cls"
               class="relative rounded-2xl border p-5 cursor-pointer hover:scale-[1.02] transition-transform overflow-hidden shadow-xl"
               :class="cls.statusColor">
               <div class="text-3xl absolute top-4 right-4 opacity-30">{{ cls.icon }}</div>
               <div class="text-2xl font-black text-white mb-1 drop-shadow-md">{{ cls.nama_kelas }}</div>
               <div class="text-xs font-bold px-2 py-1 bg-black/30 rounded inline-block text-white mb-3 shadow-inner">{{ cls.statusText }}</div>
               
               <div v-if="cls.details" class="mt-2 space-y-1 bg-black/20 p-3 rounded-lg border border-white/5">
                 <div class="text-[10px] text-slate-300 uppercase font-bold tracking-wider mb-1">{{ cls.details.mapel }}</div>
                 <div class="text-xs text-white font-semibold truncate">{{ cls.details.guru }}</div>
               </div>
          </div>
        </div>
      </div>
    </div>

    <!-- DETAIL MODAL -->
    <div v-if="selectedClassDetail" class="fixed inset-0 z-[110] bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-[#1E293B] rounded-2xl border border-white/10 w-full max-w-md overflow-hidden shadow-2xl relative">
        <div class="px-6 py-4 border-b border-white/10 flex justify-between items-center" :class="selectedClassDetail.statusColor">
          <h3 class="text-lg font-black text-white">Detail {{ selectedClassDetail.nama_kelas }}</h3>
          <button @click="selectedClassDetail = null" class="text-white/50 hover:text-white transition-colors">✕</button>
        </div>
        <div class="p-6 space-y-4">
          <div class="flex justify-between items-center pb-4 border-b border-white/5">
            <span class="text-sm text-slate-400">Status Saat Ini:</span>
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-white/10 text-white">{{ selectedClassDetail.statusText }}</span>
          </div>
          
          <template v-if="selectedClassDetail.details">
            <div>
              <div class="text-xs text-slate-500 mb-1 uppercase font-bold tracking-widest">Mata Pelajaran</div>
              <div class="text-sm font-semibold text-white">{{ selectedClassDetail.details.mapel }}</div>
            </div>
            <div>
              <div class="text-xs text-slate-500 mb-1 uppercase font-bold tracking-widest">Guru Pengajar</div>
              <div class="text-sm font-semibold text-white">{{ selectedClassDetail.details.guru }}</div>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <div class="text-xs text-slate-500 mb-1 uppercase font-bold tracking-widest">Jam Ke</div>
                <div class="text-sm font-semibold text-white font-mono">{{ selectedClassDetail.details.jam_ke }}</div>
              </div>
              <div>
                <div class="text-xs text-slate-500 mb-1 uppercase font-bold tracking-widest">Waktu Scan</div>
                <div class="text-sm font-semibold text-white font-mono">{{ selectedClassDetail.details.scan }}</div>
              </div>
            </div>
            <div class="bg-indigo-500/10 border border-indigo-500/20 p-4 rounded-xl mt-2">
              <div class="text-xs text-indigo-400 mb-2 uppercase font-bold tracking-widest">Materi Pembahasan</div>
              <p class="text-sm text-white italic">
                {{ selectedClassDetail.details.materi || 'Guru belum mengisi jurnal materi.' }}
              </p>
            </div>
          </template>
          <template v-else>
            <div class="text-center py-8 text-slate-500">
              <span class="text-4xl block mb-3 opacity-50">{{ selectedClassDetail.icon }}</span>
              <p>Tidak ada rincian sesi KBM yang tersedia.</p>
            </div>
          </template>
        </div>
        <div class="p-4 border-t border-white/5 bg-black/20 flex justify-end">
           <button @click="selectedClassDetail = null" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 rounded-lg text-xs font-bold text-white transition-colors">Tutup</button>
        </div>
      </div>
    </div>

  </AppLayout>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  stats: Object,
  live_kbm: Array,
  tanggal_hari_ini: String,
  chartDays: Array,
  progressBars: Array,
  guruPerforma: Array,
  classes: Array
});

const page = usePage();
const showCctvModal = ref(false);
const selectedClassDetail = ref(null);

const serverTime = ref(new Date());
let timer = null;

onMounted(() => {
  if (page.props.app?.current_time) {
    serverTime.value = new Date(page.props.app.current_time);
  }
  
  timer = setInterval(() => {
    serverTime.value = new Date(serverTime.value.getTime() + 1000);
  }, 1000);
});

onUnmounted(() => {
  if (timer) clearInterval(timer);
});

const currentClassesStatus = computed(() => {
  const schedules = page.props.app?.today_schedules || [];
  let classes = props.classes || [];
  
  const now = serverTime.value;
  const currentMinutes = now.getHours() * 60 + now.getMinutes();

  // 1. Tentukan shift mana yang sedang aktif saat ini
  const shiftBounds = {};
  schedules.forEach(s => {
    if (!s.waktu_mulai || !s.waktu_selesai) return;
    const [hStart, mStart] = s.waktu_mulai.split(':');
    const [hEnd, mEnd] = s.waktu_selesai.split(':');
    const startMins = parseInt(hStart) * 60 + parseInt(mStart);
    const endMins = parseInt(hEnd) * 60 + parseInt(mEnd);
    
    if (!shiftBounds[s.shift]) {
      shiftBounds[s.shift] = { min: startMins, max: endMins };
    } else {
      if (startMins < shiftBounds[s.shift].min) shiftBounds[s.shift].min = startMins;
      if (endMins > shiftBounds[s.shift].max) shiftBounds[s.shift].max = endMins;
    }
  });

  // Cari shift yang context-nya aktif (dengan buffer 60 menit sebelum dan sesudah)
  let activeContextShifts = Object.keys(shiftBounds).filter(shift => {
    const bounds = shiftBounds[shift];
    return currentMinutes >= (bounds.min - 60) && currentMinutes <= (bounds.max + 60);
  });

  // Jika tidak ada shift yang cocok (misal tengah malam), default ke shift pertama
  if (activeContextShifts.length === 0 && Object.keys(shiftBounds).length > 0) {
    activeContextShifts = [Object.keys(shiftBounds)[0]];
  }

  // Filter classes berdasarkan shift yang aktif
  if (activeContextShifts.length > 0) {
    classes = classes.filter(cls => activeContextShifts.includes(cls.shift_operasional));
  }

  return classes.map(cls => {
    // Cari jadwal aktif untuk shift kelas ini
    const activeSchedule = schedules.find(s => {
      if (s.shift !== cls.shift_operasional) return false;
      if (!s.waktu_mulai || !s.waktu_selesai) return false;
      const [hStart, mStart] = s.waktu_mulai.split(':');
      const [hEnd, mEnd] = s.waktu_selesai.split(':');
      const startMins = parseInt(hStart) * 60 + parseInt(mStart);
      const endMins = parseInt(hEnd) * 60 + parseInt(mEnd);
      return currentMinutes >= startMins && currentMinutes < endMins;
    });

    if (!activeSchedule) {
      return {
        ...cls,
        statusColor: 'bg-[#1E293B] border-white/5',
        statusText: 'Di Luar Jam',
        icon: '⚪',
        details: null
      };
    }

    if (!activeSchedule.is_kbm) {
      return {
        ...cls,
        statusColor: 'bg-indigo-900/30 border-indigo-500/30 text-indigo-200',
        statusText: activeSchedule.keterangan,
        icon: '☕',
        details: null
      };
    }

    // Ada KBM, cari sesinya
    const session = props.live_kbm.find(s => s.id_kelas === cls.id_kelas && s.jam_ke === activeSchedule.jam_ke);

    if (!session) {
      return {
        ...cls,
        statusColor: 'bg-red-900/40 border-red-500/50 text-red-200',
        statusText: 'Kelas Kosong (Tak Terjadwal)',
        icon: '❌',
        details: null
      };
    }

    let color, text, icon;
    if (session.status === 'HADIR') {
      color = 'bg-green-900/40 border-green-500/50 text-green-100';
      text = 'Guru Hadir';
      icon = '✅';
    } else if (session.status === 'TERLAMBAT') {
      color = 'bg-yellow-900/40 border-yellow-500/50 text-yellow-100';
      text = 'Guru Terlambat';
      icon = '⚠️';
    } else if (session.status === 'ALPA') {
      color = 'bg-red-900/60 border-red-500/80 text-red-100';
      text = 'Guru Alpa / Kosong';
      icon = '⛔';
    } else {
      color = 'bg-rose-900/40 border-rose-500/50 text-rose-200';
      text = 'Kosong / Belum Masuk';
      icon = '⏳';
    }

    return {
      ...cls,
      statusColor: color,
      statusText: text,
      icon: icon,
      details: {
        mapel: session.mapel,
        guru: session.guru,
        jam_ke: session.jam_ke,
        materi: session.materi_log,
        scan: session.scan_masuk
      }
    };
  });
});

const navigation = [
  {
    label: 'Utama',
    items: [
      { href: '/admin/dashboard', icon: '📊', label: 'Dashboard' },
    ],
  },
  {
    label: 'Sekolah',
    items: [
      { href: '/admin/wali-kelas', icon: '👤', label: 'Wali Kelas' },
      { href: '/admin/siswa', icon: '👥', label: 'Data Siswa' },
      { href: '/admin/events', icon: '🗓️', label: 'Event & Libur' },
      { href: '/admin/kuesioner', icon: '⭐', label: 'Kuesioner Kinerja' },
    ],
  },
  {
    label: 'Konfigurasi',
    items: [
      { href: '/admin/jadwal-jp', icon: '⏰', label: 'Jadwal JP' },
      { href: '/admin/grade-config', icon: '⚖️', label: 'Bobot Nilai' },
      { href: '/admin/timeline', icon: '📅', label: 'Timeline' },
    ],
  },
  {
    label: 'Laporan',
    items: [
      { href: '/admin/monitoring-nilai', icon: '📈', label: 'Monitoring Nilai' },
      { href: '/admin/rapor', icon: '📋', label: 'Ekspor Rapor' },
      { href: '/admin/laporan-performa', icon: '🏆', label: 'Laporan Performa' },
      { href: '/admin/jurnal', icon: '📓', label: 'Jurnal Tatap Muka' },
    ],
  },
];

const dashboardStats = computed(() => [
  { label: 'Sesi KBM Hari Ini', value: props.stats?.total_sesi || 0, delta: 'Total Sesi', icon: '📚', color: '#3B82F6' },
  { label: 'Sesi Hadir', value: props.stats?.hadir || 0, delta: 'Guru Mengajar', icon: '🟢', color: '#22C55E' },
  { label: 'Terlambat', value: props.stats?.terlambat || 0, delta: 'Perlu Perhatian', icon: '🟡', color: '#F59E0B' },
  { label: 'Alpa / Kosong', value: props.stats?.alpa || 0, delta: 'Tindakan Segera', icon: '🔴', color: '#EF4444' },
]);

const statusBadge = (s) => {
  return {
    'HADIR': 'bg-green-500/10 text-green-400 border border-green-500/20 px-2 py-0.5 rounded text-xs font-bold',
    'AKTIF': 'bg-blue-500/10 text-blue-400 border border-blue-500/20 px-2 py-0.5 rounded text-xs font-bold',
    'TERLAMBAT': 'bg-yellow-500/10 text-yellow-400 border border-yellow-500/20 px-2 py-0.5 rounded text-xs font-bold',
    'PENDING': 'bg-slate-500/10 text-slate-400 border border-slate-500/20 px-2 py-0.5 rounded text-xs font-bold',
    'ALPA': 'bg-red-500/10 text-red-400 border border-red-500/20 px-2 py-0.5 rounded text-xs font-bold',
  }[s] || 'bg-slate-500/10 text-slate-400 border border-slate-500/20 px-2 py-0.5 rounded text-xs font-bold';
};
</script>
