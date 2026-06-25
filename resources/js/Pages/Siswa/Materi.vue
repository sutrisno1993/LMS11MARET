<template>
  <Head title="Bahan Ajar / Materi Pembelajaran" />

  <AppLayout
    title="Materi Pembelajaran"
    subtitle="Unduh berkas bahan ajar yang dibagikan oleh guru mata pelajaran Anda"
    :navigation="navigation"
  >
    <div class="space-y-6 max-w-5xl mx-auto">
      
      <!-- Filter / Grouping -->
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-base font-bold">Daftar Bahan Ajar</h2>
          <p class="text-xs text-slate-500">Silakan pilih mapel untuk melihat berkas materi.</p>
        </div>
        
        <!-- Filter Mapel Select -->
        <div class="w-48">
          <select 
            v-model="selectedMapel" 
            class="w-full bg-white/5 border border-white/8 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-600 outline-none focus:border-indigo-500/50"
          >
            <option value="ALL">Semua Mapel</option>
            <option v-for="mapel in mapelList" :key="mapel" :value="mapel">{{ mapel }}</option>
          </select>
        </div>
      </div>

      <!-- Materials List -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        
        <!-- Material Card -->
        <div 
          v-for="item in filteredMaterials" 
          :key="item.id_materi"
          class="rounded-2xl border border-white/8 p-5 flex flex-col justify-between space-y-4 hover:border-indigo-500/30 transition-all duration-300 group"
          style="background: var(--card)"
        >
          <div class="space-y-3">
            <!-- Header Card: Badge mapel & size -->
            <div class="flex items-center justify-between">
              <span class="px-2 py-0.5 rounded bg-indigo-500/10 text-indigo-400 font-semibold text-[10px] border border-indigo-500/20">
                📚 {{ item.subject?.nama_mapel || '-' }}
              </span>
              <span class="text-[10px] text-slate-500 font-mono">
                💾 {{ formatBytes(item.file_size) }}
              </span>
            </div>

            <!-- Judul & Deskripsi -->
            <div class="space-y-1">
              <h3 class="font-bold text-white text-sm group-hover:text-indigo-400 transition-colors">
                {{ item.judul }}
              </h3>
              <p class="text-xs text-slate-400 line-clamp-3">
                {{ item.deskripsi || 'Tidak ada deskripsi tambahan.' }}
              </p>
            </div>
          </div>

          <!-- Footer Card: Upload details & Download button -->
          <div class="flex items-center justify-between pt-3 border-t border-white/5">
            <div class="text-[10px] text-slate-600 space-y-0.5">
              <div>👨‍🏫 {{ item.teacher?.nama_guru || 'Unknown' }}</div>
              <div>📅 {{ formatDate(item.created_at) }}</div>
            </div>
            
            <a 
              :href="`/siswa/materi/${item.id_materi}/download`"
              class="flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition-all group-hover:scale-105"
            >
              📥 Unduh
            </a>
          </div>

        </div>

        <!-- Empty State -->
        <div 
          v-if="filteredMaterials.length === 0" 
          class="col-span-full rounded-2xl border border-white/8 p-12 text-center text-slate-500"
          style="background: var(--card)"
        >
          <div class="text-4xl mb-3">🏖️</div>
          <div class="font-bold">Tidak ada bahan ajar</div>
          <div class="text-[11px] text-slate-600 mt-1">Guru belum membagikan berkas materi untuk kelas atau mapel pilihan Anda.</div>
        </div>

      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  materials: Array,
});

const selectedMapel = ref('ALL');

// Get unique subjects list for filter dropdown
const mapelList = computed(() => {
  if (!props.materials) return [];
  const list = props.materials.map(item => item.subject?.nama_mapel).filter(Boolean);
  return [...new Set(list)];
});

// Filtered materials computed list
const filteredMaterials = computed(() => {
  if (!props.materials) return [];
  if (selectedMapel.value === 'ALL') return props.materials;
  return props.materials.filter(item => item.subject?.nama_mapel === selectedMapel.value);
});

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
  return d.toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric' });
};

const navigation = [
  {
    label: 'Akademik',
    items: [
      { href: '/siswa/dashboard', icon: '🏠', label: 'Beranda' },
      { href: '/siswa/scan-qr', icon: '📷', label: 'Scan QR Presensi', badge: 'Live' },
      { href: '/siswa/materi', icon: '📁', label: 'Materi Belajar', badge: 'New' },
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
</script>
