<template>
  <Head title="Manajemen Wali Kelas" />

  <AppLayout
    title="Penugasan Wali Kelas"
    subtitle="Kelola guru pendamping untuk setiap rombongan belajar"
    :navigation="navigation"
  >
    <!-- Alert notification is handled globally by AppLayout toast -->

    <div class="flex justify-end mb-6">
      <button 
        @click="resetAll"
        :disabled="isResettingAll"
        class="px-4 py-2 bg-rose-500/10 text-rose-400 border border-rose-500/20 hover:bg-rose-500 hover:text-white transition-all rounded-xl text-sm font-bold flex items-center gap-2 disabled:opacity-50"
      >
        <span>⚠️</span> {{ isResettingAll ? 'Mereset...' : 'Reset Seluruh Wali Kelas' }}
      </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
      <div 
        v-for="cls in classes" 
        :key="cls.id_kelas"
        class="rounded-2xl border border-white/8 overflow-hidden relative group hover:border-indigo-500/50 transition-colors"
        style="background: var(--card)"
      >
        <div class="p-5 flex flex-col h-full">
          <!-- Class Badge -->
          <div class="flex items-center justify-between mb-4">
            <div class="text-xs font-bold px-2 py-1 rounded bg-indigo-500/20 text-indigo-400 tracking-wider">
              {{ cls.tingkat }} {{ cls.jurusan }}
            </div>
            <div class="w-2 h-2 rounded-full" :class="cls.id_guru_wali ? 'bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.6)]' : 'bg-red-500/50'"></div>
          </div>
          
          <h3 class="text-2xl font-black tracking-tight mb-6">{{ cls.nama_kelas }}</h3>
          
          <div class="mt-auto">
            <div class="flex items-center justify-between mb-2">
              <label class="block text-[10px] uppercase font-bold text-slate-500 tracking-wider">Wali Kelas</label>
              <button 
                v-if="cls.id_guru_wali" 
                @click="resetWaliKelas(cls)" 
                class="text-[10px] text-rose-400 hover:text-rose-300 transition-colors font-bold flex items-center gap-1"
                title="Hapus penugasan wali kelas"
              >
                <span>✕</span> Kosongkan
              </button>
            </div>
            <div class="relative">
              <select 
                v-model="cls.id_guru_wali" 
                @change="updateWaliKelas(cls)"
                class="w-full bg-black/20 border border-white/10 rounded-lg pl-3 pr-8 py-2.5 text-sm font-medium text-white appearance-none focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition-all hover:bg-black/40"
                :class="{'text-slate-400': !cls.id_guru_wali}"
              >
                <option :value="null">-- Belum Ditugaskan --</option>
                <option 
                  v-for="guru in teachers" 
                  :key="guru.id_guru" 
                  :value="guru.id_guru"
                  :disabled="isTeacherAssigned(guru.id_guru, cls.id_kelas)"
                >
                  {{ guru.nama_guru }} {{ isTeacherAssigned(guru.id_guru, cls.id_kelas) ? '(Sudah Ditugaskan)' : '' }}
                </option>
              </select>
              <!-- custom dropdown arrow -->
              <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-slate-500 text-xs">▼</div>
            </div>
          </div>
        </div>
        
        <!-- Loading overlay -->
        <div v-if="savingId === cls.id_kelas" class="absolute inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-10 rounded-2xl">
          <div class="w-6 h-6 rounded-full border-2 border-indigo-500 border-t-transparent animate-spin"></div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  classes: Array,
  teachers: Array
});

const savingId = ref(null);

const isTeacherAssigned = (guruId, currentClassId) => {
  if (!guruId) return false;
  return props.classes.some(c => c.id_guru_wali === guruId && c.id_kelas !== currentClassId);
};

// Same navigation structure
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

const updateWaliKelas = (cls) => {
  savingId.value = cls.id_kelas;

  router.post('/admin/wali-kelas', {
    id_kelas: cls.id_kelas,
    id_guru_wali: cls.id_guru_wali
  }, {
    preserveScroll: true,
    onFinish: () => {
      savingId.value = null;
    }
  });
};

const resetWaliKelas = (cls) => {
  if(confirm(`Yakin ingin mengosongkan wali kelas untuk ${cls.nama_kelas}?`)) {
    cls.id_guru_wali = null;
    updateWaliKelas(cls);
  }
};

const isResettingAll = ref(false);
const resetAll = () => {
  if (confirm("⚠️ PERINGATAN!\nApakah Anda yakin ingin MENGOSONGKAN SELURUH penugasan Wali Kelas di semua kelas?\nAksi ini tidak dapat dibatalkan!")) {
    isResettingAll.value = true;
    router.post('/admin/wali-kelas/reset', {}, {
      onFinish: () => {
        isResettingAll.value = false;
      }
    });
  }
};
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
