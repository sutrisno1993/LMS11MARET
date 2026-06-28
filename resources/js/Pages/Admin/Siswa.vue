<template>
  <Head title="Manajemen Data Siswa" />

  <AppLayout
    title="Data Induk Siswa"
    subtitle="Kelola dan import data siswa secara massal"
    :navigation="navigation"
  >
    <!-- Alert notification is handled globally by AppLayout toast -->

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
      <!-- UPLOAD CARD -->
      <div class="lg:col-span-1">
        <div class="rounded-2xl border border-white/8 p-6" style="background: var(--card)">
          <h3 class="font-bold mb-4">Import Data Siswa</h3>
          
          <div class="bg-indigo-500/10 border border-indigo-500/20 rounded-xl p-4 mb-5">
            <h4 class="text-xs font-bold text-indigo-400 mb-2 flex items-center justify-between">
              <span>📖 Petunjuk Import:</span>
              <div class="flex gap-2">
                <a href="/admin/siswa/export" class="px-3 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded-md transition-colors shadow-lg flex items-center gap-2">
                  <span>⬇️</span> Export Saat Ini
                </a>
                <button @click="downloadTemplate" class="px-3 py-1 bg-indigo-600 hover:bg-indigo-500 text-white rounded-md transition-colors shadow-lg flex items-center gap-2">
                  <span>📄</span> Template Kosong
                </button>
              </div>
            </h4>
            <ul class="text-[11px] text-slate-300 space-y-1.5 list-disc pl-4">
              <li>Pastikan file berformat <strong>.xlsx</strong> (Excel)</li>
              <li>Terdapat 4 kolom wajib berurutan: <code class="text-indigo-300">NIS, NISN, Nama Siswa, Nama Kelas</code></li>
              <li>Baris pertama (Header) akan diabaikan oleh sistem.</li>
              <li>
                <strong>Nama Kelas</strong> harus persis sama dengan data di sistem. Berikut daftar nama kelas yang valid:
                <div class="mt-2 p-2 bg-black/20 rounded border border-white/5 max-h-24 overflow-y-auto text-[10px] font-mono text-indigo-300 leading-relaxed">
                  <span v-for="(cls, index) in classes" :key="cls.id_kelas">
                    {{ cls.nama_kelas }}<span v-if="index < classes.length - 1">, </span>
                  </span>
                </div>
              </li>
              <li>Password akun siswa otomatis menggunakan <strong>NIS</strong>.</li>
            </ul>
          </div>
          
          <div 
            class="border-2 border-dashed border-white/20 rounded-xl p-8 text-center transition-colors hover:border-indigo-500/50 hover:bg-indigo-500/5 relative cursor-pointer group"
          >
            <input 
              type="file" 
              accept=".xlsx,.xls,.csv"
              @change="handleFileUpload"
              class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
            />
            <div class="text-3xl mb-3 opacity-60 group-hover:opacity-100 transition-opacity">📊</div>
            <div class="text-sm font-semibold mb-1" v-if="!selectedFile">Pilih file Excel (.xlsx)</div>
            <div class="text-sm font-semibold text-indigo-400 mb-1" v-else>{{ selectedFile.name }}</div>
            <div class="text-[10px] text-slate-500">Maks. 2MB</div>
          </div>

          <button 
            @click="submitImport"
            :disabled="!selectedFile || isUploading"
            class="w-full mt-4 py-2.5 rounded-lg font-bold text-sm transition-colors flex items-center justify-center gap-2"
            :class="selectedFile ? 'bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-500/20' : 'bg-white/5 text-slate-500 cursor-not-allowed'"
          >
            <span v-if="isUploading" class="w-4 h-4 rounded-full border-2 border-white/20 border-t-white animate-spin"></span>
            {{ isUploading ? 'Memproses...' : 'Mulai Import' }}
          </button>
        </div>
      </div>

      <!-- FILTER & DATA TABLE -->
      <div class="lg:col-span-2">
        <div class="rounded-2xl border border-white/8 flex flex-col h-full" style="background: var(--card)">
          <!-- Toolbar -->
          <div class="p-5 border-b border-white/8 flex flex-wrap items-center justify-between gap-4 bg-white/2">
            <div class="font-bold text-sm">Daftar Siswa</div>
            
            <div class="flex items-center gap-3">
              <select 
                v-model="filterKelas"
                @change="applyFilter"
                class="bg-black/20 border border-white/10 rounded-lg px-3 py-2 text-xs font-medium text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
              >
                <option value="">Semua Kelas</option>
                <option v-for="cls in classes" :key="cls.id_kelas" :value="cls.id_kelas">
                  {{ cls.nama_kelas }}
                </option>
              </select>
            </div>
          </div>

          <!-- Table -->
          <div class="overflow-x-auto flex-1">
            <table class="w-full text-left">
              <thead>
                <tr class="border-b border-white/8 bg-black/20">
                  <th class="px-5 py-3 text-[10px] font-semibold text-slate-500 uppercase tracking-wider">NIS / NISN</th>
                  <th class="px-5 py-3 text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Nama Lengkap</th>
                  <th class="px-5 py-3 text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Kelas</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="students.data.length === 0">
                  <td colspan="3" class="px-5 py-10 text-center text-sm text-slate-500">
                    Belum ada data siswa ditemukan.
                  </td>
                </tr>
                <tr 
                  v-for="siswa in students.data" 
                  :key="siswa.id_siswa"
                  class="border-b border-white/4 hover:bg-white/5 transition-colors"
                >
                  <td class="px-5 py-3">
                    <div class="text-sm font-semibold">{{ siswa.nis }}</div>
                    <div class="text-[10px] text-slate-400 font-mono">{{ siswa.nisn || '-' }}</div>
                  </td>
                  <td class="px-5 py-3 text-sm">{{ siswa.nama_siswa }}</td>
                  <td class="px-5 py-3">
                    <span class="px-2 py-1 bg-white/5 rounded text-xs font-medium border border-white/10">
                      {{ siswa.clas?.nama_kelas || '-' }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination Info (Simplified) -->
          <div class="p-4 border-t border-white/8 flex items-center justify-between text-xs text-slate-400">
            <div>Menampilkan {{ students.data.length }} dari {{ students.total }} total siswa</div>
          </div>
        </div>
      </div>
    </div>

    <!-- PREVIEW MODAL (KARANTINA) -->
    <div v-if="showPreview" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-fade-in">
      <div class="bg-[#1a1b2e] rounded-2xl border border-white/10 shadow-2xl w-full max-w-5xl max-h-[90vh] flex flex-col overflow-hidden">
        <div class="p-5 border-b border-white/10 flex items-center justify-between bg-white/5">
          <h2 class="text-lg font-bold text-white flex items-center gap-2">
            <span>🛡️</span> Karantina Data Import
          </h2>
          <button @click="showPreview = false" class="text-slate-400 hover:text-white transition-colors text-2xl leading-none">&times;</button>
        </div>
        
        <div class="p-5 bg-indigo-500/10 border-b border-indigo-500/20 text-sm text-indigo-200 flex justify-between items-center">
          <div>
            Ada <strong>{{ previewData.filter(d => d.status === 'ERROR').length }}</strong> baris bermasalah dari total {{ previewData.length }} data. 
            Silakan perbaiki kolom NISN/NIS yang kosong atau duplikat secara langsung di tabel ini.
          </div>
        </div>

        <div class="flex-1 overflow-auto p-5">
          <table class="w-full text-left text-sm whitespace-nowrap">
            <thead>
              <tr class="border-b border-white/10 text-slate-400">
                <th class="py-3 px-4 font-semibold">ID Internal</th>
                <th class="py-3 px-4 font-semibold">NIS</th>
                <th class="py-3 px-4 font-semibold">NISN</th>
                <th class="py-3 px-4 font-semibold">Nama Lengkap</th>
                <th class="py-3 px-4 font-semibold">Kelas</th>
                <th class="py-3 px-4 font-semibold">Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(row, idx) in previewData" :key="idx" 
                  class="border-b border-white/5 transition-colors"
                  :class="row.status === 'ERROR' ? 'bg-red-500/10' : 'hover:bg-white/5'">
                <td class="py-2 px-4 text-slate-400">{{ row.id_internal || 'BARU' }}</td>
                <td class="py-2 px-4">
                  <input v-model="row.nis" type="text" 
                         class="bg-black/20 border border-white/10 rounded px-2 py-1 w-24 text-white focus:border-indigo-500 outline-none" 
                         @input="revalidateRow(idx)" />
                </td>
                <td class="py-2 px-4">
                  <input v-model="row.nisn" type="text" 
                         class="bg-black/20 border border-white/10 rounded px-2 py-1 w-28 text-white focus:border-indigo-500 outline-none" 
                         @input="revalidateRow(idx)" />
                </td>
                <td class="py-2 px-4 text-white">{{ row.nama }}</td>
                <td class="py-2 px-4 text-white">{{ row.kelas }}</td>
                <td class="py-2 px-4">
                  <span v-if="row.status === 'VALID'" class="px-2 py-1 bg-emerald-500/20 text-emerald-400 rounded text-xs font-bold border border-emerald-500/30">
                    Aman
                  </span>
                  <div v-else class="flex flex-col gap-1">
                    <span class="px-2 py-1 bg-red-500/20 text-red-400 rounded text-xs font-bold border border-red-500/30 inline-block w-max">
                      Error
                    </span>
                    <span class="text-[10px] text-red-300 max-w-xs whitespace-normal leading-tight">{{ row.error_msg }}</span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="p-5 border-t border-white/10 flex justify-end gap-3 bg-white/2">
          <button @click="showPreview = false" class="px-5 py-2 rounded-lg font-bold text-sm bg-white/5 hover:bg-white/10 transition-colors">Batal</button>
          <button 
            @click="confirmImport" 
            :disabled="hasErrors || isConfirming"
            class="px-5 py-2 rounded-lg font-bold text-sm transition-colors shadow-lg flex items-center gap-2"
            :class="(!hasErrors && !isConfirming) ? 'bg-emerald-600 hover:bg-emerald-500 text-white shadow-emerald-500/20' : 'bg-slate-600 text-slate-400 cursor-not-allowed'"
          >
            <span v-if="isConfirming" class="w-4 h-4 rounded-full border-2 border-white/20 border-t-white animate-spin"></span>
            {{ isConfirming ? 'Menyimpan...' : 'Simpan Permanen' }}
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import * as XLSX from 'xlsx';
import axios from 'axios';

const props = defineProps({
  students: Object,
  classes: Array,
  filters: Object
});

const filterKelas = ref(props.filters.id_kelas || '');
const selectedFile = ref(null);
const isUploading = ref(false);

const showPreview = ref(false);
const previewData = ref([]);
const isConfirming = ref(false);

const hasErrors = computed(() => {
  return previewData.value.some(row => row.status === 'ERROR');
});

// Navigation
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
  }
];

const downloadTemplate = () => {
  const ws = XLSX.utils.aoa_to_sheet([
    ['NIS', 'NISN', 'Nama Siswa', 'Nama Kelas'],
    ['20231001', '0012345678', 'Ahmad Budi Santoso', '10 TKJ 1'],
    ['20231002', '0012345679', 'Siti Aminah', '10 TKJ 1'],
    ['20231003', '0012345680', 'Cahyono', '10 RPL 2']
  ]);
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, "Template Siswa");
  XLSX.writeFile(wb, "Template_Import_Siswa.xlsx");
};

const handleFileUpload = (e) => {
  const file = e.target.files[0];
  if (!file) return;
  selectedFile.value = file;
  errorMessage.value = '';
};

const submitImport = () => {
  if (!selectedFile.value) return;
  isUploading.value = true;

  const reader = new FileReader();
  reader.onload = async (e) => {
    try {
      const data = new Uint8Array(e.target.result);
      const workbook = XLSX.read(data, { type: 'array' });
      const firstSheetName = workbook.SheetNames[0];
      const worksheet = workbook.Sheets[firstSheetName];
      
      const csv = XLSX.utils.sheet_to_csv(worksheet);
      const blob = new Blob([csv], { type: 'text/csv' });
      const fileToUpload = new File([blob], "converted.csv", { type: 'text/csv' });

      const formData = new FormData();
      formData.append('file', fileToUpload);

      const response = await axios.post('/admin/siswa/import', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
      
      isUploading.value = false;
      if (response.data.success) {
        previewData.value = response.data.preview_data;
        showPreview.value = true;
      }
    } catch (err) {
      isUploading.value = false;
      window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Gagal memproses file. Pastikan format benar.', type: 'error' } }));
    }
  };
  
  reader.readAsArrayBuffer(selectedFile.value);
};

const revalidateRow = (idx) => {
  const row = previewData.value[idx];
  if (!row) return;
  
  // Basic frontend validation for inline editing
  let errorMsg = [];
  let isError = false;

  if (row.id_internal) {
    if (!row.nisn) { isError = true; errorMsg.push("NISN tidak boleh kosong"); }
    if (!row.nis) { isError = true; errorMsg.push("NIS tidak boleh kosong"); }
  } else {
    // New student
    if (!row.nisn) { isError = true; errorMsg.push("NISN kosong untuk anak baru"); }
    if (!row.nis) { isError = true; errorMsg.push("NIS kosong untuk anak baru"); }
  }

  // Check duplicates within the previewData array itself
  if (row.nisn) {
    const dupNisn = previewData.value.filter((r, i) => i !== idx && r.nisn === row.nisn);
    if (dupNisn.length > 0) {
      isError = true;
      errorMsg.push("NISN duplikat dengan baris lain");
    }
  }

  if (row.nis) {
    const dupNis = previewData.value.filter((r, i) => i !== idx && r.nis === row.nis);
    if (dupNis.length > 0) {
      isError = true;
      errorMsg.push("NIS duplikat dengan baris lain");
    }
  }

  row.status = isError ? 'ERROR' : 'VALID';
  row.error_msg = errorMsg.join(', ');
};

const confirmImport = () => {
  if (hasErrors.value) return;
  isConfirming.value = true;

  router.post('/admin/siswa/import-confirm', {
    students: previewData.value
  }, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      showPreview.value = false;
      selectedFile.value = null;
      window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Data siswa berhasil disimpan!', type: 'success' } }));
    },
    onFinish: () => {
      isConfirming.value = false;
    }
  });
};

const applyFilter = () => {
  router.get('/admin/siswa', { id_kelas: filterKelas.value }, { preserveState: true, preserveScroll: true });
};

watch(() => props.filters, (newFilters) => {
  if (newFilters.id_kelas !== undefined) {
    filterKelas.value = newFilters.id_kelas;
  }
}, { deep: true });
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>
