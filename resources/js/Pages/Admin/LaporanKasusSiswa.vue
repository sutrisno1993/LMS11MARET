<template>
  <Head title="Laporan Kasus & Pembinaan Siswa" />

  <AppLayout
    title="Laporan Kasus & Pembinaan"
    subtitle="Rekapitulasi Pelanggaran Disiplin & Penanganan Wali Kelas"
    :navigation="navigation"
  >
    <!-- FILTER BAR -->
    <div class="bg-[#1E293B] border border-white/10 rounded-2xl p-5 mb-6 shadow-xl">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        <!-- Kelas Filter -->
        <div>
          <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Pilih Kelas</label>
          <select v-model="filterForm.id_kelas" @change="applyFilter" class="w-full bg-[#111827] border border-white/10 rounded-lg px-3 py-2 text-xs text-white outline-none focus:border-indigo-500">
            <option :value="null">Semua Kelas</option>
            <option v-for="cls in classes" :key="cls.id_kelas" :value="cls.id_kelas">
              {{ cls.nama_kelas }}
            </option>
          </select>
        </div>

        <!-- Kategori Filter -->
        <div>
          <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Kategori Masalah</label>
          <select v-model="filterForm.kategori_kasus" @change="applyFilter" class="w-full bg-[#111827] border border-white/10 rounded-lg px-3 py-2 text-xs text-white outline-none focus:border-indigo-500">
            <option :value="null">Semua Kategori</option>
            <option value="ABSENSI">Absensi (Kehadiran)</option>
            <option value="AKADEMIK">Akademik (Tugas/Nilai)</option>
            <option value="PERILAKU">Perilaku (Kedisiplinan/Sikap)</option>
          </select>
        </div>

        <!-- Tipe Tindakan Filter -->
        <div>
          <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Tipe Penanganan</label>
          <select v-model="filterForm.tipe_tindakan" @change="applyFilter" class="w-full bg-[#111827] border border-white/10 rounded-lg px-3 py-2 text-xs text-white outline-none focus:border-indigo-500">
            <option :value="null">Semua Tindakan</option>
            <option value="PEMBINAAN_PRIBADI">Pembinaan Pribadi</option>
            <option value="PEMBINAAN_PERSONAL">Pembinaan Personal</option>
            <option value="PEMANGGILAN_ORTU">Pemanggilan Orang Tua</option>
            <option value="HOME_VISIT">Home Visit (Kunjungan)</option>
            <option value="SP_1">Surat Peringatan 1 (SP 1)</option>
            <option value="SP_2">Surat Peringatan 2 (SP 2)</option>
            <option value="SP_3">Surat Peringatan 3 (SP 3)</option>
            <option value="PERJANJIAN_TIDAK_NAIK">Perjanjian Siap Tidak Naik Kelas</option>
          </select>
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-2">
          <button @click="resetFilter" class="flex-1 px-3 py-2 border border-white/10 hover:bg-white/5 rounded-lg text-xs font-bold text-slate-400 hover:text-white transition-colors">
            Reset
          </button>
          <button @click="downloadExcel" class="flex-1 bg-emerald-600 hover:bg-emerald-500 px-3 py-2 rounded-lg text-xs font-bold text-white transition-colors shadow-lg shadow-emerald-500/20">
            📥 Ekspor
          </button>
        </div>
      </div>
    </div>

    <!-- MAIN TABLE -->
    <div class="bg-[#1E293B] border border-white/10 rounded-2xl overflow-hidden shadow-2xl">
      <div class="px-5 py-4 border-b border-white/10 bg-black/20 flex justify-between items-center">
        <h3 class="font-bold text-sm text-white">Daftar Laporan Kasus Siswa</h3>
        <span class="text-xs text-slate-400">Total: {{ cases.length }} Kasus</span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-black/40 border-b border-white/10 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
              <th class="px-5 py-4 text-center w-12">No</th>
              <th class="px-5 py-4">Siswa</th>
              <th class="px-5 py-4">Kelas & Wali</th>
              <th class="px-5 py-4">Kasus / Pelanggaran</th>
              <th class="px-5 py-4">Tindakan / Penanganan</th>
              <th class="px-5 py-4">Tindak Lanjut</th>
              <th class="px-5 py-4 text-center w-28">Bukti</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="cases.length === 0">
              <td colspan="7" class="px-5 py-12 text-center text-slate-500 text-sm">
                Tidak ada laporan kasus siswa yang cocok dengan filter saat ini.
              </td>
            </tr>
            <tr v-for="(row, idx) in cases" :key="row.id" class="border-b border-white/5 hover:bg-white/5 transition-colors group">
              <td class="px-5 py-4 text-xs text-slate-500 text-center">{{ idx + 1 }}</td>
              
              <!-- Siswa -->
              <td class="px-5 py-4">
                <div class="text-sm font-semibold text-white">{{ row.nama_siswa }}</div>
                <div class="text-[10px] text-slate-500 mt-0.5">{{ row.nis }}</div>
              </td>
              
              <!-- Kelas & Wali -->
              <td class="px-5 py-4">
                <span class="bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider">
                  {{ row.nama_kelas }}
                </span>
                <div class="text-[11px] text-slate-400 mt-1.5">Wali: {{ row.nama_wali }}</div>
              </td>
              
              <!-- Kasus -->
              <td class="px-5 py-4">
                <span class="inline-block bg-slate-500/20 text-slate-400 border border-slate-500/30 px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider mb-1.5">
                  {{ row.kategori_kasus }}
                </span>
                <div class="text-xs text-slate-300 leading-relaxed max-w-[200px]">{{ row.kasus_detail }}</div>
              </td>
              
              <!-- Tindakan -->
              <td class="px-5 py-4">
                <span class="inline-block px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider mb-1.5" :class="getBadgeClass(row.tipe_tindakan)">
                  {{ getActionLabel(row.tipe_tindakan) }}
                </span>
                <div class="text-[10px] text-slate-500 font-bold mb-0.5">{{ formatDate(row.tanggal_tindakan) }}</div>
                <div class="text-xs text-slate-400 italic max-w-[200px]">"{{ row.keterangan }}"</div>
              </td>
              
              <!-- Tindak Lanjut -->
              <td class="px-5 py-4 text-xs text-indigo-300 max-w-[150px]">
                {{ row.tindakan_lanjut }}
              </td>
              
              <!-- Foto Bukti -->
              <td class="px-5 py-4 text-center">
                <div v-if="row.foto_bukti">
                  <button @click="zoomImage(row.foto_bukti)" class="inline-block overflow-hidden rounded-lg border border-white/10 hover:border-indigo-500 transition-all shadow-md">
                    <img :src="row.foto_bukti" class="object-cover w-16 h-12 hover:scale-105 transition-transform" />
                  </button>
                </div>
                <span v-else class="text-[10px] text-slate-600">-</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ZOOM IMAGE MODAL -->
    <div v-if="zoomedImageUrl" @click="zoomedImageUrl = null" class="fixed inset-0 z-[120] bg-black/85 backdrop-blur-md flex items-center justify-center p-4 cursor-zoom-out">
      <div class="relative max-w-4xl max-h-[85vh] overflow-hidden rounded-2xl border border-white/10 shadow-2xl bg-[#111827]">
        <img :src="zoomedImageUrl" class="object-contain w-full h-full max-h-[80vh]" />
        <button @click="zoomedImageUrl = null" class="absolute top-4 right-4 bg-black/60 hover:bg-black/90 w-8 h-8 rounded-full flex items-center justify-center text-white text-lg transition-colors">
          ✕
        </button>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  cases: Array,
  classes: Array,
  filters: Object,
});

const filterForm = ref({
  id_kelas: props.filters.id_kelas || null,
  kategori_kasus: props.filters.kategori_kasus || null,
  tipe_tindakan: props.filters.tipe_tindakan || null,
});

const zoomedImageUrl = ref(null);

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
      { href: '/admin/laporan-kasus-siswa', icon: '⚡', label: 'Laporan Kasus' },
    ],
  },
];

const applyFilter = () => {
  router.get('/admin/laporan-kasus-siswa', {
    id_kelas: filterForm.value.id_kelas,
    kategori_kasus: filterForm.value.kategori_kasus,
    tipe_tindakan: filterForm.value.tipe_tindakan,
  }, {
    preserveState: true,
    preserveScroll: true,
  });
};

const resetFilter = () => {
  filterForm.value.id_kelas = null;
  filterForm.value.kategori_kasus = null;
  filterForm.value.tipe_tindakan = null;
  applyFilter();
};

const zoomImage = (url) => {
  zoomedImageUrl.value = url;
};

const formatDate = (dateStr) => {
  if (!dateStr) return '-';
  const options = { year: 'numeric', month: 'short', day: 'numeric' };
  return new Date(dateStr).toLocaleDateString('id-ID', options);
};

const getActionLabel = (type) => {
  const labels = {
    'PEMBINAAN_PRIBADI': 'Pembinaan Pribadi',
    'PEMBINAAN_PERSONAL': 'Pembinaan Personal',
    'PEMANGGILAN_ORTU': 'Pemanggilan Orang Tua',
    'HOME_VISIT': 'Home Visit (Kunjungan)',
    'SP_1': 'Surat Peringatan 1 (SP 1)',
    'SP_2': 'Surat Peringatan 2 (SP 2)',
    'SP_3': 'Surat Peringatan 3 (SP 3)',
    'PERJANJIAN_TIDAK_NAIK': 'Perjanjian Siap Tidak Naik Kelas',
  };
  return labels[type] || type;
};

const getBadgeClass = (type) => {
  if (type.startsWith('SP_') || type === 'PERJANJIAN_TIDAK_NAIK') {
    return 'bg-red-500/20 text-red-400 border border-red-500/30';
  }
  if (type === 'PEMANGGILAN_ORTU') {
    return 'bg-yellow-500/20 text-yellow-400 border border-yellow-500/30';
  }
  if (type === 'HOME_VISIT') {
    return 'bg-purple-500/20 text-purple-400 border border-purple-500/30';
  }
  return 'bg-blue-500/20 text-blue-400 border border-blue-500/30';
};

const downloadExcel = () => {
  if (props.cases.length === 0) {
    window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Tidak ada data untuk diekspor!', type: 'warning' } }));
    return;
  }

  let csvContent = "data:text/csv;charset=utf-8,";
  const headers = ["No", "Nama Siswa", "NIS", "Kelas", "Wali Kelas", "Kategori Kasus", "Detail Kasus", "Tipe Tindakan", "Tanggal", "Keterangan", "Tindak Lanjut"];
  csvContent += headers.join(",") + "\n";

  props.cases.forEach((row, idx) => {
    const rowData = [
      idx + 1,
      `"${row.nama_siswa}"`,
      `"${row.nis}"`,
      `"${row.nama_kelas}"`,
      `"${row.nama_wali}"`,
      `"${row.kategori_kasus}"`,
      `"${row.kasus_detail.replace(/"/g, '""')}"`,
      `"${getActionLabel(row.tipe_tindakan)}"`,
      `"${row.tanggal_tindakan}"`,
      `"${row.keterangan.replace(/"/g, '""')}"`,
      `"${row.tindakan_lanjut.replace(/"/g, '""')}"`
    ];
    csvContent += rowData.join(",") + "\n";
  });

  const encodedUri = encodeURI(csvContent);
  const link = document.createElement("a");
  link.setAttribute("href", encodedUri);
  link.setAttribute("download", `Laporan_Kasus_Pembinaan_Siswa_${new Date().toISOString().split('T')[0]}.csv`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};
</script>

<style scoped>
</style>
