<template>
  <Head :title="title" />

  <AppLayout
    :title="title"
    :subtitle="description"
    :navigation="navigation"
  >
    <div class="flex flex-col items-center justify-center h-[60vh] text-center border border-white/10 rounded-2xl p-10 bg-[#0B0F19]">
      <div class="text-6xl mb-6">🚧</div>
      <h2 class="text-2xl font-black text-white mb-2">Under Construction</h2>
      <p class="text-slate-400 max-w-md">{{ description }}</p>
      
      <a href="/admin/dashboard" class="mt-8 px-6 py-2.5 rounded-xl font-bold text-sm bg-indigo-600 hover:bg-indigo-500 text-white transition-colors shadow-lg shadow-indigo-500/20">
        Kembali ke Dashboard
      </a>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  title: String,
  description: String
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
  }
];
</script>
