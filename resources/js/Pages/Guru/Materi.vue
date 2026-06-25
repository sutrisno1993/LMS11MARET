<template>
  <Head title="Materi Pembelajaran (Bahan Ajar)" />

  <AppLayout
    title="Materi Pembelajaran"
    subtitle="Kelola dan unggah berkas bahan ajar untuk kelas Anda"
    :navigation="navigation"
  >
    <template #topbar-actions>
      <button 
        @click="showUploadModal = true" 
        class="flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition-all shadow-lg shadow-indigo-500/20"
      >
        <span>📤</span> Unggah Materi
      </button>
    </template>

    <div class="space-y-6 max-w-5xl mx-auto">
      
      <!-- List Materi Table/Grid -->
      <div class="rounded-2xl border border-white/8 overflow-hidden" style="background: var(--card)">
        <div class="px-5 py-4 border-b border-white/8 flex items-center justify-between">
          <h3 class="font-bold text-sm">Daftar Materi Yang Diunggah</h3>
          <div class="text-xs text-slate-500 font-mono">{{ materials.length }} berkas</div>
        </div>

        <!-- Table View -->
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-white/8 text-[11px] text-slate-600 uppercase tracking-widest font-bold">
                <th class="px-6 py-4">Judul & Mapel</th>
                <th class="px-6 py-4">Kelas</th>
                <th class="px-6 py-4">File</th>
                <th class="px-6 py-4">Ukuran</th>
                <th class="px-6 py-4">Tanggal Unggah</th>
                <th class="px-6 py-4 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5 text-xs">
              <tr v-for="item in materials" :key="item.id_materi" class="hover:bg-white/2 transition-colors">
                <td class="px-6 py-4">
                  <div class="font-bold text-white text-sm">{{ item.judul }}</div>
                  <div class="text-[10px] text-slate-400 mt-0.5">{{ item.subject?.nama_mapel || '-' }}</div>
                  <div v-if="item.deskripsi" class="text-[10px] text-slate-600 mt-1 italic max-w-xs truncate">{{ item.deskripsi }}</div>
                </td>
                <td class="px-6 py-4">
                  <span class="px-2 py-1 rounded bg-indigo-500/10 text-indigo-400 font-semibold text-[10px] border border-indigo-500/20">
                    {{ item.clas?.nama_kelas || '-' }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <div class="flex items-center gap-2 max-w-xs">
                    <span class="text-lg">📄</span>
                    <span class="truncate font-mono text-[11px] text-slate-400" :title="item.file_name">
                      {{ item.file_name }}
                    </span>
                  </div>
                </td>
                <td class="px-6 py-4 font-mono text-slate-400">
                  {{ formatBytes(item.file_size) }}
                </td>
                <td class="px-6 py-4 text-slate-400">
                  {{ formatDate(item.created_at) }}
                </td>
                <td class="px-6 py-4 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <a 
                      :href="`/guru/materi/${item.id_materi}/download`" 
                      class="p-2 bg-white/5 hover:bg-white/10 rounded-lg text-slate-400 hover:text-white transition-colors"
                      title="Unduh Berkas"
                    >
                      ⬇️
                    </a>
                    <button 
                      @click="deleteMaterial(item.id_materi)" 
                      class="p-2 bg-red-500/10 hover:bg-red-500/20 rounded-lg text-red-400 hover:text-red-300 transition-colors"
                      title="Hapus Berkas"
                    >
                      🗑️
                    </button>
                  </div>
                </td>
              </tr>

              <!-- Empty State -->
              <tr v-if="materials.length === 0">
                <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                  <div class="text-3xl mb-3">📁</div>
                  <div class="font-bold">Belum ada materi pembelajaran</div>
                  <div class="text-[11px] text-slate-600 mt-1">Tekan tombol Unggah Materi untuk membagikan berkas bahan ajar ke siswa.</div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Upload Modal (Glassmorphism Overlay) -->
      <div v-if="showUploadModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
        <div 
          class="w-full max-w-lg rounded-2xl border border-white/10 p-6 space-y-5 shadow-2xl relative"
          style="background: var(--card)"
        >
          <!-- Close button -->
          <button 
            @click="showUploadModal = false" 
            class="absolute top-4 right-4 text-slate-400 hover:text-white text-lg font-bold"
          >
            ✕
          </button>

          <div>
            <h3 class="text-base font-bold text-white">Unggah Materi Baru</h3>
            <p class="text-xs text-slate-500 mt-0.5">Berkas akan langsung dibagikan ke siswa di kelas pilihan.</p>
          </div>

          <form @submit.prevent="submitUpload" class="space-y-4">
            
            <!-- Judul -->
            <div class="space-y-1.5">
              <label class="text-[10px] text-slate-400 uppercase tracking-widest font-semibold">Judul Materi</label>
              <input 
                v-model="form.judul" 
                type="text" 
                required 
                placeholder="Contoh: Modul 1 Jaringan Dasar"
                class="w-full bg-white/5 border border-white/8 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-600 outline-none focus:border-indigo-500/50"
              />
            </div>

            <!-- Deskripsi -->
            <div class="space-y-1.5">
              <label class="text-[10px] text-slate-400 uppercase tracking-widest font-semibold">Deskripsi / Catatan Tambahan (Opsional)</label>
              <textarea 
                v-model="form.deskripsi" 
                rows="3"
                placeholder="Contoh: Baca halaman 5-20 dan buat rangkuman materi..."
                class="w-full bg-white/5 border border-white/8 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-600 outline-none focus:border-indigo-500/50 resize-none"
              ></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <!-- Kelas -->
              <div class="space-y-1.5">
                <label class="text-[10px] text-slate-400 uppercase tracking-widest font-semibold">Kelas Tujuan</label>
                <select 
                  v-model="form.id_kelas" 
                  required 
                  class="w-full bg-white/5 border border-white/8 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-600 outline-none focus:border-indigo-500/50"
                >
                  <option value="" disabled>Pilih Kelas...</option>
                  <option v-for="c in kelasList" :key="c.id_kelas" :value="c.id_kelas">{{ c.nama_kelas }}</option>
                </select>
              </div>

              <!-- Mapel -->
              <div class="space-y-1.5">
                <label class="text-[10px] text-slate-400 uppercase tracking-widest font-semibold">Mata Pelajaran</label>
                <select 
                  v-model="form.id_mapel" 
                  required 
                  class="w-full bg-white/5 border border-white/8 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-600 outline-none focus:border-indigo-500/50"
                >
                  <option value="" disabled>Pilih Mapel...</option>
                  <option v-for="m in mapelList" :key="m.id_mapel" :value="m.id_mapel">{{ m.nama_mapel }}</option>
                </select>
              </div>
            </div>

            <!-- File Upload Input -->
            <div class="space-y-1.5">
              <label class="text-[10px] text-slate-400 uppercase tracking-widest font-semibold">Berkas Bahan Ajar (Maks 20MB)</label>
              <div 
                class="border-2 border-dashed border-white/8 hover:border-indigo-500/50 rounded-2xl p-6 text-center transition-colors relative cursor-pointer"
                @click="$refs.fileInput.click()"
              >
                <input 
                  type="file" 
                  ref="fileInput" 
                  @change="handleFileChange" 
                  class="hidden" 
                  required
                />
                <div v-if="!selectedFile" class="space-y-2">
                  <div class="text-3xl">📁</div>
                  <div class="text-xs font-bold text-slate-300">Pilih berkas dari komputer Anda</div>
                  <div class="text-[10px] text-slate-600">Mendukung PDF, PPT, Doc, Zip, Image, dll.</div>
                </div>
                <div v-else class="space-y-2">
                  <div class="text-3xl text-indigo-400">📄</div>
                  <div class="text-xs font-bold text-white truncate max-w-xs mx-auto">{{ selectedFile.name }}</div>
                  <div class="text-[10px] text-slate-500 font-mono">{{ formatBytes(selectedFile.size) }}</div>
                  <div class="text-[10px] text-indigo-400 underline">Ganti berkas</div>
                </div>
              </div>
            </div>

            <!-- Submit -->
            <div class="flex gap-3 pt-2">
              <button 
                type="button" 
                @click="showUploadModal = false" 
                class="flex-1 py-3 rounded-xl border border-white/8 text-xs font-bold text-slate-400 hover:text-white hover:bg-white/5 transition-all"
              >
                Batal
              </button>
              <button 
                type="submit" 
                :disabled="isUploading"
                class="flex-1 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-xs font-bold text-white transition-all shadow-lg shadow-indigo-500/20 disabled:opacity-50"
              >
                {{ isUploading ? '⏳ Mengunggah...' : '🚀 Unggah Sekarang' }}
              </button>
            </div>

          </form>
        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  kelasList: Array,
  mapelList: Array,
  materials: Array,
});

const showUploadModal = ref(false);
const isUploading = ref(false);
const selectedFile = ref(null);
const fileInput = ref(null);

const form = ref({
  judul: '',
  deskripsi: '',
  id_kelas: '',
  id_mapel: '',
});

const handleFileChange = (e) => {
  const files = e.target.files;
  if (files.length > 0) {
    selectedFile.value = files[0];
  }
};

const submitUpload = () => {
  if (!selectedFile.value) return;

  isUploading.value = true;

  const data = new FormData();
  data.append('judul', form.value.judul);
  data.append('deskripsi', form.value.deskripsi);
  data.append('id_kelas', form.value.id_kelas);
  data.append('id_mapel', form.value.id_mapel);
  data.append('file', selectedFile.value);

  router.post('/guru/materi', data, {
    onSuccess: () => {
      showUploadModal.value = false;
      form.value = { judul: '', deskripsi: '', id_kelas: '', id_mapel: '' };
      selectedFile.value = null;
    },
    onFinish: () => {
      isUploading.value = false;
    }
  });
};

const deleteMaterial = (id) => {
  if (confirm('Apakah Anda yakin ingin menghapus materi ini? Berkas akan dihapus permanen dari server.')) {
    router.delete(`/guru/materi/${id}`);
  }
};

// Utilities
const formatBytes = (bytes, decimals = 2) => {
  if (!bytes || bytes === 0) return '0 Bytes';
  const k = 1024;
  const dm = decimals < 0 ? 0 : decimals;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
};

const formatDate = (dateStr) => {
  if (!dateStr) return '-';
  const d = new Date(dateStr);
  return d.toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const navigation = [
  {
    label: 'KBM (Kegiatan Belajar Mengajar)',
    items: [
      { href: '/guru/dashboard', icon: '📊', label: 'Dashboard' },
      { href: '/guru/jadwal', icon: '📅', label: 'Jadwal Mengajar' },
      { href: '/guru/materi', icon: '📁', label: 'Materi Pembelajaran', badge: 'New' },
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
</script>
