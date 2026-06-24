<template>
  <Head title="Laporan Performa Guru & Wali Kelas" />

  <AppLayout
    title="Laporan Performa"
    subtitle="Evaluasi Kinerja Kehadiran Guru & Penilaian Wali Kelas"
    :navigation="navigation"
  >
    <!-- TOP ACTIONS & FILTER -->
    <div class="flex items-center justify-between mb-8">
      <div class="flex items-center gap-3">
        <!-- Semester Toggle -->
        <div class="flex bg-[#1E293B] border border-white/10 rounded-xl p-1">
          <button @click="selectedSemester = 'GANJIL'; applyFilter()"
            class="px-4 py-1.5 rounded-lg text-sm font-bold transition-all"
            :class="selectedSemester === 'GANJIL' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-white'"
          >Ganjil (Jul–Des)</button>
          <button @click="selectedSemester = 'GENAP'; applyFilter()"
            class="px-4 py-1.5 rounded-lg text-sm font-bold transition-all"
            :class="selectedSemester === 'GENAP' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-white'"
          >Genap (Jan–Jun)</button>
        </div>

        <!-- Tahun Ajaran -->
        <div class="relative">
          <select v-model="selectedYear" @change="applyFilter" class="bg-[#1E293B] border border-white/10 rounded-xl px-4 py-2 text-sm text-white font-semibold focus:ring-1 focus:ring-indigo-500 appearance-none pr-10">
            <option v-for="y in availableYears" :key="y" :value="y">Tahun {{ y }}</option>
          </select>
          <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-slate-500 text-xs">▼</div>
        </div>

        <!-- Label rentang bulan -->
        <span class="text-xs text-slate-500 font-mono">{{ semesterLabel }}</span>
      </div>
      
      <div class="flex gap-3">
        <button @click="downloadExcel" class="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-500 border border-emerald-500 px-4 py-2 rounded-xl text-xs font-bold text-white transition-all shadow-lg shadow-emerald-500/20">
          <span>📥</span> Ekspor Excel (CSV)
        </button>
      </div>
    </div>

    <!-- TABS -->
    <div class="flex items-center gap-4 border-b border-white/10 mb-6">
      <button 
        @click="activeTab = 'SEMUA'"
        class="px-4 py-3 text-sm font-bold uppercase tracking-wider border-b-2 transition-colors flex items-center gap-2"
        :class="activeTab === 'SEMUA' ? 'border-indigo-500 text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-300'"
      >
        <span>👨‍🏫</span> Performa Absensi Guru
      </button>
      <button 
        @click="activeTab = 'WALI'"
        class="px-4 py-3 text-sm font-bold uppercase tracking-wider border-b-2 transition-colors flex items-center gap-2"
        :class="activeTab === 'WALI' ? 'border-indigo-500 text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-300'"
      >
        <span>⭐</span> Penilaian Wali Kelas
      </button>
    </div>

    <!-- DATA GRID GURU -->
    <div v-if="activeTab === 'SEMUA'" class="bg-[#1E293B] border border-white/10 rounded-2xl overflow-hidden shadow-2xl">
      <table class="w-full">
        <thead>
          <tr class="bg-black/20 border-b border-white/10">
            <th class="text-left px-5 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest w-12">No</th>
            <th class="text-left px-5 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest">Nama Guru</th>
            <th class="text-center px-5 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest">Total Sesi</th>
            <th class="text-center px-5 py-4 text-xs font-bold text-green-400 uppercase tracking-widest">Hadir</th>
            <th class="text-center px-5 py-4 text-xs font-bold text-yellow-400 uppercase tracking-widest">Telat</th>
            <th class="text-center px-5 py-4 text-xs font-bold text-red-400 uppercase tracking-widest">Alpa</th>
            <th class="text-center px-5 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest">Skor Kehadiran</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="laporanGuru.length === 0">
            <td colspan="7" class="px-5 py-10 text-center text-slate-500">
              <span class="text-4xl block mb-3">👻</span>
              Tidak ada data yang ditemukan untuk filter ini.
            </td>
          </tr>
          <tr v-for="(row, idx) in laporanGuru" :key="row.id_guru" class="border-b border-white/5 hover:bg-white/5 transition-colors group">
            <td class="px-5 py-4 text-sm text-slate-500">{{ idx + 1 }}</td>
            <td class="px-5 py-4 text-sm font-semibold text-white group-hover:text-indigo-400 transition-colors">
              {{ row.nama_guru }}
            </td>
            <td class="px-5 py-4 text-sm text-slate-400 text-center">{{ row.total_sesi }}</td>
            <td class="px-5 py-4 text-sm font-bold text-green-400 text-center">{{ row.hadir }}</td>
            <td class="px-5 py-4 text-sm font-bold text-yellow-400 text-center">{{ row.terlambat }}</td>
            <td class="px-5 py-4 text-sm font-bold text-red-400 text-center">{{ row.alpa }}</td>
            <td class="px-5 py-4">
              <div class="flex items-center justify-center gap-3">
                <div class="w-24 h-2 rounded-full bg-black/40 border border-white/5 overflow-hidden">
                  <div class="h-full" :class="getScoreColor(row.persentase)" :style="`width: ${row.persentase}%`"></div>
                </div>
                <span class="text-xs font-black" :class="getTextColor(row.persentase)">{{ row.persentase }}%</span>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- DATA GRID WALI KELAS -->
    <div v-if="activeTab === 'WALI'" class="bg-[#1E293B] border border-white/10 rounded-2xl overflow-hidden shadow-2xl">
      <table class="w-full">
        <thead>
          <tr class="bg-black/20 border-b border-white/10">
            <th class="text-left px-5 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest w-12">No</th>
            <th class="text-left px-5 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest">Nama Guru (Wali Kelas)</th>
            <th class="text-left px-5 py-4 text-xs font-bold text-indigo-400 uppercase tracking-widest">Kelas Wali</th>
            <th class="text-center px-5 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest">Total Responden Siswa</th>
            <th class="text-center px-5 py-4 text-xs font-bold text-yellow-400 uppercase tracking-widest">Skor Kuesioner (1-5)</th>
            <th class="text-center px-5 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest">Kepuasan Kelas</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="laporanWaliKelas.length === 0">
            <td colspan="6" class="px-5 py-10 text-center text-slate-500">
              <span class="text-4xl block mb-3">👻</span>
              Belum ada data kuesioner wali kelas untuk semester ini.
            </td>
          </tr>
          <tr v-for="(row, idx) in laporanWaliKelas" :key="row.id_guru" class="border-b border-white/5 hover:bg-white/5 transition-colors group">
            <td class="px-5 py-4 text-sm text-slate-500">{{ idx + 1 }}</td>
            <td class="px-5 py-4 text-sm font-semibold text-white group-hover:text-indigo-400 transition-colors">
              {{ row.nama_guru }}
            </td>
            <td class="px-5 py-4">
              <span class="bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 px-3 py-1 rounded text-xs font-bold tracking-widest uppercase">
                {{ row.kelas_wali }}
              </span>
            </td>
            <td class="px-5 py-4 text-sm text-slate-400 text-center">{{ row.total_respon }} Siswa</td>
            <td class="px-5 py-4 text-sm font-black text-yellow-400 text-center">⭐ {{ row.skor_kuesioner }}</td>
            <td class="px-5 py-4">
              <div class="flex items-center justify-center gap-3">
                <div class="w-24 h-2 rounded-full bg-black/40 border border-white/5 overflow-hidden">
                  <div class="h-full" :class="getScoreColor(row.persentase)" :style="`width: ${row.persentase}%`"></div>
                </div>
                <span class="text-xs font-black" :class="getTextColor(row.persentase)">{{ row.persentase }}%</span>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  laporanGuru: Array,
  laporanWaliKelas: Array,
  selectedSemester: String,
  selectedYear: [String, Number]
});

const currentYear = new Date().getFullYear();
const availableYears = [currentYear - 1, currentYear, currentYear + 1];

const selectedSemester = ref(props.selectedSemester || 'GANJIL');
const selectedYear = ref(parseInt(props.selectedYear));
const activeTab = ref('SEMUA');

// Label rentang bulan berdasarkan semester
const semesterLabel = computed(() => {
  return selectedSemester.value === 'GANJIL'
    ? `Juli – Desember ${selectedYear.value}`
    : `Januari – Juni ${selectedYear.value}`;
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

const applyFilter = () => {
  router.get('/admin/laporan-performa', {
    semester: selectedSemester.value,
    year: selectedYear.value
  }, {
    preserveState: true,
    preserveScroll: true
  });
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

// Fungsi Ekspor ke CSV
const downloadExcel = () => {
  const isWali = activeTab.value === 'WALI';
  const data = isWali ? props.laporanWaliKelas : props.laporanGuru;
  
  if (data.length === 0) return alert('Tidak ada data untuk diekspor!');

  let csvContent = "data:text/csv;charset=utf-8,";
  
  // Header CSV
  let headers = isWali 
    ? ["No", "Nama Guru (Wali Kelas)", "Kelas Wali", "Total Responden", "Skor Kuesioner", "Persentase Kepuasan"]
    : ["No", "Nama Guru", "Total Sesi", "Hadir", "Terlambat", "Alpa", "Persentase Kehadiran"];
    
  csvContent += headers.join(",") + "\n";

  // Rows CSV
  data.forEach((row, idx) => {
    let rowData = [];
    if (isWali) {
      rowData = [
        idx + 1,
        `"${row.nama_guru}"`,
        `"${row.kelas_wali}"`,
        row.total_respon,
        row.skor_kuesioner,
        row.persentase + '%'
      ];
    } else {
      rowData = [
        idx + 1,
        `"${row.nama_guru}"`,
        row.total_sesi,
        row.hadir,
        row.terlambat,
        row.alpa,
        row.persentase + '%'
      ];
    }
    csvContent += rowData.join(",") + "\n";
  });

  const encodedUri = encodeURI(csvContent);
  const link = document.createElement("a");
  link.setAttribute("href", encodedUri);
  
  const semLabel = selectedSemester.value === 'GANJIL' ? 'Ganjil' : 'Genap';
  const prefix = isWali ? 'Laporan_Wali_Kelas_Kuesioner' : 'Laporan_Absensi_Guru';
  link.setAttribute("download", `${prefix}_Semester_${semLabel}_${selectedYear.value}.csv`);
  
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};
</script>
