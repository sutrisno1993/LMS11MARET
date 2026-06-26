<template>
  <Head title="Rekap Kehadiran Guru" />

  <AppLayout
    title="Rekap Kehadiran Guru"
    :subtitle="`Laporan Harian - Tanggal: ${tanggal}`"
    :navigation="navigation"
  >
    <div class="mb-6 flex gap-4 items-end">
      <div class="flex-1 max-w-xs">
        <label class="block text-xs font-bold text-slate-400 mb-1">Pilih Tanggal</label>
        <input type="date" v-model="filterTanggal" @change="reloadLaporan" class="w-full bg-[#18181B] border border-white/10 rounded-lg text-white px-3 py-2 text-sm focus:outline-none focus:border-indigo-500" />
      </div>
      <button @click="exportExcel" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-lg text-sm font-bold text-white transition-colors">
        📥 Export Excel
      </button>
    </div>

    <div class="bg-[#18181B] border border-white/10 rounded-2xl overflow-hidden shadow-2xl">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-white/5 border-b border-white/10">
              <th class="px-6 py-4 text-xs font-semibold text-slate-300 uppercase tracking-wider w-10">No</th>
              <th class="px-6 py-4 text-xs font-semibold text-slate-300 uppercase tracking-wider">Nama Guru</th>
              <th class="px-6 py-4 text-xs font-semibold text-slate-300 uppercase tracking-wider">Shift Piket</th>
              <th class="px-6 py-4 text-xs font-semibold text-slate-300 uppercase tracking-wider">Status</th>
              <th class="px-6 py-4 text-xs font-semibold text-slate-300 uppercase tracking-wider">Waktu Datang</th>
              <th class="px-6 py-4 text-xs font-semibold text-slate-300 uppercase tracking-wider">Keterangan</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/10">
            <tr v-for="(att, index) in attendances" :key="att.id" class="hover:bg-white/5 transition-colors">
              <td class="px-6 py-4 text-slate-400 text-sm font-mono">{{ index + 1 }}</td>
              <td class="px-6 py-4">
                <div class="font-bold text-white">{{ att.teacher?.nama_guru || 'Unknown' }}</div>
              </td>
              <td class="px-6 py-4 text-slate-300 text-sm">
                {{ att.shift }}
              </td>
              <td class="px-6 py-4">
                <span class="px-2 py-1 rounded text-xs font-bold"
                      :class="{
                        'bg-green-500/20 text-green-400': att.status === 'HADIR',
                        'bg-yellow-500/20 text-yellow-400': att.status === 'SAKIT',
                        'bg-blue-500/20 text-blue-400': att.status === 'IZIN',
                        'bg-slate-500/20 text-slate-400': att.status === 'LAINNYA'
                      }">
                  {{ att.status }}
                </span>
              </td>
              <td class="px-6 py-4 text-slate-300 text-sm font-mono">
                {{ att.waktu_datang || '-' }}
              </td>
              <td class="px-6 py-4 text-slate-400 text-sm">
                {{ att.keterangan_jam || '-' }}
              </td>
            </tr>
            <tr v-if="attendances.length === 0">
              <td colspan="6" class="px-6 py-10 text-center text-slate-400 italic">Belum ada data kehadiran untuk tanggal ini.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  tanggal: String,
  attendances: Array,
});

const filterTanggal = ref(props.tanggal);

const reloadLaporan = () => {
  router.get('/admin/kehadiran-guru', { tanggal: filterTanggal.value }, { preserveState: true });
};

const exportExcel = () => {
  window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Fitur Export Excel akan segera hadir.', type: 'info' } }));
};

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
      { href: '/admin/kehadiran-guru', icon: '📅', label: 'Kehadiran Guru' },
      { href: '/admin/jurnal', icon: '📓', label: 'Jurnal Tatap Muka' },
      { href: '/admin/laporan-kasus-siswa', icon: '⚡', label: 'Laporan Kasus' },
    ],
  },
];
</script>
