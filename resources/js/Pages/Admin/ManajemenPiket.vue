<template>
  <AppLayout
    title="Manajemen Guru Piket"
    subtitle="Atur penugasan shift piket untuk guru setiap harinya."
    :navigation="navigation"
  >
    <div class="mb-6 flex justify-between items-center">
      <div>
        <h2 class="text-2xl font-black text-white tracking-tight">Manajemen Guru Piket</h2>
        <p class="text-slate-400 text-sm mt-1">Atur penugasan shift piket per hari.</p>
      </div>
      <button 
        @click="updateSemuaPiket"
        class="bg-indigo-600 hover:bg-indigo-500 text-white px-5 py-2.5 rounded-xl font-bold transition-colors shadow-lg shadow-indigo-500/30 flex items-center gap-2"
      >
        <span>💾</span> Simpan Semua Jadwal
      </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div v-for="hari in hariList" :key="hari" class="bg-[#18181B] border border-white/10 rounded-2xl overflow-hidden shadow-2xl flex flex-col">
        <!-- Card Header -->
        <div class="px-5 py-4 border-b border-white/10 bg-white/5 flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500/20 to-violet-600/20 border border-indigo-500/30 flex items-center justify-center text-indigo-400 font-black">
            {{ hari.substring(0,3) }}
          </div>
          <h3 class="text-lg font-bold text-white tracking-wide">{{ hari }}</h3>
        </div>
        
        <!-- Card Body -->
        <div class="p-5 flex-1 flex flex-col gap-4">
          <!-- Piket Pagi -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Shift Pagi</label>
            <select v-model="assignments.PAGI[hari]" class="w-full bg-white/5 border border-white/10 text-white rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all appearance-none">
              <option :value="null">-- Kosong --</option>
              <option v-for="guru in teachers" :key="guru.id_guru" :value="guru.id_guru">
                {{ guru.nama_guru }}
              </option>
            </select>
          </div>
          <!-- Piket Siang -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Shift Siang</label>
            <select v-model="assignments.SIANG[hari]" class="w-full bg-white/5 border border-white/10 text-white rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all appearance-none">
              <option :value="null">-- Kosong --</option>
              <option v-for="guru in teachers" :key="guru.id_guru" :value="guru.id_guru">
                {{ guru.nama_guru }}
              </option>
            </select>
          </div>
        </div>
      </div>
    </div>

  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  teachers: Array,
  initialAssignments: Object,
});

const page = usePage();
const hariList = ['SENIN', 'SELASA', 'RABU', 'KAMIS', 'JUMAT', 'SABTU'];

// Clone assignments agar reaktif
const assignments = ref(JSON.parse(JSON.stringify(props.initialAssignments)));

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
      { href: '/admin/piket', icon: '👮', label: 'Manajemen Piket' },
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

const updateSemuaPiket = () => {
  router.post('/admin/piket', {
    assignments: assignments.value,
  }, {
    preserveScroll: true,
  });
};
</script>
