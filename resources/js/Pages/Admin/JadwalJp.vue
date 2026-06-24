<template>
  <Head title="Manajemen Jadwal JP" />

  <AppLayout
    title="Jadwal Jam Pelajaran"
    subtitle="Konfigurasi waktu belajar dan istirahat untuk semua shift"
    :navigation="navigation"
  >
    <template #topbar-actions>
      <button 
        @click="saveChanges"
        :disabled="isSaving"
        class="px-4 py-2 text-sm font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white transition-colors flex items-center gap-2 disabled:opacity-50"
      >
        <span v-if="isSaving" class="w-4 h-4 rounded-full border-2 border-white/20 border-t-white animate-spin"></span>
        <span v-else>💾</span>
        {{ isSaving ? 'Menyimpan...' : 'Simpan Perubahan' }}
      </button>
    </template>

    <!-- ALERT NOTIFICATION -->
    <transition name="fade">
      <div v-if="successMessage" class="mb-6 p-4 rounded-xl border border-green-500/20 bg-green-500/10 text-green-400 text-sm flex items-center gap-3">
        <span>✅</span> {{ successMessage }}
      </div>
    </transition>
    <transition name="fade">
      <div v-if="errorMessage" class="mb-6 p-4 rounded-xl border border-red-500/20 bg-red-500/10 text-red-400 text-sm flex items-center gap-3">
        <span>⚠️</span> {{ errorMessage }}
      </div>
    </transition>

    <!-- SHIFT TABS -->
    <div class="flex items-center gap-2 mb-6">
      <button 
        v-for="shift in ['PAGI', 'SIANG']" 
        :key="shift"
        @click="activeShift = shift"
        class="px-5 py-2.5 rounded-xl text-sm font-bold transition-all"
        :class="activeShift === shift 
          ? 'bg-indigo-500 text-white shadow-lg shadow-indigo-500/20' 
          : 'bg-white/5 text-slate-400 hover:bg-white/10 hover:text-white'"
      >
        Shift {{ shift }}
      </button>
    </div>

    <!-- MAIN GRID CARDS PER DAY -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
      <div 
        v-for="(daySchedules, hari) in schedules[activeShift]" 
        :key="hari"
        class="rounded-2xl border border-white/8 overflow-hidden flex flex-col relative group"
        style="background: var(--card)"
      >
        <!-- Card Header -->
        <div class="px-5 py-4 border-b border-white/8 flex items-center justify-between bg-white/2 group-hover:bg-white/5 transition-colors">
          <div class="font-bold tracking-wider text-sm">{{ hari }}</div>
          <div class="text-[10px] uppercase font-semibold text-slate-500 tracking-widest px-2 py-1 rounded bg-black/20">
            {{ daySchedules.length }} Sesi
          </div>
        </div>

        <!-- Table Content -->
        <div class="flex-1 p-2">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr>
                <th class="px-3 py-2 text-[10px] font-semibold text-slate-600 uppercase">Jam Ke-</th>
                <th class="px-3 py-2 text-[10px] font-semibold text-slate-600 uppercase">Mulai</th>
                <th class="px-3 py-2 text-[10px] font-semibold text-slate-600 uppercase">Selesai</th>
              </tr>
            </thead>
            <tbody>
              <tr 
                v-for="item in daySchedules" 
                :key="item.id"
                class="group/row hover:bg-white/5 transition-colors rounded-lg"
                :class="{'opacity-80': !item.is_kbm}"
              >
                <td class="px-3 py-1.5">
                  <div v-if="item.is_kbm" class="w-6 h-6 rounded bg-indigo-500/10 text-indigo-400 flex items-center justify-center text-xs font-bold">
                    {{ item.jam_ke }}
                  </div>
                  <div v-else class="text-xs font-semibold text-orange-400 flex items-center gap-1">
                    ☕ {{ item.keterangan || 'Break' }}
                  </div>
                </td>
                <td class="px-3 py-1.5">
                  <input 
                    v-if="formData[item.id]"
                    type="time" 
                    step="60"
                    v-model="formData[item.id].waktu_mulai"
                    @change="markDirty(item.id)"
                    class="bg-black/20 border border-white/10 rounded-md px-2 py-1 text-xs font-mono text-slate-300 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all w-24"
                    :class="{'border-yellow-500/50 text-yellow-300': isDirty(item.id)}"
                  />
                </td>
                <td class="px-3 py-1.5">
                  <input 
                    v-if="formData[item.id]"
                    type="time" 
                    step="60"
                    v-model="formData[item.id].waktu_selesai"
                    @change="markDirty(item.id)"
                    class="bg-black/20 border border-white/10 rounded-md px-2 py-1 text-xs font-mono text-slate-300 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all w-24"
                    :class="{'border-yellow-500/50 text-yellow-300': isDirty(item.id)}"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </AppLayout>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  schedules: Object
});

const activeShift = ref('PAGI');
const isSaving = ref(false);
const successMessage = ref('');
const errorMessage = ref('');

// Flat form data mapped by item ID
const formData = reactive({});
const dirtyItems = ref(new Set());

// Navigation sama seperti di Dashboard
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

// Initialize form data synchronously before mount
['PAGI', 'SIANG'].forEach(shift => {
  if (props.schedules && props.schedules[shift]) {
    Object.keys(props.schedules[shift]).forEach(hari => {
      props.schedules[shift][hari].forEach(item => {
        // Format time slightly to ensure HH:mm:ss for input type="time"
        formData[item.id] = {
          id: item.id,
          waktu_mulai: item.waktu_mulai,
          waktu_selesai: item.waktu_selesai,
          _original_mulai: item.waktu_mulai,
          _original_selesai: item.waktu_selesai,
        };
      });
    });
  }
});

const markDirty = (id) => {
  dirtyItems.value.add(id);
};

const isDirty = (id) => {
  if (!formData[id]) return false;
  return formData[id].waktu_mulai !== formData[id]._original_mulai || 
         formData[id].waktu_selesai !== formData[id]._original_selesai;
};

const saveChanges = () => {
  // Collect only changed items to send to server
  const itemsToSave = [];
  dirtyItems.value.forEach(id => {
    if (isDirty(id)) {
      itemsToSave.push({
        id: formData[id].id,
        waktu_mulai: formData[id].waktu_mulai,
        waktu_selesai: formData[id].waktu_selesai,
      });
    }
  });

  if (itemsToSave.length === 0) {
    successMessage.value = "Tidak ada perubahan yang perlu disimpan.";
    setTimeout(() => successMessage.value = '', 3000);
    return;
  }

  isSaving.value = true;
  successMessage.value = '';
  errorMessage.value = '';

  router.post('/admin/jadwal-jp', { schedules: itemsToSave }, {
    preserveScroll: true,
    onSuccess: (page) => {
      isSaving.value = false;
      if (page.props.flash?.success) {
        successMessage.value = page.props.flash.success;
      } else {
        successMessage.value = 'Jadwal berhasil diperbarui!';
      }
      // Reset dirty state
      itemsToSave.forEach(item => {
        formData[item.id]._original_mulai = item.waktu_mulai;
        formData[item.id]._original_selesai = item.waktu_selesai;
      });
      dirtyItems.value.clear();
      
      setTimeout(() => successMessage.value = '', 4000);
    },
    onError: (errors) => {
      isSaving.value = false;
      errorMessage.value = "Terjadi kesalahan saat menyimpan data. Cek kembali format waktu.";
      console.error(errors);
      setTimeout(() => errorMessage.value = '', 5000);
    }
  });
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
